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
    
    // Láº¥y táº¥t cáº£ nhÃ³m
    public function getAllGroups() {
        return $this->groupModel->getAllGroups();
    }
    
    // Láº¥y nhÃ³m Ä‘ang hoáº¡t Ä‘á»™ng
    public function getActiveGroups() {
        return $this->groupModel->getActiveGroups();
    }
    
    // Láº¥y nhÃ³m theo ID
    public function getGroupById($id) {
        return $this->groupModel->getGroupById($id);
    }
    
    // âŒ XÃ“A CHá»¨C NÄ‚NG THÃŠM NHÃ“M (Cá» Äá»ŠNH 5 Háº NG)
    public function addGroup($data) {
        return [
            'success' => false, 
            'errors' => ['Hệ thống sử dụng 5 hạng cố định. Không thể thêm nhóm mới.']
        ];
    }
    
    // âœ… CHá»ˆ CHO Sá»¬A TÃŠN, MÃ” Táº¢, MÃ€U - KHÃ”NG CHO Sá»¬A minSpent/maxSpent/status
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
        
        // âœ… CHá»ˆ Cáº¬P NHáº¬T: groupName, description, color
        // âŒ KHÃ”NG CHO Sá»¬A: minSpent, maxSpent, status (luÃ´n = 1)
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
    
    // âŒ XÃ“A CHá»¨C NÄ‚NG XÃ“A NHÃ“M (Cá» Äá»ŠNH 5 Háº NG)
    public function deleteGroup($id) {
        return [
            'success' => false,
            'errors' => ['Không thể xóa nhóm! Hệ thống sử dụng 5 hạng cố định.']
        ];
    }
    
    // âŒ XÃ“A CHá»¨C NÄ‚NG TOGGLE STATUS (Cá» Äá»ŠNH LUÃ”N HOáº T Äá»˜NG)
    public function toggleStatus($id) {
        return [
            'success' => false, 
            'message' => 'Không thể thay đổi trạng thái! Tất cả nhóm luôn hoạt động.'
        ];
    }
    
    // Láº¥y thá»‘ng kÃª nhÃ³m
    public function getGroupStats($groupID) {
        return $this->groupModel->getGroupStats($groupID);
    }
    
    // Láº¥y thá»‘ng kÃª táº¥t cáº£ nhÃ³m
    public function getAllGroupStats() {
        return $this->groupModel->getAllGroupStats();
    }
    
    // TÃ¬m kiáº¿m nhÃ³m
    public function searchGroups($keyword) {
        if (empty($keyword)) {
            return $this->getAllGroups();
        }
        return $this->groupModel->searchGroups($keyword);
    }
    
    // Äáº¿m tá»•ng sá»‘ nhÃ³m
    public function countGroups() {
        return $this->groupModel->countGroups();
    }
    
    // Äáº¿m khÃ¡ch hÃ ng trong nhÃ³m
    public function countCustomersInGroup($groupID) {
        return $this->groupModel->countCustomersInGroup($groupID);
    }
}
?>
