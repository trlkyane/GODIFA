// FILE: GODIFA/public/js/chat_client.js - ĐÃ HOÀN CHỈNH VỚI CHỨC NĂNG TẢI LỊCH SỬ & FIX VỊ TRÍ TIN NHẮN

// Kiểm tra xem user có đăng nhập không trước khi khởi tạo socket
const chatMetadata = document.getElementById('chat-metadata');
const userId = chatMetadata ? chatMetadata.getAttribute('data-user-id') : 'guest';

// Nếu là guest, không khởi tạo socket và dừng việc load script
if (userId === 'guest') {
    console.log('[CHAT] Guest user detected - Socket connection disabled. Please login to chat.');
    // Không khởi tạo socket cho guest
} else {
    // Chỉ khởi tạo socket cho user đã đăng nhập
    initializeChat();
}

function initializeChat() {
// Lấy SOCKET_SERVER_URL từ metadata hoặc fallback
const metadata = document.querySelector('meta[name="socket-server-url"]');
const SOCKET_SERVER_URL_RAW = metadata ? metadata.getAttribute('content') : 'http://localhost:3000';

// Xử lý path cho production (có /ws) vs local (không có /ws)
const needsCustomPath = SOCKET_SERVER_URL_RAW.includes('/ws');
const SOCKET_SERVER_URL = SOCKET_SERVER_URL_RAW.replace('/ws', '');

// Lấy BASE_URL từ window, đảm bảo có dấu / cuối
let BASE_URL = window.BASE_URL || (window.location.origin + '/');
if (!BASE_URL.endsWith('/')) {
    BASE_URL += '/';
}

console.log('Customer Chat - BASE_URL:', BASE_URL);
console.log('Customer Chat - SOCKET_SERVER_URL_RAW:', SOCKET_SERVER_URL_RAW);
console.log('Customer Chat - SOCKET_SERVER_URL:', SOCKET_SERVER_URL);
console.log('Customer Chat - needsCustomPath:', needsCustomPath);

// Khởi tạo kết nối Socket.IO với path tùy thuộc môi trường
const socketConfig = needsCustomPath 
    ? { path: '/ws/socket.io' }  // Production: https://domain.com/ws
    : {};                         // Local: http://localhost:3000

const socket = io(SOCKET_SERVER_URL, socketConfig);

// 🔐 XÁC THỰC NGAY SAU KHI KẾT NỐI
socket.on('connect', () => {
    console.log('[SOCKET] Connected, authenticating as customer...');
    
    // Lấy customer_id từ window hoặc data attribute
    const customerId = window.CURRENT_USER_ID || document.querySelector('[data-user-id]')?.getAttribute('data-user-id') || 'guest';
    
    // Gửi thông tin xác thực
    socket.emit('authenticate', {
        role_id: 0, // Customer không có role_id
        user_id: customerId === 'guest' ? 0 : parseInt(customerId),
        type: 'customer'
    });
});

// Lắng nghe kết quả xác thực
socket.on('auth_success', (data) => {
    console.log('[AUTH] Authentication successful:', data.message);
    
    // 🔹 SAU KHI XÁC THỰC THÀNH CÔNG, JOIN ROOM NẾU CÓ
    const chatMetadata = document.getElementById('chat-metadata');
    const currentConvID = chatMetadata ? chatMetadata.getAttribute('data-conversation-id') : 'null';
    
    if (currentConvID !== 'null') {
        socket.emit('join_room', { conversationID: currentConvID });
        console.log(`[JOIN] Tham gia phòng chat conv:${currentConvID}`);
    }
});

socket.on('auth_failed', (data) => {
    console.error('[AUTH] Authentication failed:', data.message);
    alert('Xác thực thất bại: ' + data.message);
});

socket.on('auth_required', (data) => {
    console.error('[AUTH] Authentication required:', data.message);
    alert('Vui lòng đăng nhập để sử dụng chat');
});

socket.on('permission_denied', (data) => {
    console.error('[PERMISSION] Permission denied:', data.message);
    alert(data.message);
});

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
    const senderID = data.senderID;
    
    const isReceivedMessage = !isSentByCurrentUser; 
    
    let bubbleClass;
    let iconHtml = ''; 
    let bubbleStyle = '';
    
    if (isSentByCurrentUser) {
        bubbleClass = 'sent'; // Tin nhắn của người xem hiện tại (Khách hàng) -> Bên phải
    } else if (isReceivedMessage && (String(senderType) === 'bot' || String(senderID) === '0')) {
        // Tin nhắn Bot: Thêm icon robot và style trực tiếp
        bubbleClass = 'received'; 
        iconHtml = '<i class="fas fa-robot mr-2" style="color: #4CAF50;"></i>';
        
        // THÊM STYLE TRỰC TIẾP CHO BOT: Nền vàng nhạt
        bubbleStyle = 'background-color: #fffde7; border: 1px solid #ffecb3;'; 

    } else {
        // Tin nhắn Admin/User hoặc tin nhắn khác của người khác -> Bên trái
        bubbleClass = 'received';
    }
    
    const rawDate = data.date ? new Date(data.date) : new Date();
    const timeString = formatTime(rawDate.toISOString());

    const bubble = document.createElement('div');
    bubble.className = `message-bubble ${bubbleClass}`; 
    bubble.style.cssText = bubbleStyle;

    // Chèn icon vào trước nội dung tin nhắn
    bubble.innerHTML = `
        <span>${iconHtml}${content}</span>
        <span class="timestamp">${timeString}</span>
    `;
    
    messagesDisplay.appendChild(bubble);
}

