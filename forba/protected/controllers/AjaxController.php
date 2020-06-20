<?php
if (!isset($_SESSION)) { session_start(); }

class AjaxController extends CController
{
	public $code=2;
	public $msg;
	public $details;
	public $data;
	
	public function __construct()
	{
		$this->data=$_POST;	
	}
	
	public function init()
	{			
		 // set website timezone
		 $website_timezone=Yii::app()->functions->getOptionAdmin("website_timezone" );		 
		 if (!empty($website_timezone)){		 	
		 	Yii::app()->timeZone=$website_timezone;
		 }	
		 
		 $customer_timezone=getOption( Driver::getUserId(), 'customer_timezone');
		 if (!empty($customer_timezone)){
		 	Yii::app()->timeZone=$customer_timezone;
		 }
		 
		 if(isset($this->data['language'])){
		 	Yii::app()->language=$this->data['language'];
		 }	 
		 if(isset($_GET['language'])){
		 	Yii::app()->language=$_GET['language'];
		 }
		 unset($this->data['language']);	
	}
	
	public function beforeAction($action)
	{
		$action=Yii::app()->controller->action->id;
		$continue=true;
		
		$action=strtolower($action);
		if($action=="login" || $action=="forgotpassword" || $action=="resetpassword"){
	   	   $continue=false;
	    }	    
	    
	    if($continue){	  
			if (!Driver::islogin()){
				$this->msg=Driver::t("Authentication failed");
				$this->jsonResponse();
			}
	    }
		return true;
	}
	
	private function jsonResponse()
	{
		$resp=array('code'=>$this->code,'msg'=>$this->msg,'details'=>$this->details);
		echo CJSON::encode($resp);
		Yii::app()->end();
	}
	
	private function otableNodata()
	{
		if (isset($_GET['sEcho'])){
			$feed_data['sEcho']=$_GET['sEcho'];
		} else $feed_data['sEcho']=1;	   
		     
        $feed_data['iTotalRecords']=0;
        $feed_data['iTotalDisplayRecords']=0;
        $feed_data['aaData']=array();		
        echo json_encode($feed_data);
    	die();
	}

	private function otableOutput($feed_data='')
	{
	  echo json_encode($feed_data);
	  die();
    }    
    
	public function actionLogin()
	{				
		$req=array(
		  'email_address'=>Driver::t("Email address is required"),
		  'password'=>Driver::t("password is required"),
		);
		$Validator=new Validator;
		$Validator->required($req,$this->data);
		if($Validator->validate()){
			
			AdminFunctions::CheckCustomerExpiry();
			
			if ( $res = Driver::Login(trim($this->data['email_address']), trim($this->data['password']) )){
				
				if($res['status']=="active" || $res['status']=="expired"){
					
					$_SESSION['kartero']=$res;
					$this->code=1;
					$this->msg=t("Login Successful");					
					$this->details=Yii::app()->createUrl('/app/dashboard');
															
					if(isset($this->data['remember'])){
						Yii::app()->request->cookies['kt_username'] = new CHttpCookie('kt_username', $this->data['email_address']);
						$runtime_path=Yii::getPathOfAlias('webroot')."/protected/runtime";								
						if(!file_exists($runtime_path)){
							mkdir($runtime_path,0777);
						}
						
						$encryption_type=Yii::app()->params->encryption_type;
						if(empty($encryption_type)){
							$encryption_type='yii';
						}
						
						if ($encryption_type=="yii"){
							try {
								$password=Yii::app()->securityManager->encrypt( $this->data['password'] );					
							    Yii::app()->request->cookies['kt_password'] = new CHttpCookie('kt_password',$password);
							} catch (Exception $e){							
								$this->msg=t("Path is not writable by the server")." $runtime_path";
								$this->code=2;							
							}
						}
						
					} else {
						unset(Yii::app()->request->cookies['kt_username']);
			            unset(Yii::app()->request->cookies['kt_password']);
					}
					
				} else $this->msg=t("Login failed. your account is")." ".$res['status'];
				
			} else $this->msg=t("Login failed. either username and password is invalid");
		} else $this->msg=$Validator->getErrorAsHTML();
		$this->jsonResponse();
	}
	
	public function actionCreateTeam()
	{
		$params=array(
		  'team_name'=>$this->data['team_name'],
		  'location_accuracy'=>$this->data['location_accuracy'],		  
		  'status'=>$this->data['status'],
		  'date_created'=>AdminFunctions::dateNow(),
		  'ip_address'=>$_SERVER['REMOTE_ADDR']
		);		
		if(!isset($this->data['id'])){
			$this->data['id']='';
		}
		
		$team_member=isset($this->data['team_member'])?json_encode($this->data['team_member']):'';
				
		$params['customer_id']=Driver::getUserId();
		
		if(!Driver::islogin()){
			$this->msg=Driver::t("Sorry but your session has expired");
			$this->jsonResponse();
			Yii::app()->end();
		}
						
		$db=new DbExt;
		if(!empty($this->data['id'])){
			unset($params['date_created']);
			$params['date_modified']=AdminFunctions::dateNow();
			if ( $db->updateData("{{driver_team}}",$params,'team_id',$this->data['id'])){
				$this->code=1;
		   	    $this->msg=Driver::t("Successfully updated");
		   	    $this->details='create-team';
		   	    
		   	    // update driver team
		   	    if(!empty($team_member)){
			       Driver::updateDriverTeam($team_member,$this->data['id']);
		        }
		   	    
			} else $this->msg=Driver::t("failed cannot update record");
		} else {
		   if($db->insertData("{{driver_team}}",$params)){
		   	  $team_id=Yii::app()->db->getLastInsertID();
		   	  $this->code=1;
		   	  $this->msg=Driver::t("Successful");
		   	  $this->details='create-team';
		   	  
		   	  // update driver team
		   	  if(!empty($team_member)){
			     Driver::updateDriverTeam($team_member,$team_id);
		      }
		   	  
		   } else $this->msg=Driver::t("failed cannot insert record");
		}
		$this->jsonResponse();
	}

	public function actionTeamList()
	{
		$aColumns = array(
		  'a.team_id','a.team_name','a.team_name','a.status','a.date_created'
		);
		$t=AjaxDataTables::AjaxData($aColumns);		
		if (isset($_GET['debug'])){
		    dump($t);
		}
		
		if (is_array($t) && count($t)>=1){
			$sWhere=$t['sWhere'];
			$sOrder=$t['sOrder'];
			$sLimit=$t['sLimit'];
		}	
		
		$and='';				
		$and =" AND customer_id=".Driver::q(Driver::getUserId())."";
				
		$stmt="SELECT SQL_CALC_FOUND_ROWS a.*,
			(
			select count(*)
			from
			{{driver}}
			where			
			team_id=a.team_id
			) as total_driver
		FROM
		{{driver_team}} a
		WHERE 1
		$and		
		$sWhere
		$sOrder
		$sLimit
		";
		if (isset($_GET['debug'])){
		   dump($stmt);
		}
				
		$DbExt=new DbExt; 
		if ( $res=$DbExt->rst($stmt)){
			
			$iTotalRecords=0;						
			$stmtc="SELECT FOUND_ROWS() as total_records";
			if ( $resc=$DbExt->rst($stmtc)){									
				$iTotalRecords=$resc[0]['total_records'];
			}
			
			$feed_data['sEcho']=intval($_GET['sEcho']);
			$feed_data['iTotalRecords']=$iTotalRecords;
			$feed_data['iTotalDisplayRecords']=$iTotalRecords;										
			
			foreach ($res as $val) {
				$date_created=Yii::app()->functions->prettyDate($val['date_created'],true);
			    $date_created=Yii::app()->functions->translateDate($date_created);			
			    
			    $id=$val['team_id'];
			    $p="id=$id"."&tbl=driver_team&whereid=team_id";

			    $actions="<div class=\"table-action\">";
			    $actions.="<a data-modal=\".create-team\" data-id=\"$id\" 
			    data-action=\"getTeam\"
			    class=\"table-edit\" href=\"javascript:;\">".Driver::t("Edit")."</a>";    
			    
			    $actions.="&nbsp;|&nbsp;";
			    
			    $actions.="<a data-data=\"$p\" class=\"table-delete\" href=\"javascript:;\">".Driver::t("Delete")."</a>";
			    $actions.="</div>";
			    
			    $feed_data['aaData'][]=array(
			      $val['team_id'],
			      $val['team_name'].$actions,
			      $val['total_driver'],
			      t($val['status']),
			      $date_created,
			    );			    
			}
			if (isset($_GET['debug'])){
			   dump($feed_data);
			}
			$this->otableOutput($feed_data);	
		}
		$this->otableNodata();
	}	
	
	public function actiongetTeam()
	{		
		if($res=Driver::getTeam($this->data['id'])){			
			$this->code=1; 
			$this->msg=Driver::t("Successful");			
			/*if(!empty($res['team_member'])){
				$res['team_member']=json_decode($res['team_member'],true);
			}*/
			//dump($res);
			if ($driver=Driver::getDriverByTeam($res['team_id'])){
				foreach ($driver as $val) {					
					$res['team_member'][]=$val['driver_id'];
				}
			} else $res['team_member']='';
			//dump($res);
			$this->details=$res;
		} else $this->msg=Driver::t("Record not found");
		$this->jsonResponse();
	}
	
	public function actionDeleteRecords()
	{		
		if(isset($this->data['tbl']) && isset($this->data['whereid']) ){
			$wherefield=$this->data['whereid'];
			$tbl=$this->data['tbl'];
			$stmt="
			DELETE FROM
			{{{$tbl}}}
			WHERE
			$wherefield=".Driver::q($this->data['id'])."
			";
			//dump($stmt);
			$DbExt=new DbExt; 
			$DbExt->qry($stmt);
			$this->code=1;
			$this->msg=Driver::t("Successful");
		} else $this->msg=Driver::t("Missing parameters");
		$this->jsonResponse();
	}
	
