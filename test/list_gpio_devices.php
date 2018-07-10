<?php
/*==========================================================================*\
It does this by providing access to files in the filesystem directory:

/sys/class/gpio
The kernel documentation for the gpio driver can be read at
http://www.kernel.org/doc/Documentation/gpio.txt
In /sys/class/gpio there are two files that allow you to export pins for access and unexport pins to remove access.

/sys/class/gpio/export
/sys/class/gpio/unexport
To export GPIO Pin 17 so that we can read, write and control the pin, we simply write 17 to the export file:

sudo sh -c 'echo 27 > /sys/class/gpio/export'
With default settings we must use sudo as root permissions are required for the files. Note that we cannot use the following:

sudo echo 27 > /sys/class/gpio/export
The redirection of command output to /sys/class/gpio/export applies to the output of the command, which in this case is 'sudo'. We don't have permissions to write to the file so using sudo here is pointless. The work around is to run a shell ( sh ) under sudo and pass in the whole command including redirection using the -c option.

Once GPIO pin 17 is exported the following files (or links to files) are created in a new directory, gpio17.

/sys/class/gpio/gpio17/direction
/sys/class/gpio/gpio17/value
/sys/class/gpio/gpio17/edge
/sys/class/gpio/gpio17/active_low
We can set the pin as an input or output by writing either 'in' or 'out' to the direction file. We can also read the file to query the current function of the pin. By default, output pins are configured and set low. Writing 'high' or 'low' to the direction file configures the pin as an output initially set at that level.

sudo sh -c 'echo in > /sys/class/gpio/gpio17/direction'
We read the state of the pin ( 1 or 0 for high or low) by reading the value file. For pins configured as outputs, we can set the value by writing to the file.

sudo cat /sys/class/gpio/gpio17/value

sudo sh -c 'echo out > /sys/class/gpio/gpio17/direction'
sudo sh -c 'echo 1 > /sys/class/gpio/gpio17/value'

... or in a single command ....
sudo sh -c 'echo high > /sys/class/gpio/gpio17/direction'
We can set interrupts by writing to the edge file or read the file to get the current setting. Possible edge settings are none, falling, rising, or both. We check for interrupts by using poll(2) on the value file.

sudo sh -c 'echo 0 > /sys/class/gpio/gpio17/value'
sudo sh -c 'echo in > /sys/class/gpio/gpio17/direction'
sudo sh -c 'echo falling > /sys/class/gpio/gpio17/edge'
... poll /sys/class/gpio/gpio17/value in some code elsewhere
We can invert the logic of the value pin for both reading and writing so that a high == 0 and low == 1 by wrting to the active_low file. To invert logic write 1. To revert write 0

sudo sh -c 'echo 1 > /sys/class/gpio/gpio17/active_low'
\*==========================================================================*/
?><!DOCTYPE HTML>
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

<body>
<h1>List of GPIO ports</h1>
<div class="container">
<pre>
<?php

/*============================================================================*\
    Configure port
    
    This has to be done after reboot
    
    The permissions on the gpio files are root:root 644, until /etc/udev/rules.d 
    rule changes it to root:gpio after some 100 ms.

  \*============================================================================*/
  $gpio_configure=function ($unit_id,$direction="out",$active_low=0){  
    $gid_timeout=200; // ms
    
    // Write sysfs file function
    $write_file=function($fn,$data){
      // Check that file exists
      if(!file_exists($fn))
        return "The file '$fn' dose not exists";
        
      // Check access 
      if(!is_writable($fn)){
        // Get file persimmions
        $stat=stat("/sys/class/gpio/export");
        $perm=posix_getpwuid($stat['uid'])['name']
          .":".posix_getgrgid($stat['gid'])['name']
          ." ".decoct($stat['mode'] & 0777);  
        return "Write access to the file '$fn' was denied ($perm)";
      }
        
      // Write
      @file_put_contents($fn,"{$data}"); // Might return false on succes!

      return "";
    };
    
    // Remove all but the port number
    $val=preg_replace( '/[^0-9]/','',$unit_id);
    if(!is_numeric($val)) 
      return ["error"=>"Unit ID '$unit_id' given. should have the format  gpio<number>","state"=>"off-line"];
    $val=intval($val);

    // Check if port is already exported
    if(!file_exists("/sys/class/gpio/$unit_id/direction")){ 
    
      // Activate the port
      $err=$write_file("/sys/class/gpio/export","{$val}");
      if($err) return ["error"=>$err,"state"=>"off-line"];

      // Wait for export to finish and GID to be set to gpio (not root)
      for($i=0;$i<$gid_timeout/10 ;$i++){
        if(is_writable("/sys/class/gpio/$unit_id/direction")) break;
        usleep(10000);
      }
    }

    // Set port direction  
    $err=$write_file("/sys/class/gpio/$unit_id/direction",$direction);
    if($err) return ["error"=>$err." timeout: ".$i*10 ."ms)","state"=>"off-line"];

    
    // Define active hi/low  
    $err=$write_file("/sys/class/gpio/$unit_id/active_low","$active_low");
    if($err) return ["error"=>$err,"state"=>"off-line"];
    
    return ["reply"=>"ok","error"=>""];
  };

  
  
  /*============================================================================*\
  List configured ports
\*============================================================================*/
function gpio_list(){
  // Search enabled devices 
  $device=glob("/sys/class/gpio/gpio*",GLOB_NOSORT | GLOB_MARK );
  foreach($device as $name){
    if($name=="/sys/class/gpio/gpiochip0/") continue;
    $reply[]=substr($name,strrpos($name,"/",-2)+1,-1);
  }
  return $reply;
}
    
