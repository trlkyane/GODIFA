<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

<?php 
// File: ../view/order_detail.php
// Biến $order và $orderDetails đã được extract từ OrderController:orderDetail()
// Đường dẫn Controller (Dựa trên vị trí file này: /view/order_detail.php)
include_once(__DIR__ . '/../layout/header.php');
$controller_url = '../controller/cOrder.php'; 
?>

<div class="container my-5">
    <h1 class="text-2xl font-bold mb-4">Chi Tiết Đơn Hàng #<?php echo htmlspecialchars($order['orderID']); ?></h1>

    <?php if (isset($_SESSION['notify_success'])): ?>
        <div class="alert alert-success bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <?php echo htmlspecialchars($_SESSION['notify_success']); unset($_SESSION['notify_success']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['notify_error'])): ?>
        <div class="alert alert-danger bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <?php echo htmlspecialchars($_SESSION['notify_error']); unset($_SESSION['notify_error']); ?>
        </div>
    <?php endif; ?>
    
    <table class="table-auto w-full border-collapse">
        <thead>
            <tr class="bg-gray-200 text-gray-700 text-sm font-semibold uppercase tracking-wider">
                <th class="p-3 border text-left">Sản phẩm</th>
                <th class="p-3 border text-center">SL</th>
                <th class="p-3 border text-right">Giá</th>
                <th class="p-3 border text-right">Tổng</th>
                <th class="p-3 border text-center">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orderDetails as $detail): ?>
            <tr class="hover:bg-gray-50">
                <td class="p-2 border"><?php echo htmlspecialchars($detail['productName']); ?></td>
                <td class="p-2 border text-center"><?php echo htmlspecialchars($detail['quantity']); ?></td>
                <td class="p-2 border text-right"><?php echo number_format($detail['price']); ?> VNĐ</td>
                <td class="p-2 border text-right"><?php echo number_format($detail['price'] * $detail['quantity']); ?> VNĐ</td>
                
                <td class="p-2 border text-center">
                    <?php if (isset($detail['isReviewed']) && $detail['isReviewed']): ?>
                        <span class="badge bg-success text-white px-2 py-1 rounded text-sm bg-green-500">Đã Đánh Giá</span>
                    
                    <?php elseif (isset($detail['canReview']) && $detail['canReview']): ?>
                        <button 
                            class="btn btn-warning btn-sm review-btn bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 text-sm rounded transition duration-200"
                            data-bs-toggle="modal" 
                            data-bs-target="#reviewModal"
                            data-product-id="<?php echo htmlspecialchars($detail['productID']); ?>"
                            data-product-name="<?php echo htmlspecialchars($detail['productName']); ?>"
                            data-order-id="<?php echo htmlspecialchars($order['orderID']); ?>"
                        >
                            Đánh Giá Ngay
                        </button>
                    
                    <?php else: ?>
                        <span class="text-gray-500 text-sm">Chưa đủ điều kiện</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            
            <div class="modal-header">
                <h5 class="modal-title font-bold text-lg" id="reviewModalLabel">Đánh giá Sản phẩm: <span id="modalProductName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body">
                <form id="reviewForm" action="<?php echo htmlspecialchars($controller_url); ?>?action=submit_review" method="POST">
                    
                    <input type="hidden" name="order_id" id="modalOrderId" value="">
                    <input type="hidden" name="product_id" id="modalProductId" value="">
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-semibold mb-2">Chất lượng sản phẩm:</label>
                        <div class="rating-stars flex justify-start space-x-1" id="starRatingContainer">
                            <?php 
                            // Lặp từ 1 đến 5 để hiển thị sao từ trái sang phải
                            for ($i = 1; $i <= 5; $i++): 
                            ?>
                                <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required class="hidden" />
                                
                                <label for="star<?php echo $i; ?>" 
                                       class="text-3xl cursor-pointer text-gray-300 transition duration-150 flex items-center" 
                                       data-value="<?php echo $i; ?>">
                                       
                                    <span class="text-sm font-medium mr-1 text-gray-700 leading-none"><?php echo $i; ?></span>
                                    
                                    &#9733; </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="comment" class="block text-gray-700 text-sm font-semibold mb-2">Bình luận:</label>
                        <textarea id="comment" name="comment" rows="4" 
                                  class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                  maxlength="500" placeholder="Hãy chia sẻ nhận xét của bạn..." required></textarea>
                    </div>

                    <div class="text-right">
                        <button type="button" class="btn btn-secondary bg-gray-500 text-white px-3 py-2 rounded mr-2" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded">Gửi Đánh Giá</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const reviewModal = document.getElementById('reviewModal');
    const reviewForm = document.getElementById('reviewForm');
    const modalProductId = document.getElementById('modalProductId');
    const modalOrderId = document.getElementById('modalOrderId');
    const modalProductName = document.getElementById('modalProductName');
    const starContainer = document.getElementById('starRatingContainer');
    const starLabels = reviewModal.querySelectorAll('#starRatingContainer label');

    const COLOR_YELLOW = 'rgb(255, 193, 7)'; // Màu vàng cho sao đã chọn
    const COLOR_HOVER = 'rgb(253, 224, 71)'; // Màu vàng nhạt khi hover
    const COLOR_GRAY = 'rgb(209, 213, 219)'; // Màu xám mặc định

    // Hàm tô màu sao dựa trên một giá trị rating
    function highlightStars(value, color) {
        starLabels.forEach(label => {
            const lValue = parseInt(label.getAttribute('data-value'));
            // Tô màu nếu giá trị ngôi sao (lValue) nhỏ hơn hoặc bằng giá trị rating (value)
            label.style.color = lValue <= value ? color : COLOR_GRAY;
        });
    }

    // 1. Lắng nghe sự kiện khi Modal được hiển thị (Bootstrap event)
    reviewModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget; 

        const productId = button.getAttribute('data-product-id');
        const orderId = button.getAttribute('data-order-id');
        const productName = button.getAttribute('data-product-name');

        // Cập nhật giá trị vào các trường ẩn và tiêu đề Modal
        modalProductId.value = productId;
        modalOrderId.value = orderId;
        modalProductName.textContent = productName;
        
        // Reset form và rating khi modal mở
        reviewForm.reset();
        highlightStars(0, COLOR_GRAY); // Reset màu sao về 0
    });
    
    // 2. Logic cho việc Highlight Sao
    starLabels.forEach(label => {
        const ratingInput = document.getElementById(label.getAttribute('for'));
        const ratingValue = parseInt(label.getAttribute('data-value'));
        
        // A. Xử lý CLICK/CHANGE (Chọn sao)
        ratingInput.addEventListener('change', () => {
            // Khi input thay đổi (sau khi click), tô màu vĩnh viễn
            highlightStars(ratingValue, COLOR_YELLOW);
        });

        // B. Xử lý HOVER (Rê chuột)
        label.addEventListener('mouseover', () => {
            // Tô sáng TẠM THỜI từ 1 đến ratingValue
            highlightStars(ratingValue, COLOR_HOVER);
        });

        label.addEventListener('mouseout', () => {
            const checkedStar = starContainer.querySelector('input[name="rating"]:checked');
            
            if (checkedStar) {
                // Nếu ĐÃ chọn sao, trở về trạng thái đã chọn (Màu vàng)
                const selectedValue = parseInt(checkedStar.value);
                highlightStars(selectedValue, COLOR_YELLOW);
            } else {
                // Nếu CHƯA chọn, trở về trạng thái màu xám
                highlightStars(0, COLOR_GRAY);
            }
        });
    });
});
</script>
<?php
include_once(__DIR__ . '/../layout/footer.php');
?>