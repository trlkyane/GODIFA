# Hướng dẫn triển khai tính năng Hoàn trả đơn hàng

## Tổng quan
Tính năng này cho phép khách hàng yêu cầu hoàn trả sản phẩm **sau khi đã nhận hàng** (khác với Hủy đơn - dùng trước khi nhận hàng).

## Bước 1: Chạy Migration

Chạy file SQL để tạo bảng `return_requests` và thêm column `returnStatus`:

```bash
# Cách 1: Qua phpMyAdmin
- Mở phpMyAdmin
- Chọn database `godifa1`
- Import file: data/migrations/add_return_order_feature.sql

# Cách 2: Qua command line
mysql -u root -p godifa1 < data/migrations/add_return_order_feature.sql
```

## Bước 2: Tạo thư mục upload

Tạo thư mục để lưu ảnh chứng minh:

```bash
mkdir image/return_proofs
chmod 755 image/return_proofs  # Linux/Mac
```

Trên Windows: Tạo thủ công thư mục `image/return_proofs/`

## Bước 3: Kiểm tra quyền

Đảm bảo role admin có quyền xem và xử lý đơn hàng:
- `view_orders` - Xem danh sách yêu cầu
- `update_order_status` - Chấp nhận/từ chối yêu cầu

## Cấu trúc Files

### API
- `api/create_return_request.php` - API khách hàng tạo yêu cầu hoàn trả

### Admin
- `admin/pages/return_requests.php` - Trang quản lý yêu cầu hoàn trả

### Customer View
- `view/account/order_history.php` - Đã thêm nút "Yêu cầu hoàn trả" cho đơn hoàn thành

### Database
- `data/migrations/add_return_order_feature.sql` - Migration SQL

## Quy trình hoạt động

### Phía Khách hàng:
1. Vào "Lịch sử đơn hàng"
2. Với đơn có trạng thái `deliveryStatus = 'Hoàn thành'`, hiển thị nút "Yêu cầu hoàn trả"
3. Nhấn nút → Mở modal nhập:
   - Lý do hoàn trả (bắt buộc)
   - Ảnh chứng minh (tùy chọn, tối đa 5 ảnh)
4. Submit → Gọi API `create_return_request.php`
5. Trạng thái đơn tự động chuyển thành `returnStatus = 'Đang yêu cầu'`

### Phía Admin:
1. Vào menu "Hoàn trả hàng" (có badge số lượng chờ xử lý)
2. Xem danh sách yêu cầu với filters:
   - Chờ xử lý
   - Đã chấp nhận
   - Đã từ chối
   - Đã hoàn tiền
3. Click "Xem" để xem chi tiết:
   - Thông tin khách hàng
   - Lý do hoàn trả
   - Ảnh chứng minh
4. Chấp nhận/Từ chối với ghi chú
5. Khi chấp nhận → `returnStatus = 'Đã chấp nhận'`
6. Admin thực hiện hoàn tiền thủ công → Cập nhật `status = 'Đã hoàn tiền'` → `returnStatus = 'Đã hoàn trả'`

## Triggers tự động

```sql
after_return_request_insert: 
  Khi INSERT vào return_requests → UPDATE order.returnStatus = 'Đang yêu cầu'

after_return_request_update:
  - status = 'Đã chấp nhận' → returnStatus = 'Đã chấp nhận'
  - status = 'Đã từ chối' → returnStatus = 'Đã từ chối'
  - status = 'Đã hoàn tiền' → returnStatus = 'Đã hoàn trả'
```

## Kiểm tra VPS Compatibility

✅ Tất cả đường dẫn sử dụng `BASE_URL` constant
✅ Upload ảnh sử dụng đường dẫn relative
✅ Database connection qua Singleton pattern
✅ Session sử dụng tên custom 'GODIFA_USER_SESSION'

## Testing

### Test Case 1: Khách hàng tạo yêu cầu
1. Đăng nhập tài khoản khách
2. Vào "Lịch sử đơn hàng"
3. Tìm đơn có `deliveryStatus = 'Hoàn thành'`
4. Nhấn "Yêu cầu hoàn trả"
5. Nhập lý do và upload ảnh
6. Kiểm tra trong database: `return_requests` có record mới, `order.returnStatus = 'Đang yêu cầu'`

### Test Case 2: Admin xử lý
1. Đăng nhập admin
2. Vào "Hoàn trả hàng" (menu sidebar)
3. Thấy yêu cầu mới trong tab "Chờ xử lý"
4. Click "Xem" → Modal hiển thị đầy đủ thông tin
5. Chọn "Chấp nhận" với ghi chú
6. Kiểm tra: `return_requests.status = 'Đã chấp nhận'`, `order.returnStatus = 'Đã chấp nhận'`

### Test Case 3: Validation
- Thử tạo yêu cầu hoàn trả cho đơn chưa hoàn thành → Bị chặn
- Thử tạo 2 yêu cầu cho cùng 1 đơn → Lần 2 bị chặn
- Upload ảnh > 5MB → Bị chặn
- Upload > 5 ảnh → Bị chặn

## Lưu ý Deploy lên VPS

1. **Tạo thư mục upload**: Đảm bảo `image/return_proofs/` tồn tại và có quyền ghi (chmod 755)

2. **Chạy migration**: Import file SQL vào database production

3. **Cập nhật permissions**: Kiểm tra role admin có quyền `view_orders` và `update_order_status`

4. **Test upload**: Upload thử 1 ảnh để kiểm tra quyền ghi file

5. **Backup database**: Trước khi chạy migration, backup database để phòng lỗi

## Khác biệt giữa Hoàn tiền và Hoàn trả

| Tính năng | Hoàn tiền (Refund) | Hoàn trả hàng (Return) |
|-----------|-------------------|----------------------|
| **Thời điểm** | Trước/sau khi giao | Sau khi đã nhận hàng |
| **Bảng** | `refund_requests` | `return_requests` |
| **Status column** | `order.deliveryStatus` | `order.returnStatus` |
| **Menu admin** | "Hoàn tiền" | "Hoàn trả hàng" |
| **Icon** | fa-undo (màu cam) | fa-box-open (màu tím) |
| **Use case** | Khách hủy đơn đã thanh toán QR | Khách nhận hàng lỗi/hư hỏng |

## Rollback (nếu cần)

Nếu có vấn đề, rollback bằng cách:

```sql
-- Xóa triggers
DROP TRIGGER IF EXISTS after_return_request_insert;
DROP TRIGGER IF EXISTS after_return_request_update;

-- Xóa column
ALTER TABLE `order` DROP COLUMN `returnStatus`;

-- Xóa bảng
DROP TABLE IF EXISTS `return_requests`;
```

## Support

Nếu có vấn đề, kiểm tra:
1. Error log: Xem file error_log của server
2. Browser console: Kiểm tra lỗi JavaScript
3. Network tab: Xem response từ API
4. Database: Kiểm tra triggers có chạy đúng không
