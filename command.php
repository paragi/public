<?php
// Allow direct call for test purposses
define('_NON_WS_ALLOWED',true);

/*
  add a verify command
  
  return sensitivity required as result.sensitivity
*/

/*============================================================================*\
  Command 
  
  Intepret and execute commands
  
  If included, use the command() function to execute a command.
  If invoked directly, a POST, GET or websocket request with the parameter cmd
  is expected to hold a command to interpret.
  
  array command(string $command)
  
  Return array Always contain:
    [reply] String: An imidiate reply to the user
                    OK if succesfull or a simpel satement if not. (No error messages)
                    Unable to comply or if malformatted command; a simple message.
    [error] String: Empty or a level 2 error message 

  The following elements might be present i relevant:  
    [state] string:       State of an interaction
    [html]  string:       Tile content of an interaction
    [message] string:     
    [context] string:     A formatted context string 
    [interaction] string: An interaction name
    [event] string:       Name of the event triggered
    [time] float:         Time of execution in epoc
    [origin] string:      IP that originate request

  Reply and error levels:
    reply : ex: ok, failed, unable to comply
    
    Error repport level:
    1. Why did it fail? bad command/script failure/device off-line
    2. How bad is it? Where in the system is there a problem? Which script
        What is the problem? Error message from script etc
        
    seriousness: =array("","minor functional","functionl","section","system wide","total system");
     3 Find similar problems: same script errors?
     4. Full repport, all debugging data


\*============================================================================*/
require_once "$_SERVER[DOCUMENT_ROOT]/services.php";


/*============================================================================*\
  Direct request
  
  Commands are either served as a websocket request of this file or 
  this file is included and the function cmd called with command.
  POST and GET methods are only used for test purposes.
  
\*============================================================================*/
// Run this if this file is called directly (Not includede)
if($_SERVER['SCRIPT_FILENAME'] == __FILE__){
  // Catch all output and add it to error message
  ob_start();
  //ob_end_clean();

  if(_NON_WS_ALLOWED || $_SERVER['REQUEST_METHOD']=='websocket') do{
    $trust=$_SERVER['SESSION']['trust'];

    if(empty($_SERVER['SESSION']['sid'])){
      $response['error'] = "Session ID mising";
      break;
    }

    if(empty($_REQUEST['cmd'])){
      $response['error'] = "Command unreadable";
      break;
    }

    //Execute command
    $response=command($_REQUEST['cmd'],false);
    $response['event'] = $response['event'] ?: "user command";
    $response['error'] = $response['error'] ?: "";
    
  }while(false);
  
  // Add any output as an error
  $output_buffer = trim(ob_get_contents());
  if(!empty($output_buffer))
    $response['error'] .= "\n" . $output_buffer;
  ob_end_clean();

  // Send reply on websocket
  if($_SERVER['REQUEST_METHOD']=='websocket'){
    echo json_encode($response
      ,JSON_UNESCAPED_UNICODE
      |JSON_UNESCAPED_SLASHES
      |JSON_BIGINT_AS_STRING
      |JSON_BIGINT_AS_STRING
      |JSON_HEX_QUOT
      |JSON_HEX_TAG
      |JSON_PARTIAL_OUTPUT_ON_ERROR
    ); 

  // Reply on direct call. (for test purposes)
  }else{
    echo "res:<pre>",print_r($response,true),"</pre>";    
  }
}

