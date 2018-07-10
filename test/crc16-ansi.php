<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8" />
<title>Test page</title>
<link rel="shortcut icon" href="/favicon.ico" >
<link rel="icon" href="/favicon_big.ico" type="image/ico" >
<link rel="stylesheet" type="text/css" href="/theme/theme.css" />
<script type="text/javascript" src="/page_services.js" /></script>

<body>
<h1>Test</h1>
<pre>
<?php
/*
Modbus RTU frame format

+-+---+---+-----+---------+----------+--+----.....---+---+---+---------------+
| Silence 3 1⁄2 | address | Function | Data + length | CRC16 | Silence 3 1⁄2 |
+-+---+---+-----+---------+----------+--+----.....---+---+---+---------------+

CRC-16-ANSI
Example of frame in hexadecimal: 01 04 02 FF FF B8 80 (CRC-16-ANSI calculation from 01 to FF gives 80B8, which is transmitted least significant byte first).
*/

function crc16_ansi($data) {
  $crc = 0xFFFF;
  for ($i = 0; $i < strlen($data); $i++){
    $crc ^= ord($data[$i]);
    for($j = 8; $j--;)
      $crc = $crc & 0x0001 ? $crc = ($crc >> 1) ^ 0xA001 : $crc >> 1;
  }
  return $crc & 0xffff;
}



/*==========================================================================*\
  Read from serial device

  Expect to recieve a continuos stream of charakters and return on first silence
  of more than 3 1/2 byte. (at 9600bps = 3.2 ms )


  Return false on timeout
\*==========================================================================*/
function read_modbus($fp){
  $data = "";
  $elapsed_time = 0;
  stream_set_blocking($fp,0);

  do{
    $c  = fgetc($fp);

    // Wait for silence marker
    if($c === false){
      if( !$elapsed_time )
        $elapsed_time = microtime(true);

      // End reading after 3 1/2 bytes
      elseif($elapsed_time + 0.002917 > microtime(true))
        break;

      // Wait for a byte to arive
      usleep(833);

    }else{
      $elapsed_time = 0;
      $data .= $c;
    }
  }while(true);

  stream_set_blocking($fp,1);
  return $data;
}


$name = "/dev/ttyUSB0";
$data = "";
$address = 1;
$function = 1;
$data = "\x00\x01\x00\x01";
//$data = "\x02\xFF\xFF";
//$data = "123456789Dette er en meget lngere tekst asdf asfd";
//$data = "\x01\x01";

$frame  = chr($address & 0xff);
$frame .= chr($function & 0xff);
$frame .= chr(strlen($data) & 0xff) . substr($data,0,255);
$crc = crc16_ansi($frame);
$frame .= chr($crc & 0x00ff) . chr($crc >>8);

//echo "\n" . dechex(crc16_ansi($data)) . "H";

// Connect to unit
// echo "Open $name<br>";
$fp=@fopen($name,"c+");
if(!$fp){
  $error = error_get_last();
  echo "Open failed<br>", $error['message'];
  $error=error_get_last();
  //return array('error'=>"Server unable to access device: ".$error[message]);
}

// Start with 3 1/2 byte of silence
usleep(2917);
echo "\nWriting: ";
for($i = 0; $i < strlen($frame); $i++)
  echo "\x" . substr("00". dechex(ord($frame[$i])),-2);

if(fwrite($fp,$frame) === false){
  $error=error_get_last();
  echo "Write failed<br>", $error['message'];
  //return array('error'=>"Server unable to access device: ".$error[message]);
}

$response = read_modbus($fp);
echo "\nresponse: ";
for($i = 0; $i < strlen($response); $i++)
  echo "\x" . substr("00". dechex(ord($response[$i])),-2);


?>
</pre>
</div>
</body>
</html>