/**
 * Tải lịch sử tin nhắn từ PHP API và hiển thị
 */
function loadChatHistory(convID, currentUserID) {
    if (convID === 'null' || !convID) return; 

    const messagesDisplay = document.getElementById('messages-display');
    if (messagesDisplay) {
        messagesDisplay.innerHTML = '<div class="text-center text-gray-500 py-4">Đang tải lịch sử chat...</div>';
    }

    // Sử dụng BASE_URL để tương thích VPS
    // Thêm timestamp để tránh cache
    const timestamp = new Date().getTime();
    const apiUrl = `${BASE_URL}controller/ChatController.php?action=getMessages&conv_id=${convID}&_=${timestamp}`; 
    
    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                // Thử đọc response text để debug nếu lỗi 404/500
                return response.text().then(text => { throw new Error(`HTTP error! status: ${response.status}. Response: ${text.substring(0, 100)}`); });
            }
            return response.json();
        })
        .then(data => {
            if (messagesDisplay) {
                messagesDisplay.innerHTML = ''; // Xóa thông báo đang tải
            }
            if (data.success && data.messages) {
                data.messages.forEach(msg => {
                    // 🚀 FIX LỖI VỊ TRÍ: CHỈ CẦN KIỂM TRA SENDER ID TRÙNG VỚI NGƯỜI DÙNG HIỆN TẠI
                    // Bỏ qua việc kiểm tra senderType để khắc phục lỗi từ Backend
                    const isSentByCurrentUser = (String(msg.senderID) === String(currentUserID));
                    
                    displayMessage(msg, isSentByCurrentUser); 
                });
                scrollToBottom(); // Cuộn xuống dưới cùng sau khi tải xong tất cả
            } else {
                messagesDisplay.innerHTML = '<div class="text-center text-gray-500 py-4">Chưa có tin nhắn nào.</div>';
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải lịch sử chat:', error);
            if (messagesDisplay) {
                messagesDisplay.innerHTML = `<div class="text-center text-red-500 py-4">Lỗi tải lịch sử: ${error.message}</div>`;
            }
        });
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
    
    // 🚀 BẮT ĐẦU TẢI LỊCH SỬ CHAT KHI DOM LOAD XONG
    // Chỉ tải nếu đã có Conversation ID
    if (currentConvID !== 'null') {
        loadChatHistory(currentConvID, currentUserID);
    }
    
    // --- SOCKET LISTENERS ---
    
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
        socket.emit('join_room', { conversationID: currentConvID });
        
        // Tin nhắn Bot sẽ được Server broadcast sau.
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
                    
                    // Tự hiển thị tin nhắn đầu tiên (Khách hàng)
                    displayMessage(messageData, true); 
                    
                } else {
                    // GỬI TIN NHẮN BÌNH THƯỜNG
                    socket.emit('send_message', messageData); 
                    
                    // 💥 TỰ HIỂN THỊ (Client-side render)
                    displayMessage(messageData, true); 
                }
                
                // Xóa nội dung
                messageInput.value = '';
                messageInput.focus();
            }
        });
    }

}); // Đóng initializeChat function

} // Đóng if-else check guest user
