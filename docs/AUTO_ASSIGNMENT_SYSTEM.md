# 📋 TÀI LIỆU: HỆ THỐNG TỰ ĐỘNG PHÂN NHÓM KHÁCH HÀNG - GODIFA

## 🎯 TÓM TẮT HỆ THỐNG

GODIFA sử dụng **3 TRIGGER + 2 STORED PROCEDURE** để tự động phân nhóm khách hàng theo mức chi tiêu:

---

## ✅ CÁC TRIGGER (Tự động 100%)

### 1️⃣ **TRIGGER: `before_customer_insert_set_group`**

**📍 Kích hoạt:** BEFORE INSERT vào bảng `customer`  
**🎯 Mục đích:** Khách hàng mới tự động vào nhóm Bronze (minSpent = 0)

**📝 Logic:**
```sql
Khách tạo tài khoản
    ↓
Trigger kiểm tra: groupID có NULL không?
    ↓
Nếu NULL → Tìm nhóm có minSpent = 0 (Bronze)
    ↓
SET NEW.groupID = Bronze.groupID
    ↓
Khách tự động vào Bronze ✅
```

**📊 Ví dụ:**
```sql
INSERT INTO customer (customerName, phone, email, password) 
VALUES ('Nguyễn Văn A', '0999999999', 'a@gmail.com', MD5('123456'));

-- Kết quả:
-- customerID: 1224
-- groupID: 1 (Bronze) ← Tự động gán!
```

---

### 2️⃣ **TRIGGER: `after_order_update_assign_group`**

**📍 Kích hoạt:** AFTER UPDATE vào bảng `order`  
**🎯 Mục đích:** Khi khách thanh toán đơn hàng → Tự động nâng hạng real-time

**📝 Logic:**
```sql
Admin xác nhận đơn hàng
    ↓
UPDATE order SET paymentStatus = 'Đã thanh toán'
    ↓
⚡ Trigger tự động chạy:
    1. Tính tổng chi tiêu (chỉ đơn "Đã thanh toán")
    2. Tìm nhóm phù hợp theo minSpent/maxSpent
    3. UPDATE customer SET groupID = newGroupID
    ↓
Khách tự động nâng hạng ✅
```

**📊 Ví dụ:**
```sql
-- Khách A hiện tại: Bronze (chi tiêu 2M)
-- Đặt đơn mới: 10M
-- Admin xác nhận → paymentStatus = 'Đã thanh toán'

⚡ TRIGGER:
    - Tính: 2M + 10M = 12M
    - 12M thuộc Silver (5M-15M)
    - UPDATE customer SET groupID = 2

-- Kết quả: Khách A → Silver ✅
```

---

### 3️⃣ **TRIGGER: `after_customer_group_update_reassign`**

**📍 Kích hoạt:** AFTER UPDATE vào bảng `customer_group`  
**🎯 Mục đích:** Admin sửa minSpent/maxSpent → Tự động phân lại TOÀN BỘ khách hàng

**📝 Logic:**
```sql
Admin sửa Bronze: minSpent từ 0 → 1M
    ↓
UPDATE customer_group SET minSpent = 1000000
    ↓
⚡ Trigger tự động chạy:
    - Duyệt TOÀN BỘ khách hàng
    - Tính lại tổng chi tiêu của từng khách
    - Tìm nhóm mới phù hợp
    - UPDATE customer SET groupID = newGroupID
    ↓
Tất cả khách tự động phân lại ✅
```

**📊 Ví dụ:**
```sql
-- Bronze cũ: 0 - 5M (6 khách)
-- Admin sửa: 1M - 5M

⚡ TRIGGER:
    - Khách chi tiêu < 1M → Không thuộc nhóm nào → groupID = NULL
    - Khách chi tiêu >= 1M → Vẫn ở Bronze

-- Kết quả: 5 khách còn lại trong Bronze ✅
```

---

## 🔧 STORED PROCEDURE (Thủ công khi cần)

### 4️⃣ **PROCEDURE: `auto_assign_customer_groups_by_spending()`**

**📍 Chạy thủ công:** `CALL auto_assign_customer_groups_by_spending();`  
**🎯 Mục đích:** Phân lại TOÀN BỘ khách hàng (dùng 1 lần khi setup hoặc fix data cũ)

**📝 Khi nào dùng:**
- ✅ Lần đầu setup hệ thống (có data khách cũ)
- ✅ Import khách hàng từ hệ thống cũ
- ✅ Fix lỗi phân nhóm sai
- ✅ Sau khi thêm/xóa nhóm mới

