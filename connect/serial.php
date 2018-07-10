<?php
/*============================================================================*\
  Generate serial number for a device

  Post:
    local_ip: set to valid local IP
    type: optional

  A serial number is a globally unique short identifyer string.
  It consistes of digits (chars) of base 36 uppercase numbers:
  random number [6] + type[2] + epoch time [6] + crc [2]

  (c) Paragi 2017, Simon Riget
\*============================================================================*/
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require "rocket-store.php";
$rsdb = new \Paragi\RocketStore([
    "data_storage_area" => "data"
  , "data_format" => RS_FORMAT_JSON
]);

do{
  $check = "No local host address";
  if(empty($_REQUEST['local_ip'])) break;

  $check = "Local IP address: " . $_REQUEST['local_ip'];
  if(!filter_var($_REQUEST['local_ip'],FILTER_VALIDATE_IP)) break;

  $check = "IP address: " . $_SERVER['REMOTE_ADDR'];
  if(!filter_var($_SERVER['REMOTE_ADDR'],FILTER_VALIDATE_IP)) break;

  // Generalte serial number
  $check = "Internal storage";
  $serial = strtoupper(substr("00000" . base_convert(rand(),10,36),-6));
  $serial .= dechex(ord($_REQUEST['type'] == "s" ? "s" : "i"));
  $serial .= strtoupper(base_convert(time(),10,36));

  // Check if unique or add counter...

  for($i = strlen($serial)-1, $crc = 0; $i >= 0; $i--)
    $crc ^= ord($serial[$i]);

  $serial .= substr("0".strtoupper(base_convert($crc,10,36)),-2);

  // Store
  $record = [
      "time" => time()
    ,"remote_ip" => $_SERVER['REMOTE_ADDR']
    ,"local_ip" => $_REQUEST['local_ip']
    ,"serial" => $serial
  ];
  $key = "{$record["remote_ip"]}-{$serial}";
  $reply = $rsdb->post("serial",$key,$record);
  if($reply['count'] != 1){
    $check = $reply['error'];
    break;
  }

  $check = false;

  echo $serial;

}while(false);

if(!empty($check)) echo "Failed on $check";

?>
