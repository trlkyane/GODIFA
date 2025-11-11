<?php 
// session_start();
// File: ../view/orderHistory.php
// Biến $orders đã được extract từ OrderController:history()
// Biến $controller_url được đặt trong Controller hoặc được định nghĩa ở đây để dễ bảo trì
include_once(__DIR__ . '/../layout/header.php');
$controller_url = '/GODIFA/controller/cOrder.php'; // Đảm bảo đường dẫn này khớp với file Controller chính
?>

<div class="main-content p-4 md:p-6">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Lịch Sử Đơn Hàng</h1>
    
    <?php if (empty($orders)): ?>
        <p class="text-gray-500">Bạn chưa có đơn hàng nào.</p>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($orders as $order): ?>
                <div class="order-item bg-white p-5 border rounded-lg shadow-sm">
                    
                    <div class="flex justify-between items-center border-b pb-3 mb-3">
                        <p class="text-lg font-semibold">Đơn hàng #<?php echo htmlspecialchars($order['orderID']); ?></p>
                        
                        <?php 
                            // 🌟 Tối ưu hóa logic màu sắc trạng thái 🌟
                            $status = $order['deliveryStatus'];
                            $status_class = 'bg-secondary text-white'; // Mặc định
                            if ($status == 'Hoàn thành' || $status == 'Đã giao') {
                                $status_class = 'bg-green-500 text-white'; // Dùng màu green cho Thành công
                            } elseif ($status == 'Đã hủy') {
                                $status_class = 'bg-red-500 text-white'; // Thêm màu cho Đã hủy
                            }
                        ?>
                        <span class="<?php echo $status_class; ?> px-2 py-1 rounded text-sm font-medium">
                            <?php echo htmlspecialchars($status); ?>
                        </span>
                    </div>
                    
                    <p class="mb-4">
                        <strong>Ngày đặt:</strong> <?php echo date('d/m/Y', strtotime($order['orderDate'])); ?>
                    </p>
                    <p class="mb-4">
                        <strong>Tổng tiền:</strong> <span class="text-red-600 font-bold"><?php echo number_format($order['totalAmount']); ?> VNĐ</span>
                    </p>

                    <div class="text-right mt-4 pt-3 border-t">
                        
                        <?php 
                            // Sử dụng cờ canReviewAny đã được Controller tính toán
                            if (isset($order['canReviewAny']) && $order['canReviewAny']): 
                        ?>
                            <a href="<?php echo $controller_url; ?>?action=detail&id=<?php echo $order['orderID']; ?>" 
                               class="btn btn-warning bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 text-sm rounded mr-2">
                                <i class="fas fa-star mr-1"></i> Đánh Giá
                            </a>
                        <?php endif; ?>
                        
                        <a href="<?php echo $controller_url; ?>?action=detail&id=<?php echo $order['orderID']; ?>" 
                           class="btn btn-primary bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 text-sm rounded">
                            <i class="fas fa-search mr-1"></i> Xem Chi Tiết
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
include_once(__DIR__ . '/../layout/footer.php');
?>