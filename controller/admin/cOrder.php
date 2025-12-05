<?php
/**
 * Controller: cOrder (Admin version)
 * Xá»­ lÃ½ logic nghiá»‡p vá»¥ vÃ  validation cho quáº£n lÃ½ Ä‘Æ¡n hÃ ng (Admin)
 */

require_once __DIR__ . '/../../model/mOrder.php';

class cOrder {
    private $orderModel;
    
    public function __construct() {
        $this->orderModel = new Order();
    }
    
    /**
     * Láº¥y táº¥t cáº£ Ä‘Æ¡n hÃ ng (Admin)
     */
    public function getAllOrders() {
        return $this->orderModel->getAllOrders();
    }
    
    /**
     * Láº¥y Ä‘Æ¡n hÃ ng theo ID (Admin)
     */
    public function getOrderById($id) {
        return $this->orderModel->getOrderById($id);
    }
    
    /**
     * Láº¥y chi tiáº¿t Ä‘Æ¡n hÃ ng (Admin)
     */
    public function getOrderDetails($orderID) {
        return $this->orderModel->getOrderDetails($orderID);
    }
    
    /**
     * Cáº­p nháº­t tráº¡ng thÃ¡i Ä‘Æ¡n hÃ ng (Admin)
     * @param int $id - ID Ä‘Æ¡n hÃ ng
     * @param array $data - ['paymentStatus' => string, 'deliveryStatus' => string]
     * @param int $currentUserID - ID nhÃ¢n viÃªn hiá»‡n táº¡i
     * @param int $currentRoleID - Vai trÃ² hiá»‡n táº¡i (1=Chá»§ DN, 2=NVQT, 3=NVBH, 4=NVCSKH)
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateOrderStatus($id, $data, $currentUserID = null, $currentRoleID = null) {
        $errors = [];
        
        // Validate: Trạng thái thanh toán
        $validPaymentStatuses = ['Chờ thanh toán', 'Đã thanh toán', 'Đã hủy'];
        if (empty($data['paymentStatus']) || !in_array($data['paymentStatus'], $validPaymentStatuses)) {
            $errors[] = "Trạng thái thanh toán không hợp lệ!";
        }
        
        // Validate: Trạng thái giao hàng
        $validDeliveryStatuses = ['Chờ xử lý', 'Đang giao', 'Hoàn thành', 'Đã hủy'];
        if (empty($data['deliveryStatus']) || !in_array($data['deliveryStatus'], $validDeliveryStatuses)) {
            $errors[] = "Trạng thái giao hàng không hợp lệ!";
        }
        
        // Kiểm tra quyền hạn theo vai trò
        if ($currentRoleID == 3) { // Nhân viên bán hàng
            // Lấy thông tin đơn hàng
            $order = $this->orderModel->getOrderById($id);
            
            // Chỉ được sửa đơn hàng do mình tạo
            if (!$order || $order['userID'] != $currentUserID) {
                return [
                    'success' => false,
                    'message' => 'Bạn chỉ được cập nhật đơn hàng do chính mình tạo!'
                ];
            }
            
            // Chỉ được cập nhật: Chờ thanh toán -> Đã thanh toán
            if ($data['paymentStatus'] != 'Chờ thanh toán' && $data['paymentStatus'] != 'Đã thanh toán') {
                $errors[] = "Bạn chỉ được cập nhật trạng thái: Chờ thanh toán hoặc Đã thanh toán";
            }
            
            // Chỉ được cập nhật: Chờ xử lý -> Đang giao
            if ($data['deliveryStatus'] != 'Chờ xử lý' && $data['deliveryStatus'] != 'Đang giao') {
                $errors[] = "Bạn chỉ được cập nhật trạng thái: Chờ xử lý hoặc Đang giao";
            }
        } else if ($currentRoleID == 4) { // Nhân viên CSKH
            return [
                'success' => false,
                'message' => 'Nhân viên CSKH không có quyền cập nhật đơn hàng!'
            ];
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Cập nhật trạng thái
        $result = $this->orderModel->updateOrderStatus(
            $id,
            $data['paymentStatus'],
            $data['deliveryStatus']
        );
        
        if ($result) {
            return [
                'success' => true,
                'message' => "Đã cập nhật trạng thái đơn hàng thành công!"
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Lỗi khi cập nhật trạng thái đơn hàng!'
            ];
        }
    }
    
    /**
     * Lấy thống kê đơn hàng theo trạng thái (Admin)
     */
    public function getOrderStats() {
        return $this->orderModel->getOrderStats();
    }
    
    /**
     * Äáº¿m Ä‘Æ¡n hÃ ng theo tráº¡ng thÃ¡i (Admin)
     */
    public function countByStatus($status) {
        return $this->orderModel->countByStatus($status);
    }
    
    /**
     * Tìm kiếm đơn hàng theo mã đơn hàng hoặc số điện thoại (Admin)
     * @param string $keyword - Từ khóa tìm kiếm (mã đơn hàng hoặc số điện thoại)
     * @return array - Danh sách đơn hàng
     */
    public function searchOrders($keyword = null) {
        return $this->orderModel->searchOrders($keyword);
    }
}
?>
