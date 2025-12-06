<?php
/**
 * API Hủy đơn hàng từ phía khách hàng
 * File: api/cancel_order.php
 */

// Sử dụng session name giống frontend
if (session_status() === PHP_SESSION_NONE) {
    session_name('GODIFA_USER_SESSION');
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id']) || !isset($_SESSION['is_customer_logged_in'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để thực hiện thao tác này!'
    ]);
    exit;
}

require_once __DIR__ . '/../model/mOrder.php';
require_once __DIR__ . '/../model/database.php';

// Chỉ chấp nhận POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không hợp lệ!'
    ]);
    exit;
}

$customerID = $_SESSION['customer_id'];
$orderID = intval($_POST['orderID'] ?? 0);
$cancelReason = trim($_POST['cancelReason'] ?? '');
$bankAccount = trim($_POST['bankAccount'] ?? '');
$bankName = trim($_POST['bankName'] ?? '');
$accountHolder = trim($_POST['accountHolder'] ?? '');

// Validate input
if (empty($orderID)) {
    echo json_encode([
        'success' => false,
        'message' => 'Mã đơn hàng không hợp lệ!'
    ]);
    exit;
}

if (empty($cancelReason)) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng nhập lý do hủy đơn!'
    ]);
    exit;
}

// Kết nối database
$db = Database::getInstance();
$conn = $db->connect();
$orderModel = new Order();

// Lấy thông tin đơn hàng
$order = $orderModel->getOrderById($orderID);

if (!$order) {
    echo json_encode([
        'success' => false,
        'message' => 'Không tìm thấy đơn hàng!'
    ]);
    exit;
}

// Kiểm tra quyền sở hữu đơn hàng
if ($order['customerID'] != $customerID) {
    echo json_encode([
        'success' => false,
        'message' => 'Bạn không có quyền hủy đơn hàng này!'
    ]);
    exit;
}

// Kiểm tra trạng thái đơn hàng - KHÔNG cho hủy nếu đã hủy hoặc đã hoàn thành
if ($order['paymentStatus'] === 'Đã hủy') {
    echo json_encode([
        'success' => false,
        'message' => 'Đơn hàng này đã bị hủy trước đó!'
    ]);
    exit;
}

if ($order['deliveryStatus'] === 'Hoàn thành') {
    echo json_encode([
        'success' => false,
        'message' => 'Không thể hủy đơn hàng đã hoàn thành!'
    ]);
    exit;
}

// Kiểm tra nếu đơn đã được admin xác nhận (đang vận chuyển/giao)
// Logic mới: Chỉ CẤM hủy khi admin ĐÃ XÁC NHẬN đơn
if (in_array($order['deliveryStatus'], ['Đang tiến hành vận chuyển', 'Đang giao', 'Đã giao'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Đơn hàng đã được xác nhận và đang xử lý. Vui lòng liên hệ CSKH để được hỗ trợ!'
    ]);
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// LOGIC HỦY ĐỠN - CẬP NHẬT 2025-12-05
// ═══════════════════════════════════════════════════════════════════════════
// Nếu đến đây nghĩa là: deliveryStatus = "Chờ xử lý" hoặc "Chờ xác nhận"
// → Admin CHƯA XÁC NHẬN đơn → Cho phép khách hàng hủy
// 
// Trường hợp cần xử lý:
// 1. COD (chưa thanh toán) → Hủy trực tiếp, không cần form
// 2. QR chưa thanh toán → Hủy trực tiếp, không cần form
// 3. QR đã thanh toán → YÊU CẦU điền form hoàn tiền (bank info + ảnh)
// ═══════════════════════════════════════════════════════════════════════════

// Xử lý upload hình ảnh chứng minh (nếu đơn đã thanh toán QR)
$proofImage = null;
$needRefund = false;

if ($order['paymentStatus'] === 'Đã thanh toán' && $order['paymentMethod'] === 'QR') {
    // Trường hợp 3: Đã chuyển khoản QR → Cần hoàn tiền
    $needRefund = true;
    
    // Kiểm tra thông tin ngân hàng
    if (empty($bankAccount) || empty($bankName) || empty($accountHolder)) {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng cung cấp đầy đủ thông tin ngân hàng để hoàn tiền!'
        ]);
        exit;
    }
    
    // Xử lý upload hình ảnh
    if (isset($_FILES['proofImage']) && $_FILES['proofImage']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        $fileType = $_FILES['proofImage']['type'];
        $fileSize = $_FILES['proofImage']['size'];
        
        if (!in_array($fileType, $allowedTypes)) {
            echo json_encode([
                'success' => false,
                'message' => 'Chỉ chấp nhận file ảnh JPG, JPEG, PNG!'
            ]);
            exit;
        }
        
        if ($fileSize > $maxSize) {
            echo json_encode([
                'success' => false,
                'message' => 'Kích thước ảnh không được vượt quá 5MB!'
            ]);
            exit;
        }
        
        // Tạo tên file unique
        $extension = pathinfo($_FILES['proofImage']['name'], PATHINFO_EXTENSION);
        $fileName = 'refund_proof_' . $orderID . '_' . time() . '.' . $extension;
        
        // Đường dẫn lưu file (tương đối với root)
        $uploadDir = __DIR__ . '/../image/refund_proofs/';
        
        // Tạo thư mục nếu chưa có
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $uploadPath = $uploadDir . $fileName;
        
        // Di chuyển file
        if (move_uploaded_file($_FILES['proofImage']['tmp_name'], $uploadPath)) {
            $proofImage = 'image/refund_proofs/' . $fileName;
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi khi upload hình ảnh. Vui lòng thử lại!'
            ]);
            exit;
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng cung cấp hình ảnh chứng minh chuyển khoản!'
        ]);
        exit;
    }
}

