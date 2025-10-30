<?php
/**
 * Controller: cOrder (Admin version)
 * Xử lý logic nghiệp vụ và validation cho quản lý đơn hàng (Admin)
 */

require_once __DIR__ . '/../../model/mOrder.php';

class cOrder {
    private $orderModel;
    
    public function __construct() {
        $this->orderModel = new Order();
    }
    
    /**
     * Lấy tất cả đơn hàng (Admin)
     */
    public function getAllOrders() {
        return $this->orderModel->getAllOrders();
    }
    
    /**
     * Lấy đơn hàng theo ID (Admin)
     */
    public function getOrderById($id) {
        return $this->orderModel->getOrderById($id);
    }
    
    /**
     * Lấy chi tiết đơn hàng (Admin)
     */
    public function getOrderDetails($orderID) {
        return $this->orderModel->getOrderDetails($orderID);
    }
    
    /**
     * Cập nhật trạng thái đơn hàng (Admin)
     * @param int $id - ID đơn hàng
     * @param array $data - ['paymentStatus' => string, 'deliveryStatus' => string]
     * @param int $currentUserID - ID nhân viên hiện tại
     * @param int $currentRoleID - Vai trò hiện tại (1=Chủ DN, 2=NVQT, 3=NVBH, 4=NVCSKH)
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
     * Đếm đơn hàng theo trạng thái (Admin)
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
