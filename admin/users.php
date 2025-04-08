<?php
$page_title = "User Management";
require_once '../includes/header.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$conn = getDBConnection();

// Get admin settings
$stmt = $conn->prepare("SELECT setting_key, setting_value FROM admin_settings");
$stmt->execute();
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            $conn->beginTransaction();
            
            switch ($_POST['action']) {
                case 'activate':
                    $stmt = $conn->prepare("UPDATE users SET is_active = 1, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$_POST['user_id']]);
                    
                    // Update session if the affected user is the current user
                    if ($_POST['user_id'] == $_SESSION['user_id']) {
                        $_SESSION['is_active'] = 1;
                    }
                    
                    // Log the action
                    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details) VALUES (?, 'activate_user', ?)");
                    $stmt->execute([$_SESSION['user_id'], "Activated user ID: " . $_POST['user_id']]);
                    
                    $success = "User activated successfully!";
                    break;
                
                case 'deactivate':
                    $stmt = $conn->prepare("UPDATE users SET is_active = 0, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$_POST['user_id']]);
                    
                    // Update session if the affected user is the current user
                    if ($_POST['user_id'] == $_SESSION['user_id']) {
                        $_SESSION['is_active'] = 0;
                    }
                    
                    // Log the action
                    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details) VALUES (?, 'deactivate_user', ?)");
                    $stmt->execute([$_SESSION['user_id'], "Deactivated user ID: " . $_POST['user_id']]);
                    
                    $success = "User deactivated successfully!";
                    break;
                
                case 'delete':
                    // First, check if user has any pending withdrawals
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM withdrawals WHERE user_id = ? AND status = 'pending'");
                    $stmt->execute([$_POST['user_id']]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception("Cannot delete user with pending withdrawals");
                    }
                    
                    // Delete user's data
                    $stmt = $conn->prepare("DELETE FROM user_activity WHERE user_id = ?");
                    $stmt->execute([$_POST['user_id']]);
                    
                    $stmt = $conn->prepare("DELETE FROM withdrawals WHERE user_id = ?");
                    $stmt->execute([$_POST['user_id']]);
                    
                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$_POST['user_id']]);
                    
                    // Log the action
                    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details) VALUES (?, 'delete_user', ?)");
                    $stmt->execute([$_SESSION['user_id'], "Deleted user ID: " . $_POST['user_id']]);
                    
                    $success = "User deleted successfully!";
                    break;
                
                case 'make_admin':
                    $stmt = $conn->prepare("UPDATE users SET is_admin = 1, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$_POST['user_id']]);
                    
                    // Update session if the affected user is the current user
                    if ($_POST['user_id'] == $_SESSION['user_id']) {
                        $_SESSION['is_admin'] = 1;
                    }
                    
                    // Log the action
                    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details) VALUES (?, 'make_admin', ?)");
                    $stmt->execute([$_SESSION['user_id'], "Made user ID: " . $_POST['user_id'] . " an admin"]);
                    
                    $success = "User promoted to admin successfully!";
                    break;
                
                case 'remove_admin':
                    // Prevent removing the last admin
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE is_admin = 1");
                    $stmt->execute();
                    if ($stmt->fetchColumn() <= 1) {
                        throw new Exception("Cannot remove the last admin user");
                    }
                    
                    $stmt = $conn->prepare("UPDATE users SET is_admin = 0, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$_POST['user_id']]);
                    
                    // Update session if the affected user is the current user
                    if ($_POST['user_id'] == $_SESSION['user_id']) {
                        $_SESSION['is_admin'] = 0;
                    }
                    
                    // Log the action
                    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details) VALUES (?, 'remove_admin', ?)");
                    $stmt->execute([$_SESSION['user_id'], "Removed admin privileges from user ID: " . $_POST['user_id']]);
                    
                    $success = "Admin privileges removed successfully!";
                    break;
                
                case 'reset_password':
                    $newPassword = bin2hex(random_bytes(8)); // Generate random password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    
                    $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$hashedPassword, $_POST['user_id']]);
                    
                    // Update session if the affected user is the current user
                    if ($_POST['user_id'] == $_SESSION['user_id']) {
                        $_SESSION['password'] = $hashedPassword;
                    }
                    
                    // Log the action
                    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details) VALUES (?, 'reset_password', ?)");
                    $stmt->execute([$_SESSION['user_id'], "Reset password for user ID: " . $_POST['user_id']]);
                    
                    $success = "Password reset successfully! New password: " . $newPassword;
                    break;
            }
            
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Error performing action: " . $e->getMessage();
        }
    }
}

// Get all users with their stats
$stmt = $conn->prepare("
    SELECT 
        u.*,
        COUNT(DISTINCT ua.id) as total_videos_watched,
        COALESCE(SUM(CASE WHEN ua.activity_type = 'video_watched' THEN ua.amount ELSE 0 END), 0) as total_earnings,
        COUNT(DISTINCT w.id) as total_withdrawals,
        COALESCE(SUM(CASE WHEN w.status = 'pending' THEN w.amount ELSE 0 END), 0) as pending_withdrawals,
        MAX(ua.created_at) as last_watch,
        COUNT(DISTINCT CASE WHEN DATE(ua.created_at) = CURDATE() THEN ua.id END) as videos_watched_today
    FROM users u
    LEFT JOIN user_activity ua ON u.id = ua.user_id
    LEFT JOIN withdrawals w ON u.id = w.user_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$stmt->execute();
$users = $stmt->fetchAll();
?>

<div class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <!-- Admin Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-800 dark:text-white">User Management</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-white">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="settings.php" class="text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-white">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <a href="videos.php" class="text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-white">
                        <i class="fas fa-video"></i> Videos
                    </a>
                    <a href="../logout.php" class="text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-white">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Activity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Earnings</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Withdrawals</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                <?php echo htmlspecialchars($user['username']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Joined: <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['is_active'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                    <?php if ($user['is_admin']): ?>
                                        <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                                            Admin
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?php echo $user['total_videos_watched']; ?> videos
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        <?php echo $user['videos_watched_today']; ?> today
                                    </div>
                                    <?php if ($user['last_watch']): ?>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            Last: <?php echo date('M d, Y H:i', strtotime($user['last_watch'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?php echo $settings['currency_symbol'] ?? 'PKR'; ?> <?php echo number_format($user['total_earnings'], 2); ?>
                                    </div>
                                    <?php if ($user['pending_withdrawals'] > 0): ?>
                                        <div class="text-sm text-yellow-600 dark:text-yellow-400">
                                            <?php echo $settings['currency_symbol'] ?? 'PKR'; ?> <?php echo number_format($user['pending_withdrawals'], 2); ?> pending
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?php echo $user['total_withdrawals']; ?> withdrawals
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <?php if ($user['is_active']): ?>
                                            <button type="submit" name="action" value="deactivate" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 mr-2">
                                                Deactivate
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" name="action" value="activate" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 mr-2">
                                                Activate
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if (!$user['is_admin']): ?>
                                            <button type="submit" name="action" value="make_admin" class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300 mr-2">
                                                Make Admin
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" name="action" value="remove_admin" class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300 mr-2">
                                                Remove Admin
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button type="submit" name="action" value="reset_password" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-2">
                                            Reset Password
                                        </button>
                                        
                                        <button type="submit" name="action" value="delete" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?> 