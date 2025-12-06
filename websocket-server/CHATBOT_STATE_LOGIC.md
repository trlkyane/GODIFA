# 🤖 Logic Quản Lý Trạng Thái Chatbot

## Vấn Đề Đã Giải Quyết
Trước đây, khi khách hàng hỏi những câu ngoài kiến thức của bot, bot sẽ **im lặng hoàn toàn**. Nhưng điều này gây khó hiểu cho khách hàng (không biết có ai đọc tin nhắn không).

Giờ đã cải tiến để:
1. ✅ Bot gửi thông báo "Chờ nhân viên..." **CHỈ MỘT LẦN** khi không hiểu câu hỏi
2. ✅ Khi nhân viên vào trả lời, bot **TỰ ĐỘNG IM LẶNG** (không lặp lại thông báo)
3. ✅ Bot vẫn trả lời FAQ bình thường nếu tìm thấy keyword

---

## Cơ Chế Hoạt Động

### 1. Map Theo Dõi Trạng Thái (In-Memory)
```javascript
const conversationStates = new Map();
// Key: conversationID
// Value: { 
//   botSentWaiting: boolean,    // Đã gửi "Chờ nhân viên..." chưa?
//   hasStaffJoined: boolean     // Nhân viên đã tham gia chưa?
// }
```

### 2. Luồng Xử Lý Bot Response

#### Bước 1: Kiểm tra trạng thái
```javascript
const state = conversationStates.get(conversationID) || { 
    botSentWaiting: false, 
    hasStaffJoined: false 
};

// ❌ Nếu nhân viên đã tham gia -> Bot im lặng hoàn toàn
if (state.hasStaffJoined) {
    return; // Bot không làm gì cả
}
```

#### Bước 2: Tìm kiếm FAQ
```javascript
// Tìm keyword trong danh sách FAQ
for (const keyword of sortedKeywords) {
    if (normalizedMessage.includes(keyword)) {
        botResponse = faqs[keyword];
        foundKeyword = true;
        break;
    }
}
```

#### Bước 3: Xử lý khi KHÔNG tìm thấy keyword
```javascript
if (!foundKeyword && !state.botSentWaiting) {
    // ✅ Gửi thông báo chờ (LẦN ĐẦU TIÊN)
    botResponse = "Xin lỗi, tôi chưa được đào tạo để trả lời câu hỏi này. Vui lòng chờ nhân viên hỗ trợ sẽ phản hồi bạn sớm nhất có thể! 😊";
    
    // Đánh dấu đã gửi
    state.botSentWaiting = true;
    conversationStates.set(conversationID, state);
}
```

#### Bước 4: Gửi tin nhắn (nếu có)
```javascript
if (botResponse) {
    // Lưu vào CSDL và broadcast qua Socket.IO
    const chatID = await chatModel.saveMessage({...});
    io.to(`conv:${conversationID}`).emit('receive_message', botMessageData);
}
```

### 3. Đánh Dấu Nhân Viên Đã Tham Gia

Có **2 cách** đánh dấu:

#### Cách 1: Khi nhân viên GỬI TIN NHẮN
```javascript
// Trong handleMessage()
if (msg.senderType === 'user') {
    const state = conversationStates.get(conversationID_int) || {...};
    state.hasStaffJoined = true;
    conversationStates.set(conversationID_int, state);
}
```

#### Cách 2: Khi nhân viên JOIN ROOM (Tuỳ chọn)
```javascript
socket.on('join_room', (data) => {
    // Client phải gửi thêm trường: userType: 'admin' hoặc 'user'
    if (data.userType === 'admin' || data.userType === 'user') {
        state.hasStaffJoined = true;
        conversationStates.set(conversationID_int, state);
    }
});
```

> **Lưu ý:** Để Cách 2 hoạt động, phía **Client Admin** phải gửi thêm `userType` khi emit `join_room`.

---

## Kịch Bản Thực Tế

