<?php
class UpdateController extends CController
{
	public function beforeAction($action)
	{
		Yii::app()->session;
		
		if(!AdminFunctions::islogin()){						
			Yii::app()->end();
		}
		return true;
	}
	
	public function actionIndex()
	{		
		$prefix=Yii::app()->db->tablePrefix;		
		$table_prefix=$prefix;
		
		$DbExt=new DbExt;
									
		echo "<br/>Updating task_history<br/>";
		$new_field=array( 
		   'task_id'=>"int(14) NOT NULL",
		   'reason'=>"text NOT NULL",
		   'customer_signature'=>"varchar(255) NOT NULL",
		   'notification_viewed'=>"int(1) NOT NULL DEFAULT '2'",
		   'driver_id'=>"int(14) NOT NULL",
		   'driver_location_lat'=>"varchar(50) NOT NULL",
		   'driver_location_lng'=>"varchar(50) NOT NULL"		   
		);
		$this->alterTable('task_history',$new_field);
		
		
		$stmt="ALTER TABLE ".$table_prefix."driver_task AUTO_INCREMENT = 100000;";
		echo "Altering table driver_task<br/>";
		$DbExt->qry($stmt);
		
		
		echo "<br/>Updating customer<br/>";
		$new_field=array( 
		   'no_allowed_driver'=>"varchar(50) NOT NULL DEFAULT ''",		  
		   'no_allowed_task'=>"varchar(50) NOT NULL DEFAULT ''",
		   'services'=>"varchar(255) NOT NULL DEFAULT ''"
		);
		$this->alterTable('customer',$new_field);
		
		echo "<br/>Updating customer allowed task<br/>";
		$stmt="
		SELECT plan_id,allowed_driver,allowed_task
		FROM {{plan}}
		WHERE
		status IN ('published')
		";	
		if ($res_plan=$DbExt->rst($stmt)){			
			foreach ($res_plan as $res_plan_val) {
				echo "<pre>";
			    print_r($res_plan_val);
			    echo "</pre>";
			    $stmt="
			    UPDATE {{customer}}
			    SET no_allowed_driver=".Driver::q($res_plan_val['allowed_driver']).",
			    no_allowed_task =".Driver::q($res_plan_val['allowed_task'])."
			    WHERE
			    plan_id=".Driver::q($res_plan_val['plan_id'])."
			    AND no_allowed_driver=''
			    AND no_allowed_task=''    
			    ";
			    echo $stmt;
			    $DbExt->qry($stmt);
			}
		}
		
		echo "<br/><br/>Updating admin<br/>";
		$new_field=array( 
		   'email_address'=>"varchar(100) NOT NULL DEFAULT ''"		   
		);
		$this->alterTable('admin',$new_field);
		
		echo "<br/>Updating driver_task<br/>";
		$new_field=array( 
		   'dropoff_contact_name'=>"varchar(255) NOT NULL DEFAULT ''",
		   'dropoff_contact_number'=>"varchar(50) NOT NULL DEFAULT ''",
		   'drop_address'=>"varchar(255) NOT NULL DEFAULT ''",
		   'dropoff_task_lat'=>"varchar(50) NOT NULL DEFAULT ''",
		   'dropoff_task_lng'=>"varchar(50) NOT NULL DEFAULT ''",
		   'task_token'=>"varchar(255) NOT NULL DEFAULT ''",
		   'ratings'=>"varchar(14) NOT NULL DEFAULT ''",
		   'rating_comment'=>"varchar(255) NOT NULL DEFAULT ''",
		);
		$this->alterTable('driver_task',$new_field);
		
		echo "<br/>Create new table contacts<br/>";
		$stmt="
		CREATE TABLE IF NOT EXISTS ".$table_prefix."contacts (
		  `contact_id` int(14) NOT NULL,
		  `customer_id` int(14) NOT NULL,
		  `fullname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		  `phone` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
		  `address` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		  `addresss_lat` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
		  `addresss_lng` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
		  `status` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'pending',
		  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		  `ip_address` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		   PRIMARY KEY (`contact_id`),
		   KEY `customer_id` (`customer_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		$DbExt->qry($stmt);			
					
		$stmt="
		ALTER TABLE ".$table_prefix."contacts
        MODIFY `contact_id` int(14) NOT NULL AUTO_INCREMENT;  
		";
		echo "Altering table contacts<br/>";
		$DbExt->qry($stmt);
				
		
		echo "<br/>Create new table services<br/>";
		$stmt="
		CREATE TABLE IF NOT EXISTS ".$table_prefix."services (
		  `services_id` int(14) NOT NULL,
		  `services_parent_id` int(14) NOT NULL DEFAULT '0',
		  `sevices_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `description` text COLLATE utf8_unicode_ci NOT NULL,
		  `status` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'published',
		  `sequence` int(14) NOT NULL DEFAULT '0',
		  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		  `ip_address` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		   PRIMARY KEY (`services_id`),
		   KEY `services_parent_id` (`services_parent_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
		";
		$DbExt->qry($stmt);	
				
		$stmt="
		ALTER TABLE ".$table_prefix."services
        MODIFY `services_id` int(14) NOT NULL AUTO_INCREMENT;
		";
		echo "Altering table services<br/>";
		$DbExt->qry($stmt);		

		if ( !$i=$DbExt->rst("SELECT * FROM ".$table_prefix."services LIMIT 0,1")){		
			$path=Yii::getPathOfAlias('webroot')."/protected";
            require_once($path.'/config/services_data.php');
			$DbExt->qry($services);	
		} 
			
		
		echo "<br/>Updating task_history<br/>";
		$new_field=array( 
		   'notes'=>"varchar(255) NOT NULL DEFAULT ''",
		   'photo_name'=>"varchar(255) NOT NULL DEFAULT ''",
		   'receive_by'=>"varchar(255) NOT NULL DEFAULT ''",
		   'signature_base30'=>"text"		   
		);
		$this->alterTable('task_history',$new_field);
		
		echo "<br/>Updating driver<br/>";
		$new_field=array( 
		   'profile_photo'=>"varchar(255) NOT NULL DEFAULT ''",
		   'app_version'=>"varchar(14) NOT NULL DEFAULT ''"
		);
		$this->alterTable('driver',$new_field);
		
		
		/*VERSION 1.2 UPDATE*/
		
		echo "<br/>Updating plan<br/>";
		$new_field=array( 
		   'sms_limit'=>"int(14) NOT NULL DEFAULT '0'",
		);
		$this->alterTable('plan',$new_field);
		
		echo "<br/>Updating customer<br/>";
		$new_field=array( 
		   'sms_limit'=>"int(14) NOT NULL DEFAULT '0'",
		   'auto_retry_assigment'=>"varchar(1) NOT NULL DEFAULT ''",
		);
		$this->alterTable('customer',$new_field);		
		
		echo "<br/>Updating sms_logs<br/>";
		$new_field=array( 
		   'customer_id'=>"int(14) NOT NULL DEFAULT '0'",		   
		);
		$this->alterTable('sms_logs',$new_field);		
		$this->addIndex('sms_logs','customer_id');		
		
		echo "<br/>Create new table promo_code<br/>";
		$stmt="		
		CREATE TABLE IF NOT EXISTS ".$table_prefix."promo_code (
		  `promo_code_id` int(14) NOT NULL,
		  `promo_code_name` varchar(25) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `discount` varchar(14) COLLATE utf8_unicode_ci DEFAULT '',
		  `discount_type` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'fixed',
		  `expiration` date NOT NULL DEFAULT '0000-00-00',
		  `status` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'published',
		  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		  `ip_address` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		   PRIMARY KEY (`promo_code_id`),		   
		   KEY `promo_code_name` (`promo_code_name`),
		   KEY `discoun_type` (`discount_type`),
		   KEY `discount` (`discount`),
		   KEY `status` (`status`),
		   KEY `expiration` (`expiration`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
		";
		$DbExt->qry($stmt);	
				
		$stmt="
		ALTER TABLE ".$table_prefix."promo_code
        MODIFY `promo_code_id` int(14) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
		";
		echo "Altering table promo_code<br/>";
		$DbExt->qry($stmt);
		
		echo "<br/>Updating payment_logs<br/>";
		$new_field=array( 
		   'promo_code_id'=>"int(14) NOT NULL DEFAULT '0'",
		   'promo_code_discount'=>"varchar(14) NOT NULL DEFAULT ''",
		);
		$this->alterTable('payment_logs',$new_field);		
		$this->addIndex('payment_logs','promo_code_id');
		
		echo "<br/>Updating driver_task<br/>";
		$new_field=array( 
		   'critical'=>"int(1) NOT NULL DEFAULT '1'",   
		);
		$this->alterTable('driver_task',$new_field);		
		
		echo "<br/>Create new table driver_track_location<br/>";
		$stmt="		
		CREATE TABLE IF NOT EXISTS ".$table_prefix."driver_track_location (
		  `id` int(14) NOT NULL,
		  `customer_id` int(14) NOT NULL DEFAULT '0',
		  `driver_id` int(14) NOT NULL DEFAULT '0',
		  `latitude` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `longitude` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `altitude` varchar(50) COLLATE utf8_unicode_ci DEFAULT '',
		  `accuracy` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `altitudeAccuracy` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `heading` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `speed` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		  `ip_address` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `track_type` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		   PRIMARY KEY (`id`),
		   KEY `customer_id` (`customer_id`),
		   KEY `driver_id` (`driver_id`)		   
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;		
		";
		$DbExt->qry($stmt);	
		
		$stmt="
		ALTER TABLE ".$table_prefix."driver_track_location
        MODIFY `id` int(14) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
		";
		echo "Altering table driver_track_location<br/>";
		$DbExt->qry($stmt);
		
		
		echo "<br/>Updating email_logs<br/>";
		$new_field=array( 
		   'customer_id'=>"int(14) NOT NULL DEFAULT '0'",   
		);		
		$this->alterTable('email_logs',$new_field);		
		$this->addIndex('email_logs','customer_id');		
		
		
		echo "<br/>Updating driver_pushlog<br/>";
		$new_field=array( 
		   'broadcast_id'=>"int(14) NOT NULL DEFAULT '0'",   
		);		
		$this->alterTable('driver_pushlog',$new_field);		
		$this->addIndex('driver_pushlog','broadcast_id');		
		
		echo "<br/>Updating plan<br/>";
		$new_field=array( 
		   'with_broadcast'=>"int(1) NOT NULL DEFAULT '0'",   
		);		
		$this->alterTable('plan',$new_field);		
		$this->addIndex('plan','with_broadcast');		
				
		echo "<br/>Updating customer<br/>";
		$new_field=array( 
		   'with_broadcast'=>"int(1) NOT NULL DEFAULT '0'",   
		);		
		$this->alterTable('customer',$new_field);		
		$this->addIndex('customer','with_broadcast');	
		
		$stmt="		
		CREATE TABLE IF NOT EXISTS ".$table_prefix."push_broadcast (
		  `broadcast_id` int(14) NOT NULL,
		  `team_id` int(14) NOT NULL DEFAULT '0',
		  `customer_id` int(14) NOT NULL DEFAULT '0',
		  `push_title` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `push_message` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `status` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'pending',
		  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		  `date_process` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		  `ip_address` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		   PRIMARY KEY (`broadcast_id`),
		   KEY `team_id` (`team_id`),
		   KEY `customer_id` (`customer_id`),
		   KEY `status` (`status`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;		
		";
		$DbExt->qry($stmt);	
				
		$stmt="
		ALTER TABLE ".$table_prefix."push_broadcast
        MODIFY `broadcast_id` int(14) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
		";
		echo "Altering table driver_track_location<br/>";
		$DbExt->qry($stmt);
		
		
		/*1.4*/
		$stmt="		
		CREATE TABLE IF NOT EXISTS ".$table_prefix."api_logs (
		  `id` int(14) NOT NULL,
		  `map_provider` varchar(100) NOT NULL DEFAULT '',
		  `api_functions` varchar(100) NOT NULL,
		  `api_response` text,
		  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		  `date_call` date NOT NULL DEFAULT '0000-00-00',
		  `ip_address` varchar(50) NOT NULL DEFAULT ''
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		
		ALTER TABLE ".$table_prefix."api_logs
		  ADD PRIMARY KEY (`id`);
		
		ALTER TABLE ".$table_prefix."api_logs
		  MODIFY `id` int(14) NOT NULL AUTO_INCREMENT;
		";
		$DbExt->qry($stmt);	
		
		echo "<br/>Updating driver<br/>";
		$new_field=array( 
		   'last_onduty'=>"varchar(50) NOT NULL DEFAULT ''",   
		   'is_online'=>"int(1) NOT NULL DEFAULT '1'",   
		);		
		$this->alterTable('driver',$new_field);		
		
		echo "<br/>Updating driver<br/>";
		$new_field=array( 
		   'date_log'=>"date NOT NULL DEFAULT '0000-00-00'",  		   
		);		
		$this->alterTable('driver_track_location',$new_field);		
		
		
		/*Paystack*/
		$stmt="		
		CREATE TABLE IF NOT EXISTS ".$table_prefix."paystack_logs (
		  `id` int(11) NOT NULL,
		  `transaction_type` varchar(100) NOT NULL DEFAULT 'signup',
		  `customer_id` int(14) NOT NULL DEFAULT '0',
		  `reference_number` varchar(255) NOT NULL DEFAULT '',
		  `params1` varchar(255) NOT NULL DEFAULT '',
		  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  `ip_address` varchar(50) NOT NULL DEFAULT ''
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		
		ALTER TABLE ".$table_prefix."paystack_logs
		  ADD PRIMARY KEY (`id`),
		  ADD KEY `customer_id` (`customer_id`),
		  ADD KEY `reference_number` (`reference_number`);
		  
		  ALTER TABLE ".$table_prefix."paystack_logs
		  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
		";
		$DbExt->qry($stmt);	
		
		$stmt="				
		CREATE TABLE IF NOT EXISTS ".$table_prefix."paystack_webhook (
		  `id` int(11) NOT NULL,
		  `code` int(1) NOT NULL DEFAULT '0',
		  `message` varchar(255) NOT NULL DEFAULT '',
		  `receive_data` text,
		  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  `ip_address` varchar(50) NOT NULL DEFAULT ''
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
				
		  ALTER TABLE ".$table_prefix."paystack_webhook
		  ADD PRIMARY KEY (`id`);
		  
		  ALTER TABLE ".$table_prefix."paystack_webhook
		  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
		";
		$DbExt->qry($stmt);			
		
		
		/*VIEW TABLES */
		$stmt="
		Create OR replace view ".$table_prefix."driver_task_view as
		SELECT a.*,
		concat(b.first_name,' ',b.last_name) as driver_name,
		b.device_id,
		b.phone as driver_phone,
		b.email as driver_email,
		b.device_platform,
		b.enabled_push,
		b.location_lat as driver_location_lat,
		b.location_lng as driver_location_lng,
		b.last_login as driver_last_login ,
		b.transport_type_id as transport_type ,
		e.team_name
			
		FROM
		".$table_prefix."driver_task a
				
		LEFT JOIN ".$table_prefix."driver b
		ON
		b.driver_id=a.driver_id
		
		left join ".$table_prefix."driver_team e
		ON 
		e.team_id=a.team_id
		";
		
		echo "Updating driver_task_view";
		$DbExt->qry($stmt);
		
		
		$stmt="		
		Create OR replace view ".$table_prefix."view_task_history as
		select 
		a.id as history_id,
		a.task_id,
		DATE_FORMAT(a.date_created,'%Y%m%d%H%i%S') as date_created ,
		b.customer_id		
		
		FROM 
		".$table_prefix."task_history a
		left join ".$table_prefix."driver_task  b
		On
		a.task_id = b.task_id
		";
		echo "Updating view_task_history";
		$DbExt->qry($stmt);
		
		echo "<br/><br/><b>(FINISH)</b><br/>";  		
		
		?>
		<br/>
		<a href="<?php echo Yii::app()->createUrl("admin/")?>">
		 <?php echo AdminFunctions::t("Update done click here to go back")?>
		</a>
		<?php
		
	} /*end index*/
	
	public function addIndex($table='',$index_name='')
	{
		$DbExt=new DbExt;
		$prefix=Yii::app()->db->tablePrefix;		
		
		$table=$prefix.$table;
		
		$stmt="
		SHOW INDEX FROM $table
		";		
		$found=false;
		if ( $res=$DbExt->rst($stmt)){
			foreach ($res as $val) {				
				if ( $val['Key_name']==$index_name){
					$found=true;
					break;
				}
			}
		} 
		
		if ($found==false){
			echo "create index<br>";
			$stmt_index="ALTER TABLE $table ADD INDEX ( $index_name ) ";
			//dump($stmt_index);
			$DbExt->qry($stmt_index);
			echo "Creating Index $index_name on $table <br/>";		
            echo "(Done)<br/>";		
		} else echo 'index exist<br>';
	}
	
	public function alterTable($table='',$new_field='')
	{
		$DbExt=new DbExt;
		$prefix=Yii::app()->db->tablePrefix;		
		$existing_field=array();
		if ( $res = $this->checkTableStructure($table)){
			foreach ($res as $val) {								
				$existing_field[$val['Field']]=$val['Field'];
			}			
			foreach ($new_field as $key_new=>$val_new) {				
				if (!in_array($key_new,$existing_field)){
					echo "Creating field $key_new <br/>";
					$stmt_alter="ALTER TABLE ".$prefix."$table ADD $key_new ".$new_field[$key_new];
					//dump($stmt_alter);
				    if ($DbExt->qry($stmt_alter)){
					   echo "(Done)<br/>";
				   } else echo "(Failed)<br/>";
				} else echo "Field $key_new already exist<br/>";
			}
		}
	}	
	
	 public function checkTableStructure($table_name='')
    {
    	$db_ext=new DbExt;
    	$stmt=" SHOW COLUMNS FROM {{{$table_name}}}";	    	
    	if ($res=$db_ext->rst($stmt)){    		
    		return $res;
    	}
    	return false;    
    }      
    
    public function actionstructure()
    {
    	require_once 'Functions.php';
    	$path=Yii::getPathOfAlias('webroot')."/protected";
        require_once($path.'/config/table_structure_update.php');
        dump($stmt);       
        $DbExt->qry($stmt);
        
        ?>
		<br/>
		<a href="<?php echo Yii::app()->createUrl("admin/")?>">
		 <?php echo AdminFunctions::t("Update done click here to go back")?>
		</a>
		<?php
    }
	
} /*end class*/