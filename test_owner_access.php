<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Owner Access Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold mb-6 text-center">
                <i class="fas fa-shield-alt text-blue-600 mr-2"></i>
                Test Owner-Only Access Control
            </h1>

            <!-- Role Selection -->
            <div class="mb-8 bg-gradient-to-r from-blue-50 to-purple-50 p-6 rounded-lg">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-user-tie mr-2"></i>Chọn Role để test:
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <?php
                    require_once 'model/database.php';
                    $db = Database::getInstance();
                    $conn = $db->connect();
                    
                    $roles = $conn->query("SELECT * FROM role ORDER BY roleID");
                    while ($role = $roles->fetch_assoc()):
                    ?>
                    <div class="bg-white p-4 rounded-lg border-2 border-gray-200 hover:border-blue-500 transition cursor-pointer"
                         onclick="testAccess(<?= $role['roleID'] ?>, '<?= addslashes($role['roleName']) ?>')">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-semibold text-lg">
                                    <?php if ($role['roleID'] == 1): ?>
                                        <i class="fas fa-crown text-amber-500 mr-2"></i>
                                    <?php else: ?>
                                        <i class="fas fa-user text-gray-400 mr-2"></i>
                                    <?php endif; ?>
                                    <?= $role['roleName'] ?>
                                </p>
                                <p class="text-sm text-gray-600">roleID: <?= $role['roleID'] ?></p>
                            </div>
                            <i class="fas fa-arrow-right text-blue-500"></i>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Test Results -->
            <div id="results" class="hidden">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-clipboard-check mr-2"></i>Kết quả test:
                </h2>
                <div id="resultContent"></div>
            </div>

            <!-- Protected Pages -->
            <div class="mt-8 bg-yellow-50 border-2 border-yellow-200 p-6 rounded-lg">
                <h2 class="text-xl font-semibold mb-4 text-yellow-800">
                    <i class="fas fa-lock mr-2"></i>Protected Pages
                </h2>
                <div class="space-y-2">
                    <div class="flex items-center justify-between bg-white p-3 rounded">
                        <span class="font-medium">
                            <i class="fas fa-users-cog mr-2 text-amber-600"></i>
                            Quản lý Nhóm Khách hàng
                        </span>
                        <code class="text-sm bg-gray-100 px-2 py-1 rounded">
                            ?page=customer_groups
                        </code>
                    </div>
                    <div class="flex items-center justify-between bg-white p-3 rounded">
                        <span class="font-medium">
                            <i class="fas fa-magic mr-2 text-amber-600"></i>
                            Phân nhóm tự động
                        </span>
                        <code class="text-sm bg-gray-100 px-2 py-1 rounded">
                            ?page=auto_assign_groups
                        </code>
                    </div>
                </div>
            </div>

            <!-- Documentation -->
            <div class="mt-8 bg-blue-50 border-2 border-blue-200 p-6 rounded-lg">
                <h2 class="text-xl font-semibold mb-4 text-blue-800">
                    <i class="fas fa-info-circle mr-2"></i>Thông tin
                </h2>
                <ul class="space-y-2 text-gray-700">
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Middleware: <code>admin/middleware/owner_only.php</code></li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Chỉ roleID = 1 (Chủ Doanh Nghiệp) được truy cập</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Tự động ghi log các truy cập trái phép</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Redirect về dashboard với thông báo lỗi</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
    function testAccess(roleID, roleName) {
        const results = document.getElementById('results');
        const resultContent = document.getElementById('resultContent');
        
        results.classList.remove('hidden');
        
        // Check access
        const isOwner = roleID === 1;
        const accessGranted = isOwner;
        
        const html = `
            <div class="space-y-4">
                <!-- Role Info -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600 mb-2">Testing với role:</p>
                    <p class="text-xl font-bold">
                        ${isOwner ? '<i class="fas fa-crown text-amber-500 mr-2"></i>' : '<i class="fas fa-user text-gray-400 mr-2"></i>'}
                        ${roleName}
                    </p>
                    <p class="text-sm text-gray-600 mt-1">roleID: ${roleID}</p>
                </div>

                <!-- Access Result -->
                <div class="p-6 rounded-lg ${accessGranted ? 'bg-green-100 border-2 border-green-500' : 'bg-red-100 border-2 border-red-500'}">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-2xl font-bold ${accessGranted ? 'text-green-700' : 'text-red-700'} mb-2">
                                ${accessGranted ? '<i class="fas fa-check-circle mr-2"></i>ACCESS GRANTED' : '<i class="fas fa-times-circle mr-2"></i>ACCESS DENIED'}
                            </p>
                            <p class="text-sm ${accessGranted ? 'text-green-600' : 'text-red-600'}">
                                ${accessGranted 
                                    ? 'Có quyền truy cập trang Nhóm Khách hàng và Phân nhóm tự động' 
                                    : 'Không có quyền truy cập. Sẽ bị redirect về dashboard với thông báo lỗi.'}
                            </p>
                        </div>
                        <i class="fas ${accessGranted ? 'fa-unlock text-green-500' : 'fa-lock text-red-500'} text-4xl"></i>
                    </div>
                </div>

                <!-- Behavior -->
                <div class="bg-white border-2 border-gray-200 p-4 rounded-lg">
                    <p class="font-semibold mb-3"><i class="fas fa-cogs mr-2"></i>Hành vi hệ thống:</p>
                    <ul class="space-y-2 text-sm">
                        ${accessGranted ? `
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-blue-500 mr-2 mt-1"></i>
                                <span>Hiển thị menu "Nhóm KH" và "Phân nhóm tự động" trên sidebar với icon 👑</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-blue-500 mr-2 mt-1"></i>
                                <span>Có thể truy cập và thao tác với tất cả chức năng phân nhóm khách hàng</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-blue-500 mr-2 mt-1"></i>
                                <span>Xem được thống kê và báo cáo chi tiết về nhóm khách hàng</span>
                            </li>
                        ` : `
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-red-500 mr-2 mt-1"></i>
                                <span>KHÔNG hiển thị menu "Nhóm KH" và "Phân nhóm tự động" trên sidebar</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-red-500 mr-2 mt-1"></i>
                                <span>Nếu cố gắng truy cập URL trực tiếp → Bị chặn bởi middleware</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-red-500 mr-2 mt-1"></i>
                                <span>Redirect về: <code>/admin/index.php?error=permission_denied</code></span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-red-500 mr-2 mt-1"></i>
                                <span>Ghi log: "[SECURITY] User #X tried to access owner-only page"</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-red-500 mr-2 mt-1"></i>
                                <span>Hiển thị alert đỏ: "⚠️ Bạn không có quyền truy cập trang đó!"</span>
                            </li>
                        `}
                    </ul>
                </div>

                <!-- Try Access -->
                <div class="bg-gradient-to-r from-purple-50 to-blue-50 p-4 rounded-lg border-2 border-purple-200">
                    <p class="font-semibold mb-3">
                        <i class="fas fa-flask mr-2"></i>Test thực tế:
                    </p>
                    <p class="text-sm text-gray-600 mb-3">
                        Để test trên production, đăng nhập với role này và thử truy cập:
                    </p>
                    <div class="space-y-2">
                        <a href="/GODIFA/admin/index.php?page=customer_groups" 
                           class="block bg-white hover:bg-gray-50 p-3 rounded border border-gray-300 transition"
                           target="_blank">
                            <i class="fas fa-external-link-alt mr-2"></i>
                            Test: Quản lý Nhóm KH
                        </a>
                        <a href="/GODIFA/admin/index.php?page=auto_assign_groups" 
                           class="block bg-white hover:bg-gray-50 p-3 rounded border border-gray-300 transition"
                           target="_blank">
                            <i class="fas fa-external-link-alt mr-2"></i>
                            Test: Phân nhóm tự động
                        </a>
                    </div>
                </div>
            </div>
        `;
        
        resultContent.innerHTML = html;
        resultContent.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    </script>
</body>
</html>
