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
<h1>Test interaction</h1>
<div class="container" style=" width: 95%">

<!------------------------------------------------------------------------------
  Command submit form  
------------------------------------------------------------------------------->

<form method="post">
<table>
<?php
printf('<tr><td>Context: </td><td><input name="ctx" size="50" type="text" autofocus value="%s"></td></tr>',@$_REQUEST['ctx']);

printf('<tr><td>Interaction: </td><td><input name="interaction" size="50" type="text"  value="%s"></td></tr>',@$_REQUEST['interaction']);

printf('<tr><td>Action: </td><td><input name="action" size="50" type="text" value="%s"></td></tr>',@$_REQUEST['action']);

printf('<tr><td>Trust: </td><td><input name="trust" size="50" type="text" value="%s"></td></tr>',@$_REQUEST['trust']);


?>
</table>
<input type="submit" style="visibility: hidden;" />

</form>
<!------------------------------------------------------------------------------
  Submit methods  
------------------------------------------------------------------------------->
<div class="tile" onclick="
document.forms[0].submit();
">Send</div>

<div class="container" id="output" style="float:left">
<pre>
<?php
// Run interaction
$trust=$_POST['trust'];
$response=Xinteraction(["context"=>$_POST['ctx']
,"interaction"=>$_POST['interaction']
,"action"=>$_POST['action']]);
echo "<pre>". print_r($response,true) . "</pre>";



/*============================================================================*\
 Interaction command

 interaction(array $cmd)
 
 Command is an array of:
  [context]       
  [interaction]
  [action]

  return:
    a response array
    
    

\*============================================================================*/
function Xinteraction($cmd){
  global $interaction;
  global $trust;  

  do{
    // Validate command array
    if(empty($cmd[context])){
      $response['error']="Context of interaction is undefined";
      break;
    }
    
    if(empty($cmd[interaction])){
      $response['error']="interaction name is undefined";
      break;
    }

    if(empty($cmd[action])) $cmd[action]="get";

    // Find interaction definition data
    $filename="./context$cmd[context]$cmd[interaction].ia-dat";
    if(!file_exists($filename)){
      $response['error']="Can't locate interaction definition file '$filename'";
      break;
    }

    // read interaction definition data
    $ia=json_decode(str_replace( "\n", "",file_get_contents($filename)),true);
    if(!is_array($ia)){
      $response['error']="Can't interpret interaction definitions in file '$filename'";
      break;
    }

    // get latest status
    if($cmd['action']=="get" && $ia["max data age"]>0){

/*
      // Look in event log for last event

      // Extract context
      $i=strrpos($cmd[interaction],"/");

      $aq=array(
         "method"=>"post"
        ,"api"=>"/_api/cursor"
        ,"post"=>array(
           "query"=>"FOR e IN event "
          ." FILTER e.time >= ". (time()-$ia["max data age"])
          ." FILTER e.state!=null"
          ." FILTER e.context==\"$cmd[context]\""
          ." FILTER e.interaction==\"$cmd[interaction]\""
          ." LIMIT 1"
          ." RETURN e.state"
        )         
      );      
      $DBresponse=adb($aq);
      if(isset($DBresponse['result'][0]))
        $response['state']=$DBresponse['result'][0];
      */
      ;
    }
    
    
    
    // Verify meta command  
    if(!in_array($cmd['action'],["capabilities","description","status","list"])){

      // Is it defined in the ia-dat 
      if( empty($ia['cmd']) 
          || empty($ia['cmd'][$cmd['action']]) 
          || empty($ia['cmd'][$cmd['action']][0])){
        $response['error']="Command action '$cmd[action]' has no valid code in interaction definition file.";
        break;
      }

      // Check sensitivity
      if(empty($ia['cmd'][$cmd['action']][1])){
        $response['error']="Sensitivity of interaction $cmd[action] is undetermined";
        break;
      }

      if($ia['cmd'][$cmd['action']][1]>$trust){
        $response['error']="You do not have sufficient privileges to execute this command. ($ia[cmd][$cmd[action]][1]/$trust)";
        break;
      }

      // Check & translate action into driver command code
      $action=$ia['cmd'][$cmd['action']][0];
    }else
      $action=[$cmd['action']];
    
    // 
    if(!$response['state']){
      if($ia['type']!="interaface action"){

        // Get Handler file
        $filename="./device$ia[device].php";
        if(!file_exists($filename)){
          $response['error']="Can't locate interaction handler file '$filename'";
          break;
        }

        $response=device_handler($filename,$ia['unit_id'],$$cmd);

        // Translate reply
        if(is_array($ia['reply']))
          $response['state']=strtr($response['state'],$ia['reply']);
      }
    }

    // Make interaction available to tile script
    $interaction[$cmd['interaction']]=$ia;
    // Make visual response
    $response['html']=render_tile($cmd['context'],$cmd['interaction'],$response['state']);

  }while(false);

  return $response;
}

/*============================================================================*\
  Wrapper fro device handler
\*============================================================================*/
function device_handler($filename,$unit_id,$action){
  require $filename;

  // Execute actions
  foreach($action as $command){
echo "Sending command: $command\n";  
    $response=$handler(trim(strtolower($command)),$unit_id);
    if($response['error']) break;
  }
  return $response;
}
?>
</pre>
</div>
</body>
</html>
