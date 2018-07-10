<?php
/*============================================================================*\
  Programed action

  call is triggred either by an event or a user command
  
  Output must conform to a reply array in JSON format. If not, the output are
  treated as an error message.

  The following variables are set when this script is called:
    $context  Full path context
\*============================================================================*/

/*============================================================================*\
  Define access security. 
  Think carefull about who has access to this funtion and how sensitive it is!
\*============================================================================*/
$read_sensitivity=20;
$write_sensitivity=40;
if ($trust<$read_sensitivity) return;
$action=false;
if ($trust>=$write_sensitivity) $action=true;

/*============================================================================*\
  Reply format:
    error:    A short and frendly explanation as to why it failed.
    reply:    OK | failed | textual responce
\*============================================================================*/
$out=array("error"=>"","reply"=>"");


// End program
if($out['error']) $out['reply']='failed';
else if(!$out['reply']) $out['reply']='ok';
echo json_encode($out,JSON_PRETTY_PRINT | JSON_PRETTY_PRINT);
?>

