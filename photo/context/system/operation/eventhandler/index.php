<?php
/*============================================================================*\
  Event handler  
\*============================================================================*/
$title="Event handler";
$sensitivity=array("get"=>60,"set"=>80);
if ($TRUST < $sensitivity['get']) return;

/*============================================================================*\
  Function box
\*============================================================================*/
?>
<style>
table{ width:100%;}
</style>
<script>
var lastFunc = 'none';

// Change view box
function view(func){
  lastFunc = func;
  ps.services("event","unsubscribe");
  document.getElementById('output-container').style.display='block';

  if(func == 'monitor'){
    //Ask services to send alle events to us
    ps.services("event","subscribe","*");
    document.getElementById('list-container').innerHTML 
      = '<table id="list-table"><tr></tr></table>';
  
  }else if(func == "terminals"){
    ps.services("serverinfo","websockets");
    
  }else
    document.getElementById('output-container').style.display='none';
}

// Register function to receive all messages
ps.on("all",function(event,response){
  if(lastFunc == 'monitor'){
    var table = document.getElementById('list-table');
    table.insertRow(0).insertCell(0);
    table.rows[0].insertCell(1);
    table.rows[0].insertCell(2);
    table.rows[0].insertCell(3);
    table.rows[0].insertCell(4);
    table.rows[0].cells[0].innerHTML = response.event;  
    table.rows[0].cells[1].innerHTML = response.reply;  
    table.rows[0].cells[2].innerHTML = response.origin; 
    for(var name in response){
      if(['event','reply','origin','token','cmdId','time'].indexOf(name) >= 0) continue;
      if(!response[name]) continue;
      table.rows[0].cells[3].innerHTML += name + ': ';
      if(typeof response[name] == 'object')
        table.rows[0].cells[3].innerHTML += JSON.stringify(response[name], null, 2) + '<br>';   
      else  
        table.rows[0].cells[3].innerHTML += response[name] + '<br>';   
    }

    // Adjust scroll
    table.rows[0].cells[1].scrollTop = table.rows[0].cells[1].scrollHeight;  

  }else if(lastFunc == "terminals"){
    // First pass: terminals and table header
    if(typeof response.html !== 'undefined'){
      document.getElementById('list-container').innerHTML = response.html 
        + '<br><table id="list-table"><tr></tr></table>';
      ps.services("event","list");
  
    // second pass: list of subscribtions 
    }else{
      var table = document.getElementById('list-table');
      for(var row in response.result){
        var i = 0;
        table.insertRow(0);
        for(var name in response.result[row]){
          table.rows[0].insertCell(i);
          table.rows[0].cells[i++].innerHTML = response.result[row][name];  
        }
      }
      table.insertRow(0);
      table.rows[0].insertCell(0);
      table.rows[0].cells[0].innerHTML = '<b>Event subscriptions</b>';
      table.rows[0].insertCell(1);
      table.rows[0].cells[1].innerHTML = '<b>fd#</b>';

      // Adjust scroll
      table.rows[0].cells[1].scrollTop = table.rows[0].cells[1].scrollHeight;  
    }
  }    
});

document.onkeyup = function(evt) {
  if (evt.keyCode == 27 && lastFunc != 'none')
    view('none');
};

document.onclick = function(e) {
  if( !e.srcElement.onclick
      && e.target.id != 'output-container'
      && document.getElementById('output-container').style.display == 'block'
    ) 
    view('none');
}

</script>

<div id="output-container" class="container" style="
  position: fixed;
  left: 50%;
  top: 50%;
  transform: translate(-50%,-50%);
  white-space: nowrap;
  width: 95%;
  height: 95%;
  overflow:scroll;
  z-index:5;
  display: none;
">
<button onclick="view('none');">Close</button>
<hr class="flex-break">
<div id="list-container">
</div>
</div>

<?php
/*============================================================================*\
  Make function buttons, as tiles
\*============================================================================*/
$function = [
  "Terminals"=>["hint"=>"List terminals that are active: "
    ,"icon"=>"terminals.png"
    ,"text"=>""
    ,"sensitivity"=>$sensitivity['get']
    ,"action"=>"view('terminals')"]

  ,"Monitor"=>["hint"=>"Monitor events as they occur"
    ,"icon"=>"monitor.png"
    ,"text"=>""
    ,"sensitivity"=>$sensitivity['get']
    ,"action"=>"view('monitor')"]
];

function button($b,$id){
  if ($_SERVER['SESSION']['trust'] < $b['sensitivity']) return;
  $icon_path = image_path($b['icon']);
  echo "<div class=\"tile\" title=\"$b[hint]\" id=\"$id\"";
  echo " style=\"display: table; text-align: center;vertical-align: middle;";
  if($icon_path) 
    echo "background-image: url($icon_path); ";
  else if(empty($b['text']))
    $b['text'] = $b['hint'];
  echo "\" alt=\"$b[hint]\" onclick=\"$b[action]\">";
  if(!empty($b['text']))
    echo "<span class=\"tile_content\">$b[text]</span>";
  echo "</div>\n";
}

foreach($function as $id=>$b)  button($b,$id);
?>
</div>
