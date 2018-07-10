<?php
/*============================================================================*\
Basic include file for the GOS project\*

Containing generic function for:
 - Database access
 - Error handling
 - GOS simple command interface

\*============================================================================*/
define('DIR_BASE',__DIR__ . DIRECTORY_SEPARATOR);
/*============================================================================*\
  Class file loader
\*============================================================================*/
spl_autoload_register(function ($class_name) {
  @include_once DIR_BASE . strtr($class_name,'\\', DIRECTORY_SEPARATOR) . '.php';
});

/*============================================================================*\
  PHP session file store in public/var
\*============================================================================*/
ini_set('session.use_cookies', false);
ini_set('session.gc_divisor', '1');
ini_set('session.gc_probability', '1');
require "php_session.php";
session_set_save_handler(new \php_session\SessionHandler(), true);
session_start();

/*============================================================================*\
  Set up rocket-store Database
  and load miscellaneous settings
\*============================================================================*/
require "rocket-store.php";
$rsdb = new \Paragi\RocketStore([
    "data_storage_area" => DIR_BASE . "var" . DIRECTORY_SEPARATOR . "rsdb"
  , "data_format" => RS_FORMAT_JSON
]);

$db_reply = $rsdb->get("setting","miscellaneous");
if($db_reply['count'] == 1)
  $settings = $db_reply['result']['miscellaneous'];

/*============================================================================*\
Error handler must work even when the database is off-line.

Seriousness indicate
  1 minor (script error)
  2 function fatal error (eg. device failure)
  3 section fatal error (eg. central component faliure)
  4 system wide reduced functionality (eg. data base failure)
  5 system fatal error

  @@@{ marks the beginning og the PHP error handler output.
  PHP fatal error messages precedes the error handler output.
  the PHP fatal error messages should be used as error message
\*============================================================================*/
function shutdownHandler(){
  $lasterror = error_get_last();
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
//      || $entry['function'] == 'error'
      || $entry['function'] == 'shutdownHandler'){
      unset($error_repport['stack'][$key]);
    }
  }
  array_splice($error_repport['stack'], 0, 0); // Reorder keys

  if(count($error_repport['stack'])>0 && !empty($error_repport['stack'][0]['file'])){
    $error_repport['file'] = str_replace(DIR_BASE, "", $error_repport['stack'][0]['file']);
    $error_repport['line'] = $error_repport['stack'][0]['line'];
  }else{
    $error_repport['file'] = $errfile;
    $error_repport['line'] = $errline;
  }

  // Remove document root path from file name
  $error_repport['file'] = DIRECTORY_SEPARATOR . str_replace(DIR_BASE, "", $error_repport['file']);

  $error_repport['seriousness']=(isset($_error_seriousness)?$_error_seriousness:1);
  $error_repport['context']=$errcontext;
  //$error_repport['dump']=$GLOBALS;

  echo $errstr;
  if($error_repport['file']) echo " in <b>$error_repport[file]</b>";
  if($error_repport['line']) echo " on line <b>$error_repport[line]</b>";
  echo "\n";

  // Output to stderr and let the server log the error
  file_put_contents("php://stderr","@@@"
    . json_encode($error_repport,JSON_PRETTY_PRINT + JSON_NUMERIC_CHECK));

  // Exit php
  if($_error_exit) exit;

 // Return true to disable PHP error handling
  return true;
}

// Activate PHP error repporting through handler function
if(@$settings['show php errors']['value'] != "on" && false){
  set_error_handler("handle_error",E_ALL);
  error_reporting(0); // Has no effect on error_handler
  ini_set('display_errors', "0");
}
register_shutdown_function("shutdownHandler");

// Error function for script generated exception
function error($seriousness,$text,$exit=true){
  global $_error_exit,$_error_seriousness;
  if(empty($text)){
    echo "Error triggered without text<br>\n";
    return;
  }
  if(!is_numeric($seriousness)) $seriousness = 1;
  $_error_exit=$exit;
  $_error_seriousness=$seriousness;
  handle_error(E_USER_ERROR, $text, '', '', '');
//  trigger_error($text,E_USER_ERROR);
}

/*============================================================================*\
  Include a user script file Function

  Functions are global in PHP And undeleteable (almost)
  Using an immidiate function, stored in a variable, makes a realativly safe
  closure for inclusion of tile.php and index.php files, with a very local scope
  and minimises the risk of name collissions.

  Parameters:
  $fn:        file name to include
  $context:   full context


  Retur array of:
  html:       Complete code to display, including container/tile DIV with ID and
              action

  js:         javascript to run once, when the page is connected to page services

  watch_list: Array where the index is the element id to receive updates and the
              value is the event name

  The files require these variables:

  $interacton: an array indexed by interaction name,  containing interactiondata
               for the ia-dat file, and the current state.

  The state is initially off-line. a subsequest call to page services will
  assertain the actiual state of the interaction.

\*============================================================================*/
$include=function($fn,$context,$state="",$argument=''){
  $filename = DIR_BASE . "context$context$fn";
  if(!file_exists($filename))
    return false;

  $TRUST = $_SERVER['SESSION']['trust'];
  $trust = $TRUST;

  // Look for interactions
  foreach(glob(DIR_BASE . "context$context*.ia-dat",GLOB_MARK | GLOB_NOSORT) as $iafn){

    // Make full path interaction name
    $ia=substr($iafn,strpos($iafn,"/",2),-7);
    $interaction[$ia]=json_decode(str_replace( "\n", "",file_get_contents($iafn)),true);

    if(!isset($interaction[$ia]['cmd'])) continue;

    // If viewing is not allowed, don't show interaction at all
    if(@$interaction[$ia]['cmd']['get'][1] > @$TRUST)
      continue;
  }

  ob_start();
  try {
    include $filename;
  } catch (Exception $e) {
    error(1,$e->getMessage(),false);
  }
  // Make response
  $response=[];
  $response['html']=ob_get_contents();
  ob_end_clean();

  if(isset($watch_list) && is_array($watch_list))
    $response['watch_list']=$watch_list;
  if(isset($js) && is_array($js))
    $response['js']=$js;

  return $response;
};

