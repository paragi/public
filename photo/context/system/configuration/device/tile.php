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
    $state    State of interaction or null if off-line
\*============================================================================*/

/*============================================================================*\
  Access security. 
  Think carefull about who has access to this funtion and how sensitive it is!
\*============================================================================*/
$read_sensitivity=60; 
$write_sensitivity=80;
if ($_SERVER['SESSION']['trust']<$read_sensitivity) return;

$title="Device - direct access";
$hint=$title;

/*============================================================================*\
  Generate HTML to display tile
\*============================================================================*/
// Outer tile container
echo '<div id="'.$title.'" class="tile" title="'.$title.'"';

// Action
echo " onClick=\"set_context('$context');\"";

// Icon
echo " style=\"background-image: url({$_SESSION['theme']}device_handler.png);\">";

// End container
echo "</div>";
?>
