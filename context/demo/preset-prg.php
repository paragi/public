<?php
/*============================================================================*\
  Program: preset

  The code in here is executed as a command.
  
  Populated variables:
  If this program is activated as a reation to an event, the event array is
  pulated with the triggering event and result of the operation that initiated it.
  
    event:
      name: the triggering event
      reply: The reply given to the command that initiated the event
      state: State of interaction, if any
      error: any error message that occured during execution of the command
      result: An array of results of the command, if any
  
  If this program is activated by a timer, xxxxxxxxxxxxxxxxxx
  
  Session:
  If this program is activated by a command, the terminals session is active.
  Otherwise a temporary shortlived session is in effect, for the duration of 
  this program execution.
    
  $_SERVER{'session']:
    trust: Number 1-100%
    userAgent: 'Internal response' or browser agenbt string
    origin: string
    ip: ip of requester
 

  Return values
    To return values, you can popuilate the $response array. 
    These keys are used:
      reply: typically 'ok', 'failed', 'working'
      error: Null or a descriptive error message
      state: Optional. a string or numbner
      result: optional. An array of results

  Ouput:
    Any output (from echo etc.)  is collected af copied to $response['html'] 

  Errors are catched and logged. Execpt from thouse you return in $response['error']
  
  Event
    When the execution of this program ends, an event is emitted, with the name 
    of this program, in this context, and the output attached.
  
  useful function:
    array result = command($command);
    string full url = image_path(<image name>)
    array result = services(<service>,<function>,<data>);
    error($seriousness,$text,$exit=true)

  to use the cmd function, you must include command.php:
    require_once "$_SERVER[DOCUMENT_ROOT]/command.php";
    
\*============================================================================*/


/*============================================================================*\
  Access security. 
  Think carefull about who has access to this funtion and how sensitive it is!
\*============================================================================*/
$read_sensitivity=20; 
$write_sensitivity=20;
if ($_SERVER['SESSION']['trust']<$write_sensitivity) return;

/*============================================================================*\
  Access security. 
  Think carefull about who has access to this funtion and how sensitive it is!
\*============================================================================*/
//require_once "$_SERVER[DOCUMENT_ROOT]/command.php";

command('say hi');
//command('/demo/light4 on');
command('/demo/light on');
command('/demo/ceiling/light on');
command('/demo/desk/light on');

//$response = command('/demo/light3 toggle');
$response['state'] = on;
$response['result'] = $arguments;

?>
