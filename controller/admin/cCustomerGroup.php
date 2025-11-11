<?php
/**
 * Customer Group Controller - Admin
 * File: controller/admin/cCustomerGroup.php
 */

require_once __DIR__ . '/../../model/mCustomerGroup.php';

class cCustomerGroup {
    private $groupModel;
    
    public function __construct() {
        $this->groupModel = new CustomerGroup();
    }
    
    // Lấy tất cả nhóm
    public function getAllGroups() {
        return $this->groupModel->getAllGroups();
    }
    
    // Lấy nhóm đang hoạt động
    public function getActiveGroups() {
        return $this->groupModel->getActiveGroups();
    }
    
    // Lấy nhóm theo ID
    public function getGroupById($id) {
        return $this->groupModel->getGroupById($id);
    }
    
    // ❌ XÓA CHỨC NĂNG THÊM NHÓM (CỐ ĐỊNH 5 HẠNG)
    public function addGroup($data) {
        return [
            'success' => false, 
            'errors' => ['❌ Không thể thêm nhóm mới! Hệ thống sử dụng 5 hạng cố định.']
        ];
    }
    
    // ✅ CHỈ CHO SỬA TÊN, MÔ TẢ, MÀU - KHÔNG CHO SỬA minSpent/maxSpent/status
    public function updateGroup($id, $data) {
        // Validate
        $errors = [];
        
        if (empty($data['groupName'])) {
            $errors[] = "Vui lòng nhập tên nhóm!";
        }
        
        if (empty($data['color'])) {
            $data['color'] = '#6366f1';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // ✅ CHỈ CẬP NHẬT: groupName, description, color
        // ❌ KHÔNG CHO SỬA: minSpent, maxSpent, status (luôn = 1)
        $allowedData = [
            'groupName' => $data['groupName'],
            'description' => $data['description'] ?? '',
            'color' => $data['color']
        ];
        
        $result = $this->groupModel->updateGroup($id, $allowedData);
        
        if ($result) {
            return ['success' => true, 'message' => 'Cập nhật nhóm khách hàng thành công!'];
        }
        
        return ['success' => false, 'errors' => ['Lỗi khi cập nhật nhóm khách hàng!']];
    }
    
    // ❌ XÓA CHỨC NĂNG XÓA NHÓM (CỐ ĐỊNH 5 HẠNG)
    public function deleteGroup($id) {
        return [
            'success' => false,
            'errors' => ['❌ Không thể xóa nhóm! Hệ thống sử dụng 5 hạng cố định.']
        ];
    }
    
    // ❌ XÓA CHỨC NĂNG TOGGLE STATUS (CỐ ĐỊNH LUÔN HOẠT ĐỘNG)
    public function toggleStatus($id) {
        return [
            'success' => false, 
            'message' => '❌ Không thể thay đổi trạng thái! Tất cả nhóm luôn hoạt động.'
        ];
    }
    
    // Lấy thống kê nhóm
    public function getGroupStats($groupID) {
        return $this->groupModel->getGroupStats($groupID);
    }
    
    // Lấy thống kê tất cả nhóm
    public function getAllGroupStats() {
        return $this->groupModel->getAllGroupStats();
    }
    
    // Tìm kiếm nhóm
    public function searchGroups($keyword) {
        if (empty($keyword)) {
            return $this->getAllGroups();
        }
        return $this->groupModel->searchGroups($keyword);
    }
    
    // Đếm tổng số nhóm
    public function countGroups() {
        return $this->groupModel->countGroups();
    }
    
    // Đếm khách hàng trong nhóm
    public function countCustomersInGroup($groupID) {
        return $this->groupModel->countCustomersInGroup($groupID);
    }
}
?>
