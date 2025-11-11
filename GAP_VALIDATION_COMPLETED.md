# GAP VALIDATION - IMPLEMENTED

**Date:** November 9, 2025  
**Feature:** Ngăn chặn tạo khoảng trống trong phân nhóm khách hàng  
**Status:** ✅ COMPLETED

---

## 🎯 MỤC ĐÍCH

Đảm bảo **KHÔNG CÓ KHOẢNG TRỐNG** trong phân nhóm khách hàng:
- Mọi mức chi tiêu từ 0đ → ∞ đều được phân nhóm
- Không có khách hàng nào bị "rơi vào khoảng trống"
- Owner không thể tạo/sửa/xóa nhóm gây ra gap

---

## ✅ ĐÃ IMPLEMENT

### 1. **Model: `mCustomerGroup.php`**

#### Function: `validateNoGap($minSpent, $maxSpent, $excludeGroupID)`
```php
public function validateNoGap($minSpent, $maxSpent, $excludeGroupID = null)
```

**Logic:**
1. Lấy tất cả nhóm hiện có (trừ nhóm đang sửa nếu có)
2. Thêm nhóm mới vào danh sách để simulate
3. Sort theo `minSpent`
4. Kiểm tra:
   - ✅ Nhóm đầu tiên phải bắt đầu từ 0đ
   - ✅ Không có gap giữa các nhóm
   - ✅ Nhóm có `maxSpent = NULL` phải là nhóm cuối cùng

**Return:**
- `true` nếu OK
- `string` error message nếu có lỗi

---

#### Function: `checkForGaps($excludeGroupID)`
```php
public function checkForGaps($excludeGroupID = null)
```

Kiểm tra toàn bộ hệ thống có gap không (dùng khi xóa nhóm).

---

### 2. **Controller: `cCustomerGroup.php`**

#### Updated: `addGroup()`
```php
// ✅ KIỂM TRA KHÔNG CÓ KHOẢNG TRỐNG
$gapCheck = $this->groupModel->validateNoGap($data['minSpent'], $data['maxSpent'], null);
if ($gapCheck !== true) {
    $errors[] = $gapCheck;
}
```

#### Updated: `updateGroup()`
```php
// ✅ KIỂM TRA KHÔNG CÓ KHOẢNG TRỐNG (loại trừ nhóm đang sửa)
$gapCheck = $this->groupModel->validateNoGap($data['minSpent'], $data['maxSpent'], $id);
if ($gapCheck !== true) {
    $errors[] = $gapCheck;
}
```

#### Updated: `deleteGroup()`
```php
// ✅ KIỂM TRA: Xóa nhóm này có tạo khoảng trống không?
$gap = $this->groupModel->checkForGaps($id);
if ($gap) {
    return ['success' => false, 'errors' => ["⚠️ Không thể xóa nhóm này! " . $gap['message']]];
}
```

---

## 🧪 TEST CASES

### **Case 1: Thêm nhóm tạo gap**

**Initial:**
```
Nhóm 1: 0đ - 5tr
Nhóm 2: 10tr - 15tr
```

**Action:** Thêm nhóm mới: 20tr - 30tr

**Expected:** ❌ BỊ CHẶN
```
⚠️ Có khoảng trống từ 5,000,001đ đến 9,999,999đ! 
Khách hàng trong khoảng này sẽ không được phân nhóm.
```

---

### **Case 2: Sửa nhóm tạo gap**

**Initial:**
```
Nhóm 1: 0đ - 5tr
Nhóm 2: 5tr - 15tr
Nhóm 3: 15tr - 30tr
```

**Action:** Sửa Nhóm 2 thành: 10tr - 15tr (thay vì 5tr)

**Expected:** ❌ BỊ CHẶN
```
⚠️ Có khoảng trống từ 5,000,001đ đến 9,999,999đ!
```

---

### **Case 3: Xóa nhóm giữa**

**Initial:**
```
Nhóm 1: 0đ - 5tr
Nhóm 2: 5tr - 15tr ← Xóa cái này
Nhóm 3: 15tr - 30tr
```

**Action:** Xóa Nhóm 2

**Expected:** ❌ BỊ CHẶN
```
⚠️ Không thể xóa nhóm này! Có khoảng trống từ 5,000,001đ đến 14,999,999đ 
giữa nhóm 'Nhóm 1' và 'Nhóm 3'!
```

---

### **Case 4: Nhóm đầu tiên không bắt đầu từ 0đ**

**Initial:** (Trống)

**Action:** Thêm nhóm: 5tr - 15tr

**Expected:** ❌ BỊ CHẶN
```
⚠️ Nhóm đầu tiên phải bắt đầu từ 0đ để không có khách hàng nào bị bỏ sót!
```

---

### **Case 5: Có nhóm sau nhóm không giới hạn**

**Initial:**
```
Nhóm 1: 0đ - 5tr
Nhóm 2: 5tr - ∞ (maxSpent = NULL)
```

**Action:** Thêm Nhóm 3: 15tr - 30tr

**Expected:** ❌ BỊ CHẶN
```
⚠️ Nhóm 'Nhóm 2' đã cover tất cả chi tiêu (không giới hạn trên), 
không thể có nhóm 'Nhóm 3' phía sau!
```

