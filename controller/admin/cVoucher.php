<?php
/**
 * Voucher Controller - Admin
 * File: controller/cVoucher.php
 */

require_once __DIR__ . '/../../model/mVoucher.php';

class cVoucher {
    private $voucherModel;
    
    public function __construct() {
        $this->voucherModel = new Voucher();
    }
    
    // Lấy tất cả vouchers với filter
    public function getAllVouchers($filterStatus = 'all') {
        $vouchers = $this->voucherModel->getAllVouchers();
        
        if ($filterStatus === 'all') {
            return $vouchers;
        }
        
        return $this->filterVouchersByStatus($vouchers, $filterStatus);
    }
    
    // Tìm kiếm vouchers
    public function searchVouchers($keyword, $filterStatus = 'all') {
        $vouchers = $this->voucherModel->searchVouchers($keyword);
        
        if ($filterStatus === 'all') {
            return $vouchers;
        }
        
        return $this->filterVouchersByStatus($vouchers, $filterStatus);
    }
    
    // Filter vouchers theo status
    private function filterVouchersByStatus($vouchers, $status) {
        $today = date('Y-m-d');
        $filtered = [];
        
        foreach ($vouchers as $voucher) {
            $match = false;
            
            switch ($status) {
                case 'active':
                    // Đang hoạt động: trong thời hạn, còn số lượng, không bị khóa
                    $match = ($voucher['status'] == 1) &&
                             ($voucher['startDate'] <= $today) &&
                             ($voucher['endDate'] >= $today) &&
                             ($voucher['quantity'] > 0);
                    break;
                    
                case 'expired':
                    // Hết hạn
                    $match = ($voucher['endDate'] < $today);
                    break;
                    
                case 'out_of_stock':
                    // Hết số lượng
                    $match = ($voucher['quantity'] <= 0);
                    break;
                    
                case 'locked':
                    // Bị khóa
                    $match = ($voucher['status'] == 0);
                    break;
            }
            
            if ($match) {
                $filtered[] = $voucher;
            }
        }
        
        return $filtered;
    }
    
    // Lấy vouchers đang hoạt động
    public function getActiveVouchers() {
        return $this->voucherModel->getActiveVouchers();
    }
    
    // Lấy voucher theo ID
    public function getVoucherById($id) {
        return $this->voucherModel->getVoucherById($id);
    }
    
    // Thêm voucher
    public function addVoucher($data) {
        // Validate
        $errors = [];
        
        if (empty($data['voucherName'])) {
            $errors[] = "Vui lòng nhập tên voucher!";
        }
        
        if (!isset($data['value']) || $data['value'] <= 0) {
            $errors[] = "Giá trị voucher phải lớn hơn 0!";
        }
        
        if (!isset($data['quantity']) || $data['quantity'] <= 0) {
            $errors[] = "Số lượng phải lớn hơn 0!";
        }
        
        if (strtotime($data['endDate']) < strtotime($data['startDate'])) {
            $errors[] = "Ngày kết thúc phải sau ngày bắt đầu!";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Thêm voucher
        $result = $this->voucherModel->addVoucher(
            $data['voucherName'],
            $data['value'],
            $data['quantity'],
            $data['startDate'],
            $data['endDate'],
            $data['requirement'] ?? ''
        );
        
        if ($result) {
            // Lấy ID voucher vừa tạo
            $voucherID = mysqli_insert_id($this->voucherModel->getConnection());
            return ['success' => true, 'message' => 'Thêm voucher thành công!', 'voucherID' => $voucherID];
        }
        
        return ['success' => false, 'errors' => ['Lỗi khi thêm voucher!']];
    }
    
    // Cập nhật voucher
    public function updateVoucher($id, $data) {
        // Validate
        $errors = [];
        
        if (empty($data['voucherName'])) {
            $errors[] = "Vui lòng nhập tên voucher!";
        }
        
        if (!isset($data['value']) || $data['value'] <= 0) {
            $errors[] = "Giá trị voucher phải lớn hơn 0!";
        }
        
        if (!isset($data['quantity']) || $data['quantity'] < 0) {
            $errors[] = "Số lượng không được âm!";
        }
        
        if (strtotime($data['endDate']) < strtotime($data['startDate'])) {
            $errors[] = "Ngày kết thúc phải sau ngày bắt đầu!";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Cập nhật voucher
        $result = $this->voucherModel->updateVoucher(
            $id,
            $data['voucherName'],
            $data['value'],
            $data['quantity'],
            $data['startDate'],
            $data['endDate'],
            $data['requirement'] ?? ''
        );
        
        if ($result) {
            return ['success' => true, 'message' => 'Cập nhật voucher thành công!'];
        }
        
        return ['success' => false, 'errors' => ['Lỗi khi cập nhật voucher!']];
    }
    
    // Xóa voucher
    public function deleteVoucher($id) {
        $result = $this->voucherModel->deleteVoucher($id);
        
        if ($result) {
            return ['success' => true, 'message' => 'Xóa voucher thành công!'];
        }
        
        return ['success' => false, 'errors' => ['Lỗi khi xóa voucher!']];
    }
    
    // Toggle trạng thái voucher (khóa/mở khóa)
    public function toggleStatus($id) {
        $result = $this->voucherModel->toggleStatus($id);
        
        if ($result) {
            return ['success' => true, 'message' => 'Cập nhật trạng thái thành công!'];
        }
        
        return ['success' => false, 'errors' => ['Lỗi khi cập nhật trạng thái!']];
    }
    
    // Kiểm tra voucher có hiệu lực
    public function isVoucherValid($id) {
        return $this->voucherModel->isVoucherValid($id);
    }
    
    // Sử dụng voucher (giảm số lượng)
    public function useVoucher($id) {
        if (!$this->isVoucherValid($id)) {
            return ['success' => false, 'errors' => ['Voucher không còn hiệu lực!']];
        }
        
        $result = $this->voucherModel->useVoucher($id);
        
        if ($result) {
            return ['success' => true, 'message' => 'Áp dụng voucher thành công!'];
        }
        
        return ['success' => false, 'errors' => ['Lỗi khi áp dụng voucher!']];
    }
    
    // Thống kê voucher
    public function getVoucherStats() {
        $allVouchers = $this->getAllVouchers();
        $today = date('Y-m-d');
        
        $stats = [
            'total' => count($allVouchers),
            'active' => 0,
            'expired' => 0,
            'upcoming' => 0,
            'outOfStock' => 0
        ];
        
        foreach ($allVouchers as $voucher) {
            if ($voucher['quantity'] <= 0) {
                $stats['outOfStock']++;
            } elseif ($voucher['endDate'] < $today) {
                $stats['expired']++;
            } elseif ($voucher['startDate'] > $today) {
                $stats['upcoming']++;
            } else {
                $stats['active']++;
            }
        }
        
        return $stats;
    }
}
?>
