-- ============================================
-- MIGRATION: Remove Duplicate Columns from Order Table
-- Date: 2025-11-08
-- Reason: Dữ liệu bị trùng giữa bảng order và order_delivery
-- ============================================

-- BACKUP: Tạo bảng backup trước khi xóa
CREATE TABLE IF NOT EXISTS order_backup_20251108 AS SELECT * FROM `order`;

-- Kiểm tra dữ liệu trước khi xóa
SELECT 
    COUNT(*) as total_orders,
    COUNT(recipientName) as has_recipientName,
    COUNT(recipientEmail) as has_recipientEmail,
    COUNT(recipientPhone) as has_recipientPhone,
    COUNT(deliveryAddress) as has_deliveryAddress,
    COUNT(deliveryNotes) as has_deliveryNotes
FROM `order`;

-- XÓA 5 CỘT TRÙNG LẶP
ALTER TABLE `order`
DROP COLUMN recipientName,
DROP COLUMN recipientEmail,
DROP COLUMN recipientPhone,
DROP COLUMN deliveryAddress,
DROP COLUMN deliveryNotes;

-- Kiểm tra kết quả
DESCRIBE `order`;

-- Verify: Kiểm tra các trang vẫn lấy được dữ liệu từ order_delivery
SELECT 
    o.orderID,
    o.customerName,
    o.totalAmount,
    od.recipientName,
    od.recipientPhone,
    od.recipientEmail,
    od.fullAddress,
    od.deliveryNotes
FROM `order` o
LEFT JOIN order_delivery od ON o.orderID = od.orderID
LIMIT 5;

-- ============================================
-- ROLLBACK (nếu cần khôi phục):
-- DROP TABLE IF EXISTS `order`;
-- CREATE TABLE `order` AS SELECT * FROM order_backup_20251108;
-- ============================================
