<?php
/*============================================================================*\
  Getting ready

  (Some library functions are already loaded pre emptivly)
\*============================================================================*/

/*============================================================================*\
  Check enviroment
\* ===========================================================================*/
// Check that mod curl are instaled
/*
Curl is no logner supportyed
if(!function_exists("curl_init"))
  error(5,"PHP5-Curl library is not installed",true);
*/

// Check that magic quots are off
if(get_magic_quotes_gpc())
  error(5,"Magic quotes are turned on int the php.ini. It can't work with this application",true);

// Get context either form URL or last use
$context=(empty($_GET['context'])?"/":$_GET['context']);
//$_SESSION['context']=$context;

// Set default theme
if(@empty($_SESSION['theme']))
  $_SESSION['theme']="./theme/";

// Get alert state
$alert=file_get_contents('var/alert.dat');
$class=$alert?$alert."_alert":"";

/*============================================================================*\
  -- Start HTML --
  Make a HTML5 header
  Prevent scrolling
  Load style sheet
  Load page services script
\*============================================================================*/
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.
header("Content-Security-Policy: default-src 'self'");

?><!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8" />
<title>SmartCore</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="/theme/favicon.ico" >
<link rel="icon" href="/theme/favicon_big.ico" type="image/ico" >
<link rel="stylesheet" type="text/css" href="/theme/theme.css" />
<script type="text/javascript" src="/page_services.js" /></script>
<script type="text/javascript" src="/plib.js" /></script>
</head>
<?php
/*============================================================================*\
  Set alert state
\*============================================================================*/
echo "<body  class=\"$class\">\n";

