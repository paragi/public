<?php
/*============================================================================*\
  Serverside services (c) Paragi Aps, Simon Rigét 2016
  
  array response services(string $service, string $func, mixed $data)
  
  returns an array with the result of the request:

    error:      Empty on success or an error message
    result:     array result of querry 


  Services is a SmartCore server remote procedure. This function implements 
  a clinet connection through websockets to server services.
  
  The connection remains open, for the duration of the script or until closed.
   
\*============================================================================*/
require_once "$_SERVER[DOCUMENT_ROOT]/websocket_client.php";

/*============================================================================*\
  array services(string $service, string $func, mixed $data) 
 
  service
    Requested service module.
    values are: datastore, command, timer, serverinfo, event,  echo
    
  func
    Function to perform. Specific to the service requested.
    
  data (optional)
    Additional data to the function. It can be a string or an array.
  
  asyncronos_execution
    if true, request is send to the service module, but the function returns 
    before an answer is recieved.
    
  returns a reaponse record:
    reply:  Verbal reply to user. Simpel forms are: ok, Unable to comply, working.
    error:  Optional. Explanation of failure
    result: Optional. 
    state:  Optional.
    cmd_id: returned from the request


  Services handle communication with the smartcore services module, through a 
  websocket connection.
  Only with a valid sessions, will the srequest be accepted.
\*============================================================================*/
function services($service,$func,$data='',$asyncronos_execution=false){
  static $sp,$token="";
  static $json_error_str = [
     JSON_ERROR_NONE => 'No error has occurred.'
    ,JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded.'
    ,JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON.'
    ,JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded.'
    ,JSON_ERROR_SYNTAX => 'Syntax error.'
    ,JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded.'
    ,JSON_ERROR_RECURSION => 'Recursive references.'
    ,JSON_ERROR_INF_OR_NAN => 'NAN or INF values found.'
    ,JSON_ERROR_UNSUPPORTED_TYPE => 'a type that cannot be encoded.'
    
   // PHP 7 
  //  ,JSON_ERROR_UTF16 => 'Malformed UTF-16 characters, possibly incorrectly encoded.'
  //  ,JSON_ERROR_INVALID_PROPERTY_NAME => 'A property name that cannot be encoded was given'
    ];
  
  // Catch services that are not done in the server.
  if($service == "datastore" && is_array($data)){
    if($func == "applyForSetTerminal")  return applyForSetTerminal($data);
    elseif($func == "keyValueStore")    return keyValueStore($data);

  }elseif($service == "command"){
    require_once("command.php");
    return command($func);
  }
  
  if(empty($_SERVER['SESSION']['sid']))
    return ["error" => "Please supply session ID to access services ","session" =>$_SERVER['SESSION']];


  // Make websocket connection to server (Keep it open and let it close on exit)
  if(!$sp){
    $headers = ["Cookie: SID=" . $_SERVER['SESSION']['sid']];
//print_r($headers);  
    $sp=websocket_open('127.0.0.1:80',$headers,$errorstring,16);
    if(!$sp)
      return ["error" => "Unable to connect to service server: " 
        . @$errstr ."(". @$errno . ")"];

    // Get confirmation  
    $open_data=websocket_read($sp);
    $open_response = json_decode($open_data,true);
    if(@$open_response["reply"] != "connected" || empty($open_response["token"]))
      return ["error" => "Service Server refused request: $errorstring"];
      
    $token = $open_response["token"];
  }
  
  // Send request
  $request = [
     "service" => $service
    ,"func" => $func
    ,"data" => $data 
    ,"token" => $token
  ];
  websocket_write($sp,json_encode($request,JSON_NUMERIC_CHECK));

  //if($asyncronos_execution) return;
  
  // Get reply. 
  do{
    $data=websocket_read($sp,$errorstring);

    if(empty($data)){
      $response = ["error"=>"read error: $errorstring"];
      break;
    }
    
    $response = json_decode($data,true);
//echo "<pre>".print_r($data,true)."</pre>";
    if(json_last_error() != JSON_ERROR_NONE)
      $response["error"] 
        = sprintf("Communication with services failed. (%d)%s Response: '%s'"
          ,json_last_error(),$json_error_str[json_last_error()],$data
      );
//echo "<pre>".print_r($response,true)."</pre>";

    if(!empty($response["token"])) $token = $response["token"];      

  }while(!empty($response["reply"]) && $response["reply"]  == "working");


  if(empty($response["reply"]) && !empty($response["error"])) 
    $response["reply"] = "failed";
  if(!isset($response['error'])) $response['error']='';

  return $response;
}

/*============================================================================*\
  Apply for terminal registration
    // Apply for terminal registration
  // Terminal does not yet have an accouint, so it has no access to the real
  // datastore. There fore the application is added to a file, for an
  // administrators approval

  !!Grossly insecure!!
  At the moment, terminals are immidiatly acceptes. This function just add the terminal to the file.

  It is supposed to make an application record, send a message(event) and let the administrator approve the request and add it to the terminal access allowed list  
\*============================================================================*/
function applyForSetTerminal($record){
  $file_name = $_SERVER['DOCUMENT_ROOT'] . "/var/tid.json";

  if(!is_array($record)) 
    return ["error"=>"Please parse a record of data (Array)"];

  $record['tid'] = preg_replace('/[^a-z0-9+]/i', '',$record['tid']);
 
  if(empty($record['tid']) || strlen($record['tid']) < 128)
    return ["error"=>"Please specify a valid Terminal ID key ($record[tid])"];

  if(empty($record['terminal_name'])) 
    return ["error"=>"Please specify a valid Terminal name"];
  
  if(empty($record['trust']))
    return ["error"=>"Please specify a valid trust level"];

  $file = file_get_contents($file_name);
  if(!$file) return ["error"=>"Failed to access '$file_name': $errstr ($errno)"];
  
  $tid=json_decode($file,true);
  if(json_last_error() !== JSON_ERROR_NONE)
    return ["error"=>"The data file '$file_name' is corrupted."
      . json_last_error_msg()];

  $record['changed at'] = time();
  $tid[$record['tid']] = $record;

  if(!file_put_contents(
     $file_name
    ,json_encode($tid,JSON_PRETTY_PRINT + JSON_NUMERIC_CHECK)
    ))
    return ["error"=>"Unable to update the data file '$file_name'"];
    
  return ["error"=>"", "reply"=>"ok"];
}

/*==========================================================================*\
  Key value store
  
  Store and retrieve values, based on a key string.
  Valuers are keept in a file.
  
  data array:
   key => (string) valid file name
   value => (string) content of file
  
  if only key has content/is defined, a read is assumed.
   
  Reply array:  
    result => (string) value
    error => (string) text or null
    
\*==========================================================================*/
function keyValueStore($data){ 
  if(empty($data['key'])) 
    return ["error" => "please specify a key"];
   
  // Define file used for storing lates output value
  $file="{$_SERVER['DOCUMENT_ROOT']}/var/keystore-{$data['key']}.dat";

  // read file
  if(empty($data['value']))
    return ["result"=>@file_get_contents($file),"error"=>""];

  // Write value to file 
  $rc=file_put_contents($file,$data['value']);
  if($rc<1){
    $error_arr=error_get_last(); 
    return ["error"=>"Unable to store data in temporary file: $file. - $error_arr[message]","reply"=>"failed"];
  }
  return ["error"=>"","Reply"=>"ok"];
}
?>
