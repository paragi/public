<?php
  // ================================================================================
  // Server Sendt event handler

  // This script servs event updates to a page in a webbrops.r.
  // The page supply a watchlist containing the full path to events and interactions
  // that the page displays. When a change occures, this script calls the appropriate
  // render function and send the newly created HTML (usually a tile) to the page.
  // The page then inserts the HTML code into the propper DOM element.

  // A watchlist is an array where the key is the name of the render function that
  // contains an arrar of interaction names.
  // A watchlist can have any number of render functions. A renderfunction

  // Only correctly formatted event output may be sent to the page. All other output
  // captured

  // This script does not terminate on completion; it runs as long as the page are
  // show in the brops.r.
  // ================================================================================
  
  //define("_DEBUG",true);
  if(defined("_DEBUG") && !isset($_SERVER['HTTP_REFERER'])){
    define("_OB_OFF",true);
    //define("_ABD_DEBUG",true);
  }

  // Buffer all outputs, as any output other then correctly formatted are disruptive.
  $output_buffer="";

  // ================================================================================
  // This script runs a never ending loop.
  // The brops.r will reinitiate a connection if broken.
  // Since PHP is not fit for long running time its practical to let the script die after a time
  // and let the brops.r restart it.
  // Set script start time and run time in seconds. (600?) server might have a shorter run timeout
  // ================================================================================
  $start_time=time();
  $run_time=10;

  $responce_time=1000000; // parameter for usleep (micro sedonds)

  // ================================================================================
  // -- Security section. Be carefull ---
  // ================================================================================
  session_start();
  // Session start blocks writing to sessions. There fore it has to be released
  session_write_close();

  // ================================================================================
  // -- Security section. Be carefull ---
  // ================================================================================
  session_start();
  // Session start blocks writing to sessions. There fore it has to be released
  session_write_close();

  // Exit on all irregularities
  if(!@$_SESSION['TID']
    || !$_SESSION['trust']
    || !$_SESSION['watchlist']
    || !@$_GET['watchlist']
    || @$_SESSION['REMOTE_ADDR'] <> @$_SERVER['REMOTE_ADDR']
    || @$_SESSION['HTTP_USER_AGENT']<>@$_SERVER['HTTP_USER_AGENT']
    || @$_SESSION['TID']<>@$_COOKIE['TID']
    || ( !defined("_DEBUG") && !isset($_SERVER['HTTP_REFERER']) )
  ){
    //header( $_SERVER['SERVER_PROTOCOL']." 404 Not Found", true, 404 );
    //ob_end_flush();
    //die("<b>404 File not found!</b>");
  }

  // ================================================================================
  // -- End of security section. ---
  // ================================================================================

  if(isset($_SERVER['HTTP_REFERER'])){
    // Server Sent Event header
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
  }else{
    // NB: REMOVE on production version!
    // called directly: must be for test reasons.
    echo "<!DOCTYPE HTML>\n<html>\n<head>\n<meta charset=\"utf-8\" />\n</head>\n<body>\n\n";
    echo "<H1>Server Sendt Event Handler</H1>";
    echo "Directly called<pre>\n";
    $run_time=5;
  }

  // ================================================================================
  // -- End of security section. ---
  // ================================================================================

  //require_once "basics.php";

  // ================================================================================
  // Send event to JS event message handler
  // ================================================================================
  function sse($data){
    global $output_buffer;

    if(!defined("_OB_OFF"))
      while (ob_get_level()>0)
        $output_buffer.=ob_get_clean();
    // Send data. Each line is a separate new data message, but as the same instance (sse event)
    if(!$_SERVER['HTTP_REFERER'])
      // NB: REMOVE on production version!
      echo "data:". str_replace("\n","\ndata: ",stripcslashes($data)) ."\n\n";
    else
      echo "data:". str_replace("\n","\ndata: ",$data) ."\n\n";

    // Flush outrput buffer. 
    // (PHP 5.4) ob_end_flush must be called even if it make no sense. Otherwise it doesn't work
    if(!defined("_OB_OFF")){
      @ob_end_flush();
      flush();
      ob_start();
    }
  }

  // ================================================================================
  // Render tile
  // include script to render tile, in a closed scope
  // ================================================================================
  function render_tile($context,$ia_name,$state){
    global $interaction;

    // Load IA datafile
    if(!is_array($interaction[$context.$ia_name])){
      $filename="./context".$context.$ia_name.".ia-dat";
      if(file_exists($filename)){
        $interaction[$context.$ia_name]=
          json_decode(str_replace( "\n", "",file_get_contents($filename)),true);
      }
    }
    
    //Check access rights
    if(!isset($interaction[$context.$ia_name]['get sensitivity']) 
      || $_SESSION['trust']<$interaction[$context.$ia_name]['get sensitivity']){
      // Remove interaction from list
      unset($interaction[$context.$ia_name]);
      return null;
    }

    // Add state
    if($state)
      $interaction[$context.$ia_name]['state']=$state;

    // Name of render script
    $filename="./context".$context."tile.php";
    if(file_exists($filename)){
      // Collect output from script and return result
      ob_start();
      include $filename;
      return ob_get_clean();
    }
    return null;
  }

  // ================================================================================
  // Set the Reconnection-timeout
  // The brops.r attempts to reconnect to the source roughly 3 seconds after connection is closed.
  // to set, send "retry:", followed by the number of milliseconds to wait before trying to reconnect.
  // ================================================================================
  print "retry: 100\n\n";
  if(!defined("_OB_OFF")){
    @ob_end_flush();
    flush();
    ob_start();
  }


  // ================================================================================
  // Make a list of contexts and interactions to watch for
  // ================================================================================
  if(is_array($_GET)) foreach($_GET as $elm=>$event){
    //Derive context    
    if($event[0]=="/"){
      $e=strrpos($event,"/");
      $wl[$elm]['context']=substr($event,0,$e+1);
      // derive interaction
      if(strlen($event) >$e+1)
        $wl[$elm]['interaction']=substr($event,$e+1);
    }else{
      $wl[$elm]['interaction']=$event;
    }
    // Check priviliges and remove if not allowed
  }

  // NB: REMOVE on production version!
  if(defined("_DEBUG") && !isset($_SERVER['HTTP_REFERER'])){
    print_r($wl);
    print_r($interaction);
  }


  // ================================================================================
  // Register this live session 
  // All pages registers watchlist. By maintaning an "alive" session list
  // ================================================================================
