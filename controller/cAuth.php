<?php
session_start();
require_once '../model/mCustomer.php';

class AuthController {
    private $customerModel;
    
    public function __construct() {
        $this->customerModel = new Customer();
    }
    
    // Đăng ký
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $customerName = trim($_POST['customer_name']);
            $phone = trim($_POST['phone']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirm_password'];
            
            // Validate
            if (empty($customerName) || empty($phone) || empty($email) || empty($password)) {
                return ['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin'];
            }
            
            if ($password !== $confirmPassword) {
                return ['success' => false, 'message' => 'Mật khẩu xác nhận không khớp'];
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Email không hợp lệ'];
            }
            
            if ($this->customerModel->emailExists($email)) {
                return ['success' => false, 'message' => 'Email đã được đăng ký'];
            }
            
            if ($this->customerModel->register($customerName, $phone, $email, $password)) {
                return ['success' => true, 'message' => 'Đăng ký thành công'];
            }
            
            return ['success' => false, 'message' => 'Đăng ký thất bại'];
        }
    }
    
    // Đăng nhập
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            
            if (empty($email) || empty($password)) {
                return ['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin'];
            }
            
            $customer = $this->customerModel->login($email, $password);
            
            if ($customer) {
                $_SESSION['customer_id'] = $customer['customerID'];
                $_SESSION['customer_name'] = $customer['customerName'];
                $_SESSION['customer_email'] = $customer['email'];
                
                return ['success' => true, 'message' => 'Đăng nhập thành công'];
            }
            
            return ['success' => false, 'message' => 'Email hoặc mật khẩu không đúng'];
        }
    }
    
    // Đăng xuất
    public function logout() {
        session_destroy();
        header('Location: ../index.php');
        exit();
    }
    
    // Kiểm tra đăng nhập
    public function checkLogin() {
        return isset($_SESSION['customer_id']);
    }
}

// Xử lý request
if (isset($_GET['action'])) {
    $controller = new AuthController();
    $action = $_GET['action'];
    
    switch ($action) {
        case 'register':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $result = $controller->register();
                if ($result['success']) {
                    header('Location: ../view/auth/login.php?success=1');
                } else {
                    header('Location: ../view/auth/register.php?error=' . urlencode($result['message']));
                }
            } else {
                include '../view/auth/register.php';
            }
            break;
            
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $result = $controller->login();
                if ($result['success']) {
                    header('Location: ../index.php');
                } else {
                    header('Location: ../view/auth/login.php?error=' . urlencode($result['message']));
                }
            } else {
                include '../view/auth/login.php';
            }
            break;
            
        case 'logout':
            $controller->logout();
            break;
            
        default:
            header('Location: ../index.php');
    }
}
?>
