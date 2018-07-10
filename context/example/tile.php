<?php
  if(!@in_array($state,["on","off","off-line"])) $state = "off";
  $set_state_to = $state == "off" ? "on" : "off";
  $ia = $context . "switch";
  $title = "Example switch";

  echo "<div id=\"$ia\" class=\"tile\" title=\"$title\"";
  echo " alt=\"$ia $set_state_to\"";
  echo " style=\"background-image: url(" . image_path("switch:{$state}.png") .");\"";
  echo " onClick=\"cmd('$ia $set_state_to');\">";
  echo "</div>";
?>
