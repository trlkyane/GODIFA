// FILE: GODIFA/public/js/admin_chat_client.js (Phiên bản cuối cùng đã fix lỗi kiểm tra kiểu dữ liệu)

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
    // ĐÃ CẬP NHẬT: Sử dụng ID mới từ HTML
    const searchConversationInput = document.getElementById('searchConversation'); 

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

    // Hàm hiển thị tin nhắn cho giao diện Admin (ĐÃ CỐ ĐỊNH BOT_SENDER_ID = 0 VÀ DÙNG PARSEINT)
    function appendMessageToChat(message, currentUserID) {
        // Đã cố định ID của Bot trong DB là 0 
        const BOT_SENDER_ID_NUM = 0; 
        
        // Đảm bảo lấy senderID dưới dạng số nguyên để so sánh chính xác hơn
        const messageSenderID = parseInt(message.senderID); 
        const senderType = String(message.senderType);

        // XÁC ĐỊNH TIN NHẮN BOT:
        // 1. Dựa vào senderType (khi gửi trực tiếp/fix từ PHP)
        // 2. Dựa vào senderID (khi tải lịch sử) - Dùng so sánh số nguyên
        const isBotMessage = (
            senderType === 'bot' || 
            (messageSenderID === BOT_SENDER_ID_NUM) 
        );
        
        // Logic: Tin Staff/Admin ('user') căn phải. Tin Khách/Bot căn trái.
        const isSentByStaff = (senderType === 'user' && String(message.senderID) === currentUserID);
        
        let messageClass = isSentByStaff ? 'sent' : 'received';
        let iconHtml = ''; 
        let senderNameDisplay = '';
        
        // Nếu là tin nhắn bot, đảm bảo nó là 'received' để căn trái
        if (isBotMessage) {
             messageClass = 'received';
        }

        // Xác định icon và tên hiển thị cho tin nhắn CĂN TRÁI (received)
        if (messageClass === 'received') {
            if (isBotMessage) {
                // Tin nhắn Bot: Icon Robot
                iconHtml = '<i class="fas fa-robot mr-2 text-yellow-700"></i>'; 
            } 
            
            // Vẫn hiển thị tên người gửi nếu là Khách hàng (customer) hoặc Staff khác (user)
            if (senderType === 'customer' || (senderType === 'user' && !isSentByStaff)) {
                const senderName = message.senderName || 'Khách hàng';
                senderNameDisplay = `<p class="text-xs text-gray-500 mb-1">${senderName}</p>`;
            }
        }

        const messageContent = String(message.chatContent || message.content);

        const messageHTML = `
            <div class="message-row ${messageClass}" data-chat-id="${message.chatID}">
                <div class="message-bubble">
                    ${senderNameDisplay} 
                    <p class="text-sm">${iconHtml}${messageContent}</p> 
                    <span class="timestamp">${formatTime(message.date)}</span>
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
        if (!convItem) return; 

        // 1. Cập nhật Nội dung và Thời gian tin nhắn cuối
        const lastMessageContent = document.getElementById(`last-message-${msg.conversationID}`);
        const lastMessageTime = document.getElementById(`last-time-${msg.conversationID}`);
        
        if (lastMessageContent) {
            let contentDisplay = msg.chatContent || msg.content;
            contentDisplay = contentDisplay.length > 40 ? contentDisplay.substring(0, 40) + '...' : contentDisplay;
            lastMessageContent.innerHTML = contentDisplay;
        }
        
        if (lastMessageTime) {
            lastMessageTime.innerHTML = formatTime(msg.date);
        }
        
        // 2. CẬP NHẬT BADGE CHƯA ĐỌC
        // Tin nhắn đến từ KHÁCH HÀNG (customer) hoặc BOT (bot) sẽ kích hoạt badge
        // Logic kiểm tra Bot tương tự appendMessageToChat (dùng senderID=0)
        const BOT_SENDER_ID_NUM = 0;
        const isBotMessage = (
            String(msg.senderType) === 'bot' || 
            (parseInt(msg.senderID) === BOT_SENDER_ID_NUM) 
        );

        if (String(msg.senderType) !== 'user' || isBotMessage) { 
            let badge = document.getElementById(`badge-${msg.conversationID}`);
            
            if (!badge) {
                badge = document.createElement('span');
                badge.id = `badge-${msg.conversationID}`;
                badge.className = 'unread-badge px-2 py-1 bg-red-500 text-white text-xs rounded-full';
                
                const timeParentDiv = lastMessageTime ? lastMessageTime.parentElement : null;

                if (timeParentDiv) {
                    // Thêm badge vào sau time
                    timeParentDiv.appendChild(badge);
                    badge.textContent = '1';
                }
            } else {
                let currentCount = parseInt(badge.textContent, 10);
                badge.textContent = currentCount + 1;
            }

            // 3. ĐẨY ITEM CÓ TIN NHẮN MỚI LÊN ĐẦU DANH SÁCH
            const listParent = document.getElementById('conversationList');
            if (listParent && convItem !== listParent.firstElementChild) {
                 listParent.prepend(convItem);
            }
        }

        // 4. Highlight nhẹ
        convItem.classList.add('bg-yellow-50');
        setTimeout(() => {
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
        
        if (currentConvID !== 'null') {
             socket.emit('join_room', { conversationID: currentConvID }); 
             console.log(`Tham gia phòng chat conv:${currentConvID}`);
        }
    });
    
    socket.on('connect_error', (error) => {
        console.error('Lỗi kết nối Socket.IO:', error);
    });

    socket.on('receive_message', (msg) => {
        if (String(msg.conversationID) === currentConvID) { 
            
            // Nếu tin nhắn đến là từ chính Staff đang xem (đã tự appendMessage ở hàm gửi) thì bỏ qua
            const isSentByMe = (String(msg.senderType) === 'user' && String(msg.senderID) === currentUserID);
            if (isSentByMe) {
                return;
            }
            
            appendMessageToChat(msg, currentUserID);
            scrollToBottom();
        }
        
        updateConversationListItem(msg);
    });

    socket.on('new_conversation_available', (data) => {
        console.log('New conversation created. Reload list to show the new conversation.');
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
                chatContent: content, 
                senderID: currentUserID,
                senderType: 'user', 
                date: new Date().toISOString(),
                isNewConversation: false
            };

            // 1. TỰ HIỂN THỊ TIN NHẮN NGAY LẬP TỨC (Staff gửi, luôn là 'user')
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
    // 5. XỬ LÝ GIAO DIỆN & TẢI DỮ LIỆU (Load History)
    // ----------------------------------------------------------------

    let lastConvID = 'null'; 

    window.loadConversation = function(element) {
        // 1. Cập nhật biến
        lastConvID = currentConvID; 
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
            socket.emit('leave_room', { conversationID: lastConvID });
            console.log(`Rời phòng chat conv:${lastConvID}`);
        }
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
            messagesList.innerHTML = '<div class="w-full text-center py-10 text-gray-400"><i class="fas fa-spinner fa-spin text-2xl"></i> Đang tải lịch sử chat...</div>';
        }

        const apiUrl = `/GODIFA/controller/ChatController.php?action=getMessages&conv_id=${currentConvID}`; 
        
        fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (messagesList) {
                    messagesList.innerHTML = ''; // Xóa thông báo đang tải
                }
                if (data.success && data.messages) {
                    data.messages.forEach(msg => {
                        // Gọi hàm appendMessageToChat đã được sửa lỗi logic căn chỉnh
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
        const defaultConvElement = document.querySelector(`.conversation-item[data-conv-id="${currentConvID}"]`);
        if (defaultConvElement) {
            window.loadConversation(defaultConvElement); 
        }
    }

    // ----------------------------------------------------------------
    // 6. XỬ LÝ TÌM KIẾM THEO TÊN KHÁCH HÀNG (MỚI)
    // ----------------------------------------------------------------

    if (searchConversationInput) {
        searchConversationInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const conversationItems = document.querySelectorAll('.conversation-item');

            conversationItems.forEach(item => {
                // Lấy tên khách hàng từ data attribute
                const customerName = item.getAttribute('data-customer-name'); 

                if (customerName && customerName.toLowerCase().includes(searchTerm)) {
                    item.style.display = ''; // Hiển thị item
                } else {
                    item.style.display = 'none'; // Ẩn item
                }
            });
            
            // Cập nhật thông báo "Chưa có hội thoại nào"
            const visibleItems = Array.from(conversationItems).filter(item => item.style.display !== 'none');
            const noConvMessage = document.getElementById('noConversationMessage');
            
            if (noConvMessage) {
                if (visibleItems.length === 0 && conversationItems.length > 0) {
                    // Chỉ hiển thị thông báo nếu có item nhưng bị ẩn hết do tìm kiếm
                    noConvMessage.style.display = 'block'; 
                    noConvMessage.querySelector('p').textContent = 'Không tìm thấy khách hàng nào.';
                } else if (conversationItems.length === 0) {
                    // Không làm gì nếu ban đầu đã không có hội thoại
                } else {
                    noConvMessage.style.display = 'none'; 
                }
            }
        });
    }

}); // Đóng document.addEventListener('DOMContentLoaded', ...