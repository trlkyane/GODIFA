# 🤖 Luồng Xử Lý Chatbot và Nhân Viên

## Tổng Quan Kiến Trúc

```
┌─────────────┐
│  KHÁCH HÀNG │
└──────┬──────┘
       │
       ▼
┌─────────────────────────────────────────────┐
│         SOCKET.IO SERVER (Node.js)          │
│  ┌────────────────────────────────────┐    │
│  │   Conversation State Manager       │    │
│  │   (In-Memory Map)                  │    │
│  │                                    │    │
│  │   Key: conversationID              │    │
│  │   Value: {                         │    │
│  │     botSentWaiting: boolean        │    │
│  │     hasStaffJoined: boolean        │    │
│  │   }                                │    │
│  └────────────────────────────────────┘    │
│              ┌───────┐                      │
│              │  BOT  │ ← FAQ Database       │
│              └───────┘                      │
└─────────────────┬───────────────────────────┘
                  │
       ┌──────────┴──────────┐
       ▼                     ▼
┌──────────────┐      ┌──────────────┐
│  MySQL CSDL  │      │  NHÂN VIÊN   │
└──────────────┘      └──────────────┘
```

---

## 📊 Luồng Xử Lý Chi Tiết

### **GIAI ĐOẠN 1: Khách Hàng Gửi Tin Nhắn Đầu Tiên**

```
┌──────────────────────────────────────────────────────────┐
│ 1. Khách hàng gửi tin nhắn                               │
└───────────────────────┬──────────────────────────────────┘
                        │
                        ▼
┌──────────────────────────────────────────────────────────┐
│ 2. Client emit: 'create_new_conversation'                │
│    Data: {                                               │
│      senderID: customerID,                               │
│      senderType: 'customer',                             │
│      chatContent: "tin nhắn..."                          │
│    }                                                     │
└───────────────────────┬──────────────────────────────────┘
                        │
                        ▼
┌──────────────────────────────────────────────────────────┐
│ 3. Server tạo/tìm Conversation                           │
│    - Kiểm tra conversation 'open' của customer           │
│    - Nếu chưa có → Tạo mới                               │
│    - Khách join phòng: conv:{conversationID}             │
└───────────────────────┬──────────────────────────────────┘
                        │
                        ▼
┌──────────────────────────────────────────────────────────┐
│ 4. Lưu tin nhắn khách vào CSDL                           │
│    - Table: chat                                         │
│    - Fields: conversationID, chatContent, senderID,      │
│              senderType='customer', date, isRead         │
└───────────────────────┬──────────────────────────────────┘
                        │
                        ▼
┌──────────────────────────────────────────────────────────┐
│ 5. Broadcast tin nhắn khách đến phòng                    │
│    io.to('conv:X').emit('receive_message', {...})       │
└───────────────────────┬──────────────────────────────────┘
                        │
                        ▼
┌──────────────────────────────────────────────────────────┐
│ 6. GỌI handleBotResponse()                               │
└───────────────────────┬──────────────────────────────────┘
                        │
                        ▼
                  (Xem GIAI ĐOẠN 2)
```

---

### **GIAI ĐOẠN 2: Bot Phân Tích và Phản Hồi**

