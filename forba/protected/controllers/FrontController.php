<?php
if (!isset($_SESSION)) { session_start(); }

class FrontController extends CController
{
	
	public $layout='front_layout';	
	public $body_class='';
	public $action_name='';
	
	public function init()
	{			
		if (!AdminFunctions::checkIfTableExist('admin')){
			$this->redirect(Yii::app()->createUrl('/install'));
			Yii::app()->end();
		}
				
		 // set website timezone
		 $website_timezone=Yii::app()->functions->getOptionAdmin("website_timezone" );		 
		 if (!empty($website_timezone)){		 	
		 	Yii::app()->timeZone=$website_timezone;
		 }		 				 
		 
		 if(isset($_GET['lang'])){
		 	Yii::app()->language=$_GET['lang'];
		 }
	}	
		
	public function beforeAction($action)
	{
		$action_name= $action->id ;		
		$this->body_class="page-$action_name";
		
		ScriptManageFront::scripts();
		
		$cs = Yii::app()->getClientScript();
		$jslang=json_encode(AdminFunctions::jsLang());
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
		
		$language=Yii::app()->language;
		$cs->registerScript(
		  'language',
		 "var language='$language';",
		  CClientScript::POS_HEAD
		);
		
		return true;
	}
		
	public function actionIndex()
	{				
				
		$title=getOptionA('home_seo_title');
		$meta=getOptionA('home_seo_meta');
		if(!empty($title)){
			$this->pageTitle=$title;
		}
		AdminFunctions::setSEO($title,$meta);
		
		$this->render('index',array(
		  'pricing'=>FrontFunctions::getPlans(),
		  'services'=>AdminFunctions::servicesFullList(0,'published')
		));
	}
	
	public function actionPricing()
	{
		$title=getOptionA('price_seo_title');
		$meta=getOptionA('price_seo_meta');
		if(!empty($title)){
			$this->pageTitle=$title;
		}
		AdminFunctions::setSEO($title,$meta);
		
		$exlude_free=isset($_GET['hash'])?true:false;		
		$this->render('pricing',array(
		  'data'=>FrontFunctions::getPlans($exlude_free),
		  'email'=>isset($_GET['email'])?$_GET['email']:'',
		  'hash'=>isset($_GET['hash'])?$_GET['hash']:''
		));
	}
	
	private function includeMaterial()
	{
		$cs = Yii::app()->getClientScript();
		$baseUrl = Yii::app()->baseUrl.""; 
		Yii::app()->clientScript->registerScriptFile(
        "//cdnjs.cloudflare.com/ajax/libs/materialize/0.97.6/js/materialize.min.js",
		CClientScript::POS_END
		);			
		$cs->registerCssFile("//cdnjs.cloudflare.com/ajax/libs/materialize/0.97.6/css/materialize.min.css");
        $cs->registerCssFile("//fonts.googleapis.com/icon?family=Material+Icons");		 
	}
	
	public function actionSignup()
	{
		FrontFunctions::ClearPromoCode();
		if ( isset($_GET['plan_id'])){
			if(is_numeric($_GET['plan_id'])){
				
				$this->body_class.=" page-material";
				$this->includeMaterial();
				
				$this->render('signup',array(
				  'plan_id'=>$_GET['plan_id'],
				  'email_address'=>isset($_GET['email'])?$_GET['email']:''
				));
			} else  $this->redirect(Yii::app()->createUrl('/front/pricing'));
		} else $this->redirect(Yii::app()->createUrl('/front/pricing'));
	}
	
	public function actionVerification()
	{		
		if($res=FrontFunctions::getCustomerByToken($_GET['hash'])){		   
			
		   $this->body_class.=" page-material";
		   $this->includeMaterial();
		   
		   $this->render('verification',array(
		     'data'=>$res,
		     'verification_type'=>isset($_GET['type'])?$_GET['type']:''
		   ));
		} else $this->render('error',array(
		  'msg'=>t("token is invalid")
		));		
	}
	
