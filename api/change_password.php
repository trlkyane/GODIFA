<?php
/**
 * API: Đổi mật khẩu
 * File: api/change_password.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_name('GODIFA_USER_SESSION');
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['customer_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../model/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$customerID = $_SESSION['customer_id'];
$currentPassword = $_POST['currentPassword'] ?? '';
$newPassword = $_POST['newPassword'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';

// Validate
if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng điền đầy đủ thông tin'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($newPassword !== $confirmPassword) {
    echo json_encode([
        'success' => false,
        'message' => 'Mật khẩu xác nhận không khớp'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (strlen($newPassword) < 6) {
    echo json_encode([
        'success' => false,
        'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->connect();
    
    // Lấy mật khẩu hiện tại
    $stmt = $conn->prepare("SELECT password FROM customer WHERE customerID = ?");
    $stmt->bind_param("i", $customerID);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    
    if (!$customer) {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy tài khoản'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Verify mật khẩu hiện tại
    if (!password_verify($currentPassword, $customer['password'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Mật khẩu hiện tại không đúng'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Hash mật khẩu mới
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Cập nhật
    $stmt = $conn->prepare("UPDATE customer SET password = ? WHERE customerID = ?");
    $stmt->bind_param("si", $hashedPassword, $customerID);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra. Vui lòng thử lại'
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi server: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
