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
*{font-size: 16pt;}
</style>
</head>
<body>
<h1>Session</h1>
<div class="main_container">
<form name="func_form" method="POST">
<input type="hidden" name="func">
</form>


<div class="tile"
 onclick="document.func_form.func.value=''; document.func_form.submit();">
Reload page
</div>

<div class="tile"
 onclick="document.func_form.func.value='destroy'; document.func_form.submit();">
 Destroy PHP part of session
</div>



<div class="container" style=" width: 95%">
<pre>
<?php

$options=[
   "save_path"=>"{$_SERVER['DOCUMENT_ROOT']}/var" //Seems to be ignored!!
  ,"save_handler"=>"files"
  ,"serialize_handler"=>"php"
  ,"use_cookies" => false
  ,"session.cache_limiter"=>"public"	
  ,"session.cache_expire"=>"180"
  ,"gc_probability"=>0
];
session_start($options);
printf("Function: %s\n",@$_POST['func']);

if(@$_POST['func']=='destroy'){
  $_SESSION = [];
  session_destroy();
  session_id(preg_replace("/[^a-zA-Z0-9,-]/",'',$_COOKIE['SID']));
  echo "Sessions gone now";
  exit;
}
echo "<hr><h1>PHP session:</h1>",print_r($_SESSION,true);

echo "<hr><h1>Server session:</h1>",print_r($_SERVER['SESSION'],true);
echo "<hr><h1>Cookie::</h1>",print_r($_COOKIE,true);
printf("<hr><h1>session_id():</h1> %s",session_id());


if(isset($_SESSION['counter'])) 
  $_SESSION['counter']++;
else
  $_SESSION['counter']=1;

?>

</pre>
</div>
</div>
</body>
</html>
