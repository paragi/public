<!DOCTYPE HTML>
<html>
<head> 
<meta charset="utf-8" />
<body>
<pre>
<?php
/*
wget 10.0.0.13/guest/connect.php --no-cache --header="caller_ID: 1234567" -S -O -

HTTP_CALLER_ID
*/

do{

  if(empty($_SERVER['HTTP_CALLER_ID'])) break;
  print("\ncrc: " . dechex(crc32($_SERVER['HTTP_CALLER_ID'])));

  $record = time() . ",{$_SERVER['HTTP_CALLER_ID']},{$_SERVER['REMORE_ADDR']}" . PHP_EOL;


  print_r($_SERVER);
  $count = file_put_contents('register.csv',$record,FILE_APPEND);

  echo "ok". PHP_EOL;
  
}while(false);
echo "failed". PHP_EOL;

?>
</pre>
</body>
</html>
