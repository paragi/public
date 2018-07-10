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
<div class="container"></pre>
<?php
set_error_handler(null);
/*============================================================================*\
  I2C bus handler


  Namespace should reflect the directory containing this file.
  Class name should be the file name without extention.
\*============================================================================*/
//namespace device\i2c;
class i2c_bus {
/*============================================================================*\
  Device handler:
  
  This is the code that talks with a device, identified by type and a unit ID

  The class defined must have two public functions:  
    handler(<command>,[<unit ID>])
    initialize([<unit ID>]) 

  This file is included by the service module to execute interaction commands on
  devices using the handler function.
  
  Commands should be device specific or close to it. Translations is done with 
  the interaction descriptor. Commands should be at leas something like this:
    - status : request status of unit. Is it on-line etc
    - get      
    - set		
    - capabilities : list of commands
    - description : a text string
    - diagnostic : an array of status at different levels 
    (Empty means get state of unit)

  If a series of commands are requested (array), only the last commands reply
  are returned. 

  Unit ID is a code is used to identify which device is being addressed.Commands are send directly to the device. Empty means status request of unit.
  (on-line?)
  A list of commands can be requested (array)  
  The response returned is only from the last command executed

  the initialize function is called once when the server starts 

  An interaction definition file is used to define the use of this device, a 
  unit ID to wich commands should be send on requests.

  Let error handling be done with PHP error
  Execution stops on error
  Error messages:
  If the user gives a command, try to think of doing something sensible, instead
  of throwing an error message. Assume instead that the user may have a 
  perfectly reasonable expectation, that at the moment is beyond your 
  comprehension. If you are unable to do that, say do politely.
  REMEMBER:  --- The user is thy GOD! ---

  A devices that is unavailable is NOT an error. The status is just off-line
  
  To debug, use the direct command interface
  
  Return value:
  
  The function should return an array of the following format:
  
  error => Always defined but empty when ok.
  The error message is a precise technical description, related to the device.
  It's often a good idea to include offending values of variables or other 
  information that facilitates solving the problem.
      
  state => If the given command requests or manipulates the state of the device,
  this variable should reflect the (new) state of the device.
  the state can also be off-line, for a number of reasons.
  
  result => If the given command dose not directly requests or manipulates the 
  state of the device, the result of the request is given here. It can be a 
  string or an array of a strings. A typical use is for meta data.
  
  reply => High level reply to the user, depending on the outcome it eg. "ok" or
  "failed" or empty string. Might be ignored.
  
\*============================================================================*/


/*============================================================================*\
  Device specifics:

  Provids access to the i2c bus 

  To enable i2c support:

  Edit /etc/modules and add the lines:   
    i2c-bcm2708 
    i2c-dev

  Edit /boot/config.txt and add the lines:
    #enable i2c interface
    dtparam=i2c1=on
    dtparam=i2c_arm=on

  i2c-tools binaries must be installed
    apt-get install i2c-tools

  Unit ID consists of a bus number and a chip address, separated by a dash 
  
  The i2c device driver for linux require a call to ioctl to set the address of
  the device you wish to communicate with.
  Since ioctl has a 3th parameter that is a highly device specific structure, it
  is not supported in PHP.
  Certain workarounds manipulating the file descriptor and the private_data 
  appended to it by i2c-dev driver, is not stable through out versions of php.

  That leavs two alternatives: 
 	  1 make or modify a PHP extention (dio) 
	  2 make an exec call to an executable that dose the job

  This handler uses method 2, as it is a prototype. If the need arises to access
  i2c devices more then onces in a script, it would be preferable to use method 1.

  i2c-tool syntax:
    Get a register:		
	    i2cget -y 1 0x23 0x10 w
        -y   : dont ask for confirmation
        1    : bus number
        0x23 : chip address on the bus
        0x10 : register address in the chip
        w    : Read 16 bit.

    List devices
	    i2cdetect -y 1

\*============================================================================*/

var $description="I2C Bus device handler";


/*==========================================================================*\
  Handler function.
  
  Interprets and executes commands on device identified by $unit_id

  Parameters:
    $command: an array of command strings
    $unit_id: A string that identifies the device
  
  Return:
    an associative array containing one ore more of the following key-value pairs 
    error:  An explanation as to why the command failed
    state:  value returned of a get device state request
    status: on/off-line 
    result: an answer to a request
    
    Atleast one must be pressent.
\*==========================================================================*/
public function handler($command,$unit_id=null){

  // Execute commands
  if(!is_array($command)) $command=[$command];
  foreach($command as $cmd_str){
    $cmd=explode(" ", $cmd_str);

    if($cmd[0] == 'capabilities'){
      $response['result']=["get","set","status","list","diagnostic"];
      
    }elseif($cmd[0] == 'description'){
      $response['result']=$description;
      
    }elseif($cmd[0] == 'list'){
      $bus=glob("/dev/i2c*");
      if(empty($bus)) continue;
      foreach($bus as $device_name){
        $bus_no=substr($device_name,strrpos($device_name,'-')+1);
        $exe="/usr/sbin/i2cdetect -y ".escapeshellarg("{$bus_no}");
        exec($exe." 2>&1",$output,$rc);
        if($rc){
          $response['error']="Execution of $exe failed.";
          if($rc==127 && !is_executable("/usr/sbin/i2cdetect"))
            $response['error'] = "It seems that i2c-tools is not installed";
          else  
            $response['error'] .= " ".implode("\n",$output);
          continue;     
        }

        // Convert block graphics to a list
        for($l=1;$l<9;$l++){
          foreach(explode(" ",substr($output[$l],strpos($output[$l],":")+2)) as $hex_adr)
            if( ($adr = hexdec($hex_adr)) >0)
              $response['result'][] =  "$bus_no-$adr\n";
        }
      }
    
    }else{      
      if(empty($unit_id)){
        $response['error'] = "Please specify which unit to work with (empty)";
        continue;
      }else
        list($bus_no,$bus_adr)=explode("-",$unit_id);

      // Default to chip register address 0, if not specified
      if(empty($cmd[1]))$cmd[1]="0";

      if(!is_executable("/usr/sbin/i2cdetect") 
         || !is_executable("/usr/sbin/i2cdetect")
         || !is_executable("/usr/sbin/i2cdetect")){
        $response['error'] = "It seems that i2c-tools is not installed";
        continue;
      }

      if($cmd[0] == 'status'){
        $exe=sprintf("/usr/sbin/i2cdetect -y %d %d ",$bus_no,$bus_adr);
        exec($exe." 2>&1",$output,$rc);
        if($rc){
          $response['error']="Execution of $exe failed. ".implode("\n",$output);
          continue;     
        }
        $response['state'] = "on-line";
        // Convert block graphics to a list
        for($l=1;$l<9;$l++)
          foreach(explode(" ",substr($output[$l],strpos($output[$l],":")+2)) as $hex_adr)
            if(hexdec($hex_adr) >0){
              $response['state'] = "on-line";
              break 2;
            }

      }elseif($cmd[0] == 'get'){
        $exe=sprintf("/usr/sbin/i2cget -y %d %d ",$bus_no,$bus_adr);
        unset($cmd[0]);
        foreach($cmd as $parameter) $exe .= " ".escapeshellarg($parameter);
        exec($exe." 2>&1",$output,$rc);
        if($rc){
          $response['error']="Execution of $exe failed. ".implode("\n",$output);
          continue;     
        }
        
        if(strpos($output[0],"0x")===0)
          $response['state'] = hexdec($output[0]);
        else  
          $response['state'] = "off-line";

      }elseif($cmd[0] == 'set'){
        if(empty($cmd[1])){
          $response['error']="Please specify an opcode or register address to write to chip";
          continue;
        }
 
        $exe=sprintf("/usr/sbin/i2cset -y %d %d ",$bus_no,$bus_adr);
        unset($cmd[0]);
        foreach($cmd as $parameter) $exe .= " ".escapeshellarg($parameter);
        exec($exe." 2>&1",$output,$rc);
        if($rc){
          $response['error']="Execution of $exe failed. ".implode("\n",$output);
          continue;     
        }
      
      }elseif($cmd[0] == 'diagnostic'){
        $file="/sys/bus/w1/devices/".$unit_id."/w1_slave";
        if(file_exists($file))
          $response['reply'][]="The device is on-line";
        else    
          $response['reply'][]="The device is off-line";
        if(!is_dir("/sys/bus/w1/devices/w1_bus_master1"))
          $response['reply'][]="The gpio-w1 module dose not seem to be configured in the kernel";

      }else{  
        $response['error'] = 
          "The {$this->description} dose not support the command: '{$cmd[0]}'";
      }
    }
  }
  // Fill with default values
  if(!isset($response['error'])) $response['error']=null;
  return $response;
}

/*==========================================================================*\
  Initialize device

  Initialize settings and device, opon boot.
  This function is called when the server starts. It should be written to allow
  to be called multiple times. 
  The server might not be fully initialised when this script is running. You can
  not expect all services to respond.  
  preemptime loaded libraries are not likely to be loaded when this function is
  called. 

  Errors are not recordes and no events are send.
  
  When errors occurres, try to continue if at all posible.
  
  Output from this function are redirected to initialize.log
\*==========================================================================*/
public function initialize($unit_id=null){
  echo "her";      
  static $initialized=false;
  if(!$unit_id && $initialized) return;
  $initialized=true;

}

}
/*================================================================================ *\


\*================================================================================ */

