
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
<body onkeypress="function(e){if(e.keyCode==13){document.forms[0].submit();}">
<h1>Simple interaction command interpreter teste</h1>
<!------------------------------------------------------------------------------
  Command submit form  
------------------------------------------------------------------------------->

<form method="post">
<label>Command: </label>
<?php
printf('<input name="cmd" size="50" type="text" autofocus value="%s"><br>'
  ,@$_REQUEST['cmd']);
?>
<input type="submit" style="visibility: hidden;" />
</form>
<!------------------------------------------------------------------------------
  Command selector
------------------------------------------------------------------------------->
<div class="container">
Select command:
<table>
<tr><td onclick="document.forms[0].cmd.value=this.innerHTML">/utility/system/template/bedroom/ceiling/light on</td></tr>
<tr><td onclick="document.forms[0].cmd.value=this.innerHTML">utility system template bedroom ceiling light on</td></tr>
<tr><td onclick="document.forms[0].cmd.value=this.innerHTML">utility system say  bedroom ceiling light on</td></tr>
<tr><td onclick="document.forms[0].cmd.value=this.innerHTML">why</td></tr>
<tr><td onclick="document.forms[0].cmd.value=this.innerHTML">test</td></tr>
<tr><td onclick="document.forms[0].cmd.value=this.innerHTML">say hello</td></tr>
<tr><td onclick="document.forms[0].cmd.value=this.innerHTML">access</td></tr>
<tr><td onclick="document.forms[0].cmd.value=this.innerHTML">wait</td></tr>
<tr><td onclick="document.forms[0].cmd.value=this.innerHTML">computer</td></tr>

</table>
</div>
<div class="container" id="output">
</div>

<div class="container">
<pre>
<?php
define('_DEV_DEBUG',true);


  //$cmd="light bedroom on ceiling ";
  //$cmd="light bedroom on ceiling do";
  //$cmd="say light bedroom on ceiling do";
//  $cmd="light bedroom";

 $_SESSION['context']='/home/bedroom/bedstand';
echo "Context is at: $_SESSION[context]\n";

  if(isset($_POST['cmd'])){
    $response=cmd_interpret($_POST['cmd']);
    echo "<script>document.getElementById('output').innerHTML='". json_encode($response)."';</script>";
echo"Response: ", print_r($response,true);    
  }
  
