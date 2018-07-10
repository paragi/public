<?php
/*============================================================================*\
  Guest and registartion page
  
  
  stage:
  1.  Show a preface to the system, with an invitation to enter.
  2.  Begin registartion process with some user input and create terminal ID
  3.  Gather information about termina and user, Process it and create a 
      pending record.
      Inform that the registration is pending acceptance form admin
  0.  Special case for system testing

\*============================================================================*/
/*============================================================================*\
  Settings
\*============================================================================*/
$welcome_message = "Welcome to the Smart Space";
$allow_external = true;
//$debug = true;
/*============================================================================*\
  Determine enviroment and process stage
\*============================================================================*/
// External connected IP address
$external = (ip2long($_SERVER['SERVER_ADDR'])>>8 != ip2long($_SERVER['REMOTE_ADDR'])>>8); 
if($external && !$allow_external) exit;

// Determine stage
$stage = 1;
if(!empty($_GET['test']) && in_array($_GET['test'],['replace','delete']))
  $stage = 0;
elseif(empty($_COOKIE['TID']) || strlen($_COOKIE['TID']) != 128)
  $stage = 1;
elseif(!empty($_POST) && !empty($_POST['register']))
  if($_POST['register'] == 'enter')
    $stage = 2;
  elseif($_POST['register'] == 'apply')
    $stage = 3;

// Generate a unique terminal cookie
// Cookies are headers and must be sent prior to any HTML statements.
if($stage < 2){
  // delete TID
  if(@$_GET['test'] == 'delete')
    setcookie("TID","",time()-300,"/"); 

  // Validate existing TID (illigal charakters and lengths)
  elseif(!preg_match('/^[0-9a-z]{128}$/',$_COOKIE['TID'])) 
    // Create a string of 128 random hex values
    setcookie("TID",bin2hex(openssl_random_pseudo_bytes(64)),2147483647,"/");
}

/*============================================================================*\
  Generate a basic HTML header
\*============================================================================*/
?><!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8" />
<title>SmartCore</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="./favicon.ico" type="image/ico">
<link rel="icon" href="./favicon_big.ico" type="image/ico">
<link rel="stylesheet" type="text/css" href="./theme.css" />
</head>
<body>
<div class="main_container">