	public function actiondriverList()
	{
		$aColumns = array(
		  'driver_id',
		  'username',
		  'first_name',
		  'email',
		  'phone',
		  'team_id',
		  'device_platform',
		  'status',
		  'driver_id'
		);
		$t=AjaxDataTables::AjaxData($aColumns);		
		if (isset($_GET['debug'])){
		    dump($t);
		}
		
		if (is_array($t) && count($t)>=1){
			$sWhere=$t['sWhere'];
			$sOrder=$t['sOrder'];
			$sLimit=$t['sLimit'];
		}	
				
        $and='';		
        $and.=" AND customer_id=".Driver::q(Driver::getUserId())."  ";		
		
		$stmt="SELECT SQL_CALC_FOUND_ROWS a.*,
		(
		select team_name
		from
		{{driver_team}}
		where
		team_id=a.team_id
		limit 0,1
		) as team_name,
		
		(
		select SUM(ratings)/COUNT(*) AS count
		from
		{{driver_task}}
		where
		driver_id=a.driver_id
		and ratings<>''
		) as ratings
		
		FROM
		{{driver}} a
		WHERE 1		
		$and
		$sWhere
		$sOrder
		$sLimit
		";
		if (isset($_GET['debug'])){
		   dump($stmt);
		}
		
		$_SESSION['kartero_stmt_agents']=$stmt;
				
		$DbExt=new DbExt; 
		if ( $res=$DbExt->rst($stmt)){
			
			$iTotalRecords=0;						
			$stmtc="SELECT FOUND_ROWS() as total_records";
			if ( $resc=$DbExt->rst($stmtc)){									
				$iTotalRecords=$resc[0]['total_records'];
			}
			
			$feed_data['sEcho']=intval($_GET['sEcho']);
			$feed_data['iTotalRecords']=$iTotalRecords;
			$feed_data['iTotalDisplayRecords']=$iTotalRecords;										
			
			foreach ($res as $val) {				
				$date_created=Yii::app()->functions->prettyDate($val['date_created'],true);
			    $date_created=Yii::app()->functions->translateDate($date_created);			
			    
			    $id=$val['driver_id'];
			    $p="id=$id"."&tbl=driver&whereid=driver_id";

			    $actions="<div class=\"table-action\">";
			    $actions.="<a data-modal=\".new-agent\" data-id=\"$id\" 
			    data-action=\"getDriverInfo\"
			    class=\"table-edit\" href=\"javascript:;\">".Driver::t("Edit")."</a>";    
			    
			    $actions.="&nbsp;|&nbsp;";
			    
			    $actions.="<a data-data=\"$p\" class=\"table-delete\" href=\"javascript:;\">".Driver::t("Delete")."</a>";
			    $actions.="</div>";
			    
			    $send_push_action="<a data-id=\"$id\" class=\"btn orange-button  open-modal-push\" href=\"javascript:;\">".Driver::t("SendPush")."</a>";
			    
			    $ratings='<div class="raty-stars" data-score="'.$val['ratings'].'"></div>';
			    
			    $feed_data['aaData'][]=array(
			      $val['driver_id'],
			      $val['username'].$actions,
			      $val['first_name'],
			      $val['email'],
			      $val['phone'],
			      $val['team_name'],
			      $val['device_platform']."<br><span class=\"concat-text\">".$val['device_id']."</span>",
			      $ratings,
			      $date_created."<br>".t($val['status']),
			      $send_push_action
			    );			    
			}
			if (isset($_GET['debug'])){
			   dump($feed_data);
			}
			$this->otableOutput($feed_data);	
		}
		$this->otableNodata();
	}
	
	public function actionaddAgent()
	{
		
		$DbExt=new DbExt; 	
		$params=array(		  
		  'first_name'=>isset($this->data['first_name'])?$this->data['first_name']:'',
		  'last_name'=>isset($this->data['last_name'])?$this->data['last_name']:'',
		  'email'=>isset($this->data['email'])?$this->data['email']:'',
		  'phone'=>isset($this->data['phone'])?$this->data['phone']:'',
		  'username'=>isset($this->data['username'])?$this->data['username']:'',
		  'password'=>isset($this->data['password'])?md5($this->data['password']):'',
		  'team_id'=>isset($this->data['team_id_driver_new'])?$this->data['team_id_driver_new']:0,
		  'transport_type_id'=>isset($this->data['transport_type_id'])?$this->data['transport_type_id']:'',
		  'transport_description'=>isset($this->data['transport_description'])?$this->data['transport_description']:'',
		  'licence_plate'=>isset($this->data['licence_plate'])?$this->data['licence_plate']:'',
		  'color'=>isset($this->data['color'])?$this->data['color']:'',
		  'status'=>isset($this->data['status'])?$this->data['status']:'',
		  'date_created'=>AdminFunctions::dateNow(),
		  'ip_address'=>$_SERVER['REMOTE_ADDR'],
		  'profile_photo'=>isset($this->data['profile_photo'])?$this->data['profile_photo']:''
		);		
				
		$params['customer_id']=Driver::getUserId();
		
		if(!isset($this->data['id'])){
			$this->data['id']='';
		}
		
		/*dump($params);
			die();
			*/
		
		if(is_numeric($this->data['id'])){
			unset($params['date_created']);
			$params['date_modified']=AdminFunctions::dateNow();
			
			if(empty($this->data['password'])){
			   unset($params['password']);
			}
			
			/*dump($params);
			die();
			*/
			
			if(Driver::checkDriverUserExist($this->data['username'],$this->data['id'])){
				$this->msg=t("Username already exist");
				$this->jsonResponse();
				Yii::app()->end();
			}
			
						
			if(Driver::checkDriverEmailExist($this->data['email'],$this->data['id'])){
				$this->msg=t("Email address already exist");
				$this->jsonResponse();
				Yii::app()->end();
			}
			
			if(Driver::checkDriverPhoneExist($this->data['phone'],$this->data['id'])){
				$this->msg=t("Phone already exist");
				$this->jsonResponse();
				Yii::app()->end();
			}
			
			if ( $DbExt->updateData("{{driver}}",$params,'driver_id',$this->data['id'])){
				$this->code=1;
			    $this->msg=Driver::t("Successfully updated");
			    $this->details='new-agent';
			    
			    /*update team*/
			    //Driver::updateTeamDriver($this->data['id'],$params['team_id']);
			    
			} else $this->msg=Driver::t("failed cannot update record");
		} else {
			
			/*plan check*/
			if(!Driver::planCheckCanAddDriver( Driver::getUserId(),Driver::getPlanID() )){
				$this->msg=t("You cannot add more drivers you account is restrict to add new driver");
				$this->jsonResponse();
				Yii::app()->end();
			}
			
			if(Driver::checkDriverUserExist($this->data['username'])){
				$this->msg=t("Username already exist");
				$this->jsonResponse();
				Yii::app()->end();
			}
			
			if(Driver::checkDriverEmailExist($this->data['email'])){				
				$this->msg=t("Email address already exist");
				$this->jsonResponse();
				Yii::app()->end();
			}
			
			if(Driver::checkDriverPhoneExist($this->data['phone'])){
				$this->msg=t("Phone already exist");
				$this->jsonResponse();
				Yii::app()->end();
			}
			
			$params['location_address']='';
			$params['device_id']='';
			
			if ( $DbExt->insertData('{{driver}}',$params)){
				$this->code=1;
				$this->msg=Driver::t("Successful");
				$this->details='new-agent';
			} else $this->msg=Driver::t("failed cannot insert record");
		}
		$this->jsonResponse();
	}
	
	public function actiongetDriverInfo()
	{		
		if(isset($this->data['id'])){
			if ( $res=Driver::driverInfo($this->data['id'])){
				
				 if (!empty($res['profile_photo'])){
				 	 if (file_exists( Driver::driverUploadPath()."/".$res['profile_photo'] )){
				 	     $res['profile_photo_url']=websiteUrl()."/upload/photo/".$res['profile_photo'];
				 	 }
				 } 
				
				 $this->code=1;
				 $this->msg=Driver::t("Successful");
				 $this->details=$res;
				 				 
			} else $this->msg=Driver::t("Record not found");
		} else $this->msg=Driver::t("Missing parameters");
		$this->jsonResponse();
	}
	
	public function actionAddTask()
	{
		
		/*dump($this->data);
		die();*/
		
		$DbExt=new DbExt; 		
		$req=array(
		  'trans_type'=>Driver::t("Transaction type is required"),
		  'customer_name'=>Driver::t("Customer name is required")
		);
		
		if(!isset($this->data['driver_id'])){
			$this->data['driver_id']='';
		}
				
		$Validator=new Validator;
		$Validator->required($req,$this->data);
		if($Validator->validate()){
			
			$params=array(
			  'task_description'=>isset($this->data['task_description'])?$this->data['task_description']:'',
			  'trans_type'=>isset($this->data['trans_type'])?$this->data['trans_type']:'',
			  'contact_number'=>isset($this->data['contact_number'])?$this->data['contact_number']:'',
			  'email_address'=>isset($this->data['email_address'])?$this->data['email_address']:'',
			  'customer_name'=>isset($this->data['customer_name'])?$this->data['customer_name']:'',
			  'delivery_date'=>isset($this->data['delivery_date'])?$this->data['delivery_date']:'',
			  'delivery_address'=>isset($this->data['delivery_address'])?$this->data['delivery_address']:'',
			  'team_id'=>isset($this->data['team_id'])?$this->data['team_id']:'',
			  'driver_id'=>is_numeric($this->data['driver_id'])?$this->data['driver_id']:0,
			  'task_lat'=>isset($this->data['task_lat'])?$this->data['task_lat']:'',
			  'task_lng'=>isset($this->data['task_lng'])?$this->data['task_lng']:'',
			  'date_created'=>AdminFunctions::dateNow(),
			  'ip_address'=>$_SERVER['REMOTE_ADDR'],			  
			  'customer_id'=>Driver::getUserId(),
			  'dropoff_contact_name'=>isset($this->data['dropoff_contact_name'])?$this->data['dropoff_contact_name']:'',
			  'dropoff_contact_number'=>isset($this->data['dropoff_contact_number'])?$this->data['dropoff_contact_number']:'',
			  'drop_address'=>isset($this->data['drop_address'])?$this->data['drop_address']:'',
			  'dropoff_task_lat'=>isset($this->data['dropoff_task_lat'])?$this->data['dropoff_task_lat']:'',
			  'dropoff_task_lng'=>isset($this->data['dropoff_task_lng'])?$this->data['dropoff_task_lng']:'',
			  'task_token'=>Driver::generateTaskToken()
			);		

			if(empty($params['drop_address'])){
				$params['dropoff_task_lat']='';
				$params['dropoff_task_lng']='';
			}			
						
			if(!empty($params['delivery_date'])){
				$params['delivery_date']= date("Y-m-d G:i",strtotime($params['delivery_date']));
			}
			if($params['driver_id']>0){
				$params['status']='assigned';
			}
						
			if(is_numeric($this->data['task_id'])){
				
				unset($params['date_created']);				
				unset($params['user_id']);
				unset($params['task_token']);
				$params['date_modified']=AdminFunctions::dateNow();				
				
				$task_info=Driver::getTaskId($this->data['task_id']);
				if( $task_info['status']!="unassigned"){
					unset($params['status']);
				}
								
				if ( $DbExt->updateData("{{driver_task}}",$params,'task_id',$this->data['task_id'])){
					$this->code=1;
					$this->msg=Driver::t("Successfully updated");
										
					if (isset($params['status'])){
						if ($params['status']=="assigned"){
							/*add to history*/
							$assigned_task=$params['status'];
							//if ( $res=Driver::getTaskId($this->data['task_id'])){
							if($task_info){
								$status_pretty = Driver::prettyStatus($task_info['status'],$assigned_task);
								$params_history=array(								  
								  'remarks'=>$status_pretty,
								  'status'=>$assigned_task,
								  'date_created'=>AdminFunctions::dateNow(),
								  'ip_address'=>$_SERVER['REMOTE_ADDR'],
								  'task_id'=>$this->data['task_id']
								);		
								$DbExt->insertData('{{task_history}}',$params_history);	
								
								// send notification to driver							
							    Driver::sendDriverNotification('ASSIGN_TASK',$task_info);
							    
							}				
						} 
					} else {						
				        Driver::sendDriverNotification('UPDATE_TASK',$task_info);
					}
					
				} else $this->msg=Driver::t("failed cannot update record");
			} else {
				
				/*plan check*/
				if(!Driver::planCheckCAnAddTask( Driver::getUserId(),Driver::getPlanID() )){
					$this->msg=t("You cannot add more task you account is restrict to add new task");
					$this->jsonResponse();
					Yii::app()->end();
				}
		
				if($DbExt->insertData("{{driver_task}}",$params)){
					$task_id=Yii::app()->db->getLastInsertID();
					$this->code=1;
					$this->msg=Driver::t("Successful");
					
					// send notification to driver
					if ( $info=Driver::getTaskId($task_id)){				
				       Driver::sendDriverNotification('ASSIGN_TASK',$info);
			        }		
			        
			        //send notification to customer	
			        $params['task_id']=$task_id;
			        if ($params['trans_type']=="delivery"){			           
				       Driver::sendCustomerNotification("DELIVERY_TASK_CREATED",$params);
			       } else Driver::sendCustomerNotification("PICKUP_TASK_CREATED",$params);			
					
				} else $this->msg=Driver::t("failed cannot insert record");
			}
		} else $this->msg=$Validator->getErrorAsHTML();
		$this->jsonResponse();
	}
	
