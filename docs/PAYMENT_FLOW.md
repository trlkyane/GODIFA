# 💳 LUỒNG THANH TOÁN GODIFA

## 📊 TỔNG QUAN

**Có 2 FILE WEBHOOK cho SePay (TRÙNG LẶP - CẦN CHỌN 1):**
1. ❌ `view/payment/webhook.php` (Code cũ, trực tiếp xử lý DB)
2. ✅ `webhook/sepay.php` (Code mới, gọi qua Controller - CHUẨN HƠN)

**Khuyến nghị:** Dùng `webhook/sepay.php` và XÓA `view/payment/webhook.php`

---

## 🔄 LUỒNG HOẠT ĐỘNG ĐÚNG

### **BƯỚC 1: CHECKOUT (Khách hàng đặt hàng)**

```
📍 File: view/cart/checkout.php
└─> Submit form (POST)
     ├─ fullName, email, phone
     ├─ address, ward, district, city
     ├─ provinceId, districtId, wardCode (GHN)
     ├─ shippingFee (từ API GHN)
     └─ notes, paymentMethod

📍 File: controller/cCheckout.php
└─> Xử lý:
     ├─ Validate input
     ├─ Tính totalAmount = (giá sản phẩm * số lượng) + shippingFee
     ├─ Tạo transactionCode: GODIFA{Ymd}{OrderID}
     │   Ví dụ: GODIFA202511060001
     ├─ Tạo QR URL (SePay):
     │   https://qr.sepay.vn/img?acc={STK}&bank={Bank}&amount={Amount}&des={Description}
     │   Description: "SEVQR TKP155 GODIFA202511060001"
     ├─ INSERT vào bảng `order`:
     │   - paymentStatus: "Chờ thanh toán"
     │   - deliveryStatus: "Chờ xử lý"
     │   - shippingFee: 22000
     │   - transactionCode: GODIFA202511060001
     │   - qrUrl: ...
     │   - qrExpiredAt: now + 15 phút
     ├─ INSERT vào bảng `order_delivery`:
     │   - provinceId, districtId, wardCode
     │   - recipientName, recipientPhone, address
     └─> INSERT vào bảng `order_details` (từng sản phẩm)

📍 Redirect: view/cart/checkout_qr.php?orderID=123
```

---

### **BƯỚC 2: HIỂN THỊ QR CODE**

```
📍 File: view/cart/checkout_qr.php
└─> Hiển thị:
     ├─ QR Code (từ qrUrl)
     ├─ Countdown timer (15 phút)
     ├─ Thông tin:
     │   - Số tiền: 272,000₫
     │   - Nội dung CK: "SEVQR TKP155 GODIFA202511060001"
     │   - Người nhận: {recipientName}
     │   - SĐT: {recipientPhone}
     ├─ Polling API mỗi 3 giây:
     │   GET /GODIFA/api/check_payment_status.php?orderID=123
     │   → Check paymentStatus trong DB
     │   → Nếu "Đã thanh toán" → Chuyển sang thankyou.php
     └─ Nút "Tạo QR mới" (nếu hết hạn)
```

---

### **BƯỚC 3: KHÁCH HÀNG CHUYỂN KHOẢN**

```
👤 Khách hàng:
└─> Quét QR bằng App Banking
     ├─ Mở VietinBank/MBBank/VPBank...
     ├─ Quét mã QR
     ├─ Tự động điền:
     │   - STK: 105875539922
     │   - Bank: VietinBank
     │   - Số tiền: 272,000
     │   - Nội dung: SEVQR TKP155 GODIFA202511060001
     └─> Xác nhận chuyển tiền

💰 Sau 1-3 giây:
└─> SePay nhận được thông báo từ ngân hàng
     └─> SePay TỰ ĐỘNG gửi webhook về server
```

---

### **BƯỚC 4: SEPAY GỬI WEBHOOK (TỰ ĐỘNG)**

```
🌐 SePay POST về:
https://yourdomain.com/webhook/sepay.php
hoặc: https://51f1495efc89.ngrok-free.app/GODIFA/webhook/sepay.php

📦 Payload (JSON):
{
  "transactionCode": "GODIFA202511060001",
  "amount": 272000,
  "status": "success",
  "transactionId": "FT25110612345678",
  "bankCode": "VietinBank",
  "transactionTime": "2025-11-06 14:30:00",
  "description": "SEVQR TKP155 GODIFA202511060001"
}

📍 File: webhook/sepay.php
└─> Xử lý:
     ├─ Ghi log vào logs/sepay_webhook.log
     ├─ Verify signature (nếu có)
     ├─ Gọi cPayment::processWebhook($webhookData)
     
📍 File: controller/cPayment.php → processWebhook()
└─> Xử lý:
     ├─ Tìm order theo transactionCode
     ├─ Kiểm tra:
     │   - Order có tồn tại?
     │   - Đã thanh toán chưa?
     │   - Số tiền khớp?
     ├─ UPDATE `order`:
     │   SET paymentStatus = 'Đã thanh toán',
     │       deliveryStatus = 'Đang xử lý',
     │       bankTransactionId = 'FT25110612345678'
     │   WHERE orderID = 123
     └─> Return success

✅ Response về SePay:
{
  "success": true,
  "message": "Processed"
}
```

