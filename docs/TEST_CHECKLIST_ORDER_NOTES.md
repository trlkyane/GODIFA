# TEST CHECKLIST - Order Notes & UserID Removal
**Date:** 10/11/2024

---

## 🧪 CRITICAL TESTS (Must test before going live)

### ⚠️ Order Creation (UserID Removal Impact)
```
[ ] Khách hàng có thể đặt hàng thành công từ trang web
[ ] Checkout flow hoàn tất không có lỗi
[ ] Order được lưu vào database đúng
[ ] Payment redirect hoạt động bình thường
[ ] Email confirmation gửi đi (nếu có)
```

### 📋 Order Management - Admin Panel
```
[ ] Trang danh sách đơn hàng load được (/admin?page=orders)
[ ] Hiển thị đầy đủ thông tin khách hàng (không còn "Nhân viên xử lý")
[ ] Search đơn hàng theo mã đơn hàng hoạt động
[ ] Search đơn hàng theo số điện thoại hoạt động
[ ] Filter theo trạng thái hoạt động
[ ] Pagination hoạt động
```

### 🔍 Order Detail Modal
```
[ ] Click "Chi tiết" mở modal thành công
[ ] Hiển thị đầy đủ thông tin khách hàng
[ ] Hiển thị đầy đủ thông tin đơn hàng
[ ] Hiển thị đúng danh sách sản phẩm
[ ] Hiển thị đúng tổng tiền
[ ] Hiển thị lý do hủy (nếu đơn bị hủy)
[ ] Không còn hiển thị "Nhân viên xử lý" ✅
```

---

## 📝 ORDER NOTES FEATURE TESTS

### Owner/Admin/Sales (Có quyền edit)
```
[ ] Thấy textarea màu tím "Ghi chú nội bộ"
[ ] Thấy nút "Lưu ghi chú"
[ ] Textarea có placeholder text
[ ] Có thể nhập text vào textarea
```

#### Test Save Note:
```
[ ] Click "Lưu ghi chú" → Hiển thị spinner "Đang lưu..."
[ ] Lưu thành công → Hiển thị "✅ Đã lưu!"
[ ] Status tự động mất sau 3 giây
[ ] Đóng modal và mở lại → Note vẫn còn
[ ] Có thể sửa note và lưu lại
[ ] Có thể xóa note (để trống textarea và lưu)
```

#### Test Error Handling:
```
[ ] Tắt internet → Lưu → Hiển thị "❌ Lỗi kết nối"
[ ] Logout → Lưu → Hiển thị lỗi permission (nếu test được)
```

### Support (Chỉ xem, không edit)
```
[ ] Không thấy textarea edit được
[ ] Nếu có note → Thấy note read-only (màu tím nhạt)
[ ] Nếu không có note → Không thấy section ghi chú
[ ] Không có nút "Lưu ghi chú"
```

### Customer (Không thấy gì)
```
N/A - Khách hàng không truy cập admin panel
```

---

## 🔒 SECURITY TESTS

### SQL Injection:
```
[ ] Thử nhập SQL trong note: ' OR 1=1 --
[ ] Kiểm tra database → Note được lưu nguyên văn (không execute)
```

### XSS:
```
⚠️ KNOWN ISSUE: Chưa có htmlspecialchars() cho read-only display
[ ] Thử nhập: <script>alert('XSS')</script>
[ ] Reload → Kiểm tra có execute không?
[ ] TODO: Fix bằng cách thêm htmlspecialchars()
```

### Permission Bypass:
```
[ ] User không có quyền không thể POST đến update_order_note
[ ] Backend trả về error nếu không có permission
```

---

## 📱 RESPONSIVE TESTS

### Desktop (1920x1080):
```
[ ] Modal hiển thị đẹp
[ ] Textarea đủ rộng
[ ] Nút "Lưu" không bị che
```

### Tablet (768px):
```
[ ] Modal responsive
[ ] Textarea không bị tràn
[ ] Có thể scroll nếu nội dung dài
```

### Mobile (375px):
```
[ ] Modal fit màn hình
[ ] Có thể nhập text dễ dàng
[ ] Nút "Lưu" dễ bấm (đủ lớn)
```

---

## 🐛 EDGE CASES

### Empty Note:
```
[ ] Note rỗng (empty string) → Lưu thành công
[ ] Reload → Section ghi chú không hiển thị (cho read-only users)
[ ] Edit user vẫn thấy textarea trống
```

### Very Long Note:
```
[ ] Nhập note dài 1000+ characters
[ ] Lưu thành công (TEXT column supports this)
[ ] Hiển thị đúng với scroll trong textarea
[ ] Read-only view có whitespace-pre-wrap
```

### Special Characters:
```
[ ] Nhập note với: "Quote", 'Quote', <html>, &amp;
[ ] Lưu thành công
[ ] Hiển thị đúng characters
[ ] Không bị encode lỗi
```

### Unicode/Vietnamese:
```
[ ] Nhập: Tiếng Việt có dấu: á é í ó ú ắ ẵ ơ ư
[ ] Nhập emoji: 😀 🎉 ✅ ❌
[ ] Lưu và hiển thị đúng
```

### Multiple Users:
```
[ ] User A sửa note
[ ] User B mở modal cùng lúc
[ ] User A lưu
[ ] User B đóng và mở lại → Thấy note mới của User A
```

---

## ✅ BROWSER COMPATIBILITY

```
[ ] Chrome (latest)
[ ] Firefox (latest)
[ ] Edge (latest)
[ ] Safari (nếu có Mac)
```

---

## 🎯 PERFORMANCE

```
[ ] Save note < 500ms response time
[ ] Load order detail với note < 1s
[ ] Không có memory leak khi mở/đóng modal nhiều lần
```

---

## 📊 TESTING PRIORITY

**P0 (Critical - Must test):**
- ✅ Order creation still works
- ✅ Order list loads
- ✅ Order detail modal displays
- ✅ Save note works for Owner/Admin/Sales

**P1 (High):**
- ✅ Read-only note for Support
- ✅ Error handling
- ✅ Note persists after reload

**P2 (Medium):**
- ⚠️ XSS protection (add htmlspecialchars)
- ✅ Long notes
- ✅ Special characters

**P3 (Low):**
- ✅ Mobile responsive
- ✅ Multiple browsers
- ✅ Performance

---

## 🚀 GO-LIVE CHECKLIST

```
[ ] All P0 tests passed
[ ] All P1 tests passed
[ ] Known issues documented
[ ] Backup database before deploy
[ ] Migration script executed (userID already dropped ✅)
[ ] Clear PHP opcache if using
[ ] Monitor error logs after deployment
[ ] Test one order creation in production
[ ] Test one note save in production
```

---

## 📝 NOTES

**Known Issues:**
1. ⚠️ XSS protection needed for read-only note display
   - Fix: Add `htmlspecialchars($order['note'])` in orders.php
   - Priority: Medium (Low risk - only admin can edit notes)

**Future Improvements:**
- Note edit history
- Rich text editor
- Note templates
- Auto-save (save on blur)

---

**Testing Date:** ___________  
**Tested By:** ___________  
**Status:** [ ] PASS [ ] FAIL  
**Notes:** ___________