/*============================================================================*\
  Command execute

  array command(string $command, boolean $simple_command)

  This function is the only right way to execute a command
  
  programmatically formatted command should use the simple format:
    full context string with interaction name appended and optionally an action.
  
    ex: /bedroom/ceiling/light on
 
  If a command dose not conform to the simple format, the user command 
  inperpreter are invoked.
  
\*============================================================================*/
function command($cmd_line, $simple=false){
  do{
    $response = interpret($cmd_line);

    // Prepare response as event
    $response['time'] = microtime(true);
    $response['origin'] =  @$_SERVER['SESSION']['this_is'] ?: $_SERVER['REMOTE_ADDR'];
    $response['terminal'] = @$_SERVER['SESSION']['terminal_name'] ?: $_SERVER['REMOTE_ADDR'];
    
    // Maker default reply (might be spoken out loud. Keep it short and to the standard)
    if(empty($response['reply']))
      if(!empty($response['error']))
        $response['reply'] = "unable to comply";
      else
        $response['reply'] = "ok";
            
  }while(false);
  
  services('event','announce',$response,true);
  return $response;
}
/*============================================================================*\
  Structured language command interpretor

  This interpreter expects a well formed commands, using a predefined syntax.
  (Not naturel languange)
  
  Machine commands takes the form of a formattet full path context/interaction
  string and an optional action. Ex.
    /utility/system/template/bedroom/ceiling/light on
  
  All other commands are interpreted as user commands. 
  if a reserved system command word are used, a system command is assumed.
  Otherwise this function will try to interpret the command as an interaction or
   user program, found in the context tree
 
  Interpretation is done by relation and elimination of words that describe 
  known contexts, interactions, user programs or actions.
  
  The file words.php defines an associative array of words, paths and names, 
  used to interpret the command.
  Any new contexts, interactions or user programs, is not recognised before this 
  array is updated.
  
  /var/words.php contains $word_list

  Command interface
  Determin type of command
  interaction commands can have 4 formats:
  1. <full path context + / + interaction name> <action>
  2. <relative or full context> <interaction name> <action>
  3. <interaction name> <action> <relative or full context>
  <non context specific command>


  context  interaction  action

     0        0           0       Empty command. Ignore
     0        0           1       action request. Pseudo action or change of last action
     0        1           0       status resuest. sort on interactions availabel and use current or last used context.
     0        1           1       action request. sort on interactions availabel and use current or last used context.
     1        0           0       change of context
     1        0           1       ??? Same interaction in different context? tread as two commands. context first.
     1        1           0       status resuest
     1        1           1       action request


  Assume the user know more then you and hav a good reason. do your best to keep up. she is thy God!
  So when you want to throw an error, just do your best to carry out the order instead.
  try to fill in the blanks with what is posible.

\*============================================================================*/
function interpret($cmd){
  global $word_list;

  $types=['i','c','s']; // Interaction, Context, Action, System command
  foreach($types as $i) $c[$i]=0; // Array to count occurrences of word types

  do{
    // Remove special chars 
    $cmd2=preg_replace('/[^A-Za-z0-9\- .\/_]/','',trim(strtolower($cmd)));
    // trim array  and split into words
    $command=array_filter(explode(" ",$cmd2));

    /*========================================================================*\
      Categorise command
    \*========================================================================*/
    // Empty
    $words=count($command);
    if($words<1){
      $response['error'] = "No command actually given. Try typing 'help'";
      break;
    }

    // Machine syntax
    if($command[0][0] == "/"){
      // Isolate components
      $s = strrpos($command[0],"/");
      $simple_cmd['context'] = substr($command[0],0,$s+1);
      $simple_cmd['interaction'] = substr($command[0],$s+1);
      if($words < 2)
        $simple_cmd['action'] = 'get';
      else{  
        $simple_cmd['action'] = $command[1];
        $simple_cmd['options'] = implode(" ",array_slice($command,2));
      }
      break;
    }

    /*========================================================================*\
    // Compare words against word list and try to narrow options down
    // context must be part of interaction

      Interpret by relation and elimination
      Try to match words to known context and interaction names
    \*======================================================================*/
    include_once $_SERVER['DOCUMENT_ROOT'] . "/var/words.php";
    if(!is_array($word_list)){
      $response['reply'] = "failed";
      $response['error'] = "The word list " 
        . $_SERVER['DOCUMENT_ROOT'] . "/var/words.php"
        . " is off-line";
      break;
    }

    // System command (first word)
    if(@reset($word_list[$command[0]]) =='s'){
      $simple_cmd['system'] = $command;
      break;
    }
   
    // Compile a list of path options, that contain command words
    if(@is_array($command)) foreach($command as $i=>$word){
      if(!@is_array($word_list[$word])) continue;
      // Create options array from first word
      if($i==0) {
        $option=$word_list[$word];
        continue;
      }
      
      // Reduce options for each new word, that doesn't fit
      $new_option='';
      if(@is_array($option)) foreach($option as $opath=>$otype){
         if(@is_array($word_list[$word])) foreach($word_list[$word] as $path=>$type){
          if($otype=='i' && $type=='i' && $path==$opath) 
            $new_option[$path]='i'; // i i Match 
          elseif($otype=='i' && $type=='c' && strpos($opath,$path)===0) 
            $new_option[$opath]='i';  // c part of i (ic)
          elseif($otype=='c' && $type=='i' && strpos($path,$opath)===0) 
            $new_option[$path]='i';  // c part of i (ci)
          elseif($otype=='c' && $type=='c')
            if(strlen($path)>strlen($opath)){
              if(strpos($path,$opath)===0)
                $new_option[$path]='c';  //  c part of c
            }else{  
              if(strpos($opath,$path)===0)
                $new_option[$opath]='c';  // c part of c
            }
          // Keep actions and system actions  
          elseif(!strpbrk('ic',$otype) || !strpbrk('ic',$type)){
            $new_option[$opath]=$otype;  // save other
            $new_option[$path]=$type;  // save other
          }
        }
      }
      $option=$new_option;
    }
    if(@!is_array($option)) break;

    // Count options    
    foreach($types as $i) 
      $c[$i]=0;
      foreach($option as $path=>$type) 
        $c[$type]++;

    /* How do we actuially know the right context?
    // Refine match by comparing with current context
    if(!empty($context) && ($c['i']>1 || ($c['i']!=1 && $c['c']>1))){
      $new_option='';
      // Try current context
      foreach($option as $path=>$type){
        if($type=='i' && @strpos($path,$context)===0)          
          $new_option[$path]=$type;  // In context
        elseif($type=='c')
          if(strlen($path)>@strlen($context)){
            if(@strpos($path,$context)===0) $new_option[$path]='c';  
          }else{   
            if(@strpos($context,$path)===0) 
              $new_option[$context]='c';
          }
        elseif(!strpbrk('ic',$type))
          $new_option[$path]=$type;  
      }
 
      // Recount options   
      $c2=[]; 
      if(is_array($new_option)) 
        foreach($new_option as $path=>$type) 
          $c2[$type]++;
      if(@$c2['i']+@$c2['c']!=0){
        $option=$new_option;
        $c=$c2;
      }
    }
    */
    
    // Find out if previous commands gives a hint
    if(@$c['i']>1 || (@$c['i']!=1 && @$c['c']>1)){
      $res = services("datastore", "event.commandHint",[
         "age"   => "15 minutes"
        ,"origin" => $_SERVER['SESSION']['this_is']
        ,"event" => "/demo/%"
      ]); 
      if($res['rowCount'] > 0 && is_array($res['result'])){
        $new_option='';
        foreach($res['result'] as $i => $entry)
          if(!empty($option[$entry['event']])){
            $new_option[$entry['event']] = $option[$entry['event']];
            // if it match the very last command, go with that
            if($i == 0) break; 
          }  

        // Recount options   
        $c2=[]; 
        if(is_array($new_option)) 
          if(@is_array($new_option))foreach($new_option as $path=>$type) 
            $c2[$type]++;
        if(@$c2['i']+@$c2['c']!=0){
          $option=$new_option;
          $c=$c2;
        }
      }
    } 

    // Is it an interaction command?
    if($c['i']==1){
      foreach($option as $path=>$type)
        if($type=='i'){
          $s=strrpos($path,"/");
          $simple_cmd['context']=substr($path,0,$s+1);
          $simple_cmd['interaction']=substr($path,$s+1);
          // Use all words after interaction name as arguments if the word
          // is not a part og interaction name
          $exclude_word = explode("/",$path);
          $arguments = array_slice(
              $command
             ,1 + array_search($simple_cmd['interaction'], $command)
          ); 
          if(is_array($arguments)) foreach($arguments as $word)
            if(!in_array($word,$exclude_word)) 
              if(empty($simple_cmd['action']))
                $simple_cmd['action'] = $word;
              else  
                $simple_cmd['argument'][] = $word; 
        }
        
    // Compose set context command
    }elseif($c['c']==1){
      foreach($option as $path=>$type)
        if($type=='c'){
          $simple_cmd['context']=$path;
          break;
        }
    
    // See if there is a system command some where in the command
    }elseif($c['s']>0){
      foreach($command as $i=>$word)
        if(isset($word_list[$word]) && reset($word_list[$word])=='s'){
          $simple_cmd['system'] = array_slice($command,$i);
          break;
        }
    
    // Too many options
    }elseif($c['i']>1 || $c['c']>1 || $c['s']>1){
      $response['error'] = "The command is too ambiguous. There are at least "
        . ($c['i']+$c['c']+$c['s'])." likely interpretations";
      foreach($option as $path => $type)
        $response['result'][] = $path;  
      break;  
        
    // Makes no sense
    }else{
      $response['error'] = "Unable to make a meaningful interpretation ";
      break;    
    }

  }while(false); 

  // Execute command  
  if(empty($response['error']) && is_array($simple_cmd)){
    // Interaction and user program
    if(!empty($simple_cmd['interaction'])){    
      $response=interaction($simple_cmd);
      $response['event'] = $response['event'] ?: 
        $simple_cmd['context'] . $simple_cmd['interaction'];
      
    // Change context
    }elseif(!empty($simple_cmd['context']) 
      && file_exists("./context/" . $simple_cmd['context'])){ 
      $response['event']="change_context";
      $response['context']=$simple_cmd['context'];
      $response['reply']='ok';

    // system command
    }elseif(!empty($simple_cmd['system'])){
      $response = execute_system_command($simple_cmd['system']);
       $response['event'] = $response['event'] ?: "system command";

    }else{
      $response['error'] = 
        "Unable to make a meaningful interpretation. Try typing 'help'"; 
      $response['reply'] = "failed";
    }
    // Store last used context and interacttion
    if($simple_cmd['context']) 
      $_SESSION['last_context']=$simple_cmd['context'];
    if($simple_cmd['interaction']) 
      $_SESSION['last_interaction'] = $response['event']=$simple_cmd['context']
        .$simple_cmd['interaction'];

  // Give up: Not simple and not system command    
  }elseif(empty($response['error']))
    $response['error']="Unable to make a meaningful interpretation";
     
  return $response;
}

