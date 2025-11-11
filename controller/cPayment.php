<?php
/**
 * Payment Controller
 * File: controller/cPayment.php
 * Xử lý thanh toán SePay QR
 */

require_once __DIR__ . '/../model/mPayment.php';
require_once __DIR__ . '/../model/mOrder.php';

class cPayment {
    private $paymentModel;
    private $orderModel;
    private $config;
    
    public function __construct() {
        $this->paymentModel = new Payment();
        $this->orderModel = new Order();
        $this->config = include __DIR__ . '/../config/sepay.php';
    }
    
    /**
     * Tạo thanh toán QR cho đơn hàng
     */
    public function createQRPayment($orderID) {
        try {
            // Lấy thông tin đơn hàng
            $order = $this->orderModel->getOrderById($orderID);
            
            if (!$order) {
                return [
                    'success' => false,
                    'error' => 'Không tìm thấy đơn hàng'
                ];
            }
            
            // Kiểm tra đơn đã thanh toán chưa
            if ($this->paymentModel->isPaymentPaid($orderID)) {
                return [
                    'success' => false,
                    'error' => 'Đơn hàng đã được thanh toán'
                ];
            }
            
            // Tạo mã giao dịch unique
            $transactionCode = $this->paymentModel->generateTransactionCode($orderID);
            
            // Tạo nội dung chuyển khoản
            $content = str_replace('{orderID}', str_pad($orderID, 6, '0', STR_PAD_LEFT), $this->config['transfer_template']);
            
            // Tạo QR code URL
            $qrUrl = $this->paymentModel->generateQRCodeURL(
                $order['totalAmount'],
                $content,
                $this->config['bank_code'],
                $this->config['account_number']
            );
            
            // Lưu thông tin thanh toán
            $paymentData = [
                'orderID' => $orderID,
                'paymentMethod' => 'SePay QR',
                'amount' => $order['totalAmount'],
                'transactionCode' => $transactionCode,
                'bankCode' => $this->config['bank_code'],
                'accountNumber' => $this->config['account_number'],
                'qrUrl' => $qrUrl,
                'status' => 'pending'
            ];
            
            $paymentID = $this->paymentModel->createPayment($paymentData);
            
            if (!$paymentID) {
                return [
                    'success' => false,
                    'error' => 'Không thể tạo thanh toán'
                ];
            }
            
            return [
                'success' => true,
                'data' => [
                    'paymentID' => $paymentID,
                    'orderID' => $orderID,
                    'transactionCode' => $transactionCode,
                    'qrUrl' => $qrUrl,
                    'amount' => $order['totalAmount'],
                    'bankCode' => $this->config['bank_code'],
                    'accountNumber' => $this->config['account_number'],
                    'accountName' => $this->config['account_name'],
                    'content' => $content
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Create QR Payment Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Lỗi hệ thống: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Kiểm tra trạng thái thanh toán
     */
    public function checkPaymentStatus($orderID) {
        $payment = $this->paymentModel->getPaymentByOrderID($orderID);
        
        if (!$payment) {
            return [
                'success' => false,
                'status' => 'not_found'
            ];
        }
        
        return [
            'success' => true,
            'status' => $payment['status'],
            'paidAt' => $payment['paidAt'],
            'transactionID' => $payment['transactionID']
        ];
    }
    
    /**
     * Xử lý webhook từ SePay (gọi từ webhook/sepay.php)
     */
    public function processWebhook($webhookData) {
        try {
            // Lấy thông tin từ webhook
            $transactionCode = $webhookData['transactionCode'] ?? null;
            $amount = $webhookData['amount'] ?? 0;
            $status = $webhookData['status'] ?? 'failed';
            $transactionID = $webhookData['transactionID'] ?? null;
            
            if (!$transactionCode) {
                return [
                    'success' => false,
                    'error' => 'Missing transaction code'
                ];
            }
            
            // Lấy payment từ DB
            $payment = $this->paymentModel->getPaymentByTransactionCode($transactionCode);
            
            if (!$payment) {
                return [
                    'success' => false,
                    'error' => 'Payment not found'
                ];
            }
            
            // Kiểm tra đã xử lý chưa
            if ($payment['status'] === 'paid') {
                return [
                    'success' => true,
                    'message' => 'Already processed'
                ];
            }
            
            // Kiểm tra số tiền
            if ($amount < $payment['amount']) {
                return [
                    'success' => false,
                    'error' => 'Invalid amount'
                ];
            }
            
            // Cập nhật payment status
            $this->paymentModel->updatePaymentStatus(
                $transactionCode,
                $status === 'success' ? 'paid' : 'failed',
                $transactionID,
                $webhookData
            );
            
            // Nếu thanh toán thành công, cập nhật đơn hàng
            if ($status === 'success') {
                $this->orderModel->updatePaymentStatus($payment['orderID'], 'Đã thanh toán');
                
                // Gửi email xác nhận (nếu có)
                // $this->sendConfirmationEmail($payment['orderID']);
            }
            
            return [
                'success' => true,
                'message' => 'Payment processed successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Process Webhook Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
