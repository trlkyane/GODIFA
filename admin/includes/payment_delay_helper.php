<?php
/**
 * Helper: Format Payment Delay
 * Hiển thị thời gian chênh lệch giữa orderDate và paymentDate
 */

function formatPaymentDelay($minutes) {
    if ($minutes === null || $minutes <= 0) {
        return null;
    }
    
    $hours = floor($minutes / 60);
    $days = floor($hours / 24);
    $remainingHours = $hours % 24;
    $remainingMinutes = $minutes % 60;
    
    if ($days > 0) {
        return $days . ' ngày ' . $remainingHours . 'h';
    } elseif ($hours > 0) {
        return $hours . 'h ' . $remainingMinutes . 'p';
    } else {
        return $minutes . ' phút';
    }
}

function getPaymentDelayBadge($minutes, $isLatePayment) {
    if ($minutes === null || $minutes <= 0) {
        return ''; // Chưa thanh toán hoặc thanh toán ngay
    }
    
    $formattedTime = formatPaymentDelay($minutes);
    
    if ($isLatePayment) {
        // Thanh toán trễ (>= 1 giờ)
        return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800" title="Thanh toán sau ' . $formattedTime . '">
                    <i class="fas fa-clock mr-1"></i> +' . $formattedTime . '
                </span>';
    } else {
        // Thanh toán nhanh (< 1 giờ)
        return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800" title="Thanh toán sau ' . $formattedTime . '">
                    <i class="fas fa-check-circle mr-1"></i> ' . $formattedTime . '
                </span>';
    }
}

function getPaymentTimeline($orderDate, $paymentDate) {
    if (!$paymentDate) {
        return null;
    }
    
    return [
        'orderDate' => date('d/m/Y H:i', strtotime($orderDate)),
        'paymentDate' => date('d/m/Y H:i', strtotime($paymentDate)),
        'orderTimestamp' => strtotime($orderDate),
        'paymentTimestamp' => strtotime($paymentDate),
        'delaySeconds' => strtotime($paymentDate) - strtotime($orderDate)
    ];
}
