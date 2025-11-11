<?php
/**
 * Test Owner Access to Customer Groups
 * File: test_owner_access_fix.php
 * 
 * Kiểm tra xem Owner đã vào được trang customer_groups chưa
 */

session_name('GODIFA_ADMIN_SESSION');
session_start();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Owner Access - GODIFA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="max-w-4xl mx-auto p-8">
        <h1 class="text-3xl font-bold mb-6 text-gray-800">
            <i class="fas fa-vial text-blue-600"></i> Test Owner Access Fix
        </h1>

        <!-- Fix Status -->
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
            <div class="flex items-start">
                <i class="fas fa-check-circle text-green-600 text-2xl mr-3 mt-1"></i>
                <div>
                    <h2 class="text-lg font-semibold text-green-800 mb-2">✅ CODE QUALITY FIXES - COMPLETED</h2>
                    <ul class="text-sm text-green-700 space-y-1">
                        <li>✅ Fixed session variable inconsistency (admin_id → user_id)</li>
                        <li>✅ Fixed require order (auth.php before owner_only.php)</li>
                        <li>✅ Removed debug code và console.log</li>
                        <li>✅ Created constants.php với ROLE_OWNER, SESSION_ADMIN, etc.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Current Session -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">
                <i class="fas fa-user-shield text-indigo-600"></i> Current Session
            </h2>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="bg-blue-50 p-4 rounded-lg">
                    <table class="w-full text-sm">
                        <tr>
                            <td class="font-semibold py-2">User ID:</td>
                            <td><?= $_SESSION['user_id'] ?></td>
                        </tr>
                        <tr>
                            <td class="font-semibold py-2">Username:</td>
                            <td><?= $_SESSION['username'] ?? 'N/A' ?></td>
                        </tr>
                        <tr>
                            <td class="font-semibold py-2">Role ID:</td>
                            <td>
                                <span class="px-3 py-1 rounded <?= ($_SESSION['role_id'] ?? 0) == 1 ? 'bg-yellow-200 text-yellow-800' : 'bg-gray-200 text-gray-800' ?>">
                                    <?php 
                                    $role = $_SESSION['role_id'] ?? 0;
                                    $roleName = [
                                        0 => 'Customer',
                                        1 => 'Owner (Chủ DN)',
                                        2 => 'Admin',
                                        3 => 'Sales',
                                        4 => 'Support'
                                    ];
                                    echo "$role - " . ($roleName[$role] ?? 'Unknown');
                                    ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-semibold py-2">Session Name:</td>
                            <td><code class="bg-gray-100 px-2 py-1 rounded"><?= session_name() ?></code></td>
                        </tr>
                    </table>
                </div>

                <?php if (($_SESSION['role_id'] ?? 0) == 1): ?>
                    <div class="mt-4 bg-green-50 border border-green-200 p-4 rounded-lg">
                        <p class="text-green-800 font-semibold">
                            <i class="fas fa-check-circle mr-2"></i>✅ Bạn đang đăng nhập với quyền OWNER
                        </p>
                        <p class="text-sm text-green-600 mt-2">
                            Bạn có thể truy cập trang Quản lý Nhóm Khách hàng
                        </p>
                    </div>
                <?php else: ?>
                    <div class="mt-4 bg-yellow-50 border border-yellow-200 p-4 rounded-lg">
                        <p class="text-yellow-800 font-semibold">
                            <i class="fas fa-exclamation-triangle mr-2"></i>⚠️ Bạn KHÔNG phải OWNER
                        </p>
                        <p class="text-sm text-yellow-600 mt-2">
                            Vui lòng đăng nhập với tài khoản Owner (roleID = 1)
                        </p>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="bg-red-50 border border-red-200 p-4 rounded-lg">
                    <p class="text-red-800 font-semibold">
                        <i class="fas fa-times-circle mr-2"></i>❌ Chưa đăng nhập
                    </p>
                    <p class="text-sm text-red-600 mt-2">
                        Vui lòng đăng nhập vào Admin Panel trước
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Test Cases -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">
                <i class="fas fa-clipboard-check text-purple-600"></i> Test Cases
            </h2>

            <div class="space-y-4">
                <!-- Test 1 -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-700 mb-2">
                        <i class="fas fa-1 text-blue-500 mr-2"></i>Test Access Customer Groups
                    </h3>
                    <p class="text-sm text-gray-600 mb-3">
                        Kiểm tra xem Owner có truy cập được trang quản lý nhóm khách hàng không
                    </p>
                    <a href="/GODIFA/admin/index.php?page=customer_groups" 
                       class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition">
                        <i class="fas fa-arrow-right mr-2"></i>Thử truy cập Customer Groups
                    </a>
                </div>

                <!-- Test 2 -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-700 mb-2">
                        <i class="fas fa-2 text-blue-500 mr-2"></i>Test Auto Assign Groups
                    </h3>
                    <p class="text-sm text-gray-600 mb-3">
                        Kiểm tra trang tự động phân nhóm (cũng cần Owner)
                    </p>
                    <a href="/GODIFA/admin/index.php?page=auto_assign_groups" 
                       class="inline-block bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded transition">
                        <i class="fas fa-arrow-right mr-2"></i>Thử truy cập Auto Assign
                    </a>
                </div>

                <!-- Test 3 -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-700 mb-2">
                        <i class="fas fa-3 text-blue-500 mr-2"></i>Test Admin Dashboard
                    </h3>
                    <p class="text-sm text-gray-600 mb-3">
                        Trang dashboard (tất cả roles đều vào được)
                    </p>
                    <a href="/GODIFA/admin/index.php" 
                       class="inline-block bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded transition">
                        <i class="fas fa-arrow-right mr-2"></i>Vào Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Expected Results -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">
                <i class="fas fa-lightbulb text-yellow-500"></i> Expected Results
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- If Owner -->
                <div class="border-2 border-green-300 rounded-lg p-4 bg-green-50">
                    <h3 class="font-semibold text-green-800 mb-2">
                        ✅ Nếu bạn là Owner (role_id = 1):
                    </h3>
                    <ul class="text-sm text-green-700 space-y-1">
                        <li><i class="fas fa-check mr-2"></i>Vào được Customer Groups</li>
                        <li><i class="fas fa-check mr-2"></i>Vào được Auto Assign</li>
                        <li><i class="fas fa-check mr-2"></i>Vào được Dashboard</li>
                        <li><i class="fas fa-check mr-2"></i>Không bị redirect</li>
                    </ul>
                </div>

                <!-- If Not Owner -->
                <div class="border-2 border-red-300 rounded-lg p-4 bg-red-50">
                    <h3 class="font-semibold text-red-800 mb-2">
                        ❌ Nếu bạn KHÔNG phải Owner:
                    </h3>
                    <ul class="text-sm text-red-700 space-y-1">
                        <li><i class="fas fa-times mr-2"></i>Bị chặn ở Customer Groups</li>
                        <li><i class="fas fa-times mr-2"></i>Bị chặn ở Auto Assign</li>
                        <li><i class="fas fa-times mr-2"></i>Redirect về Dashboard</li>
                        <li><i class="fas fa-times mr-2"></i>Hiện error: permission_denied</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-4">
            <a href="/GODIFA/admin/login.php" 
               class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-center px-6 py-3 rounded-lg transition font-semibold">
                <i class="fas fa-sign-in-alt mr-2"></i>Đăng nhập Admin
            </a>
            <a href="/GODIFA/admin/logout.php" 
               class="flex-1 bg-gray-600 hover:bg-gray-700 text-white text-center px-6 py-3 rounded-lg transition font-semibold">
                <i class="fas fa-sign-out-alt mr-2"></i>Đăng xuất
            </a>
        </div>

        <!-- Documentation Link -->
        <div class="mt-6 text-center text-sm text-gray-500">
            <i class="fas fa-book mr-2"></i>
            Xem chi tiết: <a href="CODE_QUALITY_FIXES.md" class="text-blue-600 hover:underline">CODE_QUALITY_FIXES.md</a>
        </div>
    </div>
</body>
</html>