	public function actiongetDashboardTask()
	{				
		$db=new DbExt();
		$user_id =  Driver::getUserId();
		
		if (isset($this->data['status'])){
			//$status=$this->data['status'];
			$date='';
			if ( isset($this->data['date'])){
				$date=$this->data['date'];
			}
			
			$enabled_critical_task = getOption( Driver::getUserId(), 'enabled_critical_task');
			$critical_minutes = getOption( Driver::getUserId(), 'critical_minutes');
			if($critical_minutes<=0){
				$critical_minutes=5;
			}
			
			$data=array(); $coordinates=array();
			$status_list=array('unassigned','assigned','completed');
						
			$hide_pickup = getOption($user_id,'map_hide_pickup');
			$hide_delivery = getOption($user_id,'map_hide_delivery');
			$hide_success_task = getOption($user_id,'map_hide_success_task');
			
									
			foreach ($status_list as $status) {
				if ( $res = Driver::getTaskByStatus($this->userId(),$status,$date,
				$hide_pickup,$hide_delivery,$hide_success_task )){
					
					$total=count($res);
					$html='';
					foreach ($res as $val) {			
						//dump($val);		
						if(!empty($val['task_lat']) && !empty($val['task_lng']) ){
							$coordinates[]=array(
							  'lat'=>$val['task_lat'],
							  'lng'=>$val['task_lng'],
							  'trans_type_raw'=>$val['trans_type'],		
							  'customer_name'=>$val['customer_name'],
							  'address'=>$val['delivery_address'],
							  'task_id'=>$val['task_id'],
							  'status_raw'=>$val['status'],
							  'status'=>Driver::t($val['status']),							  
							  'trans_type'=>Driver::t($val['trans_type']),
							  'map_type'=>'restaurant'
							);
						} else {
							if ( $res_location=Driver::addressToLatLong($val['delivery_address'])){
								//dump($res_location);
								$val['task_lat']=$res_location['lat'];
								$val['task_lng']=$res_location['long'];
								
								$db->updateData("{{driver_task}}",array(
								  'task_lat'=>$res_location['lat'],
								  'task_lng'=>$res_location['long']
								),'task_id',$val['task_id']);
								
								$coordinates[]=array(
							      'lat'=>$res_location['lat'],
							      'lng'=>$res_location['long'],
							      'trans_type_raw'=>$val['trans_type'],		
							      'customer_name'=>$val['customer_name'],
							      'address'=>$val['delivery_address'],
							      'task_id'=>$val['task_id'],
							      'status_raw'=>$val['status'],
							      'status'=>Driver::t($val['status']),							      
							      'trans_type'=>Driver::t($val['trans_type']),
							      'map_type'=>'restaurant'
							    );
							}
						}
						$html.=Driver::formatTask($val, $enabled_critical_task, $critical_minutes);
					}
										
					$data[$status]=array(
					  'total'=>$total,
					  'html'=>$html					  
					);								
					$this->details=$data;			
				} else {
					$data[$status]='';
					$this->details=$data;
				}
			}
						
			/*get the driver online coordinates*/
			/*get the driver online coordinates*/
			$agent_stats=array('active');			
			$include_offline=getOption(Driver::getUserId(),'driver_include_offline_driver_map');
			if($include_offline==1){
			   $agent_stats=array('active','offline');
			}
			
			//dump($agent_stats);
			//dump($this->data);
			
			$online_agent='';
			foreach ($agent_stats as $agent_stat) {
				$res_agent=Driver::getDriverByStats(				  
				  Driver::getUserId(),
				  $agent_stat,
				  isset($this->data['date'])?$this->data['date']:date("Y-m-d"),
				  'active',
				  isset($this->data['team_id'])?$this->data['team_id']:''
				);
				if (is_array($res_agent) && count($res_agent)>=1){
				   foreach ($res_agent as $agent_val) {
				   	  $coordinates[]=array(
					   'driver_id'=>$agent_val['driver_id'],
					   'first_name'=>$agent_val['first_name'],
					   'last_name'=>$agent_val['last_name'],
					   'email'=>$agent_val['email'],
					   'phone'=>$agent_val['phone'],
					   'lat'=>$agent_val['location_lat'],
					   'lng'=>$agent_val['location_lng'],
					   'map_type'=>'driver',
					   'is_online'=>$agent_val['is_online']
					  );
				   }
				}
			}
			
		    /*dump($coordinates);
		    die();*/
			
			$this->code=1;	
			$this->msg=$coordinates;
			
		} else $this->msg=Driver::t("parameter status is missing");
		$this->jsonResponse();
	}
	
	private function userType()
	{
		return Driver::getUserType();
	}
	
	private function userId()
	{
		return Driver::getUserId();
	}
	
	public function actionassignTask()
	{
		$DbExt=new DbExt; 		
		$req=array(
		  'task_id'=>Driver::t("Task id is required"),
		  'team_id'=>Driver::t("Team id is required"),
		  'driver_id'=>Driver::t("Driver id is required"),
		);
		
		$assigned_task='assigned';
				
		
		$Validator=new Validator;
		$Validator->required($req,$this->data);
		if($Validator->validate()){
			$params=array(
			  'team_id'=>$this->data['team_id'],
			  'driver_id'=>$this->data['driver_id'],
			  'status'=>$assigned_task,
			  'date_modified'=>AdminFunctions::dateNow(),
			  'ip_address'=>$_SERVER['REMOTE_ADDR']
			);
			if ( $DbExt->updateData("{{driver_task}}",$params,'task_id',$this->data['task_id'])){
				$this->code=1;
				$this->msg=Driver::t("Successfully updated");
				$this->details='assign-task';
				
				/*add to history*/
				if ( $res=Driver::getTaskId($this->data['task_id'])){
					$status_pretty = Driver::prettyStatus($res['status'],$assigned_task);
					$params_history=array(
					  'remarks'=>$status_pretty,
					  'status'=>$assigned_task,
					  'date_created'=>AdminFunctions::dateNow(),
					  'ip_address'=>$_SERVER['REMOTE_ADDR'],
					  'task_id'=>$this->data['task_id'],
					  'reason'=>''
					);		
					$DbExt->insertData('{{task_history}}',$params_history);
				}				
				
				/*send notification to driver*/
		         //Driver::sendDriverNotification('ASSIGN_TASK',$res=Driver::getTaskId($this->data['task_id']));
		         Driver::sendDriverNotification('ASSIGN_TASK', $res );
				
			} else $this->msg=Driver::t("failed cannot update record");
		} else $this->msg=$Validator->getErrorAsHTML();
		$this->jsonResponse();
	}
	
	public function actionGetTaskDetails()
	{		
		
		if (isset($this->data['id'])){
			if ( $res=Driver::getTaskId($this->data['id'])){
				$res['status_raw']=!empty($res['status'])?$res['status']:'';
				$res['status']=!empty($res['status'])?Driver::t($res['status']):'';				
				$res['driver_name']=!empty($res['driver_name'])?$res['driver_name']:'';
				$res['team_name']=!empty($res['team_name'])?$res['team_name']:'';
				$res['customer_name']=!empty($res['customer_name'])?$res['customer_name']:'';
				$res['contact_number']=!empty($res['contact_number'])?$res['contact_number']:'';
				$res['email_address']=!empty($res['email_address'])?$res['email_address']:'';
				$res['delivery_date']=!empty($res['delivery_date'])?date("Y-m-d g:i a",strtotime($res['delivery_date'])):'-';
				//$res['delivery_date']=!empty($res['delivery_date'])?AdminFunctions::prettyDate($res['delivery_date'],true):'-';
				$res['trans_type']=!empty($res['trans_type'])?$res['trans_type']:'';
							
				$res['tracking_link']='';
				
				$res['trans_type']=Driver::t($res['trans_type']);
				
				if (isset($res['task_token'])){
					if(!empty($res['task_token'])){
				       $res['tracking_link']=websiteUrl()."/track/?id=".$res['task_token'];
					} 
				}								
										
				/*get task history*/				
				$history_details=array(); $history_data=array();
				//if ( $info=Driver::getTaskId($this->data['id'])){		

				if($info=$res){
					if($history_details = Driver::getTaskHistory($this->data['id'])){
						foreach ($history_details as $valh) {							
							$valh['status_raw']=$valh['status'];
							$valh['status']=Driver::t($valh['status']);														
							$valh['date_created']=Yii::app()->functions->FormatDateTime($valh['date_created']);
							
							if (!empty($valh['customer_signature'])){
					            $valh['customer_signature_url']=Driver::uploadURL()."/".$valh['customer_signature'];
					            if (!file_exists(Driver::uploadPath()."/".$valh['customer_signature'])){
    					            $valh['customer_signature_url']='';
    				            }
				            }
				            				            
				            if (!empty($valh['photo_name'])){			    								            	
			    				if (file_exists(Driver::driverUploadPath()."/".$valh['photo_name'])){
			    					$valh['photo_name_url']=Driver::uploadURL()."/photo/".$valh['photo_name'];
			    				}
				            }
				            
							$history_data[]=$valh;
						}
					} else {
						$history_data='';
					}
				}			
								
				$res['history_data']=$history_data;
				
				// get the order details
				$order_details='';				
				
				$res['order_details']=$order_details;
				if(isset($res['merchant_name'])){
				   $res['merchant_name']=Driver::cleanText($res['merchant_name']);
				}
				
				//dump($res);
				
				$this->code=1;
				$this->msg="OK";
				$this->details=$res;
				//dump($this->details);
				
			} else $this->msg=Driver::t("Cannot find records");
		} else $this->msg=Driver::t("missing parameter id");
		$this->jsonResponse();
	}
	
