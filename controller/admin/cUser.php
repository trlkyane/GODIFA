<?php
/**
 * User Controller - Admin
 * File: controller/cUser.php
 */

require_once __DIR__ . '/../../model/mUser.php';
require_once __DIR__ . '/../../model/mRole.php';

class cUser {
    private $userModel;
    private $roleModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->roleModel = new Role();
    }
    
    // Lấy tất cả nhân viên
    public function getAllUsers() {
        return $this->userModel->getAllUsers();
    }
    
    // Lấy nhân viên theo ID
    public function getUserById($id) {
        return $this->userModel->getUserById($id);
    }
    
    // Lấy tất cả roles
    public function getAllRoles() {
        return $this->roleModel->getAllRoles();
    }
    
    // Thêm nhân viên
    public function addUser($data, $currentRoleID = null) {
        // Validate
        $errors = [];
        
        if (empty($data['userName'])) {
            $errors[] = "Vui lòng nhập tên đăng nhập!";
        }
        
        if (empty($data['email'])) {
            $errors[] = "Vui lòng nhập email!";
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email không hợp lệ!";
        } elseif ($this->userModel->emailExists($data['email'])) {
            $errors[] = "Email đã tồn tại trong hệ thống!";
        }
        
        if (empty($data['password'])) {
            $errors[] = "Vui lòng nhập mật khẩu!";
        } elseif (strlen($data['password']) < 6) {
            $errors[] = "Mật khẩu phải có ít nhất 6 ký tự!";
        }
        
        if (!isset($data['roleID']) || $data['roleID'] <= 0) {
            $errors[] = "Vui lòng chọn vai trò!";
        }
        
        // PHÂN QUYỀN: Nhân viên Quản trị không được thêm nhân viên cùng hoặc cao hơn cấp mình
        if ($currentRoleID == 2) { // Nhân viên Quản trị
            if ($data['roleID'] == 1 || $data['roleID'] == 2) {
                return [
                    'success' => false,
                    'errors' => ['Bạn không có quyền thêm Chủ Doanh Nghiệp hoặc Nhân viên Quản trị khác!']
                ];
            }
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Thêm nhân viên
        $result = $this->userModel->addUser(
            $data['userName'],
            $data['email'],
            $data['password'],
            $data['phone'] ?? '',
            $data['roleID']
        );
        
        if ($result) {
            return ['success' => true, 'message' => 'Thêm nhân viên thành công!'];
        }
        
        return ['success' => false, 'errors' => ['Lỗi khi thêm nhân viên!']];
    }
    
    // Cập nhật nhân viên
    public function updateUser($id, $data, $currentRoleID = null) {
        // PHÂN QUYỀN: Kiểm tra trước khi validate
        // Nhân viên Quản trị không được sửa nhân viên cùng hoặc cao hơn cấp mình
        if ($currentRoleID == 2) { // Nhân viên Quản trị
            $targetUser = $this->userModel->getUserById($id);
            
            // Kiểm tra nhân viên đang được sửa
            if ($targetUser && ($targetUser['roleID'] == 1 || $targetUser['roleID'] == 2)) {
                return [
                    'success' => false,
                    'errors' => ['Bạn không có quyền sửa thông tin Chủ Doanh Nghiệp hoặc Nhân viên Quản trị khác!']
                ];
            }
            
            // Kiểm tra roleID mới (không cho thăng cấp lên roleID 1 hoặc 2)
            if (isset($data['roleID']) && ($data['roleID'] == 1 || $data['roleID'] == 2)) {
                return [
                    'success' => false,
                    'errors' => ['Bạn không có quyền thăng cấp nhân viên lên Chủ Doanh Nghiệp hoặc Nhân viên Quản trị!']
                ];
            }
        }
        
        // Validate
        $errors = [];
        
        if (empty($data['userName'])) {
            $errors[] = "Vui lòng nhập tên đăng nhập!";
        }
        
        if (empty($data['email'])) {
            $errors[] = "Vui lòng nhập email!";
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email không hợp lệ!";
        } else {
            // Kiểm tra email trùng với user khác
            $existingUser = $this->userModel->getUserById($id);
            if ($existingUser && $existingUser['email'] != $data['email']) {
                if ($this->userModel->emailExists($data['email'])) {
                    $errors[] = "Email đã được sử dụng bởi nhân viên khác!";
                }
            }
        }
        
        if (!isset($data['roleID']) || $data['roleID'] <= 0) {
            $errors[] = "Vui lòng chọn vai trò!";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Cập nhật nhân viên
        $result = $this->userModel->updateUser(
            $id,
            $data['userName'],
            $data['email'],
            $data['phone'] ?? '',
            $data['roleID'],
            $data['status'] ?? 1
        );
        
        if ($result) {
            return ['success' => true, 'message' => 'Cập nhật nhân viên thành công!'];
        }
        
        return ['success' => false, 'errors' => ['Lỗi khi cập nhật nhân viên!']];
    }
    
    // Đổi mật khẩu
    public function changePassword($id, $data) {
        // Validate
        $errors = [];
        
        if (empty($data['newPassword'])) {
            $errors[] = "Vui lòng nhập mật khẩu mới!";
        } elseif (strlen($data['newPassword']) < 6) {
            $errors[] = "Mật khẩu phải có ít nhất 6 ký tự!";
        }
        
        if (empty($data['confirmPassword'])) {
            $errors[] = "Vui lòng xác nhận mật khẩu!";
        } elseif ($data['newPassword'] != $data['confirmPassword']) {
            $errors[] = "Mật khẩu xác nhận không khớp!";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Đổi mật khẩu
        $result = $this->userModel->changePassword($id, $data['newPassword']);
        
        if ($result) {
            return ['success' => true, 'message' => 'Đổi mật khẩu thành công!'];
        }
        
        return ['success' => false, 'errors' => ['Lỗi khi đổi mật khẩu!']];
    }
    
    // Toggle trạng thái (Khóa/Mở khóa)
    public function toggleStatus($id) {
        $result = $this->userModel->toggleStatus($id);
        
        if ($result) {
            // Lấy trạng thái mới
            $user = $this->userModel->getUserById($id);
            $statusText = $user['status'] == 1 ? 'mở khóa' : 'khóa';
            return ['success' => true, 'message' => "Đã {$statusText} tài khoản thành công!"];
        }
        
        return ['success' => false, 'message' => 'Lỗi khi thay đổi trạng thái!'];
    }
    
    // Xóa nhân viên
    public function deleteUser($id, $currentUserID, $currentRoleID = null) {
        // Không cho xóa chính mình
        if ($id == $currentUserID) {
            return ['success' => false, 'errors' => ['Không thể xóa chính mình!']];
        }
        
        // PHÂN QUYỀN: Nhân viên Quản trị không được xóa nhân viên cùng hoặc cao hơn cấp mình
        if ($currentRoleID == 2) { // Nhân viên Quản trị
            $targetUser = $this->userModel->getUserById($id);
            
            if ($targetUser && ($targetUser['roleID'] == 1 || $targetUser['roleID'] == 2)) {
                return [
                    'success' => false,
                    'errors' => ['Bạn không có quyền xóa Chủ Doanh Nghiệp hoặc Nhân viên Quản trị khác!']
                ];
            }
        }
        
        $result = $this->userModel->deleteUser($id);
        
        if ($result) {
            return ['success' => true, 'message' => 'Xóa nhân viên thành công!'];
        }
        
        return ['success' => false, 'errors' => ['Lỗi khi xóa nhân viên!']];
    }
    
    // Đếm tổng số nhân viên
    public function countUsers() {
        return $this->userModel->countUsers();
    }
}
?>
