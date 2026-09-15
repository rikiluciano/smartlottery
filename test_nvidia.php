<?php
$ch = curl_init('https://integrate.api.nvidia.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer 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',
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'model' => 'nvidia/nemotron-3-ultra-550b-a55b',
    'messages' => [['role' => 'user', 'content' => 'hi']],
    'max_tokens' => 10
]));
echo curl_exec($ch);
if(curl_errno($ch)){ echo ' Error: ' . curl_error($ch); }
curl_close($ch);
?>
