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
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// Flush output buffer
ob_end_flush();
exit;