```
┌──────────────────────────────────────────────────────────┐
│ handleBotResponse(io, conversationID, message)           │
└───────────────────────┬──────────────────────────────────┘
                        │
                        ▼
┌──────────────────────────────────────────────────────────┐
│ 1. Lấy state của cuộc hội thoại                          │
│    state = conversationStates.get(conversationID)        │
│    Nếu null → khởi tạo:                                  │
│    { botSentWaiting: false, hasStaffJoined: false }      │
└───────────────────────┬──────────────────────────────────┘
                        │
                        ▼
┌──────────────────────────────────────────────────────────┐
│ 2. KIỂM TRA: Nhân viên đã tham gia?                      │
│    if (state.hasStaffJoined)                             │
└─────────┬──────────────────────┬─────────────────────────┘
          │ YES                  │ NO
          ▼                      ▼
┌─────────────────────┐  ┌──────────────────────────────────┐
│ → Bot IM LẶNG       │  │ 3. KIỂM TRA: Đã gửi "Chờ NV"?   │
│   return;           │  │    if (state.botSentWaiting)     │
└─────────────────────┘  └─────────┬────────────────┬───────┘
                                   │ YES            │ NO
                                   ▼                ▼
                         ┌─────────────────┐  ┌─────────────────────────┐
                         │ → Bot IM LẶNG   │  │ 4. TÌM KEYWORD trong FAQ│
                         │   return;       │  │    - Chuẩn hóa message  │
                         └─────────────────┘  │    - Loop sortedKeywords│
                                              │    - Check includes()   │
                                              └──────────┬──────────────┘
                                                         │
                                        ┌────────────────┴────────────────┐
                                        │ FOUND?                          │
                                        └────┬─────────────────────┬──────┘
                                             │ YES                 │ NO
                                             ▼                     ▼
                              ┌──────────────────────────┐  ┌──────────────────────────┐
                              │ 5A. Trả lời FAQ          │  │ 5B. Gửi "Chờ nhân viên" │
                              │ botResponse = faqs[key]  │  │ botResponse = "Xin lỗi..."│
                              │ foundKeyword = true      │  │ state.botSentWaiting=true│
                              └──────────┬───────────────┘  └──────────┬───────────────┘
                                         │                              │
                                         └──────────────┬───────────────┘
                                                        ▼
                                         ┌──────────────────────────────┐
                                         │ 6. Lưu tin nhắn bot vào CSDL │
                                         │    senderID: 0               │
                                         │    senderType: 'bot'         │
                                         └──────────────┬───────────────┘
                                                        ▼
                                         ┌──────────────────────────────┐
                                         │ 7. Broadcast tin nhắn bot    │
                                         │    emit('receive_message')   │
                                         └──────────────────────────────┘
```

---

### **GIAI ĐOẠN 3: Nhân Viên Tham Gia**

```
┌──────────────────────────────────────────────────────────┐
│ 1. Admin mở trang chat và chọn conversation              │
└───────────────────────┬──────────────────────────────────┘
                        │
                        ▼
┌──────────────────────────────────────────────────────────┐
│ 2. Client emit: 'join_room'                              │
│    Data: {                                               │
│      conversationID: X,                                  │
│      userType: 'admin' hoặc 'user'  (Tùy chọn)          │
│    }                                                     │
└───────────────────────┬──────────────────────────────────┘
                        │
                        ▼
┌──────────────────────────────────────────────────────────┐
│ 3. Server: socket.join('conv:X')                         │
└───────────────────────┬──────────────────────────────────┘
                        │
                        ▼
┌──────────────────────────────────────────────────────────┐
│ 4. (Tùy chọn) Nếu có userType='admin'/'user'             │
│    → Đánh dấu: state.hasStaffJoined = true               │
│    (Hoặc chờ nhân viên gửi tin nhắn đầu tiên)            │
└───────────────────────┬──────────────────────────────────┘
                        │
                        ▼
┌──────────────────────────────────────────────────────────┐
│ 5. Nhân viên gửi tin nhắn                                │
│    Client emit: 'send_message'                           │
│    Data: {                                               │
│      conversationID: X,                                  │
│      senderID: userID,                                   │
│      senderType: 'user',                                 │
│      chatContent: "..."                                  │
│    }                                                     │
└───────────────────────┬──────────────────────────────────┘
                        │
                        ▼
┌──────────────────────────────────────────────────────────┐
│ 6. Server xử lý tin nhắn (handleMessage)                 │
│    - Lưu vào CSDL                                        │
│    - Broadcast đến phòng                                 │
└───────────────────────┬──────────────────────────────────┘
                        │
                        ▼
┌──────────────────────────────────────────────────────────┐
│ 7. KIỂM TRA: senderType === 'user'?                      │
│    if (YES) {                                            │
│      state.hasStaffJoined = true;                        │
│      conversationStates.set(conversationID, state);      │
│    }                                                     │
└───────────────────────┬──────────────────────────────────┘
                        │
                        ▼
┌──────────────────────────────────────────────────────────┐
│ 8. BOT NGỪNG PHẢN HỒI từ giờ trở đi                      │
│    (state.hasStaffJoined = true)                         │
└──────────────────────────────────────────────────────────┘
```

---

### **GIAI ĐOẠN 4: Sau Khi Nhân Viên Tham Gia**

