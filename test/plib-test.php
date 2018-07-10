<!DOCTYPE HTML>
<html>
<head>
<title>Test page</title>

<style>
body {
width:100%;
padding:0;
margin:0;
font:16px sans-serif;
color:#ccd;
background-color:#303030;
}
h1 {
font-size:40px;
font-weight: normal;
margin:20px 0;
color:#fff;
}
#container {
width:800px;
margin:0 auto;
}
#demoCanvas {
background-color: #ddd;
margin-bottom:12px;
}
.options {
font-size:12px;
padding:9px 5px 9px 5px;
background-color:#555;
color:#fff;
width:790px;
margin:0;
}
.options>input[type=text] {
width:30px;
margin-left:5px;
text-align:center;
border:1px solid #fff;
}
button {
border:0;
background: rgb(187, 200, 248);
color:#000;
padding:5px 9px 4px 9px;
float:right;
margin:0 4px 0 0;
}
.options>label {
margin-left:12px;
}
a {
color:#fff;
text-decoration: none;
}
a:hover {
color:#ffa;
text-decoration: underline;
}
.footerl {
float:left;
}
.footerr {
float:right;
}
</style>


<link rel="stylesheet" type="text/css" href="/theme/theme.css" />
</head>
<body>
<?php
  //define('_I_DEBUG',true);
  //define('_DEBUG',true);

  define("_ERROR_HANDLER_OFF",true);
  define("_DEV_DEBUG",true);
  define("_ADB_DEBUG",true);
  require_once "present.php";


  //Execute command
  $trust=100;
 // $responce=cmd("/rainwater_system/well/temperature get",false);
//  echo "<pre>";
//  print_r($responce);

    $val=8;
?>
<table>
<tr><th width=200px">Value:</th>
<td width="400px" >
<input id="val" type ="range" min ="-55" max="55" value ="0" step="0.5" oninput="update()"/>
</td> 
<td><output id="val-out" for="val">0</output></td>
</tr>

<tr><th>high:</th>
<td>
<input id="h" type ="range" min ="-49" max="50" value ="50" oninput="update()"/>
</td> 
<td><output id="h-out" for="sh">0</output></td>
</tr>

<tr><th>low:</th>
<td>
<input id="l" type ="range" min ="-50" max="49" value ="-50" oninput="update()"/>
</td> 
<td><output id="l-out" for="sh">0</output></td>
</tr>

<tr><th>Scale high:</th>
<td>
<input id="sh" type ="range" min ="0" max="100" value ="0" oninput="update()"/>
</td> 
<td><output id="sh-out" for="sh">0</output></td>
</tr>


<tr><th>Scale low:</th>
<td>
<input id="sl" type ="range" min ="0" max="100" value ="100" oninput="update()"/>
</td> 
<td><output id="sl-out" for="sl">0</output></td>
</tr>

<tr>
<th>
<input id="show_value" type ="checkbox" value ="on" onclick="update()"/>
</th> 
<th>Show value:</th>
<td></td>
</tr>

<tr>
<td>
<input id="show_prefix" type ="checkbox" value ="on" onclick="update()"/>
</td> 
<th>Show prefix:</th>
<td></td>
</tr>

<tr>
<td>
<input id="array" type ="checkbox" value ="on" onclick="update()"/>
</td> 
<th>Use value array (Graph):</th>
<td></td>
</tr>

<tr><th>Color scale:</th>
<td>
<select id="color" onchange="update()">
<option>temp</option>
<option>tempindoor</option>
<option>tempwater</option>
<option>tempboiler</option>
<option>pressure</option>
<option>process</option>
<option>water</option>
<option>light</option>
<option>brightness</option>
<option>bgr</option>
<option>byr</option>
<option>gyr</option>
<option>rgr</option>
<option></option>
</select>
</td> 
<td></td>
</tr>

<tr><th>Data type:</th>
<td>
<select id="type" onchange="update()"/>
<option>number</option>
<option>words</option>
<option>bytes</option>
<option>bits</option>
<option>temperature</option>
<option>pressure</option>
<option>waterlevel</option>
<option>volt</option>
<option>ampare</option>
<option>speed</option>
<option>windspeed</option>
<option>power</option>
</select>
</td> 
<td></td>
</tr>

<tr><th>Data type:</th>
<td>
<select id="output" onchange="update()"/>
<option>text</option>
<option>verbal</option>
<option>test</option>
<option>bar</option>
<option>gauge</option>
<option>graph</option>
</select>
</td> 
<td></td>
</tr>
</table>
<div>
<output id="opt" for="sh">No options set</output>
</div>

<canvas class="tile" id="t1"  width="800" height="400" ></canvas>
<canvas class="tile" id="t3"  width="100" height="200" ></canvas>



<script type="text/JavaScript" src="/plib.js"></script>
<script type="text/JavaScript" charset="utf-8">
var value_array={};
value_array.y=[-2,2,4,6,8,10,9,8,7,7,8,9,11,14,17,19,23,23,21,20,19,18,17,16,19,23];
value_array.x=[-2,2,4,6,8,10,13,15,16,17,18,19,21,24,27,29,33,34,35,37,39,42,45,46,49,53];

function update(){
  var val=document.getElementById('val').value;
  var h=document.getElementById('h').value;
  var l=document.getElementById('l').value;
  var sh=document.getElementById('sh').value;
  var sl=document.getElementById('sl').value;
  var type=document.getElementById('type').value;
  var output=document.getElementById('output').value;
  var color=document.getElementById('color').value;

  document.getElementById('val-out').innerHTML=val;
  document.getElementById('val-out').max=h+1 + "";
  document.getElementById('val-out').min=l-1 + "";
  document.getElementById('h-out').innerHTML=h;
  document.getElementById('l-out').innerHTML=l;
  document.getElementById('sh-out').innerHTML=sh;
  document.getElementById('sl-out').innerHTML=sl;

  var opt="bgcolor=#FFF";
  opt+=" high="+h+" low="+l+" colorhigh="+sh+" colorlow="+sl+" prefix=\u00B0C";
  if(document.getElementById('show_prefix').checked) opt += " show_prefix";
  if(document.getElementById('show_value').checked) opt += " show_value";
  opt+=" color="+color+" ";

  if(document.getElementById('array').checked)
    val=value_array;

  present("t3",val,type,"test " + opt);
  opt+=" "+output+" bgcolor=water bgcolorlow=10";
  present("t1",val,type,opt);

  document.getElementById('opt').innerHTML=opt;

}
update();
</script>
</body>
</html>

