<?php
if (!isset($_SESSION)) { session_start(); }

class AppController extends CController
{
	public $layout='layout';	
	public $body_class='';
	
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
		 
		 /*dump(Yii::app()->timeZone);
		 dump(date('c'));
		 die();*/
		 		 
		 if(isset($_GET['lang'])){
		 	Yii::app()->language=$_GET['lang'];
		 }
	}
	
	public function beforeAction($action)
	{		
		/*if (Yii::app()->controller->module->require_login){
			if(! DriverModule::islogin() ){
			   $this->redirect(Yii::app()->createUrl('/admin/noaccess'));
			   Yii::app()->end();		
			}
		}*/
		$action_name= $action->id ;
		$accept_controller=array('login','ajax','resetpassword');
		if(!Driver::islogin()){			
			if(!in_array($action_name,$accept_controller)){
				$this->redirect(Yii::app()->createUrl('/app/login'));
			}
		}
		
		
		/*check user status*/
		$status=Driver::getUserStatus();
		if($status=="expired"){
			if($action_name!="profile"){
				if($action_name!="logout"){
				$this->redirect(Yii::app()->createUrl('/app/profile',array(
				  'tabs'=>2
				)));
				Yii::app()->end();
				}
			}
		}
		
		ScriptManager::scripts();
		
		$cs = Yii::app()->getClientScript();
		$jslang=json_encode(Driver::jsLang());
		$cs->registerScript(
		  'jslang',
		 "var jslang=$jslang;",
		  CClientScript::POS_HEAD
		);
				
		$js_lang_validator=Yii::app()->functions->jsLanguageValidator();
		$js_lang=Yii::app()->functions->jsLanguageAdmin();
		$cs->registerScript(
		  'jsLanguageValidator',
		  'var jsLanguageValidator = '.json_encode($js_lang_validator).'
		  ',
		  CClientScript::POS_HEAD
		);				
		$cs->registerScript(
		  'js_lang',
		  'var js_lang = '.json_encode($js_lang).';
		  ',
		  CClientScript::POS_HEAD
		);
		
		$cs->registerScript(
		  'account_status',
		 "var account_status='$status';",
		  CClientScript::POS_HEAD
		);
		
		$language=Yii::app()->language;
		$cs->registerScript(
		  'language',
		 "var language='$language';",
		  CClientScript::POS_HEAD
		);
		
		$calendar_language=getOption( Driver::getUserId(), 'calendar_language');
		$cs->registerScript(
		  'calendar_language',
		 "var calendar_language='$calendar_language';",
		  CClientScript::POS_HEAD
		);
		
		$map_hide_pickup=getOption( Driver::getUserId(), 'map_hide_pickup');
		$cs->registerScript(
		  'map_hide_pickup',
		 "var map_hide_pickup='$map_hide_pickup';",
		  CClientScript::POS_HEAD
		);
		
		$map_hide_pickup=getOption( Driver::getUserId(), 'map_hide_delivery');
		$cs->registerScript(
		  'map_hide_delivery',
		 "var map_hide_delivery='$map_hide_pickup';",
		  CClientScript::POS_HEAD
		);
		
		$map_hide_pickup=getOption( Driver::getUserId(), 'map_hide_success_task');
		$cs->registerScript(
		  'map_hide_success_task',
		 "var map_hide_success_task='$map_hide_pickup';",
		  CClientScript::POS_HEAD
		);
		
		$auto_geo_address=getOption( Driver::getUserId(), 'driver_auto_geo_address');
		$cs->registerScript(
		  'auto_geo_address',
		 "var auto_geo_address='$auto_geo_address';",
		  CClientScript::POS_HEAD
		);
		
		$driver_activity_tracking=getOption( Driver::getUserId(), 'driver_activity_tracking');		
		$cs->registerScript(
		  'disabled_activity_tracking',
		 "var disabled_activity_tracking='$driver_activity_tracking';",
		  CClientScript::POS_HEAD
		);
		
		$driver_activity_tracking_interval=getOption( Driver::getUserId(), 'driver_activity_tracking_interval');		
		if($driver_activity_tracking_interval<=0){
			$driver_activity_tracking_interval=15;
		}
		$driver_activity_tracking_interval = $driver_activity_tracking_interval*1000;
		$cs->registerScript(
		  'activity_tracking_interval',
		 "var activity_tracking_interval='$driver_activity_tracking_interval';",
		  CClientScript::POS_HEAD
		);
						
		if($action_name=="index" || $action_name=="contacts"){
			$map_provider = getOptionA('map_provider');
			if($map_provider=="mapbox"){
				$site_url=Yii::app()->baseUrl.'/';
				Yii::app()->clientScript->registerCssFile($site_url."/assets/leaflet/plugin/routing/leaflet-routing-machine.css");
				Yii::app()->clientScript->registerScriptFile($site_url."/assets/leaflet/plugin/routing/leaflet-routing-machine.min.js"
				,CClientScript::POS_END); 
			}
		}
				
		return true;				
	}
	
	public function actionLogin()
	{
		
		$encryption_type=Yii::app()->params->encryption_type;
		if(empty($encryption_type)){
			$encryption_type='yii';
		}
		
		if(Driver::islogin()){						
		    $this->redirect(Yii::app()->createUrl('/app'));			
		    Yii::app()->end();
		}
		
		$this->body_class='login-body';
		
		/*unset(Yii::app()->request->cookies['kt_username']);
		unset(Yii::app()->request->cookies['kt_password']);*/
		
		$kt_username = isset(Yii::app()->request->cookies['kt_username']) ? Yii::app()->request->cookies['kt_username']->value : '';
		$kt_password = isset(Yii::app()->request->cookies['kt_password']) ? Yii::app()->request->cookies['kt_password']->value : '';
		
		if ($encryption_type=="yii"){
			if(!empty($kt_password) && !empty($kt_username)){
			   $kt_password=Yii::app()->securityManager->decrypt( $kt_password );		
			}
		} else $kt_password='';
		
		$this->render('login',array(
		  'email_address'=>$kt_username,
		  'password'=>$kt_password
		));
	}
	
	public function actionLogout()
	{
		unset($_SESSION['kartero']);
		$this->redirect(Yii::app()->createUrl('/app/login'));
	}
	
	public function actionIndex(){		
		$this->body_class="dashboard";		
		$this->render('dashboard');
	}	
	
	public function actionDashboard()
	{
		$this->body_class="dashboard";		
		$this->render('dashboard');
	}

	public function actionAgents()
	{
		$this->body_class="page-single";	
		$this->render('agents-list');
	}
	
	public function actionTasks()
	{
		$this->body_class="page-single";	
		$this->render('task-list');
	}
	
	public function actionSettings()
	{		
				
        $country_list=require_once('CountryCode.php');
        $this->body_class='settings-page';
                     
        if ( Driver::getUserType()=="merchant"){
        	$this->render('error',array(
        	  'msg'=>Driver::t("Sorry but you don't have access to this page")
        	));
        } else {
        	
        	$language_list=getOptionA('language_list');
			if(!empty($language_list)){
			   $language_list=json_decode($language_list,true);	
			}   
			$action_name=Yii::app()->controller->action->id;
        	
			if(is_array($language_list) && count($language_list)>=1){
			   array_unshift($language_list,t("Please select"));
			}
			
			$this->render('settings',array(			  
			  'country_list'=>$country_list,
			  'language_list'=>$language_list,
			  'action_name'=>$action_name
			));
        }
	}
	
	public function actionTeams()
	{
		$this->body_class="page-single";	
		$this->render('teams');
	}
	
	public function actionlanguage()
	{
		$lang=Driver::availableLanguages();		
		$dictionary=require_once('MobileTranslation.php');		
		
		$mobile_dictionary=getOptionA('driver_mobile_dictionary');
        if (!empty($mobile_dictionary)){
	       $mobile_dictionary=json_decode($mobile_dictionary,true);
        } else $mobile_dictionary=false;
		
		$this->render('language',array(
		  'lang'=>$lang,
		  'dictionary'=>$dictionary,
		  'mobile_dictionary'=>$mobile_dictionary
		));
	}
	
	public function actionNotifications()
	{
		$this->body_class="page-single";
		$this->render('notifications');
	}
	
	public function actionPushlogs()
	{
		$this->body_class="page-single";
		$this->render('push-logs',array(
		  'broadcast_id'=>isset($_GET['broadcast_id'])?$_GET['broadcast_id']:""
		));
	}
		
	public function actionReports()
	{
		$this->body_class="page-single";
		$cs = Yii::app()->getClientScript(); 
		
		Yii::app()->clientScript->registerScriptFile(
		Yii::app()->baseUrl . '/assets/amcharts/amcharts.js'
        ,CClientScript::POS_END);		
        
        Yii::app()->clientScript->registerScriptFile(
        Yii::app()->baseUrl . '/assets/amcharts/serial.js',
        CClientScript::POS_END);		
        
        Yii::app()->clientScript->registerScriptFile(
        Yii::app()->baseUrl . '/assets/amcharts/themes/light.js',
        CClientScript::POS_END);		
		
        $team_list=Driver::teamList( Driver::getUserId());
		if($team_list){
			 $team_list=Driver::toList($team_list,'team_id','team_name',
			   Driver::t("All Team")
			 );
		}
		
		$all_driver=Driver::getAllDriver(
           Driver::getUserId()
        );   

        $start= date('Y-m-d', strtotime("-7 day") );
	    $end=date("Y-m-d", strtotime("+1 day")); 
        
		$this->render('reports',array(
		  'team_list'=>$team_list,
		  'all_driver'=>$all_driver,
		  'start_date'=>$start,
		  'end_date'=>$end
		));
	}
	
	public function actionAssignment()
	{
		$this->body_class="page-single";
		$this->render('assignment');
	}
	
	public function actionResetPassword()
	{
		$this->body_class='login-body';		
		$this->render('resetpassword',array(
		 'hash'=>isset($_GET['hash'])?$_GET['hash']:''
		));
	}
	
	public function actionprofile()
	{
		
		FrontFunctions::ClearPromoCode();
				
		$this->body_class="page-single";
		if($data=AdminFunctions::getCustomerByID( Driver::getUserId())){				
			$plans=Driver::getPlansByID( $data['plan_id']);			
			$this->render('profile',array(
			  'data'=>$data,
			  'plans'=>$plans,
			  'tabs'=>isset($_GET['tabs'])?$_GET['tabs']:1,
			  'history'=>AdminFunctions::getCustomerPaymentLogs(Driver::getUserId()),
			  'sms_balance'=>Driver::getSMSBalance(Driver::getUserId())
			));
		} else {
			$this->render('error',array(
			  'msg'=>t("Profile not available")
			));
		}
	}
	
    public function actionsetlang()
	{
		if(!empty($_GET['action'])){
			$url=Yii::app()->createUrl("app/".$_GET['action'],array(
			  'lang'=>$_GET['lang']
			));			
		} else {
			$url=Yii::app()->createUrl("app/dashboard",array(
			  'lang'=>$_GET['lang']
			));
		}				
		$this->redirect($url);
	}	
	
	public function actionContacts()
	{
		$this->body_class="page-single";	
		$this->render('contact-list');
	}
	
	public function actionServices()
	{
		$customer_id=Driver::getUserId();
		
		$this->render('services',array(
		  'services'=>AdminFunctions::servicesFullList(0,'published'),
		  'data'=>AdminFunctions::getCustomerByID( $customer_id )
		));
	}
	
	public function actionuploadmasstask()
	{
		$msg=''; $error=0; $line_processing=''; $params='';
		$params_data='';
		
		if (isset($_POST) && $_SERVER['REQUEST_METHOD']=='POST'){		
			$filename=$_FILES['file']['name'];	
			if (preg_match("/.csv/i",$filename)) {
				ini_set('auto_detect_line_endings',TRUE);
				$handle = fopen($_FILES['file']['tmp_name'], "r");
				$x=1;
				while (($data = @fgetcsv($handle)) !== FALSE){					
					$line_processing[]=t("Processing line")." ($x)";
										
					if ( count($data) >= 7){						
					    
						if ( empty($data[0])){
							$line_processing[]=$line_processing[]=t("Error on line")." ($x)" ." ".t("Transaction type is empty");
							$error++;
							//continue;
						}
						if ( empty($data[4])){
							$line_processing[]=$line_processing[]=t("Error on line")." ($x)" ." ".t("Customer name is empty");
							$error++;
							//continue;
						}
						if ( empty($data[5])){
							$line_processing[]=$line_processing[]=t("Error on line")." ($x)". " ".t("Delivery date is empty");
							$error++;
							//continue;
						}
						if ( empty($data[6])){							
							$line_processing[]=$line_processing[]=t("Error on line")." ($x)"." ".t("Address is empty");
							$error++;
							//continue;
						}
						
					    $params=array(
					      'customer_id'=>Driver::getUserId(),
					      'trans_type'=>$data[0],
					      'task_description'=>!empty($data[1])?$data[1]:'',
					      'contact_number'=>!empty($data[2])?$data[2]:'',
					      'email_address'=>!empty($data[3])?$data[3]:'',
					      'customer_name'=>!empty($data[4])?$data[4]:'',
					      'delivery_date'=>$data[5],
					      'delivery_address'=>!empty($data[6])?$data[6]:'',
					      'ip_address'=>$_SERVER['REMOTE_ADDR'],
					      'date_created'=>AdminFunctions::dateNow(),
					      'task_token'=>Driver::generateTaskToken()
					    );
					    if (isset($data[7])){
					    	$params['task_lat']=$data[7];
					    	$params['task_lng']=$data[8];
					    } else {
					    	if(!empty($data[6])){
					    	  if($lat_lng=Driver::addressToLatLong($data[6])){
					    	  	 $params['task_lat']=$lat_lng['lat'];
					    	     $params['task_lng']=$lat_lng['long'];
					    	  }
					    	}
					    }
					    					
					} else {
						$error++;
						$line_processing[]=t("Error on line")." ($x)";
					}
					
					$params_data[]=array(
					 'data'=>$params,
					 'line'=>$line_processing
					);
					
					$line_processing='';
					
					$x++;
				}
				ini_set('auto_detect_line_endings',FALSE);
			} else {			
				$error=1;	
				$msg=t("Please upload a valid CSV file");
			}			
		}
		$this->render('uploadmasstask',array(
		  'msg'=>$msg,
		  'error'=>$error,		 
		  'data'=>$params_data
		));
	}
	
	public function actionSmslogs()
	{
		$this->body_class="page-single";
		$this->render('sms-logs');
	}	
	
	public function actionEmailLogs()
	{
		$this->body_class="page-single";
		$this->render('email-logs');
	}
	
	public function actionBulkPush()
	{
		
		if ( Driver::customerCanBroadcast(Driver::getUserId())){
			if($team_list = Driver::teamList( Driver::getUserId() ) ){
	           $team_list=Driver::toList($team_list,'team_id','team_name',
	           Driver::t("Please select a team from a list") );
	        }
			$this->body_class="page-single";
			$this->render('bulk-push',array(
			  'team_list'=>$team_list
			));
		} else $this->render('error',array(
		  'msg'=>t("Your account does not have access to Push Broadcast"),
		  'upgrade_plan'=>true
		));
	}
	
	public function actionBulkLogs()
	{
		$this->body_class="page-single";
		$this->render('bulk-push-logs');
	}
	
	public function actionTrackBack()
	{
		$this->body_class="page-single";
		$this->render('track-back',array(
		  'driver_list'=>Driver::driverDropDownList( Driver::getUserId() ),
		  //'track_list'=>Driver::backTrackList( Driver::getUserId() )
		));
	}
	
	public function actionSmsPurchase()
	{
		$this->render('payment-options',array(
		  'transaction_type'=>'sms'
		));
	}
	
	public function actionexport_agents()
	{
		$data = array();
		$stmt=isset($_SESSION['kartero_stmt_agents'])?$_SESSION['kartero_stmt_agents']:'';
		if(!empty($stmt)){
			$pos = strpos($stmt,"LIMIT");
			$stmt = substr($stmt,0,$pos);			
			$DbExt=new DbExt; 
		    $DbExt->qry("SET SQL_BIG_SELECTS=1");
		    if ($res = $DbExt->rst($stmt)){
		    	foreach ($res as $val) {		    			    	
		    		$data[]=array(
		    		   $val['driver_id'],
				      $val['username'],
				      $val['first_name'],
				      $val['email'],
				      $val['phone'],
				      $val['team_name'],
				      $val['device_platform'],
				      $val['device_id'],
				      driver::t($val['status'])
		    		);		    		
		    	}
		    	
		    	$header=array(
				    driver::t("ID"),
				    driver::t("User Name"),
				    driver::t("Name"),
				    driver::t("Email"),	
				    driver::t("Phone"),
				    driver::t("Team"),
				    driver::t("Device"),
				    driver::t("Device ID"),
				    driver::t("Status"),				    
			   );
		    	
			   $filename = 'agents-'. date('c') .'.csv';    	    
		       $excel  = new ExcelFormat($filename);
		       $excel->addHeaders($header);
               $excel->setData($data);	  
               $excel->prepareExcel();
			   
		    }
		}
	}
	
	public function actionexport_task()
	{
		$data = array();
		$stmt=isset($_SESSION['kartero_stmt_taskList'])?$_SESSION['kartero_stmt_taskList']:'';
		if(!empty($stmt)){
			$pos = strpos($stmt,"LIMIT");
			$stmt = substr($stmt,0,$pos);
			$DbExt=new DbExt; 
		    $DbExt->qry("SET SQL_BIG_SELECTS=1");
		    if ($res = $DbExt->rst($stmt)){
		    	foreach ($res as $val) {
		    		$date_created=Yii::app()->functions->prettyDate($val['delivery_date'],true); 		    		
		    		$data[]=array(
		    		  $val['task_id'],		    		  
		    		  Driver::t($val['trans_type']),
		    		  $val['task_description'],
				      $val['driver_name'],
				      $val['customer_name'],
				      $val['delivery_address'],
				      $date_created,
				      Driver::t($val['status'])
		    		);		    		
		    	}
		    	
		    	$header=array(
				    driver::t("Task ID"),				    
				    driver::t("Task Type"),
				    driver::t("Description"),	    			    
				    driver::t("Driver Name"),
				    driver::t("Name"),
				    driver::t("Address"),
				    driver::t("Deliver Date"),
				    driver::t("Status"),
			   );
		    	
			   $filename = 'task-'. date('c') .'.csv';    	    
		       $excel  = new ExcelFormat($filename);
		       $excel->addHeaders($header);
               $excel->setData($data);	  
               $excel->prepareExcel();	                    	
			   
		    }
		}
	}

	public function actionexport_contact()
	{
		$data = array();
		$stmt=isset($_SESSION['kartero_stmt_contact'])?$_SESSION['kartero_stmt_contact']:'';
		if(!empty($stmt)){
			$pos = strpos($stmt,"LIMIT");
			$stmt = substr($stmt,0,$pos);
			$DbExt=new DbExt; 
		    $DbExt->qry("SET SQL_BIG_SELECTS=1");
		    if ($res = $DbExt->rst($stmt)){		    
		    	foreach ($res as $val) {
		    		
		    		$data[]=array(
		    		  $val['contact_id'],		    		  
		    		  $val['fullname'],
		    		  $val['email'],
		    		  $val['phone'],
		    		  $val['address'],
		    		  Driver::t($val['status'])
		    		);		    		
		    	}
		    	
		    	$header=array(
				    driver::t("ID"),				    
				    driver::t("Name"),
				    driver::t("Email"),	    			    
				    driver::t("Phone"),
				    driver::t("Address"),
				    driver::t("Status"),				    
			   );
		    	
			   $filename = 'contact-'. date('c') .'.csv';    	    
		       $excel  = new ExcelFormat($filename);
		       $excel->addHeaders($header);
               $excel->setData($data);	  
               $excel->prepareExcel();	                    	
			   
		    }
		}
	}
		
}/* end class*/