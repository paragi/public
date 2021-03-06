<!DOCTYPE HTML>
<html>
<head>
<title>Presentation library demo page</title>
<script type="text/JavaScript" src="/plib.js"></script>
<style>
body {
margin:20;
font:16px sans-serif;
color:#fd9;
background-color:#502;
text-align:center;
}
div{
  color:#fd9;
  background-color:#333;
  font:26px sans-serif;
  text-align:center;
  vertical-align: top;
  border-width:2px;
  border-style: solid;
  border-color:#999;
  display:inline-block;
}
.button{
  border-radius:15px; 
  border-width:2px;
  border-style: solid; 
  border-color:#fd9;
  background-color:#290;
  padding:10px;
  vertical-align:middle;
  text-align:center;
  cursor:pointer;
}
</style>
</head>

<body>
<h1>Presentation library demo
<span class="button" onclick="update(this);">Run</span>
</h1>
<!-- Gauges -->
<canvas id="g1"  width="200" height="200" ></canvas>
<canvas id="g2"  width="200" height="200" ></canvas>
<canvas id="g3"  width="200" height="200" ></canvas>
<canvas id="g4"  width="200" height="200" ></canvas>

<br>
<!-- Bars -->
<canvas id="b1"  width="100" height="200" ></canvas>
<canvas id="b2"  width="100" height="200" ></canvas>
<canvas id="b3"  width="100" height="200" ></canvas>
<canvas id="b4"  width="100" height="200" ></canvas>
<canvas id="b5"  width="100" height="200" ></canvas>
<canvas id="b6"  width="100" height="200" ></canvas>
<canvas id="b7"  width="100" height="200" ></canvas>
<canvas id="b8"  width="100" height="200" ></canvas>

<br>

<!-- Graphs -->
<canvas id="c1"  width="400" height="200" style="background-color:#246"></canvas>
<canvas id="c2"  width="400" height="200" ></canvas>
<br>
<!--
<canvas id="c3"  width="400" height="200" ></canvas>
<canvas id="c4"  width="400" height="200" ></canvas>
-->
<!-- Scalar -->
<div id="s1" style="width:100px; height:100px;">s1</div>
<canvas id="s2" style="width:100px; height:100px; border-width:2px;border-style: solid;background-color:#246;"></canvas>
<div id="s3" style="width:100px; height:100px;">s3</div>

<script type="text/JavaScript" charset="utf-8">

// Initialize gauges
present("g1",10,"gauge color=tempout low=-50 high=50 prefix=\u00B0C");
present("g2",60,"gauge color=byr low=50 high=100  show_value prefix=\u00B0C");
present("g3",150,"gauge color=process low=100 high=600 prefix=kPa");
present("g4",150,"gauge color=rgr low=0 high=500 prefix=\u007EV");

// Initialize bars
present("b1",21,"bar color=tempin low=15 high=25 prefix=\u00B0C");
present("b2",90,"bar color=bgr low=0 high=200 prefix=cm");
present("b3",90,"bar color=filter prefix=%");
present("b4",15,"bar color=tempwater low=0 high=24 show_value prefix=\u00B0C");
present("b5",78,"bar color=rgr");
present("b6",65,"bar color=byr");
present("b7",87,"bar color=humidity");
present("b8",3500,"bar color=light low=800 high=6000 prefix=K");

// Initialize graphs
var val=[20,19,20,19,20,21,20,21,21,20,19,17,19,21,21,22,22,23,22,22,20,20,19,20,21,20,21,21,20,19,17,19,21,21,22,22,23,22,22,21]
present("c1",val,"graph color=tempin low=15 high=25 prefix=\u00B0C");

var val={};
val.y=[50,60,80,85,105,95,105,120,156,175,170]
val.x=[-100,-50,0,50,100,150,200,250,300,350,400]
present("c2",val,"graph color=water low=0 high=250 prefix=cm bgcolor=water bgcolorlow=10");

//present("c3",100,"waterlevel","graph color=filter");
// Initialize scalars
present("s1",35,"high=1.0e+10 prefix=J");
present("s2",35,"text prefix=A");
present("s3",35,"");


// Animate data streams
var ci=0,timer;
function update(button){
  var v,a,nv,offset=0;

  // Change button apperince
  if(button)
    if(button.innerHTML=='Run'){
      button.innerHTML='Stop';
      button.style.backgroundColor="#920";
    }else{ 
      clearTimeout(timer); 
      button.innerHTML='Run';
      button.style.backgroundColor="#290";
      return;
    }

  // Loop through all presentations
  for( var i in present._elm){
    if(typeof(present._elm[i].val) != 'undefined'){

      // Find value to change
      if(present._elm[i].val instanceof Array){
        a=present._elm[i].val;
        v=present._elm[i].val[present._elm[i].val.length-1];
      }else if(present._elm[i].val.y instanceof Array){
        a=present._elm[i].val.y
        v=present._elm[i].val.y[present._elm[i].val.y.length-1];
      }else{
        a=null;
        v=present._elm[i].val;
      }

      // Make new value
      nv=present._elm[i].low+((Math.sin(ci+offset*5)*Math.sin(ci*4)+1)/2)*(present._elm[i].high-present._elm[i].low);

      // Assign new value
      if(a instanceof Array){
        a.push(nv);
        // Remove oldest value
        a.shift();
      }else{
        present._elm[i].val=nv;
      }

      // Show new value
      present(i,present._elm[i].val);
    }
    offset++;
  }
  // Prepare next event
  ci+=0.02;

  // Sleep a little to make the brops.r breath
  timer=setTimeout(function(){update()}, 30);
}



</script>
</body>
</html>
