<?php
/*============================================================================*\
  Retreive local IP for a registred device, and redirect browser to local device

  (c) Paragi 2017, Simon Riget
\*============================================================================*/
require "rocket-store.php";
$rsdb = new \Paragi\RocketStore([
    "data_storage_area" => "data"
  , "data_format" => RS_FORMAT_JSON
]);

/*============================================================================*\
  Find out wich state we are at, and set headers arcordingly
\*============================================================================*/
do{
  $check = "preset state"; // For testing
  if(@$_REQUEST['fail']){
    $check = $_REQUEST['fail'];
    break;
  }

  $check = "IP address: " . $_SERVER['REMOTE_ADDR'];
  if(!filter_var($_SERVER['REMOTE_ADDR'],FILTER_VALIDATE_IP)) break;

  $reply = $rsdb->get("connect",$_SERVER['REMOTE_ADDR']);

  // Set timeout
  if($reply['count'] != 1){
    session_start();

    $check = "no record";
    if(empty($_SESSION['start'])){
      $_SESSION['start'] = time();
      break;

    }else{
      $check = "wait";
      if(time() - $_SESSION['start'] < 180)
        break;

      $check = "timeout";
      unset($_SESSION['start']);
      break;
    }
  }else{
    $check = "old record";
    $record = reset($reply['result']);
    if((time() - $record['time']) >1800 )
      break;
  }

  $check = 'ok';
}while(false);

/*============================================================================*\
  Set HTTP headers
\*============================================================================*/
switch($check){
  case 'ok':
    header("Location: http://{$record["local_ip"]}");
    break;

  case 'wait':
  case 'no record':
  case 'old record';
    header("Refresh: 10");
    break;
}

/*============================================================================*\
  Set HTML heading
\*============================================================================*/
?><!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8" />
<title>Connect SmartCore </title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" href="theme.css" />
<style>
.wait-logo{
  display: block;
  background: url(wait.gif) no-repeat center center;
  background-size: contain;
  width: var(--module-size);
  height: var(--module-size);
  position: fixed;
  left:0;
  right:0;
  bottom: 10%;
  margin:0px auto;
}
</style>
</head>
<body>
<div class="main_container">
<div style="white-space: pre-wrap; text-align: center; width:100%">
<?php

/*============================================================================*\
  Display page
\*============================================================================*/
// echo "State: $check - timeout display: ", time() - @$_SESSION['start'] , "timeout state: ",  time() - @$record["time"],"\n";

switch($check){
  case 'ok':
    echo "Transfering to your local SmartCore ({$record['local_ip']})\n";
    break;

  case 'wait':
  case 'no record':
  case 'old record':
    echo <<<EOT
Hi, welcome to Smart Space.

Please <b>turn on</b> your SmartCore, and make sure it has <b>Internet access</b>.

Need <a href="http://futu-rum.com/wiki/index.php/Connecting">help</a>?

<div class="wait-logo"></div>

EOT;
    break;

  default:
    echo "Your device is not registering.\n\n";

  // retry tile
  echo "<div class=\"tile\" style=\"background-image: url(reload.png)\""
    . " onclick=\"window.location=''\"></div>";

  // Wiki tile
  echo "<div class=\"tile help \" style=\"background-image: url(wiki.png)\""
    . " onclick=\"window.location='http://futu-rum.com/wiki/index.php/Connecting'\">Help</div>";

}

/*============================================================================*\
  End HTML
\*============================================================================*/
?>
</div>
</div>
</body>
</html>