	public function actiongetTaskInfo()
	{
		$this->actionGetTaskDetails();
	}
	
	public function actiondeleteTask()
	{		
		if(isset($this->data['task_id'])){						
			
			$task_id=$this->data['task_id'];			
			$customer_id=Driver::getUserId();			
			
			/*check if customer can delete the task*/
			if (!Driver::customerCanDeleteTask($customer_id)){
				$this->msg=t("Sorry but your account is not allowed to delete task");
				$this->jsonResponse();
				Yii::app()->end();
			}
						
			if ( $res2 = Driver::getUnAssignedDriver3($task_id)){				    		
	    		foreach ($res2 as $val2) {	  
	    		   $task_info=Driver::getTaskByDriverNTask($val2['task_id'], $val2['driver_id'] );
	    		   Driver::sendDriverNotification('CANCEL_TASK',$task_info);
	    		}
	    	} else {				    	
				if ( $info=Driver::getTaskId($this->data['task_id'])){				
					Driver::sendDriverNotification('CANCEL_TASK',$info);
				}			
	    	}
			if( Driver::deleteTask($this->data['task_id'])){
				$this->code=1;
				$this->msg="OK";
			} else $this->msg=Driver::t("Failed deleting records");
		} else $this->msg=Driver::t("missing parameter id");
		$this->jsonResponse();
	}
	
	public function actionchangeStatus()
	{
		$req=array(
		  'task_id'=>Driver::t("Task ID is required"),
		  'status'=>Driver::t("Status is required"),
		);
		$Validator=new Validator;
		$Validator->required($req,$this->data);
		if($Validator->validate()){
			if ( $res=Driver::getTaskId($this->data['task_id'])){				
				$status_pretty = Driver::prettyStatus($res['status'],$this->data['status']);
				$params=array(
				  'remarks'=>$status_pretty,
				  'status'=>$this->data['status'],
				  'date_created'=>AdminFunctions::dateNow(),
				  'ip_address'=>$_SERVER['REMOTE_ADDR'],
				  'task_id'=>$this->data['task_id'],
				  'reason'=>isset($this->data['reason'])?$this->data['reason']:''
				);								
				$DbExt=new DbExt; 
				if ( $DbExt->insertData("{{task_history}}",$params)){
					$this->code=1;
					$this->msg= Driver::t("Task Status Changed Successfully");
					$this->details='task-change-status-modal';
					
					/*update the status*/
					$DbExt->updateData("{{driver_task}}",array(
					 'status'=>$this->data['status']
					),'task_id',$this->data['task_id']);
					
				} else $this->msg=Driver::t("failed cannot update record");
			} else $this->msg=Driver::t("Task id not found");
		} else $this->msg=$Validator->getErrorAsHTML();
		$this->jsonResponse();
	}
	
	public function actionloadAgentDashboard()
	{		
	
		$data=array();
		$agent_stats=array(
		  'active','offline','total'
		);
		foreach ($agent_stats as $agent_stat) {
			$res=Driver::getDriverByStats(
			  Driver::getUserId(),
			  $agent_stat,
			  isset($this->data['date'])?$this->data['date']:date("Y-m-d"),
			  'active',
			  isset($this->data['team_id'])?$this->data['team_id']:''
			);
			if($res){
				$data[$agent_stat]=$res;
			} else $data[$agent_stat]='';
		}
		
		//dump($data);
		
		$this->code=1;
		$this->msg="OK";
		$this->details=$data;
		$this->jsonResponse();
	}
	
	public function actiongetDriverDetails()
	{
		if ( isset($this->data['driver_id'])){
			if ( $res= Driver::driverInfo($this->data['driver_id'])){
				$data['driver_id']=$res['driver_id'];
				//$data['user_id']=$res['customer_id'];
				$data['name']=$res['first_name']." ".$res['last_name'];
				$data['email']=$res['email'];
				$data['phone']=$res['phone'];
				$data['transport_type_id']=$res['transport_type_id'];
				$data['licence_plate']=$res['licence_plate'];
				$data['team_name']=$res['team_name'];
				$data['transport_type_id']=Driver::t($data['transport_type_id']);
				
				$data['device_platform']=$res['device_platform'];
				$data['app_version']=$res['app_version'];
								
				$order_details='';
				
				$transaction_date=isset($this->data['date'])?$this->data['date']:date("Y-m-d");
				if ( !$order=Driver::getTaskByDriverID($this->data['driver_id'],$transaction_date)){
					$order_details='';
				} else {
					foreach ($order as $order_val) {		
						$order_val['status']=Driver::t($order_val['status']);
						$order_val['status_raw']=$order_val['status'];						
						$order_details[]=$order_val;
					}
				}
				
				if ($data['device_platform']=="null"){
					$data['device_platform']='';
				}
				//dump($data);
							
				$this->code=1;
				$this->msg="OK";
				$this->details=array(
				  'info'=>$data,
				  'task'=>$order_details
				);				
				
			} else $this->msg=Driver::t("Driver details not found");
		} else $this->msg=Driver::t("Missing parameters");
		$this->jsonResponse();
	}
	
	public function actiontaskList()
	{
		$aColumns = array(
		  'task_id',
		  'trans_type',
		  'task_description',
		  'driver_name',
		  'customer_name',
		  'delivery_address',
		  'delivery_date',
		  'status'
		);
		$t=AjaxDataTables::AjaxData($aColumns);		
		if (isset($_GET['debug'])){
		    dump($t);
		}
		
		if (is_array($t) && count($t)>=1){
			$sWhere=$t['sWhere'];
			$sOrder=$t['sOrder'];
			$sLimit=$t['sLimit'];
		}	
				
        $and='';		
        $and=" AND customer_id =".Driver::q(Driver::getUserId())."  ";
		
		$stmt="SELECT SQL_CALC_FOUND_ROWS *
		FROM
		{{driver_task_view}}
		WHERE 1		
		$and
		$sWhere
		$sOrder
		$sLimit
		";
		if (isset($_GET['debug'])){
		   dump($stmt);
		}
		
		//dump($this->data);
		
		$_SESSION['kartero_stmt_taskList']=$stmt;
				
		$DbExt=new DbExt; 
		$DbExt->qry("SET SQL_BIG_SELECTS=1");
		
		if ( $res=$DbExt->rst($stmt)){
			
			$iTotalRecords=0;						
			$stmtc="SELECT FOUND_ROWS() as total_records";
			if ( $resc=$DbExt->rst($stmtc)){									
				$iTotalRecords=$resc[0]['total_records'];
			}
			
			$feed_data['sEcho']=intval($_GET['sEcho']);
			$feed_data['iTotalRecords']=$iTotalRecords;
			$feed_data['iTotalDisplayRecords']=$iTotalRecords;										
			
			foreach ($res as $val) {
				$date_created=AdminFunctions::prettyDate($val['delivery_date'],true);
			    $date_created=Yii::app()->functions->translateDate($date_created);		
			    
			    $status="<span class=\"tag ".$val['status']." \">".Driver::t($val['status'])."</span>";	
			    
			    $action="<a class=\"btn btn-primary task-details\"
			    	data-id=\"".$val['task_id']."\" href=\"javascript:;\">".t("Details")."</a>";
			    
			    if ( $val['status']=="unassigned"){
			    	$action="<a class=\"btn btn-default assign-agent\"
			    	data-id=\"".$val['task_id']."\" href=\"javascript:;\">".Driver::t("Assigned")."</a>";
			    }
			    
			    $ratings='<div class="raty-stars" data-score="'.$val['ratings'].'"></div>';
			    			    
			    $feed_data['aaData'][]=array(
			      $val['task_id'],
			      Driver::t($val['trans_type']),
			      $val['task_description'],
			      $val['driver_name'],
			      $val['customer_name'],
			      $val['delivery_address'],
			      $date_created,
			      $ratings,
			      $val['rating_comment'],
			      $status,
			      $action
			    );			    
			}
			if (isset($_GET['debug'])){
			   dump($feed_data);
			}
			$this->otableOutput($feed_data);	
		}
		$this->otableNodata();
	}
	
