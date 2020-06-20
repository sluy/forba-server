
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
         <b><?php echo t("Services")?></b>
        </div> <!--col-->        
      </div> <!--row-->
   </div> <!--nav_option-->
  
   <div class="inner">
 
   <p><?php echo t("Check the services that you offered")?></p>
   
   <form id="frm" class="frm" method="POST" onsubmit="return false;">
   <?php echo CHtml::hiddenField('action','saveServices')?>
   
   <?php 
   $services_selected='';
   if (isset($data['services'])){
   	   $services_selected=!empty($data['services'])?json_decode($data['services']):'';
   }  
   ?>
   
   <?php if (is_array($services) && count($services)>=1):?>
   <div class="row">
   <?php foreach ($services as $val):?>
       <div class="col-sm-3 top20">
         <?php echo CHtml::checkBox("services_id[]",
         in_array($val['services_id'], (array) $services_selected )?true:false
         ,array(
          'value'=>$val['services_id']
         )) ?>
         <b><?php echo $val['sevices_name']?></b>
         
         <?php if (is_array($val['sub']) && count($val['sub'])>=1):?>
         <?php foreach ($val['sub'] as $val2):?>
            <div style="padding-left:20px;">
              <?php echo CHtml::checkBox("services_id[]",
              in_array($val2['services_id'], (array) $services_selected )?true:false
              ,array(
                'value'=>$val2['services_id']
              ))?>
              <?php echo $val2['sevices_name']?>
            </div>
         <?php endforeach;?>
         <?php endif;?>
         
       </div> <!--col-->
   <?php endforeach;?>
   </div>
   <?php endif;?>
   
   
   <div class="row top20">
    <div class="col-md-5">
    <button type="submit" class="orange-button medium rounded"><?php echo t("Save")?></button>    
    </div>   
   
   </form>

   
   </div> <!--inner-->
 
 </div> <!--content_2-->

</div> <!--parent-wrapper-->


<?php 
$this->renderPartial('/app/contact-new',array(   
));