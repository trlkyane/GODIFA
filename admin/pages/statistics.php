<?php
/**
 * View: Admin Statistics Page
 * Trang thống kê và báo cáo (theo mô hình MVC)
 */

// Check permission
if (!hasPermission('view_statistics')) {
    die('<div class="p-8"><div class="bg-red-100 text-red-700 p-4 rounded">Bạn không có quyền truy cập trang này!</div></div>');
}

// Load Controller
require_once __DIR__ . '/../../controller/admin/cStatistics.php';

// Initialize controller
$controller = new cStatistics();

// Get all statistics data
$data = $controller->getAllStatistics();

// Extract data
$revenueStats = $data['revenue'];
$orderStats = $data['orders'];
$topProducts = $data['topProducts'];
$paymentMethods = $data['paymentMethods'];
$chartData = $data['chartData'];
$period = $data['period'];
$startDate = $data['startDate'];
$endDate = $data['endDate'];

// Get period label
$periodLabel = $controller->getPeriodLabel($period);

// Set page title
$pageTitle = 'Thống kê & Báo cáo';

// Include layout
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Main Content -->
<div class="flex-1 ml-64 flex flex-col overflow-hidden">
    
    <!-- Top bar -->
    <header class="bg-white shadow-sm">
        <div class="px-4 py-4">
            <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-chart-line mr-2 text-blue-600"></i>Thống kê & Báo cáo
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">Kỳ: <?php echo $periodLabel; ?></p>
                </div>
                
                <!-- Period Filter -->
                <div class="flex flex-col sm:flex-row gap-2">
                    <select id="periodFilter" onchange="changePeriod(this.value)" 
                            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="day" <?php echo $period == 'day' ? 'selected' : ''; ?>>Hôm nay</option>
                        <option value="week" <?php echo $period == 'week' ? 'selected' : ''; ?>>Tuần này</option>
                        <option value="month" <?php echo $period == 'month' ? 'selected' : ''; ?>>Tháng này</option>
                        <option value="year" <?php echo $period == 'year' ? 'selected' : ''; ?>>Năm nay</option>
                        <option value="custom" <?php echo $period == 'custom' ? 'selected' : ''; ?>>Tùy chỉnh</option>
                    </select>
                    
                    <?php if ($period == 'custom'): ?>
                    <div class="flex gap-2 items-center">
                        <input type="date" id="startDate" value="<?php echo $startDate; ?>" 
                               class="px-3 py-2 border rounded-lg">
                        <span>đến</span>
                        <input type="date" id="endDate" value="<?php echo $endDate; ?>" 
                               class="px-3 py-2 border rounded-lg">
                        <button onclick="applyCustomDate()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-filter mr-1"></i>Áp dụng
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Main content area with scroll -->
    <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
        <div class="max-w-full mx-auto">
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            
            <!-- Total Revenue Card -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-4 text-white">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <p class="text-blue-100 text-xs font-medium">Tổng doanh thu</p>
                        <h3 class="text-xl font-bold mt-1"><?php echo $controller->formatCurrency($revenueStats['total']); ?></h3>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-2">
                        <i class="fas fa-dollar-sign text-lg"></i>
                    </div>
                </div>
                <?php $growth = $controller->formatGrowth($revenueStats['growth']); ?>
                <div class="flex items-center text-xs">
                    <span class="<?php echo str_replace('text-', 'text-white bg-', $growth['color']); ?> bg-opacity-30 px-2 py-1 rounded">
                        <?php echo $growth['icon'] . ' ' . $growth['text']; ?>
                    </span>
                    <span class="ml-2 text-blue-100">so với kỳ trước</span>
                </div>
            </div>

            <!-- Completed Revenue Card -->
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-4 text-white">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <p class="text-green-100 text-xs font-medium">Đã thanh toán</p>
                        <h3 class="text-xl font-bold mt-1"><?php echo $controller->formatCurrency($revenueStats['completed']); ?></h3>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-2">
                        <i class="fas fa-check-circle text-lg"></i>
                    </div>
                </div>
                <p class="text-green-100 text-xs">
                    <?php echo number_format(($revenueStats['total'] > 0 ? ($revenueStats['completed']/$revenueStats['total'])*100 : 0), 1); ?>% tổng doanh thu
                </p>
            </div>

            <!-- Pending Revenue Card -->
            <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg shadow-lg p-4 text-white">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <p class="text-yellow-100 text-xs font-medium">Chờ thanh toán</p>
                        <h3 class="text-xl font-bold mt-1"><?php echo $controller->formatCurrency($revenueStats['pending']); ?></h3>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-2">
                        <i class="fas fa-clock text-lg"></i>
                    </div>
                </div>
                <p class="text-yellow-100 text-xs">
                    <?php echo number_format(($revenueStats['total'] > 0 ? ($revenueStats['pending']/$revenueStats['total'])*100 : 0), 1); ?>% tổng doanh thu
                </p>
            </div>

            <!-- Total Orders Card -->
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-4 text-white">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <p class="text-purple-100 text-xs font-medium">Tổng đơn hàng</p>
                        <h3 class="text-xl font-bold mt-1"><?php echo number_format($orderStats['total']); ?></h3>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-2">
                        <i class="fas fa-shopping-cart text-lg"></i>
                    </div>
                </div>
                <div class="grid grid-cols-4 gap-1 text-xs">
                    <div class="text-center">
                        <div class="font-bold"><?php echo $orderStats['completed']; ?></div>
                        <div class="text-purple-100">Hoàn thành</div>
                    </div>
                    <div class="text-center">
                        <div class="font-bold"><?php echo $orderStats['processing']; ?></div>
                        <div class="text-purple-100">Đang xử lý</div>
                    </div>
                    <div class="text-center">
                        <div class="font-bold"><?php echo $orderStats['pending']; ?></div>
                        <div class="text-purple-100">Chờ xử lý</div>
                    </div>
                    <div class="text-center">
                        <div class="font-bold"><?php echo $orderStats['cancelled']; ?></div>
                        <div class="text-purple-100">Đã hủy</div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Chart Section -->
        <div class="bg-white rounded-lg shadow-lg p-4 mb-6">
            <h2 class="text-lg font-bold mb-3">
                <i class="fas fa-chart-area mr-2 text-blue-600"></i>Biểu đồ doanh thu (30 ngày gần nhất)
            </h2>
            <div class="w-full" style="height: 250px;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            
            <!-- Top Products -->
            <div class="bg-white rounded-lg shadow-lg p-4">
                <h2 class="text-lg font-bold mb-3">
                    <i class="fas fa-fire mr-2 text-orange-600"></i>Top 5 sản phẩm bán chạy
                </h2>
                
                <?php if (empty($topProducts)): ?>
                    <div class="text-center py-6 text-gray-500">
                        <i class="fas fa-box-open text-3xl mb-2"></i>
                        <p>Chưa có dữ liệu</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($topProducts as $index => $product): ?>
                        <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <div class="text-xl font-bold text-gray-400 w-6">
                                #<?php echo $index + 1; ?>
                            </div>
                            <img src="/GODIFA/image/<?php echo htmlspecialchars($product['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['productName']); ?>"
                                 class="w-12 h-12 object-cover rounded-lg"
                                 onerror="this.src='/GODIFA/image/no-image.png'">
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-sm text-gray-900 truncate"><?php echo htmlspecialchars($product['productName']); ?></h3>
                                <p class="text-xs text-gray-600">Đã bán: <?php echo number_format($product['totalSold']); ?> sản phẩm</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-sm text-green-600"><?php echo $controller->formatCurrency($product['revenue']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Payment Methods -->
            <div class="bg-white rounded-lg shadow-lg p-4">
                <h2 class="text-lg font-bold mb-3">
                    <i class="fas fa-credit-card mr-2 text-green-600"></i>Phương thức thanh toán
                </h2>
                
                <?php if (empty($paymentMethods)): ?>
                    <div class="text-center py-6 text-gray-500">
                        <i class="fas fa-wallet text-3xl mb-2"></i>
                        <p>Chưa có dữ liệu</p>
                    </div>
                <?php else: ?>
                    <?php 
                    $totalRevenue = array_sum(array_column($paymentMethods, 'revenue'));
                    $colors = ['bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-purple-500', 'bg-pink-500'];
                    ?>
                    <div class="space-y-3">
                        <?php foreach ($paymentMethods as $index => $method): ?>
                        <?php 
                        $percentage = $totalRevenue > 0 ? ($method['revenue'] / $totalRevenue) * 100 : 0;
                        $color = $colors[$index % count($colors)];
                        ?>
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="font-medium text-sm text-gray-700">
                                    <?php echo htmlspecialchars($method['paymentMethod'] ?: 'Không xác định'); ?>
                                </span>
                                <span class="text-xs text-gray-600">
                                    <?php echo number_format($method['orderCount']); ?> đơn
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-2 overflow-hidden">
                                    <div class="<?php echo $color; ?> h-full rounded-full transition-all" 
                                         style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <span class="text-xs font-semibold text-gray-900 w-20 text-right">
                                    <?php echo $controller->formatCurrency($method['revenue']); ?>
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1 text-right">
                                <?php echo number_format($percentage, 1); ?>%
                            </p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        </div><!-- End max-w-full -->
    </main>
</div>

<!-- JavaScript -->
<script>
        // Chart.js Configuration
        const chartData = <?php echo json_encode($chartData); ?>;
        const labels = chartData.map(item => item.date);
        const revenues = chartData.map(item => item.revenue);

        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: revenues,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(context.parsed.y) + ' đ';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('vi-VN', { 
                                    notation: 'compact',
                                    compactDisplay: 'short'
                                }).format(value) + ' đ';
                            }
                        }
                    }
                }
            }
        });

        // Period Filter Functions
        function changePeriod(period) {
            if (period === 'custom') {
                window.location.href = '?page=statistics&period=custom&start=<?php echo date('Y-m-01'); ?>&end=<?php echo date('Y-m-d'); ?>';
            } else {
                window.location.href = '?page=statistics&period=' + period;
            }
        }

        function applyCustomDate() {
            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;
            
            if (!start || !end) {
                alert('Vui lòng chọn đầy đủ ngày bắt đầu và kết thúc');
                return;
            }
            
            if (start > end) {
                alert('Ngày bắt đầu phải nhỏ hơn ngày kết thúc');
                return;
            }
            
            window.location.href = `?page=statistics&period=custom&start_date=${start}&end_date=${end}`;
        }
    </script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