```
┌──────────────────────────────────────────────────────────┐
│ Khách hàng tiếp tục gửi tin nhắn                         │
└───────────────────────┬──────────────────────────────────┘
                        │
                        ▼
┌──────────────────────────────────────────────────────────┐
│ handleBotResponse() được gọi                             │
└───────────────────────┬──────────────────────────────────┘
                        │
                        ▼
┌──────────────────────────────────────────────────────────┐
│ KIỂM TRA: state.hasStaffJoined?                          │
│ → YES: Bot IM LẶNG, return ngay                          │
└──────────────────────────────────────────────────────────┘
                        │
                        ▼
┌──────────────────────────────────────────────────────────┐
│ Chỉ còn nhân viên và khách trò chuyện                    │
│ Bot không can thiệp nữa                                  │
└──────────────────────────────────────────────────────────┘
```

---

## 🎯 Bảng Quyết Định Bot Response

| # | Tình Huống | `hasStaffJoined` | `botSentWaiting` | Tìm thấy Keyword? | Hành Động Bot |
|---|---|:---:|:---:|:---:|---|
| 1 | Khách hỏi FAQ lần đầu | ❌ | ❌ | ✅ | ✅ Trả lời FAQ |
| 2 | Khách hỏi ngoài FAQ lần đầu | ❌ | ❌ | ❌ | ✅ Gửi "Chờ nhân viên..." |
| 3 | Khách hỏi FAQ sau khi gửi "Chờ NV" | ❌ | ✅ | ✅ | ❌ Im lặng |
| 4 | Khách hỏi ngoài FAQ sau "Chờ NV" | ❌ | ✅ | ❌ | ❌ Im lặng |
| 5 | Nhân viên vào, khách hỏi FAQ | ✅ | ✅/❌ | ✅ | ❌ Im lặng |
| 6 | Nhân viên vào, khách hỏi bất kỳ | ✅ | ✅/❌ | ❌ | ❌ Im lặng |

---

## 💾 Cấu Trúc Dữ Liệu

### **1. Conversation State (In-Memory)**
```javascript
const conversationStates = new Map();

// Key: conversationID (Number)
// Value: {
//   botSentWaiting: Boolean,   // Bot đã gửi "Chờ nhân viên..." chưa?
//   hasStaffJoined: Boolean    // Nhân viên đã tham gia chưa?
// }

// Ví dụ:
conversationStates.set(123, {
    botSentWaiting: true,
    hasStaffJoined: false
});
```

**Lưu ý:** Dữ liệu này **XÓA KHI RESTART SERVER**. Nếu cần persistent, lưu vào bảng `conversation`.

---

### **2. Database Schema**

#### Bảng `conversation`
```sql
CREATE TABLE conversation (
    conversationID INT PRIMARY KEY AUTO_INCREMENT,
    customerID INT NOT NULL,
    userID INT,                          -- NULL khi chưa có nhân viên
    last_message_at DATETIME NOT NULL,
    customer_unread_count INT DEFAULT 0,
    user_unread_count INT DEFAULT 0,
    status ENUM('open','closed','pending') NOT NULL
);
```

#### Bảng `chat`
```sql
CREATE TABLE chat (
    chatID INT PRIMARY KEY AUTO_INCREMENT,
    conversation_ID INT NOT NULL,
    chatContent TEXT NOT NULL,
    sender_ID INT NOT NULL,              -- 0 = Bot, >0 = Customer/User
    senderType ENUM('customer','user','bot') NOT NULL,
    date DATETIME NOT NULL,
    isRead TINYINT DEFAULT 0
);
```

#### Bảng `chatbot` (FAQ)
```sql
CREATE TABLE chatbot (
    id INT PRIMARY KEY AUTO_INCREMENT,
    keywords VARCHAR(255) NOT NULL,      -- Từ khóa (chữ thường, không dấu)
    response_text TEXT NOT NULL          -- Câu trả lời
);
```

---

## 🔄 Flow Chart Tổng Hợp

