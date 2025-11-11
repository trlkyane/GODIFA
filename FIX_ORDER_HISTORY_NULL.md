# FIX: Order History NULL Values Error

**Date:** 2025-11-08  
**Issue:** `htmlspecialchars()` deprecated warning khi truyền NULL

## 🐛 Lỗi gốc
```
Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) of type string 
is deprecated in C:\wamp64\www\GODIFA\view\order_history.php on line 147
```

## 🔍 Nguyên nhân
Sau khi xóa 5 cột duplicate từ bảng `order` (recipientName, recipientEmail, recipientPhone, deliveryAddress, deliveryNotes), dữ liệu recipient giờ chỉ lưu trong `order_delivery`.

Các đơn hàng cũ (trước khi có `order_delivery`) sẽ có `recipientName` = NULL khi JOIN.

## ✅ Giải pháp
Sử dụng **null coalescing operator** (`??`) để xử lý NULL values:

### 1️⃣ view/order_history.php (line 147)
```php
// SAI:
<?= htmlspecialchars($order['recipientName']) ?>

// ĐÚNG:
<?= htmlspecialchars($order['recipientName'] ?? 'Chưa cập nhật') ?>
```

### 2️⃣ view/order/detail.php (line 285-295)
```php
// BEFORE:
<p class="font-semibold"><?= htmlspecialchars($order['recipientName']) ?></p>
<p class="font-semibold"><?= $order['recipientPhone'] ?></p>

// AFTER:
<p class="font-semibold"><?= htmlspecialchars($order['recipientName'] ?? 'Chưa cập nhật') ?></p>
<p class="font-semibold"><?= htmlspecialchars($order['recipientPhone'] ?? '') ?></p>
```

### 3️⃣ view/payment/thankyou.php (line 152-155)
```php
// BEFORE:
<p class="font-semibold mb-2"><?= htmlspecialchars($order['recipientName']) ?></p>
<?= htmlspecialchars($order['recipientPhone']) ?>

// AFTER:
<p class="font-semibold mb-2"><?= htmlspecialchars($order['recipientName'] ?? 'Chưa cập nhật') ?></p>
<?= htmlspecialchars($order['recipientPhone'] ?? '') ?>
```

### 4️⃣ list_pending_orders.php (line 171)
```php
// BEFORE:
<?= htmlspecialchars($order['recipientName']) ?>
<?= $order['recipientPhone'] ?>

// AFTER:
<?= htmlspecialchars($order['recipientName'] ?? 'Chưa cập nhật') ?>
<?= htmlspecialchars($order['recipientPhone'] ?? '') ?>
```

## 🔍 Cải tiến thêm
Thay đổi từ `if ($field)` → `if (!empty($field))` để xử lý chính xác:

```php
// BEFORE:
<?php if ($order['recipientEmail']): ?>

// AFTER:
<?php if (!empty($order['recipientEmail'])): ?>
```

## 📊 Files đã sửa
1. ✅ `view/order_history.php` - Line 147, 149
2. ✅ `view/order/detail.php` - Line 285, 289, 291, 299-303
3. ✅ `view/payment/thankyou.php` - Line 152, 155, 157, 165, 167
4. ✅ `list_pending_orders.php` - Line 171, 172

## ✅ Kết quả
- ✅ Không còn deprecated warning
- ✅ Hiển thị "Chưa cập nhật" cho đơn hàng cũ
- ✅ Đơn hàng mới (có order_delivery) hiển thị bình thường
- ✅ Tất cả trang order đều hoạt động ổn định

## 🧪 Test
```sql
-- Query test: Lấy orders với NULL recipient
SELECT 
    o.orderID, 
    od.recipientName,
    od.recipientPhone
FROM `order` o
LEFT JOIN order_delivery od ON o.orderID = od.orderID
LIMIT 3;
```

**Result:** Order #1-3 có recipientName = NULL → Hiển thị "Chưa cập nhật" ✅