/*============================================================================*\
  Primary command interpretor

  This interpreter expects a well formed commands, using a predefined syntax.
  (No naturel languange)
  
  Machine commands takes the form of a formattet full path context/interaction
  string and an action. Ex.
    /utility/system/template/bedroom/ceiling/light on
  
  All other commands are interpreted as user commands. 
  if a reserved system command word are used, a system command is assumed.
  Otherwise this function will try to interpret the command as an interaction.
 
  Interpretation is done by relation and elimination of words that describe 
  known contexts, interactions or actions.
  
  The file words.php defines an associative array of paths,mwords and names, 
  used to interpret the command.
  
\*============================================================================*/
function cmd_interpret($cmd){
echo "Matching command: $cmd\n";
  do{
    // Remove special chars 
    $cmdl2=preg_replace('/[^A-Za-z0-9\- .\/_]/','',trim(strtolower($cmd)));
    // trim array  and split into words
    $command=array_filter(explode(" ",$cmdl2));

    /*========================================================================*\
      Categorise command
    \*========================================================================*/
    // Empty
    $words=count($command);
    if($words<1){
      $response['error']="No command actually given";
      break;
    }

    // Try machine syntax
    $words=count($command);
    if($words<=2 && $words>=1 && $command[0][0]=="/"){
      // Isolate components
      $s=strrpos($command[0],"/");
      $simple_cmd['context']=substr($command[0],0,$s+1);
      $simple_cmd['interaction']=substr($command[0],$s+1);
      if($words<2)
        $simple_cmd['action']='get';
      else  
        $simple_cmd['action']=$command[1];
echo "Identified as machine syntax of an interaction command: \n".print_r($simple_cmd,true);
      break;
    }

    // Compare words against word list and try to narrow options down
    // context must be part of interaction
    @include_once "words.php";
    if(!is_array($word_list)){
      $response['reply']="failed";
      $response['error']="The word list is off-line";
      break;
    }else{

      // Try system command
      if(reset($word_list[$command[0]]) =='s'){
echo "Identified as a system command: $command[0]\n";    
        break;
      }
    
      /*======================================================================*\
        Interpret by relation and elimination
        Try to match words to known context and interaction names
      \*======================================================================*/
      // Process each word
      foreach($command as $i=>$word){
echo "Matching --$word--\n";    
        if(is_array($word_list[$word])){
          // If no options, choose all
          if($i==0)
            $option=$word_list[$word];
          else{
            // Intersect current options with words options
            $new='';
            foreach($option as $opath=>$otype){
              foreach($word_list[$word] as $path=>$type){
                if($otype=='i' && $type=='i' && $path==$opath) 
                  $new[$path]='i'; // i i Match 
                elseif($otype=='i' && $type=='c' && strpos($opath,$path)===0) 
                  $new[$opath]='i';  // c part of i (ic)
                elseif($otype=='c' && $type=='i' && strpos($path,$opath)===0) 
                  $new[$path]='i';  // c part of i (ci)
                elseif($otype=='c' && $type=='c')
                  if(strlen($path)>strlen($opath)){
                    if(strpos($path,$opath)===0)
                      $new[$path]='c';  //  c part of c
                  }else{  
                    if(strpos($opath,$path)===0)
                      $new[$opath]='c';  // c part of c
                  }
                // Keep actions and system actions  
                elseif(!strpbrk('ic',$otype) || !strpbrk('ic',$type)){
                  $new[$opath]=$otype;  // save other
                  $new[$path]=$type;  // save other
                }
              }
            }
            $option=$new;
          }
        } 
echo "Count: ",count($option),"\n";
print_r($option);     
      }

      // Count options    
      foreach($option as $path=>$type) $c[$type]++;
      if($c['a']>1){
        // Too many actions
        $reason="The command was too complex (".($c['a']+$c['s']).") simultanious actions";
        break;

      // Refine match by comparing with current context
      }elseif($c['i']>1 || ($c['i']!=1 && $c['c']>1)){
echo "Matching with current context ($_SESSION[context])\n";
        $new=[];
        foreach($option as $path=>$type){
          if($type=='i' && strpos($path,$_SESSION['context'])===0)          
            $new[$path]=$type;  // Is in context
          elseif($type=='c')
            if(strlen($path)>strlen($context)){
              // is in context ?
              if(strpos($path,$_SESSION['context'])===0) $new[$path]='c';  
            }else{   
              // Is in lower context branch
              if(strpos($_SESSION['context'],$path)===0) $new[$_SESSION['context']]='c';
            }
          elseif(!strpbrk('ic',$type))
            $new[$path]=$type;  
        }
   
        // Recount options   
        $c2=[]; 
        foreach($new as $path=>$type) $c2[$type]++;
        if($c2['i']+$c2['c']!=0){
          $option=$new;
          $c=$c2;
        }
        if($c2['i']>1 || ($c2['i']!=1 && $c2['c']>1)){
          // Too many options
          $reason="The command was too ambiguous. There are ". ($c['i']+$c['c']) ." likely interpretations";
          break;
        } 
      }
      
      // Find out if previous command gives a hint
      // to do
      // Is it an interaction command?
      if($c['i']==1){
        // Compose interaction format command
        if( $c['a']<1) $simple_cmd['action']='get';
        foreach($option as $path=>$type)
          if($type=='i'){
            $s=strrpos($path,"/");
            $simple_cmd['context']=substr($path,0,$s+1);
            $simple_cmd['interaction']=substr($path,$s+1);
          }elseif($type=='a') 
            $simple_cmd['action']=$path;
      // Compose set context command
      }elseif($c['c']==1){
        foreach($option as $path=>$type)
          if($type=='c'){
            $simple_cmd['context']=$path;
            break;
          }

      // See if result makes sense
      }elseif($c['i']>1 || $c['c']>1 || $c['a']>1 || $c['s']>1){
        // Too many options
        $reason="The command was too ambiguous. There are ".($c['i']+$c['c'])." likely interpretations";
        break;    
      }else{
        // Makes no sense
        $reason="XUnable to make a meaningful interpretation".print_r($c,true);
        break;    
      }
    }
  }while(false); 

echo "Final count: ",@count($option),"\n";
@print_r($option);           
@print_r($response);

  // Make error reply
  if(isset($response['error'])){
    if(!isset($response['reply'])){  
      $response['reply']="unable to comply";
    }
    
  }else{
  
    // execute simple command  
    if(!@$reason && is_array($simple_cmd)){

      // Execute simple command
      if(!@empty($simple_cmd['interaction']))
        $response=Tinteraction($simple_cmd);
      
      // change context
      else if($simple_cmd['context']){ 
        $response['event']="change_context";
        $response['context']=$simple_cmd['context'];
        $response['reply']='ok';
      }

      // Store last used context and interacttion
      if($simple_cmd['context']) 
        $_SESSION['context']=$simple_cmd['context'];
      if(@$response['interaction']) 
        $_SESSION['interaction']=$response['event']=$simple_cmd['interaction'];
    
    // system command  
    }else{
      // use from first occurence of a system command
      foreach($command as $i=>$word)
        if(reset($word_list[$word])=='s'){
          // parse to system command interpretor
          $response=Tcmd_execute(array_slice($command,$i));
          $response['event']=$word;
          break;
        }
    
       // Give up: Not simple and not system command    
       if(!$response){
         $response['reply']="unable to comply";
         if($reason)
           $response['error']=$reason;
         else
          $response['error']="Unable to make a meaningful interpretation";
       }
     }
  }

  return $response;
}

function Tinteraction($cmd){
  echo "interaction command to execute: \n".print_r($cmd,true);
  $response['reply']="ok";        
  return $response;
}

function Tcmd_execute($cmd){
  echo "System command to execute: \n".print_r($cmd,true);
  $response['reply']="ok";        
  return $response;
}
?>
</div>
</pre>
</body>
</html>
