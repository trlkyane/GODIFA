-- ============================================
-- FIX: Stored Procedure Auto Assign
-- ============================================
-- File: fix_stored_procedure_auto_assign.sql
-- Vấn đề: Stored procedure tham chiếu cột 'status' không tồn tại

-- 1. Drop stored procedure cũ
DROP PROCEDURE IF EXISTS `auto_assign_customer_groups_by_spending`;

-- 2. Tạo lại stored procedure mới (không dùng cột status)
DELIMITER $$

CREATE PROCEDURE `auto_assign_customer_groups_by_spending`()
BEGIN
    -- Cập nhật groupID cho tất cả customers dựa trên tổng chi tiêu
    UPDATE customer c
    SET groupID = (
        SELECT groupID
        FROM customer_group cg2
        WHERE (
            SELECT COALESCE(SUM(o.totalAmount), 0)
            FROM `order` o
            WHERE o.customerID = c.customerID
              AND o.paymentStatus = 'Đã thanh toán'
        ) >= COALESCE(cg2.minSpent, 0)
        AND (
            cg2.maxSpent IS NULL 
            OR (
                SELECT COALESCE(SUM(o.totalAmount), 0)
                FROM `order` o
                WHERE o.customerID = c.customerID
                  AND o.paymentStatus = 'Đã thanh toán'
            ) <= cg2.maxSpent
        )
        ORDER BY COALESCE(cg2.minSpent, 0) DESC
        LIMIT 1
    )
    WHERE customerID IN (
        SELECT DISTINCT customerID 
        FROM `order` 
        WHERE paymentStatus = 'Đã thanh toán'
    );
END$$

DELIMITER ;

-- 3. Test stored procedure
CALL auto_assign_customer_groups_by_spending();

-- 4. Kiểm tra kết quả
SELECT 
    c.customerID,
    c.customerName,
    c.groupID,
    cg.groupName,
    COALESCE(SUM(CASE WHEN o.paymentStatus = 'Đã thanh toán' THEN o.totalAmount ELSE 0 END), 0) as totalSpent
FROM customer c
LEFT JOIN `order` o ON c.customerID = o.customerID
LEFT JOIN customer_group cg ON c.groupID = cg.groupID
GROUP BY c.customerID, c.customerName, c.groupID, cg.groupName
HAVING totalSpent > 0
ORDER BY totalSpent DESC
LIMIT 10;
