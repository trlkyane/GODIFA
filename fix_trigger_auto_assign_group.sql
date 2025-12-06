-- ============================================
-- FIX TRIGGER: Auto Assign Customer Group
-- ============================================
-- File: fix_trigger_auto_assign_group.sql
-- Mục đích: Sửa lỗi encoding trong trigger tự động phân nhóm khách hàng

-- 1. XÓA TRIGGER CŨ (có lỗi encoding)
DROP TRIGGER IF EXISTS `after_order_update_assign_group`;

-- 2. TẠO LẠI TRIGGER MỚI (đúng encoding UTF-8)
DELIMITER $$

CREATE TRIGGER `after_order_update_assign_group` 
AFTER UPDATE ON `order` 
FOR EACH ROW 
BEGIN
    DECLARE customer_total_spent DECIMAL(15,2);
    DECLARE best_group_id INT;
    
    -- Chỉ chạy khi payment status thay đổi
    IF NEW.paymentStatus != OLD.paymentStatus THEN
        
        -- Tính tổng chi tiêu của customer (chỉ tính đơn "Đã thanh toán")
        SELECT COALESCE(SUM(totalAmount), 0) INTO customer_total_spent
        FROM `order`
        WHERE customerID = NEW.customerID
          AND paymentStatus = 'Đã thanh toán';
        
        -- Tìm nhóm phù hợp nhất dựa trên chi tiêu
        -- Ưu tiên nhóm có minSpent cao nhất mà customer đạt được
        SELECT groupID INTO best_group_id
        FROM customer_group
        WHERE customer_total_spent >= minSpent
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

-- 3. TEST TRIGGER (tùy chọn)
-- Uncomment để test:
-- UPDATE `order` SET paymentStatus = 'Đã thanh toán' WHERE orderID = 1;

-- 4. CHẠY STORED PROCEDURE ĐỂ CẬP NHẬT NHÓM CHO TẤT CẢ KHÁCH HÀNG HIỆN TẠI
CALL auto_assign_customer_groups_by_spending();

-- ============================================
-- KẾT QUẢ MONG ĐỢI:
-- ============================================
-- - Trigger mới sẽ tự động chạy khi paymentStatus đổi sang "Đã thanh toán"
-- - Khách hàng sẽ được phân nhóm dựa trên tổng chi tiêu:
--   + 0-5tr        → Broze (groupID = 1)
--   + 5tr-15tr     → Sliver (groupID = 2)
--   + 15tr-30tr    → Gold (groupID = 3)
--   + 30tr-50tr    → Platinum (groupID = 4)
--   + Trên 50tr    → Diamond (groupID = 5)
