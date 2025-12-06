# 📋 CHATBOT LOGIC V2 - Đơn Giản Hóa

## 🎯 **LOGIC MỚI (Simplified)**

### **Nguyên Tắc Cốt Lõi:**
Bot chỉ dựa vào **1 cờ duy nhất**: `hasStaffJoined`
- ❌ **Bỏ cờ `botSentWaiting`** (không cần nữa)
- ❌ **Bỏ kiểm tra giờ hành chính** (không cần nữa)
- ✅ Bot luôn phản hồi FAQ bất kể có admin hay không
- ⚠️ Bot lặp lại "Chờ nhân viên..." mỗi lần khách hỏi ngoài FAQ (khi chưa có admin)

---

## 📊 **Bảng Quyết Định - 2 Trường Hợp Chính**

| Trạng Thái Admin | Loại Câu Hỏi | Hành Động Bot | Icon |
|-----------------|--------------|---------------|------|
| **CHƯA CÓ ADMIN ONLINE** (`hasStaffJoined = false`) | Có từ khóa FAQ | ✅ Trả lời FAQ | 🤖 |
| **CHƯA CÓ ADMIN ONLINE** (`hasStaffJoined = false`) | Ngoài FAQ | ⚠️ "Chờ nhân viên..." (lặp lại mỗi lần) | 🔔 |
| **CÓ ADMIN ONLINE** (`hasStaffJoined = true`) | Có từ khóa FAQ | 🔇 Im lặng HOÀN TOÀN | 🚫 |
| **CÓ ADMIN ONLINE** (`hasStaffJoined = true`) | Ngoài FAQ | 🔇 Im lặng HOÀN TOÀN | 🚫 |

---

## 🔄 **Sơ Đồ Luồng Xử Lý**

```
┌─────────────────────────────┐
│   Khách Gửi Tin Nhắn       │
└──────────┬──────────────────┘
           │
           ▼
    ┌──────────────────┐
    │hasStaffJoined?   │
    └────────┬─────────┘
             │
       ┌─────┴─────┐
       │           │
     TRUE        FALSE
       │           │
       ▼           ▼
  ┌─────────┐  ┌──────────────┐
  │Im lặng  │  │ Tìm Keyword? │
  │HOÀN TOÀN│  └──────┬───────┘
  │   🔇    │         │
  └─────────┘   ┌─────┴─────┐
                │           │
               CÓ        KHÔNG
                │           │
                ▼           ▼
           ┌─────────┐  ┌──────────┐
           │Trả lời  │  │"Chờ NV"  │
           │  FAQ    │  │(Lặp lại) │
           │  ✅     │  │   ⚠️     │
           └─────────┘  └──────────┘
```

---

## 📝 **Chi Tiết Các Trường Hợp**

### **Trường Hợp 1: CHƯA CÓ ADMIN ONLINE**

#### **1.1. Câu Hỏi Có Từ Khóa FAQ**
```javascript
// Ví dụ: "Giá bao nhiêu?", "Giao hàng thế nào?"
- Điều kiện: normalizedMessage.includes(keyword)
- Phản hồi: Trả lời theo database chatbot
- State: Không thay đổi
```

**Test Case:**
```
Khách: "Chào bạn"
Bot: "Chào bạn! Chúng tôi có thể giúp gì cho bạn?"
hasStaffJoined: false (không đổi)
```

#### **1.2. Câu Hỏi Ngoài FAQ**
```javascript
// Ví dụ: "Tôi muốn đổi size", "Sản phẩm bị lỗi"
- Điều kiện: !foundKeyword && !hasStaffJoined
- Phản hồi: "Xin lỗi, tôi chưa được đào tạo... Vui lòng chờ nhân viên..."
- State: Không thay đổi (không set botSentWaiting)
- Lưu ý: Bot sẽ lặp lại tin này MỖI LẦN khách hỏi câu ngoài FAQ
```

**Test Case:**
```
Khách: "Tôi muốn đổi size"
Bot: "Xin lỗi, tôi chưa được đào tạo... Vui lòng chờ nhân viên..."
hasStaffJoined: false

Khách: "Sản phẩm bị lỗi" (câu khác ngoài FAQ)
Bot: "Xin lỗi, tôi chưa được đào tạo... Vui lòng chờ nhân viên..." (lặp lại)
hasStaffJoined: false

Khách: "Giá bao nhiêu?" (có FAQ)
Bot: "Giá sản phẩm từ 100k - 500k"
hasStaffJoined: false

Khách: "Tôi cần hỗ trợ gấp" (ngoài FAQ)
Bot: "Xin lỗi, tôi chưa được đào tạo... Vui lòng chờ nhân viên..." (lặp lại)
hasStaffJoined: false
```

---

### **Trường Hợp 2: CÓ ADMIN ONLINE**

#### **2.1. Admin Vào Cuộc Hội Thoại**
```javascript
// Khi admin gửi tin nhắn hoặc join room
- Trigger: senderType === 'user' hoặc data.userType === 'admin'
- Action: hasStaffJoined = true
- Log: "Nhân viên đã tham gia, Bot sẽ im lặng với câu ngoài FAQ"
```

#### **2.2. Câu Hỏi Có Từ Khóa FAQ**
```javascript
- Điều kiện: Bất kỳ tin nhắn nào khi hasStaffJoined = true
- Phản hồi: KHÔNG (return ngay)
- Lý do: Admin đã tiếp quản, bot im lặng hoàn toàn
```