	public function actionSignupTy()
	{
		FrontFunctions::ClearPromoCode();
		
		if($res=FrontFunctions::getCustomerByToken($_GET['hash'])){		  
		   if(isset($_GET['needs_approval'])){
		   	  if($_GET['needs_approval']==1){
		   	  	 $client_id=$res['customer_id'];
		   	  	 $db=new DbExt;
		   	  	 $db->updateData("{{customer}}",array(
		   	  	   'needs_approval'=>1,
		   	  	   'date_modified'=>AdminFunctions::dateNow()
		   	  	 ),'customer_id',$client_id);
		   	  }
		   }
		   $this->render('signupty',array(
		     'needs_approval'=>isset($_GET['needs_approval'])?$_GET['needs_approval']:'',
		     'renew'=>isset($_GET['renew'])?$_GET['renew']:''
		   ));
		} else $this->render('error',array(
		  'msg'=>t("token is invalid")
		));		
	}
	
	public function actionPayment()
	{
		if($res=FrontFunctions::getCustomerByToken($_GET['hash'])){
			
		   $enabled_promo_codes=getOptionA("enabled_promo_codes");
			
		   /*update plan_renew_id */
		   if(isset($_GET['plan_id'])){
		   	  if(is_numeric($_GET['plan_id'])){
			   	  $db=new DbExt;
			   	  $db->updateData("{{customer}}",array('renew_plan_id'=>$_GET['plan_id']),
			   	  'customer_id',$res['customer_id']);		   	  
			   	  $res['plan_id']=$_GET['plan_id'];
			   	  $enabled_promo_codes=2;
		   	  }
		   }
			
		   $plan_id=$res['plan_id'];
		   $plan_details=FrontFunctions::getPlansByID($plan_id);
		   $this->body_class.=" page-material";
		   $this->includeMaterial();
		   		   
		   $this->render('payment-details',array(
		     'data'=>$res,
		     'plan_details'=>$plan_details,
		     //'payment_options'=>AdminFunctions::paymentGatewayList()
		     'payment_options'=>AdminFunctions::getEnabledPaymentList(),
		     'enabled_promo_codes'=>$enabled_promo_codes,
		     'display_promo_codes'=>getOptionA("display_promo_codes"),
		     'apply_promo'=>isset($_SESSION['promo_code'])?$_SESSION['promo_code']:''
		   ));
		} else $this->render('error',array(
		  'msg'=>t("token is invalid")
		));		
	}
	