### Kịch Bản 1: Bot Hiểu Được (Có FAQ)
```
Khách: "Cách thanh toán"
Bot: "Bạn có thể thanh toán qua chuyển khoản hoặc COD..." ✅
Khách: "Phí ship"
Bot: "Phí vận chuyển là 30k..." ✅
```
➡️ Bot trả lời bình thường, không có thông báo chờ.

---

### Kịch Bản 2: Bot Không Hiểu (Chưa Có Nhân Viên)
```
Khách: "Tôi muốn đổi màu sản phẩm"
Bot: "Xin lỗi, tôi chưa được đào tạo... Vui lòng chờ nhân viên..." ✅ (Lần 1)

Khách: "Có thể giao hàng ngoài giờ không?"
Bot: (Im lặng) ⚠️ Đã gửi thông báo chờ rồi

Khách: "Hello?"
Bot: (Im lặng) ⚠️ Đã gửi thông báo chờ rồi
```
➡️ Bot chỉ gửi **1 lần duy nhất** thông báo chờ nhân viên.

---

### Kịch Bản 3: Nhân Viên Tham Gia
```
Khách: "Tôi muốn đổi màu sản phẩm"
Bot: "Xin lỗi, tôi chưa được đào tạo... Vui lòng chờ nhân viên..." ✅

--- Nhân viên vào và gửi tin nhắn ---
Nhân viên: "Chào bạn, tôi có thể giúp gì cho bạn?" ✅
Bot: (Đánh dấu hasStaffJoined = true)

Khách: "Tôi muốn đổi từ màu đỏ sang xanh"
Bot: (Im lặng) ✅ Không gửi lại "Chờ nhân viên..."

Nhân viên: "Bạn vui lòng cung cấp mã đơn hàng..." ✅
Khách: "Mã đơn #12345"
Bot: (Im lặng) ✅
```
➡️ Sau khi nhân viên tham gia, bot **NGỪNG HOÀN TOÀN** phản hồi.

---

## Câu Hỏi Thường Gặp (FAQ)

### Q1: Bot có trả lời FAQ sau khi nhân viên tham gia không?
**Không.** Sau khi `hasStaffJoined = true`, bot sẽ im lặng hoàn toàn, kể cả khi khách hỏi câu có keyword.

### Q2: Làm sao để reset trạng thái khi cuộc hội thoại kết thúc?
Bạn có thể:
- **Cách 1:** Xóa khỏi Map khi cuộc hội thoại đóng:
  ```javascript
  conversationStates.delete(conversationID);
  ```
- **Cách 2:** Thêm TTL (Time-to-Live) để tự động xóa sau X giờ không hoạt động.

### Q3: Nếu khách hàng tạo cuộc hội thoại mới thì sao?
Mỗi `conversationID` có trạng thái riêng, nên cuộc hội thoại mới sẽ reset về:
```javascript
{ botSentWaiting: false, hasStaffJoined: false }
```

### Q4: Client cần thay đổi gì không?
**Không bắt buộc**, nhưng **NÊN** gửi thêm trường `userType` khi admin join room:
```javascript
socket.emit('join_room', { 
    conversationID: 123, 
    userType: 'admin' // Hoặc 'user'
});
```

---

## Kiểm Tra Log

Khi chạy server, bạn sẽ thấy các log như:
```
[BOT] Phản hồi FAQ cố định (Keyword: thanh toán) cho ConvID: 1
[BOT] ConvID 2: Không tìm thấy keyword, gửi thông báo chờ nhân viên (LẦN ĐẦU).
[BOT] ConvID 2: Đã gửi thông báo chờ trước đó, bỏ qua tin nhắn này.
[STAFF] ConvID 2: Nhân viên đã tham gia, Bot sẽ ngừng phản hồi.
[BOT] ConvID 2: Nhân viên đã tham gia, Bot im lặng.
```

---

## Tổng Kết
✅ Bot gửi thông báo chờ **CHỈ 1 LẦN**  
✅ Nhân viên tham gia → Bot tự động im lặng  
✅ Không cần sửa CSDL (dùng in-memory Map)  
✅ Dễ mở rộng (có thể thêm AI sau này)

---

**Ngày cập nhật:** 6/12/2025
