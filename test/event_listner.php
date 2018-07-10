<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8" />
<title>Event listener</title>
<link rel="shortcut icon" href="/favicon.ico" >
<link rel="icon" href="/favicon_big.ico" type="image/ico" >
<link rel="stylesheet" type="text/css" href="/theme/theme.css" />
<script type="text/javascript" src="util.js"></script>
<script type="text/javascript" src="page_services.js"></script>

<style>
pre {
  white-space: pre-wrap;       /* CSS 3 */
  word-wrap: break-word;       /* Internet Explorer 5.5+ */
}

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

td:nth-child(2n) {background: #720; }

</style>
</head>
<body>


<fieldset style="display:inline"> <legend><b>Trace</b></legend>
  <table id="output" style="border:0; margin:0; padding:0"><tr></tr></table>
</fieldset>

<script type="text/javascript">
function showMessage(event ){
  var table = document.getElementById("output");

  table.insertRow(0).insertCell(0);
  table.rows[0].insertCell(1);


  table.rows[0].cells[0].innerHTML = table.rows.length-1;  
  table.rows[0].cells[1].innerHTML = 
    '<pre>'+JSON.stringify(event,null, 2).replace(/\\\"/g,"\"")+"</pre>";

  table.rows[0].cells[1].scrollTop = table.rows[0].cells[1].scrollHeight;
}


ps.on('open',function(){showMessage('[on-line]');});
ps.on('close',function(){showMessage('[off-line]');});
ps.on('error',function(data){showMessage('[Websocket error:]',data)});
ps.on('message',function(data){showMessage(data)});

ps.serviceRequest({"subscribe":"*"});


</script>
</body>
</html>
