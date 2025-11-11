# DATABASE MIGRATION - Remove Duplicate Columns

**Date:** 2025-11-08  
**Status:** ✅ COMPLETED

## 🎯 Mục đích
Xóa 5 cột trùng lặp giữa bảng `order` và `order_delivery` để tối ưu database.

## 📋 Chi tiết thay đổi

### Các cột đã XÓA khỏi bảng `order`:
1. ❌ `recipientName` - Đã có trong `order_delivery.recipientName`
2. ❌ `recipientEmail` - Đã có trong `order_delivery.recipientEmail`
3. ❌ `recipientPhone` - Đã có trong `order_delivery.recipientPhone`
4. ❌ `deliveryAddress` - Đã có trong `order_delivery.fullAddress`
5. ❌ `deliveryNotes` - Đã có trong `order_delivery.deliveryNotes`

### Các cột GIỮ LẠI trong bảng `order`:
✅ **Thông tin đơn hàng:** orderID, orderDate, totalAmount, paymentStatus, paymentMethod  
✅ **Người đặt hàng:** customerID, customerName, phone, email, address (người order)  
✅ **Địa chỉ gốc:** provinceId, districtId, wardCode (cho tính phí ship)  
✅ **Thanh toán:** transactionCode, qrUrl, qrExpiredAt, bankTransactionId  
✅ **Vận chuyển:** deliveryStatus, shippingCode, shippingProvider, shippingFee  
✅ **Khác:** voucherID, userID, note, cancelReason, shippingMetadata

## 📊 Dữ liệu trước khi xóa
- Tổng đơn hàng: **22 orders**
- Có recipientName: 13 orders
- Có recipientEmail: 13 orders
- Có recipientPhone: 13 orders
- Có deliveryAddress: 11 orders
- Có deliveryNotes: 10 orders

## 🔒 Backup
```sql
CREATE TABLE order_backup_20251108 AS SELECT * FROM `order`;
```

## ✅ Verification
```sql
-- Test JOIN query vẫn hoạt động
SELECT 
    o.orderID, 
    o.customerName, 
    o.totalAmount,
    od.recipientName,
    od.recipientPhone,
    od.recipientEmail,
    od.fullAddress,
    od.deliveryNotes
FROM `order` o
LEFT JOIN order_delivery od ON o.orderID = od.orderID;
```
✅ Query hoạt động hoàn hảo!

## 📁 Code Impact
- ✅ Tất cả code đã JOIN với `order_delivery` (od.recipientName, od.recipientPhone...)
- ✅ Không có code nào SELECT trực tiếp từ o.recipientName
- ✅ Controller chỉ INSERT vào `order_delivery`, không INSERT vào các cột này của `order`
- ✅ Không cần sửa code gì!

## 🔄 Rollback (nếu cần)
```sql
-- Khôi phục từ backup
DROP TABLE IF EXISTS `order`;
CREATE TABLE `order` AS SELECT * FROM order_backup_20251108;
```

## 📂 File liên quan
- Migration SQL: `/migrations/remove_duplicate_columns.sql`
- Backup table: `order_backup_20251108`

---

**Kết luận:** Migration thành công, database đã tối ưu hơn, không ảnh hưởng đến code hiện tại! 🎉
