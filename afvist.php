<!DOCTYPE HTML>
<html>
<head>
<meta name="robots" content="noindex">
<title>Registration denied!</title>
<style>
html {
	height:100%;
}

*{
	color: #FFEDBA;
	border-color: #FFEDBA;
	border-color: #FFEDBA;
}

body{
	margin: 0px;
	background-color: #460918;
	background-image: url(background.jpg); 
	background-repeat: no-repeat;
	background-size:100% 100%;
	inner-height:100%;
}


div{
	position: static;
	border:2px solid #f0d988;
	padding:20px; 
	border-radius:10px;
	background-color:rgba(50,10,10,0.5);
	box-shadow: 3px 3px 3px #291b2c;
	float:left;
	margin: 10px;
}

button{
  color:black;
}
</style>
</head>
<body>

<div>
This is a private site. If you don't have a legitimate reason to be here, Please refrain from using it any further.<br><br>
Kode <?php print "<pre>".$_GET['kode']."</pre>"; ?>

</div>
<div>
<a href="./index.php">test retur</a>
</div>
</body>
</html>

