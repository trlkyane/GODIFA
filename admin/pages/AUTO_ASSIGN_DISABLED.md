# ⚠️ Tính năng "Phân nhóm tự động" đã bị VÔ HIỆU HÓA

**Ngày:** 2024-11-10  
**Lý do:** Chủ doanh nghiệp đã gán nhóm cố định thủ công cho khách hàng

---

## 📋 Thay đổi

### ❌ Đã xóa:
1. **Menu sidebar**: "Phân nhóm tự động" (icon magic wand)
2. **File đổi tên**: `auto_assign_groups.php` → `auto_assign_groups.php.disabled`

### ✅ Vẫn hoạt động:
1. **Trigger tự động**: `after_order_update_assign_group`
   - Khi khách **THANH TOÁN** đơn hàng
   - Tự động gán vào nhóm phù hợp theo tổng chi tiêu
   - **Trigger này VẪN CHẠY**, không bị ảnh hưởng

2. **Stored Procedure**: `auto_assign_customer_groups_by_spending()`
   - Vẫn tồn tại trong database
   - Có thể chạy thủ công qua MySQL nếu cần

---

## 🎯 Lý do vô hiệu hóa

### Vấn đề:
- Chủ DN **gán nhóm cố định** thủ công cho khách (ví dụ: tất cả vào "Broze")
- Nút "Chạy phân nhóm tự động" sẽ **GHI ĐÈ** lên gán thủ công
- Tự động phân nhóm lại theo tổng chi tiêu → Không đúng ý muốn

### Giải pháp:
- Ẩn nút để tránh staff bấm nhầm
- Trigger vẫn hoạt động: Khách mua hàng → Tự động lên nhóm cao hơn (logic đúng)

---

## 🔄 Cách khôi phục (nếu cần)

### 1. Đổi tên file lại:
```powershell
cd C:\wamp64\www\GODIFA\admin\pages
Rename-Item -Path "auto_assign_groups.php.disabled" -NewName "auto_assign_groups.php"
```

### 2. Thêm menu vào sidebar:
File: `admin/includes/sidebar.php` (sau dòng 136)

```php
<!-- Tự động phân nhóm - CHỈ CHỦ DOANH NGHIỆP -->
<a href="?page=auto_assign_groups" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 border-l-4 border-transparent hover:border-amber-500">
    <i class="fas fa-magic w-5 text-amber-600"></i>
    <span>Phân nhóm tự động</span>
    <i class="fas fa-crown text-amber-500 text-xs ml-auto" title="Chỉ Chủ DN"></i>
</a>
```

---

## 💡 Lưu ý quan trọng

### ⚠️ Khi nào NÊN chạy lại "Phân nhóm tự động"?
1. **Tạo nhóm mới** với quy tắc khác (ví dụ: Silver, Gold, Platinum)
2. **Thay đổi minSpent/maxSpent** của các nhóm
3. **Fix lỗi dữ liệu** (khách có tổng chi 5 triệu nhưng vẫn ở nhóm Bronze)

### ✅ Trigger tự động VẪN HOẠT ĐỘNG:
- Không cần làm gì thêm
- Khách mua hàng mới → Tự động vào nhóm phù hợp
- Khách hiện tại mua thêm → Tự động lên nhóm cao hơn

---

## 🗄️ Database Objects

### Stored Procedures (vẫn tồn tại):
```sql
-- Liệt kê:
SHOW PROCEDURE STATUS WHERE db = 'godifa1' AND name LIKE '%auto%';

-- Chạy thủ công (nếu cần):
CALL auto_assign_customer_groups_by_spending();
```

### Triggers (vẫn hoạt động):
- `after_order_update_assign_group` - Gán nhóm khi thanh toán
- `after_customer_group_update_reassign` - Phân nhóm lại khi sửa nhóm

---

## 📊 Thống kê hiện tại

**Khách hàng:** 6 người  
**Nhóm hiện tại:** Tất cả ở "Broze" (minSpent = 0 VNĐ)  
**Tổng chi tiêu:** 0 VNĐ (chưa có đơn hàng thanh toán)

**Kết luận:** Gán thủ công là hợp lý vì khách chưa có lịch sử mua hàng.
