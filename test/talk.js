/*============================================================================*\
  Talk.js 
  
  (c) Paragi 2015, By Simon Rigét
  
  This contains the JS code for the tall tile, used in the panel.
  
\*============================================================================*/
var Talk={};
Talk.history = [];
Talk.historyPos = 0;
Talk.historyCurrent = '';
Talk.monitorState="off-line";
Talk.autoFocus=true;
Talk.lines=[];
Talk.maxLines=200;
Talk.backupFunction='';

/*============================================================================*\
  Key press handler
\*============================================================================*/
Talk.KeyPressHandler=function (e) { 
  // Enter
  if (e.keyCode == 13 ){
    if (this.value) {
      Talk.history[Talk.history.length] = this.value;
      Talk.historyPos = Talk.history.length;

      Talk.Output(this.value,'user');
      
      ps.cmd(this.value,Talk.CommandCallback);
      this.value="";
    }

  // Escape
  }else if (e.keyCode == 27 ){
    Talk.historyPos = Talk.history.length;
    // Scroll to top
    document.getElementById('talk-output').scrollTop = 0;  

    this.value="";

  // Arrow up/down    
  }else if (Talk.history.length && (e.keyCode == 38 || e.keyCode == 40)) {
    if (Talk.history[Talk.historyPos]) 
      Talk.history[Talk.historyPos] = this.value;
    else 
      Talk.historyCurrent = this.value;

    if (e.keyCode == 38){ // up
      if (--Talk.historyPos < 0) 
        Talk.historyPos = 0;

    }else if (e.keyCode == 40){  // down
      if (++Talk.historyPos > Talk.history.length) 
        Talk.historyPos = Talk.history.length;
    }
    
    this.value = (Talk.history[Talk.historyPos] ? 
      Talk.history[Talk.historyPos] : Talk.historyCurrent);
  }
}


/*============================================================================*\
  Output text to talk console as a responce to a command 
\*============================================================================*/
Talk.CommandCallback = function(response){
  if(response.reply) Talk.Output(response.reply);
  if(response.result) Talk.Output(response.result);
  //if(response.error) Talk.Output(response.error,'error');
  
  if(['ready','failed','working','denied','ok'].includes(response.reply))
    sound(response.reply);
}
/*============================================================================*\
  Output text to talk console
  
  Display format: 
  Input line always on top. 
  Dialog scrolling down, with newest on top.
    <2. request>: <reply>  
    <result if any>
    <1. request>: <reply>  
    <result if any>

  New request is marked by a <br id="next"> tag.
\*============================================================================*/
Talk.Output = function(text,type='reply'){
  // Insert line and add Add color
  if(type=='user')
    Talk.lines.unshift("<span class=\"talk-user\">" + text + "</span>");
  else if(type=='error')    
    Talk.lines.unshift("<span class=\"talk-computer-error\">" + text + "</span>");
  else
    Talk.lines.unshift("<span class=\"talk-computer\">" + text + "</span>");
    
  // trim line buffer
  if(Talk.lines.length>Talk.maxLines) lines.pop();
 
  // Output
  outputArea = document.getElementById('talk-output');
  outputArea.innerHTML=Talk.lines.join("\n");
  
  // Scroll to top
  outputArea.scrollTop = 0;  
}


/*============================================================================*\
  Set monitor 

  Wether to listen to all events, regarding this page or just responses to user
  commands given in talk input.
\*============================================================================*/
Talk.SetMonitor = function(func){

  // Toggle
  if(!["on","off"].includes(func)) func = Talk.monitorState=="off" ? "on" : "off";

  switch(func){
    case "on":
      Talk.monitorState = "on";
      cmd=function(command){
        Talk.Output(command,'user');
        return ps.cmd(command,Talk.CommandCallback);
      }  
      
      break;
    case "off":
      Talk.monitorState = "off";
      if(typeof Talk.hijackBackupFunction === 'function') 
        cmd=Talk.hijackBackupFunction;
      break;
  }
  
  image=document.getElementById('talk-monitor');
  if(image) image.src="/theme/monitor:" + Talk.monitorState + ".png";
}

/*============================================================================*\
  Stay in focus 
  
  Called whenever the talk input field looses focus
\*============================================================================*/
Talk.StayInFocus = function(element){
  if(Talk.autoFocus) Talk.autoFocus.focus();
}

/*============================================================================*\
  Initialise talk object, but wait until the page is fully loaded
\*============================================================================*/
window.addEventListener("load", function(){

  // Attach an event handler for keystrokes
  var cmdInputLine = document.getElementById('talk');
  if(cmdInputLine)
    cmdInputLine.addEventListener('keydown', Talk.KeyPressHandler, false);
  Talk.SetMonitor('off'); 
  
  // Activate auto focus, if there is only the one input field.
  var inputFields = document.getElementsByTagName('input');
  var textareaFields = document.getElementsByTagName('textarea');
  if(inputFields.length ==1 && textareaFields.length == 0)
    Talk.autoFocus = inputFields[0];

  // Save Page service talk back function    
  Talk.hijackBackupFunction = cmd;

},false);

