<?php
/**
 * Customer Register Controller
 * File: controller/cRegister.php
 * 
 * Xử lý đăng ký cho CUSTOMER (bảng customer)
 */

include_once(__DIR__ . "/../model/mCustomer.php");

class cRegister {
    
    /**
     * Đăng ký tài khoản Customer mới
     * @param string $customerName - Tên khách hàng
     * @param string $password - Mật khẩu
     * @param string $email - Email
     * @param string $phone - Số điện thoại
     * @return int 1=Success, 0=Email exists, -1=Error
     */
    public function registerAccount($customerName, $password, $email, $phone) {
        $customerModel = new Customer();
        
        // Kiểm tra email đã tồn tại
        if ($customerModel->emailExists($email)) {
            return 0; // Email đã tồn tại
        }
        
        // Đăng ký tài khoản mới vào bảng customer
        if ($customerModel->register($customerName, $phone, $email, $password)) {
            // Tự động đăng nhập sau khi đăng ký thành công
            $customer = $customerModel->login($email, $password);
            if ($customer) {
                $_SESSION['customer_id'] = $customer['customerID'];
                $_SESSION['customer_name'] = $customer['customerName'];
                $_SESSION['customer_email'] = $customer['email'];
                $_SESSION['customer_phone'] = $customer['phone'];
                $_SESSION['is_customer_logged_in'] = true;
            }
            return 1; // Thành công
        } else {
            return -1; // Lỗi khác
        }
    }
}
?>