/*
  do{
    //try to reuse session alive id
    if($_SESSION['alive_id']){
      // update
      $responce = adb_rest("PATCH","/_api/document/$_SESSION[alive_id]",""
        ,json_encode(array("time"=>time()), JSON_NUMERIC_CHECK));
      if(!$responce['error']) break;
    }
    // Create new session alive id
    $responce = adb_rest("POST","/_api/document","collection=session",'{ "time" : '.time().' }');  
    $_SESSION['alive_id']=$responce['_id'];
exit;
  // Purge deas sessions
  $responce=adb_rest("PUT","/_api/simple/remove-by-example",""
    ,'{ "collection": "sesson", "example" : { "acknowledged" : true } }' );
  }while(false);
 */   
  // ================================================================================
  // Monitor events
  // loop as long as the page lives
  // ================================================================================

  // Get last event key
  $responce=adb_rest("PUT","/_api/simple/last","",'{"collection": "event","count" : 1 }');
  while(!$responce['error']){
    // Close connection at intervals. Client will reconnect
    if($start_time+$run_time<time()) break;
    // Set sequence number of last processed event
    if(isset($responce['result'][0]['_key']))
      $sequence=$responce['result'][0]['_key'];

    // Look for new events
    $responce=adb_rest("POST","/_api/cursor",""
      ,'{"query" : "FOR e IN event FILTER TO_NUMBER(e._key) > '.$sequence
      .' SORT TO_NUMBER(e._key) ASC LIMIT 1 RETURN e "
      ,"count": true
    }');

    // When idle; sleep
    if(!$responce['count']){
      usleep($responce_time);
      continue;
    }


    // Skip events that are for a specific other session
    if(isset($responce['result'][0]['session id']) && $responce['result'][0]['session id']!=session_id())
      continue;

    // Scan watch list for match of event
    $matched=false;
    if(is_array($wl)) foreach($wl as $elm=>$att){
      // Filter out events that dosen't match watch list criteria
      if($att['interaction']!="all"){
        if($att['context']!=$responce['result'][0]['context']) continue;
        if(isset($att['interaction'])){
          if($att['interaction']!=$responce['result'][0]['interaction']) continue;
          if($responce['result'][0]['state']===null) continue;
        }
      }else{
        // catch all: send text only
        unset($msg);
        $msg['element']=$elm;
        $msg['message']=str_replace("\n","</br>",print_r($responce['result'][0],true));

         /*        
        // Render tile
        $msg['html']=render_tile(
           $responce['result'][0]['context']
          ,$responce['result'][0]['interaction']
          ,$responce['result'][0]['state']
        );
        */
        sse(stripslashes(json_encode($msg, JSON_NUMERIC_CHECK)));
        continue;
      }

      $matched=true;
      // Render tile
      $html=render_tile($wl[$elm]['context'],$wl[$elm]['interaction'],$responce['result'][0]['state']);
      if(!$html) continue;

      // Send update to client page
      sse(json_encode(array('element'=>$elm,'html'=>$html), JSON_NUMERIC_CHECK + JSON_PRETTY_PRINT));
    }
    if($matched) continue;

    // Force display to set new context or (just session id)
    if(!isset($responce['result'][0]['interaction'])
      && (!isset($responce['result'][0]['action'])||  $responce['result'][0]['action']=="reload")
      && $responce['result'][0]['session id']==session_id()
    ){
      sse(json_encode(array("element"=>"reload"), JSON_NUMERIC_CHECK + JSON_PRETTY_PRINT));
      continue;
    }

    // Set alarm state
    if($responce['result'][0]['action']=="red alert"
      || $responce['result'][0]['action']=="yellow alert"
      || $responce['result'][0]['action']=="green alert"
      || $responce['result'][0]['action']=="blue alert"
      || $responce['result'][0]['action']=="alert off"
    ){
      sse(json_encode(array("element"=>"reload"), JSON_NUMERIC_CHECK + JSON_PRETTY_PRINT));
      continue;
    }

    // NB: REMOVE on production version!
    if(defined("_DEBUG") && !isset($_SERVER['HTTP_REFERER'])){
      echo "Ignoreing unknown type of event:\n";
      print_r($responce['result'][0]);
    }
      
  };

  // NB: REMOVE on production version!
  if(defined("_DEBUG") && !isset($_SERVER['HTTP_REFERER'])){
    echo "</pre>SSEH Ended";
  }




?>
