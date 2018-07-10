<!DOCTYPE HTML>
<html>
<head> 
<meta charset="utf-8" />
<title>Test page</title>
<link rel="shortcut icon" href="/favicon.ico" >
<link rel="icon" href="/favicon_big.ico" type="image/ico" >
<link rel="stylesheet" type="text/css" href="/theme/theme.css" />
<style>
td:hover{
	background-color:rgba(240,217,136,0.2);
	cursor:pointer;
}
*{font-size: 16pt;}
</style>


</head>
<body>
<h1>PHP Globals</h1>
<div class="container" style=" width: 95%">
<pre>
<?php
print_r($GLOBALS);
?>

</pre>
</div>
</body>
</html>
