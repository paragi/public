<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8" />
<title>House Computer</title>
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
	table-layout:fixed; 
	width:90%;
}
td{ word-wrap: break-word; }
td:hover{
	background-color:rgba(240,217,136,0.2);
	cursor:pointer;
}
*{font-size: 16pt;}

td:nth-child(2n) {background: #720; }

</style>
</head>
<body>
<b>Commands:<b>
<table>
<?php
  foreach([
   '{"command":"say hallo world"}' 
  ,'{"command":"/utility/system/template/bedroom/ceiling/light on"}'
  ,'{"serverinfo":"memory"}'
  ,'{"service":"timer","func":"add","data":{
      "timexp":"* * * * * /2","command":"/demo/light4 toggle"
    }}'
  ,'{"service":"serverinfo","func":"all"}'
  ,'{"service":"datastore","func":"getTerminal","data":"'.$_COOKIE['TID'].'"}'
  ,'{"service":"serverinfo","func":"memory"}'
  ,'{"service":"datastore","func":"dbUsers"}'
  ,'{"service":"datastore","func":"tables"}'
  ,'{"service":"datastore","func":"hello"}'
  ] as $req){
  
    echo "<tr><td onClick='send($req);'>
    $req</td></tr>\n";
  }
?>
</table>

<form method="post" name="form1">

<fieldset style="display:inline">
  <legend>Type request</legend>
  
<label for="service">Service:</label>
<input name="service" id="service" value=<?php echo '"'.@$_POST['service'].'"';?>>
<label for="func">Function:</lable>
<input name="func" id="func" value=<?php echo '"'.@$_POST['func'].'"';?>>
<label for="data">Data:</lable>
<input name="data" id="data" value=<?php echo '"'.@$_POST['data'].'"';?>>
<button type="button" onclick="send('typed request');">Send</button>
</fieldset><br>

<fieldset style="display:inline">
  <legend>Transport method</legend>

  <input id="imp1" type="radio" name="implementation" value="browser"
  <?php if(@$_POST["implementation"] != "serverside") echo "checked=\"checked\""; ?>
  ><label for="imp1">Browser (Javascript)</label></br>

  <input id="imp2" type="radio" name="implementation" value="serverside"
  <?php if(@$_POST["implementation"] == "serverside") echo "checked=\"checked\""; ?>
  ><label for="imp2">Serverside (PHP)</label>

</fieldset>    
<input type="hidden" name="req_json">
</form>

<b>Trace</b>
<table id="output"><tr></tr></table>

<script type="text/javascript">
function send(request,cb){
  if(request == 'typed request'){
    request = {};
    request.service = document.forms.form1.service.value;
    request.func = document.forms.form1.func.value;
    request.data = document.forms.form1.data.value;
  }else{
    document.forms.form1.service.value = request.service;
    document.forms.form1.func.value = request.func;
    document.forms.form1.data.value = request.data || '';
  }

  if(document.forms.form1.implementation.value == "serverside"){
    document.forms.form1.req_json.value = JSON.stringify(request);
    document.forms.form1.submit();
    showMessage('out',"Please wait");
  }else{
    showMessage('out',request);
    ps.serviceRequest(request,cb);
  }
  return false;
}

// show message (direction [in/out],msg[[,...]])
// Using table with ID=output
function showMessage(){
  var args = Array.prototype.slice.call(arguments);

  var table = document.getElementById("output");

  table.insertRow(0).insertCell(0);
  table.rows[0].insertCell(1);
  table.rows[0].insertCell(2);

  table.rows[0].cells[0].innerHTML = table.rows.length;  
  table.rows[0].cells[0].style="width:30px";
  var containingElement = table.rows[0].cells[ (args[0]=='in'?2:1) ];

  for(var i in args){
    if(i<1) continue; 
    if(typeof args[i] === 'object')
       containingElement.innerHTML = 
        '<pre>'+JSON.stringify(args[i],null, 2).replace(/\\\"/g,"\"")+"</pre>";
      else
        containingElement.innerHTML = args[i]+"<br>"; 
  }
  containingElement.scrollTop = containingElement.scrollHeight;
}

ps.on('open',function(){showMessage('out','[on-line]');});
ps.on('close',function(){showMessage('out','[off-line]');});
ps.on('error',function(data){showMessage('out','[Websocket error:]',data)});
ps.on('message',function(data){showMessage('in',data)});
</script>

<?php 
/*============================================================================*\
  Execute server side request
\*============================================================================*/
if(@$_POST["implementation"] == "serverside"){
  require "$_SERVER[DOCUMENT_ROOT]/services.php";

  $request = json_decode($_POST['req_json'],true);
  $response = services(@$request['service'],@$request['func'],@$request['data']);
  if(!is_array($response)) $response='"No response"';

  echo "<script>\n";
  echo "showMessage('out',$_POST[req_json]);\n";
  echo "showMessage('in',".json_encode($response).");\n";
  echo "</script>\n";
}
?>
</body>
</html>
