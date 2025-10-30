<?php
/**
 * Tự động phân nhóm khách hàng
 * File: admin/pages/auto_assign_groups.php
 */

require_once __DIR__ . '/../middleware/auth.php';
requireStaff();

// Check permission
if (!hasPermission('manage_customers')) {
    die('<div class="p-8"><div class="bg-red-100 text-red-700 p-4 rounded">Bạn không có quyền truy cập trang này!</div></div>');
}

require_once __DIR__ . '/../../model/database.php';
$db = new clsKetNoi();
$conn = $db->moKetNoi();

$success = '';
$error = '';
$stats = [];

// Chạy stored procedure tự động phân nhóm
if (isset($_POST['run_auto_assign'])) {
    try {
        // Gọi stored procedure
        $sql = "CALL auto_assign_customer_groups_by_spending()";
        $result = mysqli_query($conn, $sql);
        
        if ($result) {
            // Lấy thống kê sau khi phân nhóm
                $statsQuery = "SELECT * FROM v_customer_group_stats ORDER BY minSpent DESC";
            
            $statsResult = mysqli_query($conn, $statsQuery);
            while ($row = mysqli_fetch_assoc($statsResult)) {
                $stats[] = $row;
            }
            
            $success = "Đã phân nhóm tự động thành công!";
        } else {
            $error = "Lỗi khi chạy phân nhóm tự động: " . mysqli_error($conn);
        }
    } catch (Exception $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy thống kê khách hàng chưa được phân nhóm
$unassignedQuery = "SELECT COUNT(*) as count FROM customer WHERE groupID IS NULL OR groupID = 0";
$unassignedResult = mysqli_query($conn, $unassignedQuery);
$unassigned = mysqli_fetch_assoc($unassignedResult);

// Lấy tổng số khách hàng
$totalCustomersQuery = "SELECT COUNT(*) as count FROM customer";
$totalCustomersResult = mysqli_query($conn, $totalCustomersQuery);
$totalCustomers = mysqli_fetch_assoc($totalCustomersResult);

$pageTitle = 'Tự động phân nhóm khách hàng';
include __DIR__ . '/../includes/header.php';
?>

<div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1 overflow-y-auto ml-64">
        <!-- Header -->
        <div class="bg-white shadow sticky top-0 z-10">
            <div class="px-4 md:px-6 py-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">
                        <i class="fas fa-magic text-indigo-500 mr-2"></i>
                        Tự động phân nhóm khách hàng
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Phân nhóm khách hàng dựa trên độ tuổi, giới tính và lịch sử mua hàng
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Content -->
        <div class="p-4 md:p-6">
            <!-- Alerts -->
            <?php if ($success): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <p><?php echo $success; ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <div><?php echo $error; ?></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Card: Tổng khách hàng -->
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90">Tổng khách hàng</p>
                            <p class="text-4xl font-bold mt-2"><?php echo number_format($totalCustomers['count']); ?></p>
                            <p class="text-xs mt-2 opacity-80">khách hàng trong hệ thống</p>
                        </div>
                        <i class="fas fa-users text-6xl opacity-20"></i>
                    </div>
                </div>
                
                <!-- Card: Khách hàng chưa phân nhóm -->
                <div class="bg-gradient-to-r from-orange-500 to-red-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90">Chưa phân nhóm</p>
                            <p class="text-4xl font-bold mt-2"><?php echo number_format($unassigned['count']); ?></p>
                            <p class="text-xs mt-2 opacity-80">khách hàng chưa có lịch sử mua hàng</p>
                        </div>
                        <i class="fas fa-user-slash text-6xl opacity-20"></i>
                    </div>
                </div>
            </div>

            <!-- Action Panel -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-cogs text-indigo-500 mr-2"></i>
                    Thực hiện phân nhóm tự động
                </h2>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <h3 class="font-semibold text-blue-800 mb-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        Cách thức hoạt động:
                    </h3>
                    <ul class="text-sm text-blue-700 space-y-1 list-disc list-inside">
                        <li>Hệ thống tính tổng chi tiêu của mỗi khách hàng (từ các đơn hàng đã thanh toán)</li>
                        <li>So sánh với khoảng chi tiêu của các nhóm (minSpent - maxSpent)</li>
                            <li>Tự động gán khách vào nhóm phù hợp (ưu tiên nhóm có minSpent cao hơn)</li>
                        <li>Khách hàng chưa từng mua hàng sẽ tự động vào nhóm "Nhóm khách hàng" (0 VNĐ)</li>
                    </ul>
                </div>
                
                <form method="POST" onsubmit="return confirm('Bạn có chắc muốn chạy phân nhóm tự động? Thao tác này sẽ cập nhật nhóm cho tất cả khách hàng phù hợp.')">
                    <button type="submit" name="run_auto_assign"
                            class="w-full md:w-auto px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg flex items-center justify-center">
                        <i class="fas fa-play-circle mr-2 text-xl"></i>
                        <span class="font-semibold">Chạy phân nhóm tự động</span>
                    </button>
                </form>
            </div>

            <!-- Kết quả sau khi chạy -->
            <?php if (!empty($stats)): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-chart-bar text-green-500 mr-2"></i>
                    Kết quả phân nhóm
                </h2>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nhóm</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Khoảng chi tiêu</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Số KH</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Doanh thu</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($stats as $stat): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <i class="fas <?php echo $stat['icon']; ?> text-lg mr-2" style="color: <?php echo $stat['color']; ?>"></i>
                                        <span class="font-medium text-gray-900"><?php echo htmlspecialchars($stat['groupName']); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-600">
                                        <i class="fas fa-wallet text-green-500 mr-1"></i>
                                        <?php 
                                        echo number_format($stat['minSpent']) . " VNĐ";
                                        if ($stat['maxSpent']) {
                                            echo " - " . number_format($stat['maxSpent']) . " VNĐ";
                                        } else {
                                            echo " - ∞";
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-users mr-1"></i>
                                        <?php echo number_format($stat['totalCustomers']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="text-sm font-semibold text-gray-900">
                                        <?php echo number_format($stat['totalRevenue']); ?> VNĐ
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo number_format($stat['totalOrders']); ?> đơn hàng
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
