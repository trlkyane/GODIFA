<?php
/**
 * Script chạy migration fix lỗi Foreign Key
 */
require_once __DIR__ . '/model/database.php';
$conn = Database::getInstance()->getConnection();

echo "<h2>Chạy Migration: Thêm chức năng hoàn trả đơn hàng</h2>";

// 1. Tạo bảng return_requests (MyISAM, không FK)
$sql1 = "CREATE TABLE IF NOT EXISTS `return_requests` (
    `returnID` INT(11) NOT NULL AUTO_INCREMENT,
    `orderID` INT(11) NOT NULL,
    `customerID` INT(11) NOT NULL,
    `reason` TEXT NOT NULL COMMENT 'Lý do hoàn trả',
    `images` TEXT NULL COMMENT 'JSON array chứa đường dẫn ảnh chứng minh (sản phẩm lỗi, hư hỏng)',
    `status` ENUM('Chờ xử lý', 'Đã chấp nhận', 'Đã từ chối', 'Đã hoàn tiền') NOT NULL DEFAULT 'Chờ xử lý',
    `createdAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `processedAt` DATETIME NULL COMMENT 'Thời điểm admin xử lý',
    `processedBy` INT(11) NULL COMMENT 'Admin ID người xử lý',
    `adminNote` TEXT NULL COMMENT 'Ghi chú của admin',
    PRIMARY KEY (`returnID`),
    KEY `idx_order` (`orderID`),
    KEY `idx_customer` (`customerID`),
    KEY `idx_status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Yêu cầu hoàn trả đơn hàng'";

echo "<h3>1. Tạo bảng return_requests:</h3>";
if (mysqli_query($conn, $sql1)) {
    echo "<p style='color: green'>✅ Thành công</p>";
} else {
    echo "<p style='color: red'>❌ Lỗi: " . mysqli_error($conn) . "</p>";
}

// 2. Thêm column returnStatus vào bảng order
echo "<h3>2. Thêm column returnStatus vào bảng order:</h3>";
// Kiểm tra xem column đã tồn tại chưa
$checkSql = "SHOW COLUMNS FROM `order` LIKE 'returnStatus'";
$result = mysqli_query($conn, $checkSql);
if (mysqli_num_rows($result) == 0) {
    $sql2 = "ALTER TABLE `order` 
             ADD COLUMN `returnStatus` ENUM('Không', 'Đang yêu cầu', 'Đã chấp nhận', 'Đã từ chối', 'Đã hoàn trả') 
             DEFAULT 'Không' 
             COMMENT 'Trạng thái hoàn trả đơn hàng' 
             AFTER `deliveryStatus`";
    if (mysqli_query($conn, $sql2)) {
        echo "<p style='color: green'>✅ Thành công</p>";
    } else {
        echo "<p style='color: red'>❌ Lỗi: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color: blue'>ℹ️ Column đã tồn tại, bỏ qua.</p>";
}

// 3. Tạo triggers
echo "<h3>3. Tạo triggers:</h3>";

// Xóa trigger cũ
mysqli_query($conn, "DROP TRIGGER IF EXISTS `after_return_request_insert`");
mysqli_query($conn, "DROP TRIGGER IF EXISTS `after_return_request_update`");

// Trigger 1
$sqlTrigger1 = "CREATE TRIGGER `after_return_request_insert`
AFTER INSERT ON `return_requests`
FOR EACH ROW
BEGIN
    UPDATE `order` 
    SET returnStatus = 'Đang yêu cầu' 
    WHERE orderID = NEW.orderID;
END";

if (mysqli_query($conn, $sqlTrigger1)) {
    echo "<p style='color: green'>✅ Trigger insert thành công</p>";
} else {
    echo "<p style='color: red'>❌ Lỗi Trigger insert: " . mysqli_error($conn) . "</p>";
}

// Trigger 2
$sqlTrigger2 = "CREATE TRIGGER `after_return_request_update`
AFTER UPDATE ON `return_requests`
FOR EACH ROW
BEGIN
    IF NEW.status = 'Đã chấp nhận' THEN
        UPDATE `order` 
        SET returnStatus = 'Đã chấp nhận' 
        WHERE orderID = NEW.orderID;
    ELSEIF NEW.status = 'Đã từ chối' THEN
        UPDATE `order` 
        SET returnStatus = 'Đã từ chối' 
        WHERE orderID = NEW.orderID;
    ELSEIF NEW.status = 'Đã hoàn tiền' THEN
        UPDATE `order` 
        SET returnStatus = 'Đã hoàn trả' 
        WHERE orderID = NEW.orderID;
    END IF;
END";

if (mysqli_query($conn, $sqlTrigger2)) {
    echo "<p style='color: green'>✅ Trigger update thành công</p>";
} else {
    echo "<p style='color: red'>❌ Lỗi Trigger update: " . mysqli_error($conn) . "</p>";
}

echo "<p><b>Hoàn tất!</b></p>";
?>
