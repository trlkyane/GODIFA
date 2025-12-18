<?php
echo "=== TEST PROVINCES API ===\n\n";

$apiUrl = 'https://provinces.open-api.vn/api/?depth=1';

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Error: " . ($error ?: 'None') . "\n";
echo "Response length: " . strlen($response) . " bytes\n\n";

if ($response) {
    echo "First 500 chars:\n";
    echo substr($response, 0, 500) . "\n\n";
    
    $json = json_decode($response, true);
    if ($json) {
        echo "Parsed successfully!\n";
        echo "Type: " . gettype($json) . "\n";
        echo "Count: " . count($json) . "\n";
        echo "First 3 items:\n";
        print_r(array_slice($json, 0, 3));
    } else {
        echo "JSON parse error: " . json_last_error_msg() . "\n";
    }
}
