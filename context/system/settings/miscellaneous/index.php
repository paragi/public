<?php
$title="Miscellaneous";
$sensitivity=array("get"=>60,"set"=>80);
if ($TRUST < $sensitivity['get']) return;

/*============================================================================*\
  List
\*============================================================================*/
$rsdb = new \Paragi\RocketStore([
    "data_storage_area" => DIR_BASE . "var" . DIRECTORY_SEPARATOR . "rsdb"
  , "data_format" => RS_FORMAT_JSON
]);

$db_reply = $rsdb->get("setting","miscellaneous");

echo "<div class=\"container\"><table>";
if($db_reply['count'] > 0)
  foreach($db_reply['result']['miscellaneous'] as $key => $setting){
    echo "<tr><td>{$setting['text']}</td>";
    echo "<td>{$setting['value']}</td></tr>\n";
  }
echo "</table></div>";
?>
