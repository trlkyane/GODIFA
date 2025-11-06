// FILE: GODIFA/public/js/chat_client.js - ĐÃ SỬA ĐỔI

const SOCKET_SERVER_PORT = 3000;
const SOCKET_SERVER_URL = `http://localhost:${SOCKET_SERVER_PORT}`; 

// Khởi tạo kết nối Socket.IO
const socket = io(SOCKET_SERVER_URL); 

// ----------------------------------------------------------------
// HÀM TIỆN ÍCH
// ----------------------------------------------------------------

function formatTime(isoString) {
    const date = new Date(isoString);
    return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
}

function scrollToBottom() {
    const messagesDisplay = document.getElementById('messages-display');
    if (messagesDisplay) {
        messagesDisplay.scrollTop = messagesDisplay.scrollHeight;
    }
}

/**
 * Hàm hiển thị tin nhắn trên giao diện Khách hàng
 */
function displayMessage(data, isSentByCurrentUser) {
    const messagesDisplay = document.getElementById('messages-display');
    if (!messagesDisplay) return;

    const content = data.chatContent || data.content;
    const senderType = data.senderType;
    
    let bubbleClass;
    let iconHtml = ''; 
    let bubbleStyle = '';
    
    if (isSentByCurrentUser) {
        bubbleClass = 'sent'; 
    } else if (String(senderType) === 'bot') {
        // Tin nhắn Bot: Thêm icon robot và style trực tiếp
        bubbleClass = 'received'; 
        iconHtml = '<i class="fas fa-robot mr-2" style="color: #4CAF50;"></i>';
        
        // THÊM STYLE TRỰC TIẾP CHO BOT: Nền vàng nhạt
        bubbleStyle = 'background-color: #fffde7; border: 1px solid #ffecb3;'; 

    } else {
        // Tin nhắn Admin/User
        bubbleClass = 'received';
    }
    
    const rawDate = data.date ? new Date(data.date) : new Date();
    const timeString = formatTime(rawDate.toISOString());

    const bubble = document.createElement('div');
    // ÁP DỤNG STYLE TRỰC TIẾP VÀO THUỘC TÍNH style
    bubble.className = `message-bubble ${bubbleClass}`; 
    bubble.style.cssText = bubbleStyle;

    // Chèn icon vào trước nội dung tin nhắn
    bubble.innerHTML = `
        <span>${iconHtml}${content}</span>
        <span class="timestamp">${timeString}</span>
    `;
    
    messagesDisplay.appendChild(bubble);
    scrollToBottom();
}


// ----------------------------------------------------------------
// LOGIC CHÍNH: KHỞI TẠO VÀ XỬ LÝ SỰ KIỆN DOM
// ----------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function() {
    const metadata = document.getElementById('chat-metadata');
    
    const currentUserID = metadata ? metadata.dataset.userId : 'guest';
    const currentUserType = metadata ? metadata.dataset.userType : 'customer';
    let currentConvID = metadata ? metadata.dataset.conversationId : 'null'; 

    const messageForm = document.getElementById('message-form'); 
    const messageInput = document.getElementById('message-input'); 
    
    // --- SOCKET LISTENERS & ROOM JOIN ---

    socket.on('connect', () => {
        console.log('Socket.IO: Connected.');
        
        // Tham gia Phòng Trò chuyện hiện tại nếu có ConvID
        if (currentConvID !== 'null') {
             socket.emit('join_room', { conversationID: currentConvID });
        }
    });
    
    // SỬ DỤNG SỰ KIỆN ĐÚNG: 'receive_message'
    socket.on('receive_message', (messageData) => {
        // Chỉ xử lý tin nhắn thuộc conversation đang mở
        if (String(messageData.conversationID) === currentConvID) { 
            
            const senderID_str = String(messageData.senderID);
            const currentUserID_str = String(currentUserID);
            
            // Tin nhắn của Bot luôn có senderID: 0
            const isSentByBot = (senderID_str === '0' && String(messageData.senderType) === 'bot'); 
            
            // Tin nhắn VỪA GỬI CỦA KHÁCH HÀNG: senderID khớp VÀ KHÔNG phải Bot
            const isSelfSent = (senderID_str === currentUserID_str && !isSentByBot);

            // KHẮC PHỤC LỖI LẶP TIN NHẮN: 
            // Nếu là tin nhắn của mình, ta bỏ qua.
            if (isSelfSent) { 
                 return;
            }

            // Hiển thị: Tin nhắn Bot hoặc tin nhắn Admin
            displayMessage(messageData, false); 
        }
    });
    
    // LẮNG NGHE PHẢN HỒI KHI TẠO CONVERSATION THÀNH CÔNG
    socket.on('conversation_created', (newConvData) => {
        // Cập nhật ConvID sau khi server tạo thành công
        currentConvID = String(newConvData.conversationID);
        metadata.dataset.conversationId = currentConvID; 
        console.log(`Conversation created! New ID: ${currentConvID}`);

        // 💥 QUAN TRỌNG: Gửi join_room NGAY LẬP TỨC 
        // Điều này đảm bảo Client nhận được tin nhắn Bot sắp gửi từ Server.
        socket.emit('join_room', { conversationID: currentConvID });
        
        // Server Node.js của bạn đã được tối ưu để gửi tin nhắn Bot SAU khi 
        // gửi sự kiện 'conversation_created', đảm bảo tin nhắn Bot sẽ được nhận
        // qua 'receive_message' sau khi join_room này hoàn tất.
    });


    // --- LOGIC GỬI TIN NHẮN (FORM SUBMIT) ---

    if (messageForm) {
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault(); 

            const messageContent = messageInput.value.trim();

            if (messageContent !== '' && socket.connected) {
                
                const messageData = {
                    conversationID: currentConvID === 'null' ? null : currentConvID,
                    senderID: currentUserID,
                    senderType: currentUserType,
                    chatContent: messageContent,
                    date: new Date().toISOString()
                };
                
                if (currentConvID === 'null') {
                    // YÊU CẦU TẠO CONVERSATION MỚI VÀ GỬI TIN NHẮN ĐẦU TIÊN
                    socket.emit('create_new_conversation', messageData);
                    
                    // 💥 CHỈ TỰ HIỂN THỊ TIN NHẮN ĐẦU TIÊN (Khách hàng)
                    // Tin nhắn này không bị Server broadcast lại, nên KHÔNG cần chống lặp.
                    // Tin nhắn Bot sẽ được Server broadcast sau.
                    
                } else {
                    // GỬI TIN NHẮN BÌNH THƯỜNG
                    socket.emit('send_message', messageData); 
                    
                    // 💥 TỰ HIỂN THỊ (Client-side render)
                    // Tin nhắn này bị Server broadcast lại, nhưng được chặn bởi logic isSelfSent.
                    displayMessage(messageData, true); 
                }
                
                // 💥 LƯU Ý: Vì server Node.js của bạn đã được sửa để gọi Bot 
                // sau khi gửi 'conversation_created', ta cần TỰ HIỂN THỊ tin nhắn 
                // đầu tiên ngay cả khi nó đi qua 'create_new_conversation'.
                
                // QUYẾT ĐỊNH: Tự hiển thị tin nhắn ngay lập tức (Áp dụng cho cả 2 trường hợp gửi)
                if (currentConvID === 'null') {
                    displayMessage(messageData, true); 
                }
                
                // Xóa nội dung
                messageInput.value = '';
                messageInput.focus();
            }
        });
    }

});