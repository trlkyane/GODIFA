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
        $originalPeriod = $period; // Lưu lại period gốc để hiển thị
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;

        // Hỗ trợ các period mới - chuyển thành custom cho xử lý dữ liệu
        $periodForQuery = $period;
        if ($period == '7days') {
            $startDate = date('Y-m-d', strtotime('-6 days'));
            $endDate = date('Y-m-d');
            $periodForQuery = 'custom';
        } elseif ($period == '30days') {
            $startDate = date('Y-m-d', strtotime('-29 days'));
            $endDate = date('Y-m-d');
            $periodForQuery = 'custom';
        } elseif ($period == 'lastmonth') {
            $startDate = date('Y-m-01', strtotime('-1 month'));
            $endDate = date('Y-m-t', strtotime('-1 month'));
            $periodForQuery = 'custom';
        }

        // Validate period
        $validPeriods = ['day', 'week', 'month', 'year', 'custom', '7days', '30days', 'lastmonth'];
        if (!in_array($period, $validPeriods)) {
            $period = 'month';
            $originalPeriod = 'month';
        }

        // Validate dates for custom period
        if ($periodForQuery == 'custom') {
            if (!$startDate || !$endDate) {
                $startDate = date('Y-m-01');
                $endDate = date('Y-m-d');
            }
        }
        
        return [
            'revenue' => $this->model->getRevenueStats($periodForQuery, $startDate, $endDate),
            'orders' => $this->model->getOrderStats($periodForQuery, $startDate, $endDate),
            'topProducts' => $this->model->getTopProducts($periodForQuery, 5, $startDate, $endDate),
            'paymentMethods' => $this->model->getPaymentMethods($periodForQuery, $startDate, $endDate),
            'chartData' => $this->model->getRevenueChartData($periodForQuery, $startDate, $endDate),
            'period' => $originalPeriod, // Trả về period gốc để hiển thị đúng
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
            '7days' => '7 ngày trước',
            '30days' => '30 ngày trước',
            'month' => 'Tháng này',
            'lastmonth' => 'Tháng trước',
            'week' => 'Tuần này',
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
