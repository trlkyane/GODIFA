# FIX CUSTOMER GROUP LOGIC - ISSUE REPORT

**Date:** November 9, 2025  
**Issue:** Logic phân nhóm khách hàng không hợp lý  
**Severity:** 🟡 Medium (UI confusing, nhưng không ảnh hưởng chức năng)

---

## 🔴 VẤN ĐỀ PHÁT HIỆN

### Screenshot của bạn:
```
#1223 - quốc khải - "Khách hàng mới"
#5    - lê hồng minh - "Khách hàng mới"  
#4    - nguyễn thanh tùng - "Khách hàng mới"
#3    - Nguyễn Trung Trúc - "Khách hàng thường"
#2    - Lê Trung Hiếu - "Bronze"
#1    - Ngô Hoàng Khải - "Bronze"
```

**Câu hỏi:** Tại sao có cả "Khách hàng mới", "Khách hàng thường", và "Bronze"?

---

## 🔍 PHÂN TÍCH ROOT CAUSE

### Database hiện tại:
```sql
SELECT groupID, groupName, minSpent, maxSpent, isSystem
FROM customer_group ORDER BY minSpent;
```

Result:
```
+----------+------------------+----------+----------+----------+
| groupID  | groupName        | minSpent | maxSpent | isSystem |
+----------+------------------+----------+----------+----------+
|        8 | Khách hàng mới   |        0 |        0 |        1 |  ← System group
|        1 | Bronze           |        1 |  4999999 |        0 |
|        2 | Silver           |  5000000 | 14999999 |        0 |
|        3 | Gold             | 15000000 | 29999999 |        0 |
|        4 | Platinum         | 30000000 | 49999999 |        0 |
|        5 | Diamond          | 50000000 |     NULL |        0 |
+----------+------------------+----------+----------+----------+
```

### Logic hiện tại:
```sql
-- Trigger: after_order_update_assign_group
SELECT groupID INTO best_group_id
FROM customer_group
WHERE status = 1
  AND customer_total_spent >= minSpent
  AND (maxSpent IS NULL OR customer_total_spent <= maxSpent)
ORDER BY minSpent DESC
LIMIT 1;
```

### Kịch bản:

**Case 1: Khách hàng mới đăng ký (chưa mua gì)**
- `totalSpent = 0`
- Check: `0 >= 0 AND 0 <= 0` → **"Khách hàng mới"** ✅
- Check: `0 >= 1` → ❌ Không vào Bronze

**Case 2: Khách hàng mua đơn đầu tiên (100,000đ)**
- `totalSpent = 100,000`
- Check: `100000 >= 0 AND 100000 <= 0` → ❌ Không vào "Khách hàng mới"
- Check: `100000 >= 1 AND 100000 <= 4999999` → **"Bronze"** ✅

**Case 3: "Khách hàng thường" ở đâu ra?**
- Có thể là manual insert hoặc data cũ
- Không có trong danh sách groups hiện tại!

---

## 🎯 VẤN ĐỀ

### 1. **Naming không consistent**
- Database có: "Khách hàng mới", "Bronze", "Silver", "Gold", "Platinum", "Diamond"
- UI hiển thị: "Khách hàng mới", "Khách hàng thường", "Bronze"
- → **Confusing!**

### 2. **Logic phân nhóm phức tạp**
- "Khách hàng mới" chỉ cho totalSpent = 0
- Sau khi mua 1 đơn → Chuyển sang Bronze
- → **User experience kém** (Khách vừa mua xong đã không còn là "mới")

### 3. **isSystem = 1 không có ý nghĩa**
- Group này được gọi là "system" nhưng không có logic đặc biệt
- Vẫn có thể edit/delete như group thường

---

## ✅ GIẢI PHÁP

### **Option 1: Xóa "Khách hàng mới"**
```sql
DELETE FROM customer_group WHERE groupID = 8;
UPDATE customer_group SET minSpent = 0 WHERE groupID = 1; -- Bronze bắt đầu từ 0đ
```

**Ưu điểm:**
- ✅ Đơn giản
- ✅ Ít groups hơn

**Nhược điểm:**
- ❌ "Bronze" không phù hợp với khách mới

---

### **Option 2: Đổi tên Bronze → Khách hàng thường** ⭐ **RECOMMENDED**
```sql
DELETE FROM customer_group WHERE groupID = 8; -- Xóa "Khách hàng mới"
UPDATE customer_group 
SET groupName = 'Khách hàng thường', 
    minSpent = 0,
    maxSpent = 4999999
WHERE groupID = 1;
```