	public function actiongeneralSettings()
	{				
		
		Yii::app()->functions->updateOption('drv_default_location',
		  isset($this->data['drv_default_location'])?$this->data['drv_default_location']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('drv_map_style',
		  isset($this->data['drv_map_style'])?$this->data['drv_map_style']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('drv_delivery_time',
		  isset($this->data['drv_delivery_time'])?$this->data['drv_delivery_time']:'',
		  Driver::getUserId()
		);
		
		if(!empty($this->data['drv_default_location'])){
		  $country_list=require_once('CountryCode.php');	
	       $country_name='';
	       if(array_key_exists($this->data['drv_default_location'],(array)$country_list)){
	           $country_name=$country_list[$this->data['drv_default_location']];	   
	       } else $country_name=$this->data['drv_default_location'];	       	       
	       if ( $res=Driver::addressToLatLong($country_name))	{	       	
	       	   Yii::app()->functions->updateOption("drv_default_location_lat",$res['lat'], Driver::getUserId() ); 
	       	   Yii::app()->functions->updateOption("drv_default_location_lng",$res['long'], Driver::getUserId() ); 	       	
	       } 
	    }
	    	  
		Yii::app()->functions->updateOption('driver_send_push_to_online',
		  isset($this->data['driver_send_push_to_online'])?$this->data['driver_send_push_to_online']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('driver_include_offline_driver_map',
		  isset($this->data['driver_include_offline_driver_map'])?$this->data['driver_include_offline_driver_map']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('driver_disabled_auto_refresh',
		  isset($this->data['driver_disabled_auto_refresh'])?$this->data['driver_disabled_auto_refresh']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('driver_disabled_contacts_task',
		  isset($this->data['driver_disabled_contacts_task'])?$this->data['driver_disabled_contacts_task']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('driver_enabled_notes',
		  isset($this->data['driver_enabled_notes'])?$this->data['driver_enabled_notes']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('driver_enabled_signature',
		  isset($this->data['driver_enabled_signature'])?$this->data['driver_enabled_signature']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('driver_enabled_photo',
		  isset($this->data['driver_enabled_photo'])?$this->data['driver_enabled_photo']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('driver_device_vibration',
		  isset($this->data['driver_device_vibration'])?$this->data['driver_device_vibration']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('driver_company_logo',
		  isset($this->data['driver_company_logo'])?$this->data['driver_company_logo']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('calendar_language',
		  isset($this->data['calendar_language'])?$this->data['calendar_language']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('driver_tracking_options',
		  isset($this->data['driver_tracking_options'])?$this->data['driver_tracking_options']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('enabled_critical_task',
		  isset($this->data['enabled_critical_task'])?$this->data['enabled_critical_task']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('critical_minutes',
		  isset($this->data['critical_minutes'])?$this->data['critical_minutes']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('agents_record_track_Location',
		  isset($this->data['agents_record_track_Location'])?$this->data['agents_record_track_Location']:'',
		  Driver::getUserId()
		);
				
		Yii::app()->functions->updateOption('map_hide_pickup',
		  isset($this->data['map_hide_pickup'])?$this->data['map_hide_pickup']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('map_hide_delivery',
		  isset($this->data['map_hide_delivery'])?$this->data['map_hide_delivery']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('map_hide_success_task',
		  isset($this->data['map_hide_success_task'])?$this->data['map_hide_success_task']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('customer_timezone',
		  isset($this->data['customer_timezone'])?$this->data['customer_timezone']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('app_enabled_resize_pic',
		  isset($this->data['app_enabled_resize_pic'])?$this->data['app_enabled_resize_pic']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('app_resize_width',
		  isset($this->data['app_resize_width'])?$this->data['app_resize_width']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('app_resize_height',
		  isset($this->data['app_resize_height'])?$this->data['app_resize_height']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('app_disabled_bg_tracking',
		  isset($this->data['app_disabled_bg_tracking'])?$this->data['app_disabled_bg_tracking']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('app_track_interval',
		  isset($this->data['app_track_interval'])?$this->data['app_track_interval']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('driver_auto_geo_address',
		  isset($this->data['driver_auto_geo_address'])?$this->data['driver_auto_geo_address']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('driver_activity_tracking',
		  isset($this->data['driver_activity_tracking'])?$this->data['driver_activity_tracking']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('driver_activity_tracking_interval',
		  isset($this->data['driver_activity_tracking_interval'])?$this->data['driver_activity_tracking_interval']:'',
		  Driver::getUserId()
		);
		
	    $this->code=1;
	    $this->msg=Yii::t("default","Setting saved");	
	    $this->jsonResponse();
	}
	
	public function actionSaveTranslation()
	{		
		$mobile_dictionary='';
		if (is_array($this->data) && count($this->data)>=1){
			//$version=str_replace(".",'',phpversion());					
			$mobile_dictionary=json_encode($this->data);			
			$unicode=3;
		}				
		Yii::app()->functions->updateOptionAdmin('driver_mobile_dictionary',$mobile_dictionary);
		$this->code=1;
		$this->msg=Driver::t("translation saved");
		$this->details=$unicode;
		$this->jsonResponse();
	}		
	
	public function actionSaveNotification()
	{		
		
		$driver_id=Driver::getUserId();
		
		$delivery=Driver::notificationListDelivery();
		$key="DELIVERY_";
		foreach ($delivery['DELIVERY'] as $val){
			foreach ($val as $val2) {
				$_key=$key.$val2;					
				updateOption(
				   $_key,isset($this->data[$_key])?$this->data[$_key]:'',Driver::getUserId()
				);
			}
		}
		
		$delivery=Driver::notificationListPickup();
		$key="PICKUP_";
		foreach ($delivery['PICKUP'] as $val){
			foreach ($val as $val2) {
				$_key=$key.$val2;					
				updateOption(
				   $_key,isset($this->data[$_key])?$this->data[$_key]:'',Driver::getUserId()
				);
			}
		}
		
		updateOption("ASSIGN_TASK_PUSH",
        isset($this->data['ASSIGN_TASK_PUSH'])?$this->data['ASSIGN_TASK_PUSH']:'', $driver_id);
        
        updateOption("ASSIGN_TASK_SMS",
        isset($this->data['ASSIGN_TASK_SMS'])?$this->data['ASSIGN_TASK_SMS']:'',$driver_id);
        
        updateOption("ASSIGN_TASK_EMAIL",
        isset($this->data['ASSIGN_TASK_EMAIL'])?$this->data['ASSIGN_TASK_EMAIL']:'',$driver_id);
        
        updateOption("CANCEL_TASK_PUSH",
        isset($this->data['CANCEL_TASK_PUSH'])?$this->data['CANCEL_TASK_PUSH']:'',$driver_id);
        
        updateOption("CANCEL_TASK_SMS",
        isset($this->data['CANCEL_TASK_SMS'])?$this->data['CANCEL_TASK_SMS']:'',$driver_id);
        
        updateOption("CANCEL_TASK_EMAIL",
        isset($this->data['CANCEL_TASK_EMAIL'])?$this->data['CANCEL_TASK_EMAIL']:'',$driver_id);
        
        updateOption("UPDATE_TASK_PUSH",
        isset($this->data['UPDATE_TASK_PUSH'])?$this->data['UPDATE_TASK_PUSH']:'',$driver_id);
        
        updateOption("UPDATE_TASK_SMS",
        isset($this->data['UPDATE_TASK_SMS'])?$this->data['UPDATE_TASK_SMS']:'',$driver_id);
        
        updateOption("UPDATE_TASK_EMAIL",
        isset($this->data['UPDATE_TASK_EMAIL'])?$this->data['UPDATE_TASK_EMAIL']:'',$driver_id);
        
        updateOption("FAILED_AUTO_ASSIGN_PUSH",
        isset($this->data['FAILED_AUTO_ASSIGN_PUSH'])?$this->data['FAILED_AUTO_ASSIGN_PUSH']:'',$driver_id);
        
        updateOption("FAILED_AUTO_ASSIGN_SMS",
        isset($this->data['FAILED_AUTO_ASSIGN_SMS'])?$this->data['FAILED_AUTO_ASSIGN_SMS']:'',$driver_id);
        
        updateOption("FAILED_AUTO_ASSIGN_EMAIL",
        isset($this->data['FAILED_AUTO_ASSIGN_EMAIL'])?$this->data['FAILED_AUTO_ASSIGN_EMAIL']:'',$driver_id);
        
        updateOption("AUTO_ASSIGN_ACCEPTED_PUSH",
        isset($this->data['AUTO_ASSIGN_ACCEPTED_PUSH'])?$this->data['AUTO_ASSIGN_ACCEPTED_PUSH']:'',$driver_id);
        
        updateOption("AUTO_ASSIGN_ACCEPTED_SMS",
        isset($this->data['AUTO_ASSIGN_ACCEPTED_SMS'])?$this->data['AUTO_ASSIGN_ACCEPTED_SMS']:'',$driver_id);
        
        updateOption("AUTO_ASSIGN_ACCEPTED_EMAIL",
        isset($this->data['AUTO_ASSIGN_ACCEPTED_EMAIL'])?$this->data['AUTO_ASSIGN_ACCEPTED_EMAIL']:'',$driver_id);
		
		$this->code=1; $this->msg=Driver::t("Setting saved");
		$this->jsonResponse();
	}
	
	public function actionSaveNotificationTemplate()
	{
		//dump($this->data);
		$key=array('PUSH','SMS','EMAIL');
		
		$user_type=Driver::getLoginType();
		if ( $user_type=="admin"){
						
			foreach ($key as $val) {
				$key=$this->data['option_name']."_$val"."_TPL";						
				Yii::app()->functions->updateOptionAdmin($key,
				  isset($this->data[$val])?$this->data[$val]:''
				);
			}
			
		} else {
			
			$merchant_id=Driver::getUserId();				
			foreach ($key as $val) {
				$key=$this->data['option_name']."_$val"."_TPL";						
				Yii::app()->functions->updateOption($key,
				  isset($this->data[$val])?$this->data[$val]:'',
				  $merchant_id
				);
			}
			
		}
		$this->code=1; $this->msg=Driver::t("Template saved");
		$this->jsonResponse();
	}
	
	public function actionGetNotificationTPL()
	{
		$key=array('PUSH','SMS','EMAIL');
		$user_type=Driver::getLoginType();
		if ( $user_type=="admin"){
			
			$data=array();			
			foreach ($key as $val) {
				$key=$this->data['option_name']."_$val"."_TPL";						
			    $data[$val]=getOptionA($key);
			}
			
		} else {
			
			$merchant_id=Driver::getUserId();			
			foreach ($key as $val) {
				$key=$this->data['option_name']."_$val"."_TPL";						
			    $data[$val]=getOption($merchant_id,$key);
			}
			
		}		
		$this->details=$data;
		$this->code=1; $this->msg=Driver::t("OK");
		$this->jsonResponse();
	}
	
	public function actionGetNotifications()
	{		
		$data=array(); 
		$db_ext=new DbExt; 
		if ( $res=Driver::getNotifications( Driver::getUserId() ) ){
			foreach ($res as $val) {
				$data[]=array(
				  'title'=> Driver::t($val['status'])." ".Driver::t("Task ID").":".$val['task_id'],
				  'message'=>$val['remarks'],
				  'task_id'=>$val['task_id'],
				  'status'=>Driver::t($val['status'])
				);
				$db_ext->updateData('{{task_history}}',array(
				  'notification_viewed'=>1
				),'id',$val['id']);
			}
			$this->code=1;
			$this->details=$data;
		} else $this->msg="No notifications";
		$this->jsonResponse();
	}
	
	public function actiongetInitialNotifications()
	{
		$data=array(); 
		$db_ext=new DbExt; 
		if ( $res=Driver::getNotifications( Driver::getUserId() , 1 ) ){
			foreach ($res as $val) {
				$data[]=array(
				  'title'=>$val['status']." ".Driver::t("Task ID").":".$val['task_id'],
				  'message'=>$val['remarks'],
				  'task_id'=>$val['task_id'],
				  'status'=>Driver::t($val['status'])
				);
				$db_ext->updateData('{{task_history}}',array(
				  'notification_viewed'=>1
				),'id',$val['id']);
			}
			$this->code=1;
			$this->details=$data;
		} else $this->msg="No notifications";
		$this->jsonResponse();
	}
	
	public function actionPushLogList()
	{		
		$aColumns = array(
		  'push_id',
		  'driver_id',
		  'push_title',
		  'push_message',
		  'push_type',
		  'device_platform',
		  'status'
		);
		$t=AjaxDataTables::AjaxData($aColumns);		
		if (isset($_GET['debug'])){
		    dump($t);
		}
		
		if (is_array($t) && count($t)>=1){
			$sWhere=$t['sWhere'];
			$sOrder=$t['sOrder'];
			$sLimit=$t['sLimit'];
		}	
		
		$and= " AND customer_id = ".Driver::q( Driver::getUserId())." ";
				
		if (isset($_GET['broadcast_id'])){
			if ($_GET['broadcast_id']>0){
				$and.="  AND broadcast_id=".Driver::q($_GET['broadcast_id'])." ";
			}
		}
					
				
		$stmt="SELECT SQL_CALC_FOUND_ROWS a.*,
		(
		  select concat(first_name,' ',last_name)
		  from  {{driver}}
		  where
		  driver_id=a.driver_id
		  limit 0,1
		) as driver_name
		FROM
		{{driver_pushlog}} a
		WHERE 1
		$and		
		$sWhere
		$sOrder
		$sLimit
		";
		if (isset($_GET['debug'])){
		   dump($stmt);
		}
				
		$DbExt=new DbExt; 
		if ( $res=$DbExt->rst($stmt)){
			
			$iTotalRecords=0;						
			$stmtc="SELECT FOUND_ROWS() as total_records";
			if ( $resc=$DbExt->rst($stmtc)){									
				$iTotalRecords=$resc[0]['total_records'];
			}
			
			$feed_data['sEcho']=intval($_GET['sEcho']);
			$feed_data['iTotalRecords']=$iTotalRecords;
			$feed_data['iTotalDisplayRecords']=$iTotalRecords;										
			
			foreach ($res as $val) {
				$date_created=Yii::app()->functions->prettyDate($val['date_created'],true);
			    $date_created=Yii::app()->functions->translateDate($date_created);			
			    
			    $status="<span class=\"tag_push rounded ".$val['status']."\">".Driver::t($val['status'])."</span>";
			    
			    $feed_data['aaData'][]=array(
			      $val['push_id'],
			      //$val['driver_id'],
			      $val['driver_name']." (".$val['driver_id'].")",
			      Driver::t($val['push_title']),
			      $val['push_message'],
			      Driver::t($val['push_type']),
			      $val['device_platform']."<br><span class=\"concat-text\">".$val['device_id']."</span>",
			      $status."<br>".$date_created,
			    );			    
			}
			if (isset($_GET['debug'])){
			   dump($feed_data);
			}
			$this->otableOutput($feed_data);	
		}
		$this->otableNodata();
	}
	
    public function actionsaveAssigmentSettings()
	{				
		$this->code=1;		
		Yii::app()->functions->updateOption('driver_auto_assign_type',
		  isset($this->data['driver_auto_assign_type'])?$this->data['driver_auto_assign_type']:'',
		  Driver::getUserId()
		);
				
		Yii::app()->functions->updateOption('driver_assign_request_expire',
		  isset($this->data['driver_assign_request_expire'])?$this->data['driver_assign_request_expire']:'',
		  Driver::getUserId()
		);
				
		Yii::app()->functions->updateOption('driver_enabled_auto_assign',
		  isset($this->data['driver_enabled_auto_assign'])?$this->data['driver_enabled_auto_assign']:'',
		  Driver::getUserId()
		);
				
		Yii::app()->functions->updateOption('driver_include_offline_driver',
		  isset($this->data['driver_include_offline_driver'])?$this->data['driver_include_offline_driver']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('driver_autoassign_notify_email',
		  isset($this->data['driver_autoassign_notify_email'])?$this->data['driver_autoassign_notify_email']:'',
		  Driver::getUserId()
		);
				
		Yii::app()->functions->updateOption('driver_request_expire',
		  isset($this->data['driver_request_expire'])?$this->data['driver_request_expire']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('driver_assign_radius',
		  isset($this->data['driver_assign_radius'])?$this->data['driver_assign_radius']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('auto_retry_assigment',
		  isset($this->data['auto_retry_assigment'])?$this->data['auto_retry_assigment']:'',
		  Driver::getUserId()
		);
		
		$params=array(
		  'enabled_auto_assign'=>isset($this->data['driver_enabled_auto_assign'])?$this->data['driver_enabled_auto_assign']:0,
		   'include_offline_driver'=>isset($this->data['driver_include_offline_driver'])?$this->data['driver_include_offline_driver']:0,
		   
		   'autoassign_notify_email'=>isset($this->data['driver_autoassign_notify_email'])?$this->data['driver_autoassign_notify_email']:'',
		   
		   'request_expire'=>is_numeric($this->data['driver_request_expire'])?$this->data['driver_request_expire']:0,
		   
		   'auto_assign_type'=>isset($this->data['driver_auto_assign_type'])?$this->data['driver_auto_assign_type']:'',
		   'assign_request_expire'=>is_numeric($this->data['driver_assign_request_expire'])?$this->data['driver_assign_request_expire']:0,
		   'driver_assign_radius'=>is_numeric($this->data['driver_assign_radius'])?$this->data['driver_assign_radius']:0,
		   
		   'auto_retry_assigment'=>isset($this->data['auto_retry_assigment'])?$this->data['auto_retry_assigment']:0,
		);
				
		//dump($params);
		$DbExt=new DbExt;
		$DbExt->updateData('{{customer}}',$params,'customer_id',Driver::getUserId());
		
		$this->msg= Driver::t("Setting saved");
		$this->jsonResponse();
	}
	
	public function actionretryAutoAssign()
	{		
		if ( isset($this->data['task_id'])){
			$task_id=$this->data['task_id'];
			$this->code=1;
			$this->msg="OK";
						
			$less="-1";
						
			$params=array(			  
			  'assignment_status'=>'waiting for driver acknowledgement',
			  //'assign_started'=>date('c',strtotime("$less min")),
			  'assign_started'=>date('Y-m-d G:i:s',strtotime("$less min")),
			  'auto_assign_type'=>''
			);
						
			$db=new DbExt;
			$db->updateData("{{driver_task}}",$params,'task_id',$task_id);
			
			
			$stmt="DELETE FROM
			{{driver_assignment}}
			WHERE
			task_id=".Driver::q($task_id)."
			";
			$db->qry($stmt);
									
			
		} else $this->msg=Driver::t("Missing task id");
		$this->jsonResponse();
	}
	
	public function actionChartReports()
	{	
		//dump($this->data);
		$data=array();
		if ( $data=Driver::generateReports($this->data['chart_type'], $this->data['time_selection'],
		   $this->data['team_selection'], $this->data['driver_selection'],
		   $this->data['chart_type_option'],
		   $this->data['start_date'],
		   $this->data['end_date']
		    )){		    	
		}		
						
		$new_data=array();
			
		if (is_array($data) && count($data)>=1){
			
			$first_date=date("Y-m-d",strtotime($data[0]['delivery_date']."-1 day"));
				$new_data[]=array(
				   'date'=>$first_date,
				   'successful'=>0,
				   'cancelled'=>0,
				   'failed'=>0
		    );
			
			foreach ($data as $val) {
				//dump($val);
				switch ($val['status']) {
					
					case "successful":	
					$new_data[]=array(
					  'date'=>$val['delivery_date'],
					  'successful'=>$val['total'],
					  'driver_name'=>isset($val['driver_name'])?$val['driver_name']:''
					);
					break;
						
					case "cancelled":	
					$new_data[]=array(
					  'date'=>$val['delivery_date'],
					  'cancelled'=>$val['total'],
					  'driver_name'=>isset($val['driver_name'])?$val['driver_name']:''
					);
					break;
					
					case "failed":	
					$new_data[]=array(
					  'date'=>$val['delivery_date'],
					  'failed'=>$val['total'],
					  'driver_name'=>isset($val['driver_name'])?$val['driver_name']:''
					);
					break;
				
					default:
						break;
				}
			}
		} else {
			/*$new_data[]=array(
			  'date'=>date("Y-m-d"),
			  'failed'=>0,
			  'driver_name'=>''
			);*/
		}
		
		$table='';
		
				
		if ( $this->data['chart_type_option']=="agent"){
		
			ob_start();
			require_once('charts-bar.php');
			$charts = ob_get_contents();
            ob_end_clean();
            
            ob_start();
            require_once('chart-bar-table.php');
            $table = ob_get_contents();
            ob_end_clean();
            
		} else {						        
            ob_start();
		    require_once('charts.php');		   
		    $charts = ob_get_contents();
            ob_end_clean();
            
            ob_start();
			require_once('chart-table.php');			
			$table = ob_get_contents();
            ob_end_clean();
		}		
		$this->code=1;
		$this->msg="OK";
		$this->details=array(
		  'charts'=>$charts,
		  'table'=>$table
		);
		$this->jsonResponse();
	}	
	
	public function actionForgotPassword()
	{		
		if ( $res=AdminFunctions::getCustomerByEmail($this->data['email_address'])){
			if ( AdminFunctions::sendResetPassword($res)){
				$this->code=1;
				$this->msg="OK";
				$this->details=Yii::app()->createUrl('/app/resetpassword',array(
				  'hash'=>$res['token']
				));
			} else $this->msg=t("Sorry but we cannot process your request");
		} else $this->msg=t("Sorry but email address you supplied does not exists in our records");
		$this->jsonResponse();
	}

	public function actionresetPassword()
	{
		
		if ( $this->data['password']!=$this->data['cpassword']){
			$this->msg=t("Confirm password does not macth with your new password");
			$this->jsonResponse();
			Yii::app()->end();
		}
		if ( isset($this->data['hash'])){
			if ( $res=FrontFunctions::getCustomerByToken($this->data['hash'])){
				
				if($this->data['verification_code']!=$res['verification_code']){
				   $this->msg=t("Your verification code is incorrect");
			       $this->jsonResponse();
			       Yii::app()->end();
				}
				
				$encryption_type=Yii::app()->params->encryption_type;
				if(empty($encryption_type)){
					$encryption_type='yii';
				}
				
				$customer_id=$res['customer_id'];	
				if ($encryption_type=="yii"){
				    $params['password']=CPasswordHelper::hashPassword($this->data['password']);			
				} else $params['password']=md5($this->data['password']);
				
				$params['date_modified']=AdminFunctions::dateNow();

				$db=new DbExt;
				if ($db->updateData("{{customer}}",$params,'customer_id',$customer_id)){
					$this->code=1;
				    $this->msg=t("Your password has been reset");
				    $this->details=Yii::app()->createUrl('/app/login');				
				} else $this->msg=t("failed cannot update record");				
			} else $this->msg=t("Hash does not exist or your record does not exist in our records");
		} else $this->msg=t("Hash is missing");
		$this->jsonResponse();
	}
	
	public function actionupdateProfile()
	{
		
		$encryption_type=Yii::app()->params->encryption_type;
		if(empty($encryption_type)){
			$encryption_type='yii';
		}
		
		$params=$this->data;
		unset($params['action']);
		unset($params['cpassword']);
		unset($params['password']);		
		$params['date_modified']=AdminFunctions::dateNow();
		$params['ip_address']=$_SERVER['REMOTE_ADDR'];
		
		if ( !empty($this->data['password'])){
			if ( $this->data['password']!=$this->data['cpassword']){
				$this->msg=t("Confirm password does not macth with your new password");
				$this->jsonResponse();
				Yii::app()->end();
			}
			
			if ($encryption_type=="yii"){
			    $params['password']=CPasswordHelper::hashPassword($this->data['password']);
			} else $params['password']=md5($this->data['password']);
		}
		
		$customer_id=Driver::getUserId();
		if ( FrontFunctions::checkByEmailExist($this->data['email_address'],$customer_id)){
			$this->msg=t("Email address already exist");
			$this->jsonResponse();
			Yii::app()->end();
		}
			
		if (is_numeric($customer_id)){
			$db=new DbExt;
			if ( $db->updateData('{{customer}}',$params,'customer_id',$customer_id)){
				$this->code=1;
				$this->msg=t("Profile updated");
			} else $this->msg=t("failed cannot update record");
		} else $this->msg=t("Your session has expired please re-login");
		
		$this->jsonResponse();
	}
	
	public function actionsendPush()
	{		
		if ( $res=Driver::driverInfo($this->data['driver_id_push'])){			
			$params=array(
			   'customer_id'=>$res['customer_id'],
			   'device_platform'=>$res['device_platform'],
			   'device_id'=>$res['device_id'],
			   'push_title'=>$this->data['x_push_title'],
			   'push_message'=>$this->data['x_push_message'],
			   'push_type'=>"campaign",
			   'actions'=>"private",
			   'driver_id'=>$res['driver_id'],
			   'date_created'=>AdminFunctions::dateNow(),
			   'json_response'=>''		   
			);
			$db_ext=new DbExt; 						
			if($db_ext->insertData("{{driver_pushlog}}",$params)){
				$push_id=Yii::app()->db->getLastInsertID();				
				Driver::fastRequest(Driver::getHostURL().Yii::app()->createUrl("cron/processpush"));
				$this->code=1;
				$this->msg=t("Successful");
			} else $this->msg=t("Something went wrong cannot insert records");
		} else $this->msg=t("Driver info not found");
		$this->jsonResponse();
	}
	
	public function actioncontactlist()
	{
		$aColumns = array(
		  'contact_id',
		  'fullname',
		  'email',
		  'phone',
		  'address',
		  'status',
		  'contact_id'
		);
		$t=AjaxDataTables::AjaxData($aColumns);		
		if (isset($_GET['debug'])){
		    dump($t);
		}
		
		if (is_array($t) && count($t)>=1){
			$sWhere=$t['sWhere'];
			$sOrder=$t['sOrder'];
			$sLimit=$t['sLimit'];
		}	
				
        $and='';		
        $and.=" AND customer_id=".Driver::q(Driver::getUserId())."  ";		
		
		$stmt="SELECT SQL_CALC_FOUND_ROWS a.*		
		FROM
		{{contacts}} a
		WHERE 1
		$and
		$sWhere
		$sOrder
		$sLimit
		";
		if (isset($_GET['debug'])){
		   dump($stmt);
		}
		
		$_SESSION['kartero_stmt_contact']=$stmt;
				
		$DbExt=new DbExt; 
		if ( $res=$DbExt->rst($stmt)){
			
			$iTotalRecords=0;						
			$stmtc="SELECT FOUND_ROWS() as total_records";
			if ( $resc=$DbExt->rst($stmtc)){									
				$iTotalRecords=$resc[0]['total_records'];
			}
			
			$feed_data['sEcho']=intval($_GET['sEcho']);
			$feed_data['iTotalRecords']=$iTotalRecords;
			$feed_data['iTotalDisplayRecords']=$iTotalRecords;										
			
			foreach ($res as $val) {
				$date_created=Yii::app()->functions->prettyDate($val['date_created'],true);
			    $date_created=Yii::app()->functions->translateDate($date_created);			
			    
			    $id=$val['contact_id'];
			    $p="id=$id"."&tbl=contacts&whereid=contact_id";

			    $actions="<div class=\"table-action\">";
			    $actions.="<a data-modal=\".new-contact\" data-id=\"$id\" 
			    data-action=\"getContactInfo\"
			    class=\"table-edit\" href=\"javascript:;\">".Driver::t("Edit")."</a>";    
			    
			    $actions.="&nbsp;|&nbsp;";
			    
			    $actions.="<a data-data=\"$p\" class=\"table-delete\" href=\"javascript:;\">".Driver::t("Delete")."</a>";
			    $actions.="</div>";
			    
			    $send_push_action="<a data-id=\"$id\" class=\"btn orange-button  open-modal-push\" href=\"javascript:;\">".Driver::t("SendPush")."</a>";
			    
			    $feed_data['aaData'][]=array(
			      $val['contact_id'],
			      $val['fullname'].$actions,
			      $val['email'],
			      $val['phone'],
			      $val['address'],
			      "<span class=\"tag rounded ".$val['status']."\">".Driver::t($val['status'])."</span>"
			    );			    
			}
			if (isset($_GET['debug'])){
			   dump($feed_data);
			}
			$this->otableOutput($feed_data);	
		}
		$this->otableNodata();
	}
	
	public function actionAddContact()
	{		
		$req=array(
		  'fullname'=>Driver::t("Name is required"),		  
		  'address'=>Driver::t("Address is required"),		  
		  'phone'=>Driver::t("Phone is required"),  
		  'addresss_lat'=>Driver::t("Address lat is required"),
		  'addresss_lng'=>Driver::t("Address long is required"),
		);
		$Validator=new Validator;
		
		if ( Driver::checkContactByName(Driver::getUserId(),$this->data['fullname'], $this->data['id']) ){
			$Validator->msg[]=t("Contact name already exist");
		}
		
		$Validator->required($req,$this->data);
		if($Validator->validate()){
			
			$params=array(
			  'fullname'=>$this->data['fullname'],
			  'email'=>isset($this->data['email'])?$this->data['email']:'',
			  'phone'=>$this->data['phone'],
			  'address'=>$this->data['address'],
			  'addresss_lat'=>$this->data['addresss_lat'],
			  'addresss_lng'=>$this->data['addresss_lng'],
			  'date_created'=>AdminFunctions::dateNow(),
			  'ip_address'=>$_SERVER['REMOTE_ADDR'],
			  'customer_id'=>Driver::getUserId(),
			  'status'=>$this->data['status']
			);
					
			$db=new DbExt;
				
			if (is_numeric($this->data['id'])){
				$params['date_modified']=AdminFunctions::dateNow();
				unset($params['date_created']);
				//dump($params);
				if ( $db->updateData("{{contacts}}",$params,'contact_id',$this->data['id'])){
					$this->code=1;
					$this->msg=t("Successfully updated");
				} else $this->msg=Driver::t("Something went wrong cannot update records");
			} else {
				if ( $db->insertData("{{contacts}}",$params)){
					$this->code=1;
					$this->msg=Driver::t("Successful");
				} else $this->msg=Driver::t("failed cannot insert record");
			}
			
		} else $this->msg=$Validator->getErrorAsHTML();
		$this->jsonResponse();
	}
	
	public function actionGetContactInfo()
	{		
		if ( $res=Driver::getContactByID($this->data['contact_id'],Driver::getUserId())){
			$this->msg="OK"; $this->code=1;
			$this->details=$res;			
		} else $this->msg=t("Contact not found");
		$this->jsonResponse();
	}
	
	public function actionLoadContactInfo()
	{
		$this->actionGetContactInfo();
	}
	
	public function actionLoadContactInfo2()
	{
		$this->actionGetContactInfo();
	}
	
	public function actionSaveServices()
	{
		if(!Driver::islogin()){
			$this->msg=Driver::t("Sorry but your session has expired");
			$this->jsonResponse();
			Yii::app()->end();
		}
				
		$customer_id=Driver::getUserId();		
		
		$params=array(
		  'services'=>isset($this->data['services_id'])?json_encode($this->data['services_id']):'',
		  'date_modified'=>AdminFunctions::dateNow()
		);
		$db=new DbExt;
		$db->updateData("{{customer}}",$params,'customer_id',$customer_id);
		
	    $this->code=1;
	    $this->msg=Yii::t("default","Setting saved");	
	    $this->jsonResponse();
	}
	
	public function actionUploadProfilePhoto()
	{
		require_once('Uploader.php');
		$path_to_upload=Driver::driverUploadPath();
        $valid_extensions = array('jpeg','jpg','png','gif'); 
        if(!file_exists($path_to_upload)) {	
           if (!@mkdir($path_to_upload,0777)){           	               	
           	    $this->msg=Driver::t("Error has occured cannot create upload directory");
                $this->jsonResponse();
           }		    
	    }
	    
        $Upload = new FileUpload('uploadfile');
        $ext = $Upload->getExtension(); 
        //$Upload->newFileName = mktime().".".$ext;
        $result = $Upload->handleUpload($path_to_upload, $valid_extensions);                
        if (!$result) {                    	
            $this->msg=$Upload->getErrorMsg();            
        } else {         	
        	$this->code=1;
        	$this->msg=Driver::t("upload done");        	        
			$this->details=Yii::app()->getBaseUrl(true)."/upload/photo/".$_GET['uploadfile'];			
        }
        $this->jsonResponse();
	}
	
	public function actionUploadcompanylogo()
	{
		require_once('Uploader.php');
		$path_to_upload=Driver::driverUploadPath();
        $valid_extensions = array('jpeg','jpg','png','gif'); 
        if(!file_exists($path_to_upload)) {	
           if (!@mkdir($path_to_upload,0777)){           	               	
           	    $this->msg=Driver::t("Error has occured cannot create upload directory");
                $this->jsonResponse();
           }		    
	    }
	    
        $Upload = new FileUpload('uploadfile');
        $ext = $Upload->getExtension(); 
        //$Upload->newFileName = mktime().".".$ext;
        $result = $Upload->handleUpload($path_to_upload, $valid_extensions);                
        if (!$result) {                    	
            $this->msg=$Upload->getErrorMsg();            
        } else {         	
        	$this->code=1;
        	$this->msg=Driver::t("upload done");        	        
			$this->details=Yii::app()->getBaseUrl(true)."/upload/photo/".$_GET['uploadfile'];			
        }
        $this->jsonResponse();
	}
	
	public function actionGetRemainingTask()
	{
		if($res=Driver::getTaskRemaining( Driver::getUserId(),Driver::getPlanID() )){									
			$this->code=1; 
			$this->msg="OK";
			$this->details=is_numeric($res)?$res:0;
		} else {
			$this->msg=t("Failed getting remaining task");
			$this->details=Driver::getTaskRemaining3( Driver::getUserId() );
		}
		$this->jsonResponse();
	}
	
	public function actionSmsloglist()
	{
		$aColumns = array(
		  'id',
		  'to_number',
		  'sms_text',
		  'provider',
		  'msg',
		  'date_created'		  
		);
		$t=AjaxDataTables::AjaxData($aColumns);		
		if (isset($_GET['debug'])){
		    dump($t);
		}
		
		if (is_array($t) && count($t)>=1){
			$sWhere=$t['sWhere'];
			$sOrder=$t['sOrder'];
			$sLimit=$t['sLimit'];
		}	
		
		$and= " AND customer_id = ".Driver::q( Driver::getUserId())." ";
					
				
		$stmt="SELECT SQL_CALC_FOUND_ROWS a.*			
		FROM
		{{sms_logs}} a
		WHERE 1
		$and		
		$sWhere
		$sOrder
		$sLimit
		";
		if (isset($_GET['debug'])){
		   dump($stmt);
		}
				
		$DbExt=new DbExt; 
		if ( $res=$DbExt->rst($stmt)){
			
			$iTotalRecords=0;						
			$stmtc="SELECT FOUND_ROWS() as total_records";
			if ( $resc=$DbExt->rst($stmtc)){									
				$iTotalRecords=$resc[0]['total_records'];
			}
			
			$feed_data['sEcho']=intval($_GET['sEcho']);
			$feed_data['iTotalRecords']=$iTotalRecords;
			$feed_data['iTotalDisplayRecords']=$iTotalRecords;										
			
			foreach ($res as $val) {
				$date_created=Yii::app()->functions->prettyDate($val['date_created'],true);
			    $date_created=Yii::app()->functions->translateDate($date_created);			
			    
			    $feed_data['aaData'][]=array(
			      $val['id'],
			      $val['to_number'],
			      $val['sms_text'],
			      $val['provider'],
			      $val['msg'],
			      $date_created
			    );			    
			}
			if (isset($_GET['debug'])){
			   dump($feed_data);
			}
			$this->otableOutput($feed_data);	
		}
		$this->otableNodata();
	}
	
	public function actionGetSMSBalance()
	{
		$id = Driver::getUserId();
		if ( $res=AdminFunctions::getCustomerByID($id)){
			if ($res['with_sms']==1){
				$balance=Driver::getSMSBalance($id);
				if ( $balance['code']==1){
					if ( $balance['balance']<=0){
						$this->code=1; $this->msg=t("you have insufficient sms credits to send text message");
						//$this->msg.="<br/>";
						//$this->msg.='<a href="'.Yii::app()->createUrl('app/smspurchase').'">'.t("click here to purchase sms credits").'</a>';
						$this->details=$balance['balance'];
					} else $this->msg="HAS BALANCE";
				} else $this->msg="NO SMS BALANCE";
			} else $this->msg="NO SMS";
		} else $this->msg="ID NOT FOUND";
		$this->jsonResponse();
	}
	
	public function actionEmailLogs()
	{
		$aColumns = array(
		  'id',
		  'email_address',
		  'subject',
		  'content',
		  'status',
		  'date_created'		  
		);
		$t=AjaxDataTables::AjaxData($aColumns);		
		if (isset($_GET['debug'])){
		    dump($t);
		}
		
		if (is_array($t) && count($t)>=1){
			$sWhere=$t['sWhere'];
			$sOrder=$t['sOrder'];
			$sLimit=$t['sLimit'];
		}	
		
		$and= " AND customer_id = ".Driver::q( Driver::getUserId())." ";
					
				
		$stmt="SELECT SQL_CALC_FOUND_ROWS a.*			
		FROM
		{{email_logs}} a
		WHERE 1
		$and		
		$sWhere
		$sOrder
		$sLimit
		";
		if (isset($_GET['debug'])){
		   dump($stmt);
		}
				
		$DbExt=new DbExt; 
		if ( $res=$DbExt->rst($stmt)){
			
			$iTotalRecords=0;						
			$stmtc="SELECT FOUND_ROWS() as total_records";
			if ( $resc=$DbExt->rst($stmtc)){									
				$iTotalRecords=$resc[0]['total_records'];
			}
			
			$feed_data['sEcho']=intval($_GET['sEcho']);
			$feed_data['iTotalRecords']=$iTotalRecords;
			$feed_data['iTotalDisplayRecords']=$iTotalRecords;										
			
			foreach ($res as $val) {
				$date_created=Yii::app()->functions->prettyDate($val['date_created'],true);
			    $date_created=Yii::app()->functions->translateDate($date_created);			
			    
			    $status="<span class=\"tag ".$val['status']." \">".Driver::t($val['status'])."</span>";	
			    
			    $feed_data['aaData'][]=array(
			      $val['id'],
			      $val['email_address'],
			      $val['subject'],
			      $val['content'],
			      $status,
			      $date_created
			    );			    
			}
			if (isset($_GET['debug'])){
			   dump($feed_data);
			}
			$this->otableOutput($feed_data);	
		}
		$this->otableNodata();
	}
	
	public function actionSendBulkPush()
	{		
		if ( $this->data['team_id']<=0){
			$this->msg=t("Team is required");
			$this->jsonResponse();
		}
		$params=array(
		   'customer_id'=>Driver::getUserId(),
		   'team_id'=>$this->data['team_id'],
		   'push_title'=>$this->data['push_title'],
		   'push_message'=>$this->data['push_message'],
		   'date_created'=>AdminFunctions::dateNow(),
		   'ip_address'=>$_SERVER['REMOTE_ADDR']
		);
		$db=new DbExt;
		if ( $db->insertData("{{push_broadcast}}",$params)){
			$this->code=1;
			$this->msg=Driver::t("Your request has been receive please wait while the cron process your request");
		} else $this->msg=Driver::t("failed cannot insert record");
		$this->jsonResponse();
	}
	
	public function actionBroadcastLogs()
	{
	    $aColumns = array(
		  'broadcast_id',
		  'team_id',
		  'push_title',
		  'push_message',
		  'status',
		  'date_created'		  
		);
		$t=AjaxDataTables::AjaxData($aColumns);		
		if (isset($_GET['debug'])){
		    dump($t);
		}
		
		if (is_array($t) && count($t)>=1){
			$sWhere=$t['sWhere'];
			$sOrder=$t['sOrder'];
			$sLimit=$t['sLimit'];
		}	
		
		$and= " AND customer_id = ".Driver::q( Driver::getUserId())." ";
					
				
		$stmt="SELECT SQL_CALC_FOUND_ROWS a.*,
		(
			select team_name
			from {{driver_team}}
			where
			team_id=a.team_id
			limit 0,1
		) as team_name
		FROM
		{{push_broadcast}} a
		WHERE 1
		$and		
		$sWhere
		$sOrder
		$sLimit
		";
		if (isset($_GET['debug'])){
		   dump($stmt);
		}
				
		$DbExt=new DbExt; 
		if ( $res=$DbExt->rst($stmt)){
			
			$iTotalRecords=0;						
			$stmtc="SELECT FOUND_ROWS() as total_records";
			if ( $resc=$DbExt->rst($stmtc)){									
				$iTotalRecords=$resc[0]['total_records'];
			}
			
			$feed_data['sEcho']=intval($_GET['sEcho']);
			$feed_data['iTotalRecords']=$iTotalRecords;
			$feed_data['iTotalDisplayRecords']=$iTotalRecords;										
			
			foreach ($res as $val) {
				$date_created=Yii::app()->functions->prettyDate($val['date_created'],true);
			    $date_created=Yii::app()->functions->translateDate($date_created);			
			    
			    $status="<span class=\"tag ".$val['status']." \">".Driver::t($val['status'])."</span>";	
			    
			    $view='<a href="'.Yii::app()->createUrl('/app/pushlogs',array(
			     'broadcast_id'=>$val['broadcast_id']
			    )).'" class="btn btn-info">'.t("view").'</a>';
			    
			    if ( $val['status']=="pending"){
			    	$view='';
			    }
			    
			    $feed_data['aaData'][]=array(
			      $val['broadcast_id'],
			      $val['team_name'],
			      $val['push_title'],
			      $val['push_message'],
			      $status,
			      $date_created,
			      $view
			    );			    
			}
			if (isset($_GET['debug'])){
			   dump($feed_data);
			}
			$this->otableOutput($feed_data);	
		}
		$this->otableNodata();
	}
	
	public function actionloadAgentTrackBack()
	{
		if(Driver::islogin()){					
			if ( $res=Driver::getBackTrackRecords(
			Driver::getUserId(),$this->data['track_driver_id'],$this->data['track_date']
			)){
				
				$this->code=1; $this->msg=t("Successful");
				$this->details=$res;
			} else $this->msg=t("Records not found");
		} else $this->msg = t("Session has expired");
		
		$this->jsonResponse();
	}
	
	public function actioncheckActivity()
	{
		if(Driver::islogin()){
		   $user_id = Driver::getUserId();
		   
		   $date_now = date("Y-m-d");
		   $res=''; $resp='';		  
		   $found = false;
		   
		   if ($res = Driver::checkNewTask($user_id, $date_now) ){
		   	  $res[]='checkNewTask';
			  $found  = true;
		   }
		   
		   if ($resp = Driver::checkNewUpdatedDriver($user_id)){
		   	   $resp[]='checkNewUpdatedDriver';
			   $found  = true;
		   }
		   if($resp = Driver::checkNewOfflineDriver($user_id)){
		   	   $resp[]='checkNewOfflineDriver';
			   $found  = true;
 		   }
 		   
 		   if($resp=Driver::getOfflineDriver($user_id)){
 		   	  $resp[]='getOfflineDriver';
 		   	  $found  = true;
 		   }
 		   
 		   $enabled_critical_task = getOption($user_id,'enabled_critical_task');
		   $critical_minutes = getOption($user_id,'critical_minutes');
		   if($critical_minutes<=0){
			  $critical_minutes=5;
		   }	
		   		   
		   if($enabled_critical_task==1){
			  if(Driver::checkCriticalTask($user_id,$critical_minutes)){
				 $found  = true;
			  }
		   }
		   
		   if($resp=Driver::checkNewUpdatedtask($user_id)){
		   	   $found  = true;
		   	   $resp[]='checkNewUpdatedtask';
		   }
		   
		   if($found){
			$this->code = 1;
				$this->msg = "there is activity";
				$this->details = array(
				  'res'=>$res,
				  'resp'=>$resp
				);
		  } else $this->msg = "no changes";
		   
		} else $this->msg = t("Session has expired");
		$this->jsonResponse();
	}
	
	public function actionloadTrackDate()
	{		
		$html='<option value="-1">'.t("Please select").'</option>';
		$driver_id = isset($this->data['driver_id'])?$this->data['driver_id']:'';
		
		$user_id = Driver::getUserId();
		if($user_id>0){
			if ($res = Driver::backTrackList2($driver_id,$user_id)){
				foreach ($res as $val) {
					$html.='<option value="'.$val['date_log'].'">'.$val['date_log'].'</option>';
				}
				$this->code = 1;
				$this->msg = "OK";
				$this->details = $html;
			} else $this->msg = "no results";
		} else $this->msg = t("Sorry but your session has expired");
		$this->jsonResponse();
	}
	
	public function actionloadFilterForm()
	{
		$html = Yii::app()->controller->renderPartial('/app/filter_map_form',array(
		), true);		
		$this->code = 1;
		$this->msg="OK";
		$this->details = $html;
		$this->jsonResponse();
	}
	
	public function actionmapFilterSettings()
	{
		Yii::app()->functions->updateOption('map_hide_pickup',
		  isset($this->data['map_hide_pickup'])?$this->data['map_hide_pickup']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('map_hide_delivery',
		  isset($this->data['map_hide_delivery'])?$this->data['map_hide_delivery']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('map_hide_success_task',
		  isset($this->data['map_hide_success_task'])?$this->data['map_hide_success_task']:'',
		  Driver::getUserId()
		);
		
		Yii::app()->functions->updateOption('driver_include_offline_driver_map',
		  isset($this->data['driver_include_offline_driver_map'])?$this->data['driver_include_offline_driver_map']:'',
		  Driver::getUserId()
		);
		
		$this->code=1;
	    $this->msg=Yii::t("default","Setting saved");	
		$this->jsonResponse();
	}
	
}/* end class*/