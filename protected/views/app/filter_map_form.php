   <form id="frm_map_filter" class="frm_map_filter form-horizontal">
      <?php echo CHtml::hiddenField('action','mapFilterSettings')?>
      
        <div class="form-group">
	    <label class="col-sm-4 control-label"><?php echo Driver::t("Include offline driver on map")?></label>
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
	    <label class="col-sm-4 control-label"><?php echo Driver::t("Hide Pickup Task")?></label>
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
	    <label class="col-sm-4 control-label"><?php echo Driver::t("Hide Delivery Task")?></label>
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
	    <label class="col-sm-4 control-label"><?php echo Driver::t("Hide Successful Task")?></label>
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
	    <label class="col-sm-4 control-label">&nbsp;</label>
	    <div class="col-sm-6">
		  <button type="submit" class="orange-button medium rounded">
		  <?php echo Driver::t("Save")?>
		  </button>
	    </div>	 
	  </div>
      
      </form>
      
<script type="text/javascript">
$(document).ready(function(){
    $(".switch-boostrap").bootstrapSwitch({
    	size:"mini"
    });
	    
	$.validate({ 	
	language : jsLanguageValidator,
	form : '#frm_map_filter',    
	onError : function() {      
	},
	onSuccess : function() { 	           
	  var params= $("#frm_map_filter").serialize();
	  var action = $("#frm_map_filter #action").val();
	  var button = $('#frm_map_filter button[type="submit"]');
	  dump(button);
	  callAjax(action,params,button);
	  return false;
	}  
	});    

});
</script>