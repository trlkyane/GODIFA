<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('GODIFA_USER_SESSION');
    session_start();
}

header('Content-Type: application/json');
echo json_encode([
    'session_status' => session_status(),
    'session_name' => session_name(),
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'is_logged_in' => isset($_SESSION['customer_id']) && isset($_SESSION['is_customer_logged_in'])
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