```
START
  │
  ▼
┌────────────────────────────────┐
│ Khách hàng gửi tin nhắn        │
└────────────┬───────────────────┘
             │
             ▼
┌────────────────────────────────┐
│ Lưu tin nhắn khách vào CSDL    │
└────────────┬───────────────────┘
             │
             ▼
┌────────────────────────────────┐
│ Broadcast tin nhắn khách       │
└────────────┬───────────────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│ KIỂM TRA: senderType === 'customer'?    │
└────┬─────────────────────────┬──────────┘
     │ YES                     │ NO
     ▼                         ▼
┌──────────────────────┐  ┌─────────────────────┐
│ GỌI handleBotResponse│  │ KIỂM TRA: user?     │
└────┬─────────────────┘  └──────┬──────────────┘
     │                           │ YES
     │                           ▼
     │              ┌──────────────────────────┐
     │              │ Đánh dấu hasStaffJoined  │
     │              └──────────────────────────┘
     │
     ▼
┌────────────────────────────────┐
│ State: hasStaffJoined?         │
└─┬──────────────────────────┬───┘
  │ YES                      │ NO
  ▼                          ▼
┌──────────┐      ┌──────────────────────┐
│ IM LẶNG  │      │ State: botSentWaiting?│
└──────────┘      └─┬──────────────────┬─┘
                    │ YES              │ NO
                    ▼                  ▼
              ┌──────────┐      ┌──────────────┐
              │ IM LẶNG  │      │ TÌM KEYWORD  │
              └──────────┘      └─┬───────┬────┘
                                  │ FOUND │ NOT
                                  ▼       ▼
                          ┌─────────┐  ┌─────────────┐
                          │ TRẢ LỜI │  │ "Chờ NV..." │
                          │   FAQ   │  │ + Set flag  │
                          └─────────┘  └─────────────┘
                                  │       │
                                  └───┬───┘
                                      ▼
                            ┌──────────────────┐
                            │ Lưu tin nhắn bot │
                            └────────┬─────────┘
                                     ▼
                            ┌──────────────────┐
                            │ Broadcast bot msg│
                            └──────────────────┘
                                     │
                                     ▼
                                    END
```

---

## ⚙️ Cấu Hình và Tối Ưu

### **1. Keyword Matching Strategy**
```javascript
// Sắp xếp từ khóa theo độ dài GIẢM DẦN
// → Ưu tiên từ khóa dài/cụ thể hơn
sortedKeywords = Object.keys(faqs).sort((a, b) => b.length - a.length);

// Ví dụ:
// ["cách thanh toán online", "thanh toán", "xin chào"]
// Nếu message có "cách thanh toán online" → Match cái đầu tiên
```

### **2. Charset Configuration**
```javascript
// ChatModel.js - MySQL Connection
const dbConfig = {
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'godifa1',
    charset: 'utf8mb4',  // ✅ Quan trọng để tránh lỗi collation
    waitForConnections: true,
    connectionLimit: 10
};
```

### **3. State Management**
```javascript
// Xóa state khi restart server (hoặc định kỳ)
conversationStates.clear();

// Hoặc implement TTL (Time-to-Live) cho state cũ
// Xóa conversation không hoạt động > 24h
```

---

## 🚀 Các Tình Huống Đặc Biệt

### **Tình huống 1: Khách hàng tạo conversation mới**
- State reset về: `{ botSentWaiting: false, hasStaffJoined: false }`
- Bot hoạt động bình thường từ đầu

### **Tình huống 2: Server restart**
- Map `conversationStates` bị xóa sạch
- **Giải pháp:** Lưu state vào bảng `conversation`:
  ```sql
  ALTER TABLE conversation 
  ADD COLUMN bot_sent_waiting TINYINT DEFAULT 0,
  ADD COLUMN staff_joined TINYINT DEFAULT 0;
  ```

### **Tình huống 3: Nhiều nhân viên cùng join**
- Chỉ cần 1 nhân viên gửi tin nhắn → `hasStaffJoined = true`
- Bot ngừng hoàn toàn

### **Tình huống 4: Conversation đóng (status='closed')**
- Xóa state: `conversationStates.delete(conversationID)`
- Khi mở lại, state reset về mặc định

---

## 📝 Checklist Triển Khai

- ✅ Fix collation error (utf8mb4)
- ✅ Thêm conversationStates Map
- ✅ Implement handleBotResponse() với logic 2 cờ
- ✅ Đánh dấu hasStaffJoined khi user gửi tin nhắn
- ✅ Clear state khi server restart
- ✅ Test 6 kịch bản trong bảng quyết định
- ⚠️ (Tùy chọn) Lưu state vào CSDL cho persistent
- ⚠️ (Tùy chọn) Implement TTL cho state cũ

---

**Ngày tạo:** 6/12/2025  
**Version:** 2.0 - Final Logic với Bot Silent Mode sau "Chờ nhân viên..."
