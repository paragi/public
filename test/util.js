/*============================================================================*\
  Utilities
\*============================================================================*/
// Extention for RegExp for escaping strings to legal identifiers 
// (without restricted words controle)
if(!RegExp.escape){
  RegExp.escape = function(s){
    return String(s).replace(/[\\^$*+?.()|[\]{}\/]/g, '$_');
  };
}
//
// Hexdump.js
// Matt Mower <self@mattmower.com>
// 08-02-2011
// License: MIT
//
// None of the other JS hex dump libraries I could find
// seemed to work so I cobbled this one together.
//

var Hexdump = {
	
	to_hex: function( number ) {
		var r = number.toString(16);
		if( r.length < 2 ) {
			return "0" + r;
		} else {
			return r;
		}
	},
	
	dump_chunk: function( chunk ) {
		var dumped = "";
		
		for( var i = 0; i < 4; i++ ) {
			if( i < chunk.length ) {
				dumped += Hexdump.to_hex( chunk.charCodeAt( i ) )+" ";
			} 
		}
		
		return dumped;
	},
	
	dump_block: function( block ) {
		var dumped = "";
		
		var chunks = block.match( /.{1,4}/g );
		for( var i = 0; i < 4; i++ ) {
			if( i < chunks.length ) {
				dumped += Hexdump.dump_chunk( chunks[i] );
			}
			dumped += " ";
		}
		
		dumped += "[    " + block.replace( /[\x00-\x1F]/g, "." )+']';
		
		return dumped;
	},
	
	dump: function( s ) {
		var dumped = "";
		
		var blocks = s.match( /.{1,16}/g );
		for( var block in blocks ) {
			dumped += Hexdump.dump_block( blocks[block] ) + "\n<br>";
		}
		
		return dumped;
	}
	
};
/*
function hexDump($data, $newline="\n")
{
  static $from = '';
  static $to = '';
  static $width = 16; # number of bytes per line
  static $pad = '.'; # padding for non-visible characters

  if ($from===''){
    for ($i=0; $i<=0xFF; $i++){
      $from .= chr($i);
      $to .= ($i >= 0x20 && $i <= 0x7E) ? chr($i) : $pad;
    }
  }

  $hex = str_split(bin2hex($data), $width*2);
  $chars = str_split(strtr($data, $from, $to), $width);

  $offset = 0;
  foreach ($hex as $i => $line){
    echo sprintf('%06X',$offset).' : '.implode(' ', str_split($line,2)) . ' [' . $chars[$i] . ']' . $newline;
    $offset += $width;
  }
}
*/

// HTML Var dump objects and scalars. HTMLDump(<tag id>[[,args]])
// NB: Breaks on functions...
function HTMLDump(){
  var args = Array.prototype.slice.call(arguments);
  var elm=document.getElementById(args[0]);
  if(elm){
    for(var i in args){
      if(i==0) continue; // tag id
      if(typeof args[i] === 'object')
        elm.innerHTML+='<pre>'+JSON.stringify(args[i],null, 2).replace(/\\\"/g,"\"")+"</pre></br>";
      else
        elm.innerHTML+=args[i]+"<br>"; 
    }
    elm.scrollTop = elm.scrollHeight;
  }else console.log(args);
}

function HTMLDumpCls(){
  var args = Array.prototype.slice.call(arguments);
  document.getElementById(args[0]).innerHTML='';
  HTMLDump.apply(this, arguments);
}

function HTMLHexDump(){
  var args = Array.prototype.slice.call(arguments);
  var elm=document.getElementById(args[0]);
  if(elm){
    for(var i in args){
      if(i==0) continue; // tag id
      if(typeof args[i] === 'object')
        elm.innerHTML+=Hexdump(JSON.stringify(args[i]).replace(/\\\"/g,"\"")) +"</br>";
      else
        elm.innerHTML+=Hexdump(args[i])+"<br>"; 
    }
    elm.scrollTop = elm.scrollHeight;
  }else console.log(args);
}



