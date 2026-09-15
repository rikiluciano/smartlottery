<?php
$ch = curl_init('http://numerosrd.42web.io/lottery/api_chat_quiniela.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['target' => '80', 'es_digito' => false]));
echo curl_exec($ch);
?>
