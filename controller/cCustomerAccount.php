<?php
// File: /GODIFA/controller/cCustomerAccount.php

if (session_status() === PHP_SESSION_NONE) {
    session_name('GODIFA_USER_SESSION');
    session_start();
}

ob_start();

$basePath = __DIR__ . '/..';

// 1. GÁN GIÁ TRỊ TỪ SESSION LÊN ĐẦU
$customerID = $_SESSION['customer_id'] ?? null;
$loginUrl = 'cCustomerLogin.php'; 

// REQUIRE MODEL KHÁCH HÀNG
require_once $basePath . '/model/mCustomer.php'; 

// --- HÀM KIỂM TRA ĐĂNG NHẬP VÀ CHUYỂN HƯỚNG ---
function checkCustomerLogin($loginUrl) {
    global $customerID;
    if (!$customerID) {
        $_SESSION['notify_error'] = 'Vui lòng đăng nhập để truy cập trang này.';
        header("Location: $loginUrl"); 
        exit();
    }
}

// 2. CHỈ CẦN GỌI HÀM KIỂM TRA MỘT LẦN VÀ TIẾP TỤC BẰNG LOGIC KHÁC
checkCustomerLogin($loginUrl);


class CustomerAccountController {
    private $customerModel;
    
    public function __construct() {
        $this->customerModel = new Customer(); 
    }
    
    /**
     * Lấy thông tin tài khoản để hiển thị
     */
    public function getAccountInfo($customerId) {
        $customerInfo = $this->customerModel->getCustomerById($customerId);
        
        if (!$customerInfo) {
            return ['error' => 'Không tìm thấy thông tin tài khoản.'];
        }
        
        // Lấy thông tin nhóm khách hàng
        $groupInfo = null;
        if (!empty($customerInfo['groupID'])) {
            require_once __DIR__ . '/../model/mCustomerGroup.php';
            $groupModel = new CustomerGroup();
            $groupInfo = $groupModel->getGroupById($customerInfo['groupID']);
        }
        
        return [
            'customer' => $customerInfo,
            'group' => $groupInfo
        ];
    }
    
    /**
     * Cập nhật thông tin cá nhân (Chỉ cập nhật Họ tên và SĐT)
     */
    public function updateInfo($customerId, $postData) {
        $customerName = trim($postData['customerName'] ?? '');
        $phone = trim($postData['phone'] ?? '');
    
        if (empty($customerName) || empty($phone)) {
            return ['success' => false, 'message' => 'Vui lòng điền đầy đủ Họ tên và Số Điện Thoại.'];
        }
        
        // Validate phone number format (10-11 digits)
        if (!preg_match('/^[0-9]{10,11}$/', $phone)) {
            return ['success' => false, 'message' => 'Số điện thoại không hợp lệ. Vui lòng nhập 10-11 chữ số.'];
        }
    
        // Cập nhật trong database
        $result = $this->customerModel->updateCustomerBasicInfo($customerId, $customerName, $phone); 
        
        if ($result) {
            $_SESSION['customer_name'] = $customerName;
            return ['success' => true, 'message' => 'Cập nhật thông tin thành công!'];
        }
        
        return ['success' => false, 'message' => 'Cập nhật thông tin thất bại hoặc không có gì thay đổi.'];
    }
    
    /**
     * Đổi mật khẩu
     */
    public function changePassword($customerId, $postData) {
        $currentPassword = $postData['currentPassword'] ?? '';
        $newPassword = $postData['newPassword'] ?? '';
        $confirmPassword = $postData['confirmPassword'] ?? '';
        
        if ($newPassword !== $confirmPassword) {
            return ['success' => false, 'message' => 'Mật khẩu mới và xác nhận mật khẩu không khớp.'];
        }
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự.'];
        }
        
        $customer = $this->customerModel->getCustomerById($customerId);
        if (!$customer || $customer['password'] !== md5($currentPassword)) {
            return ['success' => false, 'message' => 'Mật khẩu hiện tại không đúng.'];
        }
        
        $result = $this->customerModel->changePassword($customerId, $newPassword); 
        
        if ($result) {
            session_destroy();
            return ['success' => true, 'message' => 'Đổi mật khẩu thành công! Vui lòng đăng nhập lại.'];
        }
        
        return ['success' => false, 'message' => 'Đổi mật khẩu thất bại.'];
    }
}

// =======================================================
// 🛑 ĐIỀU PHỐI REQUEST CHÍNH (Router)
// =======================================================

$controller = new CustomerAccountController();
$action = $_GET['action'] ?? 'view';

switch ($action) {
    case 'view':
        $data = $controller->getAccountInfo($customerID);
        
        if (isset($data['error'])) {
            $_SESSION['notify_error'] = $data['error'];
            header("Location: $loginUrl"); 
            exit();
        }
        
        // Tạo biến $customer để View sử dụng
        extract($data); 
        
        // Nạp View hiển thị thông tin
        include '../view/customer/info.php'; 
        break;
        
    case 'update_info':
        // Chỉ xử lý POST request để cập nhật
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $controller->updateInfo($customerID, $_POST);
            
            if ($result['success']) {
                $_SESSION['notify_success'] = $result['message'];
            } else {
                $_SESSION['notify_error'] = $result['message'];
            }
            // Chuyển hướng về trang xem thông tin sau khi CẬP NHẬT XONG
            header('Location: cCustomerAccount.php?action=view'); 
            exit();
        }
        
        // GET request -> chuyển về view page
        header('Location: cCustomerAccount.php?action=view'); 
        exit(); 
        
    case 'change_password':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $controller->changePassword($customerID, $_POST);
            
            if ($result['success']) {
                $_SESSION['notify_success'] = $result['message'];
                header("Location: $loginUrl");
                exit(); 
            } else {
                $_SESSION['notify_error'] = $result['message'];
            }
        }
        include '../view/customer/change_password.php'; 
        break;
        
    default:
        header('Location: cCustomerAccount.php?action=view');
        exit();
}

ob_end_flush();
?>