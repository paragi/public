<!DOCTYPE HTML>
<html>
<head> 
<meta charset="utf-8" />
<title>Test page</title>
<link rel="shortcut icon" href="/favicon.ico" >
<link rel="icon" href="/favicon_big.ico" type="image/ico" >
<link rel="stylesheet" type="text/css" href="/theme/theme.css" />
<style>
td:hover{
	background-color:rgba(240,217,136,0.2);
	cursor:pointer;
}
</style>
 </head>
<body>

<?php
  echo "<div class=\"container\" style=\"white-space: pre-wrap; word-wrap: break-word; word-break: break-all;\">\n";
  echo "<h1>Root connection</h1>\n";

if(!function_exists("pg_connect")){
  die("The PostgreSQL package is not installed (apt-get install php5-pgsql)");
}

  // Root connection
  $connection_config="host=localhost port=5432 user=www-data password=thecore  dbname=smartcore  options='--client_encoding=UTF8'";

/*
  $connection_config_encrypted=openssl_encrypt($connection_config,"DES3","No fat",null,"Somspice");
  
  
  echo "Connection string: ",$connection_config."\n\n";
  echo "Encrypted string: ",$connection_config_encrypted."\n\n";

$connection_config_encrypted = "ble6f975Gq/kWFykWIpTOQrMzJC6zxzZTF4NDmiBCHXotoToDdZ9f6QzeruZo0N4/VoFqIzrHjCnN0a1Jqi8tpQ+IVvqIIQ93Qfs12jTXPEewRbUUlEkO8/V/Abn28KPTir3a7vzRTUSi05R0f75TQ==";

  $db=pg_connect(openssl_decrypt($connection_config_encrypted,"DES3","No fat",null,"Somspice"));
*/

  $db=pg_connect($connection_config);
  
  if(!$db)
    echo "Unable to connect to root database";
  else{
    echo "Connection: "
     , pg_connection_status($db) === PGSQL_CONNECTION_OK ? "Ok\n\n" : "Bad\n\n";

    printf("Database in use: %s \n\n",pg_dbname($db));
    printf("server_encoding: %s \n",pg_parameter_status($db,"server_encoding"));
    printf("client_encoding: %s \n",pg_parameter_status($db,"client_encoding"));
    printf("is_superuser: %s \n",pg_parameter_status($db,"is_superuser"));
    printf("session_authorization: %s \n",pg_parameter_status($db,"session_authorization"));
    printf("DateStyle: %s \n",pg_parameter_status($db,"DateStyle"));
    printf("TimeZone: %s \n",pg_parameter_status($db,"TimeZone"));
    printf("Integer_datetimes: %s \n",pg_parameter_status($db,"nteger_datetimes"));

    print_r(pg_version($db));  

  }  
  
  $result = pg_query($db, "SELECT * FROM timer");
  print_r(pg_fetch_all($result));  


?>

</body>
</html>
