<?php
$title="Alert";
$sensitivity=array("get"=>20,"set"=>20);
if ($TRUST < $sensitivity['get']) return;

/*============================================================================*\

\*============================================================================*/
$path=".".substr(__DIR__,strlen($_SERVER['DOCUMENT_ROOT']))."/";
?>

<div class="tile" style="background-image:url(/theme/alert[red].png);" onclick="cmd('red alert');">
<span>Intruder alert</span>
</div>

<div class="tile" style="background-image:url(/theme/alert[yellow].png);"
 onclick="cmd('yellow alert');">
<p>Fire alert</p>
</div>

<div class="tile" style="background-image:url(/theme/alert[green].png);"
 onclick="cmd('green alert');">
<p>Cyber attack alert</p>
</div>

<div class="tile" style="background-image:url(/theme/alert[blue].png);"
 onclick="cmd('blue alert');">
<p>Unit failure</p>
</div>

<div class="tile" style="background-image:url(/theme/alert[mute].png);"
 onclick="cmd('alert mute');">
<p>Alert mute</p>
</div>

<div class="tile" style="background-image:url(/theme/alert[off].png);"
 onclick="cmd('alert off');">
<p>Alert off</p>
</div>

