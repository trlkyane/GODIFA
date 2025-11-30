<?php
/**
 * Export Statistics to Excel
 * Xuất báo cáo thống kê ra file Excel
 */

// Bật output buffering ngay từ đầu để tránh output không mong muốn
ob_start();

// Load auth và constants
require_once __DIR__ . '/middleware/auth.php';
require_once __DIR__ . '/../config/constants.php';

// Start session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_ADMIN);
    session_start();
}

// Check admin login
requireLogin();

// Check permission
if (!hasPermission('view_statistics')) {
    ob_end_clean();
    die('Permission denied - Bạn không có quyền xem thống kê');
}

// Load required files
require_once __DIR__ . '/../controller/admin/cStatistics.php';
require_once __DIR__ . '/../vendor/autoload.php'; // PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Get statistics data
$controller = new cStatistics();
$data = $controller->getAllStatistics();

$revenueStats = $data['revenue'];
$orderStats = $data['orders'];
$topProducts = $data['topProducts'];
$paymentMethods = $data['paymentMethods'];
$chartData = $data['chartData'];
$period = $data['period'];
$startDate = $data['startDate'];
$endDate = $data['endDate'];

$periodLabel = $controller->getPeriodLabel($period);

// Create new Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set document properties
$spreadsheet->getProperties()
    ->setCreator("GODIFA Admin")
    ->setTitle("Báo cáo thống kê - " . $periodLabel)
    ->setSubject("Thống kê doanh thu và đơn hàng")
    ->setDescription("Báo cáo thống kê tự động được tạo từ hệ thống GODIFA");

// ===== HEADER SECTION =====
$sheet->setCellValue('A1', 'BÁO CÁO THỐNG KÊ DOANH THU');
$sheet->mergeCells('A1:F1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A2', 'Kỳ báo cáo: ' . $periodLabel);
$sheet->mergeCells('A2:F2');
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

if ($period == 'custom' && $startDate && $endDate) {
    $sheet->setCellValue('A3', 'Từ ngày: ' . date('d/m/Y', strtotime($startDate)) . ' - Đến ngày: ' . date('d/m/Y', strtotime($endDate)));
    $sheet->mergeCells('A3:F3');
    $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $currentRow = 5;
} else {
    $currentRow = 4;
}

$sheet->setCellValue('A' . $currentRow, 'Ngày xuất: ' . date('d/m/Y H:i:s'));
$sheet->mergeCells('A' . $currentRow . ':F' . $currentRow);
$sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$currentRow += 2;

// ===== REVENUE SUMMARY =====
$sheet->setCellValue('A' . $currentRow, 'TỔNG QUAN DOANH THU');
$sheet->mergeCells('A' . $currentRow . ':B' . $currentRow);
$sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A' . $currentRow)->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FF4472C4');
$sheet->getStyle('A' . $currentRow)->getFont()->getColor()->setARGB('FFFFFFFF');
$currentRow++;

$sheet->setCellValue('A' . $currentRow, 'Tổng doanh thu:');
$sheet->setCellValue('B' . $currentRow, number_format($revenueStats['total'], 0, ',', '.') . ' đ');
$currentRow++;

$sheet->setCellValue('A' . $currentRow, 'Đã thanh toán:');
$sheet->setCellValue('B' . $currentRow, number_format($revenueStats['completed'], 0, ',', '.') . ' đ');
$currentRow++;

$sheet->setCellValue('A' . $currentRow, 'Chờ thanh toán:');
$sheet->setCellValue('B' . $currentRow, number_format($revenueStats['pending'], 0, ',', '.') . ' đ');
$currentRow++;

$growth = $revenueStats['growth'];
$sheet->setCellValue('A' . $currentRow, 'Tăng trưởng:');
$sheet->setCellValue('B' . $currentRow, ($growth >= 0 ? '+' : '') . number_format($growth, 1) . '%');
$sheet->getStyle('B' . $currentRow)->getFont()->getColor()->setARGB($growth >= 0 ? 'FF00B050' : 'FFFF0000');
$currentRow += 2;

// ===== ORDER SUMMARY =====
$sheet->setCellValue('A' . $currentRow, 'TỔNG QUAN ĐỐN HÀNG');
$sheet->mergeCells('A' . $currentRow . ':B' . $currentRow);
$sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A' . $currentRow)->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FF70AD47');
$sheet->getStyle('A' . $currentRow)->getFont()->getColor()->setARGB('FFFFFFFF');
$currentRow++;

$sheet->setCellValue('A' . $currentRow, 'Tổng đơn hàng:');
$sheet->setCellValue('B' . $currentRow, number_format($orderStats['total']));
$currentRow++;

$sheet->setCellValue('A' . $currentRow, 'Hoàn thành:');
$sheet->setCellValue('B' . $currentRow, number_format($orderStats['completed']));
$currentRow++;

$sheet->setCellValue('A' . $currentRow, 'Đang xử lý:');
$sheet->setCellValue('B' . $currentRow, number_format($orderStats['processing']));
$currentRow++;

$sheet->setCellValue('A' . $currentRow, 'Chờ xử lý:');
$sheet->setCellValue('B' . $currentRow, number_format($orderStats['pending']));
$currentRow++;

$sheet->setCellValue('A' . $currentRow, 'Đã hủy:');
$sheet->setCellValue('B' . $currentRow, number_format($orderStats['cancelled']));
$currentRow += 2;

// ===== TOP PRODUCTS =====
$sheet->setCellValue('A' . $currentRow, 'TOP 5 SẢN PHẨM BÁN CHẠY');
$sheet->mergeCells('A' . $currentRow . ':D' . $currentRow);
$sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A' . $currentRow)->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FFFFC000');
$sheet->getStyle('A' . $currentRow)->getFont()->getColor()->setARGB('FFFFFFFF');
$currentRow++;

