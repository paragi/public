<?php
/*============================================================================*\
  Register connected device

  Store time, IP's and caller ID, in a lookup file.

  (c) Paragi 2017, Simon Riget
\*============================================================================*/
require "rocket-store.php";
$rsdb = new \Paragi\RocketStore([
    "data_storage_area" => "data"
  , "data_format" => RS_FORMAT_JSON
]);

$store = __DIR__ . "/data";

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

do{
  $check = "No caller ID";
  $_REQUEST['caller_id'] = trim($_REQUEST['caller_id']);
  if(empty($_REQUEST['caller_id'])) break;

  $check = "No local host address";
  if(empty($_REQUEST['local_ip'])) break;

  $check = "Local IP address: " . $_REQUEST['local_ip'];
  if(!filter_var($_REQUEST['local_ip'],FILTER_VALIDATE_IP)) break;

  $check = "Local IP address: " . $_REQUEST['local_ip'];
  if(!filter_var($_REQUEST['local_ip'],FILTER_VALIDATE_IP)) break;

  $check = "IP address: " . $_SERVER['REMOTE_ADDR'];
  if(!filter_var($_SERVER['REMOTE_ADDR'],FILTER_VALIDATE_IP)) break;

  $check = "Caller ID: " . $_REQUEST['caller_id'];
  for($i = 13, $crc = 0; $i >= 0; $i--)
    $crc ^= ord($_REQUEST['caller_id'][$i]);

  if($crc != base_convert(substr($_REQUEST['caller_id'],-2),36,10)) break;

  $check = "Caller ID unknown";

  $check = "Internal storeage";
  $record = [
     "time" => time()
    ,"remote_ip" => $_SERVER['REMOTE_ADDR']
    ,"local_ip" => $_REQUEST['local_ip']
    ,"serial" => $_REQUEST['caller_id']
  ];

  $reply = $rsdb->post("connect",$_SERVER['REMOTE_ADDR'],$record);
  if($reply['count'] != 1){
    $check = $reply['error'];
    break;
  }

  $check = false;

  echo "ok";

}while(false);

if(!empty($check)) echo "Failed on $check";

?>
