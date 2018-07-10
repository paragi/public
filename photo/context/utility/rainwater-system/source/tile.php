<?php
/*============================================================================*\
  Tile randering

  The code in here makes the HTML to display the current state of the tile.

  Images used are placed in the /image folder

  This code are are called to render the tile whenever there is a state or status 
  change in a related event 

  output are buffered and send as an event to the clients

  The following variables are set when this script is called:

    $context  Full path context
   !-- $state    State of interaction or null if off-line
    
  
  PHP functions
  string $full_url_path = image_path(string $image_file_name)
  
  Return url to icon or image, adjusted for best match to selected theme.
  
  Javascript functions
  setContext(string context)
  cmd(string or array of string command [,string id])
  ...
  
  
  CSS Styling
  
  class container: Makes a framed workspace, that fills the width of the screen.
  
  class tile: makes a square border tile. The size depends on the view port size
  and proportions. The value of height and width is set to --module-size.
  
  CSS variables:
    --module-size:    Size in pixel of a square tile or button
    --module-shadow:  Size of box shadow etc.
    
  $watch_list[<id>] = <event name>
  $js = javascript
  
    
\*============================================================================*/
$title="Water source";
$sensitivity=array("get"=>40,"set"=>60);
if ($TRUST < $sensitivity['get']) return;

$watch_list['/utility/rainwater-system/source/switch'] = '/utility/rainwater-system/source/switch';

/*============================================================================*\
  Generate HTML to display tile
\*============================================================================*/
switch (@$state){
  case "on":
    $action = "cmd('/utility/rainwater-system/source/switch off')";
    $icon = image_path("rainwater-switch[on].png"); 
    break;
  case "off":
    $action = "cmd('/utility/rainwater-system/source/switch on')";
    $icon = image_path("rainwater-switch[off].png"); 
    break;
  default:
    $action = "cmd('/utility/rainwater-system/source/switch get')";
    $icon = image_path("rainwater-switch[off-line].png"); 
}  

echo "<div class=\"tile\" title=\"$title\" alt=\"$title\"";
echo " id=\"/utility/rainwater-system/source/switch\"";
echo " onClick=\"$action\"";
echo " style=\"background-image: url($icon); background-size:cover;";
echo " width: calc(var(--module-size) * 2)\">\n";
echo "</div>\n";
?>