/*============================================================================*\
 Interaction command

 Performe an action on a interaction or execute a user program

  array $response = interaction(array $cmd)
   
  array $cmd must contain at least one or all of these:
  * interaction:  Name of interaction or user program to operate on
  * action:       Action to performe. default=get
  * context:      Context in witch the interaction reside.
    
  The action is translated into a command code according to the ia-data file, 
  the command code is what is send to the device handler.

  Returns an interaction response array
    
  For now, interaction data and states are store temporary in SESSION
  This should properly be changed

\*============================================================================*/
function interaction($cmd){
  global $include; // Function to include user scripts

  do{
    // Validate parameters
    if(!is_array($cmd) || empty($cmd['context']) || empty($cmd['interaction'])){
      $response['error']="Internal programming error with interaction command";
      break;
    }
    $ia=$cmd['context'].$cmd['interaction'];
    $response['event'] = $ia;

    // Check trust is set
    if(empty($_SERVER['SESSION']) || empty($_SERVER['SESSION']['trust'])){
      $response['error']="The trust level of this terminal is undefined";
      break;
    }

    // Execute interaction
    $filename = "$_SERVER[DOCUMENT_ROOT]/context$ia.ia-dat";
    if(file_exists($filename)){
      // Read interaction definition data 
      $ia_data=json_decode(str_replace( "\n","",file_get_contents($filename)),true);
      if(!is_array( $ia_data)){
        $response['error']="Interaction definitions in file '$filename' has invalid JSON format";
        break;
      }
        
      // Default to get command
      if(empty($cmd['action'])) $cmd['action'] = "get";

      // Check that command exists
      if(!in_array($cmd['action'],["capabilities","description","status","list"])
         && (!isset( $ia_data['cmd']) || !isset( $ia_data['cmd'][$cmd['action']]))){
        $response['error']="The command '{$cmd['action']}' is unknown to this interaction";
        break;    
      }
      
      // Check command codes exists
      if(!is_array( $ia_data['cmd'][$cmd['action']][0])){
        $response['error']="There is no valid command code for the action '{$cmd['action']}'";
        break;
      }
      
      // Check sensitivity settings
      if(!isset( $ia_data['cmd'][$cmd['action']][1]) 
              ||  $ia_data['cmd'][$cmd['action']][1]<1){
        $response['error']="Sensitivity of '{$cmd['action']}' command is undetermined";
        break;    
      }

      // Check sensitivity 
      if( $ia_data['cmd'][$cmd['action']][1]>$_SERVER['SESSION']['trust']){
        $response['error']="You do not have sufficient privileges to execute this command. ({$ia_data['cmd'][$cmd['action']][1]}/{$_SERVER['SESSION']['trust']})";
        break;
      }

/*  Move cashing to handler
      // Get cashed state
      if(( $cmd['action']=="get" || $cmd['action']=="status" ) 
        && isset( $ia_data['time'])
        &&  $ia_data["max data age"]>0
        && time()- $ia_data['time'] < $ia_data["max data age"] 
   && false // not implementet yet     
        ){
          // read last known state
          // file("get last state",$ia);
          $response['state'] =  $ia_data['state'];

      // Access device handler
      }else{
*/      
      
      // Make auto loader get the class 
      $name=strtr($ia_data['device'],"/","\\");
      if(!class_exists($name,true)){
        $response['error']="The device handler '" . $ia_data['device'] . "' dose not exists";
        break;  
      }
      $device=new $name;

      // Verify handler
      if(!method_exists($device,"handler")){
        $response['error']="The file '" . $ia_data['device'] . "' dose not contain a valid device handler";
        break;  
      }

      // Make sure command code is an array
      if(!is_array( $ia_data['cmd'][$cmd['action']][0]))
         $ia_data['cmd'][$cmd['action']][0]=
          [$ia_data['cmd'][$cmd['action']][0]];

      // Execute actions
      foreach( $ia_data['cmd'][$cmd['action']][0] as $code){
        $command_code = trim(strtolower(str_replace("*",isset($cmd['options']) ? $cmd['options'] : "",$code)));
        $response=$device->handler($command_code,@$ia_data['unit id']);
        if(!empty($response['error'])) break;
      }

      // Translate reply
      if(@is_array($ia_data['reply']))
        $response['state'] = strtr($response['state'],@$ia_data['reply']);

      // Make visual response
      if(file_exists("./context$cmd[context]/tile.php")){
        // Render tile 
        $result=$include("tile.php",$cmd['context'],$response['state'],$ia_data);

      // Autogenerate tile          
      }elseif(empty($ia_data['present'])){
        $result=generate_tile_from_iadata( $ia_data
                             ,$cmd['context'].$cmd['interaction']
                             ,$response['state']);
      }    
      if(!empty($result['html']))
        $response['html']=$result['html'];      

      if(!isset($response['state'])) $response['state'] = 'off-line';
    // Execute user program
    }else{
    
      $response = $include(
        "$cmd[interaction]-prg.php"
        ,$cmd['context']
        ,''
        ,$cmd['argument']
      );
   
      if(!is_array($response))
        $response['error']="Unable to locate interaction or program by that name ($cmd[interaction])";
    }
      
  }while(false);

  return $response;
}

