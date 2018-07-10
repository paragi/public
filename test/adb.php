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
<h1>PHP Globals</h1>
<div class="container" style=" width: 95%">
<pre>
<?php
/* ================================================================================ *\
        Configuration
\* ================================================================================ */

//define("_DEBUG",true);
/*
session_name("SID");
session_start();
*/


// Arango DB configuration
$_adb_option_defaults = array(
  CURLOPT_HEADER => false
  ,CURLOPT_RETURNTRANSFER => true
  ,CURLOPT_TIMEOUT => 7 
  ,CURLOPT_SSL_VERIFYPEER => false
  ,CURLOPT_SSL_VERIFYHOST => 0
  ,CURLOPT_VERBOSE => false
);

define("_ADB_URL","http://127.0.0.1:8529");
define("_ADB_BASE","");
define("_ADB_PL1","root");
define("_ADB_PL2","");



/* ================================================================================ *\
        ArangoDB interface

utilising REST HTTP protocol 

The parameters is an array containing some of the following named entries

[method] : string. HTTP method:  GET | POST | PUT | DELETE | HEAD | PATCH | OPTIONS
[api]    : string. Name of arango api to use (uri) eg: simple | simple/by-example | cursor ...

as required also:

[request]: string or associative array. Parameters for api (GET querry) eg. collection=test
[post]   : associative array. Might be data in a document eg. [name] = foo,

if defined "_ADB_DEBUG" additional information will be displayed

\* ================================================================================ */
function adb($arr){
  // Global Settings
  global $_adb_option_defaults;

  // Set default curl options
  $options = $_adb_option_defaults;

  // Assamble URL; and Select database
  if(strpos($arr['api'],"/_api/database")!==0)
    $url=_ADB_URL . "/_db/". _ADB_BASE;

  // Select API
  //  $url.="/_api/" . preg_replace('/[^a-z\-\/_]/', '', $arr['api']);
  $url.=preg_replace('/[^A-Za-z0-9\-\/_]/', '', $arr['api']);

  // Add GET request
  if($arr['request'])
    if(is_array($arr['request']))
      $url.="?". http_build_query($arr['request']);
    else
      $url.="?". preg_replace('/[^A-Za-z0-9\-=+&%;_]/', '', $arr['request']); 
 
  // Compose HTTP querry
  $options += array(
     CURLOPT_URL => $url
    ,CURLOPT_CUSTOMREQUEST => preg_replace('/[^A-Z]/', '', strtoupper($arr['method']) )
    ,CURLOPT_USERPWD => _ADB_PL1 .":". _ADB_PL2
  );

  // Add POST data
  if(is_array($arr['post'])){ 
    $options += array( 
       CURLOPT_POSTFIELDS => json_encode($arr['post'],JSON_NUMERIC_CHECK)
      ,CURLOPT_POST => 1
    );
  }
  
  if(defined("_ADB_DEBUG")){
    echo "<pre>DB operation: ".$options[CURLOPT_CUSTOMREQUEST]." ".$options[CURLOPT_URL]." \n";
    if($options[CURLOPT_POSTFIELDS]) echo $options[CURLOPT_POSTFIELDS]."\n";
  }

  do{
    // Handle is by default, automatically reused on local server. No need to preserve it for next call
    $adb_handle = curl_init();

    if(! curl_setopt_array($adb_handle,$options) ){
      $response['error']="Preparation of HTTP call to DB failed: ".curl_error($adb_handle);
      break;
    }

    // send request and wait for response
    $json=curl_exec($adb_handle);
    
    // Check that there is a response
    if(!$json){
      //$response['error']="Error: DB failed to reply: ".curl_error($adb_handle);
      break;    
    }
    curl_close($adb_handle);

  }while(false);

  if(isset($response['error'])){
    //error(3,$response['error'],false);
  }else{    
    $response =  json_decode($json,true);
  }

  if(defined("_ADB_DEBUG")){
    echo "response from DB: " . print_r($response,true) ."</pre>";
  }

  return($response);
}


// NB: REMOVE
// Simulate version 1
function adb_rest($method,$uri,$querry=NULL,$jsonq=NULL,$options=NULL){
  $query['method']=$method;
//  $query['api']=substr($uri,6);
  $query['api']=$uri;
  $query['request']=$querry;
  $query['post']=json_decode($jsonq,true);
  return adb($query);
}

// Add error repport
// Return false only if DB operation fails
function add_error_repport($error_repport){

  if(!is_array($error_repport)) return true;

  // Prepare ADB statement
  $aq=array(
     "method"=>"POST"
    ,"api"=>"/_api/document"
    ,"request"=>"collection=error"
    ,"post"=>$error_repport
  );
  $response = adb($aq);

  // Check if database was on-line
  if(!is_array($response) || ($response['error'] && $response['code']!=409))
    return false;

  //If repport already exists, just update count and time
  if($response['code']==409){
    // Get document ID
    $aq=array(
       "method"=>"POST"
      ,"api"=>"/_api/cursor"
      ,"post"=>array(
         "query"=>"FOR e IN error "
        ."FILTER e.message=='$error_repport[message]' "
        ."FILTER e.file=='$error_repport[file]' "
        ."FILTER e.line==$error_repport[line] "
        ."RETURN e"
        ,"count"=>true
      )
    );
    $response = adb($aq);

    if(!$response['error'] && isset($response['result'][0])){
      // Update error repport counter
      $upd['count']=$response['result'][0]['count']+$error_repport['count'];
      $upd['time']=$error_repport['time'];
      $aq=array(
         "method"=>"PATCH"
        ,"api"=>"/_api/document/".$response['result'][0]['_id']
        ,"request"=>"collection=error"
        ,"post"=>$upd
      );
      $response = adb($aq);

      if ($response['error'])
        // Somthing is wrong with the database. log to file instead
        return false;
    }
  }

  return true;
}



?>

</pre>
</div>
</body>
</html>
