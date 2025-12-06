-- ============================================
-- FIX: Customer Group Configuration
-- ============================================
-- File: fix_customer_group_minspent.sql
-- Vấn đề: Nhóm Broze có minSpent = NULL, khiến query không khớp

-- 1. FIX minSpent cho nhóm Broze
UPDATE customer_group 
SET minSpent = 0 
WHERE groupID = 1 AND minSpent IS NULL;

-- 2. Kiểm tra lại cấu hình
SELECT groupID, groupName, minSpent, maxSpent, description 
FROM customer_group 
ORDER BY minSpent ASC;

-- 3. Fix trigger với encoding đúng và xử lý NULL
DROP TRIGGER IF EXISTS `after_order_update_assign_group`;

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
        -- Sử dụng COALESCE để xử lý NULL
        SELECT groupID INTO best_group_id
        FROM customer_group
        WHERE customer_total_spent >= COALESCE(minSpent, 0)
          AND (maxSpent IS NULL OR customer_total_spent <= maxSpent)
        ORDER BY COALESCE(minSpent, 0) DESC
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

-- 4. Cập nhật nhóm cho tất cả khách hàng hiện tại
-- BỎ QUA stored procedure vì có lỗi cột 'status' không tồn tại
-- CALL auto_assign_customer_groups_by_spending();

-- 5. Cập nhật thủ công cho từng khách hàng (DÙNG CÁI NÀY)
UPDATE customer c
SET groupID = (
    SELECT groupID
    FROM customer_group cg
    WHERE (
        SELECT COALESCE(SUM(totalAmount), 0)
        FROM `order` o
        WHERE o.customerID = c.customerID
          AND o.paymentStatus = 'Đã thanh toán'
    ) >= COALESCE(cg.minSpent, 0)
    AND (
        cg.maxSpent IS NULL 
        OR (
            SELECT COALESCE(SUM(totalAmount), 0)
            FROM `order` o
            WHERE o.customerID = c.customerID
              AND o.paymentStatus = 'Đã thanh toán'
        ) <= cg.maxSpent
    )
    ORDER BY COALESCE(cg.minSpent, 0) DESC
    LIMIT 1
)
WHERE customerID IN (
    SELECT DISTINCT customerID 
    FROM `order` 
    WHERE paymentStatus = 'Đã thanh toán'
);

-- ============================================
-- KẾT QUẢ MONG ĐỢI:
-- ============================================
-- Sau khi chạy script này:
-- - Nhóm Broze sẽ có minSpent = 0 (thay vì NULL)
-- - Trigger sẽ dùng COALESCE để xử lý NULL an toàn
-- - Tất cả khách hàng sẽ được phân nhóm đúng:
--   + aaa (11tr) → Sliver
--   + Lê Trung Hiếu (830k) → Broze
--   + Nguyễn Trung Trực (583k) → Broze
