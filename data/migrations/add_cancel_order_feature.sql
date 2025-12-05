-- Migration: Thêm tính năng hủy đơn và hoàn tiền
-- File: data/migrations/add_cancel_order_feature.sql
-- Date: 2025-12-05
-- Note: Bảng order đã có cột cancelReason từ trước (admin hủy đơn)
--       Chỉ cần thêm cancelledAt và cancelledBy

-- 1. Thêm cột vào bảng order (chỉ 2 cột còn thiếu)
ALTER TABLE `order` 
ADD COLUMN `cancelledAt` DATETIME NULL COMMENT 'Thời gian hủy đơn' AFTER `cancelReason`,
ADD COLUMN `cancelledBy` ENUM('customer', 'admin') NULL COMMENT 'Người hủy: khách hay admin' AFTER `cancelledAt`;

-- 2. Tạo bảng refund_requests (yêu cầu hoàn tiền)
-- Bỏ FOREIGN KEY vì bảng order có thể dùng MyISAM (không hỗ trợ FK)
CREATE TABLE IF NOT EXISTS `refund_requests` (
  `refundID` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `orderID` INT(11) NOT NULL,
  `customerID` INT(11) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL COMMENT 'Số tiền cần hoàn',
  `bankAccount` VARCHAR(50) NOT NULL COMMENT 'Số tài khoản nhận tiền',
  `bankName` VARCHAR(100) NOT NULL COMMENT 'Tên ngân hàng',
  `accountHolder` VARCHAR(100) NOT NULL COMMENT 'Tên chủ tài khoản',
  `proofImage` VARCHAR(255) NULL COMMENT 'Ảnh chứng minh đã chuyển khoản',
  `status` ENUM('Chờ xử lý', 'Đã hoàn tiền', 'Từ chối') DEFAULT 'Chờ xử lý',
  `adminNote` TEXT NULL COMMENT 'Ghi chú từ admin',
  `processedBy` INT(11) NULL COMMENT 'Admin xử lý',
  `processedAt` DATETIME NULL COMMENT 'Thời gian xử lý',
  `createdAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_orderID` (`orderID`),
  INDEX `idx_customerID` (`customerID`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created` (`createdAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng yêu cầu hoàn tiền khi hủy đơn';

-- Note: Không dùng FOREIGN KEY vì bảng order và customer có thể đang dùng MyISAM
-- Nếu muốn thêm FK sau này, cần convert sang InnoDB trước:
-- ALTER TABLE `order` ENGINE=InnoDB;
-- ALTER TABLE `customer` ENGINE=InnoDB;
-- Sau đó thêm FK:
-- ALTER TABLE `refund_requests` 
--   ADD CONSTRAINT `fk_refund_order` FOREIGN KEY (`orderID`) REFERENCES `order`(`orderID`) ON DELETE CASCADE,
--   ADD CONSTRAINT `fk_refund_customer` FOREIGN KEY (`customerID`) REFERENCES `customer`(`customerID`) ON DELETE CASCADE;

-- 3. Tạo thư mục lưu ảnh (chỉ ghi nhớ, không chạy SQL)
-- Tạo thư mục: /image/refund_proofs/ với quyền 755

-- 4. Cập nhật các trigger (nếu có) để xử lý khi hủy đơn
-- Ví dụ: tự động hoàn voucher nếu có sử dụng
DROP TRIGGER IF EXISTS `after_order_cancelled`;

DELIMITER //

CREATE TRIGGER `after_order_cancelled` 
AFTER UPDATE ON `order`
FOR EACH ROW
BEGIN
    -- Nếu đơn hàng bị hủy và có sử dụng voucher
    IF NEW.paymentStatus = 'Đã hủy' AND OLD.paymentStatus != 'Đã hủy' THEN
        IF NEW.voucherID IS NOT NULL AND NEW.voucherID > 0 THEN
            -- Tăng lại số lượng voucher
            UPDATE voucher 
            SET quantity = quantity + 1 
            WHERE voucherID = NEW.voucherID;
        END IF;
    END IF;
END//

DELIMITER ;

-- 5. Thêm index để tối ưu query
ALTER TABLE `order` ADD INDEX `idx_cancelled` (`cancelledAt`, `cancelledBy`);

-- 6. Insert test data (optional - để test)
-- INSERT INTO `refund_requests` (orderID, customerID, amount, bankAccount, bankName, accountHolder, proofImage, status) 
-- VALUES (1, 1, 150000, '1234567890', 'Vietcombank', 'Nguyen Van A', 'image/refund_proofs/test.jpg', 'Chờ xử lý');
