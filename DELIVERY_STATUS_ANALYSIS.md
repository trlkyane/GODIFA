# 📋 PHÂN TÍCH ĐẦY ĐỦ: TRẠNG THÁI GIAO HÀNG TRONG HỆ THỐNG

## 🔍 SO SÁNH: TÀI LIỆU vs THỰC TẾ vs CODE

### Trạng thái trong PAYMENT_DELIVERY_STATUS_LIST.md (Tài liệu)

| # | Trạng thái | Có trong DB? | Có trong Code? | Tình trạng |
|---|---|:---:|:---:|---|
| 1 | `Chờ xác nhận` | ✅ Có (2 đơn) | ✅ Có | ✅ **ĐANG DÙNG** |
| 2 | `Đang tiến hành vận chuyển` | ✅ Có (4 đơn) | ✅ Có | ✅ **ĐANG DÙNG** |
| 3 | `Đang giao` | ❌ Không | ✅ Có | ⚠️ **CODE DƯ** |
| 4 | `Hoàn thành` | ✅ Có (23 đơn) | ✅ Có | ✅ **ĐANG DÙNG** |
| 5 | `Đã hủy` | ✅ Có (5 đơn) | ✅ Có | ✅ **ĐANG DÙNG** |
| 6 | `Chờ xử lý hoàn tiền` | ❌ Không | ✅ Có | ⚠️ **ĐỊNH NGHĨA NHƯNG KHÔNG DÙNG** |
| 7 | `Đã hoàn tiền` | ✅ Có (6 đơn) | ✅ Có | ✅ **ĐANG DÙNG** |
| 8 | `Yêu cầu hoàn trả` | ❌ Không | ❌ Không | ❌ **KHÔNG TỒN TẠI** |
| 9 | `Đang hoàn trả` | ❌ Không | ❌ Không | ❌ **KHÔNG TỒN TẠI** |

### Trạng thái trong CODE nhưng KHÔNG có trong tài liệu:

| # | Trạng thái | File | Dòng | Có trong DB? |
|---|---|---|---|:---:|
| 1 | `Chờ xử lý` | order_history.php | 231 | ❌ |
| 2 | `Đang chuẩn bị hàng` | show_all_order_status.php | 153 | ❌ |
| 3 | `Đã giao` | order_history.php | 228 | ❌ |
| 4 | `Đang xử lý` | order_history.php | 230 | ❌ |
| 5 | `Đã hoàn trả` | return_requests.php | 76 | ❌ |

---

## 📊 THỰC TẾ TRONG DATABASE (44 đơn)

```
✅ Có sử dụng:
- Chờ xác nhận: 6 đơn
- Đang tiến hành vận chuyển: 4 đơn  
- Hoàn thành: 23 đơn
- Đã hủy: 5 đơn
- Đã hoàn tiền: 6 đơn
- NULL: 4 đơn (lỗi)

❌ Không sử dụng:
- Chờ xử lý hoàn tiền: 0 đơn
- Đang giao: 0 đơn
- Yêu cầu hoàn trả: 0 đơn
- Đang hoàn trả: 0 đơn
- Chờ xử lý: 0 đơn
- Đang chuẩn bị hàng: 0 đơn
- Đã giao: 0 đơn
- Đang xử lý: 0 đơn
- Đã hoàn trả: 0 đơn
```

---

## 🎯 KẾT LUẬN VÀ ĐỀ XUẤT

### ✅ 5 TRẠNG THÁI GIỮ LẠI (Đang sử dụng thực tế)

1. **`Chờ xác nhận`** - Đơn mới tạo
2. **`Đang tiến hành vận chuyển`** - Đơn đang ship
3. **`Hoàn thành`** - Đơn giao thành công
4. **`Đã hủy`** - Đơn bị hủy
5. **`Đã hoàn tiền`** - Đơn hoàn trả/hoàn tiền

### ❌ 9 TRẠNG THÁI CẦN XÓA/SỬA

#### Nhóm 1: Có trong tài liệu nhưng KHÔNG dùng (4 trạng thái)

| # | Trạng thái | Lý do xóa | Hành động |
|---|---|---|---|
| 1 | `Chờ xử lý hoàn tiền` | Không có đơn nào sử dụng, logic phức tạp | ❌ **XÓA khỏi tài liệu và code** |
| 2 | `Đang giao` | Thay bằng "Đang tiến hành vận chuyển" | ❌ **XÓA - Trùng nghĩa** |
| 3 | `Yêu cầu hoàn trả` | Không có trong code và DB | ❌ **XÓA khỏi tài liệu** |
| 4 | `Đang hoàn trả` | Không có trong code và DB | ❌ **XÓA khỏi tài liệu** |

#### Nhóm 2: Có trong code nhưng KHÔNG dùng (5 trạng thái)