	public function actionpaymentPyp()
	{
		
		if(!isset($_GET['hash'])){
			 $this->render('error',array(
		      'msg'=>t("token is invalid")
		    ));		
			return ;
		}
		
		if($res=FrontFunctions::getCustomerByToken($_GET['hash'])){									
			
			/*check if transaction is renew*/
			if($res['renew_plan_id']>0){
			   $res['plan_id']=$res['renew_plan_id'];
			}
			
			if ($plan_details=FrontFunctions::getPlansByID($res['plan_id'])){
				
				$price=$plan_details['price'];
				if($plan_details['promo_price']>0.0001){
					$price=$plan_details['promo_price'];
				}
				
				$customer_token=$res['token'];
				$customer_id=$res['customer_id'];
				
				/*$db=new DbExt();
				$db->updateData("{{customer}}",array(
				  'plan_price'=>$price
				),'customer_id',$customer_id);*/
								
				if ( $con=FrontFunctions::getPaypalConnection()){
					
					if($currency=FrontFunctions::getCurrenyCode()){
						
																		
					    $params['CANCELURL']="http://".$_SERVER['HTTP_HOST'].Yii::app()->request->baseUrl."/front/payment/?hash=".urlencode($customer_token)."&lang=".Yii::app()->language;
					    $params['RETURNURL']="http://".$_SERVER['HTTP_HOST'].Yii::app()->request->baseUrl."/front/payment-pyp-confirm/?hash=".urlencode($customer_token)."&lang=".Yii::app()->language;
					    
				        $params['NOSHIPPING']='1';
			            $params['LANDINGPAGE']='Billing';
			            $params['SOLUTIONTYPE']='Sole';
			            $params['CURRENCYCODE']=$currency['currency_code'];
			            
			            $x=0;
			            
			            /*Promo code*/
			            if ($promo=FrontFunctions::getPromoCodeDetails()){				            	
			            	$price=$price-$promo['discount_amount'];
			            }			            
			            
			            $params['L_NAME'.$x]=$plan_details['plan_name'];
			            $params['L_NUMBER'.$x]=$plan_details['plan_name_description'];
			            $params['L_DESC'.$x]='';
			            $params['L_AMT'.$x]=AdminFunctions::normalPrettyPrice($price);
			            $params['L_QTY'.$x]=1;			            			            			            
			            $params['AMT']=AdminFunctions::normalPrettyPrice($price);
			            
			            //dump($params); die();
			            			            
			            $paypal=new Paypal($con);
			            $paypal->params=$params;
			            $paypal->debug=false;
			            if ($resp=$paypal->setExpressCheckout()){ 
			            	header('Location: '.$resp['url']);
			            	Yii::app()->end();
			            }  else  $this->render('error',array(
		                           'msg'=>$paypal->getError()
		                        ));					             
						
					} else $this->render('error',array(
		                'msg'=>t("Currency code not yet set")
		            ));		
					
				} else $this->render('error',array(
		            'msg'=>t("Paypal credentials not yet set")
		        ));		
				
			} else $this->render('error',array(
		        'msg'=>t("Total to pay is not valid")
		    ));		
		} else $this->render('error',array(
		  'msg'=>t("token is invalid")
		));		
	}
	
	public function actionpaymentPypConfirm()
	{
		$error='';
		if ( $con=FrontFunctions::getPaypalConnection()){
			if($res=FrontFunctions::getCustomerByToken($_GET['hash'])){									
				
			   /*check if transaction is renew*/
			   if($res['renew_plan_id']>0){
			      $res['plan_id']=$res['renew_plan_id'];
			   }
				
			   $plan_details=FrontFunctions::getPlansByID($res['plan_id']);
			   			  
			   $paypal=new Paypal($con);
			   if ($res_paypal=$paypal->getExpressDetail()){			   	   
			   } else $error=$paypal->getError();
			} else $error=t("Plan details not found");
		} else $error=t("Paypal credentials invalid");
		
		if(empty($error)){
			
			$this->body_class.=" page-material";
		    $this->includeMaterial();
			
			$this->render('pyp-confirm',array(
			  'plan_details'=>$plan_details,
			  'res_paypal'=>$res_paypal,
			  'hash'=>isset($_GET['hash'])?$_GET['hash']:'',
			  'enabled_promo_codes'=>getOptionA("enabled_promo_codes"),		     
		      'apply_promo'=>isset($_SESSION['promo_code'])?$_SESSION['promo_code']:''
			));
		} else $this->render('error',array(
		  'msg'=>$error
		));	
	}
	
	public function actionpaymentStp()
	{
		$error=''; $publish_key='';
		
		$stripe_enabled=trim(getOptionA('stripe_enabled'));
		if ($stripe_enabled==""){
			$error=t("Stripe is disabled");
		}
		
		$stripe_mode=trim(getOptionA('stripe_mode'));
		if ($stripe_mode=="sandbox"){
			$publish_key=trim(getOptionA('stripe_sandbox_publish_key'));
		} else if ($stripe_mode=="live") {
			$publish_key=trim(getOptionA('stripe_live_publish_key'));
		} else $error=t("Stripe mode is not defined");
							
		if($res=FrontFunctions::getCustomerByToken($_GET['hash'])){	
			
			/*check if transaction is renew*/
		    if($res['renew_plan_id']>0){
		       $res['plan_id']=$res['renew_plan_id'];
		    }		    		    
			
			$plan_details=FrontFunctions::getPlansByID($res['plan_id']);						
			$price=$res['plan_price'];
			
			/*check if transaction is renew*/
			if($res['renew_plan_id']>0){
				$price=$plan_details['price'];
				if($plan_details['promo_price']>0.0001){
					$price=$plan_details['promo_price'];
				}
			}
			
			if ($promo=FrontFunctions::getPromoCodeDetails()){				
				$price=$price-$promo['discount_amount'];
			}
			
			$this->body_class.=" page-material";
		    $this->includeMaterial();
		    		   
		    Yii::app()->clientScript->registerScriptFile(
              "https://js.stripe.com/v2/",
		      CClientScript::POS_END
		    );			
		    			
		} else $error = t("Plan details not found");
		
		if(empty($error)){
			$this->render('stripe-init',array(
			  'plan_details'=>$plan_details,
			  'publish_key'=>$publish_key,
			  'hash'=>isset($_GET['hash'])?$_GET['hash']:'',
			  'price'=>$price
			)); 
		} else $this->render('error',array(
		      'msg'=>$error
		  ));		
			
	}
	
