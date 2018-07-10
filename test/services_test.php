<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8" />
<title>SmartCore Services:</title>
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
<h1>SmartCore Services</h1>
<div class="container" style=" width: 95%">
<table id="service_request">
</table>
</div>
<!------------------------------------------------------------------------------
  Output area
------------------------------------------------------------------------------->
<br/>
<div class="container" id="output" style="float:left">
</div>

<script>
var items = [
   {service:'datastore', func:'event.commandHint', data:{origin: '10.0.0.100', user:'Simon Rigét'}}
  ,{service:'datastore', func:'event.get', data:{age: '15 minutes',event: '/demo/%'}}
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