/*============================================================================*\
  Test that server session information is ok
\*============================================================================*/
if(@empty($_SERVER['SESSION']))
  error(2,"Sorry. Unable to setup priviliges to show this page. No server session.
",true);

if(@empty($_SERVER['SESSION']['trust']))
  error(2,"Sorry. Your priviliges to access this is undetermined.
",true);

/*============================================================================*\
  Add panel
\*============================================================================*/
include 'panel.php';

/*============================================================================*\
  Auto render all interaction in a directory

  Make the best posible guess on a presentaion of an interaction, based on the
  interaction data file.
\*============================================================================*/
function render_all_interaction_of_dir($dir,$use_ctx_icons=false){
  $result['html']="";
  $result['js']="";;
  $result['watch_list']=[];

  // Look for interactions
  foreach(glob($dir."*.ia-dat",GLOB_MARK) as $iafn){

    // Make full path interaction name
    $ia=substr($iafn,strpos($iafn,"/",2),-7);
    $ia_data=json_decode(str_replace( "\n", "",file_get_contents($iafn)),true);

    if(!isset($ia_data['cmd'])) continue;

    // Find out what is allowed
    if(@$ia_data['cmd']['get'][1]>@$_SERVER['SESSION']['trust'])
      continue;

    $res=generate_tile_from_iadata($ia_data,$ia,'',false);

    // Add results
    if(is_array($res)){
      if(!empty($res['html'])) $result['html'].=$res['html'];
      if(!empty($res['js'])) $result['js'].=$res['js'];
      $result['watch_list'][$ia]=$ia;
    }
  }

  return $result;
}

/*============================================================================*\
  Generate page in current context

  The script will display interactions in the context tree, as defined by
  *.ia-dat JSON files, placed in it, if posible,
  The interaction in a given context, plus interactions of sub contexts, will be
  displayed, in that order.
  The interaction display is done by this priority:
  - icon field define image to represent it.
  - present field will be used in a 400 by 400 box
  - type field will be used to generate a generic icon.
  - a text box is used.

  index.php
  If an index.php script is present, only the output of that is displayed. The
  script is responsible for the page layout, except the panel.
  index script is responsible for displaying links to underlying sub contexts.
  the array $interaction[<full name>] is populated with all interactions of that
  directory.

  tile.php
  If a tile.php script is present in a sub directory, only that is displayed,
  from that directory.
  A tile.php is a short representation/overview/teaser of a context.
  the array $interaction[<full name>] is populated with all interactions of that
  directory.

  *.ia-dat file
  An interaction data file, defines the use of that particular function and how
  it connect to a physical device.

  The Icon that represent the context region are named <context (short name)>-ctx.png
    ex: bedroom-ctx.png

  the index.php or tile.php writes directly to the display.


  interactions are rendered in this order:
    - <ia name>-tile.php present in context
    - autogenerated from <ia name>.ia-dat file
  or in a userfefined manner.



\*============================================================================*/

/*============================================================================*\
  Render tiles

  Tiles are tile.php files placed in subdirectories to this context.
  include script to render tile, in a closed scope
  Collect the output and return it as a string
  Collect list of events to respond to

  The script generates the HTML to show a tile. Including functionality to make
  it interactive.
  I alså set an array of events (interactions) to watch for.

  Use the global variable/immidiate function $include to include script snipets
\*============================================================================*/
/*============================================================================*\
Add to Whatch event listner
The list is an element name (tag id) containing interaction name to watch for
eg: $watch_list[<tag id>] = <full context interaction name>;
for multiple interactions use an array of interactions
\*============================================================================*/
//echo "<pre>".print_r($GLOBALS,true) ."</pre>";

echo "<div class=\"main_container\">\n";

// Set context
$root="./context";
if(!is_dir($root.$context)) $context="/";

$js="";
$watch_list=[];
$render_subcontexts = true;

// Render index.php or autogenerate page with interactions in context
if(file_exists("./context{$context}index.php")){
  $result = $include("index.php",$context);
  if(@$result['html'])
    echo "{$result['html']}\n";
  if(@$result['js'])
    $js.=$result['js']."\n";
  if(@is_array($result['watch_list']))
    $watch_list=array_merge($watch_list,$result['watch_list']);

}else{
  $result=render_all_interaction_of_dir("./context$context",false);
  if(@$result['html'])
    echo "{$result['html']}\n";
  if(@$result['js'])
    $js.=$result['js']."\n";
  if(@is_array($result['watch_list']))
    $watch_list=array_merge($watch_list,$result['watch_list']);

// Render Sub-contexts render
  foreach(glob($root.$context."*",GLOB_MARK | GLOB_NOSORT | GLOB_ONLYDIR) as $subpath){
    // extract sub context
    $subcontext=substr($subpath,strlen($root));
    $tile_exists = file_exists("./context{$subcontext}tile.php");
    if($tile_exists){
      $result = $include("tile.php",$subcontext);
      if(isset($result['html']))
        echo "{$result['html']}\n";
      if(isset($result['js']))
        $js.=$result['js']."\n";
      if(isset($result['watch_list']) && is_array($result['watch_list']))
        $watch_list=array_merge($watch_list,$result['watch_list']);

    // Make a link tile to sub context:
    // If there is an index.php file in the sub-context
    // or there is a sub-contexts to the sub-context
    // or there is more then one interactions in the sub-context and no tile.php
    }elseif(file_exists("./context{$subcontext}index.php")
      || count(glob("./context{$subcontext}*", GLOB_NOSORT | GLOB_ONLYDIR ))>0
      || (!$tile_exists
          && count(glob("./context{$subcontext}*.ia-dat", GLOB_NOSORT))>1 )
      ){

      $title=substr($subcontext,strrpos($subcontext,"/",-2)+1,-1);

      // Make tile
      echo "<div class=\"tile\" title=\"$title\" alt=\"$title\"";
      echo " onclick=\"set_context('{$subcontext}');\"";
      echo "";

      // Use context icon
      if(file_exists($_SESSION['theme'].$title."-ctx.png")){
        echo " style=\"background-image: url({$_SESSION['theme']}{$title}-ctx.png);\">";
      // Use any icon
      }elseif(file_exists($_SESSION['theme'].$title.".png")){
        echo " style=\"background-image: url({$_SESSION['theme']}{$title}.png);\">";

      // Use default theme icon
      }elseif(file_exists($_SESSION['theme']."default.png")){
        echo " style=\"background-image: url({$_SESSION['theme']}default.png);\">";

      // Use default icon
      }elseif(strlen($_SESSION['theme'])<9
          && file_exists($_SESSION['theme']."/theme/default.png")){
        echo " style=\"background-image: url({$_SESSION['theme']}default.png);\">";

      // Center text tile
      }else{
        echo "  style=\"display: table; text-align: center;\">";
        echo " <span style=\"display:table-cell; vertical-align:middle;";
        echo " text-align: center; \">$title</span>\n";
      }
      echo "</div>\n";

    // Autogenerate tile using interactions definition files
    }else{
      $result=render_all_interaction_of_dir($subpath,true);
      // Display and store results
      if($result['html']){
        echo "{$result['html']}\n";
        if($result['js'])
          $js.=$result['js']."\n";
        if(is_array($result['watch_list']))
          $watch_list=array_merge($watch_list,$result['watch_list']);
      }
    }
  }

  // Ask page services to watch for event for eatch element in the watch list.
  if(is_array($watch_list) || $js){
    echo "<script type=\"text/javascript\">\n";
    echo $js;
    if(is_array($watch_list)){
      echo "ps.on('open',function(){\n";
      foreach($watch_list as $id=>$event){
        echo "  ps.watch('$event');\n";
        echo "  ps.cmd('$id get');\n";
      }
      echo "});\n";
    }
    echo "</script>\n";
  }
}
?>
<script>
console.log("document.referrer",document.referrer);
console.log("window.history",window.history);
</script>
</body>
</html>
