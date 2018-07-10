<?php
/*============================================================================*\
  Access security. 
  Think carefull about who has access to this funtion and how sensitive it is!
\*============================================================================*/
$read_sensitivity=60;
$write_sensitivity=80;
$title="Create interaction";
$hint=$title;

/*============================================================================*\
   Access security. 
\*============================================================================*/

if ($_SESSION['trust']<$read_sensitivity) return;
$action=false;
if ($_SESSION['trust']>=$write_sensitivity) $action=true;

/*============================================================================*\
  Display page
\*============================================================================*/
?>
<div class="container">
<b>Create interaction</b><br>

<div class="tile">
Install it
</div>

<div class="tile">
Copy from existing
</div>

<div class="tile">
Copy from template
</div>

</div>


<div class="container" id="timer list">
<b>Interaction definition</b> 
<table>
<tr><td>Interaction name/function</td><td id="name">light</td></tr>
<tr><td>Device handler</td><td id=device_handler">gpio</td></tr>
<tr><td>Unit ID</td><td id="unit_id">gpio12</td></tr>

<tr><td>Presentation</td><td id="presentation"></td></tr>
<tr><td>Icon</td><td id="icon">bedstand.light:%s.png</td></tr>

<tr><td colspan="2">Actions:
<table>
<tr><td>Action</td><td>command code</td><td>Sensitivity</td></tr>
<tr><td id="action1">get</td><td id="cmd1-1">get</td><td id="sensitivity">20</td></tr>
<tr><td id="action1">set</td><td id="cmd1-1">set %s</td><td id="sensitivity">40</td></tr>
<tr><td id="action1"></td><td id="cmd1-1"></td><td id="sensitivity"></td></tr>
</table>
<td></tr>
</table>
</div>




<div class="container">

</div>



