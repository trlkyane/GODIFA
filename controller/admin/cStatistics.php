<?php
/**
 * Controller: Admin Statistics
 * Xá»­ lÃ½ logic thá»‘ng kÃª vÃ  bÃ¡o cÃ¡o
 */

require_once __DIR__ . '/../../model/mStatistics.php';

class cStatistics {
    private $model;
    
    public function __construct() {
        $this->model = new Statistics();
    }
    
    /**
     * Láº¥y táº¥t cáº£ dá»¯ liá»‡u thá»‘ng kÃª
     */
    public function getAllStatistics() {
        $period = $_GET['period'] ?? 'month';
        $originalPeriod = $period; // LÆ°u láº¡i period gá»‘c Ä‘á»ƒ hiá»ƒn thá»‹
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;

        // Há»— trá»£ cÃ¡c period má»›i - chuyá»ƒn thÃ nh custom cho xá»­ lÃ½ dá»¯ liá»‡u
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
            'period' => $originalPeriod, // Tráº£ vá» period gá»‘c Ä‘á»ƒ hiá»ƒn thá»‹ Ä‘Ãºng
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
    }
    
    /**
     * Láº¥y label hiá»ƒn thá»‹ theo period
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
        $icon = $growth >= 0 ? 'â†‘' : 'â†“';
        
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
