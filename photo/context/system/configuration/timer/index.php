<?php
/*============================================================================*\
  Timer
  
  Display page 
\*============================================================================*/
$title="Timers";
$sensitivity=array("get"=>20,"set"=>40);
if ($_SERVER['SESSION']['trust'] < $sensitivity['get']) return;

/*============================================================================*\
  List of timers
\*============================================================================*/
?>
<style>
[contenteditable=true]:empty:before {
  content: attr(placeholder);
  color: #775; 
}
table tr {cursor: pointer;}
</style>
This is a very simple substitute for a proper page to access timers.<br>
Note that time is in UTC
<div class="flex_break"></div>
<div class="container">

<b>Timers:</b>
<button type="button" onclick="getInput();">&nbsp;+&nbsp;</button>
<hr class="flex_break">
<table id="timer_list"></table>
</div>

<script>
ps.services("event", "subscribe","timer.list");

ps.on("timer.list",function(response){
  var table = document.getElementById("timer_list");
  if(response.error){
    table.innerHTML = "<tr><td>" + response.error + "</td></tr>";
    return; 
  }

  table.innerHTML = '<tr></tr>';
  for(row in response.result){
    if(!response.result[row]) continue;
    // Add rows to table of timers
    table.insertRow(0).insertCell(0);
    table.rows[0].insertCell(1);
    table.rows[0].insertCell(2);
    table.rows[0].cells[0].innerHTML = response.result[row].command;
    table.rows[0].cells[1].innerHTML = response.result[row].timexp;  
    if(!response.result[row].active) table.rows[0].style.opacity = '0.4';
    // Adjust scroll
    table.rows[0].cells[1].scrollTop = table.rows[0].cells[1].scrollHeight;
    // Clickable row to edit
    table.rows[0].onclick = (function (timexp,command){
      return function (){getInput(timexp,command);}
    })(response.result[row].timexp,response.result[row].command);
  }  
});

ps.services('timer','list');
</script>

<?php
/*============================================================================*\
  Edit/create/delete a timer 
\*============================================================================*/
?>
<div id="timer-input-container" class="container" style="
  position: fixed;
  left: 50%;
  top: 40%;
  transform: translate(-50%,-50%);
  white-space: nowrap;
  width: 95%;
  z-index:5;
  display: none;
">
<b>Create timer:</b>
<div class="flex-break"><br></div> 

Time expression: 
<div contenteditable='true' id="timexp" placeholder="y m d h m s ms"></div>
Command: 
<div contenteditable id="command"></div>
<button style="float: right;" onclick="save();">Save</button>
<button style="float: right;" id="delete" onclick="save('delete');">Delete</button>
<button onclick="document.getElementById('timer-input-container').style.display='none';" style="float: right;">Cancel</button>
</div>

<script>
function getInput(timexp,command){
  original = {timexp: timexp, command: command};
  
  document.getElementById('timer-input-container').style.display='block';
  var element = document.getElementById("timexp")
  element.ignoreEvents = false;
  element.focus();
  element.innerHTML = timexp || '';
  document.getElementById("command").innerHTML = command || '';

  if(timexp || command){
    document.getElementById("delete").disabled = false;
  }else
    document.getElementById("delete").disabled = true;
}

function save(func){
  var newData = {
     timexp: document.getElementById("timexp").innerText
    ,command: document.getElementById("command").innerText
  };
  
  // Delete
  if(  original.timexp
    && original.command
    && original.timexp.length 
    && original.command.length
    && (  func == 'delete'
       || original.timexp != newData.timexp 
       || original.command != newData.command
       )
    ){
      
    ps.services({
       service: "timer"
      ,func: "remove"
      ,data: original
    }, function(){
      if(func == 'delete')
        ps.services("timer","list");
    });
  }
  
  // add
  if(newData.timexp.length 
    && newData.command.length
    && (original.timexp != newData.timexp 
      || original.command != newData.command
      || func != 'delete')){
      
    ps.services({
       service: "timer"
      ,func: "add"
      ,data: newData
    },function(response){
      if(response.error.length)
        alert(response.error);
  // Find lige på noget bedre end alert!?!      
      ps.services("timer","list");
        
    });
  }
  document.getElementById('timer-input-container').style.display='none';
}

</script>




