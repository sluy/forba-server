
<div class="modal fade new-contact" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
    
      <div class="modal-header">
         <button aria-label="Close" data-dismiss="modal" class="close" type="button">
           <span aria-hidden="true"><i class="ion-android-close"></i></span>
         </button> 
        <h4 id="mySmallModalLabel" class="modal-title">
        <?php echo t("Add Contact")?>
        </h4> 
      </div>  
      
      <div class="modal-body">
      
      <form id="frm" class="frm" method="POST" onsubmit="return false;">
      <?php echo CHtml::hiddenField('action','addContact')?>
      <?php echo CHtml::hiddenField('id','')?>
      <?php echo CHtml::hiddenField('addresss_lat','')?>
      <?php echo CHtml::hiddenField('addresss_lng','')?>
      <div class="inner">
      
      <div class="row">
       <div class="col-md-6">
	        <div class="row">
	          <div class="col-md-12">
	            <?php echo CHtml::textField('fullname','',array(
	              'placeholder'=>t("Name"),
	              'required'=>true
	            ))?>
	          </div>          
	        </div> <!--row-->        
	        
	        <div class="row top10">
	          <div class="col-md-6 ">
	            <?php echo CHtml::textField('email','',array(
	              'placeholder'=>t("Email"),
	              //'data-validation'=>'email'
	             // 'required'=>true
	            ))?>
	          </div>
	          <div class="col-md-6 ">
	            <?php echo CHtml::textField('phone','',array(
	              //'placeholder'=>t("Phone"),
	              'class'=>"mobile_inputs",
	              'required'=>true,
	              'maxlength'=>15
	            ))?>
	          </div>
	        </div> <!--row-->        
	                 
	        
	       <div class="row top10">
	          <div class="col-md-12">
	            <?php 
	            $map_provider = getOptionA('map_provider'); 
	            if($map_provider=="google"){
		            echo CHtml::textField('address','',array(
		              'placeholder'=>t("Address"),
		              'required'=>true,
		              'class'=>"contact_address"
		            ));
	            } elseif ( $map_provider =="mapbox"){
             	   echo "<div class=\"mapbox_geocoder_wrap\" id=\"mapbox_delivery_address\"></div>";	            	
	            }	            
	            ?>
	          </div>          
	        </div> <!--row-->    
	        
	            
	        
	        <div class="row top20">
	        <div class="col-md-12">
	        <p><?php echo t("Status")?></p>
	        <?php 
	        echo CHtml::dropDownList('status','',Driver::driverStatus(),array(
	         'required'=>true
	        ));
	        ?>
	        </div>
	        </div>

	    </div> <!--col-->    
	    
	    <div class="col-md-6">
	      <div class="map-contact-wrap">
	      
	       <div class="map_task_loader2">
                <div class="inner">
                  <div class="ploader"></div>
                </div>
            </div> <!--map_task_loader-->   
	      
	         <div class="map_contact" id="map_contact"></div>
	      </div> <!--map-contact-wrap-->
	    </div> <!--col-->
	        
        
        </div> <!--row-->
         
        
        <div class="row top20">
        <div class="col-md-5 col-md-offset-7">
        <button type="submit" class="orange-button medium rounded contact-submit"><?php echo t("Submit")?></button>
        <button type="button" data-id=".new-contact" 
            class="close-modal green-button medium rounded"><?php echo t("Cancel")?></button>
        </div>
        </div>        
        
        
      </div> <!--inner-->  
      </form>  
      
      </div> <!--body-->
    
    </div>
  </div>
</div>