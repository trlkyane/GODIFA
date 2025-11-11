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
     * Đảm bảo Model getCustomerById trả về cột 'birthdate'
     */
    public function getAccountInfo($customerId) {
        $customerInfo = $this->customerModel->getCustomerById($customerId);
        
        if (!$customerInfo) {
            return ['error' => 'Không tìm thấy thông tin tài khoản.'];
        }
        
        // // 🛑 BƯỚC ĐIỀU CHỈNH QUAN TRỌNG: 
        // // Đổi tên cột 'birthdate' (tên trong DB) thành 'dateOfBirth' (tên biến mong muốn của View) 
        // // để View update_info.php có thể sử dụng dễ dàng hơn.
        // if (isset($customerInfo['birthdate'])) {
        //     $customerInfo['dateOfBirth'] = $customerInfo['birthdate'];
        //     unset($customerInfo['birthdate']);
        // }
        
        return ['customer' => $customerInfo];
    }
    
    /**
     * Cập nhật thông tin cá nhân (Bỏ qua Email và Địa chỉ)
     */
    public function updateInfo($customerId, $postData) {
        $customerName = trim($postData['customerName'] ?? '');
        $phone = trim($postData['phone'] ?? '');
        $gender = trim($postData['gender'] ?? null);
        
        // Tên biến $dateOfBirth vẫn được sử dụng để lấy dữ liệu từ POST
        $dateOfBirth = empty($postData['birthdate']) ? NULL : date('Y-m-d', strtotime($postData['birthdate'])); 
    
        if (empty($customerName) || empty($phone)) {
            return ['success' => false, 'message' => 'Vui lòng điền đầy đủ Tên và Số Điện Thoại.'];
        }
    
        // GỌI HÀM CẬP NHẬT TRONG MODEL (Model đã được sửa để ánh xạ $dateOfBirth -> birthdate)
        $result = $this->customerModel->updateCustomerAccount(
            $customerId, 
            $customerName, 
            $phone, 
            $gender,
            $dateOfBirth
        ); 
        
        if ($result) {
            $_SESSION['customer_name'] = $customerName;
            return ['success' => true, 'message' => 'Cập nhật thông tin thành công!'];
        }
        
        return ['success' => false, 'message' => 'Cập nhật thông tin thất bại hoặc không có gì thay đổi. Vui lòng thử lại.'];
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
        
        // --- XỬ LÝ GET REQUEST (NẠP FORM) ---
        // 1. Lấy thông tin khách hàng hiện tại
        $data = $controller->getAccountInfo($customerID);
        
        // 2. Kiểm tra lỗi nếu không lấy được dữ liệu khách hàng
        if (isset($data['error'])) {
             $_SESSION['notify_error'] = $data['error'];
             header('Location: cCustomerAccount.php?action=view'); 
             exit();
        }

        // 3. Tạo biến $customer và Nạp View chứa form cập nhật
        extract($data); // Tạo biến $customer
        include '../view/customer/update_info.php';
        break; 
        
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