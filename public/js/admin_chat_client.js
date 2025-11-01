// FILE: GODIFA/public/js/admin_chat_client.js

document.addEventListener('DOMContentLoaded', () => {
    const metadata = document.getElementById('admin-metadata');
    const currentUserID = metadata.getAttribute('data-user-id');
    let currentConvID = metadata.getAttribute('data-init-conv-id');
    let currentCustomerID = metadata.getAttribute('data-init-customer-id');
    
    const messagesList = document.getElementById('messagesList');
    const messageForm = document.getElementById('userMessageForm');
    const messageInput = document.getElementById('userMessageInput');
    const conversationList = document.getElementById('conversationList');

    const SOCKET_SERVER_URL = 'http://localhost:3000'; 
    const socket = io(SOCKET_SERVER_URL);

    // XỬ LÝ SOCKET.IO
    socket.on('connect', () => {
        console.log('Connected to Socket.IO Server');
    });
    
    socket.on('connect_error', (error) => {
        console.error('Lỗi kết nối Socket.IO:', error);
    });

    // Lắng nghe tin nhắn mới từ server (SỰ KIỆN CHUNG: new_message)
    socket.on('new_message', (msg) => {
        // Chỉ xử lý tin nhắn nếu nó thuộc conversation đang được mở
        if (msg.conversationID == currentConvID) { // Sử dụng conversationID
            appendMessageToChat(msg, currentUserID);
            scrollToBottom();
        }
        
        // Luôn cập nhật danh sách hội thoại
        updateConversationListItem(msg);
    });

    socket.on('new conversation', (data) => {
        console.log('New conversation created, reloading list to show the new conversation.');
        alert('Có khách hàng mới bắt đầu chat. Vui lòng tải lại trang.'); 
    });


    // XỬ LÝ GỬI TIN NHẮN (Admin)
    if (messageForm) {
        messageForm.addEventListener('submit', (e) => {
            e.preventDefault(); 
            
            const content = messageInput.value.trim();
            if (content === '') return;

            if (currentConvID === 'null' || currentCustomerID === 'null') {
                alert('Vui lòng chọn một hội thoại để gửi tin.');
                return;
            }

            const message = {
                conversationID: currentConvID, // SỬ DỤNG conversationID
                customerID: currentCustomerID, 
                chatContent: content, // SỬ DỤNG chatContent
                senderID: currentUserID,
                senderType: 'user', 
                date: new Date().toISOString(),
                isNewConversation: false
            };

            // Gửi qua Socket.IO, SỬ DỤNG SỰ KIỆN 'send_message'
            socket.emit('send_message', message);

            // Xóa nội dung input
            messageInput.value = '';
        });
    }

    // XỬ LÝ GIAO DIỆN & TẢI DỮ LIỆU
    window.loadConversation = function(element) {
        currentConvID = element.getAttribute('data-conv-id');
        currentCustomerID = element.getAttribute('data-customer-id');
        const customerName = element.getAttribute('data-customer-name');
        
        document.querySelector('#chatHeader .font-bold').innerHTML = `<i class="fas fa-user-circle mr-2"></i>${customerName}`;
        document.getElementById('chatInputArea').classList.remove('hidden');
        messagesList.innerHTML = `<div class="text-center text-gray-400 mt-auto" id="loadingMessage"><i class="fas fa-spinner fa-spin text-2xl"></i> Đang tải lịch sử chat...</div>`;
        
        document.querySelectorAll('.conversation-item').forEach(el => el.classList.remove('bg-blue-50', 'border-blue-500'));
        element.classList.add('bg-blue-50', 'border-blue-500');

        fetch('/GODIFA/controller/ChatController.php?action=getChatHistory&convID=' + currentConvID)
            .then(response => response.json())
            .then(data => {
                messagesList.innerHTML = ''; 
                
                if (data.success && data.data.length > 0) {
                    data.data.forEach(message => {
                        appendMessageToChat(message, currentUserID);
                    });
                    scrollToBottom();
                } else if (data.success) {
                    messagesList.innerHTML = `<div class="text-center text-gray-500 mt-auto">Chưa có tin nhắn nào trong hội thoại này.</div>`;
                } else {
                     messagesList.innerHTML = `<div class="text-center text-red-500 mt-auto">Lỗi tải dữ liệu. (${data.message || 'Lỗi server'})</div>`;
                }

                const unreadBadge = document.getElementById(`badge-${currentConvID}`);
                if (unreadBadge) {
                    unreadBadge.remove();
                }
            })
            .catch(error => {
                console.error('Lỗi tải lịch sử chat (Network Error):', error);
                messagesList.innerHTML = `<div class="text-center text-red-500 mt-auto">Lỗi tải dữ liệu. (Vui lòng kiểm tra Network Tab)</div>`;
            });
    }

    // Hàm hiển thị tin nhắn (Dùng message.chatContent)
    function appendMessageToChat(message, currentUserID) {
        const isUserMessage = message.senderType === 'user';
        
        const messageClass = isUserMessage 
            ? 'bg-blue-500 text-white self-end rounded-br-none' 
            : 'bg-white text-gray-800 self-start rounded-tl-none border';

        const messageHTML = `
            <div class="flex ${isUserMessage ? 'justify-end' : 'justify-start'} mb-3">
                <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-xl shadow ${messageClass}">
                    <p class="text-sm">${message.chatContent || message.content}</p>
                    <span class="text-xs opacity-75 mt-1 block text-right">${formatTime(message.date)}</span>
                </div>
            </div>
        `;
        messagesList.insertAdjacentHTML('beforeend', messageHTML);
    }

    function updateConversationListItem(msg) {
        // ... (Logic cập nhật item danh sách giữ nguyên)
    }

    function scrollToBottom() {
        messagesList.scrollTop = messagesList.scrollHeight;
    }
    
    function formatTime(isoString) {
        const date = new Date(isoString);
        return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
    }
    
    // Tải hội thoại mặc định khi trang load xong
    if (currentConvID !== 'null') {
        const initConvEl = document.querySelector(`.conversation-item[data-conv-id="${currentConvID}"]`);
        if (initConvEl) {
            setTimeout(() => window.loadConversation(initConvEl), 100); 
        }
    }
});