# Danh sách các trạng thái Thanh toán & Giao hàng - GODIFA

## I. TRẠNG THÁI THANH TOÁN (Payment Status)

**Field**: `order.paymentStatus` - VARCHAR(50)

### Các giá trị có thể:

| Trạng thái | Mô tả | Ghi chú |
|-----------|-------|--------|
| `Chờ thanh toán` | Đơn hàng tạo mới, chưa thanh toán | Trạng thái ban đầu khi tạo đơn |
| `Chờ thanh toán (COD)` | Thanh toán khi nhận hàng | Cho phương thức COD (Cash On Delivery) |
| `Đã thanh toán` | Đã nhận tiền từ khách | Khi webhook SePay xác nhận thành công |
| `Đã hủy` | Đơn hàng bị hủy | Hủy bởi khách hoặc admin |

### Luồng thanh toán:

```
Chờ thanh toán (QR)
    ↓ [SePay webhook xác nhận]
Đã thanh toán

Chờ thanh toán (COD)
    ↓ [Admin xác nhận giao hàng]
Đã thanh toán

Chờ thanh toán / Đã thanh toán
    ↓ [Khách/Admin hủy]
Đã hủy
```

---

## II. TRẠNG THÁI GIAO HÀNG (Delivery Status)

**Field**: `order.deliveryStatus` - ENUM

### Các giá trị định nghĩa:

| Trạng thái | Mô tả | Điều kiện |
|-----------|-------|----------|
| `Chờ xác nhận` | Đơn mới, chờ admin xác nhận | Trạng thái mặc định khi tạo đơn |
| `Đang tiến hành vận chuyển` | Admin đã xác nhận, đã gửi lên hãng VC | Bước middle của quy trình |
| `Đang giao` | Hàng đang trên đường giao | Cập nhật từ webhook GHN |
| `Hoàn thành` | Giao hàng thành công | Khách hàng đã nhận hàng |
| `Đã hủy` | Đơn hàng bị hủy | Hủy trước khi giao |
| `Chờ xử lý hoàn tiền` | Chờ xử lý hoàn tiền cho khách | Khi đơn bị hủy sau khi thanh toán |
| `Đã hoàn tiền` | Đã hoàn tiền cho khách | Hoàn tiền thành công |
| `Yêu cầu hoàn trả` | Khách yêu cầu hoàn trả hàng | Sau khi nhận hàng |
| `Đang hoàn trả` | Hàng đang được hoàn về kho | Khách/Admin đã xác nhận |
| `Đã hoàn trả` | Hàng đã hoàn về kho | Hoàn tất quy trình hoàn trả |

### Luồng giao hàng:

```
Chờ xác nhận
    ↓ [Admin xác nhận]
Đang tiến hành vận chuyển
    ↓ [GHN update]
Đang giao
    ↓ [Giao thành công]
Hoàn thành
    ↓ [Khách yêu cầu hoàn trả]
Yêu cầu hoàn trả
    ↓ [Admin/Khách xác nhận]
Đang hoàn trả
    ↓ [Hàng về kho]
Đã hoàn trả

---

Chờ xác nhận / Đang tiến hành vận chuyển
    ↓ [Hủy đơn]
Đã hủy
```

### Trạng thái hoàn tiền:

```
Đã thanh toán + Đã hủy
    ↓ [Chờ xử lý hoàn tiền]
Chờ xử lý hoàn tiền
    ↓ [Admin xử lý/webhook xác nhận]
Đã hoàn tiền
```

---

## III. PHƯƠNG THỨC THANH TOÁN (Payment Method)

**Field**: `order.paymentMethod` - VARCHAR(100)

| Phương thức | Giá trị trong DB | Mô tả |
|-----------|-----------------|-------|
| QR Code | `QR` | Scan QR từ SePay |
| COD | `COD` | Thanh toán khi nhận hàng |

---

## IV. ĐƠN VỊ VẬN CHUYỂN (Shipping Provider)