---

### **BƯỚC 5: KHÁCH HÀNG ĐƯỢC REDIRECT TỰ ĐỘNG**

```
📍 File: view/cart/checkout_qr.php
└─> JavaScript polling mỗi 3 giây:
     GET /GODIFA/api/check_payment_status.php?orderID=123
     
     Response:
     {
       "status": "Đã thanh toán", // ← Đã được webhook update
       "orderID": 123
     }
     
     → JavaScript tự động redirect:
       window.location.href = '/GODIFA/view/payment/thankyou.php?orderID=123'
```

---

### **BƯỚC 6: TRANG CẢM ƠN**

```
📍 File: view/payment/thankyou.php
└─> Hiển thị:
     ├─ ✅ Thanh toán thành công!
     ├─ Thông tin đơn hàng:
     │   - Mã đơn: GODIFA202511060001
     │   - Tổng tiền: 272,000₫
     │   - Phí ship: 22,000₫
     │   - Địa chỉ: 123 Đường Test, Tây Thạnh, Tân Phú, TP.HCM
     ├─ Trạng thái:
     │   - Thanh toán: ✅ Đã thanh toán
     │   - Giao hàng: 🔄 Đang xử lý
     └─ Nút:
       - Xem chi tiết đơn hàng
       - Tiếp tục mua hàng
```

---

## 🗂️ CẤU TRÚC DATABASE

### **Bảng `order`**
```sql
orderID: 123
customerID: 1
orderDate: 2025-11-06 14:25:00
totalAmount: 272000 (250000 + 22000 ship)
shippingFee: 22000
paymentMethod: "Chuyển khoản"
paymentStatus: "Chờ thanh toán" → "Đã thanh toán" (sau khi webhook)
deliveryStatus: "Chờ xử lý" → "Đang xử lý" (sau khi webhook)
transactionCode: "GODIFA202511060001"
qrUrl: "https://qr.sepay.vn/img?acc=..."
qrExpiredAt: 2025-11-06 14:40:00
bankTransactionId: "FT25110612345678" (sau webhook)
```

### **Bảng `order_delivery`**
```sql
orderID: 123
recipientName: "Nguyễn Văn A"
recipientPhone: "0987654321"
address: "123 Đường Test"
ward: "Phường Tây Thạnh"
district: "Quận Tân Phú"
city: "TP.HCM"
provinceId: 202 (GHN)
districtId: 1456 (GHN)
wardCode: "21511" (GHN)
```

### **Bảng `order_details`**
```sql
orderID: 123
productID: 5
quantity: 2
price: 125000
```

---

## 🎯 SAU KHI CHUYỂN KHOẢN XONG

### **Timeline:**

```
⏱️ T+0s:  Khách hàng bấm "Xác nhận" chuyển khoản
⏱️ T+1s:  Ngân hàng xử lý giao dịch
⏱️ T+2s:  SePay nhận thông báo từ ngân hàng
⏱️ T+3s:  SePay POST webhook về server GODIFA
⏱️ T+4s:  webhook/sepay.php nhận request
⏱️ T+5s:  cPayment::processWebhook() update database:
           - paymentStatus: "Đã thanh toán"
           - deliveryStatus: "Đang xử lý"
⏱️ T+6s:  JavaScript polling phát hiện status thay đổi
⏱️ T+7s:  Auto redirect về thankyou.php
⏱️ T+8s:  Khách hàng thấy trang "Thanh toán thành công"
```

### **Trạng thái đơn hàng:**

| Thời điểm | paymentStatus | deliveryStatus |
|-----------|---------------|----------------|
| Sau checkout | Chờ thanh toán | Chờ xử lý |
| Sau CK thành công | **Đã thanh toán** | **Đang xử lý** |
| Admin tạo đơn GHN | Đã thanh toán | Chờ lấy hàng |
| Shipper lấy hàng | Đã thanh toán | Đang vận chuyển |
| Giao thành công | Đã thanh toán | **Đã giao** |

---

## ⚠️ VẤN ĐỀ CẦN SỬA

### **1. TRÙNG LẶP 2 FILE WEBHOOK**

**Hiện tại có:**
- ❌ `view/payment/webhook.php` (Code cũ)
- ✅ `webhook/sepay.php` (Code mới - GỌI QUA CONTROLLER)

