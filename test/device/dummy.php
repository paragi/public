<?php
/*============================================================================*\
  GPIO direct pin handler

  Namespace should reflect the directory containing this file.
  Class name should be the file name without extention.
\*============================================================================*/
namespace device;
class dummy{
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
  Dummy device handler

  Device specifics:
  The device handler simulates communication with a device.
  No real action is taken.  
\*============================================================================*/
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
  $description="Dummy device handler";
  $response=["error"=>""];

  // Execute commands
  if(!is_array($command)) $command=[$command];
  foreach($command as $cmd){
    switch ($cmd){
      // Meta commands
      case "capabilities":
        $response['result']=["set","get","status","list","diagnostic"];
        break;
      case "description":
        $response['result']=$description;
        break;
      default: 
        // Test on-line status
        switch (substr($cmd,0,strpos($cmd." "," "))){
          case "status": 
          case "get": 
            $response['state']='on';
            break;
          case "set": // set <state>
            $response['state']=substr($cmd,strpos($cmd," ")+1);
            break;
          case "list": // List connected device IDs
            $response['result']=["Dummy 1",$unit_id];
            break;
          case "diagnostic": 
            switch (substr($cmd,strpos($cmd," ")+1)){
              case "1": // curent state
                $response['result']="All is well";
                break;
              case "2": // Report error
                $response['result']="No errors found";
                break;
              case "3": // Any error 
                $response['result']="This device has a flawless record of operation";
                break;
              case "4": // stress test
                $response['result']="There is a slight flutter in the force, when operating. Nothing to worry about though";
                break;
                
            
            }
            break;
          default:
            $s=substr($cmd,0,strpos($cmd." "," "));
            $response['error']="The $description did not recognize this command: '$cmd'";
            break;
        }
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
  The server is not fully initialised when this script is running. You can not 
  expect all services to respond.  
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

 
}
?>

