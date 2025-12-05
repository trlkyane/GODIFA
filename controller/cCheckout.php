<?php
/**
 * Create Order and Redirect to QR Payment
 * File: controller/cCheckout.php
 * Updated: 2025-11-05 - Support 2 tables (order + order_delivery)
 */

// Khởi động session với tên chuẩn
if (session_status() === PHP_SESSION_NONE) {
    session_name('GODIFA_USER_SESSION');
    session_start();
}

require_once __DIR__ . '/../model/database.php';
require_once __DIR__ . '/cCartSync.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id']) || !isset($_SESSION['is_customer_logged_in'])) {
    $_SESSION['checkout_redirect'] = true;
    header('Location: ' . BASE_URL . 'view/auth/customer-login.php');
    exit;
}

// Check POST data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'view/cart/checkout.php');
    exit;
}

// Kiểm tra giỏ hàng
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: ' . BASE_URL . 'view/cart/viewcart.php');
    exit;
}

$customerID = $_SESSION['customer_id']; // Sửa từ customerID thành customer_id

// Lấy thông tin từ form
$fullName = trim($_POST['fullName'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$city = trim($_POST['city'] ?? ''); // Tên tỉnh (ví dụ: "Hồ Chí Minh")
$district = trim($_POST['district'] ?? ''); // Tên quận (ví dụ: "Quận Tân Phú")
$ward = trim($_POST['ward'] ?? ''); // Tên phường (ví dụ: "Phường Tây Thạnh")
$address = trim($_POST['address'] ?? ''); // Địa chỉ chi tiết
$notes = trim($_POST['notes'] ?? '');
$paymentMethod = $_POST['paymentMethod'] ?? 'QR';

// ✅ NEW: Lấy thông tin GHN IDs, phí ship và loại dịch vụ
$provinceId = (int)($_POST['provinceId'] ?? 0);
$districtId = (int)($_POST['districtId'] ?? 0);
$wardCode = trim($_POST['wardCode'] ?? '');
$shippingFee = (float)($_POST['shippingFee'] ?? 0); // Use float for decimal
$serviceTypeId = (int)($_POST['serviceTypeId'] ?? 2); // Default: Tiêu chuẩn

// Validate
$errors = [];
if (empty($fullName)) $errors[] = "Vui lòng nhập họ tên";
if (empty($phone)) $errors[] = "Vui lòng nhập số điện thoại";
if (empty($address)) $errors[] = "Vui lòng nhập địa chỉ";
if (empty($ward)) $errors[] = "Vui lòng chọn phường/xã";
if (empty($district)) $errors[] = "Vui lòng chọn quận/huyện";
if (empty($city)) $errors[] = "Vui lòng chọn tỉnh/thành phố";

if (!empty($errors)) {
    $_SESSION['checkout_errors'] = $errors;
    header('Location: ' . BASE_URL . 'view/cart/checkout.php');
    exit;
}

// Tính tổng tiền từ giỏ hàng
$totalAmount = 0;
foreach ($_SESSION['cart'] as $item) {
    $totalAmount += $item['price'] * $item['quantity'];
}

// ✅ NEW: Lấy voucher từ form (thay vì session)
$voucherID = !empty($_POST['voucherID']) ? (int)$_POST['voucherID'] : null;
$discountAmount = !empty($_POST['discountAmount']) ? (int)$_POST['discountAmount'] : 0;

// ✅ Tính toán: Subtotal + ShippingFee - Discount
$subtotal = $totalAmount;
$finalAmount = $subtotal + $shippingFee - $discountAmount;

// ✅ CRITICAL FIX: Không cho phép số âm - set minimum = 0
if ($finalAmount < 0) {
    $finalAmount = 0;
}

// Debug log (optional - comment out in production)
$debugLog = __DIR__ . '/../logs/checkout.log';
if (is_dir(dirname($debugLog))) {
    file_put_contents($debugLog, sprintf(
        "[%s] POST Data: shippingFee=%s, voucherID=%s, discountAmount=%s\n",
        date('Y-m-d H:i:s'),
        $_POST['shippingFee'] ?? 'NOT SET',
        $_POST['voucherID'] ?? 'NOT SET',
        $_POST['discountAmount'] ?? 'NOT SET'
    ), FILE_APPEND);
    
    file_put_contents($debugLog, sprintf(
        "[%s] Parsed Values: shippingFee=%.2f, voucherID=%s, discountAmount=%d\n",
        date('Y-m-d H:i:s'),
        $shippingFee,
        $voucherID ?? 'null',
        $discountAmount
    ), FILE_APPEND);
    
    file_put_contents($debugLog, sprintf(
        "[%s] Checkout calculation: Subtotal=%d + Ship=%.2f - Discount=%d = Final=%d\n",
        date('Y-m-d H:i:s'),
        $subtotal,
        $shippingFee,
        $discountAmount,
        $finalAmount
    ), FILE_APPEND);
}

try {
    $db = Database::getInstance();
    $conn = $db->connect();
    
    // Bắt đầu transaction
    $conn->begin_transaction();
    
    // ✅ Kiểm tra và validate voucher (nếu có)
    if ($voucherID) {
        $stmtVoucher = mysqli_prepare($conn, "
            SELECT voucherID, voucherName, value, quantity, status, startDate, endDate, minOrderValue 
            FROM voucher 
            WHERE voucherID = ? AND status = 1 AND quantity > 0 
            AND startDate <= CURDATE() AND endDate >= CURDATE()
        ");
        mysqli_stmt_bind_param($stmtVoucher, "i", $voucherID);
        mysqli_stmt_execute($stmtVoucher);
        $resultVoucher = mysqli_stmt_get_result($stmtVoucher);
        $voucher = mysqli_fetch_assoc($resultVoucher);
        
        if (!$voucher) {
            throw new Exception("Voucher không hợp lệ hoặc đã hết hạn");
        }
        
        // Validate discount amount
        if ($discountAmount != $voucher['value']) {
            throw new Exception("Số tiền giảm giá không khớp");
        }
        
        // Validate đơn tối thiểu nếu có
        $minOrderValue = isset($voucher['minOrderValue']) ? (int)$voucher['minOrderValue'] : 0;
        if ($minOrderValue > 0 && $subtotal < $minOrderValue) {
            throw new Exception("Đơn hàng chưa đạt tối thiểu " . number_format($minOrderValue, 0, ',', '.') . "₫ để sử dụng voucher");
        }
        
        // Trừ số lượng voucher
        $stmtUpdateVoucher = mysqli_prepare($conn, "UPDATE voucher SET quantity = quantity - 1 WHERE voucherID = ?");
        mysqli_stmt_bind_param($stmtUpdateVoucher, "i", $voucherID);
        mysqli_stmt_execute($stmtUpdateVoucher);
    }
    
    // 1. Tạo đơn hàng (đã xóa 5 cột duplicate: recipientName, recipientEmail, recipientPhone, deliveryAddress, deliveryNotes)
    $paymentStatus = ($paymentMethod === 'COD') ? 'Chờ thanh toán (COD)' : 'Chờ thanh toán';
    
    // Build SQL dynamically based on whether voucherID exists
    if ($voucherID) {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO `order` 
            (orderDate, paymentStatus, totalAmount, paymentMethod, customerID, voucherID, deliveryStatus, shippingFee, note) 
            VALUES (NOW(), ?, ?, ?, ?, ?, 'Chờ xác nhận', ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, "sdsiids", 
            $paymentStatus,     // s = string
            $finalAmount,       // d = double/decimal
            $paymentMethod,     // s = string
            $customerID,        // i = integer
            $voucherID,         // i = integer
            $shippingFee,       // d = double/decimal
            $notes              // s = string
        );
    } else {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO `order` 
            (orderDate, paymentStatus, totalAmount, paymentMethod, customerID, deliveryStatus, shippingFee, note) 
            VALUES (NOW(), ?, ?, ?, ?, 'Chờ xác nhận', ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, "sdsids", 
            $paymentStatus,     // s = string
            $finalAmount,       // d = double/decimal
            $paymentMethod,     // s = string
            $customerID,        // i = integer
            $shippingFee,       // d = double/decimal
            $notes              // s = string
        );
    }
    
    mysqli_stmt_execute($stmt);
    $orderID = mysqli_insert_id($conn);
    
    // ✅ 1.5. Thêm thông tin giao hàng vào bảng order_delivery
    $stmtDelivery = mysqli_prepare($conn, "
        INSERT INTO order_delivery 
        (orderID, recipientName, recipientEmail, recipientPhone, 
         address, ward, district, city, 
         provinceId, districtId, wardCode, deliveryNotes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    mysqli_stmt_bind_param($stmtDelivery, "isssssssiiis", 
        $orderID,
        $fullName,
        $email,
        $phone,
        $address,
        $ward,
        $district,
        $city,
        $provinceId,
        $districtId,
        $wardCode,
        $notes
    );
    mysqli_stmt_execute($stmtDelivery);
    
    // 2. Tạo mã giao dịch (GODIFA + YYYYMMDD + orderID với 4 chữ số)
    $transactionCode = 'GODIFA' . date('Ymd') . str_pad($orderID, 4, '0', STR_PAD_LEFT);
    
    // 3. Xử lý theo phương thức thanh toán
    if ($paymentMethod === 'COD') {
        // ✅ COD: Không cần QR code, chỉ lưu transactionCode
        $stmt = mysqli_prepare($conn, "
            UPDATE `order` 
            SET transactionCode = ? 
            WHERE orderID = ?
        ");
        mysqli_stmt_bind_param($stmt, "si", $transactionCode, $orderID);
        mysqli_stmt_execute($stmt);
        
    } else {
        // QR Payment: Tạo QR code
        // 3.1. Tính thời gian hết hạn QR (15 phút)
        $qrExpiredAt = date('Y-m-d H:i:s', time() + 15 * 60);
        
        // 3.2. Tạo QR URL theo chuẩn SePay
        $account = '105875539922'; // STK VietinBank
        $bank = 'VietinBank'; // Tên ngân hàng đầy đủ
        $description = 'SEVQR TKP155 ' . $transactionCode; // Format: SEVQR TKP{mã VA} {mã giao dịch}
        $qrUrl = "https://qr.sepay.vn/img?acc=$account&bank=$bank&amount=$finalAmount&des=" . urlencode($description);
        
        // 3.3. ✅ Update transactionCode, qrUrl và qrExpiredAt vào database
        $stmt = mysqli_prepare($conn, "
            UPDATE `order` 
            SET transactionCode = ?, qrUrl = ?, qrExpiredAt = ?
            WHERE orderID = ?
        ");
        mysqli_stmt_bind_param($stmt, "sssi", $transactionCode, $qrUrl, $qrExpiredAt, $orderID);
        mysqli_stmt_execute($stmt);
        
        // Store QR info in session for display (fallback)
        $_SESSION['qr_url'] = $qrUrl;
        $_SESSION['qr_expired_at'] = $qrExpiredAt;
    }
    
    // 4. Thêm chi tiết đơn hàng
    $stmtDetail = mysqli_prepare($conn, "
        INSERT INTO order_details (orderID, productID, quantity, price) 
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($_SESSION['cart'] as $item) {
        mysqli_stmt_bind_param($stmtDetail, "iiid", $orderID, $item['productID'], $item['quantity'], $item['price']);
        mysqli_stmt_execute($stmtDetail);
    }
    
    // 4.5. ✅ TRỪ TỒN KHO NGAY - CHO CẢ COD VÀ QR
    // Lý do: "Giữ chỗ" để không bị mua mất trong khi khách đang quét QR
    // - COD: Trừ ngay, hoàn lại nếu hủy
    // - QR: Trừ ngay, hoàn lại nếu hết hạn chưa thanh toán
    $stmtUpdateStock = mysqli_prepare($conn, "
        UPDATE product 
        SET stockQuantity = stockQuantity - ? 
        WHERE productID = ?
    ");
    
    foreach ($_SESSION['cart'] as $item) {
        mysqli_stmt_bind_param($stmtUpdateStock, "ii", $item['quantity'], $item['productID']);
        mysqli_stmt_execute($stmtUpdateStock);
    }
    
    // 5. Commit transaction
    $conn->commit();
    
    // 6. Xóa giỏ hàng (session + database)
    unset($_SESSION['cart']);
    
    // ✅ Xóa giỏ hàng trong database
    $cartSync = new CartSync();
    $cartSync->clearDatabaseCart($customerID);
    
    // 7. Chuyển hướng theo phương thức thanh toán
    if ($paymentMethod === 'COD') {
        // COD: Chuyển thẳng sang trang thank you
        header("Location: " . BASE_URL . "view/payment/thankyou.php?orderID=$orderID&method=COD");
    } else {
        // QR: Chuyển sang trang QR payment
        header("Location: " . BASE_URL . "view/cart/checkout_qr.php?orderID=$orderID");
    }
    exit;
    
} catch (Exception $e) {
    // Rollback nếu có lỗi
    $conn->rollback();
    error_log("Checkout Error: " . $e->getMessage());
    
    echo "<script>
        alert('Có lỗi xảy ra khi tạo đơn hàng. Vui lòng thử lại!');
        window.location.href = '" . BASE_URL . "view/cart/viewcart.php';
    </script>";
    exit;
}
?>
