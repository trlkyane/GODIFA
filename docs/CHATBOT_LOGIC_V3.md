# LOGIC CHATBOT V3 - THEO DÕI NHÂN VIÊN ONLINE

## 📋 Tổng Quan

Logic chatbot được cập nhật để hoạt động dựa trên trạng thái **nhân viên có đang online** hay không, thay vì chỉ kiểm tra đã từng tham gia.

---

## 🎯 Quy Tắc Hoạt Động

### ✅ **KHÔNG CÓ NHÂN VIÊN ONLINE**
Chatbot sẽ hoạt động và xử lý theo 2 trường hợp:

1. **Trúng Keyword FAQ**
   - Bot trả lời theo nội dung FAQ đã cấu hình
   - Ví dụ: Khách hỏi "giá cả" → Bot trả lời về bảng giá

2. **Không Trúng Keyword**
   - Bot trả lời: `"Xin lỗi, tôi chưa được đào tạo để trả lời câu hỏi này. Nhân viên sẽ phản hồi bạn sớm nhất có thể (Giờ làm việc là 8h-17h)!"`
   - Thông báo giờ làm việc: **8h-17h**

### ❌ **CÓ NHÂN VIÊN ONLINE** (Giờ Hành Chính)
- Bot **IM LẶNG HOÀN TOÀN**
- Không trả lời bất kỳ tin nhắn nào (kể cả FAQ)
- Nhân viên CSKH đảm nhiệm toàn bộ hội thoại

---

## 🔧 Cơ Chế Kỹ Thuật

### 1. Theo Dõi Nhân Viên Online
```javascript
// Map lưu danh sách staff sockets theo conversation
const staffOnlineByConversation = new Map();
// Key: conversationID (number)
// Value: Set of staff socket IDs
```

### 2. Khi Nhân Viên Join Room
```javascript
socket.on('join_room', (data) => {
    if (userType === 'staff') {
        // Thêm socket ID vào danh sách online
        staffOnlineByConversation.get(conversationID).add(socket.id);
        // Bot sẽ im lặng ngay lập tức
    }
});
```

### 3. Khi Nhân Viên Leave/Disconnect
```javascript
// Xóa socket ID khỏi danh sách
staffOnlineByConversation.get(conversationID).delete(socket.id);

// Nếu không còn staff nào
if (staffSet.size === 0) {
    staffOnlineByConversation.delete(conversationID);
    // Bot hoạt động trở lại
}
```

### 4. Logic Phản Hồi Bot
```javascript
async function handleBotResponse(io, conversationID, message) {
    const staffSockets = staffOnlineByConversation.get(conversationID) || new Set();
    const hasStaffOnline = staffSockets.size > 0;
    
    // ❌ CÓ STAFF ONLINE → Bot im lặng
    if (hasStaffOnline) {
        console.log(`Bot im lặng (${staffSockets.size} nhân viên online)`);
        return;
    }
    
    // ✅ KHÔNG CÓ STAFF → Bot xử lý
    // Tìm keyword trong FAQ
    for (const keyword of sortedKeywords) {
        if (normalizedMessage.includes(keyword)) {
            botResponse = faqs[keyword]; // Trả lời FAQ
            break;
        }
    }
    
    // Không trúng keyword
    if (!botResponse) {
        botResponse = "Xin lỗi, tôi chưa được đào tạo... (Giờ làm việc 8h-17h)!";
    }
    
    // Lưu và broadcast tin nhắn bot
}
```

---

## 📊 Sơ Đồ Luồng Hoạt Động

```
┌─────────────────────────────────────────────────────────────┐
│                   Khách Hàng Gửi Tin Nhắn                   │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        ▼
        ┌───────────────────────────────┐
        │ Có nhân viên CSKH online?     │
        │ (staffOnlineByConversation)   │
        └───────┬───────────────┬───────┘
                │               │
         CÓ ✅  │               │  KHÔNG ❌
                │               │
                ▼               ▼
    ┌──────────────────┐   ┌──────────────────────┐
    │ Bot im lặng      │   │ Bot kiểm tra keyword │
    │ hoàn toàn        │   │ trong FAQ            │
    └──────────────────┘   └─────────┬────────────┘
                                      │
                    ┌─────────────────┼─────────────────┐
                    │                 │                 │
            TRÚNG KEYWORD        KHÔNG TRÚNG       
                    │                 │                 
                    ▼                 ▼                 
        ┌─────────────────────┐  ┌──────────────────────────┐
        │ Bot trả lời theo    │  │ Bot: "Xin lỗi, tôi chưa │
        │ nội dung FAQ        │  │ được đào tạo... Giờ làm  │
        │                     │  │ việc 8h-17h"             │
        └─────────────────────┘  └──────────────────────────┘
```

