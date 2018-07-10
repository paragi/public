<?php
/*============================================================================*\

            Deprecated 


Present a number in a humanly easy to read way by using prefixes for very large and 
small numbers and by limiting numbers to a reasonable amount of significant digits.

Type can be decimal (default) or byte
Byte uses a base 1024 for bytes (=1 KB) 

Precision are limited only by sprintfs capabilities pt. about 54 significant digits.
Setting it higher has no effect. It only works ifthe number parameter is given a string.
IF its a numeric, its precision are limited to PHP_INT_SIZE (10-20 digits)
On some systems its pointless to set i higher then integer precision because sprintf makes
a mess of some undefined decimals if precision is higher than the number itself

NLS is supported if configuret in php.ini via sprintf for numbers in range of 10^-24 to 10^27

* Verbal if set, attempts to formulate the number in an idiomatic way

* next Precision in % 
* Present time/dates 
* NLS all the way
* Offset exponent eg.  15356 P joule = 15.3 E joule
* (vægt? temp? rumfang?)

\*============================================================================*/
define("_MAX_PRECISION",PHP_INT_SIZE*2.5); // RPI = 10

function present_number($number,$type=null,$verbal=false,$precision=3){
  // Default base for order of magnitude
  $base=1000;

  // Prefixes for very large and small numbers
  $prefix_small=array(' ','m','&#181;','n','p','f','a','z','y');
  $prefix_big=array(' ','K','M','G','T','P','E','Z','Y','H');

  // Validate boundaries
  if($precision=="max") $precision=_MAX_PRECISION;
  $precision=(int)$precision;
  if($precision<1) $precision=3;
  
  if($type=="byte"){
    // Bytes are traditionally calculated in chunks 10 bit adresses
    $base=1024;
    // There are only 8 bits of fraction of a byte.
    if(abs($number)<0.125) 
      return "0";
    // Fix bits as fraction later

    if(abs($number)<1) 
      return "0";
  }

  // Treat number as a string, to avoid reducing accuracy
  $e=sprintf("%.".($precision) ."E",$number);

  // Remove sign
  if($e[0]=='-'){
    $sign='-';    
    $e[0]=' ';
  }

  // Extract exponent 
  $epos=strpos($e,"E")+1;
  $exp=(float)substr($e,strpos($e,"E")+1); 

  // Extract coefficient
  $coef=trim(substr($e,0,$epos-1));

  // Find order of magnitude 
  if($exp<0)
    // Avoid to manny digits after decimal point in small numbers
    $oom=(int)-log($coef."E".abs($exp-1),$base);
  else
    $oom=(int)log($coef."E$exp",$base);

  // get prefix
  if($exp<0)
    $prefix=$prefix_small[abs($oom)];
  elseif($exp>0)
    $prefix=$prefix_big[$oom];

  


  // If order of magnitude are out of range; use exponential presentation
  if($exp!=0 and !$prefix){
    // assamble and remove extra digit from coefficient
    $value="$sign".substr($coef,0,-1)." 10^$exp"; 

  }else{
    // Ajust coefficient to order of magnitude  
    $coef2=sprintf("%.".($precision-1)."E",(($coef."E".$exp)/pow($base,$oom)) );
 
    // Match coaficielt to order of magnitude and maintain at leest same precision
    $float=rtrim(sprintf("%.".($precision+3)."f",$coef2),"0.");

    // Assamble number
    $value="$sign$float $prefix";
  }

  return $value;
}


/*
$n="2.120238598676466646268534675467";
for($i=-36;$i<36;$i++){
  //echo ($n*pow(10,$i)) ." = ". present_number($n*pow(10,$i),null,null,true) ."<br>";
  echo present_number($n*pow(10,$i)) ."<br>";
}

echo "----------------------- next -----------------------\n";

for($i=-36;$i<36;$i++)
  echo present_number(-$n*pow(10,$i),"byte") ."<br>";
*/
/*============================================================================*\
\*============================================================================*/

/* ================================================================================ +/
Time ago: show time differance in human readable form

* Finish
/+ ================================================================================ */
function time_ago($tm,$rcs = 1) {
   $cur_tm = time(); $dif = $cur_tm-$tm;
   $pds = array('second','minute','hour','day','week','month','year','decade','centurie','melina');
   $lngh = array(1,60,3600,86400,604800,2630880,31570560,315705600,3157056000,31570560000);
   for($v = sizeof($lngh)-1; ($v >= 0)&&(($no = $dif/$lngh[$v])<=1); $v--); if($v < 0) $v = 0; $_tm = $cur_tm-($dif%$lngh[$v]);
   $no = floor($no); if($no <> 1) $pds[$v] .='s'; $x=sprintf("%d %s ",$no,$pds[$v]);
   if(($rcs > 0)&&($v >= 1)&&(($cur_tm-$_tm) > 0)) $x .= time_ago($_tm, --$rcs);
   return $x;
}

/*============================================================================*\
  Temperature

  options:
    v: verbal
    n: Numerical (Default) can be both g and n
    g: Graphical
    d: dail

    i: Indoore (Default)
    o: Outdoor
    w: water
    h<n>: high max
    l<n>: low min
    m<n>: medium

    c: Celsius (Default)
    f: Farenheit
    k: Kelvin

  // Default temp used for off-line presentation

  Value can be an array. That will translate to at graph in g mode,
  min, med, max in d and mid +-n in n mode
\*============================================================================*/
//function present_number($number,$type=null,$verbal=false,$precision=3){
function present_temperature($val=20,$opt="nic"){

  // Add space to make strpos return true if found
  $opt=" ".strtolower($opt);
  $out="";

  if(!$val){
    $off_line=true;
    $cval=20;
  }

  // Convert to Celsius
  if(strpos($opt,"f")){ 
    $cval=($val - 32)*5/9;
    $prefix="&deg;F";
  }elseif(strpos($opt,"k")){
    $cval=$val - 273.15;
    $prefix="&deg;K";
  }else{  
    $cval=$val;
    $prefix="&deg;C";
  }

  // Check that graphical lib are installed
  if(!function_exists("imagecreatefrompng"))
    // Fall back to text mode
    $opt=str_replace('g','n',str_replace('d','n',$opt));
  
  // Graphical bar representation 
  if(strpos($opt,"g")){
  }

  // Graphical dail representation 
  if(strpos($opt,"d")){
  }

  // Numeric representation
  if(strpos($opt,"n")){
    $out.=present_number($val,null,null,2).$prefix;
  }

  // Verbal representation
  if(strpos($opt,"v")){
    $out.=present_number($val,null,null,2)."degrees";
  }

  return $out;
}
?>