/*============================================================================*\
  Automated tile generation.

  Make the best posible bid on a presentaion of an interaction, that has no user
  defined tile, based on tWhe interaction data file and context.

  parameter:
    $ia_data: array of ia data
    $ia:      Full interaction name
    $state:   State of device or null
    $use_ctx: If true, allow use of context icon, if icon is not specified.
              (Used for displaying sub context interactions)

  Return:
    array of:
    html:       Outer HTML code to display tile in current state
    js:         Javascript to be run on initialization
    watch_list: Array where the index is the element id to receive updates and
                value is the event name (often the same)

\*============================================================================*/
function generate_tile_from_iadata($ia_data,$ia,$state="off-line",$use_ctx=false){
  // Verify parameters
  if(!is_array($ia_data) || empty($ia)) return [];

  $view_only=false;
  $action="";

  if(empty($state)) $state = 'off-line';

  // Get some names to use
  $names=array_slice(explode("/",$ia),-2,2);
  $title="$names[0] $names[1]";

  // Find background image to use
  // Use icon defined in interaction data
  if(!empty($ia_data['icon'])){
    $icon = image_path(sprintf($ia_data['icon'],$state));

  // Use presentation
  }elseif(!empty($ia_data['present'])){
    ;// do nothing. The client has presentation lib to deal with it

  // Explore other options
  }else{
    // Use interaction name as icon
    $list = glob("/theme/{$names[1]}[*].png");
    if(!isset($list[1]) && !empty($ia_data['type']))
      $icon = image_path(sprintf("$ia_data[type][%s].png",$state));

    // Use context icon
    elseif( $use_ctx)
      $icon = image_path("$names[0]-ctx.png");
  }

  // Set action
  if(!empty($ia_data['type']) && $ia_data['type']=="switch"){
    if($state!='on' && isset($ia_data['cmd']) && !empty($ia_data['cmd']['on'])){
      $action="onclick=\"cmd('$ia on');\"";
      $hint="Turn {$names[1]} on";
    }elseif($state!='off' && !empty($ia_data['cmd']['off'])){
      $action="onclick=\"cmd('$ia off');\"";
      $hint="Turn {$names[1]} off";
    }
  }else{
    $action="onclick=\"cmd('$ia get',function(res){present(res.event,res.state);});\"";
    $view_only=true;
  }

  if(empty($hint)) $hint="$names[0] $names[1]";
  if(empty($action)) $view_only=true;

  // Make display code
  if(!empty($icon)){
    $result['html']="<div class=\"tile\" title=\"$hint\" id=\"$ia\" {$action}";
    $result['html'].=" style=\"background-image: url($icon);\" alt=\"$title.\">\n";
    if($view_only)
      $result['html'].="<img src=\"/theme/view-only.png\" class=\"tile_flag\">";
    $result['html'].="</div>\n";

  // Make a presentation tile
  }elseif(!empty($ia_data['present'])){
    // Add presentation ID and sin a tile DIV container
    $result['html']="<div class=\"tile\">\n";
    $result['html'].="<canvas id=\"$ia\" style=\"width:inherit; height:inherit;\"";
    $result['html'].=" title=\"$hint\" {$action}></canvas>\n";
    $result['html'].="</div>\n";

    // Create javascript code to initialise presentation
    $result['js']="present('$ia','','".$ia_data['present']."');\n";
    // Add callback funtion for value updates
    $result['js'].="ps.on('$ia',function(res){present(res.event,res.state);});\n";

  // Make a textual representation
  }else{
    $result['html']="<div class=\"tile\" title=\"$hint\" id=\"$ia\" {$action}";
    // Center text
    $result['html'].=" style=\"display: table; text-align: center;\">\n";
    $result['html'].="<span style=\"display:table-cell; vertical-align:middle; text-align: center; \">";
    $result['html'].="$title<br>$state\n";
    // Add warning of missing file
    if(!empty($ia_data['icon']) && (empty($icon) || $icon != $ia_data['icon'])){
      $i=sprintf($ia_data['icon'],$state);
      $result['html'].="<p>Icon file \"{$i}\" is missing</p>\n";
    }
    $result['html'].="</span></div>\n";
  }

  // Apply watch list
  $result['watch_list'][$ia]=$ia;

  return $result;
}

/*============================================================================*\
  Image path

  Get theme URL for an image file

  string $full_url = image_path(string $image_file_name [,string $theme])

  Return the full url path to the image file, ajusted for current theme.
  Or null, if a suitable image does not exists.
\*============================================================================*/
function image_path($image,$theme=''){
  if(empty($image)) return null;

  do{

    if(!empty($theme)){
      $path = "theme/".$theme."/";
      if(file_exists(DIR_BASE . $path . $image)) break;
    }

    if(!empty($_SERVER['SESSION']['theme'])){
      $path = "theme/" . $_SERVER['SESSION']['theme'] . "/";
      if(file_exists(DIR_BASE . $path . $image)) break;
    }

    $path = "theme/";
    if(file_exists(DIR_BASE . $path . $image)) break;

    $image = "default.png";
    if(file_exists(DIR_BASE . $path . $image)) break;

    $path = '';
    $image = '';
  }while(false);

  return  $path . $image;
}
// ================================================================================
// -- End of global functions section ---
// ================================================================================
?>
