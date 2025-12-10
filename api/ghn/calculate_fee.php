<?php
/**
 * API: Calculate shipping fee from GHN
 * URL: /api/ghn/calculate_fee.php
 * Method: POST
 * Body: {districtId, wardCode, weight, insurance}
 */

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/ghn_api.log');

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../model/mGHN.php';

$input = json_decode(file_get_contents('php://input'), true);

$districtId = $input['districtId'] ?? null;
$wardCode = $input['wardCode'] ?? null;
$weight = $input['weight'] ?? 500; // Default 500g (GHN minimum: 200g)
$insurance = $input['insurance'] ?? 0;
$serviceTypeId = $input['service_type_id'] ?? 2; // Default: TiÃªu chuáº©n

// Validate weight (GHN requires >= 200g)
if ($weight < 200) {
    $weight = 500; // Fallback to 500g
}

// Log request for debugging
error_log("GHN Calculate Fee Request: " . json_encode([
    'districtId' => $districtId,
    'wardCode' => $wardCode,
    'weight' => $weight,
    'insurance' => $insurance,
    'serviceTypeId' => $serviceTypeId
]));

if (!$districtId || !$wardCode) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing districtId or wardCode'
    ]);
    exit;
}

try {
    // Fallback: Tính phí ship cố định hoặc theo tỉnh
    // Phí ship mặc định: 30,000 VND
    $shippingFee = 30000;
    
    // Nếu có provinceId trong request, tính theo khu vực
    $provinceId = $input['provinceId'] ?? 202; // Default: HCM
    
    // Định nghĩa phí ship theo tỉnh (ID từ data/provinces_static.php)
    $feeByProvince = [
        201 => 35000,  // Hà Nội
        202 => 25000,  // Hồ Chí Minh
        203 => 30000,  // Đà Nẵng
        204 => 35000,  // Hải Phòng
        205 => 28000,  // Cần Thơ
    ];
    
    // Lấy phí theo tỉnh, nếu không có thì dùng mặc định 30k
    $shippingFee = $feeByProvince[$provinceId] ?? 30000;
    
    error_log("Shipping Fee Calculated: {$shippingFee} VND for province {$provinceId}");
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total' => $shippingFee,
            'service_fee' => $shippingFee,
            'insurance_fee' => 0,
        ],
        'source' => 'static-calculation',
        'note' => 'Phí ship tính theo tỉnh thành (không dùng GHN API)'
    ]);
    
    /* GHN API disabled
    $ghn = new GHN();
    $result = $ghn->calculateFee((int)$districtId, $wardCode, (int)$weight, (int)$insurance, (int)$serviceTypeId);
    
    // Log response
    error_log("GHN Calculate Fee Response: " . json_encode($result));
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'data' => [
                'total' => $result['data']['total'] ?? 0,
                'service_fee' => $result['data']['service_fee'] ?? 0,
                'insurance_fee' => $result['data']['insurance_fee'] ?? 0,
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Unknown error',
            'details' => $result['response'] ?? null
        ]);
    }
    */
} catch (Exception $e) {
    error_log("Shipping Fee Exception: " . $e->getMessage());
    
    // Fallback on error
    echo json_encode([
        'success' => true,
        'data' => [
            'total' => 30000,
            'service_fee' => 30000,
            'insurance_fee' => 0,
        ],
        'source' => 'fallback'
    ]);
}