**Field**: `order.shippingProvider` - VARCHAR(50)

| Đơn vị | Mã | Ghi chú |
|------|-----|--------|
| GHN | `GHN` | Giao Hàng Nhanh (mặc định) |
| J&T | `JT` | JT Express |
| GHTK | `GHTK` | Giao Hàng Tiết Kiệm |

---

## V. ĐỐI TƯỢNG HỦY ĐƠN (Cancelled By)

**Field**: `order.cancelledBy` - ENUM

| Giá trị | Ý nghĩa |
|--------|---------|
| `customer` | Khách hàng hủy |
| `admin` | Admin hủy |

---

## VI. LOGIC SỬ DỤNG TRONG CODE

### 1. **Trigger sau khi cập nhật paymentStatus**
- File: `data/godifa1.sql` (line 521)
- Kích hoạt: Tính toán lại nhóm khách hàng khi `paymentStatus = 'Đã thanh toán'`

### 2. **Trigger hoàn tiền stock khi hủy**
- File: `data/godifa1.sql` (line 503)
- Kích hoạt: Khi `paymentStatus` thay đổi thành `'Đã hủy'`, hoàn lại stock

### 3. **Stored Procedure tự động gán nhóm**
- Chỉ tính tổng tiền từ các đơn có `paymentStatus != 'Đã hủy'`

### 4. **Phía backend (PHP)**
- File: `controller/cOrder.php` - Lọc đơn theo trạng thái
- File: `api/cancel_order.php` - Xử lý hủy đơn
- File: `webhook/sepay.php` - Cập nhật payment status khi nhận webhook

### 5. **Phía frontend (View)**
- File: `view/order/detail.php` - Hiển thị badge màu tương ứng trạng thái
- File: `view/payment/thankyou.php` - Thông báo trạng thái sau thanh toán
- File: `admin/pages/customers.php` - Hiển thị danh sách đơn hàng của khách

---

## VII. QUY TẮC VÀ CONSTRAINTS

### 1. Không thể thay đổi trạng thái:
- ❌ `Hoàn thành` → `Chờ xác nhận` (chỉ có thể → `Yêu cầu hoàn trả`)
- ❌ `Đã hủy` → Bất kỳ trạng thái nào khác
- ❌ `Đã hoàn tiền` → Trạng thái khác

### 2. Chi tiết khi hủy:
- Lưu lý do hủy (`cancelReason`)
- Lưu thời gian hủy (`cancelledAt`)
- Lưu người hủy (`cancelledBy`)
- Hoàn lại số lượng sản phẩm vào kho
- Nếu đã thanh toán QR: Khởi tạo quy trình hoàn tiền

### 3. Thời gian QR:
- Field: `qrExpiredAt`
- Thời hạn: 15 phút từ lúc tạo
- Nếu quá hạn: Cần tạo QR mới

---

## VIII. GHI CHÚ QUAN TRỌNG

⚠️ **Encoding Issues**: Có một số trạng thái trong database bị encoding sai (`'???? thanh to??n'` thay vì `'Đã thanh toán'`). Cần fix bằng script SQL.

⚠️ **Default Status**: 
- `deliveryStatus` mặc định = `'Chờ xác nhận'` (không phải `'Chờ xử lý'`)
- `paymentStatus` không có giá trị mặc định, phải set khi tạo đơn

⚠️ **COD Orders**: Các đơn COD không có `qrUrl` hoặc `qrExpiredAt`

---

## IX. KIỂM TRA VÀ DỌN DẸP

### Tìm các đơn có encoding sai:
```sql
SELECT * FROM `order` 
WHERE paymentStatus LIKE '%?%' 
   OR deliveryStatus LIKE '%?%';
```

### Fix encoding:
```sql
UPDATE `order` 
SET paymentStatus = 'Đã thanh toán' 
WHERE paymentStatus LIKE '%?%';
```

---

**Cập nhật lần cuối**: December 6, 2025
**Trạng thái**: Hoàn chỉnh
