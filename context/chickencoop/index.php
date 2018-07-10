<?php
/*============================================================================*\
  Display page
\*============================================================================*/
?>
<div class="tile" style="text-align:center">Outdoor<br>
<canvas id="/chickencoop/tempout"  zindex="0"></canvas>
</div>

<div class="tile" style="text-align:center">indoor<br>
<canvas id="/chickencoop/tempin" zindex="0"></canvas>
</div>

<div class="tile" style="text-align:center">Water tank<br>
<canvas id="/chickencoop/watertank" zindex="0"></canvas>
</div>

<script type="text/JavaScript" src="plib.js"></script>

<script>
// Initialize presentations
present("/chickencoop/tempout",30,"bar color=tempout low=-30 high=40 sl=20 sh=90 prefix=\u00B0C precission=3 show_value colorlow=20");
present("/chickencoop/tempin",30,"bar color=tempin low=15 high=30 prefix=\u00B0C precission=3 show_value");
present("/chickencoop/watertank",30,"bar color=water low=0 high=30 prefix=cm precission=3 show_value colorhigh=80");

// Add callback funtion for value updates

ps.on("/chickencoop/tempout",function(res){present(res.event,res.state);});
ps.on("/chickencoop/tempin",function(res){present(res.event,res.state);});
ps.on("/chickencoop/watertank",function(res){present(res.event,res.state);});

</script>


