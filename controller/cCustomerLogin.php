<?php
/**
 * Customer Login Controller
 * File: controller/cCustomerLogin.php
 * 
 * Xử lý đăng nhập cho CUSTOMER (bảng customer)
 */

ob_start();
include_once(__DIR__ . "/../model/mCustomer.php");

class cCustomerLogin {
    
    /**
     * Đăng nhập cho Customer
     * Chỉ truy vấn bảng customer
     */
    public function login($email, $password) {
        $customerModel = new Customer();
        $customer = $customerModel->login($email, $password);
        
        if ($customer) {
            // Lưu thông tin customer vào session
            $_SESSION["customer_id"] = $customer["customerID"];
            $_SESSION["customer_name"] = $customer["customerName"];
            $_SESSION["customer_email"] = $customer["email"];
            $_SESSION["customer_phone"] = $customer["phone"];
            $_SESSION["is_customer_logged_in"] = true;
            
            // Kiểm tra có redirect sau login không (ví dụ từ checkout)
            $redirectUrl = '/GODIFA/index.php';
            if (isset($_SESSION['redirect_after_login'])) {
                $redirectUrl = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']); // Xóa sau khi dùng
            }
            
            echo "<script>alert('Đăng nhập thành công! Chào mừng " . htmlspecialchars($customer["customerName"]) . "');</script>";
            header("refresh:0;url=$redirectUrl");
            exit();
        } else {
            echo "<script>alert('Email hoặc mật khẩu không đúng!');</script>";
            header("refresh:0;url=/GODIFA/view/auth/customer-login.php");
            exit();
        }
    }
    
    /**
     * Đăng ký Customer mới
     */
    public function register($customerName, $phone, $email, $password) {
        $customerModel = new Customer();
        
        // Kiểm tra email đã tồn tại
        if ($customerModel->emailExists($email)) {
            return "exists";
        }
        
        // Đăng ký tài khoản mới
        if ($customerModel->register($customerName, $phone, $email, $password)) {
            return "success";
        } else {
            return "error";
        }
    }
}
?>
