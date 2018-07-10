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

$title="Database";
$hint=$title;

/*============================================================================*\
  Get DB status
\*============================================================================*/
require $_SERVER['DOCUMENT_ROOT']."/services.php";

// Test database connection
$response = services("datastore","db.state");
if($response['error']){
  $icon="database[red].png";
  $datastoreDBState = "Off-line";
}else{ 
  // Get disk free space
  exec("df /var/lib/postgresql/ 2>&1",$output,$rc);
  foreach($output as $line){
    //$line = strtolower(preg_replace('/[^A-Z a-z0-9\-]/', '', $line));
    $response['result'][]  = explode(",",preg_replace('/\s+/', ',', $line));
  }
  
  if($response['result'][2][4] > 90){
    $icon="database[yellow].png";
    $datastoreDBState = "Require attention";
  
  }else if(empty($response['state']) || $response['state'] != 'on-line'){
    $icon="database[yellow].png";
    $datastoreDBState = "Require attention";
  }else{
    $icon="database[green].png";
    $datastoreDBState = "Ok";
  }  
}

/*============================================================================*\
  Generate HTML to display tile
\*============================================================================*/
// Outer tile container
echo "<div id=\"$title\" class=\"tile\" title=\"$title\"";
// Show database state icon
echo " alt=\"$hint\" style=\"background-image: url(/theme/$icon);\"";
// Action
echo " onClick=\"set_context('$context');\">";
// End container
echo "</div>";

?>
