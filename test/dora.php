<!DOCTYPE HTML>
<html>
<body>
<h1>This is Dora</h1>
<p id="output">Here would be the result of a server side calculation</p>
<form method="post">
<input type="hidden" id="somevalue" name="somevalue" value="0">
<button type="button" id="backButton" onclick="back();">Back</button>
<button type="submit">Calculate</button>
<a href="./alice.php">Go to Alice</a>
</form>
<?php 
  include "history.php";
?>
<script>
// Simulate some server side action
document.getElementById("output").innerHTML = document.getElementById("somevalue").value = "Value = " + Math.random();

</script>
</body>
</html>


