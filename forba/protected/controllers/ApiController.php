<?php
class ApiController extends CController
{	
	public $data;
	public $code=2;
	public $msg='';
	public $details='';
	
	public function __construct()
	{
		$this->data=$_GET;
		
		$website_timezone=Yii::app()->functions->getOptionAdmin("website_timezone");		 
	    if (!empty($website_timezone)){
	 	   Yii::app()->timeZone=$website_timezone;
	    }		 
	    
	    if(isset($_GET['lang_id'])){
		 	Yii::app()->language=$_GET['lang_id'];
		}	    
	}
	
	public function beforeAction($action)
	{				
		/*check if there is api has key*/	
		$action=Yii::app()->controller->action->id;
		$continue=true;
		//if($action=="getLanguageSettings" || $action=="GetAppSettings"){		
		$action=strtolower($action);
		if($action=="getlanguagesettings" || $action=="getappsettings" || $action=="uploadprofile" || $action=="uploadtaskphoto" || $action=="updatedriverlocation"){
	   	   $continue=false;
	    }	    
	    if($continue){	    	
	    	$key=getOptionA('mobile_api_key');
	    	if(!empty($key)){
		    	if(!isset($this->data['api_key'])){
		    		$this->data['api_key']='';
		    	}
		    	if(trim($key)!=trim($this->data['api_key'])){
				   $this->msg=$this->t("api hash key is not valid");
			       $this->output();
			       Yii::app()->end();
				}
	    	}
	    }
		return true;
	}	
	
	public function actionIndex(){
		echo 'Api is working';
	}		
	
	private function q($data='')
	{
		return Yii::app()->db->quoteValue($data);
	}
	
	private function t($message='')
	{
		return Yii::t("default",$message);
	}
		
    private function output()
    {
    	
       if (!isset($this->data['debug'])){    		
       	  header('Access-Control-Allow-Origin: *');
          header('Content-type: application/javascript;charset=utf-8');
       } 
       
	   $resp=array(
	     'code'=>$this->code,
	     'msg'=>$this->msg,
	     'details'=>$this->details,
	     'request'=>json_encode($this->data)		  
	   );		   
	   if (isset($this->data['debug'])){
	   	   dump($resp);
	   }
	   
	   if (!isset($_GET['callback'])){
  	   	   $_GET['callback']='';
	   }    
	   
	   if (isset($_GET['json']) && $_GET['json']==TRUE){
	   	   echo CJSON::encode($resp);
	   } else echo $_GET['callback'] . '('.CJSON::encode($resp).')';		    	   	   	  
	   Yii::app()->end();
    }		
    
    public function actionLogin()
    {
    	if(!empty($this->data['username']) && !empty($this->data['password'])){
	    	if ( $res=Driver::driverAppLogin($this->data['username'],$this->data['password'])){	
	    		$token=md5(Driver::generateRandomNumber(5) . $this->data['username']);
	    		
	    		$customer_id=$res['customer_id'];	    
	    		Driver::setCustomerTimezone($customer_id);
	    		
	    		$params=array(
	    		  'last_login'=>AdminFunctions::dateNow(),
	    		  'last_online'=>strtotime("now"),
	    		  'ip_address'=>$_SERVER['REMOTE_ADDR'],
	    		  'token'=>$token,
	    		  'device_id'=>isset($this->data['device_id'])?$this->data['device_id']:'',
	    		  'device_platform'=>isset($this->data['device_platform'])?$this->data['device_platform']:'Android',
	    		  'on_duty'=>1,
	    		  'is_online'=>1,
	    		  'app_version'=>isset($this->data['app_version'])?$this->data['app_version']:''
	    		);	    		
	    		
	    		$res['on_duty']=1;
	    		
	    		if(!empty($res['token'])){
	    			unset($params['token']);
	    			$token=$res['token'];
	    		}
	    		$db=new DbExt;
	    		if ( $db->updateData("{{driver}}",$params,'driver_id',$res['driver_id'])){	    			
	    			$this->code=1;
	    			$this->msg=self::t("Login Successful");
	    			
	    			//get location accuracy
	    			$location_accuracy=2;
	    			if ( $team=Driver::getTeam($res['team_id'])){
	    				//dump($team);
	    				if($team['location_accuracy']=="high"){
	    					$location_accuracy=1;
	    				}
	    			}
	    			
	    			$app_track_interval=getOption($res['customer_id'],'app_track_interval');
	    			if (!is_numeric($app_track_interval)){
	    				$app_track_interval=8000;
	    			} else $app_track_interval=$app_track_interval*1000;
	    			
	    			if ($app_track_interval<=0){
	    				$app_track_interval=8000;
	    			}
	    			
	    			$this->details=array(
	    			  'username'=>$this->data['username'],
	    			  'password'=>$this->data['password'],
	    			  'remember'=>isset($this->data['remember'])?$this->data['remember']:'',
	    			  'todays_date'=>Yii::app()->functions->translateDate(date("M, d")),
	    			  'todays_date_raw'=>date("Y-m-d"),
	    			  'on_duty'=>$res['on_duty'],
	    			  'token'=>$token,
	    			  'duty_status'=>$res['on_duty'],
	    			  'location_accuracy'=>$location_accuracy,
	    			  'device_vibration'=>getOption($res['customer_id'],'driver_device_vibration'),
	    			  'app_disabled_bg_tracking'=>getOption($res['customer_id'],'app_disabled_bg_tracking'),
	    			  'app_track_interval'=>$app_track_interval
	    			);
	    		} else $this->msg=self::t("Login failed. please try again later");
	    	} else $this->msg=self::t("Login failed. either username or password is incorrect");
    	} else $this->msg=self::t("Please fill in your username and password");
    	$this->output();
    }
    
    public function actionForgotPassword()
    {
    	if (empty($this->data['email'])){
    		$this->msg=self::t("Email address is required");
    		$this->output();
    		Yii::app()->end();
    	}
    	$db=new DbExt;    	
    	if ( $res=Driver::driverForgotPassword($this->data['email'])){
    		$driver_id=$res['driver_id'];    		
    		$code=Driver::generateRandomNumber(5);
    		$params=array('forgot_pass_code'=>$code);
    		if($db->updateData('{{driver}}',$params,'driver_id',$driver_id)){
    			$this->code=1;
    			$this->msg=self::t("We have send the a password change code to your email");
    			
    			$tpl=EmailTemplate::forgotPasswordRequest();
    			$tpl=smarty('first_name',$res['first_name'],$tpl);
    			$tpl=smarty('code',$code,$tpl);
    			$subject='Forgot Password';
    			if ( sendEmail($res['email'],'',$subject,$tpl)){
    				$this->details="send email ok";
    			} else $this->msg="send email failed";
    			
    		} else $this->msg=self::t("Something went wrong please try again later");
    	} else $this->msg=self::t("Email address not found");
    	$this->output();
    }
    
