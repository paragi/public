<?php
/*============================================================================*\
  Access security. 
  Think carefull about who has access to this funtion and how sensitive it is!
\*============================================================================*/
$read_sensitivity=60;
$write_sensitivity=80;
$title="Device handler";
$hint=$title;
/*============================================================================*\
   Access security. 
\*============================================================================*/

if ($_SERVER['SESSION']['trust']<$read_sensitivity) return;
$action=false;
if ($_SERVER['SESSION']['trust']>=$write_sensitivity) $action=true;


/*============================================================================*\
  Find device handlers

  Device hyandlers are grouped into hardware interface type.
  There is a subdirectory for each group.
\*============================================================================*/
// List files in tree, matching wildcards * and ?
function tree($path){
  static $match,$base_length;

  if(empty($base_length)) $base_length=strlen($_SERVER['DOCUMENT_ROOT']."/device/");

  // Find the real directory part of the path, and set the match parameter
  $last=strrpos($path,"/");
  if(!is_dir($path)){
    $match=substr($path,$last);
    while(!is_dir($path=substr($path,0,$last)) && $last!==false)
      $last=strrpos($path,"/",-1);
  }
  if(empty($match)) $match="/*";
  if(!$path=realpath($path)) return;

  // List files
  foreach(glob($path.$match) as $file){
    $list[]=substr($file,$base_length);
  }  

  // Process sub directories
  foreach(glob("$path/*", GLOB_ONLYDIR) as $dir){
    $list[substr($dir,strrpos($dir,"/",-1)+1)]=tree($dir);
  }
  
  return @$list;
}


// Get list of device handlers
$list['Generic']=tree($_SERVER['DOCUMENT_ROOT']."/device/*.php");



/*============================================================================*\
  Load handler
\*============================================================================*/

if(@$_GET['hnd']){
  $handler_class="device\\".strtr(substr($_GET['hnd'],0,strrpos($_GET['hnd'],".")),"/","\\");
  $handler=new $handler_class;
}  
  
 /*============================================================================*\
  List devices
\*============================================================================*/
if(@$_GET['hnd']){
  $response=$handler->handler("list");
  if($response['error']) echo "error: ". $response['error'];
  $device_list=$response['result'];
}

/*============================================================================*\
  Show selected device
\*============================================================================*/
if($_GET['hnd'] && $_GET['uid']){
  echo '<div class="container">';

  // Get description
  $response=$handler->handler("description");
  if($response['result']) echo $response['result']."<br><br>";
  echo "Handler: ".$_GET['hnd']."<br>\n";
  echo "Unit ID: ".$_GET['uid']."<br>\n";
  
  echo "</div><br>\n";
}

/*============================================================================*\
  Make function box
\*============================================================================*/
echo '<form name="hidden_form" method="GET">';
echo '<input type="hidden" name="context" value="'.$_GET['context'].'">';
echo '<input type="hidden" name="hnd">';
echo '<input type="hidden" name="uid">';

/*============================================================================*\
  Execution form for device
\*============================================================================*/
if($_GET['hnd'] && $_GET['uid']){
  echo '<div class="container">';
  echo '<table><tr><td>';

  // List commands
  $response=$handler->handler("capabilities");
  if(is_array($response['result'])){
    echo '<table><tr><th>Commands:</th></tr>';
    foreach($response['result'] as $c)
      echo "<tr class=\"sel\">"
        ."<td onclick=\"document.hidden_form.cmd.value='$c';\">"
        ."$c</td></tr>\n";
    echo "</table>";
  } 
  
  echo "</td><td>";
  echo "Command: <input type=\"text\" name=\"cmd\" autofocus autocomplete"
    ."  value=\"".@$_GET['cmd']."\">"
    ."<button onclick=\""
    ."document.hidden_form.hnd.value='{$_GET['hnd']}';"
    ."document.hidden_form.uid.value='{$_GET['uid']}';"
    ."document.hidden_form.submit();\">"
    ."Send</button><br>";

  if(@$_GET['hnd'] && @$_GET['uid'] && @$_GET['cmd']){
    echo "<br><hr><b>Response:</b><br><pre>";
    print_r($response=$handler->handler(substr($_GET['cmd'],0,50),@$_GET['uid']));
    echo "</pre>";
  }

  echo '</td></tr></table>';
  echo '</div>';
}


/*============================================================================*\
  Show list of device handlers
\*============================================================================*/
echo '<div class="container">';
echo '<table><tr><th>Device handlers:</th></tr>';

function print_tree($list){
  static $indentation=0;
  
  if(is_array($list)) foreach($list as $group=>$name){
    if(is_array($name)){
      echo "<tr><th colspan=2>".str_repeat("&nbsp;",$indentation*2).$group."<hr></th></tr>\n";
      $indentation++;
      print_tree($name);
      $indentation--;
    }else{
      // List a handler
      echo "<tr class=\"sel\">"
      ."<td onclick=\""
      ."document.hidden_form.hnd.value='$name';"
      ."document.hidden_form.submit();\">"
      .str_repeat("&nbsp;",$indentation*2)
      .substr($name,strrpos($name,"/")+1)."</td></tr>\n";
    }
  }
}

print_tree($list);
echo '</table></div>';

/*============================================================================*\
  Show devices to that handler
\*============================================================================*/
if(@is_array($device_list)){

  echo '<div class="container">';
  echo '<table><tr><th>Unit (IDs):</th></tr>';
  
  // List available devices
  foreach($device_list as $uid){
    echo "<tr class=\"sel\">";
    echo "<td onclick=\""
      ."document.hidden_form.hnd.value='{$_GET['hnd']}';"
      ."document.hidden_form.uid.value='{$uid}';"
      ."document.hidden_form.submit();\">"
      ."$uid</td></tr>";
  }
  echo '</table>';
  echo '</div>';
}

echo '</form>';
echo '</div>';
?>


