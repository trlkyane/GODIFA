# EDGE CASES - CUSTOMER GROUP ASSIGNMENT

**Date:** November 9, 2025  
**Issue:** Khách hàng không rơi vào nhóm nào khi Owner sửa minSpent  
**Severity:** 🔴 **CRITICAL** - Dữ liệu có thể bị NULL

---

## 🎯 CÂU HỎI:

### 1. **Nếu Owner xóa/tắt tất cả nhóm có minSpent = 0?**
### 2. **Nếu có gap trong phân nhóm (VD: 0-5tr, 10tr-15tr → gap 5-10tr)?**
### 3. **Khách hàng không match nhóm nào sẽ rơi vào đâu?**

---

## 🔴 HIỆN TRẠNG - TRIGGER LOGIC

### Trigger: `after_order_update_assign_group`

```sql
SELECT groupID INTO best_group_id
FROM customer_group
WHERE status = 1
  AND customer_total_spent >= minSpent
  AND (maxSpent IS NULL OR customer_total_spent <= maxSpent)
ORDER BY minSpent DESC
LIMIT 1;

-- Cập nhật
IF best_group_id IS NOT NULL THEN
    UPDATE customer SET groupID = best_group_id WHERE customerID = NEW.customerID;
END IF;
```

### ⚠️ **VẤN ĐỀ:**
```sql
IF best_group_id IS NOT NULL THEN
```

→ **Nếu `best_group_id = NULL` (không tìm thấy nhóm) → KHÔNG UPDATE GÌ CẢ!**

---

## 🧪 TEST CASES

### **Case 1: Khách hàng mới đăng ký (totalSpent = 0)**

#### Scenario A: Có nhóm cover 0đ
```
Groups: 
- "Khách hàng thường": minSpent=0, maxSpent=5tr

Result: ✅ groupID = "Khách hàng thường"
```

#### Scenario B: KHÔNG có nhóm cover 0đ (Owner xóa/sửa)
```
Groups:
- "Bronze": minSpent=1đ, maxSpent=5tr
- "Silver": minSpent=5tr, maxSpent=15tr

Query: WHERE 0 >= minSpent → Không match nhóm nào!
Result: best_group_id = NULL
Action: ❌ KHÔNG UPDATE → groupID = NULL (hoặc giữ nguyên cũ)
```

---

### **Case 2: Gap trong phân nhóm**

```
Groups:
- "Bronze": minSpent=0, maxSpent=5tr
- "Gold": minSpent=15tr, maxSpent=30tr  ← Gap: 5tr-15tr bị bỏ trống!

Customer A: totalSpent = 10tr (rơi vào gap)
Query: WHERE 10000000 >= minSpent AND 10000000 <= maxSpent
  - Bronze: 10tr >= 0 ✅, 10tr <= 5tr ❌
  - Gold: 10tr >= 15tr ❌

Result: best_group_id = NULL
Action: ❌ groupID = NULL hoặc giữ nguyên group cũ
```

---

### **Case 3: Owner tắt status = 0 cho tất cả nhóm**

```
Groups:
- All groups: status = 0 (tạm dừng)

Query: WHERE status = 1 → Không match!
Result: best_group_id = NULL
Action: ❌ groupID = NULL
```

---

### **Case 4: Customer hiện có groupID = 2, sau đó Owner xóa group 2**

```
Initial: Customer groupID = 2 ("Silver")
Action: Owner xóa group 2
Trigger: Chỉ chạy khi có order mới + payment status change

→ Customer VẪN có groupID = 2 (đã bị xóa)
→ JOIN với customer_group sẽ trả về NULL!
```

---

## 🔥 HẬU QUẢ NGHIÊM TRỌNG

### 1. **NULL groupID**
```php
// View: admin/pages/customers.php
$groupName = $customer['groupName'] ?? 'Chưa phân loại';

→ Hiển thị "Chưa phân loại" (không đẹp)
```

### 2. **Orphaned groupID**
```sql
SELECT c.customerName, c.groupID, cg.groupName
FROM customer c
LEFT JOIN customer_group cg ON c.groupID = cg.groupID
WHERE cg.groupID IS NULL;

→ Customer có groupID nhưng group đã bị xóa!
```

