<?php
/**
 * Customer Register Controller
 * File: controller/cRegister.php
 * 
 * Xá»­ lÃ½ Ä‘Äƒng kÃ½ cho CUSTOMER (báº£ng customer)
 */

include_once(__DIR__ . "/../model/mCustomer.php");

class cRegister {
    
    /**
     * ÄÄƒng kÃ½ tÃ i khoáº£n Customer má»›i
     * @param string $customerName - TÃªn khÃ¡ch hÃ ng
     * @param string $password - Máº­t kháº©u
     * @param string $email - Email
     * @param string $phone - Sá»‘ Ä‘iá»‡n thoáº¡i
     * @return int 1=Success, 0=Email exists, -1=Error
     */
    public function registerAccount($customerName, $password, $email, $phone) {
        $customerModel = new Customer();
        
        // Kiá»ƒm tra email Ä‘Ã£ tá»“n táº¡i
        if ($customerModel->emailExists($email)) {
            return 0; // Email Ä‘Ã£ tá»“n táº¡i
        }
        
        // ÄÄƒng kÃ½ tÃ i khoáº£n má»›i vÃ o báº£ng customer
        if ($customerModel->register($customerName, $phone, $email, $password)) {
            // Tá»± Ä‘á»™ng Ä‘Äƒng nháº­p sau khi Ä‘Äƒng kÃ½ thÃ nh cÃ´ng
            $customer = $customerModel->login($email, $password);
            if ($customer) {
                $_SESSION['customer_id'] = $customer['customerID'];
                $_SESSION['customer_name'] = $customer['customerName'];
                $_SESSION['customer_email'] = $customer['email'];
                $_SESSION['customer_phone'] = $customer['phone'];
                $_SESSION['is_customer_logged_in'] = true;
            }
            return 1; // ThÃ nh cÃ´ng
        } else {
            return -1; // Lá»—i khÃ¡c
        }
    }
}
?>