**Giải pháp:**
```bash
# Xóa file cũ
rm view/payment/webhook.php

# Hoặc đổi tên để backup
mv view/payment/webhook.php view/payment/webhook.php.backup
```

**Config webhook URL trên SePay Dashboard:**
```
https://51f1495efc89.ngrok-free.app/GODIFA/webhook/sepay.php
```

---

### **2. THIẾU FILE API CHECK PAYMENT STATUS**

Cần tạo: `api/check_payment_status.php`

```php
<?php
require_once __DIR__ . '/../model/database.php';

$orderID = $_GET['orderID'] ?? 0;

if (!$orderID) {
    http_response_code(400);
    die(json_encode(['error' => 'Missing orderID']));
}

$db = Database::getInstance();
$conn = $db->connect();

$stmt = $conn->prepare("SELECT paymentStatus, deliveryStatus FROM `order` WHERE orderID = ?");
$stmt->bind_param("i", $orderID);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    http_response_code(404);
    die(json_encode(['error' => 'Order not found']));
}

echo json_encode([
    'status' => $order['paymentStatus'],
    'deliveryStatus' => $order['deliveryStatus'],
    'orderID' => $orderID
]);
```

---

### **3. THIẾU FILE API RENEW QR**

Cần tạo: `api/renew_qr.php`

```php
<?php
require_once __DIR__ . '/../model/database.php';

$orderID = $_GET['orderID'] ?? 0;

if (!$orderID) {
    die(json_encode(['success' => false, 'message' => 'Missing orderID']));
}

$db = Database::getInstance();
$conn = $db->connect();

// Lấy thông tin order
$stmt = $conn->prepare("SELECT totalAmount, transactionCode FROM `order` WHERE orderID = ?");
$stmt->bind_param("i", $orderID);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    die(json_encode(['success' => false, 'message' => 'Order not found']));
}

// Tạo QR mới (gia hạn thêm 15 phút)
$qrExpiredAt = date('Y-m-d H:i:s', time() + 15 * 60);
$account = '105875539922';
$bank = 'VietinBank';
$description = 'SEVQR TKP155 ' . $order['transactionCode'];
$qrUrl = "https://qr.sepay.vn/img?acc=$account&bank=$bank&amount={$order['totalAmount']}&des=" . urlencode($description);

// Update vào DB
$stmt = $conn->prepare("UPDATE `order` SET qrExpiredAt = ?, qrUrl = ? WHERE orderID = ?");
$stmt->bind_param("ssi", $qrExpiredAt, $qrUrl, $orderID);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'qrUrl' => $qrUrl]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
```

---

## 📋 CHECKLIST HOÀN THÀNH

- [x] Checkout form (với GHN 3-level dropdown)
- [x] Controller cCheckout.php (tạo order + order_delivery)
- [x] QR Code page (checkout_qr.php)
- [x] Webhook handler (webhook/sepay.php)
- [x] Payment controller (cPayment::processWebhook)
- [ ] **API check_payment_status.php** (CẦN TẠO)
- [ ] **API renew_qr.php** (CẦN TẠO)
- [ ] **Xóa view/payment/webhook.php** (TRÙNG LẶP)
- [ ] Config webhook URL trên SePay Dashboard
- [ ] Test end-to-end flow

---

## 🧪 CÁCH TEST

### **Test trên localhost (không cần domain):**

1. Checkout đơn hàng → Có QR code
2. **Giả lập webhook** bằng cURL:
```bash
curl -X POST http://localhost/GODIFA/webhook/sepay.php \
  -H "Content-Type: application/json" \
  -d '{
    "transactionCode": "GODIFA202511060001",
    "amount": 272000,
    "status": "success",
    "transactionId": "TEST123",
    "bankCode": "VietinBank",
    "transactionTime": "2025-11-06 14:30:00"
  }'
```
3. Kiểm tra DB → paymentStatus đã chuyển sang "Đã thanh toán"
4. Trang checkout_qr.php tự động redirect về thankyou.php

### **Test với ngrok (webhook thật từ SePay):**

1. Chạy ngrok: `ngrok http 80`
2. Config webhook trên SePay: `https://xxx.ngrok-free.app/GODIFA/webhook/sepay.php`
3. Checkout và chuyển khoản THẬT
4. SePay tự động gửi webhook
5. Kiểm tra log: `logs/sepay_webhook.log`

---

**Tóm lại:** Luồng thanh toán đã ĐÚNG, chỉ cần:
1. Tạo 2 file API còn thiếu
2. Xóa file webhook trùng lặp
3. Config webhook URL trên SePay Dashboard

Có cần tôi tạo 2 file API còn thiếu không? 🚀
