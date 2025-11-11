<?php
/**
 * API: Get provinces from GHN
 * URL: /api/ghn/provinces.php
 */

// Tắt error display để không làm hỏng JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../model/mGHN.php';

try {
    $ghn = new GHN();
    $result = $ghn->getProvinces();
    
    if ($result['success']) {
        // Filter out test data from GHN dev environment
        $filteredData = array_filter($result['data'], function($province) {
            $name = strtolower($province['ProvinceName'] ?? '');
            // Remove provinces with "test", "demo", or numbered variations like "Hà Nội 02"
            return !preg_match('/(test|demo|\s\d{2}$)/i', $name);
        });
        
        // Re-index array to ensure JSON array format
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
} catch (Throwable $t) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $t->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
