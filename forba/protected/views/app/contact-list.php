
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
         <b><?php echo t("Contacts")?></b>
        </div> <!--col-->
        <div class="col-md-6 border text-right">
            
           <a class="green-button left rounded" href="javascript:;"
           data-toggle="modal" data-target=".new-contact" >
           <?php echo t("Add Contact")?>
           </a>
           <a class="orange-button left rounded refresh-table" href="javascript:;"><?php echo t("Refresh")?></a>
           
           <a class="green-button left rounded" href="<?php echo Yii::app()->createUrl('/app/export_contact')?>">
           <?php echo t("Export")?>
           </a>           
         
        </div> <!--col-->
      </div> <!--row-->
   </div> <!--nav_option-->
  
   <div class="inner">
   <form id="frm_table" class="frm_table">
   <?php echo CHtml::hiddenField('action','contactList')?>
   <table id="table_list" class="table table-hover">
   <thead>
    <tr>
      <th width="5%"><?php echo t("ID")?></th>            
      <th width="10%"><?php echo t("Name")?></th>
      <th width="10%"><?php echo t("Email")?></th>
      <th width="10%"><?php echo t("Phone")?></th>      
      <th width="10%"><?php echo t("Address")?></th>
      <th width="10%"><?php echo t("Status")?></th>
      <!--<th width="10%"><?php echo t("Actions")?></th>-->
    </tr>
    </thead>
    <tbody>     
    </tbody>     
   </table>
   </form>
   </div>
 
 </div> <!--content_2-->

</div> <!--parent-wrapper-->


<?php 
$this->renderPartial('/app/contact-new',array(   
));