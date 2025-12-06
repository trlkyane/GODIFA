<?php
// File: controller/admin/cReview.php

// ðŸŒŸ Äáº£m báº£o Ä‘Æ°á»ng dáº«n Model chÃ­nh xÃ¡c: LÃ¹i 2 cáº¥p tá»« controller/admin/ Ä‘áº¿n model/
require_once __DIR__ . '/../../model/mReview.php'; 
// KHÃ”NG cáº§n include mCustomer vÃ  mProduct á»Ÿ Ä‘Ã¢y vÃ¬ logic JOIN Ä‘Ã£ náº±m trong Model

// Giáº£ Ä‘á»‹nh class nÃ y cÃ³ tÃªn lÃ  cReview
class cReview { 
    protected $reviewModel;

    public function __construct() {
        // Äáº£m báº£o tÃªn class Model lÃ  Review (Ä‘Ãºng nhÆ° báº¡n Ä‘Ã£ cung cáº¥p)
        $this->reviewModel = new Review();
    }

    /**
     * Láº¥y danh sÃ¡ch Ä‘Ã¡nh giÃ¡ cÃ³ lá»c, tÃ¬m kiáº¿m vÃ  join (DÃ¹ng cho Admin View)
     * ÄÃ¢y lÃ  hÃ m chÃ­nh Ä‘á»ƒ hiá»ƒn thá»‹ báº£ng Ä‘Ã¡nh giÃ¡.
     */
    public function getReviews($search = '', $status = -1) {
        // Gá»i hÃ m getFilteredReviews Ä‘Ã£ Ä‘Æ°á»£c thÃªm vÃ o Model
        return $this->reviewModel->getFilteredReviews($search, $status);
    }
    
    /**
     * Äáº¿m tá»•ng sá»‘ Ä‘Ã¡nh giÃ¡ (DÃ¹ng cho thá»‘ng kÃª)
     */
    public function countTotalReviews() {
        // Gá»i hÃ m countReviews Ä‘Ã£ Ä‘Æ°á»£c thÃªm vÃ o Model
        return $this->reviewModel->countReviews();
    }
    
    /**
     * Äáº¿m sá»‘ lÆ°á»£ng Ä‘Ã¡nh giÃ¡ theo tráº¡ng thÃ¡i (DÃ¹ng Ä‘á»ƒ hiá»ƒn thá»‹ badge "Chá» duyá»‡t")
     */
    public function countReviewsByStatus($status) {
        // Gá»i hÃ m countByStatus Ä‘Ã£ cÃ³ trong Model
        return $this->reviewModel->countByStatus($status);
    }

    /**
     * Toggle áº©n/hiá»‡n Ä‘Ã¡nh giÃ¡ (Admin Action)
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