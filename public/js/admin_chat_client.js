// FILE: GODIFA/public/js/admin_chat_client.js - ĐÃ KHẮC PHỤC HOÀN CHỈNH

document.addEventListener('DOMContentLoaded', () => {
    // ----------------------------------------------------------------
    // 1. KHỞI TẠO DỮ LIỆU & BIẾN
    // ----------------------------------------------------------------
    const metadata = document.getElementById('admin-metadata');
    const currentUserID = metadata ? metadata.getAttribute('data-user-id') : null;
    let currentConvID = metadata ? metadata.getAttribute('data-init-conv-id') : 'null';
    let currentCustomerID = metadata ? metadata.getAttribute('data-init-customer-id') : 'null';
    
    const messagesList = document.getElementById('messagesList');
    const messageForm = document.getElementById('userMessageForm');
    const messageInput = document.getElementById('userMessageInput');
    const conversationList = document.getElementById('conversationList');

    const SOCKET_SERVER_URL = 'http://localhost:3000'; 
    const socket = io(SOCKET_SERVER_URL);
    
    // ----------------------------------------------------------------
    // 2. HÀM TIỆN ÍCH
    // ----------------------------------------------------------------

    function formatTime(isoString) {
        const date = new Date(isoString);
        return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
    }

    function scrollToBottom() {
        if (messagesList) {
            messagesList.scrollTop = messagesList.scrollHeight;
        }
    }

    // Hàm hiển thị tin nhắn (Dùng message.chatContent)
    // Hàm hiển thị tin nhắn cho giao diện Admin
function appendMessageToChat(message, currentUserID) {
    const isSentByMe = (String(message.senderType) === 'user' && String(message.senderID) === currentUserID);
    
    let messageClass;
    let iconHtml = ''; 
    
    if (isSentByMe) {
        // Tin nhắn Admin gửi
        messageClass = 'bg-blue-500 text-white self-end rounded-br-none'; 
    } else if (String(message.senderType) === 'bot') {
        // ⬅️ CHỈNH SỬA CLASS TAILWIND TRỰC TIẾP
        messageClass = 'bg-yellow-100 text-gray-700 self-start rounded-tl-none border'; 
        iconHtml = '<i class="fas fa-robot mr-2 text-yellow-700"></i>'; // Icon màu vàng đậm hơn
    } else {
        // Tin nhắn Customer
        messageClass = 'bg-white text-gray-800 self-start rounded-tl-none border';
    }

    const messageHTML = `
        <div class="flex ${isSentByMe ? 'justify-end' : 'justify-start'} mb-3">
            <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-xl shadow ${messageClass}">
                <p class="text-sm">${iconHtml}${message.chatContent || message.content}</p> 
                <span class="text-xs opacity-75 mt-1 block text-right">${formatTime(message.date)}</span>
            </div>
        </div>
    `;
    
    if (messagesList) {
        messagesList.insertAdjacentHTML('beforeend', messageHTML);
    }
}

    // Hàm cập nhật Item trong danh sách hội thoại khi có tin nhắn mới
function updateConversationListItem(msg) {
    const convItem = document.querySelector(`.conversation-item[data-conv-id="${msg.conversationID}"]`);
    if (!convItem) return; // Bỏ qua nếu không tìm thấy item

    // 1. Cập nhật Nội dung và Thời gian tin nhắn cuối
    const lastMessageContent = document.getElementById(`last-message-${msg.conversationID}`);
    const lastMessageTime = document.getElementById(`last-time-${msg.conversationID}`);
    
    if (lastMessageContent) {
        let contentDisplay = msg.chatContent || msg.content;
        // Giới hạn ký tự (giống PHP)
        contentDisplay = contentDisplay.length > 40 ? contentDisplay.substring(0, 40) + '...' : contentDisplay;
        lastMessageContent.innerHTML = contentDisplay;
    }
    
    if (lastMessageTime) {
        lastMessageTime.innerHTML = formatTime(msg.date);
    }
    
    // 2. CẬP NHẬT BADGE CHƯA ĐỌC (LỖI CẦN KHẮC PHỤC)
    // Badge chỉ hiển thị khi tin nhắn KHÔNG phải do Admin gửi (senderType !== 'user')
    if (String(msg.senderType) !== 'user') {
        let badge = document.getElementById(`badge-${msg.conversationID}`);
        
        if (!badge) {
            // Nếu badge chưa tồn tại, tạo mới nó
            badge = document.createElement('span');
            badge.id = `badge-${msg.conversationID}`;
            badge.className = 'unread-badge px-2 py-1 bg-red-500 text-white text-xs rounded-full';
            
            // Tìm nơi để chèn: tìm div cha của lastMessageTime (text-right ml-2 flex-shrink-0)
            const parentDiv = lastMessageTime ? lastMessageTime.parentElement : null;

            if (parentDiv) {
                // Thêm badge vào sau time
                parentDiv.appendChild(badge);
                badge.textContent = '1';
            }
        } else {
            // Nếu badge đã tồn tại, tăng số đếm (chuyển đổi text sang số)
            let currentCount = parseInt(badge.textContent, 10);
            badge.textContent = currentCount + 1;
        }

        // 3. ĐẨY ITEM CÓ TIN NHẮN MỚI LÊN ĐẦU DANH SÁCH
        const listParent = document.getElementById('conversationList');
        if (listParent && convItem !== listParent.firstElementChild) {
             // Chỉ di chuyển lên đầu nếu nó không phải là phần tử đầu tiên
             listParent.prepend(convItem);
        }
    }

    // 4. Highlight nhẹ (tùy chọn)
    convItem.classList.add('bg-yellow-50');
    setTimeout(() => {
        // Loại bỏ highlight nếu đây không phải hội thoại đang active
        if (String(msg.conversationID) !== currentConvID) {
            convItem.classList.remove('bg-yellow-50');
        }
    }, 5000); 
}

    // ----------------------------------------------------------------
    // 3. XỬ LÝ SOCKET.IO
    // ----------------------------------------------------------------

    socket.on('connect', () => {
        console.log('Connected to Socket.IO Server');
        
        // 1. Tham gia phòng chat hiện tại (Nếu đang mở một conversation nào đó)
        if (currentConvID !== 'null') {
             // Server lắng nghe 'join_room', cần gửi object { conversationID: ID }
             socket.emit('join_room', { conversationID: currentConvID }); 
             console.log(`Tham gia phòng chat conv:${currentConvID}`);
        }
    });
    
    socket.on('connect_error', (error) => {
        console.error('Lỗi kết nối Socket.IO:', error);
    });

    // SỬA LỖI TÊN SỰ KIỆN: new_message -> receive_message (Từ Server)
    socket.on('receive_message', (msg) => {
        // Chỉ xử lý tin nhắn nếu nó thuộc conversation đang được mở
        if (String(msg.conversationID) === currentConvID) { 
            
            // QUAN TRỌNG: Tránh hiển thị lại tin nhắn VỪA GỬI (vì ta đã tự hiển thị ở bước 4)
            const isSentByMe = (String(msg.senderType) === 'user' && String(msg.senderID) === currentUserID);
            if (isSentByMe) {
                // Server broadcast tin nhắn của mình, ta bỏ qua để tránh lặp
                return;
            }
            
            // Hiển thị tin nhắn từ Khách hàng hoặc Bot
            appendMessageToChat(msg, currentUserID);
            scrollToBottom();
        }
        
        // Luôn cập nhật danh sách hội thoại
        updateConversationListItem(msg);
    });

    // SỬA LỖI TÊN SỰ KIỆN: new conversation -> new_conversation_available (Từ Server)
    socket.on('new_conversation_available', (data) => {
        console.log('New conversation created. Reload list to show the new conversation.');
        // Bạn có thể thay thế alert bằng logic tải lại danh sách conversation qua AJAX
        alert('Có khách hàng mới bắt đầu chat. Vui lòng tải lại trang.'); 
    });


    // ----------------------------------------------------------------
    // 4. XỬ LÝ GỬI TIN NHẮN (Admin)
    // ----------------------------------------------------------------
    if (messageForm) {
        messageForm.addEventListener('submit', (e) => {
            e.preventDefault(); 
            
            const content = messageInput.value.trim();
            if (content === '') return;

            if (currentConvID === 'null' || currentCustomerID === 'null') {
                alert('Vui lòng chọn một hội thoại để gửi tin.');
                return;
            }

            const messageData = {
                conversationID: currentConvID,
                customerID: currentCustomerID, 
                chatContent: content, // SỬ DỤNG chatContent
                senderID: currentUserID,
                senderType: 'user', 
                date: new Date().toISOString(),
                isNewConversation: false
            };

            // 1. TỰ HIỂN THỊ TIN NHẮN NGAY LẬP TỨC (Client-side render)
            appendMessageToChat(messageData, currentUserID);
            scrollToBottom();

            // 2. Gửi qua Socket.IO
            socket.emit('send_message', messageData);

            // 3. Xóa nội dung input
            messageInput.value = '';
            messageInput.focus();
        });
    }

    // ----------------------------------------------------------------
    // ----------------------------------------------------------------
// 5. XỬ LÝ GIAO DIỆN & TẢI DỮ LIỆU (Load History)
// ----------------------------------------------------------------

let lastConvID = 'null'; // Biến để theo dõi phòng chat cũ

window.loadConversation = function(element) {
    // 1. Cập nhật biến
    lastConvID = currentConvID; // Lưu ID cũ trước khi cập nhật
    currentConvID = element.getAttribute('data-conv-id');
    currentCustomerID = element.getAttribute('data-customer-id');
    const customerName = element.getAttribute('data-customer-name');

    // Cập nhật Header và Input Area
    const headerDisplay = document.getElementById('current-customer-name-display');
    const inputArea = document.getElementById('chatInputArea');
    if (headerDisplay) {
        headerDisplay.innerHTML = `<i class="fas fa-user-circle mr-2"></i>${customerName}`;
    }
    if (inputArea) {
        inputArea.classList.remove('hidden');
    }
    
    // 2. Xử lý Socket.IO: Rời phòng cũ và Tham gia phòng mới
    if (lastConvID !== 'null' && lastConvID !== currentConvID) {
        // Rời phòng cũ nếu nó khác null và khác phòng mới
        socket.emit('leave_room', { conversationID: lastConvID });
        console.log(`Rời phòng chat conv:${lastConvID}`);
    }
    // Tham gia phòng mới
    socket.emit('join_room', { conversationID: currentConvID });
    console.log(`Tham gia phòng chat conv:${currentConvID}`);

    // 3. Highlight cuộc hội thoại đang chọn (CSS)
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.classList.remove('bg-blue-50', 'border-blue-500'); 
        item.classList.add('border-transparent');
    });
    element.classList.remove('border-transparent');
    element.classList.add('bg-blue-50', 'border-blue-500'); 

    // 4. Tải lịch sử chat (Fetch/AJAX)
    if (messagesList) {
        // Hiện thông báo đang tải
        messagesList.innerHTML = '<div class="w-full text-center py-10 text-gray-400"><i class="fas fa-spinner fa-spin text-2xl"></i> Đang tải lịch sử chat...</div>';
    }

    // ĐIỀU CHỈNH URL API NÀY TÙY VÀO CẤU TRÚC PHP CỦA BẠN!
    // Ví dụ: Gọi file PHP để lấy tin nhắn theo ConvID
    const apiUrl = `/GODIFA/controller/ChatController.php?action=getMessages&conv_id=${currentConvID}`; 
    
    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                // Ném lỗi nếu response không thành công (vd: 404, 500)
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (messagesList) {
                messagesList.innerHTML = ''; // Xóa thông báo đang tải
            }
            if (data.success && data.messages) {
                // Hiển thị lịch sử chat mới
                data.messages.forEach(msg => {
                    // Cần đảm bảo appendMessageToChat chỉ nhận message object, không phải array
                    appendMessageToChat(msg, currentUserID); 
                });
                scrollToBottom();
            } else {
                messagesList.innerHTML = '<div class="w-full text-center py-10 text-gray-400">Chưa có tin nhắn nào trong hội thoại này.</div>';
            }
            
            // Xóa badge tin chưa đọc sau khi tải thành công
            const badge = document.getElementById(`badge-${currentConvID}`);
            if (badge) {
                badge.remove();
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải lịch sử chat:', error);
            if (messagesList) {
                messagesList.innerHTML = '<div class="w-full text-center py-10 text-red-500">Lỗi: Không thể tải lịch sử chat. Vui lòng kiểm tra console.</div>';
            }
        });
}


// --- Thêm logic để load cuộc hội thoại mặc định sau khi DOMContentLoaded ---

    if (currentConvID !== 'null') {
        // Tìm element của cuộc hội thoại mặc định để kích hoạt hàm loadConversation
        const defaultConvElement = document.querySelector(`.conversation-item[data-conv-id="${currentConvID}"]`);
        if (defaultConvElement) {
            // Kích hoạt loadConversation cho hội thoại đầu tiên (hoặc mặc định)
            window.loadConversation(defaultConvElement); 
        }
    }
    
}); // Đóng document.addEventListener('DOMContentLoaded', ...