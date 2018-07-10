<?php
/*============================================================================*\
  Manage list of words used by the command interpretor 
  
  word_list is an array where the key is a single word. 
  The value part is an array where the key is a full path/command and the value
  is the type: 
    c: context
    i: interaction or user program
    s: system command
    
  example: 
   'light' => [
     '/home/bedroom/ceiling/light' => 'i',
     '/demo/desk/light' => 'i',
   ]

  an update of words transverse the context tree, and gather words and there 
  contexts and type to compile the list.
  to that a list of predefined system words is added.

  the word list is stores as a php include file, defining the £word_list array. 

\*============================================================================*/
// File names
$word_file = $_SERVER['DOCUMENT_ROOT'] . "/var/words.php";
$unconfirmed_word_file = $_SERVER['DOCUMENT_ROOT'] . "/var/unconfirmed_words.php";
$previous_word_file = $_SERVER['DOCUMENT_ROOT'] . "/var/previous_words.php";

$word_list = [];
@include_once $unconfirmed_word_file;
if(is_array($word_list)) $unconfirmed_word_list = $word_list;
$word_list = [];
@include_once $word_file;
include_once "system_words.php";

// request mechanism
?>
<form method="post" name="talk_form">
<input type="hidden" name="func" value="">
</form>

<script type="text/javascript" >
function please(request){
  document.talk_form.func.value=request;
  document.talk_form.submit();
}
</script>
<style>
.count{
  position:absolute; 
  bottom:3%;
  left: 3%;
  background-color: #B11;
  border-radius: 2vh;
  padding: 4%;
  line-height: 0.7;
}
</style>

<?php 
/*============================================================================*\
  request buttons
\*============================================================================*/
// Show word list
$words = count($word_list);
echo '<div class="tile" onclick="please(\'list\');" style="background-image: ';
echo "url(" . image_path('talk-wordlist.png') .");";
// Place counter
echo " display: table; text-align: botton; vertical-align:bottom;\"";
echo " title=\"List of words\"> <span class=\"count\">$words</span>\n";
echo "</div>";

  // Update context word list
echo '<div class="tile" onclick="please(\'update\');" style="background-image: ';
echo "url(" . image_path('talk-update-wordlist.png') .");\"";
echo " title=\"Generate a new list of words\"></div>";

// Confirm update
if(($_POST['func'] != 'confirm' && !empty($unconfirmed_word_list)) 
  || $_POST['func'] == 'update'){
  $unconfirmed_words = count($unconfirmed_word_list);
  echo "<div class=\"tile\" onclick=\"please('confirm');\" style=\"background-image: ";
  echo "url(" . image_path('talk-confirm-update.png') .");";
  echo " display: table; text-align: botton; vertical-align:bottom;\"";
  echo " title=\"Confirm new list of words to replace old list\">";
  echo "</div>";
}

/*============================================================================*\
  Execute and output
\*============================================================================*/
//print_r(compact("unconfirmed_words","words"));
switch($_POST['func']){
  case "confirm":
    if(file_exists($unconfirmed_word_file)){
      rename($word_file,$previous_word_file);
      @rename($unconfirmed_word_file,$word_file);
    }
    break;
  case "list":
    if(is_array($word_list)){
      echo "<fieldset class=\"container\">";
      echo "<legend class=\"container\">Words used</legend><pre>";
      foreach($word_list as $word => $content){
        echo "$word:\n";
        foreach($content as $use => $type)
          echo "&nbsp;&nbsp;" . ($type == "s" ? "command $use\n" : "$use\n");
      }    
      echo "</pre></fieldset>\n";
    }
    break;

  case "update":
    // Make a new unconfirmed word list
    $unconfirmed_word_list = [];
    scan_context($unconfirmed_word_list);
    foreach($system_word_list as $word => $origin) 
      $unconfirmed_word_list[$word] = $origin;

    file_put_contents($unconfirmed_word_file
        ,"<?php\n\$word_list=" . var_export($unconfirmed_word_list,true) .";\n?>"
      );

  default:
    // Dispaly new words and differances in use
    if(!empty($unconfirmed_word_list)){
      echo "<fieldset class=\"container\">";
      echo "<legend class=\"container\">Changes</legend><pre>";
      if(!isset($changed) || $changed){
        foreach($unconfirmed_word_list as $word => $use){
          if(empty($word_list[$word])){ 
            echo "<span style=\"color: #4A0\">$word:</span>\n";
            $new_use = $use;
            $removed_use = false;
          }else{
            $new_use = array_diff_assoc($use,$word_list[$word]);
            $removed_use = array_diff_assoc($word_list[$word],$use);
            if(empty($new_use) && empty($removed_use)) continue;
            echo "$word:\n";
          }
          if(!empty($new_use)) foreach($new_use as $use => $type){
            if($type == 's') $use = "command $use";
            echo "<span style=\"color: #4A0\">&nbsp;&nbsp;$use</span>\n";
          }

          if(!empty($removed_use)) foreach($removed_use as $use => $type){
            if($type == 's') $use = "command $use";
            echo "<span style=\"color: #A40\">&nbsp;&nbsp;$use</span>\n";
          }
        }
        
        // Display removed words
        foreach(array_diff_assoc($word_list,$unconfirmed_word_list) as $word => $c)
          echo "<span style=\"color: #A40\">$word</span>\n";

      }else
        echo "No change\n";
        
      echo "</fieldset>\n";
    }
}

/*============================================================================*\
  Scan context for places, interactions and user programs
  
  Transverse the context directory tree to search for valid contexts, 
  interactions and user programs.
  
  If a directory contains a tile.php or index.php, that context is added as well.
\*============================================================================*/
function scan_context(&$content,$path='/'){
  // format path
  $path = str_replace("//","/",str_replace("..","",$path) . "/");
  $search_path = "$_SERVER[DOCUMENT_ROOT]/context" . $path;
  $current_context = substr($path,@strrpos($path,"/",-2));

  //echo "...Searching $path<br>";  
  // Interaction
  $a=glob("$search_path*.ia-dat",GLOB_NOSORT);
  if(is_array($a)) foreach($a as $i){
    $content 
      [substr($i,strrpos($i,"/")+1,-7)] 
      [substr($i,strpos($i,"/context/")+8,strrpos($i,".ia-dat")-strlen($i))] 
      = i;
  }
  
  // User program
  $a=glob("$search_path*-prg.php",GLOB_NOSORT);
  if(is_array($a)) foreach($a as $i){
    $content 
      [substr($i,strrpos($i,"/")+1,-8)] 
      [substr($i,strpos($i,"/context/")+8,strrpos($i,"-prg.php")-strlen($i))] 
      = i;
  }

  // Register context  
  if(strlen($current_context)<=1) $current_context = 'root';
  $content 
    [str_replace("/","",$current_context)] 
    [substr($search_path,strpos($search_path,"/context/")+8)] 
    = c;
  
  // Subdirectories
  $a=glob("$search_path*",GLOB_ONLYDIR|GLOB_NOSORT|GLOB_MARK);
  if(is_array($a)) foreach($a as $i){
    scan_context($content,substr($i,strpos($i,"/context/")+8));
  }

  return $content;
}
?>
</div>