/*============================================================================*\
  Set port output
\*============================================================================*/
function gpio_set($unit_id,$state){
  $states=["on"=>1,"off"=>0,"0"=>0,"1"=>1,"toggle"=>2];
  
  // Check state
  $val=$states[strtolower($state)];
  if(!is_int($val)) 
    return ["error"=>"Can not set $unit_id to state '$val'"];
  // Check that port is configured 
  if(!file_exists("/sys/class/gpio/$unit_id"))
    gpio_configure($unit_id,"out");

  $filename="/sys/class/gpio/$unit_id/value";
  if(!file_exists($filename))
    return ["error"=>"Unable to communicate with $unit_id"];
    
  // toggle state  
  if($val>1){
    $curstate=file_get_contents($filename);
    if(empty($curstate))
      return ["error"=>"Unable to determine the state of $unit_id"];
    $val=!intval($curstate)+0;
  } 

  // Set state
  file_put_contents($filename,$val);

  return ["reply"=>"ok","state"=>($val?"on":"off"),"status"=>"on-line"];
}

/*============================================================================*\
  get port state
\*============================================================================*/
function gpio_get($unit_id){
  // Check that port is configured 
  if(!file_exists("/sys/class/gpio/$unit_id"))
    gpio_configure($unit_id,"in");

  $filename="/sys/class/gpio/$unit_id/value";
  if(!file_exists($filename))
    return ["error"=>"Unable to communicate with $unit_id"];
    
  $curstate=file_get_contents($filename);
  if(empty($curstate))
    return ["error"=>"Unable to determine the state of $unit_id"];
  $val=!intval($curstate)+0;

  return ["reply"=>"ok","state"=>($val?"on":"off"),"status"=>"on-line"];
}

  $gpio_configure("gpio18","out");
  $gpio_configure("gpio23","out");
  $gpio_configure("gpio24","out",1);
  $gpio_configure("gpio25");
  


  // Show configured ports
  $attribute=["direction","active_low","edge","value"];
  // Make headers  
  echo "<tr><th>Port name</th>";
  foreach($attribute as $att)
    echo "<th>$att</th>";
  echo "</tr>";
  foreach(gpio_list() as $name){
    echo "<tr><td>{$name}</td>";
    foreach($attribute as $att){
      echo "<td>";
      if(file_exists("/sys/class/gpio/$name/$att")){
        $val=trim(file_get_contents("/sys/class/gpio/$name/$att"));
        echo $val;
      }    
      echo "</td>";
    }
    echo "</tr>\n";
  }
  echo "</table><br><div><pre>";
   

  // Play with it
  foreach(gpio_list() as $name){
    usleep(500000);
    print_r(gpio_set($name,"on"));
  }

  for($i=0;$i<2;$i++){
    foreach(gpio_list() as $name){
      usleep(500000);
      print_r(gpio_set($name,"toggle"));
    }
  }
    

 
?>

</div>
</body>
</html>
