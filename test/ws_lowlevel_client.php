<!DOCTYPE HTML>
<html>
<head>
<title>Test page</title>
<link href="theme/theme.css" type="text/css" rel="stylesheet">
<style>
</style>

</head>

<body>
<script>
var arr = {a:'test"ing', b: "test'ing"};
console.debug(JSON.stringify(arr));

</script>

<h1>Test af websocket klient</h1>
<?php


function hex_dump($data, $newline="\n")
{
  static $from = '';
  static $to = '';
  static $width = 16; # number of bytes per line
  static $pad = '.'; # padding for non-visible characters

  if ($from===''){
    for ($i=0; $i<=0xFF; $i++){
      $from .= chr($i);
      $to .= ($i >= 0x20 && $i <= 0x7E) ? chr($i) : $pad;
    }
  }

  $hex = str_split(bin2hex($data), $width*2);
  $chars = str_split(strtr($data, $from, $to), $width);

  $offset = 0;
  foreach ($hex as $i => $line){
    echo sprintf('%06X',$offset).' : '.implode(' ', str_split($line,2)) . ' [' . htmlspecialchars($chars[$i]) . ']' . $newline;
    $offset += $width;
  }
}
/*----------------------------------------------------------------------------*\
  array services(string $service, string $func, mixed $data) 
 
  service
    Requested service module.
    values are: datastore, command, timer, serverinfo, event,  echo
    
  func
    Function to perform. Specific to the service requested.
    
  data (optional)
    Additional data to the function. It can be a string or an array.
  
  returns a reaponse record:
    reply:  Verbal reply to user. Simpel forms are: ok, Unable to comply, working.
    error:  Optional. Explanation of failure
    result: Optional. 
    state:  Optional.
    cmd_id: returned from the request


  Services handle communication with the smartcore services module, through a 
  websocket connection.
  Only with a valid sessions, will the srequest be accepted.
\*----------------------------------------------------------------------------*/

function services($service,$func,$data=''){
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
  //  ,JSON_ERROR_UTF16 => 'Malformed UTF-16 characters, possibly incorrectly encoded.'
  //  ,JSON_ERROR_INVALID_PROPERTY_NAME => 'A property name that cannot be encoded was given'
    ];
  
  $response['error'] = '';

  $request = [
     "service" => $service
    ,"func" => $func
    ,"data" => $data 
    ,"sid" =>  @$_SERVER['SESSION']['sid']
  ];
  
  // Make websocket connection to server (Keep it open and let it close on exit)
  if(isset($_SERVER['SESSION']['sid'])){
    $headers = ["Cookie: SID=".$_SERVER['SESSION']['sid']];

    // Open connection to services  
    if(!$sp){
      $sp=websocket_open('127.0.0.1:80',$headers,$errorstring,16);
      if(!$sp)
        return ["error" => "Unable to connect to service server: $errorstring"];

      // Get confirmation  
      $data=websocket_read($sp);
      $open_response = json_decode($data,true);
      if(@$open_response["reply"] != "connected" || empty($open_response["token"]))
        return ["error" => "Service Server refused request"];
        
      $token = $open_response["token"];
    }
    
    // Send request
    $request["token"] = $token;
    websocket_write($sp,json_encode($request,JSON_NUMERIC_CHECK));

//echo "<pre>\nTx:".json_encode($request,JSON_NUMERIC_CHECK)."\n</pre>";

    // Get reply. 
    do{
      $data=websocket_read($sp,$errorstring);
//echo "<pre>\nRx:$data\n</pre>";

      if(empty($data)){
        $response = ["error"=>"read error: $errorstring"];
        break;
      }
      
      $response = json_decode($data,true);
      if(json_last_error() != JSON_ERROR_NONE)
        $response["error"] = sprintf("Communication with services failed. (%d)%s Response: '%s'",json_last_error(),$json_error_str[json_last_error()],$data);

      if(!empty($response["token"])) $token = $response["token"];      

    }while(@$response["reply"] == "working");


    if(empty($response["reply"]) && !empty($response["error"])) 
      $response["reply"] = "failed";
      
  // Apply for terminal registration
  // Terminal does not yet have an accouint, so it has no access to the real
  // datastore. There fore the application is added to a file, for an
  // administrators approval
  }else if($service == "datastore" 
    && $func == "applyForSetTerminal" 
    && is_array($data)){
    
    return applyForSetTerminal($data);

  }else
    $response = ["error" => "Please suply session and credintials "];

  if(!isset($response['error'])) $response['error']='';

  return $response;
}

