<?php

$ch = curl_init();
 
echo('http://localhost:8080/logWattage.php?'.$_SERVER['QUERY_STRING']);
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8080/logWattage.php?'.$_SERVER['QUERY_STRING']);
curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 1);
 
curl_exec($ch);
curl_close($ch);

?>
