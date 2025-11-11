# HƯỚNG DẪN SỬA DATABASE CHO GODIFA PROJECT

## 📋 TÓM TẮT VẤN ĐỀ

Database hiện tại có nhiều vấn đề:
- ❌ Bảng `shipping_history` không còn dùng (GHN webhook đã remove)
- ❌ Bảng `order` có quá nhiều ENUM values trong `deliveryStatus` (12 values)
- ❌ Có columns không dùng: `shippingMetadata`, `actualDeliveryTime`, `qrExpiredAt`, `qrUrl`
- ❌ Old delivery statuses (Chờ xử lý, Đang xử lý, Đang giao, Đã giao)
- ❌ Bảng backup cũ: `order_backup_20251108`

## 🎯 MỤC TIÊU

Chuẩn hóa database theo cấu trúc MVC hiện tại:
- ✅ Simplified delivery workflow: 3 trạng thái + Hủy
- ✅ Remove unused tables/columns
- ✅ Fix triggers
- ✅ Auto-payment cho COD
- ✅ Clean structure

## 📂 FILES ĐÃ TẠO

1. **migrations/fix_database_structure.sql**
   - Sửa database HIỆN TẠI (không mất data)
   - Migrate old statuses → new statuses
   - Xóa columns/tables không dùng
   - Fix triggers

2. **data/godifa_clean.sql**
   - Tạo lại database HOÀN TOÀN MỚI (từ đầu)
   - Structure chuẩn, sạch sẽ
   - Chỉ dùng khi muốn reset toàn bộ

## 🚀 OPTION 1: SỬA DATABASE HIỆN TẠI (Recommended)

**Dùng khi:** Muốn giữ data hiện tại, chỉ sửa structure

### Bước 1: Backup database
```bash
# PowerShell
cd C:\wamp64\bin\mysql\mysql8.0.x\bin
.\mysqldump.exe -u root -p godifa1 > C:\wamp64\www\GODIFA\backup_before_fix.sql
```

### Bước 2: Run migration script
1. Mở phpMyAdmin: http://localhost/phpmyadmin
2. Chọn database `godifa1`
3. Vào tab "SQL"
4. Copy toàn bộ nội dung file: `migrations/fix_database_structure.sql`
5. Paste vào và click "Go"

### Bước 3: Verify
Script sẽ tự động chạy verification queries ở cuối:
- Kiểm tra delivery status distribution
- Kiểm tra payment status
- Kiểm tra COD orders cần auto-payment

### Bước 4: Test website
1. Reload admin orders page: http://localhost/GODIFA/admin/pages/orders.php
2. Reload customer order history: http://localhost/GODIFA/view/account/order_history.php
3. Tạo đơn hàng test COD
4. Test workflow: Chờ xác nhận → Đang tiến hành vận chuyển → Hoàn thành

## 🔄 OPTION 2: TẠO LẠI DATABASE MỚI

**Dùng khi:** Muốn reset toàn bộ, start fresh (MẤT DATA)

### Bước 1: Backup (nếu cần)
```bash
.\mysqldump.exe -u root -p godifa1 > C:\wamp64\www\GODIFA\backup_full.sql
```

### Bước 2: Run clean script
1. Mở phpMyAdmin
2. Vào tab "SQL" (không chọn database cụ thể)
3. Copy toàn bộ nội dung file: `data/godifa_clean.sql`
4. Paste và click "Go"

### Bước 3: Import data (nếu cần)
- Import lại data từ backup
- Hoặc bắt đầu với database trống

## 📊 THAY ĐỔI CHI TIẾT

### 1. Delivery Status (QUAN TRỌNG NHẤT)

**Before (12 values):**
```
Chờ xác nhận
Chờ xử lý
Chờ lấy hàng
Đang lấy hàng
Đã lấy hàng
Đang vận chuyển
Đang giao
Đã giao
Giao thất bại
Đang hoàn
Đã hoàn
Đã hủy
```

