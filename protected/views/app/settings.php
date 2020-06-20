
<div id="layout_1">
<?php 
$this->renderPartial('/tpl/layout1_top',array(   
));
?> 
</div> <!--layout_1-->


<div class="parent-wrapper">

 <div class="content_1 white">   
   <?php 
   $this->renderPartial('/tpl/menu',array(   
   ));
   ?>
 </div> <!--content_1-->
 
 <div class="content_main">
 
  <div class="nav_option">
      <div class="row">
        <div class="col-md-6 ">
         <b><?php echo t("Settings")?></b>
        </div> <!--col-->
        <div class="col-md-6  text-right">
                     
        </div> <!--col-->
      </div> <!--row-->
   </div> <!--nav_option-->
   
   <div class="inner">
   
   <ul class="nav nav-tabs">
	  <li class="active"><a data-toggle="tab" href="#gen_settings"><?php echo t("General Settings")?></a></li>
	  <li><a data-toggle="tab" href="#app_settings"><?php echo t("App Settings")?></a></li>
	  <li><a data-toggle="tab" href="#localize_calendar"><?php echo t("Localize Calendar")?></a></li>
	  <li><a data-toggle="tab" href="#tracking_options"><?php echo t("Agents Tracking Options")?></a></li>
	  <li><a data-toggle="tab" href="#critical_option"><?php echo t("Task Critical Options")?></a></li>
	  <li><a data-toggle="tab" href="#map_settings"><?php echo t("Map Settings")?></a></li>
  </ul>
	
  <form id="frm" class="frm form-horizontal">
	 <?php echo CHtml::hiddenField('action','generalSettings')?>
	 <?php echo CHtml::hiddenField('driver_company_logo', getOption( Driver::getUserId(), 'driver_company_logo') )?>
	 <?php echo CHtml::hiddenField('action_name',$action_name)?>
	 
	<div class="tab-content">
	  <div id="gen_settings" class="tab-pane fade in active">
	  
	  
	  <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Logo")?></label>	    
	    <div class="col-sm-6">
	     
	       <div class="company-logo" id="company-logo">	    
		    <?php if($logo_url=Driver::getCompanyLogoUrl( Driver::getUserId())):?>
		      <img src="<?php echo $logo_url;?>" />
		    <?php else :?>
	        <p><?php echo t("Upload your logo")?></p>
	        <?php endif;?>
	       </div>    
	     
	    </div>
	  </div>
	    
	    <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Timezone")?></label>
	    <div class="col-sm-6">
	      <?php echo CHtml::dropDownList('customer_timezone',
	        getOption( Driver::getUserId(), 'customer_timezone'),
	        (array)AdminFunctions::timeZone()
	        )?>
	        <p class="text-muted top5">
	        <?php echo Yii::app()->timeZone;?>
	        <?php echo t("Current Date/Time")." ".AdminFunctions::dateNow()?>
	        </p>      
	    </div>	     
	  </div>	  	 
	  
	  <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Change Language")?></label>
	    <div class="col-sm-6">
	      <?php echo CHtml::dropDownList('change_language',
	        '',
	        (array)$language_list
	        )?>	        
	    </div>	     
	  </div>	   
	 
     <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Send Push only to online driver")?></label>
	    <div class="col-sm-6">
	      <?php
	      echo CHtml::checkBox('driver_send_push_to_online',
	      getOption( Driver::getUserId(), 'driver_send_push_to_online')==1?true:false,array(
	        'class'=>"switch-boostrap"
	      ))
	      ?>	
	      <p class="text-muted top5">
	      <?php echo Driver::t("Send push notification only to online drivers when assigning task")?>.
	      </p>      
	    </div>
	  </div>	  	 
	   
	  <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Disabled Contact List in AddTask")?></label>
	    <div class="col-sm-6">
	      <?php
	      echo CHtml::checkBox('driver_disabled_contacts_task',
	      getOption( Driver::getUserId(), 'driver_disabled_contacts_task')==1?true:false,array(
	        'class'=>"switch-boostrap"
	      ))
	      ?>	      
	    </div>
	  </div>	    
	  
	  </div>
	  <!-- gen_settings-->
	  
	  <div id="app_settings" class="tab-pane fade">
	  
	     <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Enabled Notes")?></label>
	    <div class="col-sm-6">
	      <?php
	      echo CHtml::checkBox('driver_enabled_notes',
	      getOption( Driver::getUserId(), 'driver_enabled_notes')==1?true:false,array(
	        'class'=>"switch-boostrap"
	      ))
	      ?>	      
	    </div>
	  </div>	  
	  
	  <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Enabled Signature")?></label>
	    <div class="col-sm-6">
	      <?php
	      echo CHtml::checkBox('driver_enabled_signature',
	      getOption( Driver::getUserId(), 'driver_enabled_signature')==1?true:false,array(
	        'class'=>"switch-boostrap"
	      ))
	      ?>	      
	    </div>
	  </div>	   
	  
	  <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Enabled Add Photo/Take Picture")?></label>
	    <div class="col-sm-6">
	      <?php
	      echo CHtml::checkBox('driver_enabled_photo',
	      getOption( Driver::getUserId(), 'driver_enabled_photo')==1?true:false,array(
	        'class'=>"switch-boostrap"
	      ))
	      ?>	      
	    </div>
	  </div>	  
	  
	  <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Enabled Resize Picture")?></label>
	    <div class="col-sm-6">
	      <?php
	      echo CHtml::checkBox('app_enabled_resize_pic',
	      getOption( Driver::getUserId(), 'app_enabled_resize_pic')==1?true:false,array(
	        'class'=>"switch-boostrap"
	      ))
	      ?>	      
	    </div>
	  </div>	  
	  	  
	  
	  <div class="form-group">
	    <label class="col-sm-2 control-label">
	    <?php echo Driver::t("Resize picture")?>	    
	    </label>
	    <div class="col-sm-3">
	      <?php
	      echo CHtml::textField('app_resize_width',
	      getOption( Driver::getUserId(), 'app_resize_width'),array(
	        'class'=>"numeric_only",
	        'placeholder'=>t("Width")
	      ));
	      ?>	
	      <p class="small-font top10"><?php echo t("resize picture during taking picture in the app")?></p>      	    
	    </div>
	    <div class="col-sm-3">
	      <?php
	      echo CHtml::textField('app_resize_height',
	      getOption( Driver::getUserId(), 'app_resize_height'),array(
	        'class'=>"numeric_only",
	        'placeholder'=>t("Height")
	      ));
	      ?>	      	    
	    </div>
	  </div>	   
	  
	 <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Device Vibration")?></label>
	    <div class="col-sm-3">
	      <?php
	      echo CHtml::textField('driver_device_vibration',
	      getOption( Driver::getUserId(), 'driver_device_vibration'),array(
	        'class'=>"numeric_only"
	      ));
	      ?>	      
	      <p class="small-font top10"><?php echo t("Default is 3000 Vibrate for 3 seconds")?></p>
	    </div>
	  </div>	     
	  
	  </div>
	  <!-- app_settings-->
	  
	  <div id="localize_calendar" class="tab-pane fade">
	  
	   <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Language")?></label>
	    <div class="col-sm-6">
	      <?php
	      echo CHtml::dropDownList('calendar_language',getOption( Driver::getUserId(), 'calendar_language'),
	      Driver::calendarLocalLang())
	      ?>		      
	    </div>
	  </div>	  
	  
	  </div>
	  <!--localize_calendar-->
	  
	  <div id="tracking_options" class="tab-pane fade">
	  
	   <p class="small-font top10" style="margin-bottom:10px;">
	      <?php echo t("Determine the agents online and offline status")?>
	  </p>	     
	  
	  <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Tracking Options 1")?></label>
	    <div class="col-sm-6">
	      <?php
	      $tracking_options=getOption( Driver::getUserId(), 'driver_tracking_options');
	      if(empty($tracking_options)){
	      	 $tracking_options=1;
	      }	      
	      echo CHtml::radioButton('driver_tracking_options',
	      $tracking_options==1?true:false
	      ,array(
	        'value'=>1
	      ));
	      ?>
	      <p class="small-font top10">
	      <?php echo t("This options will set the agent online when the device sents location to server")?>
	      </p>	     
	      
	    </div>
	  </div>	  
	  
	  <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Tracking Options 2")?></label>
	    <div class="col-sm-6">
	      <?php
	      echo CHtml::radioButton('driver_tracking_options',
	      $tracking_options==2?true:false
	      ,array(
	        'value'=>2
	      ));
	      ?>	

	      <p class="small-font top10">
	      <?php echo t("This options will set the agents only offline when they logout to the app or set to off duty and idle for more than 30min")?>
	      </p>	     
	            
	    </div>
	  </div>	
	  
	  
	  <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Records Agents Location")?></label>
	    <div class="col-sm-6">
	      <?php
	      echo CHtml::checkBox('agents_record_track_Location',
	      getOption( Driver::getUserId(), 'agents_record_track_Location')==1?true:false
	      ,array(
	        'value'=>1,
	        'class'=>"switch-boostrap"
	      ));
	      ?>	

	      <p class="small-font top10">
	      <?php echo t("this will save agents locations for later review")?>
	      </p>	     
	            
	    </div>
	  </div>	
	  
	  <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Disabled Background Tracking")?></label>
	    <div class="col-sm-6">
	      <?php
	      echo CHtml::checkBox('app_disabled_bg_tracking',
	      getOption( Driver::getUserId(), 'app_disabled_bg_tracking')==1?true:false
	      ,array(
	        'value'=>1,
	        'class'=>"switch-boostrap"
	      ));
	      ?>	

	      <p class="small-font top10">
	      <?php echo t("this options will not track your agents when the app is running in background")?>
	      </p>	     
	            
	    </div>
	  </div>	 
	  
	  
	 <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Track Interval")?></label>
	    <div class="col-sm-2">
	      <?php
	      echo CHtml::textField('app_track_interval',
	      getOption( Driver::getUserId(), 'app_track_interval'),array(
	        'class'=>"numeric_only"
	      ));
	      ?>	      
	      <p class="small-font top10"><?php echo t("in seconds, Default is 8 seconds");?></p>
	    </div>
	  </div> 
	  
	  
	  
	  </div>
	  <!--tracking_options-->
	  
	  <div id="critical_option" class="tab-pane fade">
	  
	    <p class="small-font top10" style="margin-bottom:10px;">
	      <?php echo t("Set critical background to the task when its unassigned after a set of minutes")?>
	  </p>	     
	  
	  <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Enabled")?></label>
	    <div class="col-sm-6">
	      <?php
	      echo CHtml::checkBox('enabled_critical_task',
	      getOption( Driver::getUserId(), 'enabled_critical_task')==1?true:false,array(
	        'class'=>"switch-boostrap"
	      ))
	      ?>	      
	    </div>
	  </div>	  
	  
	  <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Minutes")?></label>
	    <div class="col-sm-2">
	      <?php
	      echo CHtml::textField('critical_minutes',
	      getOption( Driver::getUserId(), 'critical_minutes'),array(
	        'class'=>"numeric_only"
	      ));
	      ?>	      
	      <p class="small-font top10"><?php echo t("Default is 5 minutes")?></p>
	    </div>
	  </div>
	  
	  </div>
	  <!--critical_option-->
	  
	  <div id="map_settings" class="tab-pane fade">
	  
	    <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Default Map Country")?></label>
	    <div class="col-sm-6">	      
	      <?php
	      $drv_default_location=getOption( Driver::getUserId() , 'drv_default_location');
	      echo CHtml::dropDownList('drv_default_location',
	      !empty($drv_default_location)?$drv_default_location:"US",
	      (array)$country_list,array(
	        'class'=>"form-control"
	      ))
	      ?>
	      <p class="text-muted top5">
	      <?php echo Driver::t("Set the default country to your map")?>
	      </p>
	    </div>
	  </div>	  
	  
	   <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Disabled Activity Tracking")?></label>
	    <div class="col-sm-6">
	      <?php
	      echo CHtml::checkBox('driver_activity_tracking',
	      getOption( Driver::getUserId(), 'driver_activity_tracking')==1?true:false,array(
	        'class'=>"switch-boostrap"
	      ))
	      ?>	      	     
	    </div>
	  </div>	  
	  
	   <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Activity refresh interval")?></label>
	    <div class="col-sm-6">
	      <?php
	      echo CHtml::textField('driver_activity_tracking_interval',	      
	      getOption( Driver::getUserId(), 'driver_activity_tracking_interval'),array(
	        'class'=>"numeric_only",
	        'style'=>"width:100px;"
	      ));
	      ?>	
	      <p><?php echo t("in seconds. default is 15 seconds")?></p>      	     
	    </div>
	  </div>	  
	  
	  <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Auto Geocode Address")?></label>
	    <div class="col-sm-6">
	      <?php
	      echo CHtml::checkBox('driver_auto_geo_address',
	      getOption( Driver::getUserId(), 'driver_auto_geo_address')==1?true:false,array(
	        'class'=>"switch-boostrap"
	      ))
	      ?>	      
	      <p><?php echo t("auto fill address after dragging the marker on map only for google maps")?></p>
	    </div>
	  </div>	  
	  
	   <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Include offline driver on map")?></label>
	    <div class="col-sm-6">
	      <?php
	      echo CHtml::checkBox('driver_include_offline_driver_map',
	      getOption( Driver::getUserId(), 'driver_include_offline_driver_map')==1?true:false,array(
	        'class'=>"switch-boostrap"
	      ))
	      ?>	      
	    </div>
	  </div>	  
	  
	   <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Hide Pickup Task")?></label>
	    <div class="col-sm-6">
	      <?php
	      echo CHtml::checkBox('map_hide_pickup',
	      getOption( Driver::getUserId(), 'map_hide_pickup')==1?true:false,array(
	        'class'=>"switch-boostrap",
	        'value'=>1
	      ))
	      ?>	      
	    </div>
	  </div>	
	  
	  <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Hide Delivery Task")?></label>
	    <div class="col-sm-6">
	      <?php
	      echo CHtml::checkBox('map_hide_delivery',
	      getOption( Driver::getUserId(), 'map_hide_delivery')==1?true:false,array(
	        'class'=>"switch-boostrap",
	        'value'=>1
	      ))
	      ?>	      
	    </div>
	  </div>	
	  
	  <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Hide Successful Task")?></label>
	    <div class="col-sm-6">
	      <?php
	      echo CHtml::checkBox('map_hide_success_task',
	      getOption( Driver::getUserId(), 'map_hide_success_task')==1?true:false,array(
	        'class'=>"switch-boostrap",
	        'value'=>1
	      ))
	      ?>	      
	    </div>
	  </div>	
	  
	  <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Disabled Map Auto Refresh")?></label>
	    <div class="col-sm-6">
	      <?php
	      echo CHtml::checkBox('driver_disabled_auto_refresh',
	      getOption( Driver::getUserId(), 'driver_disabled_auto_refresh')==1?true:false,array(
	        'class'=>"switch-boostrap"
	      ))
	      ?>	      
	    </div>
	  </div>
	  
	 
	  
	    <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("Google Map Style")?></label>
	    <div class="col-sm-6">
	      <?php
	      echo CHtml::textArea('drv_map_style',getOption( Driver::getUserId(),'drv_map_style'),array(
	         'class'=>"form-control",
	         'style'=>"height:250px;"
	      ))
	      ?>
	      <p class="text-muted top5">
	      <?php echo Driver::t("Set the style of your map")?>.
	      <?php echo Driver::t("get it on")?> <a target="_blank" href="https://snazzymaps.com">https://snazzymaps.com</a>
	      <br/>
	      <?php echo Driver::t("leave it empty if if you are unsure")?>.
	      </p>
	    </div>
	  </div>	  
	  
	  </div>
	  <!--map_settings-->
	  
	</div> <!--tab-content-->
	
	 <div class="form-group">	    
	    <div class="col-sm-6">
		  <button type="submit" class="orange-button medium rounded">
		  <?php echo Driver::t("Save")?>
		  </button>
	    </div>	 
	  </div>
	  
  </form>
   
   
   </div> <!--inner-->
 
 </div> <!--content_main-->
 
</div> <!--parent-wrapper-->