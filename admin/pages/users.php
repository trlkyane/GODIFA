<?php
/**
 * Quản lý Nhân viên
 * File: admin/pages/users.php
 */

require_once __DIR__ . '/../middleware/auth.php';
requireStaff();

// Check permission - Chỉ Owner và Admin mới được quản lý nhân viên
if (!hasPermission('manage_users')) {
    die('<div class="p-8"><div class="bg-red-100 text-red-700 p-4 rounded">Bạn không có quyền truy cập trang này!</div></div>');
}

// Load Controller (Admin version)
require_once __DIR__ . '/../../controller/admin/cUser.php';
$userController = new cUser();

$success = '';
$error = '';

// Xử lý THÊM nhân viên (Chỉ Owner)
if (isset($_POST['add_user'])) {
    // Kiểm tra quyền thêm nhân viên
    if (!canAddUserWithRole($_SESSION['role_id'], intval($_POST['roleID']))) {
        $error = 'Chỉ Chủ Doanh Nghiệp mới có quyền thêm nhân viên!';
    } else {
        $data = [
            'userName' => trim($_POST['userName']),
            'email' => trim($_POST['email']),
            'password' => $_POST['password'],
            'phone' => trim($_POST['phone'] ?? ''),
            'roleID' => intval($_POST['roleID'])
        ];
        
        $currentRoleID = $_SESSION['roleID'] ?? $_SESSION['role_id'] ?? null;
        $result = $userController->addUser($data, $currentRoleID);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = implode('<br>', $result['errors']);
        }
    }
}

// Xử lý SỬA nhân viên
if (isset($_POST['edit_user'])) {
    $userID = intval($_POST['userID']);
    
    $data = [
        'userName' => trim($_POST['userName']),
        'email' => trim($_POST['email']),
        'phone' => trim($_POST['phone'] ?? ''),
        'roleID' => intval($_POST['roleID'])
    ];
    
    // Truyền roleID hiện tại để Controller kiểm tra quyền
    $currentRoleID = $_SESSION['roleID'] ?? $_SESSION['role_id'] ?? null;
    $result = $userController->updateUser($userID, $data, $currentRoleID);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = implode('<br>', $result['errors']);
    }
}

// Xử lý ĐỔI MẬT KHẨU
if (isset($_POST['change_password'])) {
    $userID = intval($_POST['userID']);
    
    // Lấy thông tin nhân viên để kiểm tra quyền
    $targetUser = $userController->getUserByID($userID);
    
    if (!$targetUser) {
        $error = 'Không tìm thấy nhân viên!';
    } elseif (!canEditUser($_SESSION['role_id'], $targetUser['roleID'])) {
        $error = 'Bạn không có quyền đổi mật khẩu nhân viên này! Chỉ được đổi mật khẩu nhân viên cấp dưới.';
    } else {
        $data = [
            'newPassword' => $_POST['newPassword'],
            'confirmPassword' => $_POST['newPassword'] // View không có confirm, dùng chung
        ];
        
        $result = $userController->changePassword($userID, $data);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = implode('<br>', $result['errors']);
        }
    }
}

// Xử lý TOGGLE STATUS (Khóa/Mở khóa)
if (isset($_GET['toggle']) && hasPermission('manage_users')) {
    $userID = intval($_GET['toggle']);
    
    // Lấy thông tin nhân viên để kiểm tra quyền
    $targetUser = $userController->getUserByID($userID);
    
    if (!$targetUser) {
        $error = 'Không tìm thấy nhân viên!';
    } elseif (!canEditUser($_SESSION['role_id'], $targetUser['roleID'])) {
        $error = 'Bạn không có quyền thay đổi trạng thái nhân viên này!';
    } else {
        $result = $userController->toggleStatus($userID);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'] ?? 'Lỗi khi thay đổi trạng thái!';
        }
    }
}

