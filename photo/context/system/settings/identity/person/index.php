<?php
/*============================================================================*\
  -- This section is all about login security. Be carefull! ---
\*============================================================================*/
$title="Person";
$sensitivity=array("get"=>20,"set"=>80);
if ($_SERVER['SESSION']['trust'] < $sensitivity['get']) return;

require "$_SERVER[DOCUMENT_ROOT]/services.php";
require "$_SERVER[DOCUMENT_ROOT]/present.php";
require "$_SERVER[DOCUMENT_ROOT]/net_trace.php";

echo "<div calss=\"container\">\n";

$user_id = intval($_POST['id']);
if($user_id){
  $response = services('datastore','person.get',["user_id"=>$user_id]);

  $image = "/photo/$user_id.jpg";
  if(!file_exists($_SERVER['DOCUMENT_ROOT'] . $image))
    $image = image_path("identity-no-photo.png");
  echo "<div class=\"tile\" onclick=\"person($user_id);\""
    . " style=\"background-image: url($image);"
    . " background-size: cover; margin: 5%;\"></div>\n"
  ;

  print_r($response);
}else
  echo "<h1>New User</h1>\n";
  
echo "</div>\n";


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

document.onkeyup = function(evt){
  if (evt.keyCode == 27) identifyUser('off');
  if (evt.keyCode == 13) identifyUser('go');
};
</script>



