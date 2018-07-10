<?php
/*============================================================================*\
  -- This section is all about login security. Be carefull! ---
\*============================================================================*/
$title="Identity";
$sensitivity=array("get"=>20,"set"=>80);
if ($_SERVER['SESSION']['trust'] < $sensitivity['get']) return;

require "$_SERVER[DOCUMENT_ROOT]/services.php";
require "$_SERVER[DOCUMENT_ROOT]/present.php";
require "$_SERVER[DOCUMENT_ROOT]/net_trace.php";
?>
<style>
.place{
  background-size: contain;
  background-repeat: no-repeat;
  background-position: center center;
  padding:10%;
  display:flex;
  align-items: center; 
  justify-content: center;
  flex-flow: row wrap; 
}
</style>
<?php
/*============================================================================*\
  Display page
  
  Whereabouts values:
    home
    work
    traveling
    school
    care
    away
    unknown

activity:
  awake
  a sleep
    
\*============================================================================*/
$place = [
   "home" => []
  ,"work" => []
  ,"traveling" => []
  ,"school" => []
  ,"care" => []
  ,"away" => []
];

// Populate places
foreach([
   1=>"school"
  ,2=>"care"
  ,3=>"work"
  ,4=>"home"
//  ,5=>"traveling"
//  ,6=>"away"
//  ,16=>"awray"
 ] as $user_id => $whereabouts)
 $place[$whereabouts][] = $user_id;
 
foreach($place as $whereabouts => $person){
  if(!count($person)) continue;
  echo "<div class=\"place\" style=\"background-image: url("
    . image_path("whereabouts[$whereabouts].png")
    . ");\">\n"
  ;
  foreach($person as $user_id){  
    $image = "/photo/$user_id.jpg";
    if(!file_exists($_SERVER['DOCUMENT_ROOT'] . $image))
      $image = image_path("identity-no-photo.png");
    echo "<div class=\"tile\" onclick=\"person($user_id);\""
      . " style=\"background-image: url($image);"
      . " background-size: cover; margin: 5%;\"></div>\n"
    ;
  }
  echo "</div>\n";
} 

echo "<div class=\"flex_break\"></div>\n";

// Recognise user
echo "<div class=\"tile\" id=\"\"";
echo " style=\"background-image: url(" . image_path("identify.png") . ");\"";
echo " onclick=\"identifyUser('on')\"></div>";
 
// Terminal
echo "<div class=\"tile\" onclick=\"set_context('terminal');\"";
echo " style=\"background-image: url(" . image_path("terminal.png") . ");\"";
echo " ></div>";

// New person
echo "<div class=\"tile\" onclick=\"person('new');\"";
echo " style=\"background-image: url(" . image_path("identity-new.png") . ");\"";
echo " ></div>\n";

/*============================================================================*\
  Action
\*============================================================================*/
switch(@$_POST['func']){
  case "trace" : 
    break;
  case "show" :
    break;
}
?>

<div id="recognise" class="container" style="
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%,-50%);
  white-space: nowrap;
  background: url(/theme/login_bg.jpg) no-repeat center;
  background-size:cover;
  width: 80%;
  height: 80%;
  z-index:5;
  display: none;
">
<div style="position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%,-50%);"
>
<form name="identify" method="POST">
<table style="margin: auto;border:1px solid">
<caption>Please dentify your self</caption>
<tr><td>Your name:</td>
<td><input type="test" name="name" autofocus value="
<?php echo @$_POST['name'];?>"></td></tr>
<tr><td>Your access key:</td>
<td><input type="password" name="key"></td></tr>
<tr><td colspan=2 style="text-align:right">
<button type="button" onclick="identifyUser('off')">Cancel</button>
<button type="button" onclick="identifyUser('go')">Submit</button></td></tr>
</table>
<input type="hidden" name="id_by">
</form>
</div>
</div>

<form name="personPage" method="POST">
<input type="hidden" name="id">
</form>

<script type="text/javascript">
function pleaseImplementAPropperEncryptionFunction(str,salt=''){
  if(salt.length < 1) salt = "merry had a little doll, made of plastic";
  var lowerCaseString = str.toLowerCase();
  var hash = '';
  for(var i = 0; i < lowerCaseString.length;i++)
    hash += String.fromCharCode(
        (lowerCaseString.charCodeAt(i) - 32) 
      ^ 0x7f 
      ^ (salt.charCodeAt(i%salt.length) - 32)
      + 32);
  return hash;
}

function identifyUser(setTo){
  var elm = document.getElementById('recognise');
  if(setTo == 'on'){
    elm.style.display='block';
    document.forms['identify'].elements['name'].focus()
    scroll(0,0);
  }else if(setTo == 'go'){  
    if(document.forms['identify'].elements['name'].value.length < 2)  
      document.forms['identify'].elements['name'].focus();
    else if(document.forms['identify'].elements['key'].value.length < 2)  
      document.forms['identify'].elements['key'].focus();
    else{
      document.forms['identify'].elements['id_by'].value =
        pleaseImplementAPropperEncryptionFunction(
          document.forms['identify'].elements['key'].value
        );
      document.forms['identify'].elements['key'].value = '';  
      document.forms['identify'].submit();
    }  
  }else{
    elm.style.display='none';
  }
}  

function person(id){
  document.forms['personPage'].elements['id'].value = id;
    document.forms['personPage'].action = 
        window.location.pathname 
      + window.location.search 
      + encodeURIComponent('person/');
  document.forms['personPage'].submit();
}

document.onkeyup = function(evt){
  if (evt.keyCode == 27) identifyUser('off');
  if (evt.keyCode == 13) identifyUser('go');
};
</script>



