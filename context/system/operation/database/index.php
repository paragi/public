<?php
$title="Database";
$sensitivity=array("get"=>60,"set"=>80);
if ($TRUST < $sensitivity['get']) return;
/*============================================================================*\
  Get DB status
\*============================================================================*/
require $_SERVER['DOCUMENT_ROOT']."/services.php";

// Test database connection
$response = services("datastore","db.state");
if($response['error']){
  $icon="database[red].png";
  $datastoreDBState = "Off-line";
}else{ 
  // Get disk free space
  exec("df /var/lib/postgresql/ 2>&1",$output,$rc);
  foreach($output as $line){
    //$line = strtolower(preg_replace('/[^A-Z a-z0-9\-]/', '', $line));
    $response['result'][]  = explode(",",preg_replace('/\s+/', ',', $line));
  }
  
  if($response['result'][2][4] > 90){
    $icon="database[yellow].png";
    $datastoreDBState = "Require attention";
  
  }else if(empty($response['state']) || $response['state'] != 'on-line'){
    $icon="database[yellow].png";
    $datastoreDBState = "Require attention";
  }else{
    $icon="database[green].png";
    $datastoreDBState = "Ok";
  }  
}

/*============================================================================*\
  Function box
\*============================================================================*/
?>
<script>
var description={};

function send(func){
  document.hidden_form.func.value=func;
  document.hidden_form.submit();
}
function submitSQL(){
  document.hidden_form.sql.value = document.getElementById('sqlEditorIn').innerText;
  document.hidden_form.submit();
}
function focusSQLEditor(){
  document.getElementById('sqlEditorIn').focus();
}

function getDescription(table){
  var tableName = table.split(".")[2];
  if(!tableName) return;

  var container = document.getElementById('description-container');
  var element = document.getElementById('description');

  if(!description[table]){
    element.innerHTML = 'Working';
    description[table] = '<table><tr><th colspan = 2>' + tableName + ':</th></tr>';
    ps.services("datastore","db.describeTable",tableName, function(response){
      if(response.error || response.reply == 'working') return;
      
      for(column in response.result){
        description[table] += '<tr><td>' + response.result[column]['name'] + '</td><td>';
        for(attribute in response.result[column])
          if(response.result[column][attribute] && attribute != 'name'){
            description[table] += response.result[column][attribute] + ' ';
          }
        description[table] += "</td></tr>\n";  
      }
      description[table] += "</table>\n";  
      element.innerHTML = description[table];
    });
  }else
    element.innerHTML  = description[table];  
  container.style.display='block';
} 

document.onkeydown = function(evt) {
  if (evt.keyCode == 27)
    document.getElementById('description-container').style.display = 'none';
};

ps.services("event","subscribe","datastore.db.state");
</script>

<style> td { cursor: pointer; }</style>

<!-- Description box for table view -->
<div id="description-container" class="container" style="
  position: fixed;
  left: 50%;
  top: 50%;
  transform: translate(-50%,-50%);
  white-space: nowrap;
  width: 80%;
  height: 80%;
  overflow:scroll;
  z-index:5;
  display: none;
">

<button onclick="document.getElementById('description-container').style.display='none';">Hide</button>
<hr>
<div id="description" style="
  unicode-bidi: embed;
  font-family: monospace;
  white-space: pre;
"></div>
</div>

<?php
echo '<form name="hidden_form" method="post">';
echo '<input type="hidden" name="func" value="'.@$_POST['func'].'">';
echo '<textarea name="sql" style="display:none"></textarea>';
echo "</form>\n";

 // echo "<pre>".print_r($_POST,true)."</pre>";

