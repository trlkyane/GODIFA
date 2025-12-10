# 📊 BÁO CÁO ĐẦY ĐỦ TRẠNG THÁI ĐƠN HÀNG

## 🔍 TỔNG QUAN

**Tổng số đơn hàng:** 44 đơn  
**Tổng giá trị:** 3.951.200 đ

---

## 📋 DANH SÁCH TẤT CẢ CÁC TRẠNG THÁI

### Payment Status (5 loại):
1. `Chờ thanh toán`
2. `Chờ thanh toán (COD)`
3. `Đã thanh toán`
4. `Đã hoàn tiền`
5. `Đã hủy`

### Delivery Status (6 loại):
1. `Chờ xác nhận`
2. `Đang chuẩn bị hàng`
3. `Đang tiến hành vận chuyển`
4. `Hoàn thành`
5. `Đã hoàn tiền`
6. `Đã hủy`
7. `NULL` (trống)

---

## 📊 CHI TIẾT TẤT CẢ CÁC TRƯỜNG HỢP (9 tổ hợp)

| # | Payment Status | Delivery Status | Số đơn | Tổng tiền | Trạng thái | Ghi chú |
|---|---|---|---:|---:|:---:|---|
| 1 | `Chờ thanh toán` | `Chờ xác nhận` | 2 | 94.400 đ | ✅ OK | Đơn mới, chờ thanh toán QR/Banking |
| 2 | `Chờ thanh toán (COD)` | `Chờ xác nhận` | 4 | 416.400 đ | ✅ OK | Đơn COD, chờ xác nhận |
| 3 | `Đã thanh toán` | `Đang tiến hành vận chuyển` | 4 | 192.000 đ | ✅ OK | Đơn đang giao hàng |
| 4 | `Đã thanh toán` | `Hoàn thành` | 20 | 1.375.700 đ | ✅ OK | Đơn hoàn tất thành công |
| 5 | `Đã hủy` | `Đã hủy` | 4 | 206.400 đ | ✅ OK | Đơn đã hủy |
| 6 | `Đã hoàn tiền` | `Đã hủy` | 1 | 40.500 đ | ✅ OK | Hủy đơn + hoàn tiền |
| 7 | `Đã hoàn tiền` | `Đã hoàn tiền` | 2 | 101.000 đ | ✅ OK | Hoàn tiền đầy đủ |
| 8 | **`Đã hoàn tiền`** | **`NULL`** | **4** | **287.700 đ** | **❌ SAI** | **CẦN SỬA** |
| 9 | **`Đã hoàn tiền`** | **`Hoàn thành`** | **3** | **1.237.100 đ** | **⚠️ CHECK** | **CẦN KIỂM TRA** |

---

## ⚠️ CÁC TRƯỜNG HỢP BẤT THƯỜNG

### 1. ❌ Đã hoàn tiền + NULL (4 đơn - 287.700 đ)

**Vấn đề:** 
- Payment Status = "Đã hoàn tiền"
- Delivery Status = NULL (trống)
- Hiển thị "Chờ Xử lý" trên giao diện (màu vàng)

**Nguyên nhân:**
- Khi hoàn tiền, chỉ cập nhật `paymentStatus` mà không cập nhật `deliveryStatus`

**Giải pháp:**
```sql
UPDATE `order` 
SET deliveryStatus = 'Đã hủy'
WHERE paymentStatus = 'Đã hoàn tiền' 
AND (deliveryStatus IS NULL OR deliveryStatus = '');
```

**Đề xuất:** ✅ **NÊN SỬA NGAY**

---

### 2. ⚠️ Đã hoàn tiền + Hoàn thành (3 đơn - 1.237.100 đ)

**Vấn đề:**
- Payment Status = "Đã hoàn tiền"
- Delivery Status = "Hoàn thành"
- Logic: Đã giao hàng xong rồi mới hoàn tiền

**Khả năng:**
- **Trường hợp A:** Khách hàng trả hàng sau khi nhận (return request)
- **Trường hợp B:** Hoàn tiền 1 phần do sản phẩm lỗi nhưng giữ hàng
- **Trường hợp C:** Lỗi quy trình xử lý đơn hàng

**Cần kiểm tra:**
- Có phải là đơn trả hàng không?
- Có trong bảng `return_requests` không?
- Có refund_proof trong thư mục `image/refund_proofs/` không?

**Giải pháp:**
- **Nếu là đơn trả hàng:** Giữ nguyên hoặc đổi deliveryStatus = "Đã hoàn tiền"
- **Nếu không phải:** Cần điều tra và sửa lại

**Đề xuất:** ⚠️ **CẦN KIỂM TRA THÊM**

---

## 📈 THỐNG KÊ THEO NHÓM LOGIC

| Nhóm | Số đơn | Tỷ lệ | Tổng tiền | Mô tả |
|---|---:|---:|---:|---|
| **Đơn hoàn tất** | 23 | 52.3% | 2.612.800 đ | Đã thanh toán + Hoàn thành |
| **Đơn chờ xử lý** | 6 | 13.6% | 510.800 đ | Chờ thanh toán/xác nhận |
| **Đơn hoàn tiền** | 6 | 13.6% | 388.700 đ | Đã hoàn tiền |
| **Đơn đã hủy** | 5 | 11.4% | 246.900 đ | Đã hủy |
| **Đơn đang xử lý** | 4 | 9.1% | 192.000 đ | Đang giao hàng |

---

## 💡 KHUYẾN NGHỊ

### 1. Sửa ngay (High Priority)
✅ **Cập nhật 4 đơn "Đã hoàn tiền + NULL" thành "Đã hoàn tiền + Đã hủy"**
```bash
# Trên VPS:
php fix_refunded_orders.php
# Hoặc đổi $autoFix = true trong file
```

### 2. Kiểm tra (Medium Priority)
⚠️ **Xem xét 3 đơn "Đã hoàn tiền + Hoàn thành"**
- Kiểm tra bảng `return_requests`
- Xác định có phải đơn trả hàng không
- Quyết định có cần đổi deliveryStatus không

### 3. Tiêu chuẩn hóa (Low Priority)
📋 **Xây dựng quy trình chuẩn:**

| Tình huống | Payment Status | Delivery Status |
|---|---|---|
| Khách hủy trước khi xác nhận | Đã hủy | Đã hủy |
| Shop hủy đơn đã thanh toán | Đã hoàn tiền | Đã hủy |
| Khách trả hàng sau khi nhận | Đã hoàn tiền | Đã hoàn tiền |
| Đơn hoàn tất bình thường | Đã thanh toán | Hoàn thành |
| Đơn COD giao thành công | Đã thanh toán | Hoàn thành |

---

## 🎯 KẾT LUẬN

✅ **7/9 trường hợp hoạt động bình thường (84.1%)**

❌ **2/9 trường hợp cần xử lý (15.9%):**
- 4 đơn cần sửa ngay (deliveryStatus NULL)
- 3 đơn cần kiểm tra thêm (logic nghiệp vụ)

📊 **Tổng số đơn cần xử lý: 7/44 đơn (15.9%)**

---

*Báo cáo được tạo bởi `show_all_order_status.php`*  
*Ngày: <?php echo date('d/m/Y H:i:s'); ?>*
