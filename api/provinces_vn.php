<?php
/**
 * Alternative Province API - Vietnam Provinces
 * Sử dụng API miễn phí của Việt Nam thay vì GHN
 * URL: /api/provinces_vn.php
 */

header('Content-Type: application/json; charset=utf-8');

// API miễn phí: https://provinces.open-api.vn/api/
$apiUrl = 'https://provinces.open-api.vn/api/p/';

try {
    // Get provinces từ API miễn phí
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("Lỗi kết nối: $error");
    }
    
    $provinces = json_decode($response, true);
    
    if (!$provinces) {
        throw new Exception("Không thể parse dữ liệu");
    }
    
    // Convert sang format giống GHN để không cần sửa frontend
    $result = [];
    foreach ($provinces as $province) {
        $result[] = [
            'ProvinceID' => $province['code'],
            'ProvinceName' => $province['name'],
            'Code' => (string)$province['code']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $result,
        'source' => 'provinces.open-api.vn'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
