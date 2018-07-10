<!DOCTYPE HTML>
<?php
	//require_once ("stdlib.php");
?>

<html>
<head>
<title>Test page</title>
</head>
<body>
<p>

<?php
  // Trace route
  define ("SOL_IP", 0);
  define ("IP_TTL", 2);    // On OSX, use '4' instead of '2'.

  $dest_url = "www.google.dk";   // Fill in your own URL here, or use $argv[1] to fetch from commandline.
  $maximum_hops = 100;
  $port = 33434;  // Standard port that traceroute programs use. Could be anything actually.

  // Get IP from URL
  $dest_addr = gethostbyname ($dest_url);
  print "Tracerouting to destination: $dest_url/$dest_addr\n<br>";

  $ttl = 1;
  while ($ttl < $maximum_hops) {
      // Create ICMP and UDP sockets
     //$recv_socket = socket_create (AF_INET, SOCK_RAW, getprotobyname ('icmp'));
//     $recv_socket = socket_create (AF_INET, SOCK_STREAM, getprotobyname ('tcp'));
     $recv_socket = socket_create (AF_INET, SOCK_DGRAM, getprotobyname ('udp'));
      if ($recv_socket === false) {
        $errorcode = socket_last_error();
        $errormsg = socket_strerror($errorcode);
        die("Socket_create error: [$errorcode] $errormsg");
      }
      $send_socket = socket_create (AF_INET, SOCK_DGRAM, getprotobyname ('udp'));
      if ($send_socket === false) {
        $errorcode = socket_last_error();
        $errormsg = socket_strerror($errorcode);
        die("Socket_create2 error: [$errorcode] $errormsg");
      }

      // Set TTL to current lifetime
      $rc=socket_set_option ($send_socket, SOL_IP, IP_TTL, $ttl);
      if ($rc === false) {
        $errorcode = socket_last_error();
        $errormsg = socket_strerror($errorcode);
        die("Socket_set_option error: [$errorcode] $errormsg");
      }
      // Bind receiving ICMP socket to default IP (no port needed since it's ICMP)
      $rc=socket_bind ($recv_socket, 0, 0);
      if ($rc === false) {
        $errorcode = socket_last_error();
        $errormsg = socket_strerror($errorcode);
        die("Socket_bind error: [$errorcode] $errormsg");
      }
      // Save the current time for roundtrip calculation
      $t1 = microtime (true);

      // Send a zero sized UDP packet towards the destination
      $rc=socket_sendto ($send_socket, "", 0, 0, $dest_addr, $port);
      if ($rc === false) {
        $errorcode = socket_last_error();
        $errormsg = socket_strerror($errorcode);
        die("Socket_sendto error: [$errorcode] $errormsg");
      }
      // Wait for an event to occur on the socket or timeout after 5 seconds. This will take care of the
      // hanging when no data is received (packet is dropped silently for example)
      $r = array ($recv_socket);
      $w = $e = array ();
      $rc=socket_select ($r, $w, $e, 5, 0);
      if ($rc === false) {
        $errorcode = socket_last_error();
        $errormsg = socket_strerror($errorcode);
        die("Socket_select error: [$errorcode] $errormsg");
      }

      // Nothing to read, which means a timeout has occurred.
      if (count ($r)) {
          // Receive data from socket (and fetch destination address from where this data was found)
          $rc=socket_recvfrom ($recv_socket, $buf, 512, 0, $recv_addr, $recv_port);
          if ($rc === false) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            die("Socket_recform error: [$errorcode] $errormsg");
          }

          // Calculate the roundtrip time
          $roundtrip_time = (microtime(true) - $t1) * 1000;

          // No decent address found, display a * instead
          if (empty ($recv_addr)) {
              $recv_addr = "*";
              $recv_name = "*";
          } else {
              // Otherwise, fetch the hostname for the address found
              $recv_name = gethostbyaddr ($recv_addr);
          }

          // Print statistics
          printf ("%3d   %-15s  %.3f ms  %s<br>\n", $ttl, $recv_addr,  $roundtrip_time, $recv_name);
      } else {
          // A timeout has occurred, display a timeout
          printf ("%3d   (timeout)<br>\n", $ttl);
      }

      // Close sockets
      socket_close ($recv_socket);
      socket_close ($send_socket);
      // Increase TTL so we can fetch the next hop
      $ttl++;
      // When we have hit our destination, stop the traceroute
      if ($recv_addr == $dest_addr) break;
  }










/*
$user = "daemon";
$script_name = "uid"; //the name of this script

/////////////////////////////////////////////
//try creating a socket as a user other than root
echo "\n__________________________________________\n";
echo "Trying to start a socket as user $user\n";
$uid_name = posix_getpwnam($user);
$uid_name = $uid_name['uid'];

if(posix_seteuid($uid_name))
{
        echo "SUCCESS: You are now $user!\n";
        if($socket = @socket_create(AF_INET, SOCK_RAW, 1))
        {
                echo "SUCCESS: You are NOT root and created a socket! This should not happen!\n";
        } else {
                echo "ERROR: socket_create() failed because you're not root!\n";
        }
        $show_process = shell_exec("ps aux | grep -v grep | grep $script_name");
        echo "Current process stats::-->\t $show_process";
} else {
        exit("ERROR: seteuid($uid_name) failed!\n");
}

/////////////////////////////////////////////
//no try creating a socket as root
echo "\n__________________________________________\n";
echo "Trying to start a socket as user 'root'\n";
if(posix_seteuid(0))
{
        echo "SUCCESS: You are now root!\n";
        $show_process = shell_exec("ps aux | grep -v grep | grep $script_name");
        echo "Current process stats::-->\t $show_process";
        if($socket = @socket_create(AF_INET, SOCK_RAW, 1))
        {
                echo "SUCCESS: You created a socket as root and now should seteuid() to another user\n";
                /////////////////////////////////////////
                //now modify the socket as another user
                echo "\n__________________________________________\n";
                echo "Switching to user $user\n";
                if(posix_seteuid($uid_name))
                {
                        echo "SUCCESS: You are now $user!\n";
                        if(socket_bind($socket, 0, 8000))
                        {
                                echo "SUCCESS: socket_bind() worked as $user!\n";
                        } else {
                                echo "ERROR: Must be root to user socket_bind()\n";
                        }
                        $show_process = shell_exec("ps aux | grep -v grep | grep $script_name");
                        echo "Current process stats::-->\t $show_process";
                        socket_close($socket); //hard to error check but it does close as this user
                        echo "SUCCESS: You closed the socket as user $user!\n";
                } else {
                        echo "ERROR: seteuid($uid_name) failed while socket was open!\n";
                }

        } else {
                echo "ERROR: Socket failed for some reason!\n";
        }
} else {
        exit("ERROR: Changing to root failed!\n");
}


*/

/*


*/
?>
</p>
</body>
</html>
