-- ============================================
-- KHÓA NHÓM KHÁCH HÀNG - CỐ ĐỊNH 5 HẠNG
-- Ngày: 10/11/2025
-- Mục đích: Không cho thay đổi hạng mức, chỉ cho sửa tên/màu/mô tả
-- ============================================

-- BƯỚC 1: Xóa các cột không cần thiết
ALTER TABLE customer_group 
DROP COLUMN IF EXISTS isSystem,
DROP COLUMN IF EXISTS isEditable,
DROP COLUMN IF EXISTS status;

-- ============================================
-- VERIFICATION
-- ============================================
SELECT 
    groupID,
    groupName,
    minSpent,
    maxSpent,
    color,
    description
FROM customer_group
ORDER BY minSpent;

-- Expected Result:
-- +---------+-----------+----------+----------+---------+-------------------+
-- | groupID | groupName | minSpent | maxSpent | color   | description       |
-- +---------+-----------+----------+----------+---------+-------------------+
-- |       1 | Bronze    |        0 |  4999999 | #cd7f32 | Chi tieu 0-5tr    |
-- |       2 | Silver    |  5000000 | 14999999 | #99a6b8 | Chi tieu 5-15tr   |
-- |       3 | Gold      | 15000000 | 29999999 | #fbbf24 | Chi tieu 15-30tr  |
-- |       4 | Platinum  | 30000000 | 49999999 | #42e9ff | Chi tieu 30-50tr  |
-- |       5 | Diamond   | 50000000 |     NULL | #2042ee | Chi tieu hon 50tr |
-- +---------+-----------+----------+----------+---------+-------------------+

-- ============================================
-- THAY ĐỔI TRONG CODE
-- ============================================

/*
1. MODEL (mCustomerGroup.php):
   - ❌ XÓA: validateNoGap()
   - ❌ XÓA: checkForGaps()
   - ❌ XÓA: checkOverlappingRange()
   → Không cần validation gap nữa vì cố định

2. CONTROLLER (cCustomerGroup.php):
   - ❌ addGroup() → Trả về lỗi "Không thể thêm nhóm mới"
   - ✅ updateGroup() → CHỈ cho sửa: groupName, description, color, status
   - ❌ deleteGroup() → Trả về lỗi "Không thể xóa nhóm"

3. VIEW (customer_groups.php):
   - ❌ Ẩn nút "Thêm nhóm"
   - ❌ Ẩn nút "Xóa"
   - 🔒 Disable input minSpent/maxSpent (chỉ hiển thị, không cho sửa)

4. LOGIC:
   - 5 hạng cố định: Bronze, Silver, Gold, Platinum, Diamond
   - Owner CHỈ có thể:
     ✅ Đổi tên nhóm (VD: Bronze → "Thành viên Đồng")
     ✅ Đổi màu sắc
     ✅ Sửa mô tả
   - Owner KHÔNG thể:
     ❌ Thêm nhóm mới
     ❌ Xóa nhóm
     ❌ Sửa minSpent/maxSpent
     ❌ Tắt nhóm (luôn hoạt động)
*/

-- ============================================
-- ROLLBACK (NẾU CẦN)
-- ============================================

-- Để rollback, thêm lại cột (nếu muốn):
-- ALTER TABLE customer_group ADD COLUMN isSystem TINYINT(1) DEFAULT 0;
-- ALTER TABLE customer_group ADD COLUMN isEditable TINYINT(1) DEFAULT 1;
-- ALTER TABLE customer_group ADD COLUMN status TINYINT(1) DEFAULT 1;
-- 
-- Sau đó restore lại code cũ từ git:
-- git checkout HEAD~1 -- model/mCustomerGroup.php
-- git checkout HEAD~1 -- controller/admin/cCustomerGroup.php
-- git checkout HEAD~1 -- admin/pages/customer_groups.php

-- ============================================
-- DONE!
-- ============================================

/*
✅ Đã khóa 5 hạng cố định
✅ Không thể thêm/xóa nhóm
✅ Chỉ cho sửa: tên, màu, mô tả
✅ Không còn lo gap/overlap
✅ Logic đơn giản, dễ maintain
*/