### 3. **Không auto-assign lại**
- Trigger CHỈ chạy khi có order mới
- Khách hàng cũ KHÔNG được update lại
- Phải chạy manual: `CALL auto_assign_customer_groups_by_spending()`

---

## ✅ GIẢI PHÁP

### **Solution 1: DEFAULT GROUP (RECOMMENDED)**

Thêm 1 nhóm mặc định không thể xóa:

```sql
INSERT INTO customer_group 
(groupID, groupName, description, minSpent, maxSpent, color, status, isSystem)
VALUES 
(0, 'Chưa phân loại', 'Khách hàng chưa được phân nhóm', 0, NULL, '#gray', 1, 1);
```

**Update Trigger:**
```sql
IF best_group_id IS NOT NULL THEN
    UPDATE customer SET groupID = best_group_id WHERE customerID = NEW.customerID;
ELSE
    -- Fallback về DEFAULT GROUP
    UPDATE customer SET groupID = 0 WHERE customerID = NEW.customerID;
END IF;
```

**Ưu điểm:**
- ✅ Luôn có group cho khách hàng
- ✅ Dễ query: `WHERE groupID = 0` để tìm khách chưa phân loại
- ✅ Owner không thể xóa (isSystem = 1)

**Nhược điểm:**
- ❌ Thêm 1 record vào database

---

### **Solution 2: ENSURE CONTINUOUS RANGE**

Validation khi Owner thêm/sửa/xóa group:

```php
// admin/pages/customer_groups.php
function validateGroupRanges($groups) {
    // Check 1: Phải có nhóm cover minSpent = 0
    $hasZero = false;
    foreach ($groups as $g) {
        if ($g['minSpent'] == 0) $hasZero = true;
    }
    if (!$hasZero) {
        return ['error' => 'Phải có ít nhất 1 nhóm bắt đầu từ 0đ!'];
    }
    
    // Check 2: Không có gap
    usort($groups, fn($a, $b) => $a['minSpent'] <=> $b['minSpent']);
    for ($i = 0; $i < count($groups) - 1; $i++) {
        $current_max = $groups[$i]['maxSpent'];
        $next_min = $groups[$i + 1]['minSpent'];
        
        if ($current_max !== null && $next_min > $current_max + 1) {
            return ['error' => "Gap phát hiện: {$current_max} → {$next_min}"];
        }
    }
    
    return ['success' => true];
}
```

**Ưu điểm:**
- ✅ Đảm bảo không có gap
- ✅ Bắt lỗi trước khi lưu

**Nhược điểm:**
- ❌ Phức tạp
- ❌ Hạn chế flexibility của Owner

---

### **Solution 3: FALLBACK TO LOWEST GROUP**

Nếu không tìm thấy nhóm → Fallback về nhóm có `minSpent` thấp nhất:

```sql
IF best_group_id IS NOT NULL THEN
    UPDATE customer SET groupID = best_group_id WHERE customerID = NEW.customerID;
ELSE
    -- Fallback về nhóm thấp nhất
    SELECT groupID INTO best_group_id
    FROM customer_group
    WHERE status = 1
    ORDER BY minSpent ASC
    LIMIT 1;
    
    IF best_group_id IS NOT NULL THEN
        UPDATE customer SET groupID = best_group_id WHERE customerID = NEW.customerID;
    END IF;
END IF;
```

**Ưu điểm:**
- ✅ Luôn có group (nếu tồn tại ít nhất 1 group active)
- ✅ Đơn giản

**Nhược điểm:**
- ❌ Không đúng logic (customer có 10tr nhưng rơi vào nhóm 0-5tr)

---

### **Solution 4: ALLOW NULL + UI HANDLE**

Để NULL, xử lý ở UI:

```php
// View
if ($customer['groupID'] === null || $customer['groupName'] === null) {
    echo '<span class="badge badge-gray">Chưa phân loại</span>';
} else {
    echo '<span class="badge" style="background: ' . $customer['groupColor'] . '">' 
         . $customer['groupName'] . '</span>';
}
```

**Ưu điểm:**
- ✅ Trung thực (không fake data)
- ✅ Owner nhìn thấy vấn đề

