<?php
/*============================================================================*\
  net_trace.php   (c) paragi, Simon Rigét 2016.  License MIT
  
  Extract routing information, based on IP address
  
  array net_trace(string remote IP);
  
  return:
  
  error: null or an error message
  result: array of hops:
    ip:       ip address
    location: address retrieved from whois
     
  net_trace rely on unix shell commands ifconfig, traceroute, whois and which  
  
\*============================================================================*/

function net_trace($remote){
  //echo "<br>testing ip $remote<br>";

  $ressponse = ["error"=>"","result",[]];
  do{
    if(!$remote) $remote = $_SERVER['REMOTE_ADDR'];
    
    if(!filter_var($remote, FILTER_VALIDATE_IP)){
      $test = gethostbyname($remote); 
      if(filter_var($test, FILTER_VALIDATE_IP)) $remote = $test;
    }

    if(!filter_var($remote, FILTER_VALIDATE_IP)){
      $response['error'] = "Unable to interpret Client IP address: ".$remote;
      break;
    }

    if($remote == '127.0.0.1'){
       $response['result'][0]['ip'] = $remote;
       $response['result'][0]['location'] = 'internal';
       break;
    }
    
    // Is ifconfig installed
    exec("which ifconfig",$line,$rc);
    if($rc){
      $response['error'] = "ifconfig shell command is not available";
      break;
    }

    // Find network interfaces
    exec("ifconfig ",$ifconfig_out,$rc);
    if($rc){
      $response['error'] = "ifconfig failed with code: $rc";
      break;
    }

    foreach($ifconfig_out as $i=>$l)
      if($l && $l[0] > '/'){
        $name = strstr($l," ",true);
        $ip = strtok(strstr($ifconfig_out[$i+1]," "),"inet addr:");
        if(!filter_var($ip, FILTER_VALIDATE_IP)) continue;

        strtok(" "); // Broardcast address
        
        $mask = substr(strstr(strtok(" "),"Mask:"),5);
        if(!$mask) continue;
        $nif[$name] = ["ip" => $ip, "mask"=>$mask];
      }

    // Is client local?
    foreach($nif as $n)
      if((ip2long($remote) & ip2long($n['mask'])) 
          == (ip2long($n['ip']) & ip2long($n['mask']))){
        $response['result'][0]['ip'] = $remote;
        $response['result'][0]['location'] = 'local network';
        break 2;
      }

    // Is traceroute installed
    exec("which traceroute",$line,$rc);
    if($rc){
      $response['error'] = "traceroute shell command is not available";
      break;
    }

    // Is whois installed
    $whois=true;
    exec("which whois",$line,$rc);
    if($rc) $whois=false;
   
    // Trace route to client
    exec("traceroute -w 0.7 -n ".$remote,$route,$rc);
    if($rc){
      $response['error'] = "Traceroutre failed with code: $rc";
      break;
    }
    
    // Trace route
    foreach($route as $key=>$line){
      if($key<1) continue;
      if(strpos($line,"*") > -1) break;
      $response['result'][$key]['ip']=explode("  ",$line)[1];

      if(!filter_var($response['result'][$key]['ip'], FILTER_VALIDATE_IP)){
        $response['result'][$key]['location'] = "Invalid IP";
        continue;
      }  

      // Is it local?
      foreach($nif as $n)
        if((ip2long($response['result'][$key]['ip']) & ip2long($n['mask'])) 
          == (ip2long($n['ip']) & ip2long($n['mask']))){
          $response['result'][$key]['location'] =  "local network";
          break;
        }

      // Find owner
      if($whois && empty($response['result'][$key]['location'])){
        exec("whois ".$response['result'][$key]['ip'],$text,$rc);
        if($rc){
          $response['result'][$key]['location'] = "Whois failed with code: $rc";
          continue;  
        }
        $response['result'][$key]['location']="";
        foreach($text as $val){
          if(stripos($val,"country") === 0)
            $response['result'][$key]['location'] = trim(substr($val,8));
          if(stripos($val,"address") === 0)
            $response['result'][$key]['location'] .= ", ". trim(substr($val,8));
        }
      }
    }

    //$response['result']['hops'] = count($response['result']);

  }while(false);
//  echo "<pre>".print_r($response,true)."</pre>";
  return $response;
}
?>
