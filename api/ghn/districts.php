<?php
/**
 * API: Get districts by province from GHN
 * URL: /api/ghn/districts.php?provinceId=202
 */

// Tắt error display để không làm hỏng JSON
error_reporting(0);
ini_set('display_errors', 0);

// Xóa mọi output buffer trước đó
if (ob_get_level()) ob_end_clean();
ob_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../model/mGHN.php';

$provinceId = $_GET['provinceId'] ?? null;

if (!$provinceId) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing provinceId'
    ]);
    exit;
}

try {
    // Fallback: Trả về data đơn giản để user có thể nhập text
    // Chỉ cần ID để form submit, không cần gọi GHN API
    $districts = [
        ['DistrictID' => 1, 'DistrictName' => 'Nhập quận/huyện của bạn', 'Code' => '1']
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $districts,
        'source' => 'manual-input',
        'note' => 'Vui lòng nhập chính xác tên quận/huyện'
    ], JSON_UNESCAPED_UNICODE);
    
    /* GHN API disabled - uncomment to re-enable
    $ghn = new GHN();
    $result = $ghn->getDistricts((int)$provinceId);
    
    if ($result['success']) {
        // Filter out test data
        $filteredData = array_filter($result['data'], function($district) {
            $name = strtolower($district['DistrictName'] ?? '');
            return !preg_match('/(test|demo|\s\d{3}$)/i', $name);
        });
        
        $filteredData = array_values($filteredData);
        
        echo json_encode([
            'success' => true,
            'data' => $filteredData
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ], JSON_UNESCAPED_UNICODE);
    }
    */
} catch (Exception $e) {
    // Fallback on error
    $districts = [
        ['DistrictID' => 1, 'DistrictName' => 'Nhập quận/huyện của bạn', 'Code' => '1']
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $districts,
        'source' => 'fallback',
        'note' => 'GHN API error - manual input mode'
    ], JSON_UNESCAPED_UNICODE);
}

// Flush output buffer
ob_end_flush();
exit;
