<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8" />
<title>Test scripts:</title>
<link rel="shortcut icon" href="/favicon.ico" >
<link rel="icon" href="/favicon_big.ico" type="image/ico" >
<link rel="stylesheet" type="text/css" href="/theme/theme.css" />
<script type="text/javascript" src="util.js"></script>
<script type="text/javascript" src="/page_services.js" /></script>
<style>
td:hover{
	background-color:rgba(240,217,136,0.2);
	cursor:pointer;
}
*{font-size: 16pt;}
</style>
</head>

<body>
<h1>Test af command.php</h1>
<div class="container" style=" width: 95%">

<!------------------------------------------------------------------------------
  Command submit form  
------------------------------------------------------------------------------->

<form method="post">
<label>Command: </label>
<?php
printf('<input name="cmd" size="50" type="text" autofocus value="%s"><br>'
  ,@$_REQUEST['cmd']);
?>
<input type="submit" style="visibility: hidden;" />
</form>
<!------------------------------------------------------------------------------
  Submit methods  
------------------------------------------------------------------------------->
<br>
<div class="tile" width="200" onclick="
document.forms[0].method='post';
document.forms[0].target='';
document.forms[0].action='';
document.forms[0].submit();
">Include</div>
<br>
<div class="tile" onclick="
document.forms[0].method='post';
document.forms[0].action='/command.php';
document.forms[0].target='post window';
document.forms[0].submit();
">POST</div>

<div class="tile" onclick="
document.forms[0].method='get';
document.forms[0].action='/command.php';
document.forms[0].target='get window';
document.forms[0].submit();
">GET</div>

<div class="tile" onclick="
ps.cmd(document.forms[0].cmd.value,function(response){
  document.getElementById('output').innerHTML=(typeof response);
  HTMLDump('output',response);
});
">Websocket</div>
<!------------------------------------------------------------------------------
  Command selector
------------------------------------------------------------------------------->
<div class="container">
Select command:
<table>
<tr><td onclick="document.forms[0].cmd.value=this.innerHTML">/utility/system/template/bedroom/ceiling/light on</td></tr>
<tr><td onclick="document.forms[0].cmd.value=this.innerHTML">utility system template bedroom ceiling light on</td></tr>
<tr><td onclick="document.forms[0].cmd.value=this.innerHTML">why</td></tr>
<tr><td onclick="document.forms[0].cmd.value=this.innerHTML">test</td></tr>
<tr><td onclick="document.forms[0].cmd.value=this.innerHTML">say hello</td></tr>
<tr><td onclick="document.forms[0].cmd.value=this.innerHTML">access</td></tr>
<tr><td onclick="document.forms[0].cmd.value=this.innerHTML">wait</td></tr>
<tr><td onclick="document.forms[0].cmd.value=this.innerHTML">computer</td></tr>
<tr><td onclick="document.forms[0].cmd.value=this.innerHTML">/test/ty-switch on</td></tr>
</table>
</div>
<!------------------------------------------------------------------------------
  Server requests
------------------------------------------------------------------------------->
<div class="container">
Make a service request:<br>
<table id="service_request">
</table>
</div>
<!------------------------------------------------------------------------------
  Output area
------------------------------------------------------------------------------->
<br/>
<div class="container" id="output" style="float:left">
<script>
var items = [
   {service:'datastore', func:'commandHint', data:{origin: '10.0.0.100', user_id:'Simons Rigét'}}
  ,{service:'datastore', func:'event.get', data:{age: '15 minutes',event: '/demo/'}}
  ,{error:'Error test'}
  ,{timexp:'* * * * * /5',command:'say hello'}
  ,{removeTimexp:'* * * * * /5',command:'say hello'}
  ,{service: 'event', func: 'subscribe:', data: 'test event'}
  ,{service: 'event', func: 'unsubscribe:', data:'test event'}
  ,{service: 'event', func: 'announce', data:'test event'}
  ,{service: 'serverinfo', func: 'all'}
  ,{service: 'serverinfo', func: 'configuration'}
  ,{service: 'serverinfo', func: 'version'}
  ,{service: 'serverinfo', func: 'memory'}
  ,{service: 'serverinfo', func: 'privileges'}
  ,{service: 'serverinfo', func: 'modules'}
  ,{service: 'serverinfo', func: 'platform'}
  ,{service: 'serverinfo', func: 'websockets'}
  ,{service: 'serverinfo', func: 'watch_list'}
  ,{service: 'serverinfo', func: 'timers'}
  ,{service: 'serverinfo', func: 'reactions'} 
];

var table = document.getElementById('service_request');
for(var i in items){
  table.insertRow(-1);
  table.rows[table.rows.length-1].insertCell(0).innerHTML = JSON.stringify(items[i].service);  
  table.rows[table.rows.length-1].insertCell(1).innerHTML = JSON.stringify(items[i].func);  
  table.rows[table.rows.length-1].insertCell(2).innerHTML = JSON.stringify(items[i].data);  
  table.rows[table.rows.length-1].onclick = (function(request){
    return function(){ 
      ps.services(request,function(response){
        HTMLDumpCls('output',(response.html?response.html:response))    
      });
    }  
  })(items[i]); 
}

</script>

<?php
  // Include and execute
  if(isset($_POST['cmd'])){
    require $_SERVER['DOCUMENT_ROOT']."/command.php";
    $response=command($_POST['cmd'],false);
    echo "<pre>". print_r($response,true) . "</pre>";
  }
?>
</div>
</div>
</body>
