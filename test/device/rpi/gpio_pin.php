<?php
/*============================================================================*\
  GPIO direct pin handler

  Namespace should reflect the directory containing this file.
  Class name should be the file name without extention.
\*============================================================================*/
namespace device\rpi;
class gpio_pin {
/*============================================================================*\
  Device handler:
  
  This is the code that talks with a device, identified by type and a unit ID

  The class defined must have two public functions:  
    handler(<command>,[<unit ID>])
    initialize([<unit ID>]) 

  This file is included by the service module to execute interaction commands on
  devices using the handler function.
  
  Commands are send directly to the device. Empty means status request of unit.
  (on-line?)
  A list of commands can be requested (array)  
  The response returned is only from the last command executed

  Unit ID is a code used to identify wich device is being adressed.

  the initialize function is called once when the server starts 

  An interaction definition file is used to define the use of this device, a 
  unit ID to wich commands should be send on requests.

  error handling using PHP error
  Execution stops on error

  A devices that is unavailable is NOT an error. The status is just off-line
  
  To debug, use the direct command interface
\*============================================================================*/



/*============================================================================*\
  Device specifics:

  RPI provids access GPIOs in the system filesystem directory:

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


  In general:
  
  The namespace must be system unique

  $device shall contain a new class, with two public functions: handler and
  initialize. 
  
  This file is included by the service module to execute interaction commands on
  devices using the handler function.
  the initialize function is called once when the server starts 

  An interaction definition file defines this device, a unit ID and wich 
  commands to send on requests.

  error handling using PHP error

  The handler does the actual communication with the device. (except on meta requests)

  A devices that is unavailable is NOT an error. The status is just off-line

  Commands are send directly to the device. Empty means status request of unit.
  (on-line?)

  Unit ID is a code used to identify wich device is being adressed.

  A list of commands can be requested (array)  
  The response returned is only from the last command executed
  Execution stops on error
  
  To debug, use the direct command interface
  
\*============================================================================*/

// Time to wait for group persimmions to be changed on sysfs file, by udev rules
var $gid_timeout=200; // ms
var $initialized=false;

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
  foreach($command as $cmd){
    switch (substr($cmd,0,strpos($cmd." "," "))){
      // Meta commands
      case "capabilities":
        $response['result']=["set","get","status","list","conf","diagnostic"];
        break;
      case "description":
        $response['result']="RPI GPIO pin device handler";
        break;
      case "list": // List connected device IDs
        $response=$this->list_ports();
        break;
      case "status":
      case "get": // Get state
        $response=$this->get($unit_id);
        break;
      case "set": // set <state>
        $response=$this->set($unit_id,substr($cmd,strpos($cmd," ")+1));
        break;
      case "conf": // Initialize port
        $direction=(strpos($cmd," out")? "out" :"in");
        $low=(strpos($cmd," inv")? 1 :0);
        $response=$this->configure($unit_id,$direction,$low);
        break;
      case "diagnostic":
        $response['reply']='ok';  
        break;
      default:
        $s=substr($cmd,0,strpos($cmd." "," "));
        $response['error']="The RPI GPIO pin device handler did not recognize this command: '$cmd'";
        break;
    }
  }

  // Fill with default values
  if(empty($response['state'])) $response['state']='off-line';
  if(!isset($response['error'])) $response['error']=null;
  if(!empty($response['error'])) $response['state']='off-line';
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
  static $initialized=false;
  if($initialized) return;
  echo "Initializing ". __NAMESPACE__ . __CLASS__ ."\n";
  $initialized=true;
}

