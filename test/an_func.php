<!DOCTYPE HTML>
<html>
<head> 
<meta charset="utf-8" />
<title>Test page</title>
<link rel="shortcut icon" href="/favicon.ico" >
<link rel="icon" href="/favicon_big.ico" type="image/ico" >
<link rel="stylesheet" type="text/css" href="/theme/theme.css" />

<style>
</style>

</head>
<body>
<h1>Test page</h1>
<pre>
<?php
define('_DEV_DEBUG',true);

function plut($script){
  require $script;
  $do_thing("data\n");
};

plut("div/foo.php");
plut("div/bar.php");

// Direct call
$f=function($var){
 echo "$var\n";
};

$f("testing");


/*

interface thing{
    public function do_thing($d);
}

class plut implements thing{
  public function __construct($script){
    echo "Including $script\n";
    require $script;
    do_thing("data"); 
  }
  thing::do_thing();

}
$p=new plut("foo.php");
$p=new plut("bar.php");

function proxyExternalFunction($fileName, $functionName, $args, $setupStatements = '') {
  $output = array();
  $command = $setupStatements.";include('".addslashes($fileName)."');echo json_encode(".$functionName."(";
  foreach ($args as $arg) {
    $command .= "json_decode('".json_encode($arg)."',true),";
  }
  if (count($args) > 0) {
    $command[strlen($command)-1] = ")";//end of $functionName
  }
  $command .= ");";//end of json_encode
  $command = "php -r ".escapeshellarg($command);

  exec($command, $output);
  $output = json_decode($output,true);
}


/*

function plut($script){
  require $script;
  do_thing("data");
}

plut("foo.php");
plut("bar.php");
*/
?>
</pre>
</body>
</html>
