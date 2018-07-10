<!DOCTYPE HTML>
<html>
<head> 
<meta charset="utf-8" />
<title>Test page</title>
<link rel="shortcut icon" href="/favicon.ico" >
<link rel="icon" href="/favicon_big.ico" type="image/ico" >
<link rel="stylesheet" type="text/css" href="/theme/theme.css" />

<style>
</style>

</head>
<body>
<h1>Test page</h1>
<pre>
<?php
define('_DEV_DEBUG',true);
echo json_encode(["event"=>"red_alert"] ,JSON_UNESCAPED_UNICODE
      |JSON_UNESCAPED_SLASHES
      |JSON_BIGINT_AS_STRING
    ); 
?>
</pre>
</body>
</html>