$device=new i2c_bus;

echo "Initialize:\n";
print_r($device->initialize());

echo "list:\n";
print_r($device->handler("list"));

$unit_id="1-35";
echo "Set power on:\n";
print_r($device->handler("set 0x01",$unit_id));


echo "Set Asynchronous reset:\n";
print_r($device->handler("set 0x07",$unit_id));

echo "Set Continuous high resolution mode:\n";
print_r($device->handler("set 0x10",$unit_id));

usleep(200000);
//echo "Set mesure interval:\n";
//print_r($device->handler("set 0x11",$unit_id));
echo "Get:\n";
// i2cget tool requires a register address to allow reading word (2 bytes) 
// Using the Set Continuous high resolution mode ad data address 
$res=$device->handler("get 0x11 w",$unit_id);
print_r($res);
echo "Illumaination: ", intval($res['state']/1.2), " Lux\n";


exit;



/*================================================================================ *\
  List devices

  i2c-tool syntax: i2cdetect -y 1
    -y   : dont ask for confirmation
    1    : bus number


\*================================================================================ */

// List i2c busses
$bus=glob("/dev/i2c*");
do{
  if(empty($bus))  break;
  foreach($bus as $device_name){
    echo "$device_name\n";
    $bus_no=substr($device_name,strrpos($device_name,'-')+1);
    echo "Bus number $bus_no\n";

// resource proc_open ( string $cmd , array $descriptorspec , array &$pipes [, string $cwd [, array $env [, array $other_options ]]] )


    exec("/usr/sbin/i2cdetect -y {$bus_no} 2>&1",$output,$rc);
    echo "I2C detect returned $rc\n";
//    print_r($output);

    if($rc){
      $response['error']="Execution of /usr/sbin/i2cdetect failed.";
      if($rc==127 && !is_executable("/usr/sbin/i2cdetect"))
        $response['error'] = "It seems that i2c-tools is not installed";
      else  
       $response['error'] .= " ".implode("\n",$output);
      break;     
    }

    // Convert block graphics to a list
    for($l=1;$l<9;$l++){
      foreach(explode(" ",substr($output[$l],strpos($output[$l],":")+2)) as $hex_adr)
        if( ($adr = hexdec($hex_adr)) >0)
          $response['result'][] =  "$bus_no-$adr\n";
    }
  }
     
}while(false);

