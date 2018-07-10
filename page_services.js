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


  a few basic replyes, play a sound 
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

// Extention for RegExp for escaping strings to legal identifiers 
// (without restricted words controle)
if(!RegExp.escape){
  RegExp.escape = function(s){
    return String(s).replace(/[\\^$*+?.()|[\]{}\/]/g, '$_');
  };
}

// Make connection
document.addEventListener("DOMContentLoaded", function(event){
  ps.connect();
});

ps = {
   connection:    {}    // Connection
  ,callbackQueue: {}    // callback queue function pointers
  ,nextID:        1     // Unique index for callbackQueue  ,,,,,,,,,,,for paring comands with responses
  ,receiver:      {}    // receiver function for event message
  ,token:         []    // Session token. Must reload if out of alignment
  ,tokenRetry:    0     // Counter for retries
  ,timeOut:       5     // Send timeout; wait time for websocket to connect
  ,monitor:       ''    // function to call on event 
}


/*============================================================================*\
  Connect to smart core services via websocket
\*============================================================================*/
ps.connect=function(){
  // make a persistant connection to websocket server
  ps.connection = new WebSocket('ws://'+window.document.location.host+'/services');
  
  // Open websocket handler
  ps.connection.onopen = function () {
    // Mark page as on-line
    document.body.classList.remove('body-off-line');
    ps.EventOccured('open');
  };

  // Close websocket" handler 
  ps.connection.onclose = function (code, message) {
//alert("ws close" + JSON.stringify(code,null,2));  
    // Mark page as off-line
    document.body.classList.add('body-off-line');
    // Reconnect (after Connection timeout + this timeout)
    setTimeout(ps.connect,10000);
    ps.EventOccured('close');
  };
 
  // Mesages websockets handler
  ps.connection.onmessage = function (event) {
    //console.log("Message received data: ",typeof event.data,event.data);
    var response=JSON.parse(event.data);
console.log("Message received: ",response);

    ps.token.push(response.token);
    
    // Reply to a recently send command if there is a callback
    if(response.cmdId && typeof ps.callbackQueue[response.cmdId] === 'function'){
 console.log("queued callback identified");    
      ps.callbackQueue[response.cmdId](response);  
      if(response.reply!="working") delete ps.callbackQueue[response.cmdId];  
     
      if( response.html && (obj = document.getElementById(response.event))) {
        obj.outerHTML = response.html;
      }
    } 
    
    if(response.event){
      // Mandatory event: alert
      var alert =
        ["alert_mute","alert_off","red_alert","yellow_alert","green_alert","blue_alert"].indexOf(response.event);

      if(alert >= 0){
        // Clear any visual alert state
        if(alert > 0)
          document.body.classList.remove('red_alert','yellow_alert','blue_alert','green_alert' );

        // turn sound off    
        if(typeof sound !== 'undefined' && typeof sound.pause === 'function')
          sound.pause();
    
        // Set new alert        
        if(alert > 1){
          document.body.classList.add(response.event);
      
          // Sound alerts
          sound = new Audio(
            '/theme/' + response.event.replace("_", "-") + '.ogg');
          sound.loop = true;
          sound.play();
          
        }
      // Mandatory event: Reload page
      }else if(response.event=='reload_page') {
        window.location.reload();

      // Mandatory event: Change context
      }else if(response.event=='change_context' && response.context) {
        if(response.context == 'back' && typeof back === 'function')
          window.history.back();
        else
          window.location = 
            window.location.pathname + '?context=' 
            + encodeURIComponent(response.context);
        
      // Event function callback
      }else if( response.html && (obj = document.getElementById(response.event))) {
        obj.outerHTML = response.html;

      }
      
      ps.EventOccured(response.event,response);
    }    
  };
}

/*============================================================================*\
  Service request
  
  Send request to smart core services
  
  ps.services (service, func, data, callback)
  or 
  ps.services (request, callback)
  
  callback = function(response){ ... }
  
\*============================================================================*/
ps.services=function(){
  do{
    response = {"reply":"failed"};

    if(typeof arguments[0] !== "object" && typeof arguments[0] !== "string"){
      response.error = "Please specify a service or a request";
      break;
    }

    if(typeof arguments[0] !== "object" && typeof arguments[1] !== "string"){
      response.error = "Please specify a function"
      break;
    }
      
    if(typeof arguments[0] === "object"){
      var request = arguments[0];
      if(typeof arguments[1] === 'function') 
        var callback = arguments[1];

    }else{
      var request = {service:arguments[0], func:arguments[1], data:arguments[2]};
      if(typeof arguments[3] === 'function') 
        var callback = arguments[3];
    }
    
    var now=new Date().getTime(); // milliseconds since midnight Jan 1, 1970

    // Wait for websocket to be on-line
    if(ps.connection.readyState !== ps.connection.OPEN){
      request.retry++;
      setTimeout(function(){ps.services(request,callback)}
                ,50 * (request.reetry + 10)
      );
      document.body.classList.add('body-off-line');
      break;
    }
    request.retry = 0;

    // Wait for a request token from server
    request.token=ps.token.pop();
    if(!request.token){
      request.retry++;
      setTimeout(function(){ps.services(request,callback)}
        ,50 * (request.reetry + 10)
      );
      document.body.classList.add('body-off-line');
      break;
    }
    request.retry = 0;
    document.body.classList.remove('body-off-line');

    // Use unique id to match reply with callback function
    request.cmdId = ps.nextID++;
    ps.connection.send(JSON.stringify(request));
  console.info("Sending command: ",request);

    // Add to callback to response queue
    if(request.cmdId && callback)
      ps.callbackQueue[request.cmdId]=callback;
    
  }while(false);  
  
  if(response.error && callback) 
    callback(response);
}

/*============================================================================*\
  Event handeling

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
  Helper function for HTML actions
\*============================================================================*/

/*============================================================================*\
  Send command
  
 

\*============================================================================*/
/*============================================================================*\
  Shorthand functions to page services

  command:
  cmd( string <command> [, function <callback> [, string event]])
  
  event subscribe:
  watch( string <event> [,callback]);
  
  
  Send command to command handler via smart core services

  If event are given, the command will be executed on the event.
\*============================================================================*/
ps.cmd=function(cmd,callback,event){
  if(!cmd)
    return callback({"reply":"Unable to comply","error":"There is no command"});

  ps.services({service:'command',func:cmd, event:event},callback);
}

// Watch for update request.
ps.watch=function(event,callback){
  ps.services({service:"event", func:"subscribe",data:event},callback);
}

ps.serverInfo=function(group,callback){
  ps.services({service:"serverinfo", func:group},callback);
}

/*============================================================================*\
  Helper function for HTML actions
\*============================================================================*/
/*============================================================================*\
  set context
  
  function to set/change context for this page
  
  set_context(string new_context)
  
  new_context: either a full context path, beginning with "/" or a path relative
  to the current path.
   
  The page has to reload to complete the update.
  The context variable are passed to the new page with GET method
  That is done to get rid of the annoying "do you really want to reload" popup.
  Can we use local store instead?
  
  
\*============================================================================*/
function set_context(new_context){
  if(new_context.charAt(new_context.kength-1) != '/')
    new_context += '/';
  if(new_context.charAt(0) == '/')
    window.location = window.location.pathname+'?context='
      +encodeURIComponent(new_context);
  else  
    window.location = window.location.pathname
      + window.location.search+encodeURIComponent(new_context);
}

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