/*============================================================================*\
  Websocket client 
  
  By Paragi 2013, Simon Riget MIT license.
  
  This is a demonstration of a websocket clinet. 
  
  If you find flaws in it, please let me know at simon.riget (at) gmail
  
  Websockets use hybi10 frame encoding: 
  
        0                   1                   2                   3
        0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
       +-+-+-+-+-------+-+-------------+-------------------------------+
       |F|R|R|R| opcode|M| Payload len |    Extended payload length    |
       |I|S|S|S|  (4)  |A|     (7)     |             (16/63)           |
       |N|V|V|V|       |S|             |   (if payload len==126/127)   |
       | |1|2|3|       |K|             |                               |
       +-+-+-+-+-------+-+-------------+ - - - - - - - - - - - - - - - +
       |     Extended payload length continued, if payload len == 127  |
       + - - - - - - - - - - - - - - - +-------------------------------+
       |                               |Masking-key, if MASK set to 1  |
       +-------------------------------+-------------------------------+
       | Masking-key (continued)       |          Payload Data         |
       +-------------------------------- - - - - - - - - - - - - - - - +
       :                     Payload Data continued ...                :
       + - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - +
       |                     Payload Data continued ...                |
       +---------------------------------------------------------------+

  See: https://tools.ietf.org/rfc/rfc6455.txt
  or:  http://tools.ietf.org/html/draft-ietf-hybi-thewebsocketprotocol-10#section-4.2

\*============================================================================*/

/*============================================================================*\
  Open websocket connection

  resource websocket_open(string $host [,int $port [,$additional_headers [,string &error_string ,[, int $timeout]]]]
  
  url
    A host URL. It can be a domain name like www.example.com or an IP address, 
    with port number. Local host example: 127.0.0.1:80

  headers (optional)
    additional HTTP headers to attach to the request.  
    For example to parse a session cookie: "Cookie: SID=" . session_id()  
    
  error_string (optional)
    A referenced variable to store error messages, i any
    
  timeout (optional)
    The maximum time in seconds, a read operation will wait for an answer from 
    the server. Default value is 10 seconds.

  returns a resource handle or false.

  Open a websocket connection by initiating a HTTP GET, with an upgrade request
  to websocket. 
  If the server accepts, it sends a 101 response header, containing 
  "Sec-WebSocket-Accept"
\*============================================================================*/
function websocket_open($host="127.0.0.1",$headers='',&$error_string='',$timeout=10){

  // Generate a key (to convince server that the update is not random)
  // The key is for the server to prove it i websocket aware. (We know it is)
  $key=base64_encode(uniqid());
  $query=parse_url($host);
  $header = "GET / HTTP/1.1\r\n"
    ."Host: $query[host]\r\n"
    ."pragma: no-cache\r\n"
    ."Upgrade: WebSocket\r\n"
    ."Connection: Upgrade\r\n"
    ."Sec-WebSocket-Key: $key\r\n"
    ."Sec-WebSocket-Version: 13\r\n";

  // Add extra headers 
  if(!empty($headers)) foreach($headers as $h) $header.=$h."\r\n";  

  // Add end of header marker
  $header.="\r\n";

echo "Headers: <pre>$header</pre>";
  // Connect to server  
  $sp=fsockopen($query['host'], $query['port'], $errno, $errstr,$timeout); 
  if(!$sp){
    $error_string = "Unable to connect to websocket server: $errstr ($errno)";
    return false;
  }

  // Set timeouts
  stream_set_timeout($sp,$timeout);

  //Request upgrade to websocket 
  $rc = fwrite($sp,$header);
  if(!$rc){
    $error_string = "Unable to send upgrade header to websocket server: $errstr ($errno)";
    return false;
  }
  
  // Read response into an assotiative array of headers. Fails if upgrade failes.
  $reaponse_header=fread($sp, 1024);

  // status code 101 indicates that the WebSocket handshake has completed.
  if(!strpos($reaponse_header," 101 ") 
    || !strpos($reaponse_header,'Sec-WebSocket-Accept: ')){
    $error_string = "Server did not accept to upgrade connection to websocket."
      .$reaponse_header. E_USER_ERROR;
    return false;
  }
  // The key we send is returned, concatenate with "258EAFA5-E914-47DA-95CA-
  // C5AB0DC85B11" and then base64-encoded. one can verify if one feels the need...
  
  return $sp;
}

/*============================================================================*\
  Write to websocket
  
  int websocket_write(resource $handle, string $data ,[boolean $final])
  
  Write a chunk of data through the websocket, using hybi10 frame encoding
  
  handle
    the resource handle returned by websocket_open, if successful
    
  data
    Data to transport to server
    
  final (optional)
    indicate if this block is the final data block of this request. Default true  
\*============================================================================*/
function websocket_write($sp,$data,$final=true){
printf("Sending request. Length %d<br>:%s\n",strlen($data),$data);
  // Assamble header: FINal 0x80 | Opcode 0x02
  $header=chr(($final?0x80:0) | 0x02); // 0x02 binary

  // Mask 0x80 | payload length (0-125) 
  if(strlen($data)<126) $header.=chr(0x80 | strlen($data));  
  elseif (strlen($data)<0xFFFF) $header.=chr(0x80 | 126) . pack("n",strlen($data));
  else $header.=chr(0x80 | 127) . pack("N",0) . pack("N",strlen($data));

  // Add mask
  $mask=pack("N",rand(1,0x7FFFFFFF));       
  $header.=$mask;
  
  // Mask application data. 
  for($i = 0; $i < strlen($data); $i++)
    $data[$i]=chr(ord($data[$i]) ^ ord($mask[$i % 4]));
  
  $rc = fwrite($sp,$header.$data);    
  echo "Send:<br>";
  hex_dump($header.$data,"<br>"); 
  
  return $rc;
}

