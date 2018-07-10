<!DOCTYPE HTML>
<html>
<head>
<title>Test page</title>
<link href="theme/theme.css" type="text/css" rel="stylesheet">
<style>
</style>

</head>

<body>
<h1>Test JSON encoding</h1>
Remember to turn on developer view, to see console output
<br>

<script>
console.log('case 4:','-\"-'); // Outopus -"-
</script>

<?php
    $a=["html"=>'<div id="test/this">Simple text</div>'];

    $jt=json_encode($a); 
    echo "<script>var jt = ".$jt.";console.log('Case *2:',jt);</script>";
exit;
    echo "<pre>"; 
    echo "json_encode: ",htmlentities(json_encode($a)); 
    // becomes: {"html":"<div id=\"test\/this\">Simple text<\/div>"}
    echo "\n";
    
    $jt=rawurlencode(json_encode($a));
    echo "rawurlencode:",htmlentities($jt);
    // becomes: %7B%22html%22%3A%22%3Cdiv%20id%3D%5C%22test%5C%2Fthis%5C%22%3ESimple%20text%3C%5C%2Fdiv%3E%22%7D
    echo "\n</pre>";

    echo "<script>\n";

    echo "var jtu='$jt';";
    echo "\n";

    echo "console.log('raw:',jtu);";
    // case 2: %7B%22html%22%3A%22%3Cdiv%20id%3D%5C%22test%5C%2Fthis%5C%22%3ESimple%20text%3C%5C%2Fdiv%3E%22%7D   
    echo "\n";
    
    echo "var jt=decodeURIComponent(jtu);";
    echo "console.log('decodeURIComponent:',jt)";
    // decodeURIComponent(jt): {"html":"<div id=\"test\/this\">Simple text<\/div>"}
    echo "\n";

    echo "var jo=JSON.parse(jt);";
    echo "\n";
    echo "console.log('JSON.parse:',jo);";
    // Object {html: "<div id="test/this">Simple text</div>"}
    echo "\n";
    echo "</script>\n";
?>
</body>
</html>
