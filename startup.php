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
  If this file is called on start up
\*============================================================================*/
if($_SERVER['REQUEST_METHOD']=="direct"
  && !@$_SERVER['HTTP_CONNECTION']){
  ob_start();

  // Exit action
  function shutdown(){
    echo "\nScript ended ", date("Y-m-d H:i:s")."\n";
    file_put_contents('var/initialise.log',  ob_get_contents());
  }
  register_shutdown_function('shutdown');
  
  echo "starting ". date("Y-m-d H:i:s")."\n";

  // Class file loader
  spl_autoload_register(function ($class_name) {
    @include_once "{$_SERVER['DOCUMENT_ROOT']}/" .strtr($class_name,'\\','/') . '.php';
  });
  
/*============================================================================*\
  If this file is called as a page
\*============================================================================*/
}else{
  // Running as a page etc.
  // exit;
  echo "<pre> Test mode\n";
}

/*============================================================================*\
  Catch all output and errors and dump it to initialize.log
\*============================================================================*/
function ErrorHandler($errno, $errstr, $errfile, $errline){
  global $if;
  //echo "Initializing ",substr($if,strpos($if,"/"),strrpos($if,".")),"\n";
  echo "ERROR [$errno] $errstr on line $errline in file $errfile \n";
  /* Don't execute PHP internal error handler */
  return true;
}
set_error_handler("ErrorHandler");


/*============================================================================*\
  Run Initialization
\*============================================================================*/
//make_word_list();
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
    // Read interaction definition file
    $ia=json_decode(file_get_contents($if),true);
    
    echo "\n".substr($if,strpos($if,"/"),strrpos($if,".")-strlen($if)).":";
    if(is_array($ia) && !empty($ia['device'])){
      // Preset undefined to null
      $ia['unit id']=isset($ia['unit id'])?$ia['unit id']:'';
      $ia['cmd']=is_array($ia['cmd'])?$ia['cmd']:['init'=>''];
      $cmd='';
      
      // Make auto loader get the class 
      $name=strtr($ia['device'],"/","\\");
      if(!class_exists($name,true)){
        echo "\n  The device handler '" . $ia['device'] . "' dose not exists";
        continue;  
      }
      $device=new $name;
      // Verify handler
      if(!method_exists($device,"handler")){
        echo "\n  The file '" . $ia['device'] . "' dose not contain a valid device handler method";
        continue;  
      }
      if(!method_exists($device,"initialize")){
        echo "\n  The file '" . $ia['device'] . "' dose not contain a valid device initialize method";
        continue;  
      }

      echo " {$ia['device']} {$ia['unit id']}:";
      // Initialize device 
      $device->initialize($ia['unit id']);

      // Make array of init commands
      if(!empty($ia['cmd']) && !empty($ia['cmd']['init'])){
        // Make sure init command is an array, even of one
        if(!is_array($ia['cmd']['init'])) 
          $ia['cmd']['init']=[$ia['cmd']['init']];

        // Collect init commands
        foreach($ia['cmd']['init'][0] as $code)
          $cmd[]=trim(strtolower($code));

        // Execute init commands
        $device->handler($cmd,$ia['unit id']);
            
        if(is_array($cmd)) foreach($cmd as $command)
          echo "\n  {$command}";
      }
    }
  }
  
  // Search all sub directories as well
  if(count($a)>0) foreach($a as $subpath){
    search_interactions($subpath.'/',$ltrim);
  }
}

?>

