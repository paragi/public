/*============================================================================*\
  Set up a persistant websocket connection to the service module
  
  Mark page as on/off-line by changing style (dimming or bw)
  Make a public funtion to send command to the event handler


  A command is a text message to the server.
  The command has a more or less immediate reply as a confirmation. 
  In most cases the command envokes an event. If set up to listen for that event, 
  the terminal will receive an event notice as well. That ussually contains the 
  answer to the command, if it was a question.
  Some events are mandatory to respond to: Reload, X_alert
  Event updates is either calling a registred event callback function or if not
  registred, updating a HTML tag with the event as tag id if present.

  Event names for interactions are a full path context + interaction name

  Commands (and events) coded into a page, must have an underscore or slash to 
  avoid confusing them with spoken user commands.

  message format
  command: 
    cmd:    <command>
    cmdId: a scalar value will bereturned with the reply, to identify callback
    token:  a scalar value that bust be passed back and forth with the server
            to validate commands.

  response:
    reply:  Verbal reply to user. Simpel forms are: ok, Unable to comply, working.
    error:  Optional. Explanation of failure
    cmdId: returned from the request
    token:  New token to use

  event:
    event:  An unique event name (Must be a valid HTML tag id as well)
    state:  New state of the object of the event
    html:   Optional. HTML code update
    message: Optional. A text message update 
    token:  New token to use
    
  Mandatory events:
    Yellow_alert: fire, smoke
    red_alert:    Intruder or other physical danger
    Blue_alert:   Cyber atack or serious system malfunctions.
    Green_alert:  System failure
    alert_off:    Normal state

    reload_page:  Force reload page
    change_context: go to another context  
    receive_tid:  Update TID cookie
    receive_tcb:  Update Terminal code block    

\*============================================================================*/

// Extention for RegExp for escaping strings to legal identifiers 
// (without restricted words controle)
if(!RegExp.escape){
  RegExp.escape = function(s){
    return String(s).replace(/[\\^$*+?.()|[\]{}\/]/g, '$_');
  };
}

ps = {
   connection:    {}    // Connection
  ,callbackQueue: {}    // callback queue function pointers
  ,nextID:        1     // Unique index for callbackQueue  ,,,,,,,,,,,for paring comands with responses
  ,receiver:      {}    // receiver function for event message
  ,token:         false // Session token. Must reload if out of alignment
  ,timeOut:       5     // Send timeout; wait time for websocket to connect
  ,monitor:       ''    // function to call on event 
}

/*============================================================================*\
  Connect to smart core services via websocket
\*============================================================================*/
ps.connect=function(){
  // make a persistant connection to websocket server
  ps.connection = new WebSocket('ws://'+window.document.location.host+'/services');
  
  // Create event handler for "On Open websocket"
  ps.connection.onopen = function () {
    // Mark page as on-line
    document.body.classList.remove('body-off-line');
    ps.EventOccured('open');
  };

  // Create event handler for "On close websocket"
  ps.connection.onclose = function (code, message) {
    // Mark page as off-line
    document.body.classList.add('body-off-line');
    // Reconnect (after Connection timeout + this timeout)
    setTimeout(ps.connect,1000);
    ps.EventOccured('close');
  };

  // Create event handler for "incomming websockets mesages"
  ps.connection.onmessage = function (event) {

//console.log("Message received data: ",typeof event.data,event.data);
    // Convert JSON to Object
    var res=JSON.parse(event.data);
console.log("Message received: ",res);

    ps.token = res.token;

    ps.EventOccured('message',res);
    
    // Reply to a recently send command if there is a callback
    if(res.cmdId && typeof ps.callbackQueue[res.cmdId] === 'function'){
 console.log("queued callback identified");    
      ps.callbackQueue[res.cmdId](res);  
      if(res.reply!="working") delete ps.callbackQueue[res.cmdId];  
     
      if( res.html && (obj = document.getElementById(res.event))) {
        obj.outerHTML = decodeURI(res.html);
      }
    } 
    
    if(res.event){
      // Mandatory event: alert
      if(res.event.indexOf('alert')>=0){
        document.body.classList.remove('red_alert', 'yellow_alert', 'blue_alert', 'green_alert' );
        if(res.event.indexOf('_alert')>0)
          document.body.classList.add(res.event);
      
      // Sound alerts

      // Mandatory event: Reload page
      }else if(res.event=='reload_page') {
        window.location.reload();

      // Mandatory event: Change context
      }else if(res.event=='change_context' && res.context) {
        window.location=window.location.pathname+'?context='+encodeURIComponent(res.context);
        
      // Event function callback
      }else if(ps.receiver[res.event] instanceof Array){
        for(var i in ps.receiver[res.event])
          if(typeof ps.receiver[res.event][i] === 'function'){
console.log("There is a callback function registred");    
            ps.receiver[res.event][i](res);
          }
      // Default to event as tag id; Update HTML (Outer tag) 
      }else if( res.html && (obj = document.getElementById(res.event))) {
  console.log("its an element");    
        obj.outerHTML = decodeURI(res.html);
//        talk_back(res);

      // Trigger special event: message
      }else if(!res.cmdId && typeof ps.receiver.wsmessage === 'function'){
        ps.receiver.wsmessage(res);

      // No recipient
      }else{
        console.log('service message recieved but no recipient registred to event');      
      }
    }    
  };
}

