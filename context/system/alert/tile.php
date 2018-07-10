<?php
$title="Alert";
$sensitivity=array("get"=>20,"set"=>20);
if ($TRUST < $sensitivity['get']) return;

echo '<div class="tile" title="Alerts" onclick="set_context(\''. $context .'\')"';
echo 'style="background-image: url('.image_path("alert.png").')">';
echo "</div>";

// What about a dangeling bell?
?>

