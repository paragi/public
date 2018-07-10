<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8" />
<title>Test scripts:</title>
<link rel="shortcut icon" href="/favicon.ico" >
<link rel="icon" href="/favicon_big.ico" type="theme/ico" >
<link rel="stylesheet" type="text/css" href="/theme/theme.css" />
<script type="text/javascript" src="util.js"></script>
<script type="text/javascript" src="/page_services.js" /></script>
<style>

</style>
</head>
<h1>List of USB devices</h1>
<pre>
<?php

/*==========================================================================*\
  Write sysfs file function
  
  Return: a meaningfull error string
\*==========================================================================*/
function write_file($fn,$data){
  // Check that file exists
  if(!file_exists($fn))
    return "The file '$fn' dose not exists";
    
  // Check access 
  if(!is_writable($fn)){
    // Get file persimmions
    $stat=stat($fn);
    $perm=posix_getpwuid($stat['uid'])['name']
      .":".posix_getgrgid($stat['gid'])['name']
      ." ".decoct($stat['mode']);  
    return "Write access to the file '$fn' was denied ($perm)";
  }
    
  // Write
  $rc=@file_put_contents($fn,"{$data}"); // Might return false on succes!
  if($rc<strlen("{$data}")){
    $error=error_get_last(); 
    return $error['message'];
  }
  
  return "";
}

  // Search for usb devices attached
  $device=glob("/sys/class/usbmisc/*",GLOB_NOSORT | GLOB_MARK );
  foreach($device as $name){

    echo "Looking at $name\n";
    // Get device name
    $uevent=parse_ini_file($name."uevent");
    if(is_array($uevent) && $uevent['DEVNAME']){
      $dev_name="/dev/".$uevent['DEVNAME'];   
    }

    // Find the sysfs path to information about the device
    $arr=explode("/",realpath(dirname(realpath($name))."/../../"));
    $path=implode("/",$arr);
    if(!$path) continue;

echo "Path : $path\n";
    
     // Get alternative device name based on bus number/device number
    // !! TEST THIS
    if(!$dev_name){
      $dev_name="/dev/bus/usb";
      $dev_name.="/".sprintf("%03d",trim(file_get_contents($path."/busnum")));
      $dev_name.="/".sprintf("%03d",trim(file_get_contents($path."/devnum")));
    }
    
    // Compose a unique ID from device serial number
    if(file_exists($path."/serial")){
      $id=trim(file_get_contents($path."/serial"));     // !!Test this!!

    // Alternativly use bus number
    }else{
      // The only way to uniquely identify identical devices, without a serial 
      // number, is to use the connetor placement in the USB bus.
      // This means that the divece change ID with placement of USB connector :(
     // Find usb bus path
     $id=end($arr);
    }
    
    // Add vendor and product ID
    $id.=":".sprintf("%-4s",trim(file_get_contents($path."/idVendor")));
    $id.=":".sprintf("%-4s",trim(file_get_contents($path."/idProduct")));
echo "ID: $id = $dev_name\n";

    print_r(write_file($dev_name,"0"));  

  }
  
  
?>
</body>
</html>