print_r($response);



/*



  echo "Devices:\n"; 
  foreach($bus as $device_name){
    echo "Bus: $device_name\n";
    $i2c=fopen($device_name,"w+b");
    if(!$i2c){ 
      echo "error opening device\n"; 
      break 2; 
    }

    $adr=0x23;
    
    // https://www.kernel.org/doc/Documentation/i2c/instantiating-devices
    // /sys/bus/i2c/devices/i2c-1# echo test 0x23 > new_device
    
    
    $write_instruction = chr($adr<<1) . chr(0x10);
    $rc=fwrite($i2c,$write_instruction);
    printf("Sending %s: %d\n",$write_instruction,$rc);
    
    $rc=0;
    fseek($i2c, 0x10 , SEEK_CUR );
    
    
    //usleep(180000);
    
    $read_instruction = chr($adr<<1 | 1);
    /*
    $rc=fwrite($i2c,$read_instruction);
    
    $reply=fread($i2c,2);
    if($reply===false)
      echo "read instruction failed\n";
    else  
      printf("reading instruction: %s: (%d) reply: %X\n",$read_instruction,$rc,$reply);
    
var_dump($reply);
/*    
    //  for($adr=0x03; $adr<=0x77;$adr++){
    $registry=0;
    foreach([0x23,0x77,0x10] as $adr){    
      printf("Adr %x: ",$adr);
  
      fwrite($i2c,chr($adr<<1).chr(01));
      //fseek($i2c, ($adr & 0xFE) << 8 & $registry);
      for($c=1;$c<10;$c++){ 
        $reply=fread($i2c,2); 
        if($reply!==false)
          printf("%x\n",ord($reply));
        else {
          printf("null\n");
          break;
        }
      }
    }
  */ /*
  
    $address = ($address | 0x01) << 8 & $registry;
   $i2c = fopen("/dev/i2c-2", "w+b");
   fseek($i2c, $address)
   $rtn = fread($i2c, $length)
   fclose($i2c);
  

*/

?>
</div>
</body>
</html>
