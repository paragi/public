<?php
/*============================================================================*\
  Retreive local IP for a registred device
  
  (c) Paragi 2017, Simon Riget
\*============================================================================*/
function check(){
  $store = __DIR__ . "/data";

  $check = "testing";
  if($_REQUEST['fail']) 
    return $_REQUEST['fail'];

  $check = "IP address: " . $_SERVER['REMOTE_ADDR'];
  if(!filter_var($_SERVER['REMOTE_ADDR'],FILTER_VALIDATE_IP)) 
    return $check;

  $check = "No record";
  $record = unserialize(@file_get_contents("$store/connect/{$_SERVER['REMOTE_ADDR']}"));
  if(empty($record)) 
    return $check;
  
  if(time() - $record[0] >1800 ) 
    return $check;
  
  return false;
}

/*============================================================================*\
  Find out wich state we are at, and set headers arcordingly
\*============================================================================*/
$check = check();
if($check){ 
  if($check == "No record"){
    session_start();
    if(empty($_SESSION['start'])) 
      $_SESSION['start'] = time();
    
    if(time() - $_SESSION['start'] < 180){
      $check = "wait";
      header("Refresh: 10");
    }else
      unset($_SESSION['start']);
  }

// redirecto to local address of device
}else
  header("Location: http://{$record[2]}");    


do{  
  // Display HTML header etc.
  echo <<<EOT
<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8" />
<title>Connect SmartCore </title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" href="theme.css" />
</head>
<body>
<div class="main_container">
<div style="white-space: pre-wrap; text-align: center; width:100%">

EOT;

  if($check == "wait") {
    echo <<<EOT
Hi, welcome to Smart Space.

Please <b>turn on</b> your SmartCore, and make sure it has <b>Internet access</b>.

<div style="
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
"></div>

EOT;
    break;

  // registration issue
  }elseif($check == "No record") 
    echo "Your device is not registering.\n\n";

  // Unknown issue
  else 
    echo "Sorry. We are unable locate your your device.\n\n$check\n\n";
    
  // Wiki tile
  echo "<div class=\"tile\" style=\"background-image: url(wiki.png)\""
    . " onclick=\"window.location='http://futu-rum.com/wiki/index.php/Connecting'\"></div>";

  // retry tile
  echo "<div class=\"tile\" style=\"background-image: url(reload.png)\""   
    . " onclick=\"window.location=''\"></div>";

}while(false);
?>

