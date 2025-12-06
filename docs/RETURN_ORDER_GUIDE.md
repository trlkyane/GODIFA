# 🔄 Hướng Dẫn Chức Năng Hoàn Trả Đơn Hàng

## 📋 Tổng Quan

Chức năng hoàn trả đơn hàng cho phép khách hàng yêu cầu hoàn trả sản phẩm **SAU KHI ĐÃ NHẬN HÀNG** (khác với hủy đơn trước khi giao).

### Phân Biệt: Hủy Đơn vs Hoàn Trả

| Tính năng | Hủy Đơn | Hoàn Trả |
|-----------|---------|----------|
| **Thời điểm** | Trước/trong khi giao hàng | Sau khi đã nhận hàng |
| **Điều kiện** | Đơn chưa được admin xác nhận vận chuyển | Đơn có `deliveryStatus = 'Hoàn thành'` |
| **Quy trình** | 1 bước (Admin xác nhận hủy) | 2 bước (Admin chấp nhận → Hoàn tiền) |
| **Trạng thái** | `paymentStatus = 'Đã hủy'` | `returnStatus` riêng biệt |

---

## 👤 Hướng Dẫn Cho Khách Hàng

### 1. Tạo Yêu Cầu Hoàn Trả

**Điều kiện:**
- Đơn hàng đã được giao thành công (`deliveryStatus = 'Hoàn thành'`)
- Chưa có yêu cầu hoàn trả nào trước đó (`returnStatus = 'Không'`)

**Các bước:**
1. Vào **Tài khoản** → **Lịch sử đơn hàng**
2. Tìm đơn hàng có trạng thái "Hoàn thành"
3. Click nút **"Yêu cầu hoàn trả"** (màu cam)
4. Điền form:
   - **Lý do hoàn trả** (bắt buộc): Mô tả chi tiết vấn đề
   - **Ảnh chứng minh** (tùy chọn): Tối đa 5 ảnh, mỗi ảnh 5MB
5. Click **"Gửi Yêu Cầu"**

**Kết quả:**
- Trạng thái đơn hàng thay đổi sang: **"Đang yêu cầu"** (badge màu vàng)
- Admin sẽ nhận được thông báo

### 2. Theo Dõi Trạng Thái

**Xem trạng thái:**
- Vào **Lịch sử đơn hàng**
- Đơn có yêu cầu hoàn trả sẽ hiển thị **badge màu** bên cạnh trạng thái giao hàng
- Click nút **"Xem yêu cầu hoàn trả"** để xem chi tiết

**Các trạng thái:**

| Trạng thái | Màu | Ý nghĩa | Hành động tiếp theo |
|------------|-----|---------|---------------------|
| 🟡 **Đang yêu cầu** | Vàng | Chờ admin xem xét | Chờ phản hồi (24-48h) |
| 🟢 **Đã chấp nhận** | Xanh lá | Admin đồng ý hoàn trả | Đóng gói hàng, chờ shipper |
| 🔴 **Đã từ chối** | Đỏ | Admin từ chối yêu cầu | Xem lý do từ chối |
| 🟣 **Đã hoàn tiền** | Tím | Đã hoàn tiền thành công | Hoàn tất |

### 3. Nhận Email Thông Báo

Khách hàng sẽ nhận email tự động khi:
- ✅ Admin chấp nhận yêu cầu
- ❌ Admin từ chối yêu cầu
- 💰 Admin xác nhận đã hoàn tiền

---

## 🛠️ Hướng Dẫn Cho Admin

### 1. Truy Cập Trang Quản Lý

**URL:** `admin/index.php?page=return_requests`

**Menu:** Sidebar → **"Hoàn trả hàng"** (có badge số lượng yêu cầu chờ xử lý)

### 2. Xem Danh Sách Yêu Cầu