	/*public function missingAction($action_name)
	{
		dump($action_name);
	}*/
	
	public function actionPage()
	{				
		$url=isset($_SERVER['REQUEST_URI'])?explode("/",$_SERVER['REQUEST_URI']):false;
		if(is_array($url) && count($url)>=1){
			$page_slug=$url[count($url)-1];
			$page_slug=str_replace('page-','',$page_slug);			
			if(isset($_GET)){				
				$c=strpos($page_slug,'?');
				if(is_numeric($c)){
					$page_slug=substr($page_slug,0,$c);
				}
			}
			//dump($page_slug);
			if ( $res=AdminFunctions::getCustomPageByPageSlug($page_slug,'published')){
				$this->render('page',array(
				 'data'=>$res
				));
			} else $this->render('error',array(
		       'msg'=>t("Sorry but we cannot find what you are looking for")
		   ));
		} else $this->render('error',array(
		  'msg'=>t("Sorry but we cannot find what you are looking for")
		));
	}
	
	public function actionsetlang()
	{
		if(!empty($_GET['action'])){
			$url=Yii::app()->createUrl("front/".$_GET['action'],array(
			  'lang'=>$_GET['lang']
			));
		} else {
			$url=Yii::app()->createUrl("front/dashboard",array(
			  'lang'=>$_GET['lang']
			));
		}		
		$this->redirect($url);
	}
	
	public function xactionpaymentList()
	{
		
	}
	
	public function actionPaymentmcd()
	{
		$error=''; $memo=''; $credentials='';
		
		if($res=FrontFunctions::getCustomerByToken($_GET['hash'])){	
			
			/*check if transaction is renew*/
		    if($res['renew_plan_id']>0){
		       $res['plan_id']=$res['renew_plan_id'];
		    }		    		    
			
			$plan_details=FrontFunctions::getPlansByID($res['plan_id']);						
			$price=$res['plan_price'];
			
			/*check if transaction is renew*/
			if($res['renew_plan_id']>0){
				$price=$plan_details['price'];
				if($plan_details['promo_price']>0.0001){
					$price=$plan_details['promo_price'];
				}
			}
			
			if ($promo=FrontFunctions::getPromoCodeDetails()){			    
			    $price=$price-$promo['discount_amount'];
		    }
			
			$credentials=array(
			  'mcd_mode'=>getOptionA('mcd_mode'),
			  'mcd_client_id'=>getOptionA('mcd_client_id'),
			  'mcd_secret'=>getOptionA('mcd_secret'),
			);
			
			$this->body_class.=" page-material";
		    $this->includeMaterial();
		    		   
		    Yii::app()->clientScript->registerScriptFile(
              "https://js.stripe.com/v2/",
		      CClientScript::POS_END
		    );			
		    
		    $memo=t("Payment by")." ".$res['first_name']." ".$res['last_name'];
		    
		    if(empty($credentials['mcd_client_id'])){
		    	$error=t("Payment gateway not properly set");
		    }
		    if(empty($credentials['mcd_secret'])){
		    	$error=t("Payment gateway not properly set");
		    }
			
		} else $error = t("Plan details not found");
		
		if(empty($error)){
			$this->render('payment-mcd',array(
			  'plan_details'=>$plan_details,			  
			  'price'=>$price,
			  'credentials'=>$credentials,
			  'memo'=>$memo,
			  'hash'=>$_GET['hash']
			)); 
		} else $this->render('error',array(
		      'msg'=>$error
		  ));	
	}
	
