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
    
\*============================================================================*/
$title="Weather";
$hint=$title;
$sensitivity=array("get"=>20,"set"=>20);
if ($TRUST < $sensitivity['get']) return;

/*============================================================================*\
  Generate HTML to display tile
\*============================================================================*/
$chart = "http://www.yr.no/sted/Danmark/Hovedstaden/Annisse/meteogram.png";
echo "<div class=\"tile\" title=\"$hint\" alt=\"$title\"";
echo " onClick=\"set_context('$context');\"";
echo " style=\"background-image: url($chart); background-size:cover;";
echo " width: calc(var(--module-size) * 2)\">\n";
echo "</div>\n";
?>


