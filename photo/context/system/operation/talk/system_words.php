<?php
/*============================================================================*\
  List of system command words, used by the command interpreter
\*============================================================================*/
$system_word_list = [
   "say" => ["say" => "s"]
  ,"why" => ["why" => "s"]
  ,"help" => ["help" => "s"]
  ,"analyse" => ["analyse" => "s"]
  ,"explain" => ["explain" => "s"]
  ,"up" => ["up" => "s"]
  ,"top" => ["top" => "s"]
  ,"reload" => ["reload" => "s"]
  ,"context" => ["context" => "s","/" => "c"]
  ,"red" => ["red_alert" => "s"]
  ,"yellow" => ["yellow_alert" => "s"]
  ,"blue" => ["blue_alert" => "s"]
  ,"green" => ["green_alert" => "s"]
  ,"alert" => [
     "alert off" => "s"
    ,"red_alert" => "s"
    ,"green_alert" => "s"
    ,"blue_alert" => "s"
    ,"yellow_alert" => "s"
    ,"/utility/system/alert/" => "c"
  ]
  ,"computer" => ["computer" => "s"]
  ,"wait" => ["wait" => "s"]
  ,"access" => ["access" => "s"]
  ,"test" => ["test" => "s"]
  ,"delete" => ["delete" => "s"]
  ,"hi" => ["hi" => "s"]
  ,"hello" => ["hello" => "s"]
  ,"howdy" => ["howdy" => "s"]
  ,"fuck" => ["fuck" => "s"]
  ,"how" => ["how" => "s"]
  ,"yes" => ["yes" => "s"]
  ,"no" => ["no" => "s"]
  ,"all" => ["all" => "s"]
  ,"do" => ["do" => "s"]
  ,"if" => ["if" => "s"]
  ,"verify" => ["verify" => "s"]
  ,"list" => ["list" => "s"]
  ,"origin" => ["origin" => "s"]
  ,"root" => ["root" => "s"]
  ,"mute" => ["alert mute" => "s"]
  ,"context" => ["context" => "s"]
  ,"pod" => ["pod bay door" => "s"]  
];
