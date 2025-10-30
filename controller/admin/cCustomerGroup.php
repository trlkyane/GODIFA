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
    
    // Thêm nhóm mới
    public function addGroup($data) {
        // Validate
        $errors = [];
        
        if (empty($data['groupName'])) {
            $errors[] = "Vui lòng nhập tên nhóm!";
        }
        
        if (empty($data['color'])) {
            $data['color'] = '#6366f1'; // Màu mặc định
        }
        
        if (!isset($data['status'])) {
            $data['status'] = 1;
        }
        
        // Validate minSpent và maxSpent
        if (!empty($data['maxSpent']) && $data['maxSpent'] <= $data['minSpent']) {
            $errors[] = "Chi tiêu tối đa phải lớn hơn chi tiêu tối thiểu!";
        }
        
        // ✅ KIỂM TRA KHOẢNG CHI TIÊU KHÔNG ĐƯỢC GIAO NHAU
        $overlapping = $this->groupModel->checkOverlappingRange(
            $data['minSpent'], 
            $data['maxSpent'], 
            null // null = không loại trừ nhóm nào (đang thêm mới)
        );
        
        if ($overlapping) {
            $errors[] = "Khoảng chi tiêu ({$data['minSpent']} - " . ($data['maxSpent'] ?: '∞') . ") bị trùng với nhóm '{$overlapping['groupName']}' ({$overlapping['minSpent']} - " . ($overlapping['maxSpent'] ?: '∞') . ")!";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Thêm nhóm
        $result = $this->groupModel->addGroup($data);
        
        if ($result) {
            return ['success' => true, 'message' => 'Thêm nhóm khách hàng thành công!'];
        }
        
        return ['success' => false, 'errors' => ['Lỗi khi thêm nhóm khách hàng!']];
    }
    
    // Cập nhật nhóm
    public function updateGroup($id, $data) {
        // Validate
        $errors = [];
        
        if (empty($data['groupName'])) {
            $errors[] = "Vui lòng nhập tên nhóm!";
        }
        
        if (empty($data['color'])) {
            $data['color'] = '#6366f1';
        }
        
        if (!isset($data['status'])) {
            $data['status'] = 1;
        }
        
        // Validate minSpent và maxSpent
        if (!empty($data['maxSpent']) && $data['maxSpent'] <= $data['minSpent']) {
            $errors[] = "Chi tiêu tối đa phải lớn hơn chi tiêu tối thiểu!";
        }
        
        // ✅ KIỂM TRA KHOẢNG CHI TIÊU KHÔNG ĐƯỢC GIAO NHAU (loại trừ nhóm hiện tại)
        $overlapping = $this->groupModel->checkOverlappingRange(
            $data['minSpent'], 
            $data['maxSpent'], 
            $id // Loại trừ nhóm đang sửa
        );
        
        if ($overlapping) {
            $errors[] = "Khoảng chi tiêu ({$data['minSpent']} - " . ($data['maxSpent'] ?: '∞') . ") bị trùng với nhóm '{$overlapping['groupName']}' ({$overlapping['minSpent']} - " . ($overlapping['maxSpent'] ?: '∞') . ")!";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Cập nhật nhóm
        $result = $this->groupModel->updateGroup($id, $data);
        
        if ($result) {
            return ['success' => true, 'message' => 'Cập nhật nhóm khách hàng thành công!'];
        }
        
        return ['success' => false, 'errors' => ['Lỗi khi cập nhật nhóm khách hàng!']];
    }
    
    // Xóa nhóm
    public function deleteGroup($id) {
        // Kiểm tra số lượng khách hàng trong nhóm
        $count = $this->groupModel->countCustomersInGroup($id);
        
        if ($count > 0) {
            return [
                'success' => false, 
                'errors' => ["Không thể xóa nhóm đang có $count khách hàng! Vui lòng chuyển khách hàng sang nhóm khác trước."]
            ];
        }
        
        $result = $this->groupModel->deleteGroup($id);
        
        if ($result) {
            return ['success' => true, 'message' => 'Xóa nhóm khách hàng thành công!'];
        }
        
        return ['success' => false, 'errors' => ['Lỗi khi xóa nhóm khách hàng!']];
    }
    
    // Toggle status
    public function toggleStatus($id) {
        $result = $this->groupModel->toggleStatus($id);
        
        if ($result) {
            return ['success' => true, 'message' => 'Đã thay đổi trạng thái nhóm!'];
        }
        
        return ['success' => false, 'message' => 'Lỗi khi thay đổi trạng thái!'];
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
