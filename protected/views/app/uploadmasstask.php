
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
        <div class="col-md-6 border">
         <b><?php echo t("Upload Tasks")?></b>
        </div> <!--col-->        
      </div> <!--row-->
   </div> <!--nav_option-->
  
   <div class="inner">
 
   <?php if (isset($_POST) && $_SERVER['REQUEST_METHOD']=='POST'):?>
      <?php if (!empty($msg)):?>
        <p class="text-danger"><?php echo $msg?></p>
      <?php else :?>
         <?php //dump($error);dump($data);?>
         <?php  if (is_array($data) && count($data)>=1): ?>
            <?php 
            $task_remaining=Driver::getTaskRemaining2( Driver::getUserId(),Driver::getPlanID());
            /*dump($task_remaining);
            echo count($data);*/
            
            if ( $task_remaining>=count($data)){          
	            $total_inserted=0;
	            $db=new DbExt;
	            foreach ($data as $val) {            	
	            	if ($error==0){
	            		if(Driver::planCheckCAnAddTask( Driver::getUserId(),Driver::getPlanID() )){
	            		   $params=$val['data'];            		   
	            		   if($db->insertData("{{driver_task}}",$params)){
	            		   	   $total_inserted++;
	            		   }
	            		}
	            	}
	            	foreach ($val['line'] as $val2) {
	            		echo '<p class="text-muted">'.$val2.'</p>';
	            	}
	            }
	            if ($error>0){
	            	echo '<p class="text-danger">'.t("CSV not process please fixed the csv issue and try again").'...</p>';
	            } else {
	            	if ($total_inserted>0){
	            	   echo '<p class="text-success">'.t("CSV successfully process").'</p>';            	
	            	   echo '<p class="text-success">'.t("Total records inserted")." : $total_inserted".'</p>';       
	            	}
	            	if(!Driver::planCheckCAnAddTask( Driver::getUserId(),Driver::getPlanID() )){
	            		echo '<p class="text-danger">'.t("Your account has insufficient to insert task").'</p>';       
	            	}
	            }
            } else {
            	echo '<p class="text-danger">'.t("Your account has insufficient to insert task").'</p>';   
            	echo '<p class="text-muted">'.t("Task Remaining"). " : ". $task_remaining .'</p>';   
            	echo '<p class="text-muted">'.t("Total CSV Records"). " : ". count($data) .'</p>';   
            }
            ?>
            <p class="top20">
              <a href="<?php echo Yii::app()->createUrl('/app/uploadmasstask')?>">
              <?php echo t("Click here to go back")?></a>
            </p>
         <?php  else :?>
            <p class="text-danger"><?php echo t("CSV is empty")?></p>
         <?php endif;?>
      <?php endif;?>
   <?php else :?>
   
   <p><?php echo t("Upload your mass task using csv formatted data")?></p>
   
   <form class="form-horizontal" method="post" enctype="multipart/form-data"  >
   
     <div class="form-group">
	    <label class="col-sm-2 control-label"><?php echo Driver::t("CSV")?></label>
	    <div class="col-sm-6">
	      <input type="file" name="file" id="file" />
	    </div>
	  </div>	  	 
       
    <div class="form-group">
       <label class="col-sm-2 control-label"></label>
       <div class="col-md-5">
       <button type="submit" class="orange-button medium rounded"><?php echo t("Submit")?></button>    
    </div>
	</div>	  	

	<?php endif;?>
    	  
   </form> 
  
   <?php if (isset($_POST) && $_SERVER['REQUEST_METHOD']=='POST'):?>
   <?php else :?>
   <p class="top30" style="margin-top:100px;">
     <a href="<?php echo websiteUrl()."/sample.csv"?>">
     <?php echo t("click here to download sample csv format")?>
     </a>
   </p>
   
   <p class="text-muted to20"">
   <span class="text-success"><?php echo t("CSV format")?> : </span>
   <?php echo t("trans_type,task_description,contact_number,email_address,customer_name,delivery_date,delivery_address,task_lat,task_lng")?>
   <br/>
   <span class="text-success"><?php echo t("task_lat and task_lng is optional")?></span>
   </p>
   <?php endif;?>
   
   </div> <!--inner-->
 
 </div> <!--content_2-->

</div> <!--parent-wrapper-->


<?php 
$this->renderPartial('/app/contact-new',array(   
));