<?php
  $remote = $_SERVER['REMOTE_ADDR'];
  $remote = "83.88.18.31";
  require "$_SERVER[DOCUMENT_ROOT]/net_trace.php";
  $trace=net_trace($remote);


echo "<pre>" . print_r(net_trace($remote),true) . "</pre>"
?>
