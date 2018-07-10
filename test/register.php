<?php
/*============================================================================*\
  Generate Terminal ID cookie.
  
  This must be done before the header is sent.      
\*============================================================================*/
if(empty($_COOKIE['TID'])){
  if(!empty($_POST['create'])){
    // Generate new TID
    $remote=$_SERVER['REMOTE_ADDR'];

    $TID=password_hash($_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR'], PASSWORD_DEFAULT);

    // Make TID cookie. Expire in 100 years. Only on this site
    //  setcookie ("TID",$TID,2000000000,"/", $_SERVER['HTTP_HOST'],false,true);
    setcookie ("TID",$TID,2000000000,"/", "",false,true);
    
    // Save settings
    
    // Relocate to context root
  }
}else{
  // Relocate to context root
}

?><!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8" />
<title>SmartCore</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="/favicon.ico" >
<link rel="icon" href="/favicon_big.ico" type="image/ico" >
<link rel="stylesheet" type="text/css" href="/theme/theme.css" />
</head>

<body>
<!-- Logo --->
<div class="panel" style="background-image: url(/theme/smartcore.png);  
  align-items:  flex-end;
  justify-content: flex-start;">
<i>SmartCore System I</i>
</div>
<br>

<?php
/*
  Show this message if
    origin ip is not local
    hop is greeater then 1
    user agent or other is vauge
    There is registred terminals (Not first time startup)
  

*/
?>
<div class="container" style="text-align: center; background: #700">
This is a private site.<br>
If you don't have a legitimate reason to be here, Please refrain from using it any further.
</div>
<br>

<h1>Terminal setup</h1>
<div class="container">
Please register this terminal to get started:<br><br>
<form name="register" method="POST">
<input type="hidden" name="create" value="true">
<table>
<tr><td></td><td> </td></tr>

<tr><td>Who are you?</td><td><input type"text" name="user" autofocus required="required"></td></tr>

<tr><td>What name do you want to assign to this terminal?</td><td><input type"text" name="terminal_name" autofocus required="required"></td></tr>

<tr><td>Is this a stationary terminal?</td><td>
<select name="usual" required="required">
<option value="yes">Yes</option>
<option value="">No</option>
</select>
</td></tr>

<tr><td>You can change these settings later</td><td>
<button type="submit" >Register</button>
</td></tr>

</table>
</form>
</div>

<div class="container">
<pre>
<div id="compabilityTest"></div>
</pre>
</div>

<div class="container">
<pre>
<?php
if(empty($_COOKIE['TID'])){
  if(!empty($_POST['create'])){
    echo "Created. Should relocate now <br>\n";
    exit;
  }
  echo "NO TID<br>\n";
}else    
  echo "TID already exists. Should relocate now <br>\n";
print_r($GLOBALS);
/*

Checks:

  only local
  no hops
  


<?php
  print 'document.cookie="apply=\''.uniqid().",".$_SERVER['REMOTE_ADDR'].'\'; expires=" + exdate.toUTCString();';
?>

  // HTTP agent
  $http_agent=$_SERVER['HTTP_USER_AGENT'];

  if(strpos($http_agent,"iPhone")!==false)
    $mobile="yes";
  else
    $mobile="no";

  $languages= $_SERVER['HTTP_ACCEPT_LANGUAGE'];

  $apply=htmlspecialchars($_COOKIE['apply']);  
  // Check IP

  //$user_info=json_decode(file_get_contents("apply-$apply.txt"),true);
  if(is_array($user_info))
    unlink("apply-$apply.txt");  

  $suspicion=0;
  $trust=100;
*/


/*============================================================================*\
  Test Browser compability
\*============================================================================*/
?>
<script>
  elm=document.getElementById("compabilityTest");

  if (!!!window.EventSource)
    elm.innerHTML+="Sory. Your browser doesn't support <b>Server-Sent Events</b>. You can't use this application without it.\n";

  if(!navigator.cookieEnabled)
    elm.innerHTML+="Sory. Your browser must have the use of <b>cookies enabled</b>. You can't use this application without it.\n";

  if(elm.innerHTML.length<1)
    elm.innerHTML="Browser checks out"; 
</script>
</body>
</html>

