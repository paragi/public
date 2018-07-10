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
<h1>Test USB relay board</h1>
<pre>
<?php
/*
$fn="/dev/usb/lp0";
$data=255;
$bit=1;
for($i=0;$i<3;$i++){
  for($bit=0;$bit<8;$bit++){
    $data=(1<<$bit);
    printf("%d: %X\n",$bit,$data);
    print_r(write_file($fn,"$data"));
    
    usleep(200000);
  }
}
exit;

echo "writing $data";
file_put_contents($fn,chr($data));
write_file($fn,0);
usleep(200000);
write_file($fn,0xff);
exit;
*/
/*============================================================================*\
  Class file loader
\*============================================================================*/
spl_autoload_register(function ($class_name) {
global $_SERVER;
  echo "loading: {$_SERVER['DOCUMENT_ROOT']}/" .strtr($class_name,'\\','/') . '.php';
  include "{$_SERVER['DOCUMENT_ROOT']}/" .strtr($class_name,'\\','/') . '.php';
});


include "device/usb/generic_bit.php";
$device=new device\usb\generic_bit;

echo "Initialize:\n";
$device->initialize();

echo "list:\n";
print_r($device->handler("list"));

$unit_id="1-1.3:067b:2305";

echo "\nTest using unit ID: $unit_id\n";

$bit=1;
for($i=0;$i<3;$i++){
  for($bit=1;$bit<9;$bit++){
    $opr=($i%2!=1?"set":"reset");
    echo "$opr-$bit\n";
    print_r($device->handler("$opr $bit",$unit_id));
    
    usleep(500000);
  }
}

echo "\n Test gpio pins";

$device=new device\rpi\gpio_pin;
$device->initialize();
print_r($device->handler("get","gpio190"));
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
?>
</pre>
</div>
</body>
</html>
