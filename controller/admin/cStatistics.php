<?php
/**
 * Controller: Admin Statistics
 * Xử lý logic thống kê và báo cáo
 */

require_once __DIR__ . '/../../model/mStatistics.php';

class cStatistics {
    private $model;
    
    public function __construct() {
        $this->model = new Statistics();
    }
    
    /**
     * Lấy tất cả dữ liệu thống kê
     */
    public function getAllStatistics() {
        $period = $_GET['period'] ?? 'month';
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        // Validate period
        $validPeriods = ['day', 'week', 'month', 'year', 'custom'];
        if (!in_array($period, $validPeriods)) {
            $period = 'month';
        }
        
        // Validate dates for custom period
        if ($period == 'custom') {
            if (!$startDate || !$endDate) {
                $startDate = date('Y-m-01');
                $endDate = date('Y-m-d');
            }
        }
        
        return [
            'revenue' => $this->model->getRevenueStats($period, $startDate, $endDate),
            'orders' => $this->model->getOrderStats($period, $startDate, $endDate),
            'topProducts' => $this->model->getTopProducts($period, 5, $startDate, $endDate),
            'paymentMethods' => $this->model->getPaymentMethods($period, $startDate, $endDate),
            'chartData' => $this->model->getRevenueChartData(30),
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
    }
    
    /**
     * Lấy label hiển thị theo period
     */
    public function getPeriodLabel($period) {
        $labels = [
            'day' => 'Hôm nay',
            'week' => 'Tuần này',
            'month' => 'Tháng này',
            'year' => 'Năm nay',
            'custom' => 'Tùy chỉnh'
        ];
        
        return $labels[$period] ?? 'Tháng này';
    }
    
    /**
     * Format số tiền (VNĐ)
     */
    public function formatCurrency($amount) {
        return number_format($amount, 0, ',', '.') . ' đ';
    }
    
    /**
     * Format phần trăm tăng trưởng
     */
    public function formatGrowth($growth) {
        $sign = $growth >= 0 ? '+' : '';
        $color = $growth >= 0 ? 'text-green-600' : 'text-red-600';
        $icon = $growth >= 0 ? '↑' : '↓';
        
        return [
            'text' => $sign . number_format($growth, 1) . '%',
            'color' => $color,
            'icon' => $icon
        ];
    }
    
    /**
     * Export dữ liệu thống kê (CSV, Excel - TODO)
     */
    public function exportStatistics($format = 'csv') {
        // TODO: Implement export functionality
        return false;
    }
}
