<?php
/*============================================================================*\
  Access security. 
  Think carefull about who has access to this funtion and how sensitive it is!
\*============================================================================*/
$read_sensitivity=60; 
$write_sensitivity=80;
if ($trust<$read_sensitivity) return;

$title="System error reports";
$hint=$title;
/*============================================================================*\
   Access security. 
\*============================================================================*/

if ($TRUST<$read_sensitivity) return;
$action = $TRUST >= $write_sensitivity ? true : false;

require "$_SERVER[DOCUMENT_ROOT]/services.php";
// ================================================================================

?>
<style>
td{
  background-size: contain;
  background-repeat: no-repeat;
  background-position: center;
  cursor: pointer; 
}
</style>
<script>
<?php
  $check_icon = image_path('check.png');
  $new_icon = image_path('new.png');

  echo "var check_icon = 'url(\"$check_icon\")';\n";
  echo "var new_icon = 'url(\"$new_icon\")';\n";
?>

  function toggleAck(elm,id){
    if(Number.isInteger(id)){
      ps.services('datastore','error.toggleAcknowledge',id, function(response){
        if(response.reply == 'ok')
          elm.style.backgroundImage = 
            elm.style.backgroundImage == check_icon ? new_icon : check_icon;
      });
    }      
  }

  function purgeErrorReports(){
    ps.services('datastore','error.purgeAcknowledgedReports','',function(){
      location.reload();
    });
  }
  
  function AcknowledgeAllErrorReports(){
    ps.services('datastore','error.acknowledgedAllReports','',function(){
      location.reload();
    });
  }
  
  function getErrorRepport(id){
    ps.services('datastore','error.get',id,function(response){
      if(response.reply == "working") return;
      document.getElementById('description-container').style.display = 'block';
      document.getElementById('description').innerHTML = 
        JSON.stringify(response.result,null,2);
    });
  }
  
  document.onkeyup = function(evt) {
    if (evt.keyCode == 27)
      document.getElementById('description-container').style.display = 'none';
  };


  document.onclick = function(e) {
    if(e.target.id != 'timer-input-container' && !e.srcElement.onclick)
      document.getElementById('description-container').style.display = 'none';
  }

  ps.services('event','subscribe','datastore.error.get');
</script>
<!-- Full error repport box -->
<div id="description-container" class="container" style="
  position: fixed;
  left: 50%;
  top: 50%;
  transform: translate(-50%,-50%);
  white-space: nowrap;
  width: 80%;
  height: 80%;
  overflow:scroll;
  z-index:5;
  display: none;
">
<button onclick="document.getElementById('description-container').style.display='none';">Hide</button>
<hr>
<div id="description" style="
  unicode-bidi: embed;
  font-family: monospace;
  white-space: pre-wrap;
  overflow-wrap: break-word;
"></div>
</div>

<?php
// ================================================================================
// Execute functions on the error log
// ================================================================================
// Purge acknowledged error reports
if($trust >= $write_sensitivity){
  echo "<div class=\"tile\" style=\"background-image:"
    . "url(" . image_path('error-report-purge.png') . ");\""
    . "onclick=\"purgeErrorReports();\""
    . " title=\"Purge acknowledged error reports\"></div>";

  // Acknowledge all new error reports
  echo "<div class=\"tile\" style=\"background-image:"
    . "url(" . image_path('error-acknowledge.png') . ");\""
    . "onclick=\"AcknowledgeAllErrorReports();\""
    . " title=\"Acknowledge all error reports\"></div>";
}
  
echo '<div class="container">';
// List error reports
$response = services('datastore','error.list');

if(!empty($response['error'])) 
  trigger_error('List error reports failed. '.$response['error']);

echo "<table>\n";
$check_icon = image_path('check.png');
$new_icon = image_path('new.png');

if(!is_array($response['result']) || empty($response['result'][0][id])){
  echo "<tr><td>No errors to repport.</td>";
  echo "<td style=\"width: 7%; background-image: url(";
  echo image_path('smily.png') . "\"></td></tr>\n";

}else foreach($response['result'] as $repport){
  $onclick = "onclick=\"getErrorRepport('$repport[id]');\"";
  echo "<tr><td style=\"width: 7%; background-image: url(";
  echo $repport[acknowledged] ? $check_icon : $new_icon;
  echo ");\" onclick=\"toggleAck(this,$repport[id]);\">&nbsp;&nbsp;&nbsp;</td>\n";
  
  echo "<td style=\"width: 4%;\" $onclick>$repport[seriousness]</td>";
  
  echo "<td style=\"width: 70%;\" $onclick>$repport[message]</td>";

  echo "<td style=\"width: 15%;\" $onclick>";
  if(!empty($repport[file])) echo $repport[file];
  if(!empty($repport[line])) echo " on line $repport[line]";
  echo "</td>\n";
  
  echo "<td style=\"width: 10%;\" $onclick>$repport[count]</td>\n";

  echo "<td style=\"width: 10%;\" $onclick>";
  if(is_numeric($repport['time']))
    echo "At ". date("Y-m-d H:i:s",$repport['time']);
  echo "</td></tr>\n";
} 
echo "</table>\n";


echo "</div>";

?>


