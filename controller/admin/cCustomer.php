<?php
/**
 * Customer Controller - Admin
 * File: controller/cCustomer.php
 */

require_once __DIR__ . '/../../model/mCustomer.php';

class cCustomer {
    private $customerModel;
    
    public function __construct() {
        $this->customerModel = new Customer();
    }
    
    // Lấy tất cả khách hàng
    public function getAllCustomers() {
        return $this->customerModel->getAllCustomers();
    }
    
    // Lấy khách hàng theo ID
    public function getCustomerById($id) {
        return $this->customerModel->getCustomerById($id);
    }
    
    // Thêm khách hàng mới (Admin tạo)
    public function addCustomer($data) {
        // Validate
        $errors = [];
        
        if (empty($data['customerName'])) {
            $errors[] = "Vui lòng nhập tên khách hàng!";
        }
        
        if (empty($data['email'])) {
            $errors[] = "Vui lòng nhập email!";
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email không hợp lệ!";
        } elseif ($this->customerModel->emailExists($data['email'])) {
            $errors[] = "Email đã tồn tại!";
        }
        
        if (empty($data['phone'])) {
            $errors[] = "Vui lòng nhập số điện thoại!";
        }
        
        if (empty($data['password'])) {
            $errors[] = "Vui lòng nhập mật khẩu!";
        } elseif (strlen($data['password']) < 6) {
            $errors[] = "Mật khẩu phải có ít nhất 6 ký tự!";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Thêm khách hàng
        $result = $this->customerModel->register(
            $data['customerName'],
            $data['phone'],
            $data['email'],
            $data['password']
        );
        
        if ($result) {
            return ['success' => true, 'message' => 'Thêm khách hàng thành công!'];
        }
        
        return ['success' => false, 'errors' => ['Lỗi khi thêm khách hàng!']];
    }
    
    // Cập nhật thông tin khách hàng
    public function updateCustomer($id, $data, $currentRoleID = null) {
        // Validate
        $errors = [];
        
        if (empty($data['customerName'])) {
            $errors[] = "Vui lòng nhập tên khách hàng!";
        }
        
        if (empty($data['email'])) {
            $errors[] = "Vui lòng nhập email!";
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email không hợp lệ!";
        } else {
            // Kiểm tra email trùng với khách hàng khác
            $existingCustomer = $this->customerModel->getCustomerById($id);
            if ($existingCustomer && $existingCustomer['email'] != $data['email']) {
                if ($this->customerModel->emailExists($data['email'])) {
                    $errors[] = "Email đã được sử dụng bởi khách hàng khác!";
                }
            }
        }
        
        if (empty($data['phone'])) {
            $errors[] = "Vui lòng nhập số điện thoại!";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Xác định có update status không
        $status = null;
        
        // Chỉ Chủ DN và NVQT được thay đổi trạng thái
        if (isset($data['status']) && ($currentRoleID == 1 || $currentRoleID == 2)) {
            $status = intval($data['status']);
        }
        
        // Cập nhật khách hàng
        $result = $this->customerModel->updateCustomer(
            $id,
            $data['customerName'],
            $data['phone'],
            $data['email'],
            $status
        );
        
        if ($result) {
            return ['success' => true, 'message' => 'Cập nhật thông tin thành công!'];
        }
        
        return ['success' => false, 'errors' => ['Lỗi khi cập nhật thông tin!']];
    }
    
    // Cập nhật trạng thái khách hàng (Hoạt động/Đã khóa)
    public function updateStatus($id, $status, $currentRoleID = null) {
        // PHÂN QUYỀN: Chỉ Chủ DN và NVQT được thay đổi trạng thái
        if ($currentRoleID != 1 && $currentRoleID != 2) {
            return [
                'success' => false,
                'message' => 'Bạn không có quyền thay đổi trạng thái khách hàng!'
            ];
        }
        
        // Validate status
        if ($status !== 0 && $status !== 1 && $status !== '0' && $status !== '1') {
            return [
                'success' => false,
                'message' => 'Trạng thái không hợp lệ!'
            ];
        }
        
        $result = $this->customerModel->updateStatus($id, intval($status));
        
        if ($result) {
            $statusText = $status == 1 ? 'Hoạt động' : 'Đã khóa';
            return [
                'success' => true,
                'message' => "Đã chuyển trạng thái sang: $statusText"
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Lỗi khi cập nhật trạng thái!'
        ];
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
        $result = $this->customerModel->changePassword($id, $data['newPassword']);
        
        if ($result) {
            return ['success' => true, 'message' => 'Đổi mật khẩu thành công!'];
        }
        
        return ['success' => false, 'errors' => ['Lỗi khi đổi mật khẩu!']];
    }
    
    // Xóa khách hàng
    public function deleteCustomer($id) {
        $result = $this->customerModel->deleteCustomer($id);
        
        if ($result) {
            return ['success' => true, 'message' => 'Xóa khách hàng thành công!'];
        }
        
        return ['success' => false, 'errors' => ['Lỗi khi xóa khách hàng!']];
    }
    
    // Lấy lịch sử mua hàng
    public function getOrderHistory($customerID) {
        return $this->customerModel->getOrderHistory($customerID);
    }
    
    // Thống kê khách hàng
    public function getCustomerStats($customerID) {
        return $this->customerModel->getCustomerStats($customerID);
    }
    
    // Tìm kiếm khách hàng
    public function searchCustomers($keyword) {
        if (empty($keyword)) {
            return $this->getAllCustomers();
        }
        return $this->customerModel->searchCustomers($keyword);
    }
    
    // Đếm tổng số khách hàng
    public function countCustomers() {
        return $this->customerModel->countCustomers();
    }
}
?>
