<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8" />
<title>Test scripts:</title>
<link rel="shortcut icon" href="/favicon.ico" >
<link rel="icon" href="/favicon_big.ico" type="image/ico" >
<link rel="stylesheet" type="text/css" href="/theme/theme.css" />

<style>
table{
	border:1px solid #f0d988;
	padding:3px;
	margin:6px;
}
td:hover{
	background-color:rgba(240,217,136,0.2);
	cursor:pointer;
}
*{font-size: 16pt;}
</style>
</head>

<body>
<h1>Test scripts</h1>
<table>
<?php
  foreach(array_merge(glob(__DIR__."/*.php"),glob(__DIR__."/*.html")) as $path)
    if(!strrpos($path,"entry.php"))
      echo "<tr><td onclick=\"window.location.href='"
       .substr(str_replace(__DIR__, '', $path),1)."'\">"
       .substr($path,strrpos($path,"/")+1)."</td></tr>";
?>
</table>
</body>
