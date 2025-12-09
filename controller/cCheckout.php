<?php
/**
 * Create Order and Redirect to QR Payment
 * File: controller/cCheckout.php
 * Updated: 2025-12-09 - Fixed wardCode type mismatch and cart clearing issue
 */

// Khởi động session với tên chuẩn
if (session_status() === PHP_SESSION_NONE) {
    session_name('GODIFA_USER_SESSION');
    session_start();
}

require_once __DIR__ . '/../model/database.php';

try {
    require_once __DIR__ . '/cCartSync.php';
} catch (Exception $e) {
    die("Error loading CartSync: " . $e->getMessage());
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id']) || !isset($_SESSION['is_customer_logged_in'])) {
    file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - NOT LOGGED IN, redirecting to login\n", FILE_APPEND);
    $_SESSION['checkout_redirect'] = true;
    header('Location: ' . BASE_URL . 'view/auth/customer-login.php');
    exit;
}

file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Logged in as customer_id: {$_SESSION['customer_id']}\n", FILE_APPEND);

// Check POST data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - NOT POST, redirecting to checkout page\n", FILE_APPEND);
    header('Location: ' . BASE_URL . 'view/cart/checkout.php');
    exit;
}

file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - POST request OK\n", FILE_APPEND);

// Kiểm tra giỏ hàng
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - CART EMPTY, redirecting to viewcart\n", FILE_APPEND);
    header('Location: ' . BASE_URL . 'view/cart/viewcart.php');
    exit;
}

file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Cart has " . count($_SESSION['cart']) . " items\n", FILE_APPEND);

// Debug: Log POST data
file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - POST data: " . json_encode($_POST) . "\n", FILE_APPEND);

// Kiểm tra chế độ "Mua ngay"
$buyNowMode = isset($_POST['buyNowMode']) && isset($_POST['buyNowProductId']);
$buyNowProductId = $buyNowMode ? (int)$_POST['buyNowProductId'] : null;

file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - buyNowMode: " . ($buyNowMode ? 'YES' : 'NO') . ", buyNowProductId: " . ($buyNowProductId ?? 'NULL') . "\n", FILE_APPEND);

// Lấy danh sách sản phẩm cần thanh toán
$checkoutCart = [];
if ($buyNowMode && $buyNowProductId && isset($_SESSION['cart'][$buyNowProductId])) {
    // Chỉ lấy 1 sản phẩm vừa chọn "Mua ngay"
    $checkoutCart[$buyNowProductId] = $_SESSION['cart'][$buyNowProductId];
    file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Using BUY NOW mode for product: $buyNowProductId\n", FILE_APPEND);
} else {
    // Thanh toán tất cả sản phẩm trong giỏ
    $checkoutCart = $_SESSION['cart'];
    file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Using FULL CART mode\n", FILE_APPEND);
}

// Kiểm tra checkoutCart có rỗng không
if (empty($checkoutCart)) {
    file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - ERROR: checkoutCart is EMPTY!\n", FILE_APPEND);
    header('Location: ' . BASE_URL . 'view/cart/viewcart.php');
    exit;
}

$customerID = $_SESSION['customer_id']; // Sửa từ customerID thành customer_id

file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Starting form validation\n", FILE_APPEND);

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

file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Form data parsed, paymentMethod: $paymentMethod\n", FILE_APPEND);

// ✅ NEW: Lấy thông tin GHN IDs, phí ship và loại dịch vụ
$provinceId = (int)($_POST['provinceId'] ?? 0);
$districtId = (int)($_POST['districtId'] ?? 0);
$wardCode = trim($_POST['wardCode'] ?? '');
$shippingFee = (float)($_POST['shippingFee'] ?? 0); // Use float for decimal
$serviceTypeId = (int)($_POST['serviceTypeId'] ?? 2); // Default: Tiêu chuẩn

file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - GHN data parsed\n", FILE_APPEND);

// Validate
$errors = [];
if (empty($fullName)) $errors[] = "Vui lòng nhập họ tên";
if (empty($phone)) $errors[] = "Vui lòng nhập số điện thoại";
if (empty($address)) $errors[] = "Vui lòng nhập địa chỉ";
if (empty($ward)) $errors[] = "Vui lòng chọn phường/xã";
if (empty($district)) $errors[] = "Vui lòng chọn quận/huyện";
if (empty($city)) $errors[] = "Vui lòng chọn tỉnh/thành phố";