/*============================================================================*\
  Make function buttons, as tiles
\*============================================================================*/
$function = [
  "DBState"=>["hint"=>"State: ". $datastoreDBState
    ,"icon"=>$icon
    ,"text"=>""
    ,"sensitivity"=>$sensitivity['get']
    ,"action"=>"send('state')"]

  ,"statusReport"=>["hint"=>"Status report"
    ,"icon"=>"db-statusreport.png"
    ,"text"=>""
    ,"sensitivity"=>$sensitivity['get']
    ,"action"=>"send('statusReport')"]

  ,"tables"=>["hint"=>"List of tables"
    ,"icon"=>"db-tables.png"
    ,"text"=>""
    ,"sensitivity"=>$sensitivity['get']
    ,"action"=>"send('tables')"]

/*  ,"purgeDB"=>["hint"=>"Purge Database"
    ,"icon"=>"db-purge.png"
    ,"text"=>""
    ,"sensitivity"=>$sensitivity['set']
    ,"action"=>"send('purgeDB')"]
*/
  ,"backupDB"=>["hint"=>"Backup database"
    ,"icon"=>"db-backup.png"
    ,"text"=>""
    ,"sensitivity"=>$sensitivity['get']
    ,"action"=>"send('backupDB')"]

  ,"restoreDB"=>["hint"=>"Restore database from backup"
    ,"icon"=>"db-restore.png"
    ,"text"=>""
    ,"sensitivity"=>$sensitivity['set']
    ,"action"=>"send('restoreDB')"]  

  ,"regenerateDB"=>["hint"=>"Regenerate DB Structure (nondestructive)" 
    ,"icon"=>"db-regenerate.png" 
    ,"text"=>""
    ,"sensitivity"=>$sensitivity['set']
    ,"action"=>"send('regenerateDB')"]

  ,"sqlEditor"=>["hint"=>"SQL Editor"
    ,"icon"=>"sql-editor.png"
    ,"text"=>""
    ,"sensitivity"=>$sensitivity['set']
    ,"action"=>"send('sqlEditor')"]
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

/*============================================================================*\
  Action
\*============================================================================*/
if(isset($_POST['func'])){
  echo "<div class=\"flex_break\"></div>";
  echo '<div class="container" style="min-width:50%;">';

  // Get result
  if(array_key_exists($_POST['func'],$function)){
    switch($_POST['func']){
      case "state" : 
        break;

      case "statusReport":
        $response = services("datastore","db.statusReport");
        break;
        
      case "tables":
        $response = services("datastore","db.tables");
        break;

      case "sqlEditor" : // For testing only!! To be removed!
        $response = null;
        if(!empty($_POST['sql'])) 
          $response = services("datastore","db.sql",$_POST['sql']);
        echo "<div contenteditable id=\"sqlEditorIn\"";
        echo " style=\"overflow: auto;width:99%\">" . @$_POST['sql'] ."</div>";
        echo "<button style=\"float: right;\" onclick=\"submitSQL()\">";
        echo "Execute</button>"; 
        echo "<script>focusSQLEditor()</script>\n";
        echo "<div class=\"flex_break\"></div>\n"; 
        break;
        
      case "purgeDB" :
        $response = null;
        $response['result'][0]['Purge'] = 
          "This should do some truncation of tables. But it dosen't yet!";
        break;

      case "backupDB" :
        $response = null;
        $url = "/var/DBbackup.db";
        $outfile = $_SERVER['DOCUMENT_ROOT'] . $url;
        //array_map('unlink', glob($_SERVER['DOCUMENT_ROOT'] . "/var/DBbackup-*.sql"));
        exec("pg_dump -Fc smartcore > \"$outfile\"",$output,$rc);
        if($rc == 0) 
          echo "<script> window.location.href='$url';</script>\n"; 
        break;

      case "restoreDB" :
        $response = null;
        $infile = $_SERVER['DOCUMENT_ROOT'] . "/var/DBbackup.db";
        exec("pg_restore -d smartcore 2>&1 <\"$infile\" ",$output,$rc);
        $response['result'][] = ["c1"=>"Restore: " . ($rc ? "failed" : "Done")];
        foreach($output as $line) $response['result'][][0] = $line;
        break;

      case "regenerateDB" :
        $response = null;
        $outfile = __DIR__ . "/DBregenerate.sql";
        exec("psql -f \"$outfile\" postgres 2>&1",$output,$rc);
        $response['result'][] = ["Output:"=>"Regeneration: " . ($rc ? "failed" : "Done")];
        foreach($output as $line) $response['result'][][0] = $line;
        break;
    }
    // List result
    echo "<table><caption style=\"white-space: nowrap;\"><b>";
    echo ( $function[$_POST['func']]['hint'] ? 
      $function[$_POST['func']]['hint'] :$_POST['func']);
    echo "</b><hr></caption>\n";

    if(!empty($response['error'])) 
      echo "<tr><td>$response[error]</td></tr>\n";

    else if(is_array(@$response['result']) && is_array($response['result'][0])){ 
      echo "<tr>";
      foreach($response['result'][0] as $heading=>$data){
        if(is_array($data)) continue;
        echo "<th>$heading</th>";
      }
      echo "</tr>";
      foreach($response['result'] as $row){
        // Make rows clickable for table view. => Get describtion
        if($_POST['func'] == 'tables') 
          echo "<tr onclick=\"getDescription('".@$row['Table name']."');\">";
        else  
          echo "<tr>";
        foreach($row as $column){
          if(is_array($column)) 
            echo "<td><pre>" . print_r($column,true) . "</pre></td>";
          else  
            echo "<td>$column</td>";    
        }
        echo "</tr>\n";
      } 

    }else if(!empty($response['state']))
      echo "<tr><td>".$response['state']."</td></tr>\n";
    echo "</table>";
    echo '</div>';
  }
}
?>