**Bộ lọc tabs:**
- **Tất cả**: Hiển thị toàn bộ yêu cầu
- **Chờ xử lý**: Yêu cầu mới cần duyệt
- **Đã chấp nhận**: Đã đồng ý, chờ hoàn tiền
- **Đã từ chối**: Yêu cầu bị từ chối
- **Đã hoàn tiền**: Hoàn tất

**Thông tin hiển thị:**
- Mã yêu cầu, Mã đơn hàng, Khách hàng
- Số tiền, Ngày yêu cầu, Trạng thái
- Nút **"Xem chi tiết"**

### 3. Xử Lý Yêu Cầu (Bước 1: Duyệt)

**Khi trạng thái = "Chờ xử lý":**

1. Click **"Xem chi tiết"** yêu cầu
2. Xem:
   - Lý do hoàn trả
   - Ảnh chứng minh (nếu có)
   - Thông tin đơn hàng
3. Quyết định:
   - **Chấp nhận**: Click nút xanh "✓ Chấp nhận"
   - **Từ chối**: Click nút đỏ "✗ Từ chối"
4. Nhập **Ghi chú** (khuyến nghị):
   - Nếu chấp nhận: Hướng dẫn đóng gói, lịch lấy hàng
   - Nếu từ chối: Lý do rõ ràng
5. Xác nhận

**Kết quả:**
- Trạng thái thay đổi → **"Đã chấp nhận"** hoặc **"Đã từ chối"**
- Gửi email tự động cho khách hàng
- Cập nhật `returnStatus` trong bảng `order`

### 4. Hoàn Tiền (Bước 2: Sau khi nhận hàng)

**Khi trạng thái = "Đã chấp nhận":**

1. Chờ nhận được hàng hoàn trả từ khách
2. Kiểm tra tình trạng sản phẩm
3. Vào lại chi tiết yêu cầu
4. Phần **"Xử lý hoàn tiền"** hiện ra:
   - Nhập **Ghi chú giao dịch** (Mã GD, Ngân hàng, Số tài khoản...)
   - Click **"Xác nhận đã hoàn tiền"** (nút tím)
5. Xác nhận

**Kết quả:**
- Trạng thái → **"Đã hoàn tiền"** / **"Đã hoàn trả"**
- Gửi email thông báo hoàn tiền cho khách
- Đơn hàng hoàn tất quy trình

---

## 🗄️ Cấu Trúc Database

### Bảng `return_requests`

```sql
CREATE TABLE return_requests (
    returnID INT AUTO_INCREMENT PRIMARY KEY,
    orderID INT NOT NULL,
    customerID INT NOT NULL,
    reason TEXT NOT NULL,
    images JSON DEFAULT NULL,
    status ENUM('Chờ xử lý', 'Đã chấp nhận', 'Đã từ chối', 'Đã hoàn tiền') DEFAULT 'Chờ xử lý',
    adminNote TEXT DEFAULT NULL,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    processedAt DATETIME DEFAULT NULL,
    processedBy INT DEFAULT NULL
) ENGINE=MyISAM;
```

### Column `order.returnStatus`

```sql
ALTER TABLE `order` 
ADD COLUMN returnStatus ENUM('Không', 'Đang yêu cầu', 'Đã chấp nhận', 'Đã từ chối', 'Đã hoàn trả') 
DEFAULT 'Không';
```

### Triggers (Tự động đồng bộ)

- `after_return_request_insert`: Cập nhật `order.returnStatus = 'Đang yêu cầu'` khi tạo yêu cầu
- `after_return_request_update`: Đồng bộ trạng thái khi admin xử lý

---

## 📁 Files Liên Quan (VPS Compatible)

### Backend (PHP)
- `api/create_return_request.php` - Tạo yêu cầu hoàn trả
- `api/get_return_request.php` - Lấy chi tiết yêu cầu
- `admin/pages/return_requests.php` - Trang quản lý admin
- `includes/send_return_notification.php` - Gửi email thông báo

### Frontend (View)
- `view/account/order_history.php` - Giao diện khách hàng

### Database
- `data/migrations/add_return_order_feature.sql` - Migration tạo bảng

---

