// FILE: GODIFA/public/js/chat_client.js

const SOCKET_SERVER_PORT = 3000;
const SOCKET_SERVER_URL = `http://localhost:${SOCKET_SERVER_PORT}`; 

// Khởi tạo kết nối Socket.IO
const socket = io(SOCKET_SERVER_URL); 

// ----------------------------------------------------------------
// HÀM TIỆN ÍCH: HIỂN THỊ TIN NHẮN TRÊN GIAO DIỆN
// ----------------------------------------------------------------
function displayMessage(data, isSentByCurrentUser) {
    const messagesDisplay = document.getElementById('messages-display');
    if (!messagesDisplay) return;

    const bubble = document.createElement('div');
    const bubbleClass = isSentByCurrentUser ? 'sent' : 'received';
    bubble.className = `message-bubble ${bubbleClass}`; 
    
    // Sử dụng date từ Server nếu có, hoặc thời gian hiện tại
    const rawDate = data.date ? new Date(data.date) : new Date();
    const timeString = rawDate.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });

    // Dùng data.chatContent vì đây là tên cột trong CSDL của bạn
    bubble.innerHTML = `
        <span>${data.chatContent || data.content}</span>
        <span class="timestamp">${timeString}</span>
    `;
    
    messagesDisplay.appendChild(bubble);
    messagesDisplay.scrollTop = messagesDisplay.scrollHeight;
}


// ----------------------------------------------------------------
// LOGIC CHÍNH: KHỞI TẠO VÀ XỬ LÝ SỰ KIỆN DOM
// ----------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function() {
    const metadata = document.getElementById('chat-metadata');
    
    // Lấy dữ liệu người dùng
    const currentUserID = metadata ? metadata.dataset.userId : 'guest';
    const currentUserType = metadata ? metadata.dataset.userType : 'customer';
    let currentConvID = metadata ? metadata.dataset.conversationId : 'null'; 

    const messageForm = document.getElementById('message-form'); 
    const messageInput = document.getElementById('message-input'); 
    
    // --- SOCKET LISTENERS & ROOM JOIN ---

    socket.on('connect', () => {
        console.log('Socket.IO: Connected. Socket ID:', socket.id);
        
        // 1. Tham gia Phòng Cá nhân
        if (currentUserID !== 'guest') {
            socket.emit('join_user_room', { userID: currentUserID, userType: currentUserType });
        }
        
        // 2. Tham gia Phòng Trò chuyện hiện tại
        if (currentConvID !== 'null') {
             socket.emit('join_conversation', currentConvID);
        }
    });
    
    // 3. XỬ LÝ SỰ KIỆN NHẬN TIN NHẮN TỪ SERVER (Cho cả tin nhắn mình gửi và đối phương gửi)
    socket.on('new_message', (messageData) => {
        // Chỉ hiển thị tin nhắn nếu nó thuộc conversation đang được mở
        if (String(messageData.conversationID) === currentConvID) { 
            const isSent = (String(messageData.senderID) === currentUserID);
            displayMessage(messageData, isSent);
        }
    });
    
    // 4. LẮNG NGHE PHẢN HỒI KHI TẠO CONVERSATION THÀNH CÔNG
    socket.on('conversation_created', (newConvData) => {
        // Cập nhật ConvID sau khi server tạo thành công
        currentConvID = String(newConvData.conversationID);
        metadata.dataset.conversationId = currentConvID; // Cập nhật metadata HTML
        console.log(`Conversation created! New ID: ${currentConvID}`);

        // Tham gia phòng chat mới trên Socket.IO
        socket.emit('join_conversation', currentConvID);
        // Server sẽ tự động gửi tin nhắn đầu tiên qua 'new_message'
    });


    // --- LOGIC GỬI TIN NHẮN (FORM SUBMIT) ---

    if (messageForm) {
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault(); 

            const messageContent = messageInput.value.trim();

            if (messageContent !== '' && socket.connected) {
                
                if (currentConvID === 'null') {
                    // YÊU CẦU TẠO CONVERSATION MỚI VÀ GỬI TIN NHẮN ĐẦU TIÊN
                    const initialMessageData = {
                        senderID: currentUserID,
                        senderType: currentUserType,
                        chatContent: messageContent,
                        date: new Date().toISOString()
                    };
                    socket.emit('create_new_conversation', initialMessageData);
                    
                } else {
                    // GỬI TIN NHẮN BÌNH THƯỜNG
                    const messageData = {
                        conversationID: currentConvID,
                        senderID: currentUserID,
                        senderType: currentUserType,
                        chatContent: messageContent,
                        date: new Date().toISOString()
                    };
                    socket.emit('send_message', messageData); 
                }
                
                // Xóa nội dung
                messageInput.value = '';
                messageInput.focus();
            } else if (!socket.connected) {
                 console.error("Lỗi: Socket.io chưa được kết nối.");
                 alert("Lỗi kết nối đến máy chủ chat. Vui lòng thử lại sau.");
            }
        });
    }

});