// Bắt đầu transaction
mysqli_begin_transaction($conn);

try {
    // 1. Cập nhật trạng thái đơn hàng
    // - Nếu ĐÃ THANH TOÁN QR: Giữ trạng thái để admin xác nhận hoàn tiền, GIỮ NGUYÊN paymentDate
    // - Nếu CHƯA THANH TOÁN: Hủy luôn
    
    if ($needRefund) {
        // Trường hợp QR đã thanh toán: GIỮ LẠI paymentStatus và paymentDate
        // Chỉ đổi deliveryStatus để đánh dấu là "yêu cầu hủy/hoàn tiền"
        $sql = "UPDATE `order` SET 
                deliveryStatus = 'Chờ xử lý hoàn tiền',
                cancelReason = ?,
                cancelledAt = NOW(),
                cancelledBy = 'customer'
                WHERE orderID = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $cancelReason, $orderID);
    } else {
        // Trường hợp COD hoặc QR chưa thanh toán: Hủy hoàn toàn
        $sql = "UPDATE `order` SET 
                paymentStatus = 'Đã hủy',
                deliveryStatus = 'Đã hủy',
                cancelReason = ?,
                cancelledAt = NOW(),
                cancelledBy = 'customer'
                WHERE orderID = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $cancelReason, $orderID);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Lỗi khi cập nhật trạng thái đơn hàng!');
    }
    
    // 2. Hoàn lại tồn kho
    $sqlDetails = "SELECT productID, quantity FROM order_details WHERE orderID = ?";
    $stmtDetails = mysqli_prepare($conn, $sqlDetails);
    mysqli_stmt_bind_param($stmtDetails, "i", $orderID);
    mysqli_stmt_execute($stmtDetails);
    $resultDetails = mysqli_stmt_get_result($stmtDetails);
    
    while ($item = mysqli_fetch_assoc($resultDetails)) {
        $sqlUpdateStock = "UPDATE product SET stockQuantity = stockQuantity + ? WHERE productID = ?";
        $stmtStock = mysqli_prepare($conn, $sqlUpdateStock);
        mysqli_stmt_bind_param($stmtStock, "ii", $item['quantity'], $item['productID']);
        
        if (!mysqli_stmt_execute($stmtStock)) {
            throw new Exception('Lỗi khi hoàn lại tồn kho!');
        }
    }
    
    // 3. Nếu cần hoàn tiền, lưu vào bảng refund_requests
    if ($needRefund) {
        $sqlRefund = "INSERT INTO refund_requests 
                     (orderID, customerID, amount, bankAccount, bankName, accountHolder, proofImage, status, createdAt) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'Chờ xử lý', NOW())";
        
        $stmtRefund = mysqli_prepare($conn, $sqlRefund);
        mysqli_stmt_bind_param($stmtRefund, "iidssss", 
            $orderID, 
            $customerID, 
            $order['totalAmount'], 
            $bankAccount, 
            $bankName, 
            $accountHolder, 
            $proofImage
        );
        
        if (!mysqli_stmt_execute($stmtRefund)) {
            throw new Exception('Lỗi khi tạo yêu cầu hoàn tiền!');
        }
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    $responseMessage = 'Hủy đơn hàng thành công!';
    if ($needRefund) {
        $responseMessage .= ' Yêu cầu hoàn tiền của bạn đã được gửi đến bộ phận CSKH. Chúng tôi sẽ xử lý trong vòng 24-48 giờ.';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $responseMessage,
        'needRefund' => $needRefund
    ]);
    
} catch (Exception $e) {
    // Rollback nếu có lỗi
    mysqli_rollback($conn);
    
    // Xóa file ảnh nếu đã upload
    if ($proofImage && file_exists(__DIR__ . '/../' . $proofImage)) {
        unlink(__DIR__ . '/../' . $proofImage);
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