## 🔒 Bảo Mật & Validation

### API Security
- ✅ Session check: `GODIFA_USER_SESSION` + `customer_id`
- ✅ Prepared statements: Chống SQL injection
- ✅ Input validation: `orderID`, `reason`, file upload
- ✅ Owner check: Chỉ xem/tạo yêu cầu của chính mình

### File Upload
- **Max files:** 5 ảnh
- **Max size:** 5MB/ảnh
- **Allowed types:** PNG, JPG, JPEG
- **Storage:** `image/return_proofs/`
- **Naming:** `return_[orderID]_[timestamp]_[random].[ext]`

### Admin Permissions
- **view_orders**: Xem danh sách yêu cầu
- **update_order_status**: Duyệt/từ chối/hoàn tiền

---

## 🚀 VPS Compatibility

### BASE_URL Usage
Tất cả đường dẫn sử dụng `BASE_URL` constant:

```php
// PHP
require_once __DIR__ . '/../config/constants.php';
$imageUrl = BASE_URL . 'image/return_proofs/' . $filename;

// JavaScript (truyền từ PHP)
window.BASE_URL = '<?php echo BASE_URL; ?>';
fetch(`${window.BASE_URL}api/get_return_request.php?orderID=${orderID}`);
```

### Configuration
File: `config/constants.php`
```php
define('BASE_URL', $isLocal ? 'http://localhost/GODIFA/' : 'https://godifa.id.vn/');
```

---

## 📊 Workflow Diagram

```
[Khách nhận hàng] 
    ↓
[Phát hiện vấn đề]
    ↓
[Tạo yêu cầu hoàn trả] → Status: "Đang yêu cầu" 🟡
    ↓
[Admin xem xét]
    ├─ [Chấp nhận] → Status: "Đã chấp nhận" 🟢
    │       ↓
    │   [Khách đóng gói hàng]
    │       ↓
    │   [Shipper lấy hàng]
    │       ↓
    │   [Admin nhận hàng & kiểm tra]
    │       ↓
    │   [Admin xác nhận hoàn tiền] → Status: "Đã hoàn tiền" 🟣
    │       ↓
    │   [HOÀN TẤT] ✅
    │
    └─ [Từ chối] → Status: "Đã từ chối" 🔴
            ↓
        [KẾT THÚC] ❌
```

---

## 🧪 Testing Checklist

### User Flow
- [ ] Tạo yêu cầu hoàn trả thành công
- [ ] Upload ảnh (1-5 ảnh, kiểm tra giới hạn)
- [ ] Xem trạng thái yêu cầu
- [ ] Badge hiển thị đúng màu
- [ ] Modal chi tiết hiển thị đầy đủ thông tin

### Admin Flow
- [ ] Xem danh sách yêu cầu
- [ ] Filter tabs hoạt động
- [ ] Badge số lượng chờ xử lý đúng
- [ ] Chấp nhận yêu cầu
- [ ] Từ chối yêu cầu
- [ ] Xác nhận hoàn tiền
- [ ] Ghi chú admin hiển thị đúng

### Email Notifications
- [ ] Email chấp nhận gửi thành công
- [ ] Email từ chối gửi thành công
- [ ] Email hoàn tiền gửi thành công
- [ ] Nội dung email đúng format

### Database
- [ ] Trigger cập nhật `returnStatus` tự động
- [ ] PHP backup sync hoạt động
- [ ] Không có lỗi foreign key (MyISAM)

### VPS Compatibility
- [ ] BASE_URL hoạt động trên localhost
- [ ] BASE_URL hoạt động trên VPS
- [ ] Image paths đúng
- [ ] API endpoints accessible

---

## 📞 Support

**Liên hệ khi cần hỗ trợ:**
- 📧 Email: dev@godifa.vn
- 📱 Hotline: 0123456789
- 💬 Telegram: @godifa_dev

---

**Phiên bản:** 1.0  
**Cập nhật:** December 5, 2025  
**Tương thích VPS:** ✅ Yes
