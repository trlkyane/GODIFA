# 🔄 Hướng Dẫn Reset Bot Khi Nhân Viên Rời Conversation

## Tổng Quan

Khi nhân viên đóng/rời khỏi conversation, cần **emit event** để reset state của bot, cho phép bot hoạt động trở lại.

---

## 🔧 Server-side (Node.js) - ĐÃ HOÀN THÀNH ✅

### Event Mới: `staff_leave_conversation`

```javascript
socket.on('staff_leave_conversation', (data) => {
    const conversationID_int = parseInt(data.conversationID);
    
    // Reset state của bot
    const state = conversationStates.get(conversationID_int);
    if (state) {
        state.hasStaffJoined = false;   // Bot hoạt động lại
        state.botSentWaiting = false;   // Có thể gửi "Chờ nhân viên..." lại
        conversationStates.set(conversationID_int, state);
    }
    
    // Rời khỏi phòng
    socket.leave(`conv:${conversationID_int}`);
});
```

---

## 💻 Client-side (Admin Panel) - CẦN IMPLEMENT

### **Bước 1: Tìm File JavaScript Chat Admin**

Giả sử file là: `public/js/admin_chat.js` hoặc tương tự.

---

### **Bước 2: Thêm Hàm Emit Event**

```javascript
/**
 * Gọi hàm này khi admin đóng conversation hoặc chuyển sang conversation khác
 */
function leaveConversation(conversationID) {
    if (!socket || !socket.connected) {
        console.warn('Socket chưa kết nối!');
        return;
    }
    
    // Emit event để server reset state của bot
    socket.emit('staff_leave_conversation', { 
        conversationID: conversationID 
    });
    
    console.log(`[ADMIN] Đã rời conversation ${conversationID}, bot sẽ hoạt động lại.`);
}
```

---

### **Bước 3: Gọi Hàm Khi Cần Thiết**

#### **Trường hợp 1: Admin đóng cửa sổ chat**
```javascript
// Khi click nút đóng (X)
document.getElementById('close-chat-btn').addEventListener('click', function() {
    const currentConvID = getCurrentConversationID(); // Hàm lấy conv hiện tại
    
    if (currentConvID) {
        leaveConversation(currentConvID);
    }
    
    // Đóng UI chat
    closeChatPanel();
});
```

#### **Trường hợp 2: Admin chuyển sang conversation khác**
```javascript
// Khi click vào conversation mới trong danh sách
function switchConversation(newConvID) {
    const oldConvID = getCurrentConversationID();
    
    // Rời conversation cũ
    if (oldConvID && oldConvID !== newConvID) {
        leaveConversation(oldConvID);
    }
    
    // Join conversation mới
    socket.emit('join_room', { 
        conversationID: newConvID,
        userType: 'admin' 
    });
    
    // Load tin nhắn mới...
    loadMessages(newConvID);
}
```

#### **Trường hợp 3: Admin logout hoặc refresh trang**
```javascript
// Khi admin logout
function logoutAdmin() {
    const currentConvID = getCurrentConversationID();
    
    if (currentConvID) {
        leaveConversation(currentConvID);
    }
    
    // Disconnect socket
    if (socket) {
        socket.disconnect();
    }
    
    // Redirect đến trang login...
}

// Khi refresh trang (beforeunload)
window.addEventListener('beforeunload', function(e) {
    const currentConvID = getCurrentConversationID();
    
    if (currentConvID) {
        leaveConversation(currentConvID);
    }
});
```

---

## 📋 Ví Dụ Code Hoàn Chỉnh

### File: `public/js/admin_chat.js`

```javascript
// ============================================
// KẾT NỐI SOCKET.IO
// ============================================
const socket = io('http://localhost:3000'); // Hoặc URL server của bạn

let currentConversationID = null;

socket.on('connect', () => {
    console.log('[ADMIN] Đã kết nối Socket.IO:', socket.id);
});

// ============================================
// HÀM RỜI CONVERSATION
// ============================================
function leaveConversation(conversationID) {
    if (!socket || !socket.connected) {
        console.warn('[ADMIN] Socket chưa kết nối!');
        return;
    }
    
    socket.emit('staff_leave_conversation', { 
        conversationID: conversationID 
    });
    
    console.log(`[ADMIN] Đã rời conversation ${conversationID}, bot hoạt động lại.`);
}

// ============================================
// HÀM CHUYỂN CONVERSATION
// ============================================
function switchToConversation(newConvID) {
    // 1. Rời conversation cũ
    if (currentConversationID && currentConversationID !== newConvID) {
        leaveConversation(currentConversationID);
    }
    
    // 2. Join conversation mới
    socket.emit('join_room', { 
        conversationID: newConvID,
        userType: 'admin' // Quan trọng!
    });
    
    // 3. Cập nhật biến global
    currentConversationID = newConvID;
    
    // 4. Load tin nhắn
    loadMessages(newConvID);
    
    console.log(`[ADMIN] Đã chuyển sang conversation ${newConvID}`);
}

// ============================================
// XỬ LÝ ĐÓNG/LOGOUT
// ============================================
document.getElementById('close-chat-btn')?.addEventListener('click', function() {
    if (currentConversationID) {
        leaveConversation(currentConversationID);
        currentConversationID = null;
    }
    closeChatPanel();
});

window.addEventListener('beforeunload', function() {
    if (currentConversationID) {
        leaveConversation(currentConversationID);
    }
});

// ============================================
// LOAD DANH SÁCH CONVERSATION
// ============================================
function renderConversationList(conversations) {
    const listContainer = document.getElementById('conversation-list');
    
    conversations.forEach(conv => {
        const item = document.createElement('div');
        item.className = 'conversation-item';
        item.textContent = `Khách hàng #${conv.customerID}`;
        
        // Click để chuyển conversation
        item.addEventListener('click', () => {
            switchToConversation(conv.conversationID);
        });
        
        listContainer.appendChild(item);
    });
}
```

---

## 🧪 Test Flow

### **Kịch bản 1: Admin đóng chat**

```
1. Khách hàng: "Tôi muốn đổi hàng"
   → Bot: "Xin lỗi... chờ nhân viên..."

