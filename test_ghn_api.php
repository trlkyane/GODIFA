<?php
/**
 * Test GHN API - Provinces
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TEST GHN API - PROVINCES ===\n\n";

require_once __DIR__ . '/model/mGHN.php';
require_once __DIR__ . '/config/ghn.php';

echo "1. Kiểm tra config GHN:\n";
$config = include __DIR__ . '/config/ghn.php';
echo "   - API URL: {$config['api_url']}\n";
echo "   - Token: " . substr($config['token'], 0, 10) . "...\n";
echo "   - Shop ID: {$config['shop_id']}\n\n";

echo "2. Test kết nối GHN API:\n";
$ghn = new GHN();
$result = $ghn->getProvinces();

echo "   - Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";

if ($result['success']) {
    echo "   - Số tỉnh/thành: " . count($result['data']) . "\n";
    echo "   - 5 tỉnh đầu tiên:\n";
    foreach (array_slice($result['data'], 0, 5) as $province) {
        echo "     • [{$province['ProvinceID']}] {$province['ProvinceName']}\n";
    }
} else {
    echo "   - Error: {$result['error']}\n";
    if (isset($result['response'])) {
        echo "   - Response: " . json_encode($result['response'], JSON_UNESCAPED_UNICODE) . "\n";
    }
}

echo "\n3. Test CURL trực tiếp đến GHN:\n";
$ch = curl_init('https://dev-online-gateway.ghn.vn/shiip/public-api/master-data/province');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Token: ' . $config['token']
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "   - HTTP Code: $httpCode\n";
if ($error) {
    echo "   - CURL Error: $error\n";
} else {
    $json = json_decode($response, true);
    echo "   - GHN Response Code: " . ($json['code'] ?? 'N/A') . "\n";
    echo "   - Message: " . ($json['message'] ?? 'N/A') . "\n";
    if (isset($json['data'])) {
        echo "   - Data count: " . count($json['data']) . "\n";
    }
}
