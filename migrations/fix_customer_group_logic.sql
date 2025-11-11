-- ============================================
-- FIX CUSTOMER GROUP LOGIC
-- Ngày: 09/11/2025
-- Vấn đề: Logic phân nhóm khách hàng không hợp lý
-- ============================================

-- ============================================
-- PHÂN TÍCH VẤN ĐỀ
-- ============================================
/*
VẤN ĐỀ 1: Có 2 nhóm cho khách mới
- "Khách hàng mới" (groupID=8): minSpent=0, maxSpent=0
- "Bronze" (groupID=1): minSpent=1, maxSpent=4,999,999

→ Khách chưa mua gì (totalSpent=0) vào nhóm nào?
→ Sau khi mua 1 đơn thì chuyển sang Bronze → Confusing!

VẤN ĐỀ 2: isSystem = 1 nhưng không có ý nghĩa
- Khách hàng vừa đăng ký → groupID = NULL (default)
- Sau khi mua đơn đầu tiên → Auto assign vào nhóm

→ "Khách hàng mới" không bao giờ được dùng!

GIẢI PHÁP:
Option 1: XÓA "Khách hàng mới", để Bronze bắt đầu từ 0đ
Option 2: GIỮ "Khách hàng mới" nhưng sửa logic: minSpent=0, maxSpent=0 (chỉ cho chưa mua)
Option 3: ĐỔI TÊN "Bronze" → "Khách hàng thường" với minSpent=0

KHUYẾN NGHỊ: Option 3 - Rõ ràng và dễ hiểu nhất
*/

-- ============================================
-- OPTION 3: ĐỔI LOGIC PHÂN NHÓM (RECOMMENDED)
-- ============================================

-- BƯỚC 1: XÓA nhóm "Khách hàng mới" (không cần thiết)
DELETE FROM customer_group WHERE groupID = 8 AND isSystem = 1;

-- BƯỚC 2: Đổi Bronze → Khách hàng thường (bắt đầu từ 0đ)
UPDATE customer_group 
SET 
    groupName = 'Khách hàng thường',
    description = 'Khách hàng có tổng chi tiêu dưới 5 triệu',
    minSpent = 0,
    maxSpent = 4999999
WHERE groupID = 1;

-- BƯỚC 3: Cập nhật lại các nhóm khác (optional - giữ nguyên cũng được)
UPDATE customer_group SET groupName = 'Khách hàng trung thành', description = 'Chi tiêu từ 5-15 triệu' WHERE groupID = 2;
UPDATE customer_group SET groupName = 'Khách hàng VIP', description = 'Chi tiêu từ 15-30 triệu' WHERE groupID = 3;
UPDATE customer_group SET groupName = 'Khách hàng Premium', description = 'Chi tiêu từ 30-50 triệu' WHERE groupID = 4;
UPDATE customer_group SET groupName = 'Khách hàng Kim cương', description = 'Chi tiêu trên 50 triệu' WHERE groupID = 5;

-- BƯỚC 4: Cập nhật tất cả khách hàng có groupID = 8 về groupID = 1
UPDATE customer SET groupID = 1 WHERE groupID = 8;

-- BƯỚC 5: Cập nhật khách hàng có groupID = NULL về 1 (Khách hàng thường)
UPDATE customer SET groupID = 1 WHERE groupID IS NULL;

-- ============================================
-- VERIFICATION
-- ============================================

-- Kiểm tra lại các nhóm
SELECT groupID, groupName, description, minSpent, maxSpent, isSystem
FROM customer_group
ORDER BY minSpent;

-- Expected Result:
/*
+----------+------------------------+----------------------------------+----------+----------+----------+
| groupID  | groupName              | description                      | minSpent | maxSpent | isSystem |
+----------+------------------------+----------------------------------+----------+----------+----------+
|        1 | Khách hàng thường      | Chi tiêu dưới 5 triệu           |        0 |  4999999 |        0 |
|        2 | Khách hàng trung thành | Chi tiêu từ 5-15 triệu          |  5000000 | 14999999 |        0 |
|        3 | Khách hàng VIP         | Chi tiêu từ 15-30 triệu         | 15000000 | 29999999 |        0 |
|        4 | Khách hàng Premium     | Chi tiêu từ 30-50 triệu         | 30000000 | 49999999 |        0 |
|        5 | Khách hàng Kim cương   | Chi tiêu trên 50 triệu          | 50000000 |     NULL |        0 |
+----------+------------------------+----------------------------------+----------+----------+----------+
*/

-- Kiểm tra phân bổ khách hàng
SELECT 
    cg.groupName,
    cg.minSpent,
    cg.maxSpent,
    COUNT(c.customerID) as totalCustomers
FROM customer c
LEFT JOIN customer_group cg ON c.groupID = cg.groupID
GROUP BY cg.groupName, cg.minSpent, cg.maxSpent
ORDER BY cg.minSpent;

-- Kiểm tra khách hàng không có nhóm
SELECT customerID, customerName, groupID
FROM customer
WHERE groupID IS NULL;
-- Expected: 0 rows (tất cả phải có nhóm)

-- ============================================
-- CHẠY LẠI AUTO ASSIGN (NẾU CẦN)
-- ============================================

-- Tính lại groupID cho tất cả khách hàng dựa trên tổng chi tiêu
UPDATE customer c
SET c.groupID = (
    SELECT cg.groupID
    FROM customer_group cg
    WHERE cg.status = 1
      AND (
          SELECT COALESCE(SUM(o.totalAmount), 0)
          FROM `order` o 
          WHERE o.customerID = c.customerID 
            AND o.paymentStatus = 'Đã thanh toán'
      ) >= cg.minSpent
      AND (
          cg.maxSpent IS NULL 
          OR (
              SELECT COALESCE(SUM(o.totalAmount), 0)
              FROM `order` o 
              WHERE o.customerID = c.customerID 
                AND o.paymentStatus = 'Đã thanh toán'
          ) <= cg.maxSpent
      )
    ORDER BY cg.minSpent DESC
    LIMIT 1
);

-- ============================================
-- DONE!
-- ============================================
/*
✅ Xóa nhóm "Khách hàng mới" (không cần thiết)
✅ Bronze → "Khách hàng thường" (minSpent = 0)
✅ Tất cả khách hàng đều có nhóm
✅ Logic rõ ràng: 
   - Chưa mua gì: Khách hàng thường (0đ)
   - Mua 1 triệu: Khách hàng thường (< 5tr)
   - Mua 10 triệu: Khách hàng trung thành (5-15tr)
   - ...
*/
