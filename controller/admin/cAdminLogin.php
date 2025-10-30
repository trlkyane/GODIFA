<?php
/**
 * Admin Login Controller
 * File: controller/admin/cAdminLogin.php
 * 
 * Xử lý đăng nhập cho ADMIN/STAFF (bảng user)
 */

ob_start();
include_once(__DIR__ . "/../../model/mUser.php");

class cAdminLogin {
    
    /**
     * Đăng nhập cho Admin/Staff
     * Chỉ truy vấn bảng user với roleID từ 1-4
     */
    public function login($email, $password) {
        $userModel = new User();
        $user = $userModel->login($email, $password);
        
        if ($user) {
            // Kiểm tra role - Chỉ cho phép Admin/Staff (roleID 1-4)
            if ($user["roleID"] < 1 || $user["roleID"] > 4) {
                echo "<script>alert('Bạn không có quyền truy cập Admin Panel!');</script>";
                header("refresh:0;url=/GODIFA/admin/login.php");
                exit();
            }
            
            // Kiểm tra trạng thái tài khoản
            if ($user["status"] != '1') {
                echo "<script>alert('Tài khoản của bạn đã bị vô hiệu hóa!');</script>";
                header("refresh:0;url=/GODIFA/admin/login.php");
                exit();
            }
            
            // Lưu thông tin user vào session
            $_SESSION["user_id"] = $user["userID"];
            $_SESSION["user_name"] = $user["userName"];
            $_SESSION["user_email"] = $user["email"];
            $_SESSION["role_id"] = $user["roleID"];
            $_SESSION["role_name"] = $user["roleName"];
            $_SESSION["is_admin_logged_in"] = true;
            
            // Hiển thị thông báo theo role
            $roleDisplay = $user["roleName"] ?? "Staff";
            echo "<script>alert('Đăng nhập thành công! Chào mừng " . htmlspecialchars($roleDisplay) . " - " . htmlspecialchars($user["userName"]) . "');</script>";
            header("refresh:0;url=/GODIFA/admin/index.php");
            exit();
        } else {
            echo "<script>alert('Email hoặc mật khẩu không đúng!');</script>";
            header("refresh:0;url=/GODIFA/admin/login.php");
            exit();
        }
    }
}
?>