/*============================================================================*\
  Read from websocket

  string websocket_read(resource $handle [,string &error_string])
  
  read a chunk of data from the server, using hybi10 frame encoding
  
  handle
    the resource handle returned by websocket_open, if successful

  error_string (optional)
    A referenced variable to store error messages, i any

  Read 
 
  Note:
    - This implementation waits for the final chunk of data, before returning.
    - Reading data while handling/ignoring other kind of packages
 \*============================================================================*/
function websocket_read($sp,&$error_string=NULL){
  $data="";

  do{
    // Read header
$elt=time(); 
    $header=fread($sp,2);
printf("Reading header 1 (%ds) length 2:<br>",time()-$elt); hex_dump($header,"<br>");        
    if(!$header){
      $error_string = "Reading header from websocket failed.";
      return false;
    }

    $opcode = ord($header[0]) & 0x0F;
    $final = ord($header[0]) & 0x80;
    $masked = ord($header[1]) & 0x80;
    $payload_len = ord($header[1]) & 0x7F;
    
    // Get payload length extensions
    $ext_len = 0;
    if($payload_len >= 0x7E){
      $ext_len = 2;
      if($payload_len == 0x7F) $ext_len = 8;
$elt=time(); 
      $ext=fread($sp,$ext_len);
printf("Reading header 2 (%ds) lenght %d:<br>",time()-$elt,$ext_len); hex_dump($header,"<br>");        
      if(!$ext){
        $error_string = "Reading header extension from websocket failed.";
        return false;
      }
 
      // Set extented paylod length
      $payload_len= 0;
      for($i=0;$i<$ext_len;$i++) 
        $payload_len += ord($ext[$i]) << ($ext_len-$i-1)*8;
    }
    
    // Get Mask key
    if($masked){
      $mask=fread($sp,4);
printf("Reading mask (%ds) length 4:<br>",time()-$elt); hex_dump($mask,"<br>");        
      if(!$mask){
        $error_string = "Reading header mask from websocket failed.";
        return false;
      }
    }
    
    // Get payload
    $frame_data='';
    do{
$elt=time(); 
      $frame= fread($sp,$payload_len);
      if(!$frame){
        $error_string = "Reading from websocket failed.";
        return false;
      }
printf("Reading frame (%ds) length: %d (%d):<br>",time()-$elt,strlen($frame),$payload_len); 
      
      $payload_len -= strlen($frame);
      $frame_data.=$frame;
    }while($payload_len>0);    

    // Handle ping requests by sending a pong and continue to read
    if($opcode == 9){
      // Assamble header: FINal 0x80 | Opcode 0x0A + Mask on 0x80 with zero payload
      fwrite($sp,chr(0x8A) . chr(0x80) . $ext . $mask . $frame_data);    
      continue;

    // Close
    } elseif($opcode == 8){
      fclose($sp);
      
    // 0 = continuation frame, 1 = text frame, 2 = binary frame
    }elseif($opcode < 3){ 
      // Unmask data
      $data_len=strlen($frame_data);
      if($masked)
        for ($i = 0; $i < $data_len; $i++) 
          $data.= $frame_data[$i] ^ $mask[$i % 4];
      else    
        $data.= $frame_data;

    }else
      continue;
    
if(strlen($data) < 0x10000) hex_dump($data,"<br>");        

    echo ($final ? "done" : "more...") . "<br>";     

  }while(!$final);
    
  return $data;
}

// Short <126
$start=time();
echo "<pre>".print_r(services("echo","ping\"go"),true)."</pre>";      
printf("Time total; %d s<br>\n",time()-$start);
echo "--------------------------------------------------------------<br>\n";

// Medium <64000 
$start=time();
echo "<pre>".print_r(services("serverinfo","all"),true)."</pre>";      
printf("Time total; %d s<br>\n",time()-$start);

// large
$start=time();
echo "<pre>".print_r(services("datastore","tables"),true)."</pre>";      
printf("Time total; %d s<br>\n",time()-$start);

// huge
$start=time();
echo "<pre>".print_r(services("huge","test"),true)."</pre>";      
printf("Time total; %d s<br>\n",time()-$start);





?>
</body>
</html>
