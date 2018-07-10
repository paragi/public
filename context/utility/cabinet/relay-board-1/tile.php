<?php
/*============================================================================*\
  Generic 8 bit relay board tile 

  Tile randering

  The code in here makes the HTML to display the current state of the tile.

  Images used are found with the image_path(<image file name>) function.

  This code is are called to render the tile whenever there is a state or status 
  change in a related event 

  output are buffered and send as an event to the clients

  The following variables are set when this script is called:

    $context  Full path context
    $state    State of interaction or null if off-line
\*============================================================================*/
$context_str = substr(dirname( __FILE__ ) . '/', strpos(__FILE__,"context") + 7);
$ia = $context_str . "relay";
$watch_list[$ia] = $ia;

$title = ucfirst(substr($context_str,strrpos($context_str,"/",-2)+1,-1));
$hint=$title;

$icon['on'] = image_path("panel-switch[on].png"); 
$icon['off'] = image_path("panel-switch[off].png"); 
$icon['off-line'] = image_path("panel-switch[off-line].png"); 

$bits = 8;
/*============================================================================*\
  Generate HTML to display tile
\*============================================================================*/
$switch_style ='
<style>
.sw{
  border: 10px;
  background-size: 100% 100%;
  background-repeat:   no-repeat;
  background-position: center center; 
  height: calc(var(--module-size) / 2);
  cursor: pointer;
}
</style>';
$container_style = '
  display: table; 
  text-align: center;
  width: calc(var(--module-size) * 2);
  background:#77a;
  cursor: auto;
';

// Outer tile container
echo "<div id=\"$ia\" class=\"tile\" title=\"$title\" alt=\"$hint\"";
echo " style=\"$container_style\">\n";
echo "$switch_style\n";
echo "<table style=\"width:100%;\"><tr><td colspan=\"100\">$title</td></tr><tr>\n";

//if(empty($state) || !is_numeric($state)) $state = 0; 

for($i=$bits; $i>0; $i--){
  if(!is_numeric($state)){ 
    $s = 'off-line';
    $action = '';
  }else{
    $s = ($state & 1<<($i-1)) ? 'on' : 'off';
    $action = "cmd('$ia set " . ($state ^ 1<<($i-1)) ."')";
  }  
  echo "<td class=\"sw\" style=\"background-image: url($icon[$s]);\""; 
  echo " onclick=\"$action\" title=\"relay $i\"></td>\n";
}
echo "</tr></table>\n";

// End container
echo "</div>\n";
?>