    public function actionChangePassword()
    {    	
    	$Validator=new Validator;
    	$req=array(
    	  'email_address'=>self::t("Email address is required"),
    	  'code'=>self::t("Code is required"),
    	  'newpass'=>self::t("New Password is required")
    	);
    	$Validator->required($req,$this->data);
    	if ( $Validator->validate()){
    		if ( $res=Driver::driverForgotPassword($this->data['email_address'])){    			
    			if ( $res['forgot_pass_code']==$this->data['code']){
    				$params=array( 
    				  'password'=>md5($this->data['newpass']),
    				  'date_modified'=>AdminFunctions::dateNow(),
    				  'forgot_pass_code'=>Driver::generateRandomNumber(5)
    				 );
    				$db=new DbExt;    				
    				if ( $db->updateData("{{driver}}",$params,'driver_id',$res['driver_id'])){
    				    $this->code=1;
    				    $this->msg=self::t("Password successfully changed");
    				} else $this->msg=self::t("Something went wrong please try again later");    				
    			} else $this->msg=self::t("Invalid password code");
    		} else $this->msg=self::t("Email address not found");
    	} else $this->msg=Driver::parseValidatorError($Validator->getError());		
    	$this->output();
    }
    
    public function actionChangeDutyStatus()
    {    	
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		$this->output();
    		Yii::app()->end();
    	} 
    	$driver_id=$token['driver_id'];
    	    	
    	Driver::setCustomerTimezone( $token['customer_id'] );    	
    	
    	$params=array(
    	  'on_duty'=>isset($this->data['onduty'])?$this->data['onduty']:2,
    	  'last_online'=>strtotime("now"),
    	  'last_onduty'=>strtotime("now"),
    	);
    	if ( $this->data['onduty']==2){
    		//$params['last_online']=time() - 300;
    		$tracking_type=Driver::getTrackingOptions( $token['customer_id'] );
    	    if ($tracking_type==2){
    	        $params['last_online']=strtotime("-35 minutes");
    	    } else $params['last_online']=strtotime("-20 minutes");
    	    
    	    $params['is_online']=2;
    	    
    	} else $params['is_online']=1;
    	
