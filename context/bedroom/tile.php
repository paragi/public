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

$title="Simon's bedroom";
$hint=$title;

/*============================================================================*\
  Generate HTML to display tile
\*============================================================================*/


// Outer tile container
echo '<div id="'.$title.'" class="tile" title="'.$title.'"';
// Action
echo ' onClick="set_context(\''.$context.'\');">';

// Display icon and room temperature
echo '<table><tr>';
echo "<td><img src=\"/theme/bedroom-ctx.png\" alt=\"".$hint."\" width=\"100\" height=\"100\" ></td>";
echo '<td style="text-align:right;">20,7</td><td>&degC</td></td>';
echo '</tr></table>';

// End container
echo "</div>";

?>
