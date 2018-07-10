<?php
echo "<!----->\n";
echo '<div id="panel" class="panel">';
/*============================================================================*\
  Navication UP
\*============================================================================*/

// Find icon for this context
$this_name = substr($context,@strrpos($context,"/",-2)+1,-1);
if(empty($this_name)) $this_name = "start";
$icon_file = image_path("$this_name-ctx.png");
if(strpos($icon_file,"/default,png")>=0)
  $icon_file = image_path("$this_name.png");

?>
<div id="backButton" class="tile" onclick="back();">
  <img src="/theme/up.png" class="tile_flag" alt="Level up">
</div>

<script>
var thisPageIcon = <?php echo "\"$icon_file\""; ?>;
var context = <?php echo "\"$context\""; ?>;

// Load or create a history array
var pageHistory = JSON.parse(sessionStorage.pageHistory || '[]');

// Find this page in history
var thisPageIndex = -1;
for(var i in pageHistory){
  if(pageHistory[i].context == context){
    thisPageIndex = i;
    break;
  }
}

// If this page was not in the history, add it to the top
if( thisPageIndex < 0){
  pageHistory.push({context: context, image: thisPageIcon});
  thisPageIndex = pageHistory.length -1;
  
// Wipe the forward history
}else if(thisPageIndex < pageHistory.length -1){
  for(; thisPageIndex < pageHistory.length -1;)
    pageHistory.pop();
}

// Store history array   
sessionStorage.pageHistory = JSON.stringify(pageHistory);

// Set page up background image
document.getElementById("backButton").style.backgroundImage = 
  thisPageIndex > 0 ?
    'url(' + pageHistory[thisPageIndex-1].image + ')'
    :
    <?php echo "'url(" . image_path("world-ctx.png") . ")'";?> ;
        
// Back button function
function back(){
  if(thisPageIndex > 0 ) 
    set_context(pageHistory[thisPageIndex - 1].context); 
  else
    window.location.href = 'http://futu-rum.com/smartcore-world/entry.php';
}

window.history.back = back; // not work!

console.log("pageHistory",pageHistory);
</script>
<?php

/*============================================================================*\
  Talk box
  
\*============================================================================*/
if($_SERVER['SESSION']['trust']>60){
echo "<script type=\"text/javascript\" src=\"/talk.js\"></script>
<div class=\"talk\" onclick=\"Talk.TakeFocus()\">
<input type=\"text\" name=\"talk\" id=\"talk\" size=\"40\"
 class=\"talk-input\" placeholder=\"Talk here\" autofocus onblur=\"Talk.StayInFocus();\">
<div class=\"talk-output\" id=\"talk-output\"></div>
<img class=\"talk-monitor\" id=\"talk-monitor\" onclick=\"Talk.SetMonitor();\"
 src=\"/theme/monitor[off-line].png\">
</div>\n";
}
/*
<script>
var recognition = new webkitSpeechRecognition();
recognition.continuous = false;
recognition.interimResults = false;
recognition.lang = "en-US";
recognition.start();
recognition.onresult = function(e) { cmd(e.results[0][0].transcript);};
recognition.onerror = function(e) {
  recognition.stop();
}
</script>
<?php
*/

/*============================================================================*\
  Navigate links
\*============================================================================*/
if($context != '/system/alert/'){
  echo "<div class=\"tile\" onclick=\"set_context('/system/alert/');\"";
  echo " style=\"background-image: url(/theme/alert-ctx.png);\"></div>";
}
 //echo "<div class=\"tile\" onclick=\"\"";
// echo " style=\"background-image: url(/theme/messages-ctx.png);\"></div>";

if($context != '/system/identity/'){
 echo "<div class=\"tile\" onclick=\"set_context('/system/identity/');\"";
 echo " style=\"background-image: url(/theme/identity.png);\"></div>";
}
 echo "</div>\n<!----->\n";
?>

