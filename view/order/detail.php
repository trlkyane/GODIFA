<?php
/**
 * Chi tiết đơn hàng
 * File: view/order/detail.php
 * Chức năng: Hiển thị chi tiết đơn hàng và cho phép đánh giá sản phẩm (nếu đủ điều kiện).
 */

// Kiểm tra đăng nhập
if (session_status() === PHP_SESSION_NONE) {
    session_name('GODIFA_USER_SESSION');
    session_start();
}

if (!isset($_SESSION['customer_id'])) {
    header('Location: /GODIFA/view/auth/customer-login.php');
    exit;
}

require_once __DIR__ . '/../../model/database.php';

$orderID = $_GET['id'] ?? 0;
$customerID = $_SESSION['customer_id'];

if (!$orderID) {
    header('Location: /GODIFA/view/account/order_history.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->connect();

// Lấy thông tin đơn hàng (chỉ của customer này)
$stmt = $conn->prepare("
    SELECT 
        o.orderID,
        o.orderDate,
        o.totalAmount,
        o.shippingFee,
        o.paymentStatus,
        o.paymentMethod,
        o.deliveryStatus,
        o.transactionCode,
        od.recipientName,
        od.recipientPhone,
        od.recipientEmail,
        od.address,
        od.ward,
        od.district,
        od.city,
        od.deliveryNotes
    FROM `order` o
    LEFT JOIN order_delivery od ON o.orderID = od.orderID
    WHERE o.orderID = ? AND o.customerID = ?
");
$stmt->bind_param("ii", $orderID, $customerID);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: /GODIFA/view/account/order_history.php');
    exit;
}

// Lấy chi tiết sản phẩm
$stmt = $conn->prepare("
    SELECT 
        od.productID,
        od.quantity,
        od.price,
        p.productName,
        p.image
    FROM order_details od
    JOIN product p ON od.productID = p.productID
    WHERE od.orderID = ?
");
$stmt->bind_param("i", $orderID);
$stmt->execute();
$rawOrderDetails = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$orderDetails = [];
// Điều kiện đánh giá: Đơn hàng phải ở trạng thái "Hoàn thành" hoặc "Đã giao"
$canReviewOrder = ($order['deliveryStatus'] === 'Hoàn thành' || $order['deliveryStatus'] === 'Đã giao');

// Chuẩn bị statement để kiểm tra từng sản phẩm đã được đánh giá chưa
// ⚠️ LƯU Ý: Bảng review phải tồn tại để câu lệnh này không báo lỗi.
$stmtReview = $conn->prepare("
    SELECT COUNT(*) FROM review 
    WHERE orderID = ? AND productID = ? AND customerID = ?
");

foreach ($rawOrderDetails as $item) {
    $item['canReview'] = false;
    $item['isReviewed'] = false;

    // Chỉ kiểm tra đánh giá nếu đơn hàng đã hoàn thành
    if ($canReviewOrder && $stmtReview) {
        $productID = $item['productID'];
        
        // 1. Kiểm tra sản phẩm đã được đánh giá chưa
        $stmtReview->bind_param("iii", $orderID, $productID, $customerID);
        $stmtReview->execute();
        $isReviewed = $stmtReview->get_result()->fetch_row()[0] > 0;
        
        $item['isReviewed'] = $isReviewed;
        
        // 2. Thiết lập cờ có thể đánh giá (Chỉ khi đã hoàn thành và chưa đánh giá)
        if (!$isReviewed) {
            $item['canReview'] = true;
        }
    }
    $orderDetails[] = $item;
}

if ($stmtReview) {
    $stmtReview->close();
}

// Lịch sử vận chuyển đã bị xóa (simplified)
$shippingHistory = [];

// Lấy thông báo từ Session (nếu có)
$notify_success = $_SESSION['notify_success'] ?? null;
$notify_error = $_SESSION['notify_error'] ?? null;
unset($_SESSION['notify_success'], $_SESSION['notify_error']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi Tiết Đơn Hàng #<?= $orderID ?> - GODIFA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body class="bg-gray-50">
    
    <?php include __DIR__ . '/../layout/header.php'; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-6">
            <a href="/GODIFA/view/account/order_history.php" class="text-indigo-600 hover:text-indigo-800 font-semibold">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại lịch sử đơn hàng
            </a>
        </div>
        
        <?php if ($notify_success): ?>
            <div class="alert alert-success bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <?= htmlspecialchars($notify_success); ?>
            </div>
        <?php endif; ?>
        <?php if ($notify_error): ?>
            <div class="alert alert-danger bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <?= htmlspecialchars($notify_error); ?>
            </div>
        <?php endif; ?>
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-wrap items-center justify-between mb-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">
                        <i class="fas fa-receipt text-indigo-600 mr-2"></i>
                        Đơn hàng #<?= $orderID ?>
                    </h1>
                    <p class="text-gray-600 mt-2">
                        <i class="far fa-clock mr-1"></i>
                        Ngày đặt: <?= date('d/m/Y H:i', strtotime($order['orderDate'])) ?>
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-3xl font-bold text-indigo-600">
                        <?= number_format($order['totalAmount'], 0, ',', '.') ?>₫
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-gray-700 mb-2">💳 Thanh toán</h3>
                    <?php
                    $paymentColors = [
                        'Đã thanh toán' => 'bg-green-100 text-green-800',
                        'Chờ thanh toán' => 'bg-yellow-100 text-yellow-800',
                        'Đã hủy' => 'bg-red-100 text-red-800'
                    ];
                    $paymentColor = $paymentColors[$order['paymentStatus']] ?? 'bg-gray-100 text-gray-800';
                    ?>
                    <span class="inline-block px-4 py-2 rounded-full text-sm font-semibold <?= $paymentColor ?> mb-2">
                        <?= $order['paymentStatus'] ?>
                    </span>
                    <p class="text-sm text-gray-600">Phương thức: <?= $order['paymentMethod'] ?></p>
                    <?php if ($order['transactionCode']): ?>
                    <p class="text-sm text-gray-600 font-mono mt-1">Mã GD: <?= $order['transactionCode'] ?></p>
                    <?php endif; ?>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-gray-700 mb-2">🚚 Giao hàng</h3>
                    <?php
                    $deliveryColors = [
                        'Hoàn thành' => 'bg-green-100 text-green-800',
                        'Đang tiến hành vận chuyển' => 'bg-blue-100 text-blue-800',
                        'Chờ xác nhận' => 'bg-yellow-100 text-yellow-800',
                        'Đã hủy' => 'bg-red-100 text-red-800',
                        'Đã giao' => 'bg-green-100 text-green-800',
                        'Đang giao' => 'bg-blue-100 text-blue-800',
                        'Đang xử lý' => 'bg-blue-100 text-blue-800',
                        'Chờ xử lý' => 'bg-yellow-100 text-yellow-800',
                    ];
                    $deliveryColor = $deliveryColors[$order['deliveryStatus']] ?? 'bg-gray-100 text-gray-800';
                    ?>
                    <span class="inline-block px-4 py-2 rounded-full text-sm font-semibold <?= $deliveryColor ?> mb-2">
                        <?= $order['deliveryStatus'] ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-box mr-2"></i>Chi tiết sản phẩm
                    </h2>
                    
                    <div class="hidden md:flex items-center text-sm font-semibold text-gray-600 bg-gray-100 p-3 rounded-t-lg">
                        <span class="w-1/2">Sản phẩm</span>
                        <span class="w-1/6 text-center">SL</span>
                        <span class="w-1/6 text-right">Tổng giá</span>
                        <span class="w-1/6 text-center">Đánh giá</span>
                    </div>

                    <div class="space-y-3">
                        <?php foreach ($orderDetails as $item): ?>
                        <div class="flex flex-wrap md:flex-nowrap items-center bg-gray-50 p-4 rounded-lg shadow-sm hover:shadow-md transition">
                            <img src="/GODIFA/image/<?= htmlspecialchars($item['image']) ?>" 
                                 alt="<?= htmlspecialchars($item['productName']) ?>"
                                 class="w-16 h-16 object-cover rounded-lg mr-4 flex-shrink-0">
                            
                            <div class="flex-1 min-w-0 pr-4 w-full md:w-1/2">
                                <p class="font-semibold text-gray-800 truncate"><?= htmlspecialchars($item['productName']) ?></p>
                                <p class="text-gray-600 text-sm mt-1">
                                    <?= number_format($item['price'], 0, ',', '.') ?>₫ / SP
                                </p>
                            </div>
                            
                            <p class="text-gray-700 text-center font-medium w-1/4 md:w-1/6 mt-2 md:mt-0">x<?= $item['quantity'] ?></p>

                            <p class="font-bold text-indigo-600 text-right w-1/4 md:w-1/6 mt-2 md:mt-0">
                                <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>₫
                            </p>

                            <div class="w-full md:w-1/6 text-center mt-3 md:mt-0 pl-4">
                                <?php if (isset($item['isReviewed']) && $item['isReviewed']): ?>
                                    <span class="inline-block bg-indigo-100 text-indigo-700 px-2 py-1 text-xs font-semibold rounded-full">Đã đánh giá</span>
                                
                                <?php elseif (isset($item['canReview']) && $item['canReview']): ?>
                                    <button 
                                    class="review-btn bg-orange-500 hover:bg-orange-600 text-white px-3 py-1 text-sm rounded transition duration-200 shadow-sm"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#reviewModal"
                                        data-product-id="<?php echo htmlspecialchars($item['productID']); ?>"
                                        data-product-name="<?php echo htmlspecialchars($item['productName']); ?>"
                                        data-order-id="<?php echo htmlspecialchars($orderID); ?>"
                                    >
                                        Đánh Giá Ngay
                                    </button>
                                
                                <?php else: ?>
                                    <span class="text-gray-400 text-xs">Chưa đủ ĐK</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="border-t mt-4 pt-4 space-y-2">
                        <div class="flex justify-between text-gray-600">
                            <span>Tạm tính:</span>
                            <span><?= number_format($order['totalAmount'] - ($order['shippingFee'] ?? 0), 0, ',', '.') ?>₫</span>
                        </div>
                        <?php if ($order['shippingFee']): ?>
                        <div class="flex justify-between text-gray-600">
                            <span>Phí vận chuyển:</span>
                            <span><?= number_format($order['shippingFee'], 0, ',', '.') ?>₫</span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between text-xl font-bold text-gray-800 pt-2 border-t">
                            <span>Tổng cộng:</span>
                            <span class="text-indigo-600"><?= number_format($order['totalAmount'], 0, ',', '.') ?>₫</span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($shippingHistory)): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-route mr-2"></i>Lịch sử vận chuyển
                    </h2>
                    <div class="relative">
                        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                        <div class="space-y-4">
                            <?php foreach ($shippingHistory as $index => $history): ?>
                            <div class="relative pl-10">
                                <div class="absolute left-0 w-8 h-8 rounded-full flex items-center justify-center <?= $index === 0 ? 'bg-green-500' : 'bg-gray-300' ?>">
                                    <i class="fas fa-check text-white text-xs"></i>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <p class="font-semibold text-gray-800"><?= htmlspecialchars($history['status']) ?></p>
                                    <?php if ($history['description']): ?>
                                    <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($history['description']) ?></p>
                                    <?php endif; ?>
                                    <?php if ($history['location']): ?>
                                    <p class="text-sm text-gray-500 mt-1">
                                        <i class="fas fa-map-marker-alt mr-1"></i><?= htmlspecialchars($history['location']) ?>
                                    </p>
                                    <?php endif; ?>
                                    <p class="text-xs text-gray-400 mt-2">
                                        <i class="far fa-clock mr-1"></i><?= date('d/m/Y H:i', strtotime($history['createdAt'])) ?>
                                    </p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-user mr-2"></i>Người nhận
                    </h2>
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-600">Họ tên:</p>
                            <p class="font-semibold"><?= htmlspecialchars($order['recipientName'] ?? 'Chưa cập nhật') ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Số điện thoại:</p>
                            <p class="font-semibold"><?= htmlspecialchars($order['recipientPhone'] ?? '') ?></p>
                        </div>
                        <?php if (!empty($order['recipientEmail'])): ?>
                        <div>
                            <p class="text-sm text-gray-600">Email:</p>
                            <p class="font-semibold"><?= htmlspecialchars($order['recipientEmail']) ?></p>
                        </div>
                        <?php endif; ?>
                        <div>
                            <p class="text-sm text-gray-600">Địa chỉ:</p>
                            <p class="font-semibold">
                                <?= htmlspecialchars($order['address'] ?? '') ?><?= !empty($order['address']) ? ',' : '' ?>
                                <?= htmlspecialchars($order['ward'] ?? '') ?><?= !empty($order['ward']) ? ',' : '' ?>
                                <?= htmlspecialchars($order['district'] ?? '') ?><?= !empty($order['district']) ? ',' : '' ?>
                                <?= htmlspecialchars($order['city'] ?? '') ?>
                            </p>
                        </div>
                        <?php if (!empty($order['deliveryNotes'])): ?>
                        <div>
                            <p class="text-sm text-gray-600">Ghi chú:</p>
                            <p class="text-gray-700"><?= htmlspecialchars($order['deliveryNotes']) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-tools mr-2"></i>Thao tác
                    </h2>
                    <div class="space-y-2">
                        <?php if ($order['paymentStatus'] === 'Chờ thanh toán'): ?>
                        <a href="/GODIFA/view/cart/checkout_qr.php?orderID=<?= $orderID ?>" 
                           class="block w-full text-center bg-yellow-600 text-white px-4 py-3 rounded-lg hover:bg-yellow-700 transition font-semibold">
                            <i class="fas fa-credit-card mr-2"></i>Thanh Toán Ngay
                        </a>
                        <?php endif; ?>
                        
                        <button onclick="window.print()" 
                                class="block w-full text-center bg-gray-600 text-white px-4 py-3 rounded-lg hover:bg-gray-700 transition font-semibold">
                            <i class="fas fa-print mr-2"></i>In Đơn Hàng
                        </button>
                        
                        <a href="mailto:support@godifa.com?subject=Hỗ trợ đơn hàng #<?= $orderID ?>" 
                           class="block w-full text-center bg-indigo-600 text-white px-4 py-3 rounded-lg hover:bg-indigo-700 transition font-semibold">
                            <i class="fas fa-envelope mr-2"></i>Liên Hệ Hỗ Trợ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                
                <div class="modal-header">
                    <h5 class="modal-title font-bold text-lg" id="reviewModalLabel">Đánh giá Sản phẩm: <span id="modalProductName" class="text-indigo-600"></span></h5>
                    <button type="button" class="text-gray-400 hover:text-gray-600" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="modal-body">
                    <form id="reviewForm" action="/GODIFA/controller/cOrder.php?action=submit_review" method="POST">
                        
                        <input type="hidden" name="order_id" id="modalOrderId" value="">
                        <input type="hidden" name="product_id" id="modalProductId" value="">
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Chất lượng sản phẩm:</label>
                            <div class="rating-stars flex justify-start space-x-1" id="starRatingContainer">
                                <?php 
                                for ($i = 1; $i <= 5; $i++): 
                                ?>
                                    <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required class="hidden" />
                                    
                                    <label for="star<?php echo $i; ?>" 
                                            class="text-3xl cursor-pointer text-gray-300 transition duration-150 flex items-center hover:text-yellow-400" 
                                            data-value="<?php echo $i; ?>">
                                        &#9733; 
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="comment" class="block text-gray-700 text-sm font-semibold mb-2">Bình luận:</label>
                            <textarea id="comment" name="comment" rows="4" 
                                      class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" 
                                      maxlength="500" placeholder="Hãy chia sẻ nhận xét của bạn..." required></textarea>
                        </div>

                        <div class="text-right">
                            <button type="button" class="btn btn-secondary bg-gray-400 text-white px-3 py-2 rounded mr-2 hover:bg-gray-500" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-primary bg-orange-500 hover:bg-orange-600 text-white px-3 py-2 rounded">Gửi Đánh Giá</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const reviewModal = document.getElementById('reviewModal');
        if (!reviewModal) return;

        const reviewForm = document.getElementById('reviewForm');
        const modalProductId = document.getElementById('modalProductId');
        const modalOrderId = document.getElementById('modalOrderId');
        const modalProductName = document.getElementById('modalProductName');
        const starContainer = document.getElementById('starRatingContainer');
        const starLabels = reviewModal.querySelectorAll('#starRatingContainer label');

        // Định nghĩa màu sắc (sử dụng màu Tailwind hoặc RGB)
        const COLOR_YELLOW = 'rgb(255, 193, 7)'; 
        const COLOR_HOVER = 'rgb(253, 224, 71)'; 
        const COLOR_GRAY = 'rgb(209, 213, 219)'; 

        function highlightStars(value, color) {
            starLabels.forEach(label => {
                const lValue = parseInt(label.getAttribute('data-value'));
                label.style.color = lValue <= value ? color : COLOR_GRAY;
            });
        }

        // 1. Lắng nghe sự kiện khi Modal được hiển thị (Bootstrap event)
        reviewModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; 

            const productId = button.getAttribute('data-product-id');
            const orderId = button.getAttribute('data-order-id');
            const productName = button.getAttribute('data-product-name');

            modalProductId.value = productId;
            modalOrderId.value = orderId;
            modalProductName.textContent = productName;
            
            // Reset form và rating khi modal mở
            reviewForm.reset();
            highlightStars(0, COLOR_GRAY); 
        });
        
        // 2. Logic cho việc Highlight Sao
        starLabels.forEach(label => {
            const ratingInput = document.getElementById(label.getAttribute('for'));
            const ratingValue = parseInt(label.getAttribute('data-value'));
            
            // A. Xử lý CLICK/CHANGE (Chọn sao)
            ratingInput.addEventListener('change', () => {
                highlightStars(ratingValue, COLOR_YELLOW);
            });

            // B. Xử lý HOVER (Rê chuột)
            label.addEventListener('mouseover', () => {
                highlightStars(ratingValue, COLOR_HOVER);
            });

            label.addEventListener('mouseout', () => {
                const checkedStar = starContainer.querySelector('input[name="rating"]:checked');
                
                if (checkedStar) {
                    const selectedValue = parseInt(checkedStar.value);
                    highlightStars(selectedValue, COLOR_YELLOW);
                } else {
                    highlightStars(0, COLOR_GRAY);
                }
            });
        });
    });
    </script>
    <?php include __DIR__ . '/../layout/footer.php'; ?>

</body>
</html>