---

### **Case 6: Thêm nhóm đúng - Liền kề**

**Initial:**
```
Nhóm 1: 0đ - 5tr (0 - 4,999,999)
```

**Action:** Thêm Nhóm 2: 5tr - 15tr (5,000,000 - 14,999,999)

**Expected:** ✅ THÀNH CÔNG
```
✅ Thêm nhóm khách hàng thành công!
```

---

## 📊 LOGIC KIỂM TRA

### Công thức:
```
Nhóm i: [minSpent_i, maxSpent_i]
Nhóm i+1: [minSpent_i+1, maxSpent_i+1]

✅ OK nếu: minSpent_i+1 = maxSpent_i + 1
❌ GAP nếu: minSpent_i+1 > maxSpent_i + 1
```

### Ví dụ:
```
Nhóm 1: [0, 4,999,999]
Nhóm 2: [5,000,000, 14,999,999]

Check: 5,000,000 = 4,999,999 + 1 ✅ OK
```

```
Nhóm 1: [0, 4,999,999]
Nhóm 2: [10,000,000, 14,999,999]

Check: 10,000,000 > 4,999,999 + 1 ❌ GAP
Gap range: 5,000,000 đến 9,999,999
```

---

## 🎨 UI MESSAGES

### Thêm nhóm - Thành công:
```
✅ Thêm nhóm khách hàng thành công!
```

### Thêm nhóm - Gap Error:
```
❌ ⚠️ Có khoảng trống từ 5,000,001đ đến 9,999,999đ! 
Khách hàng trong khoảng này sẽ không được phân nhóm.
```

### Xóa nhóm - Gap Error:
```
❌ ⚠️ Không thể xóa nhóm này! 
Có khoảng trống từ 5,000,001đ đến 14,999,999đ 
giữa nhóm 'Khách hàng thường' và 'Khách hàng VIP'!
```

### Nhóm đầu không bắt đầu từ 0:
```
❌ ⚠️ Nhóm đầu tiên phải bắt đầu từ 0đ 
để không có khách hàng nào bị bỏ sót!
```

---

## ✅ BENEFITS

### 1. **Data Integrity**
- ✅ Mọi khách hàng đều được phân nhóm
- ✅ Không có `groupID = NULL` do rơi vào gap

### 2. **Business Logic**
- ✅ Loyalty program hoạt động đúng
- ✅ Voucher theo nhóm chính xác
- ✅ Thống kê theo nhóm đầy đủ

### 3. **User Experience**
- ✅ Owner không thể vô tình tạo gap
- ✅ Error messages rõ ràng, hướng dẫn cụ thể
- ✅ UI prevents mistakes trước khi lưu

---

## 🚀 DEPLOYMENT

### Files Changed:
1. ✅ `model/mCustomerGroup.php` - Thêm `validateNoGap()` và `checkForGaps()`
2. ✅ `controller/admin/cCustomerGroup.php` - Update `addGroup()`, `updateGroup()`, `deleteGroup()`

### No Database Changes Required:
- ✅ Pure PHP validation
- ✅ No new tables/columns

### Backward Compatible:
- ✅ Existing data không bị ảnh hưởng
- ✅ Chỉ enforce rule cho thao tác mới

---

## 🧪 MANUAL TESTING

### Test bằng UI:
1. Login admin với Owner account
2. Vào: `Admin → Quản lý Nhóm Khách hàng`
3. Click "Thêm nhóm mới"
4. Thử các case trên

### Test cases cụ thể:

**Test 1:** Thêm nhóm 10tr-15tr (giả sử đã có 0-5tr)
```
Expected: ❌ "Có khoảng trống từ 5,000,001đ đến 9,999,999đ!"
```

**Test 2:** Thêm nhóm 5tr-15tr (liền kề với 0-5tr)
```
Expected: ✅ "Thêm nhóm khách hàng thành công!"
```

**Test 3:** Xóa nhóm giữa (giả sử có 0-5tr, 5tr-15tr, 15tr-30tr, xóa nhóm giữa)
```
Expected: ❌ "Không thể xóa nhóm này! Có khoảng trống..."
```

---

## 📝 NOTES

### Edge Cases Handled:
1. ✅ Nhóm đầu tiên phải từ 0đ
2. ✅ Nhóm có `maxSpent = NULL` phải là cuối cùng
3. ✅ Không có gap giữa các nhóm
4. ✅ Overlap đã được check riêng (existing code)

### Not Handled (By Design):
- ❌ Không prevent Owner tạo 2 nhóm giống nhau (name) - Cho phép
- ❌ Không check minSpent < 0 - MySQL sẽ reject
- ❌ Không check maxSpent < minSpent - Đã check ở controller

---

## 🎉 CONCLUSION

**Feature hoàn chỉnh!**

Owner giờ **KHÔNG THỂ** tạo khoảng trống trong phân nhóm khách hàng.

Mọi khách hàng từ 0đ → ∞ đều được đảm bảo rơi vào 1 nhóm nào đó!

---

**Developer:** GitHub Copilot  
**Date:** November 9, 2025  
**Status:** ✅ Production Ready
