<?php

if(!@in_array($state,["on","off","off-line"]))
  $state = "on";
$ia = "/example/switch";

$icon['on'] = image_path("switch:on.png"); 
$icon['off'] = image_path("switch:off.png"); 
$icon['off-line'] = image_path("switch:off-line.png"); 

$title = "Example switch";
// Outer tile container
echo "<div id=\"$ia\" class=\"tile\" title=\"$title\"";
// Show database state icon
echo " alt=\"$ia\" style=\"background-image: url(" . $icon[$state] . ");\"";
// Action
echo " onClick=\"cmd('$ia toggle');\">";
// End container
echo "</div>";
?>

