<?php
/**
 * API: Get wards by district from GHN
 * URL: /api/ghn/wards.php?districtId=1542
 */

// Tắt error display để không làm hỏng JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../model/mGHN.php';

$districtId = $_GET['districtId'] ?? null;

if (!$districtId) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing districtId'
    ]);
    exit;
}

try {
    $ghn = new GHN();
    $result = $ghn->getWards((int)$districtId);
    
    if ($result['success']) {
        // Filter out test data
        $filteredData = array_filter($result['data'], function($ward) {
            $name = strtolower($ward['WardName'] ?? '');
            return !preg_match('/(test|demo|ngoc\s+test)/i', $name);
        });
        
        $filteredData = array_values($filteredData);
        
        echo json_encode([
            'success' => true,
            'data' => $filteredData
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