	public function actionMcd()
	{
		$data=$_GET; $error=''; $db=new DbExt();		
		$status=isset($data['status'])?$data['status']:'' ;		
	    $reference=isset($data['external_reference'])?$data['external_reference']:'';		
	    if ( $status=="success" || $status=="pending"){		
	    		    		    		    	
	    	$hash=explode("-",$reference);
	    	$hash=$hash[1];
	    	
	    	if($res=FrontFunctions::getCustomerByToken($hash)){	    	
		    	
	    		$signup_needs_approval=getOptionA('signup_needs_approval');
		    	$customer_id=$res['customer_id'];			
				$currency_code=FrontFunctions::getCurrenyCode(true);				
				
				require_once 'mercadopago/mercadopago.php';
		    	
		    	$credentials=array(
				  'mcd_mode'=>getOptionA('mcd_mode'),
				  'mcd_client_id'=>getOptionA('mcd_client_id'),
				  'mcd_secret'=>getOptionA('mcd_secret'),
				);
		    	
		    	try {
		    		$mp = new MP($credentials['mcd_client_id'], $credentials['mcd_secret']);	
					$filters = array(            
		               "external_reference" => $reference
		            );      
		            	               
	               $searchResult = $mp->search_payment($filters);           
	               if (is_array($searchResult) && count($searchResult)>=1){
	               	   if ($searchResult['status']==200){
	               	   	   
	               	   	  if($res['renew_plan_id']>0){
	               	   	  	  // renew plan	               	   	  	  
	               	   	  	  if ($plan_details=FrontFunctions::getPlansByID($res['renew_plan_id'])){	
				               	    $price=$plan_details['price'];
								    if($plan_details['promo_price']>0.0001){
										$price=$plan_details['promo_price'];
									}					
											
									$days=$plan_details['expiration'];
									$plan_type=$plan_details['plan_type'];
									
									$renew_params['plan_price']=$price;
									$renew_params['plan_id']=$res['renew_plan_id'];
									$renew_params['plan_expiration']=date("Y-m-d",strtotime($res['plan_expiration']." +$days $plan_type"));
									$renew_params['plan_currency_code']=FrontFunctions::getCurrenyCode(true);
									$renew_params['status']="active";
																		
									//if (is_numeric($res['no_allowed_driver'])){
									if (is_numeric($plan_details['allowed_driver']) ){
									    $renew_params['no_allowed_driver']=$res['no_allowed_driver']+$plan_details['allowed_driver'];
									    $renew_params['no_allowed_task']=$res['no_allowed_task']+$plan_details['allowed_task'];
									} else {
									    $renew_params['no_allowed_driver']=$plan_details['allowed_driver'];
									    $renew_params['no_allowed_task']=$plan_details['allowed_task'];
									}
									
									if ( $plan_details['sms_limit']>0){
										$renew_params['sms_limit']=$res['sms_limit']+$plan_details['sms_limit'];
									} else {
										$renew_params['sms_limit']=$plan_details['sms_limit'];							    
									}				
									
									if ( $plan_details['with_sms']==1){
							             $renew_params['with_sms']=1;
						            } else $renew_params['with_sms']=2;
						            
						            if ( $plan_details['with_broadcast']==1){
								        $renew_params['with_broadcast']=1;
							        } else $renew_params['with_broadcast']=2;
							        							        								
									$db->updateData("{{customer}}",$renew_params,'customer_id',$customer_id);
																		
									$redirect_url=Yii::app()->createUrl('front/signupty',array(
									  'hash'=>$hash,
									  'renew'=>1
									));
									
									$memo="Payment by ".$res['first_name']." ".$res['last_name'];					
									FrontFunctions::savePaymentLogs($customer_id,
									  'signup','mcd',$memo,
									  $price,
									  $currency_code,
									  $reference
									);
									
									$this->redirect($redirect_url);	               	   	  	
							        Yii::app()->end();
									
				               } else $error=t("Payment is successful but cannot find plan details");
	               	   	  	  
	               	   	  } else {
	               	   	  	
	               	   	  	 // new
	               	   	  	 if ($signup_needs_approval!=1){		            	
								$db->updateData("{{customer}}",array(
								  'status'=>'active',
								  'verification_confirm_date'=>AdminFunctions::dateNow()
							 	),'customer_id',$customer_id);
			            	 }
			            								
							 $redirect_url=Yii::app()->createUrl('front/signupty',array(
							   'hash'=>$hash,
							   'needs_approval'=>$signup_needs_approval
							 ));
							
							 $memo="Payment by ".$res['first_name']." ".$res['last_name'];					
							 FrontFunctions::savePaymentLogs($customer_id,
							   'signup','mcd',$memo,
							   $res['plan_price'],
							   $currency_code,
							   $reference
							 );
							
							 FrontFunctions::sendEmailWelcome($res);  
							 $this->redirect($redirect_url);	               	   	  	
							 Yii::app()->end();
	               	   	  }
	               	   	
	               	   } else $error=t("Failed. Cannot process payment");
	               } else  $error=t("Failed. Cannot process payment")." ".$searchResult['status'];
		    	} catch (Exception $e){
				    $error=$e->getMessage();
			    }				
				
	    	} else $error=t("Transaction not found");
		    
		    if(!empty($error)){
		    	 $this->render('error',array(
		         'msg'=>$error
		       ));	
		   }
		    
	    } else $this->render('error',array(
		      'msg'=>t("Invalid payment status")
		));	
	}		
	
