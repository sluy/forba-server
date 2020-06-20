<?php
return array(
	'name'=>'Forba',
	
	'defaultController'=>'front',
		
	'import'=>array(
		'application.models.*',
		'application.models.admin.*',
		'application.components.*',
		'application.vendor.*',		
	),
		
	'language'=>'en',		
	
	'params'=>array(
	   //'encryption_type'=>"yii",
	   'encryption_type'=>"md5",
	),
				
	'components'=>array(		   
	    'urlManager'=>array(
	        'class' => 'UrlManager',
		    'urlFormat'=>'path',
		    //'urlSuffix'=>'.html',
		    'showScriptName'=>false,	
		    'caseSensitive'=>false,     	    
		    'rules'=>array(
		       '/' => array('/front/index/'),		 
		       '/app/' => array('/app/index/'),		       		       
		       'admin/' => "admin/index",
		       'api/' => "api/index",
		       'install/' => "install/index",		       
		       '<_c:(front)>' => '<_c>/index',		       		       
		       'lang/*'=>'front/index',	
		       '<lang:\w+>/<controller:\w+>/<action:\w+>/'=>'<controller>/<action>',		       		       	      
		       '<action:[\w\-]+>' => 'front/<action>',	       		       		       
		       '<controller:\w+>/<action:\w+>/<id:\d+>'=>'<controller>/<action>',
		       '<controller:\w+>/<action:\w+>'=>'<controller>/<action>',			       
		    )		    
		),
				
		'db'=>array(	        
		    'class'            => 'CDbConnection' ,
			'connectionString' => 'mysql:host=localhost;dbname=forba_forbadb',
			'emulatePrepare'   => true,
			'username'         => 'forba_user',
			'password'         => '~*@6koGqi~YN',
			'charset'          => 'utf8',
			'tablePrefix'      => 'kt_',
	    ),	 
					    	   
	    'functions'=> array(
	       'class'=>'Functions'	       
	    ),
	    'validator'=>array(
	       'class'=>'Validator'
	    )
	),
);