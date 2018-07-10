<?php
/*----------------------------------------------------------------------------*\
  Kostal Solar electric - PIKO 5.5 - photovoltaic power plant
 
  
 
  Note: The Kostal server uses &nbsp; without the ending  ;
  Fix reading problem on/off. text missing.
  Screen live update  
  Store data in the background and reuse on multible reqs
\*----------------------------------------------------------------------------*/

$title="Solar power plant";

$reload_time=10;    //Seconds
$user="pvserver";   // Login Credentials for PIKO
$word="netsitron";
$local_ip="10.0.0.12";


// Interactions to monitor
//$interaction[$context."temp"]=json_decode(file_get_contents(dirname(__FILE__).'/temp.ia-dat'), true); 
//$interaction[$context."output"]=json_decode(file_get_contents(dirname(__FILE__).'/output.ia-dat'), true); 




/*============================================================================*\
  Display page
\*============================================================================*/
?>
<script type="text/JavaScript" src="/plib.js"></script>
<?php
/*
  foreach($interaction as $ia_name=>$ia){
    // show interactions
    echo "<div class='tile' style='text-align:center;width:120px;'>$ia[name]<br>\n";
    echo "<canvas id='$ia_name' width='100' height='200'></canvas>\n";
    echo "</div>\n";

    // add to watch list
    $watch_list[$ia_name]=$ia_name;

  }

  echo "<script type=text/Javascript>\n";
  foreach($interaction as $ia_name=>$ia){
    // Initialize display
    echo "present('$ia_name','','$ia[present] prefix=\u00B0C show_value');\n";

    // Add callback funtion for value updates
    echo "ps.on('$ia_name',function(res){present(res.event,res.state);});\n";
  }
  echo "</script>\n";
*/





/*----------------------------------------------------------------------------*\
  HTML page grapper

  Open a page pointed to by the URL, grab all text that appers between tags and 
  return an array of texts.

  Example:

    $text=grab_html_page("http://example.com")

  or with user authorization information:
  
    $text=grab_html_page("http://$user:$word@example.com/list.")

  Return an array of texts or false

\*----------------------------------------------------------------------------*/

function grab_html_page($url){ 
  // Grab the next pease of text from a HTML document.
  // Optionally specify a string to search for and return text after that point.
  function grab($page,$find){
    static $s=0,$e=0;

    $text="";
   // printf("s:%s e:%s find( %s ) ",$s,$e,$find);

    // Find search string
    if($find){
      $s=stripos($page,$find,$e);
      if(!$s) return "";
    }

    do{
      // Find end of tag
      $s=strpos($page,">",$s);
      if(!$s) return "";
      $s++;

      // Find start of next tag
      $e=strpos($page,"<",$s);
      if(!$e) return "";

      if($e>$s){ 
        $text=htmlspecialchars_decode(substr($page,$s,$e-$s));
        $text=trim(str_replace('&nbsp',"",$text));
      }
      // printf("s:%s e:%s = '%s'<br> \n ",$s,$e,$text);
    }while(strlen($text)<1);

    //printf("s:%s e:%s = '%s'<br> \n ",$s,$e,$text);
    return $text;
  }

  // Get page from inverter
  $page=@file_get_contents($url);
  if(!$page) return 0;

  // Grab tile
  $text=grab($page,"<title");
  $page_data[]=$text;

  // Grab all text in body
  $text=grab($page,"<body");
  $page_data[]=$text;
  while (strlen($text)>0){
    $text=grab($page,"");
    $page_data[]=$text;
  }

  // REturn array of texts
  return $page_data;
}


/*----------------------------------------------------------------------------*\
  Kostal PIKO read current state

  Note: 
  - The Kostal server uses &nbsp; without the ending  ";"
  - Use login credentials in the URL


\*----------------------------------------------------------------------------*/

function PIKO_read($user, $word, $ip){

  $text=grab_html_page("http://$user:$word@$ip/index.fhtml"); 
  // Off-line
  if(!$text){
    $data['state']="off-line";
    return $data;
  }

  // Check array validity
  if(!$text[0] == "PV Webserver" & !$text[60] == "RS485 communication"){
    $data['error']="Inverter read error";
    return $data;
  }

  // Extract relevant values and order data into associative array
  if($text[15]=="off")
    $data['production']="off";
  else
    $data['production']="on";
    
  $data['output W']=floatval($text[6]);
  $data['days output W']=floatval($text[12]);
  $data['grid L1 V']=floatval($text[24]);
  $data['grid L1 W']=floatval($text[30]);
  $data['grid L2 V']=floatval($text[38]);
  $data['grid L2 W']=floatval($text[44]);
  $data['grid L3 V']=floatval($text[52]);
  $data['grid L3 W']=floatval($text[58]);
  $data['string 1 V']=floatval($text[21]);
  $data['string 1 A']=floatval($text[27]);
  $data['string 2 V']=floatval($text[35]);
  $data['string 2 A']=floatval($text[41]);
  $data['string 3 V']=floatval($text[49]);
  $data['string 3 A']=floatval($text[55]);
  $data['time']=time(true);

  return $data;
}


echo "<div class='container'>";


$data=PIKO_read($user,$word,$local_ip);
  //  print_r($text);

  // Get historical data file
  // Resolution is 15 minutes. Consider rewrite to save some trafic
  //$file=@file_get_contents("http://$user:$word@$local_ip/LogDaten.dat");
  if(!$file){
    //echo "Unable to read historical data";
  }

  // Display inverter state
  if($data['state']=="off-line"){
    echo "<image src='/theme/led:red.png' width='50px' height='50px'>";
  }else{
    echo "<image src='/theme/led:green.png' width='50px' height='50px'>";
  }
  echo "</div>";

  //print_r($data);

  // Display production state
  echo '<div id="solar power plant" class="content" onclick="" style="width:auto; height:auto;">';


//-----------------------
  echo '<div id="solar power plant" class="tile" onclick="" >';
  echo "<table><tr>";
  echo '<td><img src="/theme/solar-power-plant.png"  height="100px" width="108px alt="Solar Power Plant:"></td>';
  echo '<td><img src="/theme/chart1.png" height="100px" width="108px" alt="Days production"></td>';

  echo "</tr></table>";

  echo "<table>";
  echo "<tr><td>Output</td><td>".$data['output W']."</td><td>";
  echo ($data['Production']=="Off"?"":"W")."</td></tr>";
  echo "<tr><td>Days production</td><td>".$data['days output W']."</td><td>KWh</td></tr>";
 
  echo "<tr><td><br>Production strings:</td></tr>";
  echo "<tr><td>String 1</td><td>".$data['string 1 V']."/".$data['string 1 A']."</td><td>V/A</td></tr>";
  echo "<tr><td>String 2</td><td>".$data['string 2 V']."/".$data['string 2 A']."</td><td>V/A</td></tr>";
  echo "<tr><td>String 3</td><td>".$data['string 3 V']."/".$data['string 3 A']."</td><td>V/A</td></tr>";

  echo "<tr><td><br>Power Output to Grid</td></tr>";
  echo "<tr><td>L1</td><td>".$data['grid L1 V']."/".$data['grid L1 W']."</td><td>V/W</td></tr>";
  echo "<tr><td>L2</td><td>".$data['grid L2 V']."/".$data['grid L2 W']."</td><td>V/W</td></tr>";
  echo "<tr><td>L3</td><td>".$data['grid L3 V']."/".$data['grid L3 W']."</td><td>V/W</td></tr>";
  echo "</table>";
  echo "</div>";



?>

<script type="text/javascript">

//setTimeout(document.forms["master_form"].submit(),10000);

</script>