| # | Trạng thái | File cần sửa | Hành động |
|---|---|---|---|
| 1 | `Chờ xử lý` | order_history.php:231 | ❌ **XÓA** |
| 2 | `Đang chuẩn bị hàng` | show_all_order_status.php:153 | ❌ **XÓA** |
| 3 | `Đã giao` | order_history.php:228 | ❌ **XÓA** (dùng "Hoàn thành") |
| 4 | `Đang xử lý` | order_history.php:230 | ❌ **XÓA** |
| 5 | `Đã hoàn trả` | return_requests.php:76 | ✏️ **ĐỔI** → "Đã hoàn tiền" |

---

## 🔧 DANH SÁCH FILE CẦN SỬA

### 1. Cập nhật tài liệu: `PAYMENT_DELIVERY_STATUS_LIST.md`

**XÓA các trạng thái:**
- ❌ `Chờ xử lý hoàn tiền`
- ❌ `Đang giao`
- ❌ `Yêu cầu hoàn trả`
- ❌ `Đang hoàn trả`

**GIỮ LẠI:**
```markdown
| Trạng thái | Mô tả |
|-----------|-------|
| `Chờ xác nhận` | Đơn mới, chờ admin xác nhận |
| `Đang tiến hành vận chuyển` | Đơn đang được giao |
| `Hoàn thành` | Giao hàng thành công |
| `Đã hủy` | Đơn bị hủy |
| `Đã hoàn tiền` | Đơn hoàn trả hoặc hoàn tiền |
```

### 2. Sửa code PHP:

**File: `view/account/order_history.php` (Dòng 220-231)**
```php
// XÓA các dòng:
'Chờ xử lý hoàn tiền' => 'bg-orange-100 text-orange-800',  // ❌
'Đã giao' => 'bg-green-100 text-green-800',                // ❌  
'Đang giao' => 'bg-blue-100 text-blue-800',                // ❌
'Đang xử lý' => 'bg-blue-100 text-blue-800',               // ❌
'Chờ xử lý' => 'bg-yellow-100 text-yellow-800',            // ❌
```

**File: `view/order/detail.php` (Dòng 209)**
```php
// XÓA dòng:
'Chờ xử lý' => 'bg-yellow-100 text-yellow-800',  // ❌
```

**File: `api/cancel_order.php` (Dòng 210)**
```php
// TRƯỚC:
deliveryStatus = 'Chờ xử lý hoàn tiền',  // ❌

// SAU:
deliveryStatus = 'Đã hủy',  // ✅
```

**File: `admin/pages/return_requests.php` (Dòng 76)**
```php
// TRƯỚC:
SET deliveryStatus = 'Đã hoàn trả',  // ❌

// SAU:
SET deliveryStatus = 'Đã hoàn tiền',  // ✅
```

**File: `show_all_order_status.php` (Dòng 153, 190)**
```php
// XÓA references tới:
'Đang chuẩn bị hàng'  // ❌
```

---

## 📝 LƯU Ý QUAN TRỌNG

### "Chờ xử lý" vs "Chờ xác nhận"

❌ **"Chờ xử lý"** - KHÔNG dùng cho delivery_status
- Chỉ dùng cho `return_requests.status` và `refund_requests.status`
- Là trạng thái của **yêu cầu hoàn trả/hoàn tiền**, không phải đơn hàng

✅ **"Chờ xác nhận"** - Dùng cho delivery_status
- Trạng thái ban đầu của đơn hàng
- Đợi admin xác nhận

### Logic xử lý hủy đơn đã thanh toán

**TRƯỚC ĐÂY:**
```php
// Đặt deliveryStatus = 'Chờ xử lý hoàn tiền'
// Chờ admin vào trang refund_requests xử lý
```

**SAU KHI SỬA:**
```php
// 1. Đặt deliveryStatus = 'Đã hủy'
// 2. Tự động tạo record trong refund_requests với status = 'Chờ xử lý'
// 3. Admin vào refund_requests để xử lý
```

→ **Đơn giản hơn, rõ ràng hơn, dùng đúng bảng để quản lý**

---

## ✅ TỔNG KẾT

### Số liệu:

- **Tài liệu định nghĩa:** 9 trạng thái
- **Thực tế sử dụng:** 5 trạng thái (+ 1 NULL lỗi)
- **Code dư thừa:** 9 trạng thái không dùng
- **Cần xóa/sửa:** 9 trạng thái + 1 file tài liệu + 6 file code

### Sau khi clean up:

✅ **5 trạng thái chuẩn:**
1. Chờ xác nhận
2. Đang tiến hành vận chuyển
3. Hoàn thành
4. Đã hủy
5. Đã hoàn tiền

✅ **Code sạch, không dư thừa**
✅ **Tài liệu khớp với thực tế**
✅ **Dễ bảo trì và mở rộng**

---

*Phân tích được tạo từ `show_all_order_status.php` và manual code review*  
*Ngày: 11/12/2025*
