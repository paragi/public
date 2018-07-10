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

$title="Timer";
$hint=$title;

/*============================================================================*\
  Generate HTML to display tile
\*============================================================================*/
// Outer tile container
echo "<div id=\"$title\" class=\"tile\" title=\"$title\"";
// Show database state icon
echo " alt=\"$hint\" style=\"background-image: url(/theme/timer.png);\"";
// Action
echo " onClick=\"set_context('$context');\">";
// End container
echo "</div>";
?>