/*==========================================================================*\
  Configure port
  
  This has to be done after reboot
  
  The permissions on the gpio files are root:root 644, until /etc/udev/rules.d 
  rule changes it to root:gpio after some 100 ms.

\*==========================================================================*/
private function configure($unit_id,$direction="out",$active_low=0){  
  // Remove all but the port number
  $val=preg_replace( '/[^0-9]/','',$unit_id);
  if(!is_numeric($val)) 
    return ["error"=>"Unit ID '$unit_id' given. should have the format  gpio<number>","state"=>"off-line"];
  $val=intval($val);

  // Check if port is already exported
  if(!file_exists("/sys/class/gpio/$unit_id/direction")){ 
  
    // Activate the port
    $err=$this->write_file("/sys/class/gpio/export","{$val}");
    if($err) return ["error"=>$err,"state"=>"off-line"];

    // Wait for export to finish and GID to be set to gpio (not root)
    for($i=0;$i<$this->gid_timeout/10 ;$i++){
      if(is_writable("/sys/class/gpio/$unit_id/direction")) break;
      usleep(10000);
    }
  }

  // Set port direction  
  $err=$this->write_file("/sys/class/gpio/$unit_id/direction",$direction);
  if($err) return ["error"=>$err." timeout: ".$i*10 ."ms)","state"=>"off-line"];

  // Define active hi/low  
  $err=$this->write_file("/sys/class/gpio/$unit_id/active_low","$active_low");
  if($err) return ["error"=>$err,"state"=>"off-line"];
  
  return ["reply"=>"ok","error"=>""];
}

/*==========================================================================*\
  Write sysfs file function
  
  Return: a meaningfull error string
\*==========================================================================*/
private function write_file($fn,$data){
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
}

/*============================================================================*\
  List configured ports
\*============================================================================*/
private function list_ports(){
  // Search enabled devices 
  $device=glob("/sys/class/gpio/gpio*",GLOB_NOSORT | GLOB_MARK );
  foreach($device as $name){
    if($name=="/sys/class/gpio/gpiochip0/") continue;
    $result[]=substr($name,strrpos($name,"/",-2)+1,-1);
  }
  return ["reply"=>"ok","result"=>$result];
}
    
/*============================================================================*\
  Set port output
\*============================================================================*/
private function set($unit_id,$state){
  $states=["on"=>1,"off"=>0,"0"=>0,"1"=>1,"toggle"=>2];

  if(empty($unit_id))
    return ["error"=>"Must have a unit ID to set"];
  if(empty($state))
    return ["error"=>"Must have a defined state to set port"];

  // Check state
  $val=$states[strtolower($state)];
  if(!is_int($val)) 
    return ["error"=>"Can not set $unit_id to state '$val'","state"=>"off-line"];
  // Check that port is configured 
  if(!file_exists("/sys/class/gpio/$unit_id"))
    gpio_configure($unit_id,"out");

  $filename="/sys/class/gpio/$unit_id/value";
  if(!file_exists($filename))
    return ["error"=>"Unable to communicate with unit: '$unit_id'","state"=>"off-line"];
    
  // toggle state  
  if($val>1){
    $curstate=file_get_contents($filename);
    if(empty($curstate))
      return ["error"=>"Unable to determine the state of $unit_id","state"=>"off-line"];
    $val=!intval($curstate)+0;
  } 

  // Set state
  $err=$this->write_file($filename,$val);
  if(!empty($err)) return ["error"=>$err,"state"=>"off-line"];

  return ["reply"=>"ok","state"=>($val?"on":"off")];
}

/*============================================================================*\
  get port state
\*============================================================================*/
private function get($unit_id){
  // Check that port is configured 
  if(!file_exists("/sys/class/gpio/$unit_id"))
    return ["error"=>"$unit_id is not configured","state"=>"off-line"];
//      $this->configure($unit_id,"in");

  $filename="/sys/class/gpio/$unit_id/value";
  if(!file_exists($filename))
    return ["error"=>"Unable to communicate with $unit_id","state"=>"off-line"];
    
  $curstate=file_get_contents($filename);
  if(empty($curstate))
    return ["error"=>"Unable to determine the state of $unit_id","state"=>"off-line"];
  $val=!intval($curstate)+0;

  return ["reply"=>"ok","state"=>($val?"on":"off")];
}  

}
?>