**Nhược điểm:**
- ❌ UI không đẹp
- ❌ Khách hàng thấy "Chưa phân loại" → Không professional

---

## 🚀 KHUYẾN NGHỊ

### **COMBINATION: Solution 1 + Solution 2**

1. **Tạo DEFAULT GROUP (groupID = 0)**
```sql
INSERT INTO customer_group VALUES 
(0, 'Chưa phân loại', 'Khách hàng chưa được phân nhóm tự động', 
 0, NULL, '#9ca3af', 1, 1);
```

2. **Update Trigger với Fallback**
```sql
IF best_group_id IS NOT NULL THEN
    UPDATE customer SET groupID = best_group_id WHERE customerID = NEW.customerID;
ELSE
    UPDATE customer SET groupID = 0 WHERE customerID = NEW.customerID;
END IF;
```

3. **Validation khi Owner sửa groups** (Optional)
- Cảnh báo nếu xóa group có minSpent = 0
- Hiển thị preview: "X khách hàng sẽ rơi vào 'Chưa phân loại'"

4. **UI Enhancement**
```php
// Hiển thị số khách hàng "Chưa phân loại" trên dashboard
SELECT COUNT(*) FROM customer WHERE groupID = 0;
→ Alert Owner: "Có X khách hàng chưa được phân loại!"
```

---

## 📊 MIGRATION SCRIPT

```sql
-- 1. Tạo DEFAULT GROUP
INSERT INTO customer_group 
(groupID, groupName, description, minSpent, maxSpent, color, status, isSystem, createdAt)
VALUES 
(0, 'Chưa phân loại', 'Khách hàng chưa thuộc nhóm cụ thể', 0, NULL, '#9ca3af', 1, 1, NOW())
ON DUPLICATE KEY UPDATE groupName = groupName; -- Nếu đã tồn tại thì không làm gì

-- 2. Update khách hàng có groupID = NULL về 0
UPDATE customer SET groupID = 0 WHERE groupID IS NULL;

-- 3. Fix trigger
DROP TRIGGER IF EXISTS after_order_update_assign_group;
DELIMITER $$
CREATE TRIGGER after_order_update_assign_group AFTER UPDATE ON `order` FOR EACH ROW 
BEGIN
    DECLARE customer_total_spent DECIMAL(15,2);
    DECLARE best_group_id INT;
    
    IF NEW.paymentStatus != OLD.paymentStatus THEN
        SELECT COALESCE(SUM(totalAmount), 0) INTO customer_total_spent
        FROM `order`
        WHERE customerID = NEW.customerID 
          AND paymentStatus = 'Đã thanh toán';
        
        SELECT groupID INTO best_group_id
        FROM customer_group
        WHERE status = 1
          AND isSystem = 0  -- Chỉ lấy groups không phải system
          AND customer_total_spent >= minSpent
          AND (maxSpent IS NULL OR customer_total_spent <= maxSpent)
        ORDER BY minSpent DESC
        LIMIT 1;
        
        -- Fallback về DEFAULT GROUP nếu không tìm thấy
        IF best_group_id IS NULL THEN
            SET best_group_id = 0;
        END IF;
        
        UPDATE customer 
        SET groupID = best_group_id 
        WHERE customerID = NEW.customerID;
    END IF;
END$$
DELIMITER ;

-- 4. Verification
SELECT 
    'Khách hàng chưa phân loại' as category,
    COUNT(*) as total
FROM customer 
WHERE groupID = 0;
```

---

## ✅ KẾT LUẬN

**Câu trả lời:**

1. **Nếu Owner xóa nhóm minSpent=0:**
   - ❌ Khách mới đăng ký → groupID = NULL (hoặc giữ nguyên cũ)
   - ⚠️ UI hiển thị lỗi hoặc "Chưa phân loại"

2. **Nếu có gap trong phân nhóm:**
   - ❌ Khách rơi vào gap → groupID = NULL
   - ⚠️ Data không consistent

3. **Giải pháp:**
   - ✅ Tạo DEFAULT GROUP (groupID=0) "Chưa phân loại"
   - ✅ Trigger fallback về group 0 nếu không match
   - ✅ Owner được cảnh báo khi thay đổi groups

**BẠN CÓ MUỐN TÔI FIX NGAY KHÔNG?**