**Ưu điểm:**
- ✅ Naming rõ ràng, phù hợp Việt Nam
- ✅ Khách mới đăng ký → "Khách hàng thường" (hợp lý)
- ✅ Khách mua nhiều → "Khách hàng trung thành" / "VIP" / "Premium"

**Nhược điểm:**
- ❌ Không có (đây là best practice)

---

### **Option 3: Giữ nguyên, sửa maxSpent**
```sql
UPDATE customer_group SET maxSpent = NULL WHERE groupID = 8;
-- "Khách hàng mới" áp dụng cho tất cả totalSpent >= 0
```

**Ưu điểm:**
- ✅ Không thay đổi nhiều

**Nhược điểm:**
- ❌ Logic vẫn phức tạp
- ❌ Naming không consistent (Bronze, Silver vs Khách hàng mới)

---

## 🚀 KHUYẾN NGHỊ: OPTION 2

### Migration script:
```sql
-- 1. Xóa "Khách hàng mới"
DELETE FROM customer_group WHERE groupID = 8;

-- 2. Đổi tên các groups
UPDATE customer_group SET 
    groupName = 'Khách hàng thường',
    description = 'Chi tiêu dưới 5 triệu',
    minSpent = 0
WHERE groupID = 1;

UPDATE customer_group SET 
    groupName = 'Khách hàng trung thành',
    description = 'Chi tiêu từ 5-15 triệu'
WHERE groupID = 2;

UPDATE customer_group SET 
    groupName = 'Khách hàng VIP',
    description = 'Chi tiêu từ 15-30 triệu'
WHERE groupID = 3;

UPDATE customer_group SET 
    groupName = 'Khách hàng Premium',
    description = 'Chi tiêu từ 30-50 triệu'
WHERE groupID = 4;

UPDATE customer_group SET 
    groupName = 'Khách hàng Kim cương',
    description = 'Chi tiêu trên 50 triệu'
WHERE groupID = 5;

-- 3. Update khách hàng có groupID = 8 về 1
UPDATE customer SET groupID = 1 WHERE groupID = 8;

-- 4. Update khách hàng NULL về 1
UPDATE customer SET groupID = 1 WHERE groupID IS NULL;
```

### Kết quả sau khi fix:
```
+----------+---------------------------+---------------------------+----------+----------+
| groupID  | groupName                 | description               | minSpent | maxSpent |
+----------+---------------------------+---------------------------+----------+----------+
|        1 | Khách hàng thường         | Chi tiêu dưới 5 triệu     |        0 |  4999999 |
|        2 | Khách hàng trung thành    | Chi tiêu từ 5-15 triệu    |  5000000 | 14999999 |
|        3 | Khách hàng VIP            | Chi tiêu từ 15-30 triệu   | 15000000 | 29999999 |
|        4 | Khách hàng Premium        | Chi tiêu từ 30-50 triệu   | 30000000 | 49999999 |
|        5 | Khách hàng Kim cương      | Chi tiêu trên 50 triệu    | 50000000 |     NULL |
+----------+---------------------------+---------------------------+----------+----------+
```

### UI sau khi fix:
```
#1223 - quốc khải - "Khách hàng thường"         (0đ)
#5    - lê hồng minh - "Khách hàng thường"      (50,000đ)
#4    - nguyễn thanh tùng - "Khách hàng thường" (200,000đ)
#3    - Nguyễn Trung Trúc - "Khách hàng thường" (0đ)
#2    - Lê Trung Hiếu - "Khách hàng trung thành" (8,000,000đ)
#1    - Ngô Hoàng Khải - "Khách hàng VIP"       (20,000,000đ)
```

---

## 📊 IMPACT

### Trước khi fix:
- ❌ Confusing naming (Khách hàng mới vs Bronze)
- ❌ Logic phức tạp (minSpent=0, maxSpent=0)
- ❌ UI không consistent

### Sau khi fix:
- ✅ Naming rõ ràng: Thường → Trung thành → VIP → Premium → Kim cương
- ✅ Logic đơn giản: minSpent=0 cho nhóm đầu tiên
- ✅ UI consistent
- ✅ User experience tốt hơn

---

## 🧪 TESTING

1. Chạy migration: `fix_customer_group_logic.sql`
2. Kiểm tra groups: `SELECT * FROM customer_group ORDER BY minSpent`
3. Kiểm tra phân bổ: `SELECT cg.groupName, COUNT(*) FROM customer c JOIN customer_group cg ON c.groupID = cg.groupID GROUP BY cg.groupName`
4. Test case:
   - Đăng ký khách mới → groupID = 1 ("Khách hàng thường")
   - Mua đơn 100k → Vẫn groupID = 1
   - Mua đơn 5tr → groupID = 2 ("Khách hàng trung thành")

---

**Bạn có muốn tôi chạy migration này không?**
