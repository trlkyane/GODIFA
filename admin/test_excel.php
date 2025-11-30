<?php
/**
 * Test file - Kiểm tra xuất Excel đơn giản
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Tạo spreadsheet mới
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Thêm dữ liệu đơn giản
$sheet->setCellValue('A1', 'Test Excel');
$sheet->setCellValue('A2', 'Hello World');
$sheet->setCellValue('B2', '123456');

// Xuất file
$filename = 'test_' . date('YmdHis') . '.xlsx';

// Clear buffer
while (ob_get_level()) {
    ob_end_clean();
}

// Set headers
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Tạo writer và xuất
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
