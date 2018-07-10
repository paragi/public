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
$title="System error repports";

/*============================================================================*\
  Icons for condition
    Green:  no errors
    Yellow: acknowledged error repports
    Red:    new error repports
\*============================================================================*/
$icon_json = "var icon = {"
  .   "green: \"url(" . image_path("error-report[green].png")
  . ")\", yellow: \"url(" . image_path("error-report[yellow].png")
  . ")\", red: \"url(" .image_path("error-report[red].png")
  . ")\", offLine: \"url(" .image_path("error-report[off-line].png")
  . ")\"};";
?>
<script>
  <?php echo $icon_json; ?>

  ps.on('datastore.error.count',function(event){
    var tile = document.getElementById('tile');
    var count = document.getElementById('error_message_count');
    
    if(event.error || event.rowCount != 1){
      tile.style.backgroundImage = icon.offLine;
      count.style.display = 'none';
    }else if(event.result[0].new == 0){
      if(event.result[0].total == 0){
        tile.style.backgroundImage = icon.green;
        count.style.display = 'none';
      }else{
        tile.style.backgroundImage = icon.yellow;
        count.style.display = 'inline';
        count.innerHTML = event.result[0].total;
      }  
    }else{    
      tile.style.backgroundImage = icon.red;    
      count.style.display = 'inline';
      count.innerHTML = event.result[0].new;
    }    
  });
  
  ps.services('event','subscribe','datastore.error.count');
  ps.services('datastore','error.count');
</script>

<style>
.message_count{
  position:absolute; 
  bottom:3%;
  left: 3%;
  background-color: #B11;
  border-radius: 2vh;
  padding: 4%;
  display: none;
  line-height: 0.7;

}
</style>
<?php
/*============================================================================*\
  Generate HTML to display tile
\*============================================================================*/
// Outer tile container
echo "<div id=\"tile\" class=\"tile\" title=\"$title\" alt=\"$title\"";
// Action
echo " onClick=\"set_context('$context');\"";
// Icon
echo " style=\"background-image: url(".image_path("error-report[off-line].png").");";
// Place counter
echo "display: table; text-align: botton; vertical-align:bottom;\">";
echo " <span id=\"error_message_count\" class=\"message_count\"></span>\n";
// End container
echo "</div>";
?>
