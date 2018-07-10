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
<h1>PHP Globals</h1>
<div class="container" style=" width: 95%">
<pre>
<?php
/*================================================================================ *\
\*================================================================================ */
spl_autoload_register(function ($class_name) {
  echo "including: ","{$_SERVER['DOCUMENT_ROOT']}/" .strtr($class_name,'\\','/') . '.php';
    include "{$_SERVER['DOCUMENT_ROOT']}/" .strtr($class_name,'\\','/') . '.php';
});

include "{$_SERVER['DOCUMENT_ROOT']}/device/dummy.php";

$cmd = ["action" => "set 123"];
     $operation = explode(" ",$cmd['action'],2);
      if(empty($operation[1])) $operation[1]=' ';

$device=new device\dummy;
$device->initialize();
echo "List:";
print_r($device->handler("list"));
echo "Get:";
print_r($device->handler("get",'1'));

echo "Set:";
print_r($device->handler("set 217",'1'));

echo "Toggle:";
print_r($device->handler("toggle",'1'));

echo "diagnostic:";
print_r($device->handler("diagnostic",'1'));
""

/*  
print_r($device->handler("set off","gpio18"));

$device=new device\dummy;
$device->initialize();
print_r($device->handler("get","gpio190"));
print_r($device->handler("set on","gpio18"));

$device=new device\rpi\gpio_pin;
$device->initialize();
print_r($device->handler("get","gpio190"));
print_r($device->handler("set on","gpio18"));

$device=new device\rpi\gpio_pin;
$device->initialize();
print_r($device->handler("get","gpio190"));
print_r($device->handler("set off","gpio18"));

*/
?>

</pre>
</div>
</body>
</html>