// Header
$sheet->setCellValue('A' . $currentRow, 'Hạng');
$sheet->setCellValue('B' . $currentRow, 'Tên sản phẩm');
$sheet->setCellValue('C' . $currentRow, 'Số lượng bán');
$sheet->setCellValue('D' . $currentRow, 'Doanh thu');
$sheet->getStyle('A' . $currentRow . ':D' . $currentRow)->getFont()->setBold(true);
$sheet->getStyle('A' . $currentRow . ':D' . $currentRow)->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FFE7E6E6');
$currentRow++;

if (!empty($topProducts)) {
    foreach ($topProducts as $index => $product) {
        $sheet->setCellValue('A' . $currentRow, $index + 1);
        $sheet->setCellValue('B' . $currentRow, $product['productName']);
        $sheet->setCellValue('C' . $currentRow, number_format($product['totalSold']));
        $sheet->setCellValue('D' . $currentRow, number_format($product['revenue'], 0, ',', '.') . ' đ');
        $currentRow++;
    }
} else {
    $sheet->setCellValue('A' . $currentRow, 'Chưa có dữ liệu');
    $sheet->mergeCells('A' . $currentRow . ':D' . $currentRow);
    $currentRow++;
}
$currentRow++;

// ===== PAYMENT METHODS =====
$sheet->setCellValue('A' . $currentRow, 'PHƯƠNG THỨC THANH TOÁN');
$sheet->mergeCells('A' . $currentRow . ':D' . $currentRow);
$sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A' . $currentRow)->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FF9933FF');
$sheet->getStyle('A' . $currentRow)->getFont()->getColor()->setARGB('FFFFFFFF');
$currentRow++;

// Header
$sheet->setCellValue('A' . $currentRow, 'Phương thức');
$sheet->setCellValue('B' . $currentRow, 'Số đơn hàng');
$sheet->setCellValue('C' . $currentRow, 'Doanh thu');
$sheet->setCellValue('D' . $currentRow, 'Tỷ lệ');
$sheet->getStyle('A' . $currentRow . ':D' . $currentRow)->getFont()->setBold(true);
$sheet->getStyle('A' . $currentRow . ':D' . $currentRow)->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FFE7E6E6');
$currentRow++;

if (!empty($paymentMethods)) {
    $totalRevenue = array_sum(array_column($paymentMethods, 'revenue'));
    foreach ($paymentMethods as $method) {
        $percentage = $totalRevenue > 0 ? ($method['revenue'] / $totalRevenue) * 100 : 0;
        $sheet->setCellValue('A' . $currentRow, $method['paymentMethod'] ?: 'Không xác định');
        $sheet->setCellValue('B' . $currentRow, number_format($method['orderCount']));
        $sheet->setCellValue('C' . $currentRow, number_format($method['revenue'], 0, ',', '.') . ' đ');
        $sheet->setCellValue('D' . $currentRow, number_format($percentage, 1) . '%');
        $currentRow++;
    }
} else {
    $sheet->setCellValue('A' . $currentRow, 'Chưa có dữ liệu');
    $sheet->mergeCells('A' . $currentRow . ':D' . $currentRow);
    $currentRow++;
}
$currentRow++;

// ===== CHART DATA =====
$sheet->setCellValue('A' . $currentRow, 'DỮ LIỆU BIỂU ĐỒ DOANH THU');
$sheet->mergeCells('A' . $currentRow . ':B' . $currentRow);
$sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A' . $currentRow)->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FF5B9BD5');
$sheet->getStyle('A' . $currentRow)->getFont()->getColor()->setARGB('FFFFFFFF');
$currentRow++;

// Header
$sheet->setCellValue('A' . $currentRow, 'Ngày');
$sheet->setCellValue('B' . $currentRow, 'Doanh thu (VNĐ)');
$sheet->getStyle('A' . $currentRow . ':B' . $currentRow)->getFont()->setBold(true);
$sheet->getStyle('A' . $currentRow . ':B' . $currentRow)->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FFE7E6E6');
$currentRow++;

if (!empty($chartData)) {
    foreach ($chartData as $data) {
        $sheet->setCellValue('A' . $currentRow, $data['date']);
        $sheet->setCellValue('B' . $currentRow, number_format($data['revenue'], 0, ',', '.') . ' đ');
        $currentRow++;
    }
} else {
    $sheet->setCellValue('A' . $currentRow, 'Chưa có dữ liệu');
    $sheet->mergeCells('A' . $currentRow . ':B' . $currentRow);
}

// ===== STYLING & AUTO-SIZE =====
// Auto size columns
foreach (range('A', 'D') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Add borders to all used cells
$highestRow = $sheet->getHighestRow();
$highestColumn = $sheet->getHighestColumn();
$sheet->getStyle('A1:' . $highestColumn . $highestRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// ===== EXPORT FILE =====
$filename = 'Bao_cao_thong_ke_' . date('Y-m-d_His') . '.xlsx';

// Xóa tất cả output buffer trước khi xuất file
while (ob_get_level()) {
    ob_end_clean();
}

// Tạo file tạm
$tempFile = sys_get_temp_dir() . '/' . $filename;

// Tạo Writer và lưu vào file tạm
$writer = new Xlsx($spreadsheet);
$writer->save($tempFile);

// Set headers cho file Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Content-Length: ' . filesize($tempFile));
header('Cache-Control: max-age=0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Đọc file và xuất ra
readfile($tempFile);

// Xóa file tạm
@unlink($tempFile);

exit;