/*============================================================================*\
  Register callback function to websocket events
  
  events can be specifig or 'all'
\*============================================================================*/
ps.on = function(event,callback){
  if(!(ps.receiver[event] instanceof Array)) ps.receiver[event]=[];
  ps.receiver[event].push(callback);
}

ps.EventOccured = function(event,data){
  // Go through list of receiverfor for this event
  if(ps.receiver[event] instanceof Array)
    for(var listner in ps.receiver[event])
      if(typeof ps.receiver[event][listner] === 'function')
        ps.receiver[event][listner](data);

  // Go through list of receiverfor for "all" events
  if(ps.receiver.all instanceof Array)
    for(var listner in ps.receiver.all)
      if(typeof ps.receiver.all[listner] === 'function')
        ps.receiver.all[listner](event,data);
}
/*============================================================================*\
  Send request to smart core services
\*============================================================================*/
ps.serviceRequest=function(obj,callback){
  if(!obj || obj.length<1){
    callback({"reply":"Unable to comply","error":"Object to send is not defined"});
    return;
  }
  
  var now=new Date().getTime(); // milliseconds since midnight Jan 1, 1970

  // Check that websocket is on-line
  if(ps.connection.readyState !== ps.connection.OPEN){
    // Set time for first try
    if(!obj._deferTime) obj._deferTime=now;
    if(obj._deferTime+1000*ps.timeOut > now){
      // Defer send about half a second 
      setTimeout(function(){ps.serviceRequest(obj,callback)},500);
      return;
    }else{   
      callback({"reply":"Unable to comply","error":"your terminal is off-line"});
      return;
    }
  }
  if(obj._deferTime) delete obj._deferTime;

  // Use unique id to match reply with callback function
  obj.cmdId = ps.nextID++;

  // Send request
  obj.token=ps.token;
  ps.connection.send(JSON.stringify(obj));
console.info("Sending command: ",obj);

  // Add to callback to response queue
  if(obj.cmdId){
    ps.callbackQueue[obj.cmdId]=callback;
  } 
}

/*============================================================================*\
  Send command to command handler via smart core services

  If event are given, the command will be executed on the event.
\*============================================================================*/
ps.cmd=function(cmd,callback,event){
  // Check that its a valid command
  if(!cmd){
    callback({"reply":"Unable to comply","error":"There is no command"});
    return;
  }
  var obj={'command':cmd};
  if(event) obj.event=event;
  ps.serviceRequest(obj,callback);
}

// Watch for update request.
ps.watch=function(event,callback){
  ps.serviceRequest({'subscribe':event},callback);
}

ps.serverInfo=function(group,callback){
  ps.serviceRequest({'serverinfo':group},callback);
}

// Make connection
ps.connect();

/*============================================================================*\
  Helper function for HTML actions
\*============================================================================*/

/*============================================================================*\
  Send command
  
  For a few basic replyes, play a sound 
  Sound schema:
    ok
    failed (Command does not compute)
    denied
    Ready (to recieve)
    Alerts: red yellow green blue
    Working
    Message
    wakeup alarm

\*============================================================================*/

function cmd(cmd){
  ps.cmd(cmd,function(response){
    console.log("Command response: ", response);

    if(!response.reply) return;
  
    if(['ready','failed','working','denied','ok'].includes(response.reply)){
      var snd = new Audio('/theme/'+ response.reply + ".ogg");
      snd.play();      
    }else{
      // Speak it
    }
  });
}

/*============================================================================*\
  set context
  
  function to set/change context for this page
  The page has to reload to complete the update.
  The context variable are passed to the new page with GET method
  That is done to get rid of the annoying "do you really want to reload" popup.
  Can we use local store instead?
\*============================================================================*/
function set_context(new_context){
  window.location=window.location.pathname+'?context='+encodeURIComponent(new_context);
}