file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Validation done, errors: " . count($errors) . "\n", FILE_APPEND);

if (!empty($errors)) {
    $_SESSION['checkout_errors'] = $errors;
    header('Location: ' . BASE_URL . 'view/cart/checkout.php');
    exit;
}

// Tính tổng tiền từ giỏ hàng (dựa trên checkoutCart)
$totalAmount = 0;
foreach ($checkoutCart as $item) {
    $totalAmount += $item['price'] * $item['quantity'];
}

file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Total calculated: $totalAmount\n", FILE_APPEND);

// ✅ NEW: Lấy voucher từ form (thay vì session)
$voucherID = !empty($_POST['voucherID']) ? (int)$_POST['voucherID'] : null;
$discountAmount = !empty($_POST['discountAmount']) ? (int)$_POST['discountAmount'] : 0;

file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Voucher: $voucherID, Discount: $discountAmount\n", FILE_APPEND);

// ✅ Tính toán: Subtotal + ShippingFee - Discount
$subtotal = $totalAmount;
$finalAmount = $subtotal + $shippingFee - $discountAmount;

file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Calculated: subtotal=$subtotal, shippingFee=$shippingFee, finalAmount=$finalAmount\n", FILE_APPEND);

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
    file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Starting database transaction\n", FILE_APPEND);
    
    $db = Database::getInstance();
    $conn = $db->connect();
    
    file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Database connected\n", FILE_APPEND);
    
    // Bắt đầu transaction
    $conn->begin_transaction();
    
    file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Transaction started\n", FILE_APPEND);
    
    // ✅ Kiểm tra và validate voucher (nếu có)
    if ($voucherID) {
        file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Checking voucher: $voucherID\n", FILE_APPEND);
        
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
        
        file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Voucher validated\n", FILE_APPEND);
        
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
    
    file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Creating order record\n", FILE_APPEND);
    
    // 1. Tạo đơn hàng (đã xóa 5 cột duplicate: recipientName, recipientEmail, recipientPhone, deliveryAddress, deliveryNotes)
    $paymentStatus = ($paymentMethod === 'COD') ? 'Chờ thanh toán (COD)' : 'Chờ thanh toán';
    
    file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Payment status: $paymentStatus\n", FILE_APPEND);
    
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
    
    file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Order ID: $orderID\n", FILE_APPEND);
    
    // ✅ 1.5. Thêm thông tin giao hàng vào bảng order_delivery
    $stmtDelivery = mysqli_prepare($conn, "
        INSERT INTO order_delivery 
        (orderID, recipientName, recipientEmail, recipientPhone, 
         address, ward, district, city, 
         provinceId, districtId, wardCode, deliveryNotes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Inserting delivery info\n", FILE_APPEND);
    
    mysqli_stmt_bind_param($stmtDelivery, "isssssssiiss", 
        $orderID,       // i - integer
        $fullName,      // s - string
        $email,         // s - string
        $phone,         // s - string
        $address,       // s - string
        $ward,          // s - string
        $district,      // s - string
        $city,          // s - string
        $provinceId,    // i - integer
        $districtId,    // i - integer
        $wardCode,      // s - STRING (NOT integer!)
        $notes          // s - string
    );
    
    file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Executing delivery insert\n", FILE_APPEND);
    
    mysqli_stmt_execute($stmtDelivery);
    
    file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Delivery info inserted\n", FILE_APPEND);
    
    // 2. Tạo mã giao dịch (GODIFA + YYYYMMDD + orderID với 4 chữ số)
    $transactionCode = 'GODIFA' . date('Ymd') . str_pad($orderID, 4, '0', STR_PAD_LEFT);
    
    file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Transaction code: $transactionCode, Payment: $paymentMethod\n", FILE_APPEND);
    
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
        
        file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - COD: Transaction code updated\n", FILE_APPEND);
        
    } else {
        file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - QR Payment: Creating QR code\n", FILE_APPEND);
        // QR Payment: Tạo QR code
        // 3.1. Tính thời gian hết hạn QR (2 phút)
        $qrExpiredAt = date('Y-m-d H:i:s', time() + 2 * 60);
        
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
        
        file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - QR code created and saved\n", FILE_APPEND);
        
        // Store QR info in session for display (fallback)
        $_SESSION['qr_url'] = $qrUrl;
        $_SESSION['qr_expired_at'] = $qrExpiredAt;
    }
    
    file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Adding order details\n", FILE_APPEND);
    
    // 4. Thêm chi tiết đơn hàng (từ checkoutCart, không phải toàn bộ cart)
    $stmtDetail = mysqli_prepare($conn, "
        INSERT INTO order_details (orderID, productID, quantity, price) 
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($checkoutCart as $item) {
        mysqli_stmt_bind_param($stmtDetail, "iiid", $orderID, $item['productID'], $item['quantity'], $item['price']);
        mysqli_stmt_execute($stmtDetail);
        file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Added detail for product: {$item['productID']}\n", FILE_APPEND);
    }
    
    file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Updating stock quantities\n", FILE_APPEND);
    
    // 4.5. ✅ TRỪ TỒN KHO NGAY - CHO CẢ COD VÀ QR
    // Lý do: "Giữ chỗ" để không bị mua mất trong khi khách đang quét QR
    // - COD: Trừ ngay, hoàn lại nếu hủy
    // - QR: Trừ ngay, hoàn lại nếu hết hạn chưa thanh toán
    $stmtUpdateStock = mysqli_prepare($conn, "
        UPDATE product 
        SET stockQuantity = stockQuantity - ? 
        WHERE productID = ?
    ");
    
    foreach ($checkoutCart as $item) {
        mysqli_stmt_bind_param($stmtUpdateStock, "ii", $item['quantity'], $item['productID']);
        mysqli_stmt_execute($stmtUpdateStock);
        file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Updated stock for product: {$item['productID']}\n", FILE_APPEND);
    }
    
    file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Committing transaction\n", FILE_APPEND);
    
    // 5. Commit transaction
    $conn->commit();
    
    file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Transaction committed successfully\n", FILE_APPEND);
    
    // 6. Xóa giỏ hàng (session + database)
    if ($buyNowMode && $buyNowProductId) {
        // Chỉ xóa sản phẩm vừa mua
        unset($_SESSION['cart'][$buyNowProductId]);
        file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Removed product $buyNowProductId from session cart\n", FILE_APPEND);
    } else {
        // Xóa toàn bộ giỏ hàng
        unset($_SESSION['cart']);
        file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Cleared entire session cart\n", FILE_APPEND);
    }
    
    file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Clearing database cart\n", FILE_APPEND);
    
    file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Clearing database cart\n", FILE_APPEND);
    
    // ✅ Xóa giỏ hàng trong database - FIX: Dùng query trực tiếp thay vì qua CartSync
    try {
        if ($buyNowMode && $buyNowProductId) {
            // Chỉ xóa 1 sản phẩm trong database
            $stmtDelete = mysqli_prepare($conn, "DELETE FROM cart_items WHERE cartID = ? AND productID = ?");
            mysqli_stmt_bind_param($stmtDelete, "ii", $customerID, $buyNowProductId);
            mysqli_stmt_execute($stmtDelete);
            mysqli_stmt_close($stmtDelete);
            file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Removed product $buyNowProductId from database cart\n", FILE_APPEND);
        } else {
            // Xóa toàn bộ giỏ hàng
            $stmtDelete = mysqli_prepare($conn, "DELETE FROM cart_items WHERE cartID = ?");
            mysqli_stmt_bind_param($stmtDelete, "i", $customerID);
            mysqli_stmt_execute($stmtDelete);
            mysqli_stmt_close($stmtDelete);
            file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Cleared entire database cart\n", FILE_APPEND);
        }
    } catch (Exception $e) {
        // Không quan trọng lắm nếu xóa cart thất bại
        file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Warning: Could not clear cart: " . $e->getMessage() . "\n", FILE_APPEND);
    }
    
    file_put_contents(__DIR__ . '/../logs/checkout_debug.log', date('Y-m-d H:i:s') . " - Redirecting to payment page, method: $paymentMethod\n", FILE_APPEND);
    
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
