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
$title="Power monitor and management";
$hint=$title;

/*============================================================================*\
  Generate HTML to display tile
\*============================================================================*/
// Outer tile container
echo "<div id=\"$title\" class=\"tile\" title=\"$title\" alt=\"$hint\"";
// Action
echo " onClick=\"set_context('$context');\"";
// Icon
echo " style=\"background-image: url(/theme/power-ctx.png);";
// Center text
echo "display: table; text-align: center; width: 400px\">";

echo " <span style=\"display:table-cell; vertical-align:bottom;";
echo " background-color:inherit; text-align: center; \">";

echo "<table><tr>";
//echo '<td><img src="/theme/power-ctx.png" width="100" height="100" alt="Power" /></td>';
echo "<td><img src=\"/theme/chart1.png\" width=\"108\" height=\"100\" alt=\"\" class=\"chart\"/></td>";
echo "<td><img src=\"/theme/red-light.png\" width=\"25\" height=\"25\" alt=\"\" class=\"On\"/><br>1482 W</td>";
echo "</tr></table>";
echo "</span>\n";

// End container
echo "</div>\n";
?>