/*============================================================================*\
  System command execution

  + fun stuff :)
  
  the $cmd is an array of 0 => system command 1..n => parameters to the command 
\*============================================================================*/
function execute_system_command($cmd){
  global $word_list;
  
  $response['error']='';
  $response['command'] = implode(" ",$cmd);
  
  switch ($cmd[0]){
    case "do": // ??
      if($_SERVER['SESSION']['trust']<80){$response['reply']="denied"; break;}
      $response['reply'] = print_r($cmd,true);
      break;
    // Explain error
    case "why":  
    case "explain":  
    case "analyse": 
      $response['reply']="That information is not available at this time";
      // Define origin of last commands (This terminal or user)
      if(isset($_SERVER['SESSION']['this_is'])) 
        $origin=$_SESSION['this_is'];
      else  
        $origin=$_SERVER['REMOTE_ADDR'];
      // Get last command event from this origin, within 15 minutes, where reply 
      // was an error or not ok
      $res = services("datastore","event.get",[
         "origin" => $origin
        ,"age" => "15 minutes"
      ]); 
      if(!empty($res["error"])){
        $response['reply']="Unable to access this information";
//        error(2,$response['reply'],false);
  //       break;
      }
      $level=1;

      // Find last error and increase diagnostic level each time the user ask
      if(is_array($res['result'])) foreach($res['result'] as $prev_event){ 
        if(isset($prev_event['error'])) break;
        if(in_array($prev_event['command'],array("why","explain","analyse")))     
          $level++;
      }      
//print_r($res);
break;
      // Analyse
      switch($level){
        case 1: // Why did it fail? bad command/script failure/device off-line
          $response['reply']=$prev_event['error'];
          break;

        case 2: // How bad is it?
        case 3: // What is the problem? Error message from script etc
          if($_SERVER['SESSION']['trust']<20){$response['reply']="denied"; break;}
          // seriousness
          $seriousness=array("","minor functional","functionl","section","system wide","total system");

          // Look for an error message
          $db_response=adb_rest("POST","/_api/cursor","",'{"query" : "'
            .'FOR e IN error'
           .'  FILTER e.message == \''.$prev_event['error'].'\''
            .'  SORT TO_NUMBER(e._key) DESC LIMIT 1'
            .' RETURN e "'
          .',"count": true}');

          // The DB failed
          if($db_response['error']){
            $response['error']="Unable to process request for data: ".$db_response['errorMessage'];
            error(3,$response['error'],false);
            break;
          }


          if($db_response['count']){
            $ermsg=$db_response['result'][0];
            if($level==2){
              // How bad is it?
              $response['reply']="there was a ".$seriousness[$ermsg['seriousness']]." failure ";
              if($ermsg['count']>1) $response['reply'].="last one was ";
              $response['reply'].=time_ago($ermsg['time'])." ago.";
            }else{
              // Wwere did it fail
              $response['reply']="an error was registres in the script $ermsg[file] at line $ermsg[line]";
              if($ermsg['count']>1) 
                $response['reply'].=". It has happened $ermsg[count] times before";
            }
          }else{
            $response['reply'].="There are no error records on this";
          }
           break;
          // Where in the system is there a problem? Which script

        case 4: // Find similar problems: same script errors?
          if($_SERVER['SESSION']['trust']<20){$response['reply']="denied"; break;}
          $response['reply']="hmm - let me think...";
          break;
        case 5: // Full repport
          if($_SERVER['SESSION']['trust']<20){$response['reply']="denied"; break;}
          $response['reply']="Its a tough one...";
          break;
        case 6:
          if($_SERVER['SESSION']['trust']<20){$response['reply']="denied"; break;}
          $response['reply']="An error occured... I think?";
          break;
        case 7:
          if($_SERVER['SESSION']['trust']<20){$response['reply']="denied"; break;}
          $response['reply']="It must be do to lack of foresight from the system designer!";
          break;            
        default:  
          if($_SERVER['SESSION']['trust']<20){$response['reply']="denied"; break;}
          $response['reply']="Are you sure you really wan't to know that?"; 
          $response['reply']="hmm - let me think...";
          $response['reply']="Its a tough one...";
          $response['reply']="An error occured... I think?";
          $response['reply']="Sorry. I just don't know";
      }  
      break;
    case "up": // --
      // Add new event signal
      $response['context'] = 'back';
      $response['event'] = "change_context";
      $response['reply'] = "ok";
      break;
    case "top": // --
    case "origin": // --
    case "root": // --
      // Add new event signal
      $response['event'] = 'change_context';
      $response['context'] = $cmd;
      $response['reply'] = "ok";
      break;
    case "reload": // Reload current page
      if($cmd[1]=='all'){
        if($_SERVER['SESSION']['trust']<20){$response['reply']="denied"; break;}
        $response['event']="broadcast_reload_page";
      }else  
        $response['event']="reload_page";
      break;
    case "red":
    case "yellow":
    case "green":
    case "blue":
      if(@$cmd[2]!='off'){
        if($_SERVER['SESSION']['trust']<20){$response['reply']="denied"; break;}
        if($cmd[1]!="alert") break;
        // Send alert signal
        $response['event']="broadcast_".$cmd[0]."_".$cmd[1];
        $response['reply']=$cmd[0]." ".$cmd[1]."!";
        file_put_contents('var/alert.dat',$cmd[0]);
        break;
      }
    case "alert":
      if($_SERVER['SESSION']['trust']<20){$response['reply']="denied"; break;}
      if ($cmd[1] == "off"){
        // Send alert signal
        $response['event']="broadcast_alert_off";
        file_put_contents('var/alert.dat',"");
        $response['reply'] = "ok";
      }elseif( $cmd[1] == "mute"){
        $response['event']="broadcast_alert_mute";
        $response['reply'] = "ok";
      }elseif($cmd[1] == "mute"){
        $response['event']="broadcast_alert_mute";
        $response['reply'] = "ok";
      }
      break;
    case "mute":  
      if( $cmd[1] == "alert"){
        $response['reply'] = "ok";
        $response['event']="broadcast_alert_mute";
      }
      break;
    // Play things for talk interface
    case "computer":
      $response['reply']="ready";
      break;

    case "wait":
      $response['reply']="working";
      break;

    case "access":
      $response['reply']="denied";
      break;

    case "test":
      $response['error']="Test generated an error message on purpos";
      error(5,$response['error'],false);
      //$response['reply']="Please do, always!";
      break;

    case "delete": // --
      if($context=="/system/error/" && $cmd[1]=="all"){
        @unlink("error_log.dat");
        $DBresponse = adb(array("method"=>"put","api"=>"/_api/collection/error/truncate"));
        $response['error']=$DBresponse['error'];
        // Add new event signal
        $signal['session id']=session_id();
        $signal['context']=$context;
        send_event_signal($signal);
      }else
        $response['error']="there is no function to accommodate this request ".$context;
      break;
    case "hi":
    case "hello":
    case "howdy":
      $response['reply'] = 
         "Hi there.\n"
        ."This is a command interface for the SmartCore\n"
        ."Type the word 'help' for a general description og commands \n"
        ."or just try some commands in this window"
      ;
      break;
    case "pod":
      $response['reply'] = "I'm sorry. I'm afreaid that I can't let you do that";
      break;
    case "no":
      $response['reply']="ok, then don't do it";
      break;
    case "yes":
      $response['reply']="ok then";
      break;
    case "how":
      if($cmd[1] == 'are' && $cmd[2] == 'you')
        $response['reply']="I'm fine thanks. How are you?";
      break;
    case "fuck":
      $response['reply']="I'm sorry. I'm unable to performe that function with the current interface";
      break;
    case "say":
      if(count($cmd)<2){
        $response['error'] = "What should I say?";
        break;
      }
      $response['reply'] = "";
      foreach($cmd as $i=>$word) 
        if($i>0) 
          $response['reply'] .= "$word ";
      break;
    case "verify":
      $response['error']="The VERIFY command is not yes implementet.";
      break;
    case "if":
      $response['error']="The IF command is not yes implementet.";
      break;       
    case "help":
    case "?":
      $response['reply'] = "ok";
      $response['result'] = 
         "please use structured context based language\n"
        ."In general you might state one or all of the following, in that order:\n"
        ." 1 specify the context eg. room or place in question\n"
        ." 2 Specify the name of the interaction you wish to operate\n"
        ." 3 specify the operation you wist to performe\n\n"
        ."Example: livingroom desk light on\n" 
        ."There are some system commands as well.\n"
        ."type 'list system commands' for more help"
      ; 
      break;
    case "list":
      switch ($cmd[1]){
        case "system":
          foreach($word_list as $c)
            foreach($c as $word => $type)
              if($type == 's') 
                $response['result'][] = $word;
          break;
        case "contexts":
        case "places":
          foreach($word_list as $c)
            foreach($c as $word => $type)
              if($type == 'c') 
                $response['result'][] = $word;
          
          break;
        default:
          $response['reply'] = 
            "Please specify 'list system commands' or 'list places'?";
      }
      if(!empty($response['result']))
        $response['reply'] = "ok";
      break;
  }

  if(@$response['reply'] == "denied")
    $response['error'] = 
      "You do not have sufficient privileges to perform that action on this system";

  if(empty($response['reply'])){
    $response['error']="The command '"
      .implode(" ",$cmd)
      ."' was not fully appriciated by the interpreter. Please clarify";
    $response['reply'] = "unable to comply";
  }
  
  if(!empty($response['error']) && empty($response['reply']))
    $response['reply'] = "failed";
    
  if(empty($response['reply']))
    $response['reply'] = "unable to comply";
    

  return $response;
}


?>