	public function actionPaymentatz()
	{
		$error=''; $memo=''; $credentials='';
		
		if($res=FrontFunctions::getCustomerByToken($_GET['hash'])){	
			
			/*check if transaction is renew*/
		    if($res['renew_plan_id']>0){
		       $res['plan_id']=$res['renew_plan_id'];
		    }		    		    
			
			$plan_details=FrontFunctions::getPlansByID($res['plan_id']);						
			$price=$res['plan_price'];
			
			/*check if transaction is renew*/
			if($res['renew_plan_id']>0){
				$price=$plan_details['price'];
				if($plan_details['promo_price']>0.0001){
					$price=$plan_details['promo_price'];
				}
			}
			
			if ($promo=FrontFunctions::getPromoCodeDetails()){			    
			    $price=$price-$promo['discount_amount'];
		    }
			
			$credentials=array(
			  'mode'=>getOptionA('atz_mode'),
			  'atz_login_id'=>getOptionA('atz_login_id'),
			  'atz_transaction_key'=>getOptionA('atz_transaction_key'),
			);
			
			$this->body_class.=" page-material";
		    $this->includeMaterial();
		    		   
		    Yii::app()->clientScript->registerScriptFile(
              "https://js.stripe.com/v2/",
		      CClientScript::POS_END
		    );			
		    
		    $memo=t("Payment by")." ".$res['first_name']." ".$res['last_name'];
		    
		    if(empty($credentials['atz_login_id'])){
		    	$error=t("Payment gateway not properly set");
		    }
		    if(empty($credentials['atz_transaction_key'])){
		    	$error=t("Payment gateway not properly set");
		    }
			
		} else $error = t("Plan details not found");
		
		$default_country=getOptionA('website_default_country');
		if(empty($default_country)){
		  $default_country='US';
		}
		
		if(empty($error)){
			$this->render('payment-atz',array(
			  'plan_details'=>$plan_details,			  
			  'price'=>$price,
			  'credentials'=>$credentials,
			  'memo'=>$memo,
			  'hash'=>$_GET['hash'],
			  'default_country'=>$default_country
			)); 
		} else $this->render('error',array(
		      'msg'=>$error
		  ));	
	}
	