**Test Case:**
```
hasStaffJoined: true
Khách: "Chào bạn" (có FAQ)
Bot: (im lặng) 🔇
```

#### **2.3. Câu Hỏi Ngoài FAQ**
```javascript
- Điều kiện: Bất kỳ tin nhắn nào khi hasStaffJoined = true
- Phản hồi: KHÔNG (return ngay)
- Lý do: Admin đã tiếp quản, bot im lặng hoàn toàn
```

**Test Case:**
```
hasStaffJoined: true
Khách: "Tôi muốn đổi size" (ngoài FAQ)
Bot: (im lặng) 🔇
Admin: "Dạ, anh muốn đổi size nào ạ?"
```

---

### **Trường Hợp 3: ADMIN RỜI CUỘC HỘI THOẠI**

#### **3.1. Event `staff_leave_conversation`**
```javascript
socket.on('staff_leave_conversation', (data) => {
    state.hasStaffJoined = false; // Reset về ban đầu
});
```

**Test Case:**
```
hasStaffJoined: true
Admin: (emit staff_leave_conversation)
hasStaffJoined: false (reset)

Khách: "Tôi cần hỗ trợ" (ngoài FAQ)
Bot: "Xin lỗi, tôi chưa được đào tạo... Vui lòng chờ nhân viên..." (hoạt động lại)
```

---

## 🔍 **So Sánh Logic Cũ vs Mới**

| Khía Cạnh | Logic Cũ | Logic Mới |
|-----------|----------|-----------|
| **Số cờ state** | 2 cờ (`botSentWaiting`, `hasStaffJoined`) | 1 cờ (`hasStaffJoined`) |
| **Bot "Chờ NV"** | Chỉ gửi 1 lần duy nhất | Lặp lại mỗi lần khách hỏi ngoài FAQ |
| **Kiểm tra giờ HC** | Có (8h-17h) | Không (bỏ hẳn) |
| **FAQ khi có admin** | Trả lời ✅ | Im lặng 🔇 |
| **Ngoài FAQ khi có admin** | Im lặng 🔇 | Im lặng 🔇 |

---

## 🧪 **Test Scenarios - Toàn Bộ Flow**

### **Scenario 1: Khách Hỏi Nhiều Câu Ngoài FAQ (Chưa Có Admin)**
```
1. Khách: "Tôi muốn đổi size"
   Bot: "Xin lỗi... Vui lòng chờ nhân viên..." ⚠️

2. Khách: "Khi nào có người trả lời?"
   Bot: "Xin lỗi... Vui lòng chờ nhân viên..." ⚠️ (lặp lại)

3. Khách: "Giá bao nhiêu?" (FAQ)
   Bot: "Giá từ 100k-500k" ✅

4. Khách: "Sản phẩm bị lỗi"
   Bot: "Xin lỗi... Vui lòng chờ nhân viên..." ⚠️ (lặp lại)
```

### **Scenario 2: Admin Vào Giữa Chừng**
```
1. Khách: "Tôi cần hỗ trợ"
   Bot: "Xin lỗi... Vui lòng chờ nhân viên..." ⚠️
   hasStaffJoined: false

2. Admin: "Xin chào, tôi có thể giúp gì?"
   hasStaffJoined: true ✅

3. Khách: "Tôi muốn đổi size"
   Bot: (im lặng) 🔇

4. Khách: "Giao hàng thế nào?" (có FAQ)
   Bot: (im lặng) 🔇

5. Admin: "Dạ giao hàng trong 2-3 ngày ạ"
```

### **Scenario 3: Admin Rời Đi**
```
1. hasStaffJoined: true
   Khách: "Cảm ơn"
   Bot: (im lặng) 🔇

2. Admin: (emit staff_leave_conversation)
   hasStaffJoined: false ✅

3. Khách: "Tôi còn thắc mắc"
   Bot: "Xin lỗi... Vui lòng chờ nhân viên..." ⚠️ (hoạt động lại)
```

---

## ⚙️ **Cấu Hình State Map**

```javascript
// Định nghĩa
const conversationStates = new Map();

// Cấu trúc
conversationStates.set(conversationID, {
    hasStaffJoined: false  // Chỉ 1 cờ duy nhất
});

// Khởi tạo mặc định
const state = conversationStates.get(conversationID) || { hasStaffJoined: false };
```

---

## 📌 **Lưu Ý Quan Trọng**

1. **Bot sẽ "spam" tin "Chờ nhân viên..."**
   - Đây là hành vi mong muốn
   - Nhắc khách đợi nếu họ liên tục hỏi câu khác ngoài FAQ

2. **Không còn kiểm tra giờ hành chính**
   - Bỏ hàm `isBusinessHours()`
   - Tin nhắn chờ giống nhau 24/7

3. **Bot vẫn giúp admin với FAQ**
   - ❌ KHÔNG còn đúng (bot im lặng hoàn toàn khi có admin)
   - Admin phải tự trả lời mọi câu hỏi

4. **Reset state khi admin leave**
   - Cho phép bot hoạt động lại
   - Cần implement client-side emit event

---

## 🔗 **Files Liên Quan**

- `websocket-server/server.js` - Logic bot chính
- `public/js/admin_chat_client.js` - Client admin (cần thêm staff_leave event)
- `public/js/chat_client.js` - Client khách hàng
- `docs/CHATBOT_STAFF_LEAVE_GUIDE.md` - Hướng dẫn implement leave event

---

**Cập nhật lần cuối:** 2025-12-06  
**Phiên bản:** 2.0 (Đơn giản hóa - Chỉ 1 cờ)