// Xử lý XÓA nhân viên (chỉ Owner)
if (isset($_GET['delete']) && hasPermission('delete_user')) {
    $userID = intval($_GET['delete']);
    
    // Truyền roleID hiện tại để Controller kiểm tra quyền
    $currentUserID = $_SESSION['userID'] ?? $_SESSION['user_id'] ?? null;
    $currentRoleID = $_SESSION['roleID'] ?? $_SESSION['role_id'] ?? null;
    $result = $userController->deleteUser($userID, $currentUserID, $currentRoleID);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = isset($result['errors']) ? implode('<br>', $result['errors']) : $result['message'];
    }
}

// Lấy danh sách nhân viên và roles
$users = $userController->getAllUsers();
$roles = $userController->getAllRoles();

// Debug: Kiểm tra dữ liệu
if (empty($users)) {
    error_log("WARNING: No users found in database!");
}

$pageTitle = 'Quản lý Nhân viên';
include __DIR__ . '/../includes/header.php';
?>

<div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1 overflow-y-auto ml-64">
        <!-- Header -->
        <div class="bg-white shadow sticky top-0 z-10">
            <div class="px-4 md:px-6 py-4 flex flex-wrap justify-between items-center gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">
                        <i class="fas fa-users text-blue-500 mr-2"></i>
                        Quản lý Nhân viên
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Tổng số: <strong><?php echo count($users); ?></strong> nhân viên
                    </p>
                </div>
                <?php if ($_SESSION['role_id'] == ROLE_OWNER): ?>
                <button onclick="openAddModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg inline-flex items-center text-sm md:text-base">
                    <i class="fas fa-plus mr-2"></i>Thêm nhân viên
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Content -->
        <div class="p-4 md:p-6">
            <!-- Success/Error Messages -->
            <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
            </div>
            <?php endif; ?>

            <!-- Users Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Họ tên</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Điện thoại</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Vai trò</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Trạng thái</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-user-slash text-4xl mb-2"></i>
                                    <p>Chưa có nhân viên nào</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                <?php
                                // Safe access với giá trị mặc định
                                $userID = $user['userID'] ?? 0;
                                $userName = $user['userName'] ?? 'N/A';
                                $email = $user['email'] ?? 'N/A';
                                $phone = $user['phone'] ?? '';
                                $roleID = $user['roleID'] ?? 0;
                                $roleName = $user['roleName'] ?? 'N/A';
                                $status = $user['status'] ?? '0';
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center text-white font-bold mr-3">
                                                <?php echo strtoupper(mb_substr($userName, 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($userName); ?></div>
                                                <?php if (isset($_SESSION['userID']) && $userID == $_SESSION['userID']): ?>
                                                <span class="text-xs text-blue-600 font-semibold">(Bạn)</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <i class="fas fa-envelope mr-1 text-gray-400"></i>
                                        <?php echo htmlspecialchars($email); ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <i class="fas fa-phone mr-1 text-gray-400"></i>
                                        <?php echo htmlspecialchars($phone ?: 'N/A'); ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <?php
                                        $roleColors = [
                                            1 => 'bg-red-100 text-red-800',      // Owner
                                            2 => 'bg-purple-100 text-purple-800', // Admin
                                            3 => 'bg-blue-100 text-blue-800',     // Sales
                                            4 => 'bg-green-100 text-green-800'    // Support
                                        ];
                                        $colorClass = $roleColors[$roleID] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="px-3 py-1 <?php echo $colorClass; ?> rounded-full text-xs font-semibold">
                                            <?php echo htmlspecialchars($roleName); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-3 py-1 <?php echo $status == '1' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> rounded-full text-xs font-medium inline-flex items-center">
                                            <i class="fas fa-<?php echo $status == '1' ? 'check-circle' : 'times-circle'; ?> mr-1"></i>
                                            <?php echo $status == '1' ? 'Hoạt động' : 'Đã khóa'; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex justify-center items-center space-x-2">
                                            <?php if (canEditUser($_SESSION['role_id'], $roleID)): ?>
                                            <!-- Sửa -->
                                            <button onclick='openEditModal(<?php echo json_encode($user, JSON_HEX_APOS); ?>)' 
                                                    class="text-blue-600 hover:text-blue-800" title="Sửa thông tin">
                                                <i class="fas fa-edit text-lg"></i>
                                            </button>
                                            
                                            <!-- Đổi mật khẩu -->
                                            <button onclick="openPasswordModal(<?php echo $userID; ?>, '<?php echo htmlspecialchars($userName); ?>')" 
                                                    class="text-purple-600 hover:text-purple-800" title="Đổi mật khẩu">
                                                <i class="fas fa-key text-lg"></i>
                                            </button>
                                            
                                            <!-- Khóa/Mở khóa -->
                                            <?php if ($status == '1'): ?>
                                            <button onclick="toggleUserStatus(<?php echo $userID; ?>, '<?php echo htmlspecialchars($userName); ?>', 0)" 
                                                    class="text-orange-600 hover:text-orange-800" title="Khóa tài khoản">
                                                <i class="fas fa-lock text-lg"></i>
                                            </button>
                                            <?php else: ?>
                                            <button onclick="toggleUserStatus(<?php echo $userID; ?>, '<?php echo htmlspecialchars($userName); ?>', 1)" 
                                                    class="text-green-600 hover:text-green-800" title="Mở khóa tài khoản">
                                                <i class="fas fa-unlock text-lg"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php endif; ?>
                                            
                                            <!-- Xóa (chỉ Owner, không cho xóa chính mình) -->
                                            <?php if (hasPermission('delete_user') && isset($_SESSION['user_id']) && $userID != $_SESSION['user_id']): ?>
                                            <button onclick="deleteUser(<?php echo $userID; ?>, '<?php echo htmlspecialchars($userName); ?>')" 
                                                    class="text-red-600 hover:text-red-800" title="Xóa">
                                                <i class="fas fa-trash text-lg"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if (!canEditUser($_SESSION['role_id'], $roleID) && !hasPermission('delete_user')): ?>
                                            <!-- Không có quyền -->
                                            <span class="text-gray-400 text-sm italic">Không có quyền</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal THÊM nhân viên -->
<div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-user-plus text-blue-500 mr-2"></i>Thêm nhân viên mới
            </h3>
            <button onclick="closeAddModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Họ tên <span class="text-red-500">*</span></label>
                    <input type="text" name="userName" required
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" required
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Điện thoại</label>
                    <input type="text" name="phone"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mật khẩu <span class="text-red-500">*</span></label>
                    <input type="password" name="password" required minlength="6"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Tối thiểu 6 ký tự</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Vai trò <span class="text-red-500">*</span></label>
                    <select name="roleID" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <?php foreach ($roles as $role): ?>
                            <?php if ($role['roleID'] != ROLE_OWNER): // Bỏ Chủ Doanh Nghiệp ?>
                            <option value="<?php echo $role['roleID']; ?>">
                                <?php echo htmlspecialchars($role['roleName']); ?>
                            </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" onclick="closeAddModal()" 
                        class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg">
                    Hủy
                </button>
                <button type="submit" name="add_user" 
                        class="px-6 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg">
                    <i class="fas fa-save mr-2"></i>Thêm nhân viên
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal SỬA nhân viên -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-edit text-blue-500 mr-2"></i>Sửa thông tin nhân viên
            </h3>
            <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" class="p-6">
            <input type="hidden" name="userID" id="edit_userID">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Họ tên <span class="text-red-500">*</span></label>
                    <input type="text" name="userName" id="edit_userName" required
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="edit_email" required
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Điện thoại</label>
                    <input type="text" name="phone" id="edit_phone"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Vai trò <span class="text-red-500">*</span></label>
                    <select name="roleID" id="edit_roleID" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <?php foreach ($roles as $role): ?>
                            <?php if ($role['roleID'] != ROLE_OWNER): // Bỏ Chủ Doanh Nghiệp ?>
                            <option value="<?php echo $role['roleID']; ?>">
                                <?php echo htmlspecialchars($role['roleName']); ?>
                            </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    Sử dụng nút <strong>Khóa/Mở khóa</strong> ở cột Hành động để thay đổi trạng thái tài khoản.
                </p>
            </div>
            
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" onclick="closeEditModal()" 
                        class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg">
                    Hủy
                </button>
                <button type="submit" name="edit_user" 
                        class="px-6 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg">
                    <i class="fas fa-save mr-2"></i>Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal ĐỔI MẬT KHẨU -->
<div id="passwordModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full">
        <div class="bg-purple-500 text-white px-6 py-4 rounded-t-lg">
            <h3 class="text-xl font-bold">
                <i class="fas fa-key mr-2"></i>Đổi mật khẩu
            </h3>
        </div>
        
        <form method="POST" class="p-6">
            <input type="hidden" name="userID" id="password_userID">
            
            <div class="mb-4">
                <p class="text-sm text-gray-600 mb-4">
                    Đổi mật khẩu cho nhân viên: <strong id="password_userName"></strong>
                </p>
                
                <label class="block text-sm font-medium text-gray-700 mb-2">Mật khẩu mới <span class="text-red-500">*</span></label>
                <input type="password" name="newPassword" required minlength="6"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
                <p class="text-xs text-gray-500 mt-1">Tối thiểu 6 ký tự</p>
            </div>
            
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closePasswordModal()" 
                        class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg">
                    Hủy
                </button>
                <button type="submit" name="change_password" 
                        class="px-6 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg">
                    <i class="fas fa-check mr-2"></i>Đổi mật khẩu
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal functions
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}

function openEditModal(user) {
    document.getElementById('edit_userID').value = user.userID;
    document.getElementById('edit_userName').value = user.userName;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_phone').value = user.phone || '';
    document.getElementById('edit_roleID').value = user.roleID;
    
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function openPasswordModal(userID, userName) {
    document.getElementById('password_userID').value = userID;
    document.getElementById('password_userName').textContent = userName;
    document.getElementById('passwordModal').classList.remove('hidden');
}

function closePasswordModal() {
    document.getElementById('passwordModal').classList.add('hidden');
}

function toggleUserStatus(id, name, newStatus) {
    const action = newStatus === 0 ? 'KHÓA' : 'MỞ KHÓA';
    const message = newStatus === 0 
        ? `Bạn có chắc muốn KHÓA tài khoản "${name}"?\n\nNhân viên sẽ không thể đăng nhập cho đến khi được mở khóa.`
        : `Bạn có chắc muốn MỞ KHÓA tài khoản "${name}"?\n\nNhân viên sẽ có thể đăng nhập lại.`;
    
    if (confirm(message)) {
        window.location.href = `?page=users&toggle=${id}`;
    }
}

function deleteUser(id, name) {
    if (confirm(`Bạn có chắc muốn XÓA nhân viên "${name}"?\n\nLưu ý: Hành động này không thể hoàn tác!`)) {
        window.location.href = `?page=users&delete=${id}`;
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const addModal = document.getElementById('addModal');
    const editModal = document.getElementById('editModal');
    const passwordModal = document.getElementById('passwordModal');
    
    if (event.target == addModal) closeAddModal();
    if (event.target == editModal) closeEditModal();
    if (event.target == passwordModal) closePasswordModal();
}
</script>

<style>
/* Table responsive */
table {
    width: 100%;
    border-collapse: collapse;
}

table th, table td {
    word-wrap: break-word;
    overflow-wrap: break-word;
    vertical-align: middle;
}

/* Action buttons */
table td button {
    padding: 0.25rem;
    transition: all 0.2s ease;
}

table td button:hover {
    transform: scale(1.2);
}

/* Avatar gradient animation */
.bg-gradient-to-br {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* Modal animations */
#addModal, #editModal, #passwordModal {
    animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Responsive */
@media (max-width: 768px) {
    table {
        font-size: 0.875rem;
    }
    
    .overflow-x-auto {
        overflow-x: auto;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
