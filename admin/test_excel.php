<?php
/**
 * Test file - Kiá»ƒm tra xuáº¥t Excel Ä‘Æ¡n giáº£n
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Táº¡o spreadsheet má»›i
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// ThÃªm dá»¯ liá»‡u Ä‘Æ¡n giáº£n
$sheet->setCellValue('A1', 'Test Excel');
$sheet->setCellValue('A2', 'Hello World');
$sheet->setCellValue('B2', '123456');

// Xuáº¥t file
$filename = 'test_' . date('YmdHis') . '.xlsx';

// Clear buffer
while (ob_get_level()) {
    ob_end_clean();
}

// Set headers
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Táº¡o writer vÃ  xuáº¥t
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
