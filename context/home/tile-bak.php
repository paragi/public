<?php
/*============================================================================*\
  Tile randering

  The code in here makes the HTML to display the current state of the tile.

  Images used are placed in the /theme folder

  This code are are called to render the tile whenever there is a state or status 
  change in a related event 

  output are buffered and send as an event to the clients

  The following variables are set when this script is called:

    $context  Full path context
    $state    State of interaction or null if off-line
    
    
  This script can set the following for events that needs to monitored:

    $watch_list[<tag id>] = <full context interaction name>;
   
  opon return, the event will be added to the watch list.
  When ever the state of that event changes, this script will be called to render
  the tile, with the new state.

  Interactions in the watch list will be requested for state, immidiatly after 
  this script has returned.
   
\*============================================================================*/

/*============================================================================*\
  Generate HTML to display tile
\*============================================================================*/
//$base=dirname(__FILE__);
	//$base=dirname($_SERVER['REQUEST_URI']).$tile;
	//hputs('base:'.$path);
	//print_r_html($_SERVER);
	//puts('<base href="'.$path.'">');

//  style=\"background-image:url(/theme/home-ctx.png);"; 
//  echo " width:35%; background-size: cover;\"
  
	echo "<div id=\"home\" class=\"tile\" style=\"width:auto;background-image: url(/theme/home-ctx.png);padding-left:20%\" onClick=\"set_context('$context');\" title=\"Home\">";

  echo "<table><tr><td></td>";

  echo "<td>Simon<br>Karin<br>Alvin</td>";
  echo "<td style=\"text-align:right;\">20,7<br> Open</td><td>&degC<br> </td></td>";

  echo "</tr></table></div>";
?>
