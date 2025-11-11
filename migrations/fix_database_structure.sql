-- ============================================
-- FIX DATABASE STRUCTURE - GODIFA PROJECT
-- Ngày: 09/11/2025
-- Mục đích: Chuẩn hóa database theo cấu trúc MVC hiện tại
-- ============================================

-- ============================================
-- BƯỚC 1: BACKUP TABLES QUAN TRỌNG
-- ============================================
CREATE TABLE IF NOT EXISTS order_backup_before_fix AS SELECT * FROM `order`;
CREATE TABLE IF NOT EXISTS shipping_history_backup AS SELECT * FROM shipping_history;

-- ============================================
-- BƯỚC 2: XÓA BẢNG KHÔNG CẦN THIẾT
-- ============================================

-- Xóa shipping_history vì không còn dùng GHN webhook
DROP TABLE IF EXISTS `shipping_history`;

-- Xóa các bảng backup cũ
DROP TABLE IF EXISTS `order_backup_20251108`;

-- ============================================
-- BƯỚC 3: CẬP NHẬT BẢNG ORDER - DELIVERY STATUS
-- ============================================

-- Sửa ENUM deliveryStatus về 3 trạng thái mới (đơn giản)
ALTER TABLE `order` 
MODIFY COLUMN `deliveryStatus` ENUM(
    'Chờ xác nhận',
    'Đang tiến hành vận chuyển', 
    'Hoàn thành',
    'Đã hủy'
) COLLATE utf8mb3_unicode_520_ci DEFAULT 'Chờ xác nhận';

-- Migrate old statuses to new statuses
UPDATE `order` SET deliveryStatus = 'Chờ xác nhận' 
WHERE deliveryStatus IN ('Chờ xử lý', 'Chờ lấy hàng');

UPDATE `order` SET deliveryStatus = 'Đang tiến hành vận chuyển' 
WHERE deliveryStatus IN ('Đang xử lý', 'Đang lấy hàng', 'Đã lấy hàng', 'Đang vận chuyển', 'Đang giao');

UPDATE `order` SET deliveryStatus = 'Hoàn thành' 
WHERE deliveryStatus IN ('Đã giao');

-- Giữ nguyên 'Đã hủy'
UPDATE `order` SET deliveryStatus = 'Đã hủy' 
WHERE deliveryStatus IN ('Giao thất bại', 'Đang hoàn', 'Đã hoàn');

-- ============================================
-- BƯỚC 4: CLEAN UP BẢNG ORDER - XÓA COLUMNS KHÔNG DÙNG
-- ============================================
-- NOTE: These will be handled by PHP script
-- PHP will check if column exists before dropping
-- __PHP_DROP_COLUMNS__

-- ============================================
-- BƯỚC 5: CẬP NHẬT PAYMENT STATUS CHO COD
-- ============================================

-- Auto-payment: COD orders với deliveryStatus = 'Hoàn thành' → Đã thanh toán
UPDATE `order` 
SET paymentStatus = 'Đã thanh toán'
WHERE paymentMethod = 'COD' 
  AND deliveryStatus = 'Hoàn thành'
  AND paymentStatus LIKE '%Chờ thanh toán%';

-- Chuẩn hóa payment status format
UPDATE `order` SET paymentStatus = 'Chờ thanh toán' 
WHERE paymentStatus = 'Chờ thanh toán (COD)';

-- ============================================
-- BƯỚC 6: FIX BẢNG ORDER_DELIVERY
-- ============================================

-- Đảm bảo provinceId, districtId được lưu đúng (không phải 0)
UPDATE `order_delivery` 
SET provinceId = NULL, districtId = NULL, wardCode = NULL
WHERE provinceId = 0 OR districtId = 0;

-- ============================================
-- BƯỚC 7: OPTIMIZE TABLES
-- ============================================

OPTIMIZE TABLE `order`;
OPTIMIZE TABLE `order_delivery`;
OPTIMIZE TABLE `order_details`;

-- ============================================
-- BƯỚC 8: CẬP NHẬT TRIGGER
-- ============================================

-- Drop old trigger
DROP TRIGGER IF EXISTS `after_order_update_assign_group`;

-- Recreate trigger with fixed logic
DELIMITER $$
CREATE TRIGGER `after_order_update_assign_group` AFTER UPDATE ON `order` FOR EACH ROW 
BEGIN
    DECLARE customer_total_spent DECIMAL(15,2);
    DECLARE best_group_id INT;
    
    -- Chỉ chạy khi payment status thay đổi
    IF NEW.paymentStatus != OLD.paymentStatus THEN
        -- Tính tổng chi tiêu của customer (không tính đơn hủy)
        SELECT COALESCE(SUM(totalAmount), 0) INTO customer_total_spent
        FROM `order`
        WHERE customerID = NEW.customerID 
          AND paymentStatus = 'Đã thanh toán';
        
        -- Tìm nhóm phù hợp nhất
        SELECT groupID INTO best_group_id
        FROM customer_group
        WHERE status = 1
          AND customer_total_spent >= minSpent
          AND (maxSpent IS NULL OR customer_total_spent <= maxSpent)
        ORDER BY minSpent DESC
        LIMIT 1;
        
        -- Cập nhật nhóm cho customer
        IF best_group_id IS NOT NULL THEN
            UPDATE customer 
            SET groupID = best_group_id 
            WHERE customerID = NEW.customerID;
        END IF;
    END IF;
END$$
DELIMITER ;

-- ============================================
-- VERIFICATION QUERIES
-- ============================================

-- Kiểm tra số lượng orders theo delivery status
SELECT 
    deliveryStatus, 
    COUNT(*) as total,
    SUM(CASE WHEN paymentMethod = 'COD' THEN 1 ELSE 0 END) as cod_orders,
    SUM(CASE WHEN paymentMethod = 'QR' THEN 1 ELSE 0 END) as qr_orders
FROM `order`
GROUP BY deliveryStatus
ORDER BY 
    CASE deliveryStatus
        WHEN 'Chờ xác nhận' THEN 1
        WHEN 'Đang tiến hành vận chuyển' THEN 2
        WHEN 'Hoàn thành' THEN 3
        WHEN 'Đã hủy' THEN 4
    END;

-- Kiểm tra payment status
SELECT paymentStatus, COUNT(*) as total
FROM `order`
GROUP BY paymentStatus;

-- Kiểm tra COD orders cần auto-payment
SELECT orderID, paymentStatus, deliveryStatus, paymentMethod
FROM `order`
WHERE paymentMethod = 'COD'
  AND deliveryStatus = 'Hoàn thành'
  AND paymentStatus != 'Đã thanh toán';

-- ============================================
-- DONE! 
-- ============================================
-- Sau khi chạy script này:
-- ✅ Database structure đã chuẩn hóa
-- ✅ Delivery status đã đơn giản hóa (3 trạng thái + Hủy)
-- ✅ Xóa columns/tables không dùng
-- ✅ COD auto-payment khi hoàn thành
-- ✅ Trigger được fix
-- ============================================
