# OWNER-ONLY ACCESS CONTROL

**Date:** 2025-11-08  
**Feature:** Phân quyền truy cập chỉ dành cho Chủ Doanh Nghiệp

---

## 🎯 Mục đích

Bảo mật các tính năng nhạy cảm về phân nhóm khách hàng - **CHỈ CHỦ DOANH NGHIỆP** có thể truy cập.

---

## 🔐 Middleware: `admin/middleware/owner_only.php`

### Chức năng:
1. ✅ Kiểm tra session đăng nhập
2. ✅ Kiểm tra `roleID = 1` (Chủ Doanh Nghiệp)
3. ✅ Ghi log truy cập trái phép
4. ✅ Chuyển hướng về dashboard với thông báo lỗi

### Cách sử dụng:
```php
<?php
// Thêm vào đầu file cần bảo vệ
require_once __DIR__ . '/../middleware/owner_only.php';
?>
```

### Security Features:
- **Auto-redirect:** Tự động chuyển về `/admin/index.php?error=permission_denied`
- **Error logging:** Ghi log mọi truy cập trái phép
- **Session check:** Kiểm tra session trước khi check role

---

## 📋 Các trang được bảo vệ

### 1️⃣ Quản lý Nhóm Khách hàng
**File:** `admin/pages/customer_groups.php`

**Quyền truy cập:**
- ✅ Chủ Doanh Nghiệp (roleID = 1)
- ❌ Nhân Viên Quản Trị (roleID = 2)
- ❌ Nhân Viên Bán Hàng (roleID = 3)
- ❌ Nhân Viên CSKH (roleID = 4)

**Chức năng:**
- Tạo/Sửa/Xóa nhóm khách hàng
- Xem danh sách nhóm
- Cấu hình ưu đãi cho từng nhóm

---

### 2️⃣ Phân nhóm tự động
**File:** `admin/pages/auto_assign_groups.php`

**Quyền truy cập:**
- ✅ Chủ Doanh Nghiệp (roleID = 1) ONLY

**Chức năng:**
- Chạy stored procedure phân nhóm
- Xem thống kê phân nhóm
- Cấu hình rules phân nhóm

---

## 🎨 UI/UX Changes

### Sidebar Menu
Menu items có icon **👑 (crown)** màu amber để chỉ rõ quyền hạn:

```
📊 Tổng quan           (Tất cả)
📦 Sản phẩm            (Tất cả)
🏷️ Danh mục            (Tất cả)
🛒 Đơn hàng            (Tất cả)
👥 Khách hàng          (Tất cả)
👑 Nhóm KH            (CHỈ CHỦ DN) ← Màu amber
👑 Phân nhóm tự động  (CHỈ CHỦ DN) ← Màu amber
```

### Hover Effect
```css
border-left: 4px solid transparent;
hover:border-amber-500
```

### Error Alert
Khi nhân viên cố truy cập:
```
⚠️ Bạn không có quyền truy cập trang đó! 
Chỉ Chủ Doanh Nghiệp mới có thể truy cập.
```

Hiển thị ở đầu trang dashboard với:
- Background: `bg-red-100`
- Border: `border-l-4 border-red-500`
- Icon: `fas fa-exclamation-triangle`

---

## 🧪 Test Cases

### Test 1: Chủ Doanh Nghiệp truy cập
```
User: roleID = 1
URL: /admin/index.php?page=customer_groups
Expected: ✅ Hiển thị trang quản lý nhóm KH
```

### Test 2: Nhân viên cố truy cập
```
User: roleID = 2, 3, or 4
URL: /admin/index.php?page=customer_groups
Expected: 
  1. ❌ Bị chặn bởi owner_only.php
  2. 🔄 Redirect về /admin/index.php?error=permission_denied
  3. 📝 Ghi log: "[SECURITY] User #X tried to access..."
  4. 🔔 Hiển thị alert đỏ trên dashboard
```

### Test 3: Chưa đăng nhập
```
Session: Empty
URL: /admin/index.php?page=customer_groups
Expected:
  1. ❌ Bị chặn bởi owner_only.php
  2. 🔄 Redirect về /admin/login.php?error=unauthorized
```

---

## 📊 Database Schema

### Table: `role`
```sql
roleID | roleName              | description
-------+-----------------------+-------------
1      | Chủ Doanh Nghiệp     | Full access
2      | Nhân Viên Quản Trị   | Limited
3      | Nhân Viên Bán Hàng   | Orders only
4      | Nhân Viên CSKH       | Chat + Orders
```

### Table: `user`
```sql
userID | userName | roleID | status
-------+----------+--------+--------
1      | admin    | 1      | active  ← Owner
2      | staff1   | 2      | active  ← Admin
3      | sales1   | 3      | active  ← Sales
```

---

## 🔍 Security Logging

### Log Format:
```
[TIMESTAMP] [SECURITY] User #ID (Name) tried to access owner-only page: /path
```

### Example:
```
[2025-11-08 10:30:45] [SECURITY] User #2 (staff1) tried to access owner-only page: /admin/index.php?page=customer_groups
```

### Log Location:
- PHP error_log (default)
- Can be configured to separate file: `/logs/security.log`

---

## 📝 Code Implementation

### Files Modified:
1. ✅ `admin/middleware/owner_only.php` (NEW)
2. ✅ `admin/pages/customer_groups.php` (added middleware)
3. ✅ `admin/pages/auto_assign_groups.php` (added middleware)
4. ✅ `admin/pages/statistics.php` (added error display)
5. ✅ `admin/includes/sidebar.php` (conditional menu display)

### Lines of Code:
- Middleware: **45 lines**
- Error handling: **15 lines**
- UI changes: **8 lines**
- **Total: 68 lines**

---

## ✅ Benefits

1. **Security:** Ngăn nhân viên xem/sửa phân nhóm khách hàng
2. **Audit Trail:** Ghi log mọi truy cập trái phép
3. **User Experience:** UI rõ ràng với icon 👑
4. **Maintainable:** Dễ mở rộng cho các trang khác
5. **Reusable:** Middleware có thể dùng lại

---

## 🚀 Future Enhancements

1. **Email Alert:** Gửi email cho owner khi có truy cập trái phép
2. **IP Blocking:** Tự động block IP sau N lần vi phạm
3. **2FA:** Two-factor authentication cho owner
4. **Audit Dashboard:** Trang xem log security riêng
5. **Role-based Pages:** Mở rộng cho nhiều role khác

---

## 📞 Support

Nếu cần thêm quyền hạn cho nhân viên:
1. Thay đổi `roleID != 1` trong middleware
2. Hoặc tạo middleware mới cho từng role
3. Update sidebar condition

**⚠️ LƯU Ý:** Không nên cho phép nhân viên truy cập phân nhóm KH vì đây là chiến lược kinh doanh nhạy cảm!

---

**Developed:** 2025-11-08  
**Version:** 1.0  
**Status:** ✅ Production Ready
