# ⚠️ HƯỚNG DẪN QUẢN LÝ NHÓM KHÁCH HÀNG

## 📋 TÓM TẮT

Hệ thống GODIFA tự động phân nhóm khách hàng dựa trên **tổng chi tiêu** (chỉ tính đơn "Đã thanh toán").

---

## ✅ NGUYÊN TẮC QUAN TRỌNG

### 1️⃣ **LUÔN GIỮ 1 NHÓM CÓ `minSpent = 0`**

**Lý do:**
- Khách hàng **MỚI** (chưa từng mua hàng) cần có nhóm
- Khách hàng **chưa thanh toán** đơn hàng nào cần có nhóm
- Đảm bảo KHÔNG có khách hàng bị `groupID = NULL`

**Nhóm mặc định:** Bronze (0 - 5M VND)

---

### 2️⃣ **KHÔNG NÊN SỬA `minSpent` CỦA NHÓM THẤP NHẤT**

**❌ Tránh:**
```
Bronze: 0 → 1M
        ↓
Admin sửa minSpent = 1,000,000
        ↓
Khách chi tiêu < 1M sẽ bị...?
```

**✅ Đúng:**
```
Bronze: 0 - 5M (CỐ ĐỊNH - Không sửa minSpent)
Silver: 5M - 15M
Gold:   15M - 30M
...
```

---

## 🎯 CẤU TRÚC NHÓM ĐỀ XUẤT

| Nhóm | minSpent | maxSpent | Mô tả |
|------|----------|----------|-------|
| **Bronze** | **0** | 4,999,999 | Khách hàng mới & chi tiêu thấp ⚠️ KHÔNG SỬA minSpent! |
| Silver | 5,000,000 | 14,999,999 | Khách hàng trung bình |
| Gold | 15,000,000 | 29,999,999 | Khách hàng tốt |
| Platinum | 30,000,000 | 49,999,999 | Khách hàng VIP |
| Diamond | 50,000,000 | NULL (∞) | Khách hàng VVIP |

---

## 🔧 CÁC THAO TÁC AN TOÀN

### ✅ **An toàn - Có thể làm:**

1. **Thêm nhóm mới:**
   ```
   VIP Special: 100M - ∞
   ```

2. **Sửa maxSpent:**
   ```
   Bronze: 0 - 5M → 0 - 10M ✅
   ```

3. **Sửa minSpent của nhóm CAO HƠN:**
   ```
   Silver: 5M → 8M ✅ (không ảnh hưởng khách chi tiêu = 0)
   ```

4. **Tắt/Bật nhóm:**
   ```
   status = 0 (Tắt) hoặc 1 (Bật)
   ```

---

### ⚠️ **Cẩn thận - Cần suy nghĩ:**

1. **Sửa minSpent của Bronze:**
   ```
   Bronze: 0 → 500,000
   ```
   
   **Hậu quả:**
   - Khách chi tiêu < 500K sẽ vẫn ở Bronze (trigger tự động tìm nhóm thấp nhất)
   - Nhưng logic kém rõ ràng
   - **Khuyến nghị:** KHÔNG NÊN làm!

2. **Xóa nhóm Bronze:**
   ```
   DELETE FROM customer_group WHERE groupName = 'Bronze'
   ```
   
   **Hậu quả:**
   - Khách mới sẽ vào nhóm có minSpent thấp nhất (ví dụ: Silver)
   - Khách chi tiêu < 5M sẽ vào Silver → SAI LOGIC!
   - **Khuyến nghị:** KHÔNG BAO GIỜ xóa Bronze!

---

## 🧪 VẤN ĐỀ & GIẢI PHÁP

### **Vấn đề: Admin sửa Bronze từ 0 → 1M**

**Tình huống:**
```
Admin vào trang "Quản lý nhóm khách hàng"
    ↓
Sửa Bronze: minSpent từ 0 → 1,000,000 VND
    ↓
Lưu thay đổi
    ↓
❓ Khách chi tiêu < 1M sẽ ở đâu?
```

**Giải pháp (ĐÃ XỬ LÝ):**

Trigger `after_customer_group_update_reassign` tự động:
1. Duyệt TOÀN BỘ khách hàng
2. Tính lại tổng chi tiêu từng khách
3. Tìm nhóm phù hợp theo `minSpent` và `maxSpent`
4. **Nếu không tìm thấy → Tìm nhóm có `minSpent` THẤP NHẤT**
5. Cập nhật `groupID`

**Kết quả:**
```
✅ Khách chi tiêu 0 VND → Vẫn ở Bronze
✅ Khách chi tiêu 500K → Vẫn ở Bronze
✅ Khách chi tiêu 1.5M → Vẫn ở Bronze
✅ Khách chi tiêu 6M → Chuyển sang Silver
```

**Tại sao?**
- Bronze vẫn là nhóm có `minSpent` thấp nhất (1M)
- Trigger ưu tiên gán khách vào nhóm thấp nhất khi không tìm thấy nhóm phù hợp

---

## 📝 KHUYẾN NGHỊ

### **Cách tốt nhất: Cố định Bronze = 0**

```sql
-- KHÔNG cho admin sửa minSpent của Bronze
-- Có thể thêm validation ở tầng Controller:

if ($groupName == 'Bronze' && $minSpent != 0) {
    return [
        'success' => false,
        'errors' => ['Không thể sửa minSpent của nhóm Bronze! Phải giữ = 0.']
    ];
}
```

### **Nếu cần linh hoạt:**

1. Tạo nhóm "Khách hàng mới" riêng:
   ```
   Khách hàng mới: 0 - 0 (cố định)
   Bronze: 0 - 5M
   ```

2. Set `isDefault = 1` cho nhóm này
   ```sql
   ALTER TABLE customer_group ADD COLUMN isDefault TINYINT(1) DEFAULT 0;
   UPDATE customer_group SET isDefault = 1 WHERE groupName = 'Khách hàng mới';
   ```

3. Trigger ưu tiên nhóm `isDefault = 1`

---

## ✅ KẾT LUẬN

**Hệ thống đã xử lý tốt:**
- ✅ Khách mới luôn có nhóm
- ✅ Khách chi tiêu = 0 luôn có nhóm
- ✅ Admin sửa nhóm → Tự động phân lại
- ✅ Không bao giờ có `groupID = NULL`

**Khuyến nghị cho Admin:**
- ⚠️ KHÔNG sửa `minSpent` của Bronze khỏi 0
- ✅ Chỉ sửa `maxSpent` của Bronze nếu cần
- ✅ Thêm nhóm mới thoải mái
- ✅ Sửa các nhóm cao hơn thoải mái

---

**Cập nhật:** 30/10/2025  
**Phiên bản:** 2.1 (Hoàn thiện + Xử lý edge case)
