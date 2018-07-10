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

$address = 1;
$function = 1;
$data = "\x01";
//$data = "\x02\xFF\xFF";
//$data = "123456789Dette er en meget lngere tekst asdf asfd";
//$data = "\x01\x01";

$frame = chr($address & 0xff) . chr($function & 0xff) . $data;
$crc = crc16_ansi($frame);
$frame .= chr($crc & 0x00ff) . chr($crc >>8);

for($i = 0; $i < strlen($frame); $i++)
  echo "x" . dechex(ord($frame[$i]));

//echo "\n" . dechex(crc16_ansi($data)) . "H";




?>
</pre>
</div>
</body>
</html>