**After (4 values - Simplified):**
```
Chờ xác nhận          → Khách đặt hàng
Đang tiến hành vận chuyển → Admin xác nhận
Hoàn thành            → Giao thành công
Đã hủy                → Đơn bị hủy
```

**Migration Logic:**
```sql
'Chờ xử lý', 'Chờ lấy hàng' 
  → 'Chờ xác nhận'

'Đang xử lý', 'Đang lấy hàng', 'Đã lấy hàng', 'Đang vận chuyển', 'Đang giao'
  → 'Đang tiến hành vận chuyển'

'Đã giao'
  → 'Hoàn thành'

'Giao thất bại', 'Đang hoàn', 'Đã hoàn', 'Đã hủy'
  → 'Đã hủy'
```

### 2. Xóa Bảng

- ❌ `shipping_history` (GHN webhook không dùng)
- ❌ `order_backup_20251108` (bảng backup cũ)

### 3. Xóa Columns từ `order`

- ❌ `shippingMetadata` (JSON từ GHN webhook)
- ❌ `actualDeliveryTime` (không cần thiết)
- ❌ `qrExpiredAt` (webhook tự động xử lý)
- ❌ `qrUrl` (không lưu trong DB)

### 4. Keep Columns (QUAN TRỌNG)

- ✅ `shippingCode` (Mã vận đơn GHN)
- ✅ `transactionCode` (GODIFA202511080001)
- ✅ `shippingFee` (Phí ship)
- ✅ `cancelReason` (Lý do hủy)

### 5. Auto-Payment Logic

```sql
-- COD orders với deliveryStatus = 'Hoàn thành' tự động thanh toán
UPDATE `order` 
SET paymentStatus = 'Đã thanh toán'
WHERE paymentMethod = 'COD' 
  AND deliveryStatus = 'Hoàn thành'
  AND paymentStatus LIKE '%Chờ thanh toán%';
```

### 6. Trigger Fix

```sql
-- Chỉ tính đơn "Đã thanh toán" cho loyalty points
WHERE paymentStatus = 'Đã thanh toán'  -- Fixed!
-- (Trước đây: paymentStatus != 'Đã hủy')
```

## ✅ VERIFICATION CHECKLIST

Sau khi run migration, check:

- [ ] Tables bị xóa:
  - `shipping_history` không còn
  - `order_backup_20251108` không còn

- [ ] Bảng `order`:
  - `deliveryStatus` chỉ còn 4 values
  - Columns đã xóa: `shippingMetadata`, `actualDeliveryTime`, `qrExpiredAt`, `qrUrl`
  - Columns còn lại: `shippingCode`, `transactionCode`, `cancelReason`

- [ ] Data migration:
  - Tất cả orders có delivery status mới
  - COD orders với status "Hoàn thành" đã auto-paid

- [ ] Website hoạt động:
  - Admin orders page hiển thị đúng
  - Customer order history hiển thị đúng
  - Tạo order mới thành công
  - Update status thành công

## 🆘 TROUBLESHOOTING

### Lỗi: Cannot drop column referenced by foreign key
```sql
-- Tạm disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;
-- Run commands
SET FOREIGN_KEY_CHECKS = 1;
```

### Lỗi: Cannot modify ENUM
```sql
-- Nếu không modify được, drop và recreate:
ALTER TABLE `order` DROP COLUMN `deliveryStatus`;
ALTER TABLE `order` ADD COLUMN `deliveryStatus` ENUM(...) DEFAULT 'Chờ xác nhận';
```

### Restore từ backup
```bash
.\mysql.exe -u root -p godifa1 < C:\wamp64\www\GODIFA\backup_before_fix.sql
```

## 📞 CONTACTS

Nếu có lỗi, check:
1. Backup đã tạo chưa?
2. Script chạy hết chưa? (Xem error message)
3. Verification queries có kết quả gì?

---
**Ngày tạo:** 09/11/2025  
**Version:** 2.0  
**Status:** Ready to deploy
