<?php
/**
 * Controller: Chat (Admin)
 * File: controller/admin/cChat.php
 * Xử lý logic nghiệp vụ và phân quyền cho quản lý chat
 */

require_once __DIR__ . '/../../model/mChat.php';

class cChat {
    private $chatModel;
    
    public function __construct() {
        $this->chatModel = new Chat();
    }
    
    /**
     * Lấy tất cả hội thoại
     * @param int $currentRoleID - Vai trò người dùng (1=Chủ DN, 2=NVQT, 3=NVBH, 4=NVCSKH)
     * @return array
     */
    public function getAllConversations($currentRoleID = null) {
        // PHÂN QUYỀN: Nhân viên Bán Hàng KHÔNG được truy cập Chat
        if ($currentRoleID == 3) {
            return [
                'success' => false,
                'message' => 'Nhân viên Bán Hàng không có quyền truy cập Chat!'
            ];
        }
        
        return [
            'success' => true,
            'data' => $this->chatModel->getAllConversations()
        ];
    }
    
    /**
     * Lấy tin nhắn của 1 khách hàng
     * @param int $customerID
     * @param int $currentRoleID
     * @return array
     */
    public function getMessagesByCustomer($customerID, $currentRoleID = null) {
        // PHÂN QUYỀN: Nhân viên Bán Hàng KHÔNG được truy cập Chat
        if ($currentRoleID == 3) {
            return [
                'success' => false,
                'message' => 'Nhân viên Bán Hàng không có quyền truy cập Chat!'
            ];
        }
        
        return [
            'success' => true,
            'data' => $this->chatModel->getMessagesByCustomer($customerID)
        ];
    }
    
    /**
     * Gửi tin nhắn phản hồi
     * @param array $data - ['customerID', 'content']
     * @param int $currentUserID
     * @param int $currentRoleID
     * @return array
     */
    public function sendMessage($data, $currentUserID, $currentRoleID = null) {
        // PHÂN QUYỀN: Nhân viên Bán Hàng KHÔNG được truy cập Chat
        if ($currentRoleID == 3) {
            return [
                'success' => false,
                'message' => 'Nhân viên Bán Hàng không có quyền gửi tin nhắn!'
            ];
        }
        
        // Validate
        $errors = [];
        
        if (empty($data['customerID'])) {
            $errors[] = "Vui lòng chọn khách hàng!";
        }
        
        if (empty($data['content'])) {
            $errors[] = "Vui lòng nhập nội dung tin nhắn!";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Gửi tin nhắn
        $result = $this->chatModel->sendMessage(
            $data['customerID'],
            $currentUserID,
            $data['content']
        );
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Đã gửi tin nhắn thành công!'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Lỗi khi gửi tin nhắn!'
        ];
    }
    
    /**
     * Xóa tin nhắn
     * @param int $chatID
     * @param int $currentRoleID
     * @return array
     */
    public function deleteMessage($chatID, $currentRoleID = null) {
        // PHÂN QUYỀN: Chỉ Chủ DN và NVCSKH được xóa tin nhắn
        if ($currentRoleID == 3) {
            return [
                'success' => false,
                'message' => 'Nhân viên Bán Hàng không có quyền xóa tin nhắn!'
            ];
        }
        
        if ($currentRoleID == 2) {
            return [
                'success' => false,
                'message' => 'Nhân viên Quản Trị không có quyền xóa tin nhắn!'
            ];
        }
        
        $result = $this->chatModel->deleteMessage($chatID);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Đã xóa tin nhắn thành công!'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Lỗi khi xóa tin nhắn!'
        ];
    }
    
    /**
     * Xóa hội thoại
     * @param int $customerID
     * @param int $currentRoleID
     * @return array
     */
    public function deleteConversation($customerID, $currentRoleID = null) {
        // PHÂN QUYỀN: Chỉ Chủ DN và NVCSKH được xóa hội thoại
        if ($currentRoleID == 3) {
            return [
                'success' => false,
                'message' => 'Nhân viên Bán Hàng không có quyền xóa hội thoại!'
            ];
        }
        
        if ($currentRoleID == 2) {
            return [
                'success' => false,
                'message' => 'Nhân viên Quản Trị không có quyền xóa hội thoại!'
            ];
        }
        
        $result = $this->chatModel->deleteConversation($customerID);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Đã xóa hội thoại thành công!'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Lỗi khi xóa hội thoại!'
        ];
    }
    
    /**
     * Đếm tổng số tin nhắn
     * @return int
     */
    public function countMessages() {
        return $this->chatModel->countMessages();
    }
    
    /**
     * Đếm tin nhắn chưa đọc
     * @return int
     */
    public function countUnreadMessages() {
        return $this->chatModel->countUnreadMessages();
    }
    
    /**
     * Tìm kiếm hội thoại
     * @param string $keyword
     * @param int $currentRoleID
     * @return array
     */
    public function searchConversations($keyword, $currentRoleID = null) {
        // PHÂN QUYỀN: Nhân viên Bán Hàng KHÔNG được truy cập Chat
        if ($currentRoleID == 3) {
            return [
                'success' => false,
                'message' => 'Nhân viên Bán Hàng không có quyền tìm kiếm Chat!'
            ];
        }
        
        return [
            'success' => true,
            'data' => $this->chatModel->searchConversations($keyword)
        ];
    }
}
?>
