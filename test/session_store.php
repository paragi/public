<!DOCTYPE HTML>
<html>
<head> 
<meta charset="utf-8" />
<title>Test page</title>
<link rel="shortcut icon" href="/favicon.ico" >
<link rel="icon" href="/favicon_big.ico" type="image/ico" >
<link rel="stylesheet" type="text/css" href="/theme/theme.css" />
<style>
td:hover{
	background-color:rgba(240,217,136,0.2);
	cursor:pointer;
}
</style>
 </head>
<body>
<div class="container" style=" width: 95%">
<h1>Test</h1>
<pre>
<?php

// Start PHP session
$options=[
   "save_path"=>$_SERVER['DOCUMENT_ROOT']."/var" 
//  ,"save_handler"=>"files"
  ,"name"=>"SID"
  ,"serialize_handler"=>"php"
  ,"use_cookies" => false
  ,"session.cache_limiter"=>"public"	
  ,"session.cache_expire"=>"180"
 // ,"gc_probability"=>0
];
ini_set('session.save_path',realpath($_SERVER['DOCUMENT_ROOT']) . '/var');
ini_set('session.use_cookies', false);


/*============================================================================*\
  Install datastore session store
\*============================================================================*/
include "datastore.php";
$handler = new datastore\MySessionHandler();
session_set_save_handler($handler, true);
session_start($options);

$datastore=new datastore\datastore;

$datastore->warning("Warning test");
$datastore->info("Info test");
$datastore->debug("Debug test");
$datastore->error("Error test");
$datastore->fatal("Fatal test");
$datastore->log("Log test");
$datastore->event("Event test");





//register_shutdown_function('session_write_close');


//session_start();
$_SESSION['test']="test";
session_write_close();



print_r(glob("var/*"));



?>
</pre>
</div>
</body>
</html>