    	$db=new DbExt;
    	if ( $db->updateData('{{driver}}',$params,'driver_id',$driver_id)){
    		$this->code=1;
    		$this->msg="OK";
    		$this->details=$this->data['onduty'];
    	} else $this->msg=self::t("Something went wrong please try again later");   
    	$this->output();
    }
    
    public function actionGetTaskByDate()
    {    	
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		$this->output();
    		Yii::app()->end();
    	}     	
    	$driver_id=$token['driver_id'];    	
    	
    	Driver::setCustomerTimezone( $token['customer_id'] );
    	
    	if (isset($this->data['onduty'])){
    		if ($this->data['onduty']==1){    			
    	        Driver::updateLastOnline($driver_id);
    		}
    	}
    	
    	$task_type = isset($this->data['task_type'])?$this->data['task_type']:'pending';
    	if(empty($task_type)){
    		$task_type='pending';
    	}    	
    	
    	//if ( $res=Driver::getTaskByDriverID($driver_id,$this->data['date'])){
    	if ( $res=Driver::getTaskByDriverIDWithAssigment($driver_id,$this->data['date'],$task_type)){
    		$this->code=1;
    		$this->msg="OK";
    		$data=array();
    		foreach ($res as $val) {
    			$val['delivery_time']=Yii::app()->functions->timeFormat($val['delivery_date'],true);
    			$val['status_raw']=$val['status'];
    			$val['status']=self::t($val['status']);    			
    			$val['trans_type_raw']=$val['trans_type'];
    			$val['trans_type']=self::t($val['trans_type']);    			
    			$data[]=$val;
    		}
    		$this->details=$data;
    	} else $this->msg=self::t("No task for the day");
    	$this->output();
    }
    
    public function actionviewTaskDescription()
    {
    	$this->actionTaskDetails();
    }
    public function actionTaskDetails()
    {    	
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		$this->output();
    		Yii::app()->end();
    	}   
    	    	
    	$customer_id=$token['customer_id'];    	
    	Driver::setCustomerTimezone( $token['customer_id'] );
    	
    	if (isset($this->data['task_id'])){
    		if ( $res=Driver::getTaskId($this->data['task_id']) ){
    			
    			//check task belong to current driver    			    			
    			if ( $res['status']!="unassigned"){
	    			$driver_id=$token['driver_id'];
	    			if ($driver_id!=$res['driver_id']){
	    				$this->msg=Driver::t("Sorry but this task is already been assigned to others");
	    				$this->output();
	    				Yii::app()->end();
	    			}    			
    			}
    			
    			$this->code=1;
    			$this->msg=self::t("Task").":".$this->data['task_id'];
    			
    			$res['delivery_time']=Yii::app()->functions->timeFormat($res['delivery_date'],true);    			
    			$res['status_raw']=$res['status'];
    			$res['status']=self::t($res['status']);    			
    			$res['trans_type_raw']=$res['trans_type'];
    			$res['trans_type']=self::t($res['trans_type']);
    			
    			$res['history']=Driver::getDriverTaskHistory($this->data['task_id']);
    			
    			/*get signature if any*/
    			$res['customer_signature_url']='';
    			if (!empty($res['customer_signature'])){
    				$res['customer_signature_url']=Driver::uploadURL()."/".$res['customer_signature'];
    				if (!file_exists(Driver::uploadPath()."/".$res['customer_signature'])){
    					$res['customer_signature_url']='';
    				}
    			}
    			
    			$res['driver_enabled_notes']=getOption( $customer_id , 'driver_enabled_notes');
    			$res['driver_enabled_signature']=getOption( $customer_id , 'driver_enabled_signature');
    			$res['driver_enabled_photo']=getOption( $customer_id , 'driver_enabled_photo');
    			$res['history_notes']['total']=Driver::getNotesTotal( $this->data['task_id'] );
    			$res['task_photo']['total']=Driver::getTaskPhotoTotal( $this->data['task_id'] );
    			
    			$res['map_icons']=array(
    			  'driver'=>websiteUrl()."/assets/images/car.png",
				  'customer'=>websiteUrl()."/assets/images/racing-flag.png",
				  'merchant'=>websiteUrl()."/assets/images/restaurant-pin-32.png",
    			);
    			
    			$app_enabled_resize_pic=getOption($customer_id,'app_enabled_resize_pic');
    			$app_resize_width=getOption($customer_id,'app_resize_width');
    			$app_resize_height=getOption($customer_id,'app_resize_height');
    			
    			if ( $app_resize_width<=0){
    				$app_enabled_resize_pic=2;
    			}
    			if ( $app_resize_height<=0){
    				$app_enabled_resize_pic=2;
    			}
    			if (empty($app_enabled_resize_pic)){
    				$app_enabled_resize_pic=2;
    			}
    			
    			$res['resize_picture']=array(
    			   'app_enabled_resize_pic'=>$app_enabled_resize_pic,
    			   'app_resize_width'=>$app_resize_width,
    			   'app_resize_height'=>$app_resize_height,
    			);
    			    			    					
    			$this->details=$res;
    		} else $this->msg=self::t("Task not found");
    	} else $this->msg=self::t("Task id is missing");
    	$this->output();
    }
	
    public function actionChangeTaskStatus()
    {
    	
    	if(isset($_GET['debug'])){
    	   dump($this->data);
    	}
    	
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		$this->output();
    		Yii::app()->end();
    	}     	
    	$driver_id=$token['driver_id'];    	
    	$team_id=$token['team_id']; 
    	$driver_name=$token['first_name'] ." " .$token['last_name'];    
    	
    	Driver::setCustomerTimezone( $token['customer_id'] );	
    	
    	$db=new DbExt;	
    	
    	if (isset($this->data['status_raw']) && isset($this->data['task_id'])){
    		
    		$task_id=$this->data['task_id'];
    		$task_info=Driver::getTaskId($task_id);
    		if(!$task_info){
    			$this->msg=self::t("Task not found");
    			$this->output();
    			Yii::app()->end();
    		}    		
    		
    		$params_history=array();    		
    		$params_history['ip_address']=$_SERVER['REMOTE_ADDR'];
    	    $params_history['date_created']=AdminFunctions::dateNow();
    	    $params_history['task_id']=$task_id;    	    
    	    $params_history['driver_id']=$driver_id;        	    
    	    $params_history['driver_location_lat']=isset($token['location_lat'])?$token['location_lat']:'';
    	    $params_history['driver_location_lng']=isset($token['location_lng'])?$token['location_lng']:'';
    	    
    				
    		
    		switch ($this->data['status_raw']) {
    			
    			case "failed":
    			case "cancelled":    	
    			   $params=array('status'=>$this->data['status_raw']);    				
    				// update task id
    				$db->updateData("{{driver_task}}",$params,'task_id',$task_id);
    				
    				$remarks=Driver::driverStatusPretty($driver_name,$this->data['status_raw']);    				
    				$params_history['status']=$this->data['status_raw'];
    				$params_history['remarks']=$remarks; 			    				
    				$params_history['reason']=isset($this->data['reason'])?$this->data['reason']:'' ; 
    				// insert history    				
    				$db->insertData("{{task_history}}",$params_history);
    				
    				$this->code=1;
    				$this->msg="OK";
    				$this->details=array(
    				  'task_id'=>$this->data['task_id'],
    				  'status_raw'=>$params['status'],
    				  'reload_functions'=>'getTodayTask'
    				);    				
    				
    				//send notification to customer
    				if ( $task_info['trans_type']=="delivery"){    					
    				    Driver::sendNotificationCustomer('DELIVERY_FAILED',$task_info);
    				} else {
    					Driver::sendNotificationCustomer('PICKUP_FAILED',$task_info);
    				}
    							
    				break;
    				
    			case "declined":
    				
    				if ( $assigment_info=Driver::getAssignmentByDriverTaskID($driver_id,$task_id)){
    					
    					$stmt_assign="UPDATE 
    					{{driver_assignment}}
    					SET task_status='declined',
    					date_process=".Driver::q(AdminFunctions::dateNow()).",
    					ip_address=".Driver::q($_SERVER['REMOTE_ADDR'])."
    					WHERE
    					task_id=".Driver::q($task_id)."
    					AND
    					driver_id=".Driver::q($driver_id)."
    					";
    					//dump($stmt_assign);
    					$db->qry($stmt_assign);
    					
    					$this->code=1;
	    				$this->msg="OK";
	    				$this->details=array(
	    				  'task_id'=>$this->data['task_id'],
	    				  'status_raw'=>'declined',
	    				  'reload_functions'=>'getTodayTask'
	    				);    				
	    				
    				} else {
	    				$params=array('status'=>"declined");    
	    				//dump($params);
	    				// update task id
	    				$db->updateData("{{driver_task}}",$params,'task_id',$task_id);
	    				
	    				$remarks=Driver::driverStatusPretty($driver_name,'declined');    				
	    				$params_history['status']='declined';
	    				$params_history['remarks']=$remarks;  
	    				$params_history['reason'] ='';  				    				
	    				// insert history    				
	    				$db->insertData("{{task_history}}",$params_history);
	    				
	    				$this->code=1;
	    				$this->msg="OK";
	    				$this->details=array(
	    				  'task_id'=>$this->data['task_id'],
	    				  'status_raw'=>$params['status'],
	    				  'reload_functions'=>'getTodayTask'
	    				);    				
	    				
	    				//send email to admin or merchant
    				}
    				
    				break;
    				
    				
    			case "acknowledged":    		
    			
    			    // double check if someone has already the accept task   			    
    			    if($task_info['status']!="unassigned"){        			    	
    			    	if ( $task_info['driver_id']!=$driver_id){			    	
    			           $this->msg=Driver::t("Sorry but this task is already been assigned to others");
    			           $this->output();
    			    	   Yii::app()->end();
    			    	}
    			    }
    			    
    				$params=array(
    				  'driver_id'=>$driver_id,
    				  'status'=>"acknowledged",
    				  'team_id'=>$team_id
    				);    	
    				
    				// update task id    				
    				$db->updateData("{{driver_task}}",$params,'task_id',$task_id);
    				
    				$remarks=Driver::driverStatusPretty($driver_name,'acknowledged');
    				$params_history['status']='acknowledged';
    				$params_history['remarks']=$remarks; 
    				$params_history['reason'] ='';
    				// insert history     				
    				$db->insertData("{{task_history}}",$params_history);
    				
    				$this->code=1;
    				$this->msg="OK";
    				$this->details=array(
    				  'task_id'=>$this->data['task_id'],
    				  'status_raw'=>$params['status'],
    				  'reload_functions'=>'TaskDetails'
    				);    				
    				
    				//update driver_assignment
    				$stmt_assign="UPDATE
    				{{driver_assignment}}
    				SET task_status='acknowledged'
    				WHERE task_id=".Driver::q($task_id)."
    				";
    				$db->qry($stmt_assign);
    				
    				//send notification to customer
    				if ( $task_info['trans_type']=="delivery"){  
    				   Driver::sendNotificationCustomer('DELIVERY_REQUEST_RECEIVED',$task_info);
    				} else {
    				   Driver::sendNotificationCustomer('PICKUP_REQUEST_RECEIVED',$task_info);
    				}
    				
    				break;
    				
    			case "started":	
    			    $params=array('status'=>"started");
    			    $db->updateData("{{driver_task}}",$params,'task_id',$task_id);
    				// update task id
    				
    				$remarks=Driver::driverStatusPretty($driver_name,'started');   
    				$params_history['status']='started';
    				$params_history['remarks']=$remarks;    	
    				$params_history['reason'] ='';			
    				// insert history
    				$db->insertData("{{task_history}}",$params_history);
    				
    				$this->code=1;
    				$this->msg="OK";
    				$this->details=array(
    				  'task_id'=>$this->data['task_id'],
    				  'status_raw'=>$params['status'],
    				  'reload_functions'=>'TaskDetails'
    				);    		
    				
    				//send notification to customer
    				if ( $task_info['trans_type']=="delivery"){  
    				    Driver::sendNotificationCustomer('DELIVERY_DRIVER_STARTED',$task_info);
    				} else {
    					Driver::sendNotificationCustomer('PICKUP_DRIVER_STARTED',$task_info);
    				}
    						
    				break;    			   
    		
    			case "inprogress":
    				 $params=array('status'=>"inprogress");
    				 $db->updateData("{{driver_task}}",$params,'task_id',$task_id);
    				// update task id
    				
    				$remarks=Driver::driverStatusPretty($driver_name,'inprogress');    				
    				$params_history['status']='inprogress';
    				$params_history['remarks']=$remarks;    	
    				$params_history['reason'] ='';						
    				// insert history
    				$db->insertData("{{task_history}}",$params_history);
    				
    				$this->code=1;
    				$this->msg="OK";
    				$this->details=array(
    				  'task_id'=>$this->data['task_id'],
    				  'status_raw'=>$params['status'],
    				  'reload_functions'=>'TaskDetails'
    				);    			
    				
    				//send notification to customer
    				if ( $task_info['trans_type']=="delivery"){  
    				   Driver::sendNotificationCustomer('DELIVERY_DRIVER_ARRIVED',$task_info);
    				} else {
    				   Driver::sendNotificationCustomer('PICKUP_DRIVER_ARRIVED',$task_info);
    				}
    				
    				break;
    				
    			case "successful":	    			   
    			    $params=array('status'=>"successful");
    			    $db->updateData("{{driver_task}}",$params,'task_id',$task_id);
    				// update task id
    				
    				$remarks=Driver::driverStatusPretty($driver_name,'successful');    				
    				$params_history['status']='successful';
    				$params_history['remarks']=$remarks;    
    				$params_history['reason'] ='';				
    				// insert history
    				$db->insertData("{{task_history}}",$params_history);
    				
    				$this->code=1;
    				$this->msg="OK";
    				$this->details=array(
    				  'task_id'=>$this->data['task_id'],
    				  'status_raw'=>$params['status'],
    				  'reload_functions'=>'getTodayTask'
    				);    			
    				
    				//send notification to customer
    				if ( $task_info['trans_type']=="delivery"){  
    				    Driver::sendNotificationCustomer('DELIVERY_SUCCESSFUL',$task_info);
    				} else {
    					Driver::sendNotificationCustomer('PICKUP_SUCCESSFUL',$task_info);
    				}
    				
    				break;
    				   
    			default:
    				$this->msg=self::t("Missing status");
    				break;
    		}
    	} else $this->msg=self::t("Missing parameters");
    	
    	$this->output();
    }
    
    public function actionAddSignatureToTask()
    {
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		$this->output();
    		Yii::app()->end();
    	}     	
    	$driver_id=$token['driver_id'];    	
    	
    	Driver::setCustomerTimezone( $token['customer_id'] );
    	
    	if ( isset($this->data['image'])){
    		    		
    		if ($this->data['image']=="image/jsignature;base30,"){
    			$this->msg=self::t("Signature is required");
    			$this->output();
    		    Yii::app()->end();
    		}
    		
	    	$path_to_upload=Yii::getPathOfAlias('webroot')."/upload";      	
	    	if (!file_exists($path_to_upload)){
	    		if (!@mkdir($path_to_upload,0777)){           	    
	    			$this->msg=self::t("Failed cannot create folder"." ".$path_to_upload);
           	        Yii::app()->end();
                }		    
	    	}
	    	
	    	$filename="signature_".$this->data['task_id'] . "-" . Driver::generateRandomNumber(10) .".png";
	    	//$filename="signature_".$this->data['task_id'] . "-.png";
	    	
	    	/*$img = $this->data['image'];
	    	$img = str_replace('data:image/png;base64,', '', $img);
	        $img = str_replace(' ', '+', $img);
	        $data = base64_decode($img);
	        @file_put_contents($path_to_upload."/$filename", $data);*/
	    	
	    	
	    	$img = $this->data['image'];	   	    	
	    	Driver::base30_to_jpeg($img, $path_to_upload."/$filename");	    	
	        	        
	        $params=array(
	          'customer_signature'=>$filename,
	          'date_modified'=>AdminFunctions::dateNow(),
	          'ip_address'=>$_SERVER['REMOTE_ADDR']
	        );
	        
	        $task_id=$this->data['task_id'];	  
	        $driver_name=$token['first_name'] ." " .$token['last_name'];         

	        $db=new DbExt;		        
	        
	        $task_id=$this->data['task_id'];
    		$task_info=Driver::getTaskId($task_id);
    		if(!$task_info){
    			$this->msg=self::t("Task not found");
    			$this->output();
    			Yii::app()->end();
    		}    		
	        
	        if ( $db->updateData("{{driver_task}}",$params,'task_id',$task_id)){
		        $this->code=1;
		        $this->msg="Successful";      
		        $this->details=$this->data['task_id'];	
		        
		        $remarks=Driver::driverStatusPretty($driver_name,'sign');  
		        $params_history=array(
		           'status'=>'sign',
		           'remarks'=>$remarks,
		           'date_created'=>AdminFunctions::dateNow(),
		           'ip_address'=>$_SERVER['REMOTE_ADDR'],
		           'task_id'=>$task_id,
		           'customer_signature'=>$filename ,		           
		           'driver_id'=>$driver_id,
		           'driver_location_lat'=>isset($token['location_lat'])?$token['location_lat']:'',
		           'driver_location_lng'=>isset($token['location_lng'])?$token['location_lng']:'',
		           'reason'=>'',
		           'receive_by'=>isset($this->data['receive_by'])?$this->data['receive_by']:'',
		           'signature_base30'=>$this->data['image']
		        );
		        
		        if ( $this->data['signature_id']>0){
		        	$db->updateData("{{task_history}}",$params_history,'id',$this->data['signature_id']);
		        } else $db->insertData("{{task_history}}",$params_history);       
		        		        
 			    $task_info['signature_link']=websiteUrl()."/upload/$filename";		
 			    if ( $task_info['trans_type']=="delivery"){ 
 				   Driver::sendCustomerNotification('DELIVERY_SIGNATURE',$task_info);
 			    } else Driver::sendCustomerNotification('PICKUP_SIGNATURE',$task_info);	           
		        	       
	        } else $this->msg=self::t("Something went wrong please try again later");
	        
    	} else $this->msg=self::t("Signature is required");
    	$this->output();     
    }
    
    public function actionCalendarTask()
    {    	
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		$this->output();
    		Yii::app()->end();
    	}     	
    	$driver_id=$token['driver_id'];       	
    	Driver::setCustomerTimezone( $token['customer_id'] );	
    	
    	if (isset($this->data['start']) && isset($this->data['end'])){
    		$start=$this->data['start'] ." 00:00:00";
    		$end=$this->data['end'] ." 23:59:00";    		
    		$data=array();
    		if ( $res=Driver::getDriverTaskCalendar($driver_id,$start,$end)){
    			//dump($res);
    			 foreach ($res as $val) {    			 	
    			 	$data[]=array(
    			 	  'title'=> Driver::getTotalTaskByDate($driver_id,$val['delivery_date']),
    			 	  'id'=>$val['delivery_date'],
    			 	  'year'=>date("Y",strtotime($val['delivery_date'])),
    			 	  'month'=>date("m",strtotime($val['delivery_date'] ." -1 months" )),
    			 	  'day'=>date("d",strtotime($val['delivery_date'])),
    			 	);
    			 }
    			 $this->code=1;
    			 $this->msg="OK";
    			 $this->details=$data;
    		}
    	} else $this->msg=self::t("Missing parameters");
    	
    	$this->output();     
    }
    
    public function actionGetProfile()
    {    	
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		$this->output();
    		Yii::app()->end();
    	}     	
    	$driver_id=$token['driver_id'];    	
    	$info=Driver::driverInfo($driver_id); 
    	
    	$profile_photo='';
    	if(!empty($info['profile_photo'])){
    		$profile_photo_path=Driver::driverUploadPath()."/".$info['profile_photo'];
    		if(file_exists($profile_photo_path)){
    			$profile_photo=websiteUrl()."/upload/photo/".$info['profile_photo'];
    		}
    	}
    	    	  
    	$this->code=1;
    	$this->msg="OK";
    	$this->details=array(
    	  'team_name'=>$info['team_name'],
    	  'email'=>$info['email'],
    	  'phone'=>$info['phone'],
    	  'transport_type_id'=>$info['transport_type_id'],
    	  'transport_type_id2'=>ucwords(self::t($info['transport_type_id'])),
    	  'transport_description'=>$info['transport_description'],
    	  'licence_plate'=>$info['licence_plate'],
    	  'color'=>$info['color'],
    	  'profile_photo'=>$profile_photo,
    	  'full_name'=>$info['first_name']." ".$info['last_name'],
    	  'transport_list'=>Driver::transportType()
    	);
    	$this->output();     
    }
    
    public function actionGetTransport()
    {    	
    	$this->code=1;
    	$this->code=1;
    	$this->details=Driver::transportType();
    	$this->output();     
    }
    
    public function actionUpdateProfile()
    {    	
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		$this->output();
    		Yii::app()->end();
    	}     	
    	$driver_id=$token['driver_id']; 
    	Driver::setCustomerTimezone( $token['customer_id'] );
    	
    	$Validator=new Validator;
    	$req=array(
    	  'phone'=>self::t("Phone is required")    	  
    	);
    	$Validator->required($req,$this->data);
    	if ( $Validator->validate()){
    		$params=array(
    		  'phone'=>$this->data['phone'],
    		  'date_modified'=>AdminFunctions::dateNow(),
    		  'ip_address'=>$_SERVER['REMOTE_ADDR']
    		);
    		$db=new DbExt;
    		if ( $db->updateData("{{driver}}",$params,'driver_id',$driver_id)){
    			$this->code=1;
    			$this->msg=self::t("Profile Successfully updated");
    		} else $this->msg=self::t("Something went wrong please try again later");
    	} else $this->msg=Driver::parseValidatorError($Validator->getError());
    	$this->output();     
    }
    
    public function actionUpdateVehicle()
    {    	
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		$this->output();
    		Yii::app()->end();
    	}     	
    	$driver_id=$token['driver_id']; 
    	Driver::setCustomerTimezone( $token['customer_id'] );
    	
    	$Validator=new Validator;
    	$req=array(
    	  'transport_type_id'=>self::t("Transport Type is required"),
    	  'transport_description'=>self::t("Description is required"),
    	  /*'licence_plate'=>self::t("License Plate is required"),
    	  'color'=>self::t("Color is required"),*/
    	);
    	if ( $this->data['transport_type_id']=="truck"){
    		unset($req);
    		$req=array(
    		  'transport_type_id'=>self::t("Transport Type is required")
    		);
    	}
    	$Validator->required($req,$this->data);
    	if ( $Validator->validate()){
    		$params=array(
    		  'transport_type_id'=>$this->data['transport_type_id'],
    		  'transport_description'=>$this->data['transport_description'],
    		  'licence_plate'=>isset($this->data['licence_plate'])?$this->data['licence_plate']:'',
    		  'color'=>isset($this->data['color'])?$this->data['color']:'',
    		  'date_modified'=>AdminFunctions::dateNow(),
    		  'ip_address'=>$_SERVER['REMOTE_ADDR']
    		);
    		$db=new DbExt;
    		if ( $db->updateData("{{driver}}",$params,'driver_id',$driver_id)){
    			$this->code=1;
    			$this->msg=self::t("Vehicle Info updated");
    		} else $this->msg=self::t("Something went wrong please try again later");
    	} else $this->msg=Driver::parseValidatorError($Validator->getError());
    	$this->output();     
    }
    
    public function actionProfileChangePassword()
    {
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		$this->output();
    		Yii::app()->end();
    	}     	
    	$driver_id=$token['driver_id']; 
    	
    	Driver::setCustomerTimezone( $token['customer_id'] );
    	
    	$Validator=new Validator;
    	$req=array(
    	  'current_pass'=>self::t("Current password is required"),
    	  'new_pass'=>self::t("New password is required"),
    	  'confirm_pass'=>self::t("Confirm password is required")    	  
    	);    	
    	if ( $this->data['new_pass']!=$this->data['confirm_pass']){
    		$Validator->msg[]=self::t("Confirm password does not macth with your new password");
    	}
    	
    	$Validator->required($req,$this->data);
    	if ( $Validator->validate()){
    		    		    		
    		if (!Driver::driverAppLogin($token['username'],$this->data['current_pass'])){
    			$this->msg=self::t("Current password is invalid");
    			$this->output();     
    			Yii::app()->end();
    		}    		
    		$params=array(
    		  'password'=>md5($this->data['new_pass']),
    		  'date_modified'=>AdminFunctions::dateNow(),
    		  'ip_address'=>$_SERVER['REMOTE_ADDR']
    		);
    		$db=new DbExt;
    		if ( $db->updateData("{{driver}}",$params,'driver_id',$driver_id)){
    			$this->code=1;
    			$this->msg=self::t("Password Successfully Changed");
    			$this->details=$this->data['new_pass'];
    		} else $this->msg=self::t("Something went wrong please try again later");
    	} else $this->msg=Driver::parseValidatorError($Validator->getError());
    	$this->output();     
    }
    
    public function actionSettingPush()
    {
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		$this->output();
    		Yii::app()->end();
    	}     	
    	$driver_id=$token['driver_id']; 
    	
    	Driver::setCustomerTimezone( $token['customer_id'] );
    	    	
    	$params=array(
    	  'enabled_push'=>$this->data['enabled_push'],
    	  'date_modified'=>AdminFunctions::dateNow(),
    	  'ip_address'=>$_SERVER['REMOTE_ADDR']
    	);
    	$db=new DbExt;
		if ( $db->updateData("{{driver}}",$params,'driver_id',$driver_id)){
			$this->code=1;
			$this->msg=self::t("Setting Saved");	
			
			$this->details = array(
			  'enabled_push'=>$params['enabled_push']
			);
					
		} else $this->msg=self::t("Something went wrong please try again later");
		$this->output();     
    }
    
    public function actionGetSettings()
    {
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		$this->output();
    		Yii::app()->end();
    	}     	
    	$driver_id=$token['driver_id']; 
    	
    	Driver::setCustomerTimezone( $token['customer_id'] );
    	
    	$lang=Driver::availableLanguages();
    	$lang='';
    	
    	$resp=array(
    	  'enabled_push'=>$token['enabled_push'],
    	  'language'=>$lang
    	);
    	$this->code=1;
    	$this->msg="OK";
    	$this->details=$resp;
    	$this->output();     
    }
    
    public function actionLanguageList()
    {
    	$final_list='';
    	$lang=getOptionA('language_list');
    	if(!empty($lang)){
    		$lang=json_decode($lang,true);
    	}
    	if(is_array($lang) && count($lang)>=1){
    		foreach ($lang as $lng) {
    			$final_list[$lng]=$lng;
    		}
    		$this->code=1; $this->msg="OK";
    	} else $this->msg=t("No language");
    	$this->details=$final_list;    	
		$this->output();
    }
    
    public function actionGetAppSettings()
    {    	
    	
    	$translation=Driver::getMobileTranslation();    	
    	$this->code=1;
    	$this->msg="OK";
    	$this->details=array(
    	  'notification_sound_url'=>Driver::moduleUrl()."/sound/food_song.mp3",  
    	  'app_default_lang'=>getOptionA('app_default_lang'),
    	  'app_force_lang'=>getOptionA('app_force_lang'),
    	  'map_provider'=>getOptionA('map_provider'),
    	  'mapbox_access_token'=>getOptionA('mapbox_access_token'),
    	  'translation'=>$translation
    	);
    	$this->output();
    }
    
    public function actionViewOrderDetails()
    {
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		$this->output();
    		Yii::app()->end();
    	}     	
    	$driver_id=$token['driver_id']; 
    	$order_id= $this->data['order_id'];
    	
    	Driver::setCustomerTimezone( $token['customer_id'] );
    	
		$_GET['backend']='true';
		if ( $data=Yii::app()->functions->getOrder2($order_id)){	
			//dump($data);					
			$json_details=!empty($data['json_details'])?json_decode($data['json_details'],true):false;
			if ( $json_details !=false){
			    Yii::app()->functions->displayOrderHTML(array(
			       'merchant_id'=>$data['merchant_id'],
			       'order_id'=>$order_id,
			       'delivery_type'=>$data['trans_type'],
			       'delivery_charge'=>$data['delivery_charge'],
			       'packaging'=>$data['packaging'],
			       'cart_tip_value'=>$data['cart_tip_value'],
				   'cart_tip_percentage'=>$data['cart_tip_percentage'],
				   'card_fee'=>$data['card_fee'],
				   'donot_apply_tax_delivery'=>$data['donot_apply_tax_delivery'],
				   'points_discount'=>isset($data['points_discount'])?$data['points_discount']:'' /*POINTS PROGRAM*/
			     ),$json_details,true);
			     $data2=Yii::app()->functions->details;
			     unset($data2['html']);			     
			     $this->code=1;
			     $this->msg="OK";
			     
			     $admin_decimal_separator=getOptionA('admin_decimal_separator');
		         $admin_decimal_place=getOptionA('admin_decimal_place');
		         $admin_currency_position=getOptionA('admin_currency_position');
		         $admin_thousand_separator=getOptionA('admin_thousand_separator');
			     
			     $data2['raw']['settings']=Driver::priceSettings();
			     $data2['raw']['order_info']=array(
			       'order_id'=>$data['order_id'],
			       'order_change'=>$data['order_change'],
			     );
			     
			     $this->details=$data2['raw'];			     
			     
			} else $this->msg = self::t("Record not found");
		} else $this->msg = self::t("Record not found");    	
    	$this->output();
    }
    
    public function actionGetNotifications()
    {
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		$this->output();
    		Yii::app()->end();
    	}     	
    	$driver_id=$token['driver_id'];    	
    	Driver::setCustomerTimezone( $token['customer_id'] );
    	
    	if ( $res=Driver::getDriverNotifications($driver_id)) {
    		 $data=array();
    		 foreach ($res as $val) {
    		 	$val['date_created']=Driver::prettyDate($val['date_created']);
    		 	//$val['date_created']=date("h:i:s",strtotime($val['date_created']));
    		 	$val['push_title']=Driver::t($val['push_title']);
    		 	$data[]=$val;
    		 }
    		 $this->code=1;
    		 $this->msg="OK";
    		 $this->details=$data;
    	} else $this->msg=self::t("No notifications");
    	$this->output();
    }
    
    public function actionUpdateDriverLocation()
    {    	
    	//Driver::createLogs('test','track');
    	    	
    	$json = @file_get_contents('php://input');        
        if(!empty($json)){                   
            $json = json_decode($json,true);
            if(!is_array($json) && count((array)$json)<=1){
                Yii::app()->end();
            }
            $this->data = $json[0];
        }
        
        $this->data['token'] = isset($this->data['token'])?$this->data['token']:'';
        
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		$this->output();
    		Yii::app()->end();
    	}     	
    	$driver_id=$token['driver_id'];    	
    	Driver::setCustomerTimezone( $token['customer_id'] );
    	
    	$params=array(
    	  'location_lat'=>$this->data['lat'],
    	  'location_lng'=>$this->data['lng'],
    	  'last_login'=>AdminFunctions::dateNow(),
	      'last_online'=>strtotime("now"),
	      'is_online'=>1,
	      'app_version'=>isset($this->data['app_version'])?$this->data['app_version']:''
    	);
    	
    	if ( $token['on_duty']==2){
    	    unset($params['last_online']);
    	    unset($params['is_online']);
    	}   
    	
    	$db=new DbExt;
    	if ( $db->updateData("{{driver}}",$params,'driver_id',$driver_id)){
    		
    		$this->code=1;
    		$this->msg=self::t("Location set");
    		
    		/*log driver location*/    		
    		$is_record=getOption($token['customer_id'],'agents_record_track_Location');
    		if ($is_record==1){
	    		$logs=array(
	    		  'customer_id'=>$token['customer_id'],
	    		  'driver_id'=>$driver_id,
	    		  'latitude'=>$this->data['lat'],
	    	      'longitude'=>$this->data['lng'],
	    	      
	    	      'altitude'=>isset($this->data['altitude'])?$this->data['altitude']:'',
	    	      'accuracy'=>isset($this->data['accuracy'])?$this->data['accuracy']:'',
	    	      'altitudeAccuracy'=>isset($this->data['altitudeAccuracy'])?$this->data['altitudeAccuracy']:'',
	    	      'heading'=>isset($this->data['heading'])?$this->data['heading']:'',
	    	      'speed'=>isset($this->data['speed'])?$this->data['speed']:'',
	    	      'track_type'=>isset($this->data['track_type'])?$this->data['track_type']:'',	    
	    	      	      
	    	      'date_created'=>AdminFunctions::dateNow(),
	    	      'ip_address'=>$_SERVER['REMOTE_ADDR'],	
	    	      'date_log'=>date("Y-m-d")
	    		);
	    		$db->insertData("{{driver_track_location}}",$logs);
    		}    		
    		    		    		
    	} else $this->msg="Failed";
    	$this->output();    	
    }
    
    public function actionClearNofications()
    {
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		$this->output();
    		Yii::app()->end();
    	}     	
    	$driver_id=$token['driver_id'];
    	$stmt="UPDATE 
    	{{driver_pushlog}}
    	SET
    	is_read='1'
    	WHERE
    	driver_id=".self::q($driver_id)."
    	AND
    	is_read='2'
    	";
    	$this->code=1;
    	$this->msg="OK";
    	$db=new DbExt;
    	$db->qry($stmt);
    	$this->output();    	
    }
    
    public function actionLogout()
    {
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		$this->output();
    		Yii::app()->end();
    	} 
    	
    	Driver::setCustomerTimezone( $token['customer_id'] );
    	
    	$driver_id=$token['driver_id'];
    	
    	$tracking_type=Driver::getTrackingOptions( $token['customer_id'] );
    	if ( $tracking_type==2){
    	    $last_online=strtotime("-35 minutes");
    	} else $last_online=strtotime("-20 minutes");
    	
    	$params=array(    	  
    	  'last_online'=>$last_online,
    	  'on_duty'=>2,
    	  'ip_address'=>$_SERVER['REMOTE_ADDR'],
    	  'is_online'=>2
    	);
    	
    	$db=new DbExt;
    	$db->updateData('{{driver}}',$params,'driver_id',$driver_id);
    	$this->code=1;
    	$this->msg="OK";
    	unset($db);
    	$this->output();
    }
    
    public function actionLoadNotes()
    {
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		$this->output();
    		Yii::app()->end();
    	} 
    	
    	$driver_id=$token['driver_id'];  
    	Driver::setCustomerTimezone( $token['customer_id'] );  	
    	    	
    	if ( $res=Driver::getTaskId($this->data['task_id']) ){    		
    		if ( $notes=Driver::getNotes($res['task_id'])){
    			$data=array();
    			foreach ($notes as $val) {
    				$val['status_raw']=$val['status'];
    				$val['status']=self::t($val['status']);
    				$val['date_created']=Driver::prettyDate($val['date_created']);
    				$data[]=$val;
    			}
    			$this->code=1;
    			$this->msg=$res['status'];
    			$this->details=$data;
    		} else $this->msg=self::t("no results");
    	} else $this->msg=self::t("Task not found");
    	
    	$this->output();
    }
    
    public function actionAddNotes()
    {
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		$this->output();
    		Yii::app()->end();
    	} 
    	
    	$driver_id=$token['driver_id'];    	
    	$driver_name=$token['first_name'] ." " .$token['last_name'];    
    	
    	Driver::setCustomerTimezone( $token['customer_id'] );	
    	
    	if ( $res=Driver::getTaskId($this->data['task_id']) ){    		
	    	if (isset($this->data['notes'])){
	    	 	if(!empty($this->data['notes'])){
	    	 		$db=new DbExt;
	    	 		$params=array(
	    	 		   'status'=>"notes",
	    	 		   'remarks'=>Driver::driverStatusPretty( $driver_name ,'notes'),
	    	 		   'task_id'=>$this->data['task_id'],
	    	 		   'driver_id'=>$driver_id,
	    	 		   'driver_location_lat'=>isset($token['location_lat'])?$token['location_lat']:'',
		               'driver_location_lng'=>isset($token['location_lng'])?$token['location_lng']:'',
		               'date_created'=>AdminFunctions::dateNow(),
		               'ip_address'=>$_SERVER['REMOTE_ADDR'],
		               'reason'=>"",
		               'notes'=>$this->data['notes']
	    	 		);
	    	 		if ( $db->insertData("{{task_history}}",$params)){
	    	 			$this->code=1; $this->msg="OK";
	    	 			$this->details=array(
	    	 			  'task_id'=>$this->data['task_id'],
	    	 			  'driver_id'=>$driver_id
	    	 			);
	    	 			
	    	 			$task_info=$res;
	    	 			$task_info['notes']=$this->data['notes'];
	    	 			
	    	 			if ( $task_info['trans_type']=="delivery"){ 
	    	 				Driver::sendCustomerNotification('DELIVERY_NOTES',$task_info);
	    	 			} else Driver::sendCustomerNotification('PICKUP_NOTES',$task_info);
	    	 			
	    	 		} else $this->msg=self::t("cannot saved notes");
	    	 		
	    	 		unset($db);
	    	 	} else $this->msg=self::t("Notes is required");
	    	} else $this->msg=self::t("Notes is required");
    	} else $this->msg=self::t("Task not found");
    	$this->output();
    }
    
    public function actionDeleteNotes()
    {
    	
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		$this->output();
    		Yii::app()->end();
    	} 
    	
    	$driver_id=$token['driver_id'];    	
    	Driver::setCustomerTimezone( $token['customer_id'] );
    	    	
    	if ( $res=Driver::getTaskId($this->data['task_id']) ){       		
    		Driver::deleteNotes($this->data['id']);
    		$this->msg="OK";
    		$this->code=1;
    		$this->details=array(
 			  'task_id'=>$this->data['task_id'],
 			  'driver_id'=>$driver_id
 			);
    	} else $this->msg=self::t("Task not found");
    	
    	$this->output();
    }
    
    public function actionUpdateNotes()
    {
    	
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		$this->output();
    		Yii::app()->end();
    	} 
    	
    	$driver_id=$token['driver_id']; 
    	Driver::setCustomerTimezone( $token['customer_id'] );
    	    	
    	if ( $res=Driver::getTaskId($this->data['task_id']) ){    		
    		if(isset($this->data['id'])){
    			$db=new DbExt;
    	 		$params=array(    	 		   
    	 		   'notes'=>$this->data['notes'],    	 		       	 		   
    	 		   'driver_location_lat'=>isset($token['location_lat'])?$token['location_lat']:'',
	               'driver_location_lng'=>isset($token['location_lng'])?$token['location_lng']:'',
	               'date_created'=>AdminFunctions::dateNow(),
	               'ip_address'=>$_SERVER['REMOTE_ADDR'],
	               'reason'=>""
    	 		);    	 		
    	 		if ( $db->updateData("{{task_history}}",$params,'id',$this->data['id'])){
    	 		 	$this->code=1;
    	 		 	$this->msg=self::t("Notes updated");
    	 		 	$this->details=array(
		 			  'task_id'=>$this->data['task_id'],
		 			  'driver_id'=>$driver_id
		 			);
		 			
		 			$task_info=$res;
    	 			$task_info['notes']=$this->data['notes'];
    	 				    	 			
    	 			if ( $task_info['trans_type']=="delivery"){ 
    	 				Driver::sendCustomerNotification('DELIVERY_UPDATE_NOTES',$task_info);
    	 			} else Driver::sendCustomerNotification('PICKUP_UPDATE_NOTES',$task_info);
		 			
    	 		} else $this->msg=self::t("cannot saved notes");
    		} else $this->msg=self::t("ID is required");
    	} else $this->msg=self::t("Task not found");
    	    	
    	$this->output();
    }
    
    public function actionUploadTaskPhoto()
    {
    	
    	$this->data=$_REQUEST;
    	$request=json_encode($_REQUEST);
    	
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		echo "$this->code|$this->msg|$this->details|".$request;
    		Yii::app()->end();
    	} 
    	
    	$driver_id=$token['driver_id'];  
    	$driver_name=$token['first_name'] ." " .$token['last_name'];  
    	
    	Driver::setCustomerTimezone( $token['customer_id'] );  	
    	  	
    	if ( $res=Driver::getTaskId($this->data['task_id']) ){    		
    	
    		 $task_id=$res['task_id'];
    		
    		 $path_to_upload=Driver::driverUploadPath();
    		
    		 if(isset($_FILES['file'])){
    		 	
    		 	header('Access-Control-Allow-Origin: *');
    		 	
    		 	$new_image_name = urldecode($_FILES["file"]["name"]).".jpg";	
		        $new_image_name=str_replace(array('?',':'),'',$new_image_name);
		        
		        if(@move_uploaded_file($_FILES["file"]["tmp_name"], "$path_to_upload/".$new_image_name)){		        
			        $params=array(
			           'status'=>"photo",
			           'remarks'=>Driver::driverStatusPretty($driver_name,"photo"),
			           'task_id'=>$task_id,
			           'driver_id'=>$driver_id,
			           'driver_location_lat'=>isset($token['location_lat'])?$token['location_lat']:'',
			           'driver_location_lng'=>isset($token['location_lng'])?$token['location_lng']:'',
			           'reason'=>'',
			           'date_created'=>AdminFunctions::dateNow(),
			           'ip_address'=>$_SERVER['REMOTE_ADDR'],	
			           'photo_name'=>$new_image_name
			        );
			        
			        $db=new DbExt;
			        if($db->insertData("{{task_history}}",$params)){
			           $this->code=1;
				       $this->msg=self::t("Upload successful");
	    		       $this->details=$task_id;
	    		       
	    		       $photo_link=websiteUrl()."/upload/photo/".$new_image_name;
	    		       
	    		       $task_info=$res;
    	 			   $task_info['photo_link']=$photo_link;
    	 				    	 			
    	 			   if ( $task_info['trans_type']=="delivery"){ 
    	 				   Driver::sendCustomerNotification('DELIVERY_PHOTO',$task_info);
    	 			   } else Driver::sendCustomerNotification('PICKUP_PHOTO',$task_info);	    		       
	    		       
			        } else $this->msg=self::t("failed cannot insert record");
		        } else $this->msg=self::t("Cannot upload photo");
		        		            		    
    		 } else $this->msg=self::t("Image is missing");
    		 
    	} else $this->msg=self::t("Task not found");	
    	
    	echo "$this->code|$this->msg|$this->details|".$request;
    }
    
    public function actionGetTaskPhoto()
    {
    	
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		$this->output();
    		Yii::app()->end();
    	} 
    	
    	$driver_id=$token['driver_id'];    	
    	Driver::setCustomerTimezone( $token['customer_id'] );
    	    	
    	if ( $res=Driver::getTaskId($this->data['task_id']) ){    		    		
    		if ( $photos=Driver::getTaskPhoto($this->data['task_id'])){
	    		$this->code=1;
	    		$this->msg=$res['status'];
	    		$this->details=$photos;
	    	} else $this->msg=self::t("No photo to show");    		
    		
    	} else $this->msg=self::t("Task not found");
    	    	
    	$this->output();
    }
    
    public function actionLoadSignature()
    {    
    
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		$this->output();
    		Yii::app()->end();
    	} 
    	
    	$driver_id=$token['driver_id'];    	
    	Driver::setCustomerTimezone( $token['customer_id'] );
    	    	
    	if ( $res=Driver::getTaskId($this->data['task_id']) ){    		    		
    		$task_id=$res['task_id'];
    		if ( $data=Driver::getLastSignature($task_id)){    			
    			$this->msg="OK";
    			$this->code=1;
    			if (!empty($data['customer_signature'])){
    				$data['customer_signature_url']=Driver::uploadURL()."/".$data['customer_signature'];
    				if (!file_exists(Driver::uploadPath()."/".$data['customer_signature'])){
    					$data['customer_signature_url']='';
    				}
    			}
    			
    			$this->details=array(
    			  'task_id'=>$task_id,
    			  'status'=>$res['status'],
    			  'data'=>$data
    			);
    		} else $this->msg=self::t("no signature found");
    	} else $this->msg=self::t("Task not found");
    	$this->output();
    }

    public function actionUploadProfile()
    {
    	$this->data=$_REQUEST;
    	
    	$request=json_encode($_REQUEST);
    	
    	if (!isset($this->data['token'])){
    		$this->data['token']='';
    	}
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("token not found");
    		echo "$this->code|$this->msg||".$request;
    		Yii::app()->end();
    	}     	    	
    	
    	$driver_id=$token['driver_id'];  
    	Driver::setCustomerTimezone( $token['customer_id'] );
    	
    	$path_to_upload=Driver::driverUploadPath();
    	if(!file_exists($path_to_upload)) {	
           if (!@mkdir($path_to_upload,0777)){           	               	
           	    $this->msg=Driver::t("Error has occured cannot create upload directory");
                $this->jsonResponse();
           }		    
	    }
	    
	    $profile_photo='';
	    	    	    
	    if(isset($_FILES['file'])){
	    	
	    	header('Access-Control-Allow-Origin: *');
	    	
		    $new_image_name = urldecode($_FILES["file"]["name"]).".jpg";	
		    $new_image_name = str_replace(array('?',':'),'',$new_image_name);
		        
		    @move_uploaded_file($_FILES["file"]["tmp_name"], "$path_to_upload/".$new_image_name);
		    
		    $db=new DbExt;
		    $params=array(
		     'profile_photo'=>$new_image_name,
		     'date_modified'=>AdminFunctions::dateNow()
		    );
		    if($db->updateData("{{driver}}",$params,'driver_id',$driver_id)){
			    $this->code=1;
			    $this->msg=t("Upload successful");
			    $this->details=$new_image_name;
			    $profile_photo=websiteUrl()."/upload/photo/".$new_image_name;
		    } else $this->msg=self::t("Error cannot update");
		    
	    } else $this->msg=self::t("Image is missing");
	    
    	echo "$this->code|$this->msg|$profile_photo|".$request;
    }
    
    public function actionDeletePhoto()
    {
    	
    	if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		$this->output();
    		Yii::app()->end();
    	} 
    	
    	$driver_id=$token['driver_id'];    	
    	
    	if (isset($this->data['id'])){
    		if ( $res=Driver::getTaskId($this->data['task_id']) ){  
    			if ( $data=Driver::getTasHistoryByID($this->data['id'])){    				
    				$file=Driver::driverUploadPath()."/".$data['photo_name'];
    				if (file_exists($file)){
    					@unlink($file);
    				}
		    		Driver::deleteSignature($this->data['id']);
		    		$this->code=1; $this->msg="OK";
		    		$this->details=$this->data['task_id'];
    			} else $this->msg=self::t("Task not found");
    		} else $this->msg=self::t("Task not found");
    	} else $this->msg=self::t("missing parameters");
    	    	
    	$this->output();
    }
    
    public function actiongetTaskCompleted()
    {
    	$this->actionGetTaskByDate();
    }
    
    public function actionreRegisterDevice()
    {
    	$new_device_id = isset($this->data['new_device_id'])?$this->data['new_device_id']:'';
		if(empty($new_device_id)){
			$this->msg = $this->t("New device id is empty");
			$this->output();
		}
		
		if ( !$token=Driver::getDriverByToken($this->data['token'])) {
    		$this->msg=self::t("Token not valid");
    		$this->output();
    		Yii::app()->end();
    	} 
    	
    	$driver_id=$token['driver_id']; 
    	
    	$db=new DbExt();
    	
    	$params = array(
    	  'device_id'=>$new_device_id,
    	  'device_platform'=>isset($this->data['device_platform'])?$this->data['device_platform']:'',
    	  'app_version'=>isset($this->data['app_version'])?$this->data['app_version']:'',
    	);
		if ($db->updateData("{{driver}}",$params,'driver_id',$driver_id)){
			$this->code = 1;
			$this->msg = "OK";
			$this->details = $new_device_id;
		} else $this->msg = "Failed cannot update";
		$this->output();
    } 

    
} /*end class*/