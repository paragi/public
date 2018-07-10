<?php
/*============================================================================*\
  Set sensitivity level of this page
  And test current session against it
\*============================================================================*/
$sensitivity=array("get"=>20,"set"=>60);
if ($trust<$sensitivity['get']) return;

$title="Chickencoop Cam 1";


/*============================================================================*\
  Display page
\*============================================================================*/
?>
<div class="tile" style="text-align:center">
<img id="/chickencoop/tempout" src="/theme/cam1.jpg"></img>
</div>


