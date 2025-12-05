-- Migration: Thêm chức năng hoàn trả đơn hàng (sau khi đã giao)
-- Ngày: 2025-12-05
-- Mô tả: Khách hàng có thể yêu cầu hoàn trả sản phẩm đã nhận nếu có vấn đề

-- 1. Tạo bảng return_requests để lưu yêu cầu hoàn trả
CREATE TABLE IF NOT EXISTS `return_requests` (
    `returnID` INT(11) NOT NULL AUTO_INCREMENT,
    `orderID` INT(11) NOT NULL,
    `customerID` INT(11) NOT NULL,
    `reason` TEXT NOT NULL COMMENT 'Lý do hoàn trả',
    `images` TEXT NULL COMMENT 'JSON array chứa đường dẫn ảnh chứng minh (sản phẩm lỗi, hư hỏng)',
    `bankName` VARCHAR(100) NOT NULL COMMENT 'Tên ngân hàng nhận tiền hoàn',
    `bankAccount` VARCHAR(20) NOT NULL COMMENT 'Số tài khoản ngân hàng',
    `accountHolder` VARCHAR(100) NOT NULL COMMENT 'Tên chủ tài khoản',
    `status` ENUM('Chờ xử lý', 'Đã chấp nhận', 'Đã từ chối', 'Đã hoàn tiền') NOT NULL DEFAULT 'Chờ xử lý',
    `createdAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `processedAt` DATETIME NULL COMMENT 'Thời điểm admin xử lý',
    `processedBy` INT(11) NULL COMMENT 'Admin ID người xử lý',
    `adminNote` TEXT NULL COMMENT 'Ghi chú của admin',
    PRIMARY KEY (`returnID`),
    KEY `idx_order` (`orderID`),
    KEY `idx_customer` (`customerID`),
    KEY `idx_status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Yêu cầu hoàn trả đơn hàng';

-- 2. Thêm column returnStatus vào bảng order để tracking
-- Kiểm tra xem column đã tồn tại chưa trước khi thêm (để tránh lỗi khi chạy lại)
SET @dbname = DATABASE();
SET @tablename = "order";
SET @columnname = "returnStatus";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE `order` ADD COLUMN `returnStatus` ENUM('Không', 'Đang yêu cầu', 'Đã chấp nhận', 'Đã từ chối', 'Đã hoàn trả') DEFAULT 'Không' COMMENT 'Trạng thái hoàn trả đơn hàng' AFTER `deliveryStatus`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 3. Tạo trigger để tự động cập nhật returnStatus khi return_requests thay đổi
-- Xóa trigger cũ nếu tồn tại để tránh lỗi
DROP TRIGGER IF EXISTS `after_return_request_insert`;
DROP TRIGGER IF EXISTS `after_return_request_update`;

DELIMITER $$

CREATE TRIGGER `after_return_request_insert`
AFTER INSERT ON `return_requests`
FOR EACH ROW
BEGIN
    UPDATE `order` 
    SET returnStatus = 'Đang yêu cầu' 
    WHERE orderID = NEW.orderID;
END$$

CREATE TRIGGER `after_return_request_update`
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
END$$

DELIMITER ;

-- 4. Insert test data (có thể xóa sau)
-- INSERT INTO `return_requests` (`orderID`, `customerID`, `reason`, `images`) 
-- VALUES (1, 1, 'Sản phẩm bị lỗi, không hoạt động', '["image/return_proofs/test1.jpg"]');
