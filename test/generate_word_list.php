<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8" />
<title>Test page</title>
<link rel="shortcut icon" href="/favicon.ico" >
<link rel="icon" href="/favicon_big.ico" type="image/ico" >
<link rel="stylesheet" type="text/css" href="/theme/theme.css" />

<style>
</style>

</head>
<body>
<h1>Generate word list</h1>
<pre>
<?php
define('_DEV_DEBUG',true);

$sys_word=[
   'say'=>['say'=>'s']
  ,'why'=>['why'=>'s']
  ,'analyse'=>['analyse'=>'s']
  ,'explain'=>['explain'=>'s']
  ,'up'=>['up'=>'s']
  ,'top'=>['top'=>'s']
  ,'reload'=>['reload'=>'s']
  ,'context'=>['context'=>'s']
  ,'red'=>['red_alert'=>'s']
  ,'yellow'=>['yellow_alert'=>'s']
  ,'blue'=>['blue_alert'=>'s']
  ,'green'=>['green_alert'=>'s']
  ,'alert'=>['alert off'=>'s']
  ,'computer'=>['computer'=>'s']
  ,'wait'=>['wait'=>'s']
  ,'access'=>['access'=>'s']
  ,'test'=>['test'=>'s']
  ,'delete'=>['delete'=>'s']
];

$action_word=[
   'on'=>['on'=>'a']
  ,'off'=>['off'=>'a']
  ,'get'=>['get'=>'a']
  ,'set'=>['set'=>'a']
];


// Get word file
include "words.php";
$old_count=count($word_list);
$word_list=[];
$word_list=$sys_word;

// Search context for words
function search_contexts($path){
  global $word_list;
  $c=0;
  // search for tile.php and index.php og ia-dat its a valid context
  if(file_exists($path."/tile.php") || file_exists($path."/index.php")){
    // Break down multible words to single word referances
    $w=substr($path,strrpos($path,"/",-2)+1,-1);
    $c=substr($path,strpos($path,"/",2));
    if($w && $c) $word_list[$w][$c]='c';      
  }
  // Search for interaction data files
  $a=glob($path.'*.ia-dat',GLOB_NOSORT);
  if(is_array($a)){ 
    // Add to context
    $w=substr($path,strrpos($path,"/",-2)+1,-1);
    $c=substr($path,strpos($path,"/",2));
    if($w && $c) $word_list[$w][$c]='c';      
    // Add to interactions   
    foreach($a as $i){
      $w=substr($i,strrpos($i,"/",-2)+1,-7);
      $ia=substr($i,strpos($i,"/",2),strrpos($i,".ia-dat")-strlen($i));
      if($w && $c) $word_list[$w][$ia]='i';      
    }
  }
  // Search recursively
  $a=glob($path.'*', GLOB_ONLYDIR|GLOB_NOSORT);
  if(is_array($a)) 
    foreach($a as $subpath)
      search_contexts($subpath.'/');
}

// Remove context and interaction words
foreach($word_list as $w=>$a)
  foreach($a as $i=>$aa)
    if($aa[0]=='c' || $aa[0]=='i') unset($word_list[$w][$i]);

search_contexts('./context/');
// Remove empty entries
$word_list=array_filter($word_list);
$new_count=count($word_list);

// Get remarks from old word file
$word_file=file_get_contents("words.php");

// Write new word file into thje /var dir
if(file_put_contents("var/words.php"
  ,substr($word_file,0,strpos($word_file,'$word_list'))
  .'$word_list='
  .var_export(array_filter($word_list),true)
  .";\n?>"
)){
  echo "A new word file has been written to the /var directory.\n";
  echo "  The file contains $new_count words. The old one had $old_count words";
  foreach($word_list as $w=>$a){
    echo "\n$w :";
    foreach($a as $t) echo "$t[0] ";
  }
}else
  echo "Backup failed\n";

?>
</pre>
</body>
</html>
