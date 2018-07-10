<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8" />
<title>Postgresql statust</title>
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
<h1>Postgresql statust</h1>
<fieldset style="display:inline"> <legend><b>Postgresql statust</b></legend><pre>
<?php 
  exec('/etc/init.d/postgresql status',$reply);
  foreach($reply as $line) echo "$line\n"; 
?>
</fieldset>

</body>

