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
            $check = $con->prepare("SELECT userID FROM user WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $check->close();
                $p->dongKetNoi($con);
                return "exists"; // Trả về trạng thái tồn tại
            }
            $check->close();

            // Thêm tài khoản mới
            $roleID = 2; // Mặc định là nhân viên hoặc user thường
            $status = '1';
            $stmt = $con->prepare("INSERT INTO user (userName, email, password, phone, status, roleID) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssi", $hoten, $email, $password, $phone, $status, $roleID);

            if ($stmt->execute()) {
                $stmt->close();
                $p->dongKetnoi($con);
                return "success";
            } else {
                $stmt->close();
                $p->dongKetnoi($con);
                return "error";
            }
        } else {
            return "db_error";
        }
    }
    

}
?>