<?php
/*============================================================================*\
  Stage 1: Show a preface to the system, with an invitation to enter.
  
  If the user choose to enter, submit a form to begin the registration process.
  
  And do same browser compability checks
\*============================================================================*/
if($stage == 1){

  // If browser compability failes show message
  if(!empty($_POST['reason'])){
    echo "Sorry. You can't use this termianl device.<br>";
    echo "<b>{$_POST['reason']}</b>";
    echo " and this system can't work without it.<br>\n";
    
  // Show welcome  
  }else{
    echo "<h1>$welcome_message</h1>\n";
  
  // HTML and Javascript
?>
<div class="tile" onclick="document.forms.terminal.submit();" 
style="background-image: url(request.png); margin: auto;" alt="Press to enter"></div>

<form name="terminal" method="POST">
<input type="hidden" name="register" value="failed">
<input type="hidden" name="reason" value="Your device does not allow Javascript">
</form>

<script type="text/javascript">
  if(!window.CanvasRenderingContext2D) 
    document.forms.terminal.reason.value='HTML5 compability is required';

  else if(!("geolocation" in navigator))
    document.forms.terminal.reason.value='localisation must be enabled';

  else if(!("cookieEnabled" in navigator))
    document.forms.terminal.reason.value='Cookies must be enabled';

  else{
    document.forms.terminal.register.value='enter';
    document.forms.terminal.reason.value='';
  }
</script>

<?php
  }
  
/*============================================================================*\
  Stage 2: Get some user input.
  
  Ask for terminal device name
  
  Determine if this is an external request. I so, 
    ask for:
      email and phone confirmation
      location to be turned on
      Name
  
    set trust to zero
    
    send email and phone confirmation codes etc.
    
    set up a page to clear it
        
 Apply for access     
    
\*============================================================================*/
}elseif($stage == 2){

  // Input form
?>
<div class="container">What is the name of the device you are using right now?
</div>
<div class="flex_break">&nbsp;</div>

<form name="terminal" method="POST">
<input type="hidden" name="register" value="apply">
<input type="hidden" name="reason">
<input type="hidden" name="latitude">
<input type="hidden" name="longitude">
<input type="hidden" name="accuracy">
<input type="hidden" name="external_ip">

<table style="margin: auto;">
<tr>
  <td>Device name:</td>
  <td><input type="text" name="terminal_name" 
  value="<?php echo @$_POST['terminal_name'];?>"
  placeholder="Ex: Simon's phone" autofocus></td>
</tr>

<?php

  // If terminal is from external network, add some queries and checks
  if($external){

    // get geolocation and IP location. Set default country etc.
?>
<tr>
  <td>Your full name:</td>
  <td><input type="text" name="user" value="<?php echo @$_POST['user'];?>"
  placeholder="Ex: simon@gmail.com"></td>
</tr>
<tr>
  <td>E-mail (needs to be verified):</td>
  <td><input type="email" name="email" value="<?php echo @$_POST['email'];?>"
  placeholder="Ex: simon@gmail.com"></td>
</tr>
<tr>
  <td>Phone (needs to be verified):</td>
  <td><input type="tel" name="phone" value="<?php echo @$_POST['phone'];?>"
  placeholder="Ex: +45 12345678"></td>
</tr>
<tr>
  <td>Country you are ibn now:</td>
  <td><input type="text" name="country" value="<?php echo @$_POST['country'];?>"
  placeholder=""></td>
</tr>
<tr>
  <td>What do you call your current location:</td>
  <td><input type="text" name="location" value="<?php echo @$_POST['location'];?>"
  placeholder="Ex: Home"></td>
</tr>
<tr>
  <td>Is this a stationary device?:</td>
  <td><input type="checkbox" name="stationary" 
  <?php echo @$_POST['stationary'] == 'true' ? 'checked' : ''; ?> 
  ></td>
</tr>
<tr>
  <td>Are you the only user?:</td>
  <td><input type="checkbox" name="usual_user" 
  <?php echo @$_POST['usual_user'] == 'true' ? 'checked' : '' ;?> 
  ></td>
</tr>

<?php
  }
?>  

</table>
</form>

<div class="flex_break">&nbsp;</div>
<div class="tile" onclick="document.forms.terminal.submit();" 
style="background-image: url(apply.png); margin: auto;" alt="Press to apply for access"></div>
<div class="flex_break">&nbsp;</div>

<div class="container">(The name is used to identify your device to the administrator)
</div>

<?php
/*============================================================================*\
  Stage 3: Gather information about termina and user, Process it and create a 
      pending record.
      Depending on the outcore, either redirect to root or go to stage 4
\*============================================================================*/
}elseif($stage == 3){

  // Assamble a terminal record
  $terminal['tid'] = $_COOKIE['TID'];
  $terminal['status'] = 'pending';

  // From applicant
  $terminal['terminal_name'] = $_POST['terminal_name'];
  $terminal['email'] = $_POST['email'];
  $terminal['phone'] = preg_replace('/(\+*\d{1,})*([ |\(])*(\d{3})[^\d]*(\d{3})[^\d]*(\d{4})/', '<a href="tel:$1$3$4$5">$1 ($3) $4-$5</a>', $_POST['phone']);
  $terminal['country'] = $_POST['country'];
  $terminal['location'] = $_POST['location'];
  $terminal['mobile'] = empty($_POST['stationary']);
  if($_POST['usual_user'] == "true") $terminal['default_user'] = $_POST['user'];
  $terminal['applicant'] = $_POST['user'];

  // from browser
  $terminal['usual_ip'] = $_SERVER['REMOTE_ADDR'];
  $terminal['http_agent'] = $_SERVER['HTTP_USER_AGENT'];
  $terminal['http_languages'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

  // from geolocation
  $terminal['latitude'] = @$_POST['latitude'] ?: '';
  $terminal['longitude'] = @$_POST['longitude'] ?: '';
  $terminal['accuracy'] = @$_POST['accuracy'] ?: '';

  do{
    // Verify some user inputs
    $fail = "Terminal ID is malformed";
    if(strlen($terminal['tid']) < 128) break;
    
    $fail = "Terminal name must be at least 4 letters long";
    if(strlen($terminal['terminal_name']) < 4) break;
  
    if($external){
      $fail = "E-mail address is invalid";
      if(!filter_var($terminal['email'], FILTER_VALIDATE_EMAIL)) break;

      $fail = "Phone number seems to be invalid";
      $len = strlen($terminal['phone']);
      if($len < 6 || $len >45) break;

      $fail = "Your name is too short";
      if(strlen($terminal['applicant']) < 3) break;
    } 
        
    $fail = "Your IP addres seems to be invalid";
    if(!filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) break;

    $fail = "Your language settings seems invalid";
    if(strlen($terminal['http_languages']) < 3) break;
    
    $fail = "Your browser agent string seems invalid";
    if(strlen($terminal['http_agent']) < 40) break;

    //network trace
    require "$_SERVER[DOCUMENT_ROOT]/net_trace.php";
    $trace=net_trace($_SERVER['REMOTE_ADDR']);
    if(!empty($trace['error'])){
      $fail = $trace['error'];
      break;
    }
    $terminal['max_hops'] = count($trace['result']);
    $terminal['network_location'] = end($trace['result'])['location'];

    // Calculate basic trust
    // This code is simplified to instant approval. This is for demo purpose only. 
    // NB: It is wildly insecure!
    if($external || $terminal['max_hops'] > 1)
      $terminal['trust'] = 20; // 0 No trust
    else  
      $terminal['trust'] = 100; // 20 Guest
    
    // Save application for aproval 
    $terminal['suspiciousness'] = 0;
    $terminal['location'] = $terminal['location'] ?: "Home";
  
    require "$_SERVER[DOCUMENT_ROOT]/services.php";
    $response = services("datastore","applyForSetTerminal",$terminal);
    if(!empty($response['error'])){
      $fail = "Internal issue: " . $response['error'];
      break;
    }
    
    $fail = "";
  }while(false);
  
  // Generate output
  if(!empty($fail)){
    echo "Sorry. Your device could not register.<br>\n";
    echo "($fail)<br>\n"; 
  }
  // Send verification e-mail

  // Send verification sms code 
}
/*============================================================================*\
  Stage 4: Waiting for approval.
  
  validate TID
\*============================================================================*/
if($stage >= 3 && empty($fail)){
    echo "Please wait for approval.<br>\n";
    echo "<div class=\"flex_break\">&nbsp;</div>";
    echo "<div class=\"tile\" onclick=\"window.location.href='/'\"";
    echo " style=\"background-image: url(enter.png); margin: auto;\""; 
    echo " alt=\"Press to apply for access\"";
    echo " ></div>\n";
}

echo "</div>";

if($debug){  
  echo "<pre>Debug information:\n";
  echo "Registration at stage: $stage\n";
  if(!empty($terminal))
    echo "Registering terminal with: ", print_r($terminal,true);
  echo "Page request: ", print_r($_REQUEST,true);
}

?>
</body>
</html>
