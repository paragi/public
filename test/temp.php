#!/usr/bin/php
<?php
// Append sensordata to CSV file
// File format: time[[,ID,data]..]

define("FILE_NAME","sensor_log.csv");
$csv[]=time();

// Read all w1 devices
$list=glob("/sys/bus/w1/devices/*");
if(is_array($list)) foreach($list as $dir){
  if(!is_dir($dir)) continue;
  if(strpos($dir,"bus_master")) continue;
  // Read sensor data
  $data=file_get_contents($dir."/w1_slave");
  if(!$data) continue;
  // Derive device id and data
  $csv[]=strtoupper(substr($dir,strrpos($dir,"/")+1));
  $csv[]=(float)substr($data,strrpos($data,"=")+1);    
}

// Append to file
if(count($csv)>1){
  $file=fopen(FILE_NAME,"a");
  if($file!==null){
    fputcsv($file,$csv);
    fclose($file);
  }
}
?>
