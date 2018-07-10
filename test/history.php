<?php
/*------------------------------------------------------------------------------
  History back
  
  Create and maintain a client side stack pages.
  
  
------------------------------------------------------------------------------*/
?>
<script>
// Load or create a history array
var pageHistory = JSON.parse(sessionStorage.pageHistory || '[]');

// Find this page in history
var thisPageIndex = pageHistory.indexOf(window.location.pathname);

// If this page was not in the history, add it to the top
if( thisPageIndex < 0){
  pageHistory.push(window.location.pathname);
  thisPageIndex = pageHistory.length -1;
  
// Wipe the forward history
}else if(thisPageIndex < pageHistory.length -1){
  for(; thisPageIndex < pageHistory.length -1;)
    pageHistory.pop();
}

// Store history array   
sessionStorage.pageHistory = JSON.stringify(pageHistory);

// Back button function
function back(){
  if(thisPageIndex > 0 ) 
    window.location.href = pageHistory[thisPageIndex - 1]; 
}

// Disable back button if this is the first page
if(thisPageIndex < 1) 
  document.getElementById("backButton").disabled = true;
      
  
  
console.log("thisPageIndex",thisPageIndex);  
window.history.back = back; // no work!
console.log("sessionStorage",sessionStorage);
</script>