	public function actionpaymentrzr()
	{
		$data=$_GET;
		
		$error=''; $memo=''; $credentials='';
		
		if($res=FrontFunctions::getCustomerByToken($_GET['hash'])){	
			
			/*check if transaction is renew*/
		    if($res['renew_plan_id']>0){
		       $res['plan_id']=$res['renew_plan_id'];
		    }		    		    
			
			$plan_details=FrontFunctions::getPlansByID($res['plan_id']);						
			$price=$res['plan_price'];
			
			/*check if transaction is renew*/
			if($res['renew_plan_id']>0){
				$price=$plan_details['price'];
				if($plan_details['promo_price']>0.0001){
					$price=$plan_details['promo_price'];
				}
			}
						
			if ($promo=FrontFunctions::getPromoCodeDetails()){				 				
				$price=$price-$promo['discount_amount'];
			}
			
			$credentials=array(
			  'mode'=>getOptionA('rzr_mode'),
			  'rzr_key_id'=>getOptionA('rzr_key_id'),
			  'rzr_secret'=>getOptionA('rzr_secret'),
			);
			
			$this->body_class.=" page-material";
		    $this->includeMaterial();
		    		   
		    Yii::app()->clientScript->registerScriptFile(
              "https://js.stripe.com/v2/",
		      CClientScript::POS_END
		    );			
		    		    
		    $memo=t("Payment by")." ".$res['first_name']." ".$res['last_name'];
		    
		    if(empty($credentials['rzr_key_id'])){
		    	$error=t("Payment gateway not properly set");
		    }
		    if(empty($credentials['rzr_secret'])){
		    	$error=t("Payment gateway not properly set");
		    }
		    
		} else $error = t("Plan details not found");
		
		if(empty($error)){
			$this->render('payment-rzr',array(
			  'plan_details'=>$plan_details,			  
			  'price'=>$price,
			  'credentials'=>$credentials,
			  'memo'=>$memo,
			  'hash'=>$_GET['hash'],
			  'customer_details'=>$res
			)); 
		} else $this->render('error',array(
		      'msg'=>$error
		  ));	
	}
	
