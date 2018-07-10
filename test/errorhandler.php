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
<h1>Error test page</h1>
<pre>
<?php
function shutdownHandler(){
  echo "in Shutdown:";
  $lasterror = error_get_last();
  print_r($lasterror);
  switch ($lasterror['type']){
    case E_ERROR:
    case E_CORE_ERROR:
    case E_COMPILE_ERROR:
    case E_USER_ERROR:
    case E_RECOVERABLE_ERROR:
    case E_CORE_WARNING:
    case E_COMPILE_WARNING:
    case E_PARSE:
      handle_error(
        $lasterror['type']
       ,$lasterror['message']
       ,$lasterror['file']
       ,$lasterror['line']
       ,null
     );
  }
}

// PHP Error handler function
function handle_error($errlvl, $errstr, $errfile, $errline, $errcontext ){
  global $_error_exit,$_error_seriousness;
  static $count_calls =0;
  echo "\n in error handler: $errstr, $errline";
  
  // Check if error was suppressed with the @-operator
  if (0 === error_reporting()) return true;
 
  switch ($errlvl) {
    case E_ERROR:
    case E_CORE_ERROR:
    case E_COMPILE_ERROR:
    case E_PARSE:
      if(!isset($_error_seriousness)) $_error_seriousness = 3;
      break;
    case E_USER_ERROR:
    case E_RECOVERABLE_ERROR:
      if(!isset($_error_seriousness)) $_error_seriousness = 2;
      break;
    case E_WARNING:
    case E_CORE_WARNING:
      if(!isset($_error_seriousness)) $_error_seriousness = 1;
      break;
    case E_COMPILE_WARNING:
    case E_USER_WARNING:
    case E_NOTICE:
    case E_USER_NOTICE:
    case E_STRICT:
    default:
      return true;
  }

  // repport only primary error
  if($count_calls++ > 0) return true;

  // Make new repport
  $error_repport['message']=$errstr;
  $error_repport['stack']=debug_backtrace();

  foreach($error_repport['stack'] as $key => $entry){
    // Remove unwanted backslashes
    if(!empty($error_repport['stack'][$key]['file']))
      $error_repport['stack'][$key]['file'] = strtr($entry['file'],"\\",'');
    // Remove error handlers from stack    
    if( false && $entry['function'] == 'trigger_error' 
      || $entry['function'] == 'handle_error'
      || $entry['function'] == 'shutdownHandler'){ 
      unset($error_repport['stack'][$key]);
    }
  }
  array_splice($error_repport['stack'], 0, 0); // Reorder keys
  
  if(count($error_repport['stack'])>0 && !empty($error_repport['stack'][0]['file'])){
    $error_repport['file'] = str_replace($_SERVER['DOCUMENT_ROOT']."/",""
                                        ,$error_repport['stack'][0]['file']);
    $error_repport['line'] = $error_repport['stack'][0]['line']; 
  }else{
    $error_repport['file'] = $errfile; 
    $error_repport['line'] = $errline;   
  }
  
  // Remove document root path from file name
  $error_repport['file'] = str_replace($_SERVER['DOCUMENT_ROOT'],""
                          ,$error_repport['file']);
    
  $error_repport['seriousness']=(isset($_error_seriousness)?$_error_seriousness:1);
  $error_repport['context']=$errcontext;
  //$error_repport['dump']=$GLOBALS;
 
  echo "<pre>Error perort:" . print_r($error_repport,true) ."</pre>";

  echo "Program error: $errstr";
  if($error_repport['file']) echo " in $error_repport[file]";
  if($error_repport['line']) echo " on line $error_repport[line]";
  

  // Output to stderr and let the server log the error
  file_put_contents("php://stderr","X@@@" 
    . json_encode($error_repport,JSON_PRETTY_PRINT + JSON_NUMERIC_CHECK));

  // Exit php
  if($_error_exit) exit;

 // Return true to disable PHP error handling
  return true;
}

// Activate PHP error repporting through handler function
set_error_handler("handle_error",E_ALL);
register_shutdown_function("shutdownHandler");
error_reporting(0); // Has no effect on error_handler
ini_set('display_errors', 'Off');


echo "running script\n";

asdfg 
//$a=1/0;
//throw new Exception('Uncaught funny Exception');
echo "Ending script\n";

?>
</pre>
</body>
</html>
