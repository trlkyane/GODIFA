<?php
include_once(__DIR__ . "/database.php");

class mLogin {
    public function selectUser($email, $password) {
        $p = new clsKetNoi();
        $con = $p->moKetNoi();
        if ($con->connect_error) {
            return false;
        } else {
            $email = $con->real_escape_string($email);
            $password = $con->real_escape_string($password);
            $query = "SELECT * FROM user WHERE email = '$email' AND password = '$password'";
            $result = mysqli_query($con, $query);
            $p->dongKetNoi($con);
            return $result;
        }
    }
    public function registerAccount($username, $password, $hoten, $email, $phone) {
        $p = new clsKetNoi();
        $con = $p->moKetNoi();
        if ($con) {
            $password = md5($password); // Mã hóa mật khẩu

            // Kiểm tra trùng email
            $check = mysqli_prepare($con, "SELECT userID FROM user WHERE email = ?");
            mysqli_stmt_bind_param($check, "s", $email);
            mysqli_stmt_execute($check);
            mysqli_stmt_store_result($check);

            if (mysqli_stmt_num_rows($check) > 0) {
                mysqli_stmt_close($check);
                $p->dongKetNoi($con);
                return "exists"; // Trả về trạng thái tồn tại
            }
            mysqli_stmt_close($check);

            // Thêm tài khoản mới
            $roleID = 2; // Mặc định là nhân viên hoặc user thường
            $status = '1';
            $stmt = mysqli_prepare($con, "INSERT INTO user (userName, email, password, phone, status, roleID) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sssssi", $hoten, $email, $password, $phone, $status, $roleID);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                $p->dongKetnoi($con);
                return "success";
            } else {
                mysqli_stmt_close($stmt);
                $p->dongKetnoi($con);
                return "error";
            }
        } else {
            return "db_error";
        }
    }
    

}
?>