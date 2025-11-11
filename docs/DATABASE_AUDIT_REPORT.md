# BÁO CÁO KIỂM TRA CÁC CỘT KHÔNG DÙNG TRONG DATABASE
**Ngày kiểm tra:** 10/11/2024  
**Database:** godifa1

---

## 📊 TỔNG QUAN

| Bảng | Tổng cột | Cột có vấn đề | Ghi chú |
|------|----------|---------------|---------|
| order_delivery | 15 | 3 cột có >50% NULL | provinceId, districtId, wardCode |
| order | 14 | 1 cột 87.5% NULL | note (GIỮ LẠI - sẽ implement UI) |
| user | 7 | ✅ OK | Tất cả đều cần thiết |
| customer | 7 | ✅ OK | Đã cleanup trước đó |
| customer_group | 7 | ✅ OK | Đã cleanup trước đó |
| product | 9 | ✅ OK | Tất cả đều cần thiết |
| voucher | 8 | ✅ OK | Tất cả đều cần thiết |
| blog | 5 | ✅ OK | Tất cả đều cần thiết |

---

## 🔍 CHI TIẾT PHÂN TÍCH

### 1. Bảng `order_delivery` (15 cột)

**Phân tích NULL values (14 records total):**

| Cột | NULL Count | % NULL | Đánh giá |
|-----|------------|--------|----------|
| recipientEmail | 0 | 0% | ✅ OK - Luôn có giá trị |
| ward | 0 | 0% | ✅ OK - Luôn có giá trị |
| **provinceId** | 8 | **57.1%** | ⚠️ Hơn nửa NULL |
| **districtId** | 8 | **57.1%** | ⚠️ Hơn nửa NULL |
| **wardCode** | 8 | **57.1%** | ⚠️ Hơn nửa NULL |
| deliveryNotes | 3 | 21.4% | ✅ OK - Chấp nhận được |

**Nguyên nhân:**
- `provinceId`, `districtId`, `wardCode` là các cột ID của GHN API
- Khi nhập địa chỉ thủ công (không dùng GHN dropdown), các cột này để NULL
- Dữ liệu vẫn được lưu ở `city`, `district`, `ward` (dạng text)

**Khuyến nghị:** 
- ✅ **GIỮ LẠI** - Các cột này cần thiết cho tích hợp GHN
- Khi khách hàng chọn địa chỉ từ GHN API → có ID
- Khi nhập thủ công → NULL nhưng vẫn có text ở city/district/ward
- Không phải cột "không dùng", chỉ là optional fields

---

### 2. Bảng `order` (14 cột)

**Phân tích NULL values (24 records total):**

| Cột | NULL Count | % NULL | Đánh giá |
|-----|------------|--------|----------|
| **note** | 21 | **87.5%** | ⚠️ Chủ yếu NULL nhưng CÓ DÙNG |
| voucherID | Một số NULL | - | ✅ OK - Optional voucher |
| shippingCode | Một số NULL | - | ✅ OK - Chờ GHN tạo mã |
| cancelReason | Nhiều NULL | - | ✅ OK - Chỉ khi hủy mới có |
| transactionCode | Một số NULL | - | ✅ OK - QR code payment |

**Khuyến nghị:**
- ✅ **GIỮ LẠI `note`** - Sẽ implement UI để admin có thể ghi chú đơn hàng
- Các cột khác đều có mục đích rõ ràng

---

### 3. Các bảng khác

**✅ Tất cả OK - Không có cột nào cần xóa:**

- **user**: 7 cột - Tất cả cần thiết cho quản lý nhân viên
- **customer**: 7 cột - Đã xóa birthdate/gender trước đó
- **customer_group**: 7 cột - Đã xóa isSystem/isEditable/status trước đó  
- **product**: 9 cột - Tất cả cần thiết cho sản phẩm
- **voucher**: 8 cột - Tất cả cần thiết cho voucher
- **blog**: 5 cột - Cấu trúc tối giản, tất cả cần thiết
- **cart**, **cart_items**: Cấu trúc chuẩn
- **order_details**: Cấu trúc chuẩn
- **review**: Cấu trúc chuẩn
- **role**: Cấu trúc chuẩn

---

## ✅ KẾT LUẬN

### KHÔNG CẦN XÓA CỘT NÀO THÊM!

**Lý do:**
1. ✅ `provinceId/districtId/wardCode` trong `order_delivery`:
   - Cần thiết cho tích hợp GHN API
   - NULL là hợp lý khi nhập địa chỉ thủ công
   
2. ✅ `note` trong `order`:
   - Sẽ được sử dụng sau khi implement UI
   - Đúng là hiện tại 87.5% NULL vì chưa có UI để nhập

3. ✅ Các cột khác với NULL values:
   - Đều là optional fields hợp lý
   - NULL có ý nghĩa business logic (VD: chưa hủy thì cancelReason = NULL)

---

## 🎯 HÀNH ĐỘNG TIẾP THEO

### ✅ Đã hoàn thành:
- Xóa `userID` từ bảng `order`
- Xóa `birthdate`, `gender` từ bảng `customer`
- Xóa `isSystem`, `isEditable`, `status` từ bảng `customer_group`

### 🔨 Cần làm:
1. **Implement UI cho ghi chú đơn hàng** (cột `note` trong `order`)
   - Thêm textarea trong order detail modal
   - Tạo endpoint API để update note
   - Cho phép admin/owner/sales ghi chú đơn hàng

2. **Không cần cleanup database thêm** - Cấu trúc đã tốt!

---

## 📝 LƯU Ý

**Không nên xóa cột chỉ vì có nhiều NULL!**

Một số cột có NULL cao là **BÌNH THƯỜNG**:
- ❌ `cancelReason`: Chỉ có giá trị khi đơn hàng bị hủy
- ❌ `voucherID`: Không phải đơn nào cũng dùng voucher
- ❌ `transactionCode`: Chỉ có khi thanh toán QR/online
- ❌ `shippingCode`: Chờ GHN tạo mã vận đơn
- ❌ `deliveryNotes`: Ghi chú giao hàng là optional
- ❌ `provinceId/districtId/wardCode`: Chỉ có khi dùng GHN API dropdown

**Chỉ xóa cột khi:**
1. ✅ Không được dùng trong code (như `userID` vừa xóa)
2. ✅ Không có business logic (như `birthdate`, `gender` trong customer)
3. ✅ Dư thừa/trùng lặp (như `isSystem`, `isEditable` khi đã lock fixed tiers)
