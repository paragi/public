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
  Generate HTML to display tile
\*============================================================================*/
// Outer tile container
$title  ="Identoty";
echo "<div id=\"$title\" class=\"tile\" title=\"$title\"";
// Action
echo " onClick=\"set_context('$context');\"";
echo " style=\"background-image: url(" . image_path("identity.png") . ");";
echo " display: table; text-align: center;\">";
echo " <span style=\"display:table-cell; vertical-align:bottom;background-color:inherit;";
echo " text-align: right; \">";
echo "Trustr: {$_SERVER['SESSION']['trust']}\n";
echo "Trust in terminal: {$_SERVER['SESSION']['trust_in_terminal']}\n";
echo "Suspiciousness: {$_SERVER['SESSION']['suspiciousness']}\n";
echo "Defensiveness: {$_SERVER['SESSION']['defensiveness']}\n";

echo "<img src=\"/theme/security-graph.png\" width=\"108\" height=\"100\" a class=\"chart\"/>";
echo "</span>\n";
// End container
echo "</div>";

?>
