
<div id="layout_1">
<?php 
$this->renderPartial('/tpl/layout1_top',array(   
));
?> 
</div> <!--layout_1-->

<div class="parent-wrapper task-list-area">

 <div class="content_1 white">   
   <?php 
   $this->renderPartial('/tpl/menu',array(   
   ));
   ?>
 </div> <!--content_1-->
 
 <div class="content_main">

   <div class="nav_option">
      <div class="row">
        <div class="col-md-6 border">
         <b><?php echo t("Send Bulk Push")?></b>
        </div> <!--col-->        
      </div> <!--row-->
   </div> <!--nav_option-->
  
   <div class="inner">
   <form id="frm" class="frm form-horizontal">
   <?php echo CHtml::hiddenField('action','sendBulkPush')?>
   
      <div class="row">
          <div class="col-md-2">
            <?php echo t("Select Team")?>
          </div>
		  <div class="col-md-5 ">
		    <?php
		    echo CHtml::dropDownList('team_id','', (array) $team_list ,array(
		      'class'=>"team_id",
		      'data-validation'=>"required"
		    ));
		    ?>
		  </div>		  
       </div> <!--row-->     
       
      <div class="row top20">
          <div class="col-md-2">
            <?php echo t("Push Title")?>
          </div>
		  <div class="col-md-5">
		    <?php
		    echo CHtml::textField('push_title','',array(
		      'data-validation'=>"required"
		    ));
		    ?>
		  </div>		  
       </div> <!--row-->      
       
      <div class="row top20">
          <div class="col-md-2">
            <?php echo t("Push Message")?>
          </div>
		  <div class="col-md-5">
		    <?php
		    echo CHtml::textArea('push_message','',array(
		      'style'=>"height:100px;",
		      'data-validation'=>"required"
		    ));
		    ?>
		  </div>		  
       </div> <!--row-->       
       
   
       <div class="row top20">
        <div class="col-md-2"></div> 
        <div class="col-md-5">
        <button type="submit" class="orange-button medium rounded"><?php echo t("Submit")?></button>    
       </div>  
      </div>   
       
   </form>
   </div>
 
 </div> <!--content_2-->

</div> <!--parent-wrapper-->