**❌ Không cần dùng cho:**
- ❌ Khách hàng mới đăng ký (trigger tự động)
- ❌ Khách thanh toán đơn hàng (trigger tự động)
- ❌ Admin sửa nhóm (trigger tự động)

---

### 5️⃣ **PROCEDURE: `auto_assign_customer_groups()` (CŨ - ĐÃ XÓA)**

**⚠️ Không dùng nữa** vì đã xóa `birthdate` và `gender` từ bảng customer.

---

## 📊 BẢNG SO SÁNH

| Trường hợp | Giải pháp | Tự động? | Khi nào chạy? |
|------------|-----------|----------|---------------|
| **Khách tạo tài khoản mới** | TRIGGER `before_customer_insert_set_group` | ✅ 100% | Ngay khi INSERT |
| **Khách thanh toán đơn hàng** | TRIGGER `after_order_update_assign_group` | ✅ 100% | Khi UPDATE paymentStatus |
| **Admin sửa minSpent/maxSpent** | TRIGGER `after_customer_group_update_reassign` | ✅ 100% | Khi UPDATE customer_group |
| **Setup lần đầu/Fix data cũ** | PROCEDURE `auto_assign_customer_groups_by_spending()` | ❌ Thủ công | Admin chạy 1 lần |

---

## 🎯 FLOW HOÀN CHỈNH

### **Kịch bản 1: Khách hàng MỚI đăng ký**
```
1. Khách điền form đăng ký → Submit
2. INSERT INTO customer (customerName, phone, email, password)
3. ⚡ TRIGGER before_customer_insert_set_group
   → SET groupID = 1 (Bronze)
4. ✅ Khách vào Bronze
```

### **Kịch bản 2: Khách hàng mua hàng lần ĐẦU**
```
1. Khách đặt đơn 10M → Chờ thanh toán
2. Admin xác nhận → UPDATE order SET paymentStatus = 'Đã thanh toán'
3. ⚡ TRIGGER after_order_update_assign_group
   → Tính: 0 + 10M = 10M
   → 10M thuộc Silver (5M-15M)
   → UPDATE customer SET groupID = 2
4. ✅ Khách nâng từ Bronze → Silver
```

### **Kịch bản 3: Khách hàng mua thêm nhiều lần**
```
1. Khách đặt đơn 20M → Xác nhận
2. ⚡ TRIGGER after_order_update_assign_group
   → Tính: 10M + 20M = 30M
   → 30M thuộc Platinum (30M-50M)
   → UPDATE customer SET groupID = 4
3. ✅ Khách nâng từ Silver → Platinum
```

### **Kịch bản 4: Admin sửa nhóm khách hàng**
```
1. Admin vào trang "Quản lý nhóm khách hàng"
2. Sửa Bronze: minSpent từ 0 → 1M
3. UPDATE customer_group SET minSpent = 1000000 WHERE groupID = 1
4. ⚡ TRIGGER after_customer_group_update_reassign
   → Duyệt TẤT CẢ khách hàng
   → Tính lại từng khách
   → Phân nhóm lại
5. ✅ Tất cả khách tự động phân lại
```

---

## 🔍 KIỂM TRA HỆ THỐNG

### **Xem tất cả trigger:**
```sql
SHOW TRIGGERS WHERE `Table` IN ('customer', 'customer_group', 'order');
```

### **Xem stored procedure:**
```sql
SHOW PROCEDURE STATUS WHERE Db = 'godifa';
```

### **Test tạo khách mới:**
```sql
INSERT INTO customer (customerName, phone, email, password) 
VALUES ('Test User', '0999999999', 'test@example.com', MD5('123456'));

-- Kiểm tra groupID
SELECT customerID, customerName, groupID FROM customer ORDER BY customerID DESC LIMIT 1;
```

### **Test phân lại toàn bộ:**
```sql
CALL auto_assign_customer_groups_by_spending();
```

---

## ✅ KẾT LUẬN

**Hệ thống GODIFA đã HOÀN THIỆN 100%:**

1. ✅ **Khách mới đăng ký** → Tự động vào Bronze
2. ✅ **Khách thanh toán đơn** → Tự động nâng hạng real-time
3. ✅ **Admin sửa nhóm** → Tự động phân lại toàn bộ khách hàng
4. ✅ **Setup lần đầu** → Có procedure để phân lại hàng loạt

**Không cần làm gì thêm! Mọi thứ đã TỰ ĐỘNG!** 🎉

---

**Ngày tạo:** 30/10/2025  
**File SQL:** `data/fix_auto_assignment.sql`  
**Phiên bản:** 2.0 (Hoàn thiện)
