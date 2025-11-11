# TÍNH NĂNG GHI CHÚ ĐỠN HÀNG (ORDER NOTES)
**Ngày triển khai:** 10/11/2024  
**Feature:** Internal order notes for admin staff

---

## 📝 TỔNG QUAN

**Mục đích:**  
Cho phép nhân viên admin ghi chú nội bộ về đơn hàng (chỉ admin mới thấy, khách hàng không thấy).

**Use cases:**
- 🔹 Ghi nhận yêu cầu đặc biệt của khách hàng
- 🔹 Ghi chú về vấn đề phát sinh (VD: "Khách yêu cầu giao trước 15h")
- 🔹 Lưu lại lịch sử trao đổi với khách
- 🔹 Ghi nhớ thông tin quan trọng cho lần xử lý tiếp theo

---

## 🎯 CHỨC NĂNG

### 1. Quyền truy cập

| Vai trò | Xem ghi chú | Sửa ghi chú |
|---------|-------------|-------------|
| Owner | ✅ | ✅ |
| Admin | ✅ | ✅ |
| Sales | ✅ | ✅ |
| Support | ✅ | ❌ (Chỉ xem) |
| Customer | ❌ | ❌ |

**Logic quyền:**
- `hasPermission('update_order_status')` → Có thể edit note
- Nếu không có quyền → Chỉ xem note (read-only)

### 2. Giao diện

**Vị trí:** Admin → Đơn hàng → Chi tiết đơn hàng (modal)

**UI Components:**
- 📋 **Textarea** màu tím (purple-50 background)
- 💾 **Nút "Lưu ghi chú"** màu tím
- ✅ **Status indicator** hiển thị trạng thái lưu (spinner/success/error)

**Hiển thị:**
- Nếu có quyền edit → Textarea editable + nút Lưu
- Nếu không có quyền nhưng có note → Hiển thị read-only
- Nếu không có note và không có quyền → Ẩn phần ghi chú

### 3. Tính năng kỹ thuật

**Frontend (JavaScript):**
```javascript
function saveOrderNote() {
    - Lấy note từ textarea
    - Lấy orderID từ dataset
    - POST AJAX đến backend
    - Hiển thị status (loading/success/error)
    - Auto clear status sau 3 giây
}
```

**Backend (PHP):**
```php
// AJAX endpoint: ?page=orders&action=update_order_note
- Check permission: hasPermission('update_order_status')
- Validate orderID
- Sanitize note (trim)
- Call orderModel->updateOrderNote()
- Return JSON response
```

**Database:**
```sql
-- Cột: `order`.`note`
-- Type: TEXT (nullable)
-- Purpose: Internal notes for admin staff only
UPDATE `order` SET note = ? WHERE orderID = ?
```

---

## 🔧 FILES MODIFIED

### 1. `admin/pages/orders.php`

**HTML Changes:**
```php
<!-- Added after "Lý do hủy đơn" section -->
<div class="bg-purple-50 p-4 rounded-lg border-l-4 border-purple-500">
    <h4>Ghi chú nội bộ</h4>
    <textarea id="detail_note" rows="3"></textarea>
    <button onclick="saveOrderNote()">Lưu ghi chú</button>
    <span id="note_save_status"></span>
</div>
```

**JavaScript Functions:**
- `saveOrderNote()` - Save note via AJAX
- Load note when opening detail modal
- Display read-only note for non-edit users

**Backend Handlers:**
- `POST action=update_order_note` - Update note endpoint

### 2. `model/mOrder.php`

**New Method:**
```php
public function updateOrderNote($orderId, $note) {
    $sql = "UPDATE `order` SET note = ? WHERE orderID = ?";
    $stmt = mysqli_prepare($this->conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $note, $orderId);
    return mysqli_stmt_execute($stmt);
}
```

**Existing Methods:**
- `getOrderById()` - Already returns `note` column ✅
- All SELECT queries already include `o.*` so note is included ✅

---

## ✅ TESTING CHECKLIST

### Manual Testing:

- [ ] Owner có thể thấy textarea và lưu note
- [ ] Admin có thể thấy textarea và lưu note  
- [ ] Sales có thể thấy textarea và lưu note
- [ ] Support chỉ thấy note read-only (nếu có)
- [ ] Lưu note thành công → Hiển thị "Đã lưu!"
- [ ] Reload modal → Note vẫn còn
- [ ] Note rỗng → Có thể xóa note (set empty string)
- [ ] AJAX error → Hiển thị thông báo lỗi
- [ ] Permission check hoạt động đúng
- [ ] UI responsive trên mobile

### Security Testing:

- [ ] Check SQL injection (prepared statement ✅)
- [ ] Check XSS (htmlspecialchars needed in display)
- [ ] Check permission bypass attempts
- [ ] Validate orderID is integer

---

## 🎨 UI/UX DETAILS

**Color Scheme:**
- Background: `bg-purple-50` (nhẹ nhàng, phân biệt với các section khác)
- Border: `border-purple-500` (accent màu tím)
- Button: `bg-purple-600` hover `bg-purple-700`

**Icons:**
- 📝 `fas fa-sticky-note` - Biểu tượng ghi chú
- 💾 `fas fa-save` - Nút lưu
- ⚙️ `fas fa-spinner fa-spin` - Loading state
- ✅ `fas fa-check-circle` - Success state
- ❌ `fas fa-times-circle` - Error state

**Placeholder:**
```
"Nhập ghi chú về đơn hàng này (chỉ admin mới thấy)..."
```

---

## 📊 THỐNG KÊ SỬ DỤNG

**Hiện tại:**
- Total orders: 24
- Orders with notes: 3 (12.5%)
- Orders without notes: 21 (87.5%)

**Mục tiêu:**
- Tăng tỷ lệ sử dụng note cho các đơn hàng có yêu cầu đặc biệt
- Cải thiện communication giữa các nhân viên xử lý đơn

---

## 🔮 FUTURE ENHANCEMENTS

**Possible improvements:**

1. **Note History:**
   - Track who added/edited note and when
   - Show edit history (requires new table: `order_note_history`)

2. **Rich Text Editor:**
   - Support bold, italic, bullet points
   - Add mentions (@username)

3. **Note Templates:**
   - Quick insert common phrases
   - Example: "Khách yêu cầu giao ngoài giờ", "Đơn ưu tiên"

4. **Notifications:**
   - Alert staff when note is updated
   - Email notification for important notes

5. **Note Tags:**
   - Tag notes by category: "urgent", "special-request", "issue"
   - Filter orders by note tags

6. **Public vs Private Notes:**
   - Private note: Chỉ admin xem
   - Public note: Khách hàng cũng thấy (customer-facing)

---

## 🐛 KNOWN ISSUES

None currently - Feature mới triển khai.

---

## 📚 RELATED DOCUMENTATION

- `docs/DATABASE_AUDIT_REPORT.md` - Analysis of `note` column usage
- `migrations/remove_order_userID.sql` - Related order table cleanup
- `admin/pages/orders.php` - Main implementation file

---

## 👨‍💻 DEVELOPER NOTES

**Best Practices:**
- ✅ Use prepared statements (SQL injection prevention)
- ✅ Check permissions before save
- ✅ Trim user input
- ✅ Use AJAX for better UX (no page reload)
- ✅ Clear, user-friendly error messages
- ⚠️ TODO: Add htmlspecialchars() when displaying note to prevent XSS

**Code Maintainability:**
- Function `saveOrderNote()` is self-contained
- Easy to extend with validation/sanitization
- Clear separation between frontend/backend logic
