<?php
/**
 * Payment Model
 * File: model/mPayment.php
 * Xử lý dữ liệu thanh toán (SePay)
 */

require_once __DIR__ . '/database.php';

class Payment {
    private $conn;
    
    public function __construct() {
        $db = new clsKetNoi();
        $this->conn = $db->moKetNoi();
    }
    
    /**
     * Tạo payment record mới
     */
    public function createPayment($data) {
        $sql = "INSERT INTO payment (
            orderID, paymentMethod, amount, transactionCode, 
            bankCode, accountNumber, qrUrl, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param(
            $stmt, 
            "isdsssss",
            $data['orderID'],
            $data['paymentMethod'],
            $data['amount'],
            $data['transactionCode'],
            $data['bankCode'],
            $data['accountNumber'],
            $data['qrUrl'],
            $data['status']
        );
        
        $result = mysqli_stmt_execute($stmt);
        
        if ($result) {
            return mysqli_insert_id($this->conn);
        }
        
        return false;
    }
    
    /**
     * Lấy payment theo transaction code
     */
    public function getPaymentByTransactionCode($transactionCode) {
        $sql = "SELECT * FROM payment WHERE transactionCode = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $transactionCode);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    /**
     * Lấy payment theo orderID
     */
    public function getPaymentByOrderID($orderID) {
        $sql = "SELECT * FROM payment WHERE orderID = ? ORDER BY createdAt DESC LIMIT 1";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $orderID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    /**
     * Cập nhật trạng thái thanh toán
     */
    public function updatePaymentStatus($transactionCode, $status, $transactionID = null, $metadata = null) {
        $sql = "UPDATE payment 
                SET status = ?, 
                    transactionID = ?,
                    metadata = ?,
                    paidAt = IF(? = 'paid' AND paidAt IS NULL, NOW(), paidAt),
                    updatedAt = NOW()
                WHERE transactionCode = ?";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        $metadataJson = $metadata ? json_encode($metadata) : null;
        
        mysqli_stmt_bind_param(
            $stmt,
            "sssss",
            $status,
            $transactionID,
            $metadataJson,
            $status,
            $transactionCode
        );
        
        return mysqli_stmt_execute($stmt);
    }
    
    /**
     * Kiểm tra payment đã thanh toán chưa
     */
    public function isPaymentPaid($orderID) {
        $sql = "SELECT status FROM payment WHERE orderID = ? AND status = 'paid' LIMIT 1";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $orderID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_num_rows($result) > 0;
    }
    
    /**
     * Tạo mã giao dịch unique
     */
    public function generateTransactionCode($orderID) {
        return "GODIFA" . str_pad($orderID, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Tạo QR code URL (VietQR)
     */
    public function generateQRCodeURL($amount, $content, $bankCode, $accountNumber) {
        $config = include __DIR__ . '/../config/sepay.php';
        
        $template = $config['qr_template'] ?? 'compact2';
        $qrUrl = "https://img.vietqr.io/image/{$bankCode}-{$accountNumber}-{$template}.png";
        $qrUrl .= "?amount={$amount}";
        $qrUrl .= "&addInfo=" . urlencode($content);
        $qrUrl .= "&accountName=" . urlencode($config['account_name']);
        
        return $qrUrl;
    }
}