2. Admin join vào → state.hasStaffJoined = true
   → Bot im lặng (trừ FAQ)

3. Admin gửi tin: "Cho em mã đơn hàng"
   → Khách trả lời

4. Admin đóng chat → emit 'staff_leave_conversation'
   → state.hasStaffJoined = false
   → state.botSentWaiting = false

5. Khách hàng: "Cảm ơn!"
   → Bot: (Im lặng vì không có keyword)

6. Khách hàng: "Tôi muốn hủy đơn"
   → Bot: "Xin lỗi... chờ nhân viên..." (Gửi lại vì đã reset)
```

---

### **Kịch bản 2: Admin chuyển conversation**

```
1. Admin đang ở Conv#1 với Khách A
2. Admin click vào Conv#2 (Khách B)
   → Emit 'staff_leave_conversation' cho Conv#1
   → Emit 'join_room' cho Conv#2
3. Bot ở Conv#1 hoạt động lại
4. Bot ở Conv#2 im lặng (admin mới vào)
```

---

## ⚠️ Lưu Ý Quan Trọng

### 1. **UserType phải được gửi khi join_room**
```javascript
socket.emit('join_room', { 
    conversationID: 123,
    userType: 'admin' // ← BẮT BUỘC để đánh dấu hasStaffJoined
});
```

### 2. **Chỉ Admin mới emit staff_leave_conversation**
Khách hàng không được phép emit event này.

### 3. **State được xóa khi server restart**
- Map `conversationStates` là in-memory
- Khi server restart → Tất cả state bị xóa
- Muốn persistent → Lưu vào bảng `conversation`

### 4. **Có thể thêm validation bảo mật**
```javascript
socket.on('staff_leave_conversation', async (data) => {
    // Kiểm tra user có phải admin không
    const userSession = await validateAdminSession(socket.id);
    if (!userSession || userSession.role !== 'admin') {
        console.error('Unauthorized staff_leave_conversation');
        return;
    }
    
    // Xử lý reset state...
});
```

---

## 📊 Bảng Quyết Định State

| Sự Kiện | `hasStaffJoined` | `botSentWaiting` | Bot Response |
|---|:---:|:---:|---|
| Khách hỏi FAQ (chưa có ai) | ❌ | ❌ | ✅ Trả lời FAQ |
| Khách hỏi ngoài FAQ | ❌ | ❌ → ✅ | ✅ Gửi "Chờ NV..." |
| Admin join room | ❌ → ✅ | Any | (Không ảnh hưởng ngay) |
| Khách hỏi FAQ (có admin) | ✅ | ❌ | ✅ Trả lời FAQ |
| Khách hỏi ngoài FAQ (có admin) | ✅ | ❌/✅ | ❌ Im lặng |
| **Admin leave** | **✅ → ❌** | **Any → ❌** | **Reset hoàn toàn** |
| Khách hỏi sau khi admin leave | ❌ | ❌ | ✅ Hoạt động bình thường |

---

## 🚀 Checklist Implementation

### Server-side (Node.js) ✅
- [x] Thêm event `staff_leave_conversation`
- [x] Reset `hasStaffJoined = false`
- [x] Reset `botSentWaiting = false`
- [x] Socket leave room

### Client-side (Admin Panel) ⚠️ CẦN LÀM
- [ ] Thêm hàm `leaveConversation(conversationID)`
- [ ] Emit khi đóng chat
- [ ] Emit khi chuyển conversation
- [ ] Emit khi logout/refresh (optional)
- [ ] Gửi `userType: 'admin'` khi join_room

---

**Ngày tạo:** 6/12/2025  
**Trạng thái:** Server ✅ | Client ⚠️ (Cần implement)
