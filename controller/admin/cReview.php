<?php
// File: controller/admin/cReview.php

// 🌟 Đảm bảo đường dẫn Model chính xác: Lùi 2 cấp từ controller/admin/ đến model/
require_once __DIR__ . '/../../model/mReview.php'; 
// KHÔNG cần include mCustomer và mProduct ở đây vì logic JOIN đã nằm trong Model

// Giả định class này có tên là cReview
class cReview { 
    protected $reviewModel;

    public function __construct() {
        // Đảm bảo tên class Model là Review (đúng như bạn đã cung cấp)
        $this->reviewModel = new Review();
    }

    /**
     * Lấy danh sách đánh giá có lọc, tìm kiếm và join (Dùng cho Admin View)
     * Đây là hàm chính để hiển thị bảng đánh giá.
     */
    public function getReviews($search = '', $status = -1) {
        // Gọi hàm getFilteredReviews đã được thêm vào Model
        return $this->reviewModel->getFilteredReviews($search, $status);
    }
    
    /**
     * Đếm tổng số đánh giá (Dùng cho thống kê)
     */
    public function countTotalReviews() {
        // Gọi hàm countReviews đã được thêm vào Model
        return $this->reviewModel->countReviews();
    }
    
    /**
     * Đếm số lượng đánh giá theo trạng thái (Dùng để hiển thị badge "Chờ duyệt")
     */
    public function countReviewsByStatus($status) {
        // Gọi hàm countByStatus đã có trong Model
        return $this->reviewModel->countByStatus($status);
    }

    /**
     * Toggle ẩn/hiện đánh giá (Admin Action)
     */
    public function toggleVisibility($reviewID) {
        if ($this->reviewModel->toggleVisibility($reviewID)) {
            return ['success' => true, 'message' => "Đã thay đổi hiển thị đánh giá thành công."];
        }
        return ['success' => false, 'message' => "Lỗi khi thay đổi hiển thị đánh giá."];
    }

    /**
     * Xóa đánh giá (Admin Action)
     */
    public function deleteReview($reviewID) {
        if ($this->reviewModel->deleteReview($reviewID)) {
            return ['success' => true, 'message' => "Đã xóa đánh giá thành công."];
        } else {
            return ['success' => false, 'message' => "Lỗi khi xóa đánh giá."];
        }
    }
}