	public function actionVerifyPaymentRzr()
	{
		$db=new DbExt();
		$data=$_POST; $error=''; $payment_code='rzr';
		$hash=isset($_GET['hash'])?$_GET['hash']:'';
				
		if($res=FrontFunctions::getCustomerByToken($hash)){	 		   
		   if (isset($data['razorpay_payment_id'])){
		   if(!empty($data['razorpay_payment_id'])){
		   	  $payment_id=$data['razorpay_payment_id'];
		   	  
		   	  $signup_needs_approval=getOptionA('signup_needs_approval');
		      $customer_id=$res['customer_id'];			
			  $currency_code=FrontFunctions::getCurrenyCode(true);				
				
			  if($res['renew_plan_id']>0){
			  	 // renew plan	               	   	  	  
       	   	  	  if ($plan_details=FrontFunctions::getPlansByID($res['renew_plan_id'])){	
	               	    $price=$plan_details['price'];
					    if($plan_details['promo_price']>0.0001){
							$price=$plan_details['promo_price'];
						}					
								
						$days=$plan_details['expiration'];
						$plan_type=$plan_details['plan_type'];
						
						$renew_params['plan_price']=$price;
						$renew_params['plan_id']=$res['renew_plan_id'];
						$renew_params['plan_expiration']=date("Y-m-d",strtotime($res['plan_expiration']." +$days $plan_type"));
						$renew_params['plan_currency_code']=FrontFunctions::getCurrenyCode(true);
						$renew_params['status']="active";
															
						//if (is_numeric($res['no_allowed_driver'])){
						if (is_numeric($plan_details['allowed_driver']) ){
						    $renew_params['no_allowed_driver']=$res['no_allowed_driver']+$plan_details['allowed_driver'];
						    $renew_params['no_allowed_task']=$res['no_allowed_task']+$plan_details['allowed_task'];
						} else {
						    $renew_params['no_allowed_driver']=$plan_details['allowed_driver'];
						    $renew_params['no_allowed_task']=$plan_details['allowed_task'];
						}
						
						if ( $plan_details['sms_limit']>0){
							$renew_params['sms_limit']=$res['sms_limit']+$plan_details['sms_limit'];
						} else {
							$renew_params['sms_limit']=$plan_details['sms_limit'];							    
						}	
												
						if ( $plan_details['with_sms']==1){
							$renew_params['with_sms']=1;
						} else $renew_params['with_sms']=2;
						
						if ( $plan_details['with_broadcast']==1){
						    $renew_params['with_broadcast']=1;
						} else $renew_params['with_broadcast']=2;
															
						$db->updateData("{{customer}}",$renew_params,'customer_id',$customer_id);
															
						$redirect_url=Yii::app()->createUrl('front/signupty',array(
						  'hash'=>$hash,
						  'renew'=>1
						));
						
						$memo="Payment by ".$res['first_name']." ".$res['last_name'];					
						FrontFunctions::savePaymentLogs($customer_id,
						  'signup',$payment_code,$memo,
						  $price,
						  $currency_code,
						  $payment_id
						);
						
						$this->redirect($redirect_url);	               	   	  	
				        Yii::app()->end();
						
	               } else $error=t("Payment is successful but cannot find plan details");
				               
			  } else {
			  	 // new
       	   	  	 if ($signup_needs_approval!=1){		            	
					$db->updateData("{{customer}}",array(
					  'status'=>'active',
					  'verification_confirm_date'=>AdminFunctions::dateNow()
				 	),'customer_id',$customer_id);
            	 }
            								
				 $redirect_url=Yii::app()->createUrl('front/signupty',array(
				   'hash'=>$hash,
				   'needs_approval'=>$signup_needs_approval
				 ));
				
				 $memo="Payment by ".$res['first_name']." ".$res['last_name'];					
				 FrontFunctions::savePaymentLogs($customer_id,
				   'signup',$payment_code,$memo,
				   $res['plan_price'],
				   $currency_code,
				   $payment_id
				 );
				
				 FrontFunctions::sendEmailWelcome($res);  
				 $this->redirect($redirect_url);	               	   	  	
				 Yii::app()->end();
			  }
		   	  
		   } else $error=t("Payment id is invalid");
		   } else $error=t("Payment id is invalid");
		} else $error=t("Transaction not found");
		
		if(!empty($error)){
	    	 $this->render('error',array(
	         'msg'=>$error
	       ));	
	    }
	}
	
	public function actionTrack()
	{				
		$this->layout='track_layout';
				
		$id = isset($_GET['id'])?$_GET['id']:'';		
		if(!empty($id)){
			if ( $res=Driver::getTaskByToken($id)){		
				
			   //dump($res); die();			
			   /*$this->body_class.=" page-material";
		       $this->includeMaterial();*/
			   
			   if ( $res['status']=="successful" && $res['ratings']>0){
			   	   $this->render('track-task-done',array(
			   	     'msg'=>t("This task is already completed"),
			   	     'logo_url'=>Driver::getCompanyLogoUrl($res['customer_id'])
			   	   ));		   
			   } else {	    
				   $this->render('track-task',array(
				     'data'=>$res,
				     'avatar'=>Driver::getDriverProfilePic($res['driver_id']),
				     'is_driver_online'=>Driver::isDriverOnline($res['driver_id']),
				     'logo_url'=>Driver::getCompanyLogoUrl($res['customer_id'])
				   ));		
			   }
			} else {
				$this->render('error',array(
		         'msg'=>t("sorry but we cannot find what you are looking for")
		        ));	
			}
		} else {
			$this->render('error',array(
	         'msg'=>t("sorry but we cannot find what you are looking for")
	        ));	
		}
		
	}
		
	
} /*end class*/