---

## 🧪 Test Cases

### Test 1: Không Có Nhân Viên Online
```
INPUT:  Khách hỏi "Giá bánh bao nhiêu?" (có keyword "giá")
OUTPUT: Bot trả lời nội dung FAQ về giá cả
```

### Test 2: Không Có Nhân Viên + Không Trúng Keyword
```
INPUT:  Khách hỏi "Tôi muốn gặp sếp"
OUTPUT: Bot: "Xin lỗi, tôi chưa được đào tạo để trả lời câu hỏi này. 
             Nhân viên sẽ phản hồi bạn sớm nhất có thể (Giờ làm việc là 8h-17h)!"
```

### Test 3: Có Nhân Viên Online
```
INPUT:  CSKH join room → Khách hỏi bất kỳ câu gì
OUTPUT: Bot im lặng hoàn toàn (không có phản hồi)
```

### Test 4: Nhân Viên Rời Đi
```
SETUP:  CSKH đang online (Bot im lặng)
ACTION: CSKH leave room hoặc disconnect
RESULT: Bot hoạt động trở lại ngay lập tức
```

### Test 5: Nhiều Nhân Viên Cùng Online
```
SETUP:  CSKH #1 join room → Bot im lặng
ACTION: CSKH #2 cũng join room
RESULT: Bot vẫn im lặng
ACTION: CSKH #1 leave
RESULT: Bot vẫn im lặng (còn CSKH #2)
ACTION: CSKH #2 leave
RESULT: Bot hoạt động trở lại
```

---

## 🔍 Debug & Monitoring

### Console Logs Quan Trọng

```javascript
// Khi bot kiểm tra trạng thái
"[BOT DEBUG] ConvID 123: staffOnline = true (2 nhân viên), Message: 'hello'"

// Khi bot im lặng
"[BOT] ConvID 123: Có 2 nhân viên online, Bot im lặng HOÀN TOÀN."

// Khi bot trả lời FAQ
"[BOT] Phản hồi FAQ (Keyword: 'giá') cho ConvID: 123"

// Khi bot không trúng keyword
"[BOT] ConvID 123: Không trúng keyword, gửi thông báo chờ nhân viên."

// Khi staff join
"[STAFF] ConvID 123: CSKH (socket abc123) join room, Bot sẽ im lặng. Total staff online: 1"

// Khi staff leave
"[STAFF] ConvID 123: Không còn nhân viên online, Bot sẽ hoạt động trở lại."
```

---

## ⚙️ Khởi Động Server

### Local (WAMP)
```powershell
cd c:\wamp64\www\godifaproject.id.vn\websocket-server
node server.js
```

### VPS (Production)
```bash
cd /var/www/godifaproject.id.vn/websocket-server
pm2 restart godifa-chat
pm2 logs godifa-chat --lines 100
```

---

## 📝 So Sánh Với Version Cũ

| Tiêu Chí | V2 (Cũ) | V3 (Mới) |
|----------|---------|----------|
| **Theo dõi** | `hasStaffJoined` (boolean) | `staffOnlineByConversation` (Map) |
| **Logic** | Đã từng tham gia = im lặng | Đang online = im lặng |
| **Reset** | Phải gọi `staff_leave_conversation` | Auto reset khi disconnect |
| **Nhiều staff** | Không hỗ trợ | Hỗ trợ nhiều staff cùng lúc |
| **Thông báo** | Chờ nhân viên (không có giờ) | Chờ nhân viên + Giờ 8h-17h |

---

## 🚀 Deploy Checklist

- [ ] Test chatbot không có nhân viên (cả FAQ và non-FAQ)
- [ ] Test chatbot khi có nhân viên join
- [ ] Test chatbot khi nhân viên leave
- [ ] Test nhiều nhân viên cùng online
- [ ] Kiểm tra thông báo "Giờ làm việc 8h-17h"
- [ ] Test disconnect socket tự động xóa staff
- [ ] Kiểm tra logs console đầy đủ
- [ ] Restart PM2 trên VPS: `pm2 restart godifa-chat`
- [ ] Verify trên production: https://godifaproject.id.vn

---

## 📚 Files Liên Quan

- `websocket-server/server.js` - Logic chính
- `public/js/admin_chat_client.js` - Admin chat interface
- `public/js/chat_client.js` - Customer chat interface
- `admin/pages/chat.php` - Admin chat page
- `view/chat/chat.php` - Customer chat page

---

**Ngày cập nhật:** 2025-12-09  
**Version:** 3.0  
**Tác giả:** GitHub Copilot
