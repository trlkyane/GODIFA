<?php
    ob_start();
    include_once(__DIR__ . "/../model/mUser.php");
    
    class cLogin{
        // Đăng nhập cho admin/user (bảng user)
        public function getUser($email, $password){
            $userModel = new User();
            $user = $userModel->login($email, $password);
            if ($user) {
                if ($user["status"] != '1') {
                    echo "<script>alert('Tài khoản của bạn đã bị vô hiệu hóa!');</script>";
                    header("refresh:0;url=/GODIFA/view/auth/login.php");
                    exit();
                }
                
                // Lưu thông tin vào session
                $_SESSION["dn"] = $user["roleID"];
                $_SESSION["user_id"] = $user["userID"];
                $_SESSION["username"] = $user["userName"];
                $_SESSION["user_name"] = $user["userName"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role_id"] = $user["roleID"];
                
                // Redirect theo role - Staff (role 1,2,3,4) vào admin, khách hàng vào trang chủ
                if ($user["roleID"] >= 1 && $user["roleID"] <= 4) {
                    // Nhân viên/Admin -> redirect vào admin panel
                    echo "<script>alert('Đăng nhập thành công! Chào mừng đến Admin Panel');</script>";
                    header("refresh:0;url=/GODIFA/admin/index.php");
                } else {
                    // Khách hàng -> redirect về trang chủ
                    echo "<script>alert('Đăng nhập thành công!');</script>";
                    header("refresh:0;url=/GODIFA/index.php");
                }
                exit();
            } else {
                echo "<script>alert('Email hoặc mật khẩu không đúng!');</script>";
                header("refresh:0;url=/GODIFA/view/auth/login.php");
                exit();
            }
        }
        
        // Hàm đăng ký với đầy đủ tham số
        public function registerAccount($username, $password, $hoten, $email, $phone) {
            $p = new mLogin();
            $result = $p->registerAccount($username, $password, $hoten, $email, $phone);

            if ($result === "success") {
                $_SESSION['is_login'] = array(
                    'userName' => $hoten,
                    'email' => $email,
                );
                return 1; // Thành công
            } elseif ($result === "exists") {
                return 0; // Trùng email
            } else {
                return -1; // Lỗi khác
            }
        }
    }
    ob_end_flush();
?>
