<?php
if (!isset($_SESSION)) { session_start(); }

class AjaxfrontController extends CController
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
		 
		 if(isset($this->data['language'])){
		 	Yii::app()->language=$this->data['language'];
		 }	 
		 unset($this->data['language']);	 
	}
	
	private function jsonResponse()
	{
		$resp=array('code'=>$this->code,'msg'=>$this->msg,'details'=>$this->details);
		echo CJSON::encode($resp);
		Yii::app()->end();
	}
	
	public function actionSignup()
	{		
		if ( $this->data['cpassword']!=$this->data['password']){
			$this->msg=t("Confirm password does not match");
			$this->jsonResponse();
			Yii::app()->end();
		}
		if (isset($this->data['plan_id'])){
			$params=$this->data;
			unset($params['action']);
			unset($params['cpassword']);
			$params['date_created']=AdminFunctions::dateNow();
			$params['ip_address']=$_SERVER['REMOTE_ADDR'];												
			
			$encryption_type=Yii::app()->params->encryption_type;
			if(empty($encryption_type)){
				$encryption_type='yii';
			}
			
			if ( $encryption_type=="yii"){
				$params['password']=CPasswordHelper::hashPassword($params['password']);		
			} else $params['password']=md5($params['password']);
				
			$plan_price=FrontFunctions::getPlansPrice($this->data['plan_id']);
			
			$token=md5(AdminFunctions::generateCode(10));
			$verification_code=AdminFunctions::generateNumericCode(5);
			$params['token']=$token;
			$params['verification_code']=$verification_code;
			
			if ($plan_details=FrontFunctions::getPlansByID($params['plan_id'])){							
				$price=$plan_details['price'];
				if($plan_details['promo_price']>0.0001){
					$price=$plan_details['promo_price'];
				}
				$params['plan_price']=$price;
				$days=$plan_details['expiration'];
				$plan_type=$plan_details['plan_type'];
				$params['plan_expiration']=date("Y-m-d",strtotime("+$days $plan_type"));
				$params['plan_currency_code']=FrontFunctions::getCurrenyCode(true);
				$params['with_sms']=$plan_details['with_sms'];
				$params['sms_limit']=$plan_details['sms_limit'];
				$params['with_broadcast']=$plan_details['with_broadcast'];
				
				$params['no_allowed_driver']=$plan_details['allowed_driver'];
				$params['no_allowed_task']=$plan_details['allowed_task'];				
			}
			
			/*dump($params);
			die();*/
			
			$db=new DbExt();
			if ( !FrontFunctions::getCustomerByEmail($this->data['email_address'])){
				if ( $db->insertData("{{customer}}",$params)){
					$customer_id=Yii::app()->db->getLastInsertID();
					$this->code=1;
					$this->msg=t("Registration successul");
					
					$signup_needs_approval=getOptionA('signup_needs_approval');
					$signup_verification_enabled=getOptionA('signup_verification_enabled');
					$signup_verification=getOptionA('signup_verification');
					
					if ( $plan_price>0.0001){
					    $this->details=Yii::app()->createUrl('front/payment',array(
					   'hash'=>$token
					   ));
					} else {
						if ( $signup_needs_approval==1){
							$this->details=Yii::app()->createUrl('front/signupty',array(
							   'hash'=>$token,
							   'needs_approval'=>1
							));							
							FrontFunctions::sendEmailWelcome($this->data);
						} else {
							if ( $signup_verification_enabled==1){
																
								if($signup_verification=="sms"){		
									//send sms verification
									
									$signup_tpl_sms=getOptionA('signup_tpl_sms');
									if(!empty($signup_tpl_sms) && !empty($this->data['mobile_number']) ){
										$company_name=getOptionA('company_name');
										$signup_tpl_sms=smarty('first_name',$params['first_name'],$signup_tpl_sms);
										$signup_tpl_sms=smarty('first_name',$params['first_name'],$signup_tpl_sms);
										$signup_tpl_sms=smarty('verification_code',$verification_code,$signup_tpl_sms);
										$signup_tpl_sms=smarty('company_name',$company_name,$signup_tpl_sms);
										sendSMS($this->data['mobile_number'],$signup_tpl_sms);
									}
									
								} else {									
									//send email verification
									FrontFunctions::sendEmailSignVerification($this->data,$verification_code);
								}
								
								$this->details=Yii::app()->createUrl('front/verification',array(
								  'hash'=>$token,
								  'type'=>$signup_verification
								));
										
							} else {
								$db->updateData("{{customer}}",array(
								  'status'=>'active'
								),'customer_id',$customer_id);
								
								$this->details=Yii::app()->createUrl('front/signupty',array(
								  'hash'=>$token
								));
								
								FrontFunctions::sendEmailWelcome($this->data);
							}
						}
					}
					    
				} else $this->msg=t("Something went wrong during processing your request");
			} else {
				$this->msg=t("Email address already exist in our records");
			}
			
		} else $this->msg=t("Plan id is missing");
		$this->jsonResponse();
	}
	
	public function actionverifySignupCode()
	{		
		if($res=FrontFunctions::getCustomerByToken($this->data['hash'])){			
			$customer_id=$res['customer_id'];			
			if ( $res['verification_code']==$this->data['verification_code']){
				$this->code=1;
				$this->msg=t("Successful");
				
				$signup_needs_approval=getOptionA('signup_needs_approval');
								
				if($signup_needs_approval!=1){
					$db=new DbExt();
					$db->updateData("{{customer}}",array(
					  'status'=>'active',
					  'verification_confirm_date'=>AdminFunctions::dateNow()
					),'customer_id',$customer_id);
				}
				
				$this->details=Yii::app()->createUrl('front/signupty',array(
				  'hash'=>$this->data['hash'],
				  'needs_approval'=>$signup_needs_approval
				));
				
				FrontFunctions::sendEmailWelcome($res);
				
			} else $this->msg=t("Verification code is invalid");
		} else $this->msg=t("Token not found");
		$this->jsonResponse();
	}
	
	public function actionpaymentOption()
	{		
		if(empty($this->data['payment_provider'])){
			$this->msg=t("Please choose payment options");
			$this->jsonResponse();
			Yii::app()->end();
		}
		if($res=FrontFunctions::getCustomerByToken($this->data['token'])){				
			$this->code=1;
			$this->msg=t("Please wait while we redirect you");
			$this->details=Yii::app()->createUrl('front/payment-'.$this->data['payment_provider'],array(
			  'hash'=>$this->data['token'],
			  'lang'=>Yii::app()->language
			));
		} else $this->msg=t("Token not found");
		$this->jsonResponse();
	}
	
	public function actionpaypalExpressCheckout()
	{		
		$db=new DbExt();
		$signup_needs_approval=getOptionA('signup_needs_approval');
		if ( $con=FrontFunctions::getPaypalConnection()){
			if($res=FrontFunctions::getCustomerByToken($this->data['hash'])){
				$customer_id=$res['customer_id'];			
				$currency_code=FrontFunctions::getCurrenyCode(true);				
				$paypal=new Paypal($con);	
				$paypal->debug=false;			
								
				$paypal->params['PAYERID']=$this->data['payerid'];
	            $paypal->params['AMT']=AdminFunctions::normalPrettyPrice($this->data['amt']);   
	            $paypal->params['TOKEN']=$this->data['token'];     
	            $paypal->params['CURRENCYCODE']=$currency_code;
	            	            
	            if ($resp_paypal=$paypal->expressCheckout()){  
	            	
	            	//dump($resp_paypal);
	            	
	            	  /*check if transaction is renew*/
		            if($res['renew_plan_id']>0){
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
							
							$this->code=1;
							$this->msg=t("Payment successful");
							$this->details=Yii::app()->createUrl('front/signupty',array(
							  'hash'=>$this->data['hash'],
							  'renew'=>1
							));
							
							$memo="Payment by ".$res['first_name']." ".$res['last_name'];					
							FrontFunctions::savePaymentLogs($customer_id,
							  'signup','pyp',$memo,
							  $price,
							  $currency_code,
							  $resp_paypal['TRANSACTIONID']
							);
							
		               } else $this->msg=t("Payment is successful but cannot find plan details");
		                 
		            } else {
	            	
		            	if ($signup_needs_approval!=1){		            	
							$db->updateData("{{customer}}",array(
							  'status'=>'active',
							  'verification_confirm_date'=>AdminFunctions::dateNow()
							),'customer_id',$customer_id);
		            	}
						$this->code=1;
						$this->msg=t("Payment successful");
						$this->details=Yii::app()->createUrl('front/signupty',array(
						  'hash'=>$this->data['hash'],
						  'needs_approval'=>$signup_needs_approval
						));
						
						$memo="Payment by ".$res['first_name']." ".$res['last_name'];					
						FrontFunctions::savePaymentLogs($customer_id,
						  'signup','pyp',$memo,
						  $res['plan_price'],
						  $currency_code,
						  $resp_paypal['TRANSACTIONID']
						);
						
						FrontFunctions::sendEmailWelcome($res);
		            }
	            	
	            } else $this->msg=$paypal->getError();
			} else $this->details=t("Plan details not found");
		} else $this->msg=t("Paypal credentials invalid");
		$this->jsonResponse();
	}
	
	public function actionPaymentStripe()
	{	
		$db=new DbExt();
		if (isset($this->data['stripe_token'])){		 	
			if($res=FrontFunctions::getCustomerByToken($this->data['hash'])){		
				$customer_id=$res['customer_id'];
				$original_amount=$res['plan_price'];
				$amount=AdminFunctions::normalPrettyPrice($res['plan_price']);
				
				if($res['renew_plan_id']>0){
					if ($plan_details=FrontFunctions::getPlansByID($res['renew_plan_id'])){	
					$price=$plan_details['price'];
					    if($plan_details['promo_price']>0.0001){
							$price=$plan_details['promo_price'];
						}			
					}
					$amount=AdminFunctions::normalPrettyPrice($price);
				}
												
				if ($promo=FrontFunctions::getPromoCodeDetails()){				    
				    $amount=$amount-$promo['discount_amount'];
				    $amount=AdminFunctions::normalPrettyPrice($amount);
			    }
			    
			    if($amount>0.0001){
					$amount=$amount*100;
				} else $amount=0;				
			    				
				//dump($amount); die();
								
				try {
				    	
					$stripe_mode=getOptionA('stripe_mode'); $secret_key='';
					if ($stripe_mode=="sandbox"){
						$secret_key=getOptionA('stripe_sandbox_secret_key');
					} else {
						$secret_key=getOptionA('stripe_live_secret_key');
					} 
					
					$currency_code=FrontFunctions::getCurrenyCode(true);
										
					require_once('stripe/lib/Stripe.php');
					Stripe::setApiKey($secret_key);
					
					$customer = Stripe_Customer::create(array(			    
			         'card'  => $this->data['stripe_token']
			        ));
			        
			        $charge = Stripe_Charge::create(array(
			          'customer' => $customer->id,
			          'amount'   => $amount,
			          'currency' => $currency_code
			        ));	
			        
			        $chargeArray = $charge->__toArray(true);			        			        
			        $ref_id=$chargeArray['id'];
			        
			        /*check if renew*/
			        if($res['renew_plan_id']>0){
			        	
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
							
							$this->code=1;
							$this->msg=t("Payment successful");
							$this->details=Yii::app()->createUrl('front/signupty',array(
							  'hash'=>$this->data['hash'],
							  'renew'=>1
							));
							
							$memo="Payment by ".$res['first_name']." ".$res['last_name'];					
							FrontFunctions::savePaymentLogs($customer_id,
							  'signup','stp',$memo,
							  $price,
							  $currency_code,
							  $ref_id
							);
							
		                } else $this->msg=t("Payment is successful but cannot find plan details");
			        				        	
			        	$this->jsonResponse();
			        	Yii::app()->end();
			        } /*end renew*/
			        			        
			        $signup_needs_approval=getOptionA('signup_needs_approval');
	            	if ($signup_needs_approval!=1){		            	
						$db->updateData("{{customer}}",array(
						  'status'=>'active',
						  'verification_confirm_date'=>AdminFunctions::dateNow()
						),'customer_id',$customer_id);
	            	}
					$this->code=1;
					$this->msg=t("Payment successful");
					$this->details=Yii::app()->createUrl('front/signupty',array(
					  'hash'=>$this->data['hash'],
					  'needs_approval'=>$signup_needs_approval
					));
					
					$memo="Payment by ".$res['first_name']." ".$res['last_name'];					
					FrontFunctions::savePaymentLogs($customer_id,
					  'signup','stp',$memo,
					  $res['plan_price'],
					  $currency_code,
					  $ref_id
					);
					
					FrontFunctions::sendEmailWelcome($res);
			        
				} catch (Exception $e)   {
					 $this->msg=$e->getMessage();
				}
				
			} else $this->msg=t("Plan details not found");
		} else $this->msg=t("Stripe token is invalid");
		$this->jsonResponse();
	}
	
	public function actionResendCode()
	{
		
		if ($res=FrontFunctions::getCustomerByToken($this->data['hash'])){
			
			$verification_code=$res['verification_code'];
			if($this->data['verification_type']=="mail"){				
				FrontFunctions::sendEmailSignVerification($res,$verification_code);
				$this->code=1;
				$this->msg=t("We have sent your verification code to your email");
			} else if ($this->data['verification_type']=="sms") {
				
				$signup_tpl_sms=getOptionA('signup_tpl_sms');
				if(!empty($signup_tpl_sms) && !empty($res['mobile_number']) ){
					$company_name=getOptionA('company_name');
					$signup_tpl_sms=smarty('first_name',$res['first_name'],$signup_tpl_sms);
					$signup_tpl_sms=smarty('first_name',$res['first_name'],$signup_tpl_sms);
					$signup_tpl_sms=smarty('verification_code',$verification_code,$signup_tpl_sms);
					$signup_tpl_sms=smarty('company_name',$company_name,$signup_tpl_sms);
					sendSMS($res['mobile_number'],$signup_tpl_sms);					
					$this->code=1;
				    $this->msg=t("We have sent your verification code to your mobile");
				} else $this->msg=t("SMS template not available");
				
			} else $this->msg=t("Invalid verification type");
		} else $this->msg=t("hash not found");
		$this->jsonResponse();
	}
	
	public function actiongetSignup()
	{		
		if($res=FrontFunctions::getCustomerByEmail($this->data['email_address'])){						
			if($res['status']=="pending"){
				$this->code=1;
				$this->msg=t("Application found");
				$this->details=Yii::app()->createUrl('/front/payment',array(
				  'hash'=>$res['token'],
				  'lang'=>Yii::app()->language
				));
			} else $this->msg=t("Your application found but the status is already")." ".t($res['status']);
		} else $this->msg=t("Email not found");
		$this->jsonResponse();
	}
	
	public function actionatz()
	{
		
		$db=new DbExt();
		
		if($res=FrontFunctions::getCustomerByToken($this->data['hash'])){	
			
			/*check if transaction is renew*/
			
			$customer_id=$res['customer_id'];			
			$currency_code=FrontFunctions::getCurrenyCode(true);				
			
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
			
			$credentials=array(
			  'mode'=>getOptionA('atz_mode'),
			  'atz_login_id'=>getOptionA('atz_login_id'),
			  'atz_transaction_key'=>getOptionA('atz_transaction_key'),
			);
			
			$memo=t("Payment by")." ".$res['first_name']." ".$res['last_name'];
			//$memo.=" ".AdminFunctions::generateNumericCode(5);
						
			
			$default_country=getOptionA('website_default_country');
			if(empty($default_country)){
			  $default_country='US';
			}
			
			if ($promo=FrontFunctions::getPromoCodeDetails()){			    
			    $price=$price-$promo['discount_amount'];
		    }
						
			$amount_to_pay=number_format($price,2,'.','');			
			
			define("AUTHORIZENET_API_LOGIN_ID",$credentials['atz_login_id']); 
            define("AUTHORIZENET_TRANSACTION_KEY",$credentials['atz_transaction_key']);
            define("AUTHORIZENET_SANDBOX",$credentials['mode']=="sandbox"?true:false);  
            
            require_once 'anet_php_sdk/AuthorizeNet.php';
            $transaction = new AuthorizeNetAIM;
            $transaction->setSandbox(AUTHORIZENET_SANDBOX);  
            
            $params= array(		        
		        'description' => $memo,
		        'amount'     => $amount_to_pay, 
		        'card_num'   => $this->data['card_number'], 
		        'exp_date'   => $this->data['expiration_month']."/".$this->data['expiration_year'],
		        'first_name' => $this->data['first_name'],
		        'last_name'  => $this->data['last_name'],
		        'address'    => $this->data['address'],
		        'city'       => $this->data['city'],
		        'state'      => $this->data['state'],
		        'country'    => $this->data['country'],
		        'zip'        => $this->data['zipcode'],		        
		        'card_code'  => $this->data['cvc'],
	        );  
	        	        
	        $transaction->setFields($params);        
            $response = $transaction->authorizeAndCapture(); 
            
            if ($response->approved) {
            	
            	$signup_needs_approval=getOptionA('signup_needs_approval');
            	
            	$transaction_id = $response->transaction_id;
            	//dump($resp_transaction);
            	
            	if($res['renew_plan_id']>0){
            		
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
						
						//dump($renew_params);
			
						$db->updateData("{{customer}}",$renew_params,'customer_id',$customer_id);
						
						$this->code=1;
						$this->msg=t("Payment successful");
						$this->details=Yii::app()->createUrl('front/signupty',array(
						  'hash'=>$this->data['hash'],
						  'renew'=>1
						));
						
						$memo="Payment by ".$res['first_name']." ".$res['last_name'];					
						FrontFunctions::savePaymentLogs($customer_id,
						  'signup','atz',$memo,
						  $price,
						  $currency_code,
						  $transaction_id
						);
        			
            		} else $this->msg=t("Payment is successful but cannot find plan details");
            		
            	} else {
            		if ($signup_needs_approval!=1){		            	
						$db->updateData("{{customer}}",array(
						  'status'=>'active',
						  'verification_confirm_date'=>AdminFunctions::dateNow()
						),'customer_id',$customer_id);
	            	}
					$this->code=1;
					$this->msg=t("Payment successful");
					$this->details=Yii::app()->createUrl('front/signupty',array(
					  'hash'=>$this->data['hash'],
					  'needs_approval'=>$signup_needs_approval
					));
					
					$memo="Payment by ".$res['first_name']." ".$res['last_name'];					
					FrontFunctions::savePaymentLogs($customer_id,
					  'signup','atz',$memo,
					  $res['plan_price'],
					  $currency_code,
					  $transaction_id
					);
					
					FrontFunctions::sendEmailWelcome($res);
            	}
            	
            } else {
            	if(!empty($response->response_reason_text)){
            	   $this->msg=$response->response_reason_text;
            	} else {
            	   $this->msg=$response->error_message;
            	}
            }
		
		} else $this->msg=t("Plan details not found");
		$this->jsonResponse();
	}
	
	public function actionrunTrackMap()
	{		
		if ( $res=Driver::getTaskByToken($this->data['task_id'])){				
			$res['status_raw']=$res['status'];
			$res['status']=t($res['status']);			
						
			$distance="00 ".t("Mins");
			
			if (!empty($res['transport_type'])){
				$travel_mode=$res['transport_type'];
			} else $travel_mode='driving';
									
			if ($res['driver_id']>0){				
				if ( $res['trans_type']=="delivery"){						
					if(!empty($res['drop_address'])){						
						if ( $distance=Driver::getTaskDistance( $res['driver_location_lat'], $res['driver_location_lng'],
						    $res['dropoff_task_lat'], $res['dropoff_task_lng'] , $travel_mode )){											    							    	
						    	
						}
					} else {
						if ( $distance=Driver::getTaskDistance( $res['driver_location_lat'], $res['driver_location_lng'],
						    $res['task_lat'], $res['task_lng'] )){						
						}
					}					
				} else {					
					if(!empty($res['drop_address'])){
						if ( $distance=Driver::getTaskDistance( $res['driver_location_lat'], $res['driver_location_lng'],
						    $res['task_lat'], $res['task_lng'] , $travel_mode )){						    	
						}
					} else {
						if ( $distance=Driver::getTaskDistance( $res['driver_location_lat'], $res['driver_location_lng'],
						    $res['task_lat'], $res['task_lng'] , $travel_mode )){						
						}
					}
				}
			}	

			/*echo 'response :<br/>';
			dump($distance);*/
			if (!$distance){
				$distance="00 ".t("Mins");
			}
			
			$res['eta']=$distance;		
			$res['find_driver_label']=$find_driver_label=t("Find agent");
			
			$driver_info_window='<p>'.$res['driver_name']."</p>";
            $driver_info_window.='<p>'.t("Your Agent")."</p>";
            
            $drofoff_info_window='<p>'.$res['drop_address']."</p>";
			if ($res['trans_type']=="delivery"){
			   $drofoff_info_window.='<p>'.t("Pickup Details")."</p>";	
			} else $drofoff_info_window.='<p>'.t("Drop Details")."</p>";
            
            $res['driver_info_window']=$driver_info_window;
            $res['travel_mode']='driving';
            $res['drofoff_info_window']=$drofoff_info_window;
            
            $avatar=Driver::getDriverProfilePic($res['driver_id']);
            $is_driver_online=Driver::isDriverOnline($res['driver_id']);
            
            
            $distance=Yii::app()->functions->translateDate($distance);
            
            //dump($distance); die();
                      
            /*track details*/
            //dump($res);
            ob_start();	
            if ($res['driver_id']>0):
            ?>
             <div class="row"> 
		      <div class="col-xs-3"> 
		          <div class="avatar-wrapper">
		            <img src="<?php echo $avatar?>" class="avatar">          
		          <?php if ($is_driver_online):?>
		            <div class="status-circle cicle-online"></div>
		          <?php else :?>		          
		            <div class="status-circle cicle-offline"></div>
		          <?php endif;?>
		          </div> <!--avatar-wrapper-->
		      </div>
		      <div class="col-xs-4" style="padding-left:0;"> 
		        <p class="agent-name"><?php echo $res['driver_name']?><br/>
		        <span><?php echo t("Your Agent")?></span><br/>
		        <span style="font-size:11px;"><?php echo t("Task ID").": ".$res['task_id']?></span>
		        </p>
		      </div>
		      
		      <div class="col-xs-5 text-right">
		        <div class="eta-wrap">
		        <p><?php echo t("ETA")?><br/>
		        <b class="track_eta"><?php echo $distance?></b><br/>
		        <span class="task-status"><?php echo t("Task Status")?> : 
		          <b class="task-stats">
		            <span class="tag <?php echo $res['status']?>"><?php echo t($res['status'])?></span>
		          </b>
		        </span>
		        </p>
		        </div> <!--eta-wrap-->
		        
		        <?php if (!empty($res['driver_phone'])):?>
		        <div class="track-contact-wrap">
		           <a href="tel:<?php echo $res['driver_phone']?>" >     
		            <i class="ion-ios-telephone-outline"></i>     
		           </a>
		        </div>
		        <?php endif;?>
		        
		      </div> <!--text-right-->      
		    </div> <!--row-->
		    <?php
		    else :
		      ?><p class="text-center no-agent-p"><?php echo t("There is no assign agent for this task")?></p><?php
		    endif;
		    $track_details = ob_get_contents();
            ob_end_clean();
            
            //dump($track_details);
            
            $res['track_details']=$track_details;
            
            $contact_phone='';
            
            if (!empty($res['driver_phone'])){
            ob_start();	
	            ?>
	            <a href="tel:<?php echo $res['driver_phone']?>" >     
	           <i class="ion-ios-telephone-outline"></i>     
	            </a>
	            <?php
	            $contact_phone = ob_get_contents();
	            ob_end_clean();
            }
            
            $res['contact_phone']=$contact_phone;

			//dump($res);
			switch ($res['status']) {
				case "failed":
				case "declined":
				case "cancelled":
					$this->msg = '<h3 style="margin-top:40%;">'.t("This task has been marked as")." ".t($res['status']).'</h3>';
					break;
			
				default:
					$this->msg="OK";
					break;
			}
						
			$this->code=1;			
			$this->details=array(
			  'task_info'=>$res
			);
		} else $this->msg=t("Task not found");
		$this->jsonResponse();
	}
	
	public function actionCustomerRating()
	{
		if (isset($this->data['task_id'])){
			
			if (empty($this->data['score'])){
				$this->msg=t("Rating is required");
				$this->jsonResponse();
				Yii::app()->end();
			}
			
			$params=array(
			   'ratings'=>$this->data['score'],
			   'rating_comment'=>$this->data['rating_comment'],
			   'date_modified'=>AdminFunctions::dateNow(),
			   'ip_address'=>$_SERVER['REMOTE_ADDR']
			);			
			$db=new DbExt();
			if ( $db->updateData("{{driver_task}}",$params,'task_id',$this->data['task_id'])){
				$this->code=1;
				$this->msg=t("Thank you! we have receive your feedback");
				$this->details=array(
				  'task_id'=>$this->data['task_id']
				);
			} else $this->msg=t("Something went wrong cannot update records");
		} else $this->msg=t("Task not found");
		$this->jsonResponse();
	}
	
	public function actionApplyPromocode()
	{	
		if ( !empty($this->data['promo_code'])){
			$promo_code=trim($this->data['promo_code']);			
			if ( $res=AdminFunctions::getPromoCode($promo_code)){				
				$time_1=date('Y-m-d g:i:s a');			
				$time_2=date("Y-m-d g:i:s a",strtotime($res['expiration']));
				$time_diff=Functions::dateDifference($time_2,$time_1);				
				if (is_array($time_diff) && count($time_diff)>=1){
					if ( $time_diff['days']>0){
						$this->msg=t("Promo code already expired");
						$this->jsonResponse();
					}
					if ( $time_diff['hours']>0){
						$this->msg=t("Promo code already expired");
						$this->jsonResponse();
					}
				} 				
				$discount_amount=0;
				if ( $res['discount_type']=="percentage"){
					if($plan=FrontFunctions::getCustomerByToken($this->data['token'])){						
						$discount_amount=$plan['plan_price']*($res['discount']/100);
					} else {
						$this->msg=t("Something went wrong please try again later");
						$this->jsonResponse();
					}
				} else $discount_amount=$res['discount'];
				
				if ($discount_amount<=0){
					$this->msg=t("Promo code cannot be applied the total amount to be paid is less than or equal to zero");
					$this->jsonResponse();
				}
				
				
				if($plan=FrontFunctions::getCustomerByToken($this->data['token'])){
					$total = $plan['plan_price']-$discount_amount;					
					if($total<=0){
						$this->msg=t("Cannot apply voucher code resulting to negative balance or zero amount");
						$this->jsonResponse();
					}
				}
								
				$promo_details=array(
				  'promo_code_id'=>$res['promo_code_id'],
				  'code'=>$promo_code,
				  'discount'=>$res['discount'],
				  'discount_amount'=>$discount_amount,
				  'expiration'=>$res['expiration'],
				  'discount_type'=>$res['discount_type']
				);								
				$_SESSION['promo_code']=$promo_details;
				
				$this->code=1; $this->msg=t("Successful");
				$this->details=Yii::app()->createUrl('front/payment',array(
					   'hash'=>$this->data['token'],
					   'promo'=>"apply"
					   ));
				
			} else $this->msg=t("Invalid promo code");
		} else $this->msg=t("Invalid promo code");
		$this->jsonResponse();
	}
	
	public function actionRemovePromocode()
	{
		unset($_SESSION['promo_code']);
		$this->code=1; $this->msg=t("Successful");
		$this->details=Yii::app()->createUrl('front/payment',array(
					   'hash'=>$this->data['token'],
					   'promo'=>"remove"
					   ));
		$this->jsonResponse();
	}
	
}/* end class*/