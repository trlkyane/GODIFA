<?php
/**
 * Quản lý Đánh giá Sản phẩm (Reviews)
 * File: admin/pages/reviews.php
 */

// Định tuyến và kiểm tra quyền hạn
require_once __DIR__ . '/../middleware/auth.php';
requireStaff();

// Check permission
if (!hasPermission('view_reviews') && !hasPermission('manage_reviews')) {
    die('<div class="p-8"><div class="bg-red-100 text-red-700 p-4 rounded">Bạn không có quyền truy cập trang này!</div></div>');
}

// Load Controller (Admin version)
// Đường dẫn này được giả định là chính xác: /GODIFA/controller/admin/cReview.php
require_once __DIR__ . '/../../controller/admin/cReview.php'; 
$reviewController = new cReview();

// --- XỬ LÝ LẤY DỮ LIỆU ---

// Xử lý TÌM KIẾM
$searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : '';
$reviews = $reviewController->getReviews($searchKeyword, -1); 
$totalReviews = $reviewController->countTotalReviews(); 

$pageTitle = 'Quản lý Đánh giá';
// Giả định các file include (header, sidebar, footer) tồn tại
include __DIR__ . '/../includes/header.php';
?>

<div class="flex h-screen bg-gray-100">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="flex-1 overflow-y-auto ml-64">
        <div class="bg-white shadow sticky top-0 z-10">
            <div class="px-4 md:px-6 py-4 flex flex-wrap justify-between items-center gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">
                        <i class="fas fa-star text-yellow-500 mr-2"></i>
                        Quản lý Đánh giá
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Tổng số: <strong><?php echo $totalReviews; ?></strong> đánh giá
                        
                    </p>
                </div>
                
                <div class="flex items-center space-x-2">
                    <form method="GET" class="flex items-center">
                        <input type="hidden" name="page" value="reviews">
                        <div class="relative">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($searchKeyword); ?>"
                                    placeholder="Tìm khách hàng, sản phẩm..." 
                                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 w-64">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <button type="submit" class="ml-2 px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700">
                            Tìm
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="p-4 md:p-6">
            <div id="notification-container" class="fixed top-5 right-5 z-50 space-y-2"></div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <?php if (empty($reviews)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-search-minus text-4xl mb-2 text-gray-300"></i>
                    <p>Không tìm thấy đánh giá nào phù hợp.</p>
                </div>
                <?php else: ?>
                <table class="min-w-full divide-y divide-gray-200" id="reviewsTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sản phẩm</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Khách hàng</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Điểm</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bình luận</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Hiển thị</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($reviews as $review): ?>
                        <?php
                        $reviewID = $review['reviewID'] ?? 0;
                        $productName = htmlspecialchars($review['productName'] ?? 'Sản phẩm N/A');
                        $customerName = htmlspecialchars($review['customerName'] ?? 'Khách hàng N/A');
                        $rating = $review['rating'] ?? 0;
                        $comment = htmlspecialchars($review['comment'] ?? 'Không có bình luận.');
                        $status = $review['status'] ?? 1; // 1: hiển thị, khác: ẩn
                        $statusText = ($status == 1) ? 'Hiển thị' : 'Đang ẩn';
                        $statusColor = ($status == 1) ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700';

                        // --- LOGIC MỚI: Cắt ngắn Tên sản phẩm ---
                        $maxProductNameLength = 10; // Giới hạn ký tự tên sản phẩm hiển thị trên bảng
                        $productNameExcerpt = mb_substr($productName, 0, $maxProductNameLength);
                        if (mb_strlen($productName) > $maxProductNameLength) {
                            $productNameExcerpt .= '...';
                        }
                        // --- END LOGIC MỚI ---

                        $commentExcerpt = mb_substr($comment, 0, 50) . (mb_strlen($comment) > 50 ? '...' : '');
                        ?>
                        <tr class="<?php echo $status != 1 ? 'opacity-75' : ''; ?>" data-review-id="<?php echo $reviewID; ?>">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $reviewID; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" title="<?php echo $productName; ?>"><?php echo $productNameExcerpt; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo $customerName; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-center">
                                <span class="font-bold text-lg text-amber-500"><?php echo $rating; ?></span><i class="fas fa-star text-xs ml-1 text-amber-500"></i>
                            </td>
                            <td class="px-6 py-4 max-w-xs text-sm text-gray-600 truncate" title="<?php echo htmlspecialchars($comment); ?>"><?php echo $commentExcerpt; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusColor; ?>" data-status-id="<?php echo $status; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                <div class="flex justify-center space-x-2">
                                    <button onclick='viewReview(<?php echo json_encode($review, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE); ?>)' 
                                            class="px-2 py-1 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 text-sm" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </button>

                                    <?php if (hasPermission('manage_reviews')): ?>
                                        <?php if ($status == 1): ?>
                                        <button onclick='toggleVisibility(<?php echo $reviewID; ?>, <?php echo json_encode($productName); ?>)'
                                                class="px-2 py-1 bg-red-100 text-red-600 rounded hover:bg-red-200 text-sm" title="Ẩn đánh giá">
                                            <i class="fas fa-eye-slash"></i>
                                        </button>
                                        <?php else: ?>
                                        <button onclick='toggleVisibility(<?php echo $reviewID; ?>, <?php echo json_encode($productName); ?>)'
                                                class="px-2 py-1 bg-green-100 text-green-600 rounded hover:bg-green-200 text-sm" title="Hiển thị lại">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php if (hasPermission('delete_reviews')): ?>
                                        <button onclick='deleteReview(<?php echo $reviewID; ?>, <?php echo json_encode($productName); ?>)' 
                                                class="px-2 py-1 bg-gray-100 text-gray-600 rounded hover:bg-gray-200 text-sm" title="Xóa vĩnh viễn">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div id="viewModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-2/3 shadow-lg rounded-lg bg-white mb-10">
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-search-plus text-blue-500 mr-2"></i>
                Chi tiết Đánh giá #<span id="view_reviewID"></span>
            </h3>
            <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div class="mt-4 space-y-3">
            <p class="text-lg font-semibold text-gray-800" id="view_productName"></p>
            <p class="text-sm text-gray-600">
                <i class="fas fa-user mr-2 text-blue-500"></i> Khách hàng: <span id="view_customerName"></span>
                <i class="fas fa-shopping-bag ml-4 mr-2 text-green-500"></i> Đơn hàng: #<span id="view_orderID"></span>
            </p>
            <div class="flex items-center text-amber-500 text-2xl font-bold">
                <span id="view_rating"></span><i class="fas fa-star text-base ml-1"></i>
            </div>
            
            <h4 class="font-bold text-gray-700 pt-2 border-t mt-3">Bình luận:</h4>
            <div class="bg-gray-50 p-3 rounded border text-gray-800 whitespace-pre-wrap" id="view_comment"></div>
            
            <p class="text-xs text-gray-500 pt-2 border-t mt-3">
                <i class="fas fa-clock mr-1"></i> Ngày tạo: <span id="view_dateReview"></span>
            </p>
        </div>
    </div>
</div>

<div id="confirmModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-1/4 mx-auto p-6 border w-96 shadow-xl rounded-lg bg-white">
        <div class="flex justify-between items-center pb-4 border-b">
            <h3 class="text-lg font-bold text-gray-800 flex items-center" id="confirm_title">
            </h3>
            <button onclick="closeConfirmModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div class="mt-4 text-gray-700">
            <p id="confirm_message" class="text-base leading-relaxed"></p>
        </div>
        
        <div class="mt-6 flex justify-end space-x-3">
            <button onclick="closeConfirmModal()" class="px-4 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition duration-150">
                Hủy bỏ
            </button>
            <button id="confirmActionButton" onclick="executeAction()" class=""></button>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
// --- Global State for Confirmation ---
let currentAction = null;
let currentReviewId = null;

// --- View Modal Logic ---
function viewReview(review) {
    document.getElementById('view_reviewID').textContent = review.reviewID || 'N/A';
    document.getElementById('view_productName').textContent = review.productName || 'N/A';
    document.getElementById('view_customerName').textContent = review.customerName || 'N/A';
    document.getElementById('view_orderID').textContent = review.orderID || 'N/A';
    document.getElementById('view_rating').textContent = review.rating || 0;
    
    // Sử dụng innerHTML để hiển thị bình luận an toàn
    document.getElementById('view_comment').innerHTML = (review.comment || 'Không có bình luận.').replace(/\n/g, '<br>');
    
    // Xử lý ngày tháng
    let dateStr = review.dateReview;
    let date;
    if (dateStr) {
        if (!isNaN(Date.parse(dateStr))) {
            date = new Date(dateStr).toLocaleString('vi-VN');
        } else if (!isNaN(parseInt(dateStr)) && String(parseInt(dateStr)).length === dateStr.length) {
            date = new Date(parseInt(dateStr) * 1000).toLocaleString('vi-VN');
        } else {
            date = dateStr; 
        }
    } else {
        date = 'N/A';
    }

    document.getElementById('view_dateReview').textContent = date;
    
    document.getElementById('viewModal').classList.remove('hidden');
}

function closeViewModal() {
    document.getElementById('viewModal').classList.add('hidden');
}

// ----------------------------------------------------------------------------------
// JS HANDLERS (Hàm gọi Modal Xác nhận)
// ----------------------------------------------------------------------------------

function toggleVisibility(id, productName) {
    openConfirmModal(id, 'toggle', productName);
}

/**
 * Hàm trung gian gọi Modal Xác nhận cho hành động Xóa.
 * @param {number} id ID của đánh giá
 * @param {string} productName Tên sản phẩm
 */
function deleteReview(id, productName) {
    openConfirmModal(id, 'delete', productName);
}

// --- Confirmation Modal Logic ---

function openConfirmModal(id, action, productName) {
    currentReviewId = id;
    currentAction = action;
    const modal = document.getElementById('confirmModal');
    const titleElement = document.getElementById('confirm_title');
    const messageElement = document.getElementById('confirm_message');
    const confirmButton = document.getElementById('confirmActionButton');

    let title = '';
    let message = '';
    let btnText = '';
    let btnClass = '';

    if (action === 'toggle') {
        title = 'Thay đổi hiển thị đánh giá';
        message = `Bạn có chắc muốn **ẨN/HIỂN THỊ** đánh giá cho sản phẩm **"${productName}"**?`;
        btnText = 'Xác nhận';
        btnClass = 'bg-yellow-600 hover:bg-yellow-700';
    } else if (action === 'delete') {
        title = 'Xóa Vĩnh viễn Đánh giá';
        message = `CẢNH BÁO: Bạn có chắc muốn **XÓA VĨNH VIỄN** đánh giá cho sản phẩm **"${productName}"**? Hành động này không thể hoàn tác!`;
        btnText = 'Xác nhận Xóa';
        btnClass = 'bg-gray-700 hover:bg-gray-800';
    } else {
        return;
    }

    titleElement.innerHTML = `<i class="fas fa-exclamation-triangle text-xl mr-2 text-yellow-500"></i> ${title}`;
    // Thay thế markdown **...** bằng thẻ <strong>
    messageElement.innerHTML = message.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    
    confirmButton.textContent = btnText;
    confirmButton.className = `px-4 py-2 text-white font-semibold rounded-lg shadow-md transition duration-150 ${btnClass}`;
    
    modal.classList.remove('hidden');
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.add('hidden');
    currentAction = null;
    currentReviewId = null;
}

// ----------------------------------------------------------------------------------
// AJAX IMPLEMENTATION (FETCHO API)
// ----------------------------------------------------------------------------------

function executeAction() {
    if (currentReviewId && currentAction) {
        // Đường dẫn API Gateway: /GODIFA/api/review_action.php
        const apiUrl = '/GODIFA/api/review_action.php'; 
        
        let postData = {
            reviewID: currentReviewId,
            action: currentAction === 'delete' ? 'delete' : 'toggle'
        };

        // 1. Gửi yêu cầu AJAX
        fetch(apiUrl, { 
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(postData)
        })
        .then(response => {
            if (!response.ok) {
                // Xử lý lỗi HTTP (ví dụ: 404, 500)
                throw new Error('Lỗi mạng hoặc server không phản hồi.');
            }
            return response.json();
        })
        .then(data => {
            closeConfirmModal();
            
            if (data.success) {
                showNotification(data.message, 'success'); 
                
                // Cập nhật DOM VÀ TẢI LẠI TRANG
                updateReviewRow(currentReviewId, postData.status, postData.action); 

            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            closeConfirmModal();
            console.error('Fetch Error:', error);
            showNotification(`Lỗi: ${error.message || 'Không thể kết nối với API.'}`, 'error');
        });
    }
}

// ----------------------------------------------------------------------------------
// CẬP NHẬT GIAO DIỆN VÀ TẢI LẠI TRANG
// ----------------------------------------------------------------------------------

// Hàm tìm dòng <tr> dựa trên reviewID (Dùng thuần JS)
function findRowById(reviewID) {
    // Sử dụng data attribute đã thêm vào <tr>
    return document.querySelector(`tr[data-review-id="${reviewID}"]`);
}

/**
 * Xử lý cập nhật giao diện sau khi hành động thành công.
 * Sẽ tải lại trang để đảm bảo các nút thao tác được PHP render lại chính xác.
 */
function updateReviewRow(reviewID, newStatus, action) {
    // Nếu là hành động xóa
    if (action === 'delete') {
        const row = findRowById(reviewID);
        if (row) {
            row.style.opacity = '0';
            // Xóa dòng khỏi bảng sau khi chuyển màu mờ
            setTimeout(() => row.remove(), 300);
        }
        
        // Tải lại trang sau khi xóa hoàn tất
        setTimeout(() => window.location.reload(), 500); 
        return;
    }

    // Nếu là hành động updateStatus (Duyệt/Từ chối)
    // Tải lại toàn bộ trang để PHP render lại trạng thái mới và các nút thao tác
    setTimeout(() => {
        window.location.reload();
    }, 500); // Chờ 0.5 giây để người dùng kịp thấy thông báo thành công
}

// ----------------------------------------------------------------------------------
// HIỂN THỊ THÔNG BÁO (Toast)
// ----------------------------------------------------------------------------------
function showNotification(message, type) {
    const container = document.getElementById('notification-container');
    if (!container) return;
    
    const div = document.createElement('div');
    const colorClass = type === 'success' ? 'bg-green-500' : 'bg-red-500';
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
    
    div.className = `p-4 text-white rounded-lg shadow-lg transition-all duration-300 transform translate-x-0 ${colorClass}`;
    div.style.opacity = '1';
    div.innerHTML = `<i class="fas ${icon} mr-2"></i> ${message}`;
    
    container.prepend(div); // Thêm vào đầu container

    setTimeout(() => {
        div.style.transform = 'translateX(100%)';
        div.style.opacity = '0';
        setTimeout(() => div.remove(), 300);
    }, 4000); // Hiển thị 4 giây
}


// Close modal when clicking outside
window.onclick = function(event) {
    const viewModal = document.getElementById('viewModal');
    const confirmModal = document.getElementById('confirmModal');
    if (event.target == viewModal) closeViewModal();
    if (event.target == confirmModal) closeConfirmModal();
}
</script>