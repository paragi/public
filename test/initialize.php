<!DOCTYPE HTML>
<html>
<head> 
<meta charset="utf-8" />
<title>Test page</title>
<link rel="shortcut icon" href="/favicon.ico" >
<link rel="icon" href="/favicon_big.ico" type="image/ico" >
<link rel="stylesheet" type="text/css" href="/theme/theme.css" />

<style>
</style>

</head>
<body>
<h1>Init test</h1>
<?php
/*============================================================================*\
  Initialise
  
  This script is executet opon server start.
  
  Note:
    Output is dumped into initialize.log
    there is no session
    $_SERVER variables are for the most part NOT set
  
\*============================================================================*/

/*============================================================================*\
  Catch all output and errors and dump it to initialize.log
\*============================================================================*/
ob_start();
define('_DEV_DEBUG',true);
echo "<pre>".date("Y-m-d H:i:s")."\n";

function ErrorHandler($errno, $errstr, $errfile, $errline){
  global $if;
  echo "Initializing ",substr($if,strpos($if,"/"),strrpos($if,".")),"\n";
  echo "ERROR [$errno] $errstr on line $errline in file $errfile \n";
  /* Don't execute PHP internal error handler */
  return true;
}
set_error_handler("ErrorHandler");

function exception_handler($exception) {
  global $if;
  echo "Initializing ",substr($if,strpos($if,"/"),strrpos($if,".")),"\n";
  echo "Uncaught exception: " , $exception->getMessage(), "\n";
  return true;
}
set_exception_handler('exception_handler');

function shutdown()
{
  echo 'Script ended ', date("Y-m-d H:i:s")."\n";
  //file_put_contents('var/initialise.log',  ob_get_contents());
  echo ob_get_clean();
}
register_shutdown_function('shutdown');

/*============================================================================*\
  Start the search for interactions that has devices that need initialization 
\*============================================================================*/
search_interactions("context/"); 
exit;


/*============================================================================*\
  Find all interactions and initialize the devices
  
  The code must be rather robust and resilient to errors.
  When errors occurres, try to continue.
\*============================================================================*/
// Search paths for interactions
function search_interactions($path){
  global $if;
  $a=glob($path.'*', GLOB_ONLYDIR|GLOB_NOSORT);
  $ltrim=strlen($path)-1;
  
  // Search this directory for ia-dat files  
  foreach(glob($path.'*.ia-dat',GLOB_NOSORT) as $if){
    echo "looking at: ",substr($if,strpos($if,"/"),strrpos($if,".")),"\n";

    // Read interaction definition file
    $ia=json_decode(file_get_contents($if),true);
    
    if(is_array($ia) && !empty($ia['device'])){
      // Preset undefined to null
      $ia['unit_id']=isset($ia['unit_id'])?$ia['unit_id']:'';
      $ia['cmd']=is_array($ia['cmd'])?$ia['cmd']:['init'=>''];
      $cmd='';
      
echo "Found device: ",(!empty($ia['device'])?$ia['device']:'-')."\n";
echo "unit_id: ",(!empty($ia['unit_id'])?$ia['unit_id']:'-')."\n";
echo "Init cmd: ",print_r(!empty($ia['cmd']) && !empty($ia['cmd']['init']) ?$ia['cmd']['init']:'-',true)."\n";

      // Get device handler file 
      $filename="device$ia[device].php";
      if(!file_exists($filename)){
        echo "Unable to locate device handler file '$filename'\n";
        return;
      }

      // Make array of init commands
      if(!empty($ia['cmd']) && !empty($ia['cmd']['init'])){
        // Make sure init command is an array, even of one
        if(!is_array($ia['cmd']['init'])) 
          $ia['cmd']['init']=[$ia['cmd']['init']];

        // Collect init commands
        foreach($ia['cmd']['init'] as $code)
          $cmd[]=trim(strtolower($code[0]));
      }
          
      // Execute actions
      $response=init_device($filename,$ia['unit_id'],$cmd);
      
      if($response['error']) echo $response['error'];

echo "Response: ",print_r($response,true),"\n---------------------------\n";      
      
    }
  }
  
  // Search all sub directories as well
  if(count($a)>0) foreach($a as $subpath){
    search_interactions($subpath.'/',$ltrim);
  }
}

// Wrapper for device handler init call
function init_device($filename,$unit_id,$cmd){
  require $filename;
  if(is_callable($initialize)){ 
    $response=$initialize($unit_id);
echo "Command: Init. Response: ",print_r($response,true),"\n"; 
  }
    
  if(is_array($cmd) && is_callable($handler)) 
    foreach($cmd as $command){
      $response=$handler($command,$unit_id);
echo "Command: $command. Response: ",print_r($response,true),"\n"; 
    }  
  return $response;    
}

//print_r($GLOBALS);
?>

</pre>
</body>
</html>
