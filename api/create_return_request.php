<?php
/**
 * API: Tạo yêu cầu hoàn trả đơn hàng (sau khi đã nhận hàng)
 * File: api/create_return_request.php
 * VPS Compatible: Sử dụng BASE_URL constant
 */

// Bắt đầu output buffering để tránh HTML errors leak vào JSON response
ob_start();

session_name('GODIFA_USER_SESSION');
session_start();
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../model/database.php';
// require_once __DIR__ . '/../middleware/customer_only.php'; // Bỏ middleware này vì nó check sai session key

// Xóa buffer và set header
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

// Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để thực hiện chức năng này'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$customerID = $_SESSION['customer_id'];

// Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không hợp lệ'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Lấy dữ liệu
$orderID = intval($_POST['orderID'] ?? 0);
$reason = trim($_POST['reason'] ?? '');
$bankName = trim($_POST['bankName'] ?? '');
$bankAccount = trim($_POST['bankAccount'] ?? '');
$accountHolder = strtoupper(trim($_POST['accountHolder'] ?? ''));

// Validate
if ($orderID <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Mã đơn hàng không hợp lệ'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($reason)) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng nhập lý do hoàn trả'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($bankName) || empty($bankAccount) || empty($accountHolder)) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng nhập đầy đủ thông tin tài khoản ngân hàng'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!preg_match('/^[0-9]{9,16}$/', $bankAccount)) {
    echo json_encode([
        'success' => false,
        'message' => 'Số tài khoản không hợp lệ (phải là 9-16 chữ số)'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$conn = Database::getInstance()->getConnection();

// 1. Kiểm tra đơn hàng có tồn tại và thuộc về customer này không
$sqlCheck = "SELECT o.orderID, o.deliveryStatus, o.paymentStatus 
             FROM `order` o 
             WHERE o.orderID = ? AND o.customerID = ?";
$stmtCheck = mysqli_prepare($conn, $sqlCheck);
mysqli_stmt_bind_param($stmtCheck, "ii", $orderID, $customerID);
mysqli_stmt_execute($stmtCheck);
$resultCheck = mysqli_stmt_get_result($stmtCheck);
$order = mysqli_fetch_assoc($resultCheck);

if (!$order) {
    echo json_encode([
        'success' => false,
        'message' => 'Không tìm thấy đơn hàng hoặc đơn hàng không thuộc về bạn'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. Kiểm tra đơn hàng đã hoàn thành chưa
if ($order['deliveryStatus'] !== 'Hoàn thành') {
    echo json_encode([
        'success' => false,
        'message' => 'Chỉ có thể yêu cầu hoàn trả đơn hàng đã giao thành công'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. Kiểm tra đã có yêu cầu hoàn trả chưa (query từ bảng return_requests thay vì order)
$sqlCheckReturn = "SELECT returnID, status FROM return_requests WHERE orderID = ? LIMIT 1";
$stmtCheckReturn = mysqli_prepare($conn, $sqlCheckReturn);
mysqli_stmt_bind_param($stmtCheckReturn, "i", $orderID);
mysqli_stmt_execute($stmtCheckReturn);
$resultCheckReturn = mysqli_stmt_get_result($stmtCheckReturn);
$existingReturn = mysqli_fetch_assoc($resultCheckReturn);

if ($existingReturn) {
    $statusMessages = [
        'Chờ xử lý' => 'Đơn hàng này đã có yêu cầu hoàn trả đang chờ xử lý',
        'Đã chấp nhận' => 'Đơn hàng này đã được chấp nhận hoàn trả',
        'Đã từ chối' => 'Yêu cầu hoàn trả đơn hàng này đã bị từ chối. Bạn có thể gửi yêu cầu mới nếu cần.',
        'Đã hoàn tiền' => 'Đơn hàng này đã được hoàn tiền thành công'
    ];
    
    // Cho phép gửi lại nếu đã bị từ chối
    if ($existingReturn['status'] !== 'Đã từ chối') {
        echo json_encode([
            'success' => false,
            'message' => $statusMessages[$existingReturn['status']] ?? 'Đơn hàng này không thể yêu cầu hoàn trả'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 4. Xử lý upload ảnh chứng minh (nếu có)
$uploadedImages = [];
$uploadDir = __DIR__ . '/../image/return_proofs/';

// Tạo thư mục nếu chưa có
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
    $fileCount = count($_FILES['images']['name']);
    
    // Giới hạn tối đa 5 ảnh
    if ($fileCount > 5) {
        echo json_encode([
            'success' => false,
            'message' => 'Chỉ được upload tối đa 5 ảnh'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    for ($i = 0; $i < $fileCount; $i++) {
        $fileName = $_FILES['images']['name'][$i];
        $fileTmp = $_FILES['images']['tmp_name'][$i];
        $fileSize = $_FILES['images']['size'][$i];
        $fileError = $_FILES['images']['error'][$i];
        
        // Bỏ qua nếu không có file
        if ($fileError === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        
        // Kiểm tra lỗi upload
        if ($fileError !== UPLOAD_ERR_OK) {
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi khi upload ảnh: ' . $fileName
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Kiểm tra kích thước (max 5MB)
        if ($fileSize > 5 * 1024 * 1024) {
            echo json_encode([
                'success' => false,
                'message' => 'Ảnh ' . $fileName . ' vượt quá 5MB'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Kiểm tra định dạng
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            echo json_encode([
                'success' => false,
                'message' => 'Ảnh ' . $fileName . ' không đúng định dạng (chỉ chấp nhận: jpg, jpeg, png, gif)'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Tạo tên file unique
        $newFileName = 'return_' . $orderID . '_' . time() . '_' . $i . '.' . $fileExtension;
        $uploadPath = $uploadDir . $newFileName;
        
        // Upload file
        if (move_uploaded_file($fileTmp, $uploadPath)) {
            // Lưu đường dẫn relative (VPS compatible)
            $uploadedImages[] = 'image/return_proofs/' . $newFileName;
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi khi lưu ảnh: ' . $fileName
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

// 5. Lưu yêu cầu hoàn trả vào database
$imagesJson = json_encode($uploadedImages, JSON_UNESCAPED_UNICODE);

$sqlInsert = "INSERT INTO return_requests (orderID, customerID, reason, images, bankName, bankAccount, accountHolder) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmtInsert = mysqli_prepare($conn, $sqlInsert);
mysqli_stmt_bind_param($stmtInsert, "iisssss", $orderID, $customerID, $reason, $imagesJson, $bankName, $bankAccount, $accountHolder);

if (mysqli_stmt_execute($stmtInsert)) {
    $returnID = mysqli_insert_id($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã gửi yêu cầu hoàn trả thành công! Chúng tôi sẽ xem xét và phản hồi trong thời gian sớm nhất.',
        'returnID' => $returnID
    ], JSON_UNESCAPED_UNICODE);
} else {
    // Xóa ảnh đã upload nếu insert thất bại
    foreach ($uploadedImages as $imagePath) {
        $fullPath = __DIR__ . '/../' . $imagePath;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi lưu yêu cầu hoàn trả: ' . mysqli_error($conn)
    ], JSON_UNESCAPED_UNICODE);
}
?>
