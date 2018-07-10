<?php
/*============================================================================*\
  -- This section is all about login security. Be carefull! ---
\*============================================================================*/
$title="Terminal";
$sensitivity=array("get"=>20,"set"=>80);
if ($_SERVER['SESSION']['trust'] < $sensitivity['get']) return;

/*============================================================================*\
\*============================================================================*/
require "$_SERVER[DOCUMENT_ROOT]/net_trace.php";
require "$_SERVER[DOCUMENT_ROOT]/services.php";
require "$_SERVER[DOCUMENT_ROOT]/present.php";
/*============================================================================*\
  Display page
    
\*============================================================================*/
echo '<div class="container"><table>';
echo '<tr><td colspan=2><b>Terminal:</b></td></tr>';
echo '<tr><td>Terminal name</td><td>'.$_SERVER['SESSION']['terminal_name'].'</td></tr>';
echo '<tr><td>IP address</td><td>'.$_SERVER['SESSION']['ip'].'</td></tr>';
echo '<tr><td>Location</td><td>'.$_SERVER['SESSION']['location'].'</td></tr>';
echo '<tr><td>Trust in terminal</td><td>'.$_SERVER['SESSION']['trust_in_terminal'] .'</td></tr>';
echo '<tr><td>Current suspiciousness</td><td>' .$_SERVER['SESSION']['suspiciousness']
.'</td></tr>';
echo '<tr><td>Connection time</td><td>',time_ago($_SERVER['SESSION']['start']/1000) ,"</td></tr>";
echo '</table></div>';

echo "<div class=\"tile\" onclick=\"submit('trace')\">Trace<br>terminal</div>";
echo "<div class=\"tile\" onclick=\"submit('show')\">Show all</div>";


switch(@$_POST['func']){
  case "trace" : 
    echo "<div class=\"container\">";
    $trace=net_trace($_SERVER['REMOTE_ADDR']);
    if(!empty($trace['error'])){
      echo "$trace[error]</div>\n";
      break;
    }  
 
    echo "<table>";
    echo "<tr><td colspan=2><b>Network trace:</b></td></tr>";
    foreach($trace['result'] as $station)
      echo "<tr><td>$station[ip]</td><td>$station[location]</td></tr>\n";
      echo "<tr><td>Hops</td><td>". count($trace['result'])."</td></tr>\n";
    echo "</table></div>\n";
    break;
  
  case "show" :
    echo "<div class=\"container\">";
    echo "<pre>".print_r($_SERVER['SESSION'],true)."</pre>";  
    echo "</div>";
    break;
}
?>
</div>
<form name="hiddenform" method="POST">
<input type="hidden" name="func">
</form>


</div>
<script type="text/javascript">
function submit(func){
  document.forms['hiddenform'].elements['func'].value = func;
  document.forms['hiddenform'].submit();
}
</script>



