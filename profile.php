<?php
$page_title = "Profile";
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isUserLoggedIn()) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();
$user = getCurrentUser();
$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();
        
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        
        
        // Check if username or email already exists (excluding current user)
        $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        if ($stmt->rowCount() > 0) {
            throw new Exception('Username or email already exists');
        }
        
        // Update password if provided
        if (!empty($currentPassword) && !empty($newPassword)) {
            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                throw new Exception('Current password is incorrect');
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $user['id']]);
        }
        
        $conn->commit();
        $success = 'Profile updated successfully!';
        
        // Refresh user data
        $user = getCurrentUser();
    } catch (Exception $e) {
        $conn->rollBack();
        $error = $e->getMessage();
    }
}

// Get user statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_videos,
        SUM(amount) as total_earnings,
        COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as videos_today
    FROM user_activity 
    WHERE user_id = ? 
    AND activity_type = 'video_watched' 
    AND status = 'completed'
");
$stmt->execute([$user['id']]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get pending withdrawals
$stmt = $conn->prepare("
    SELECT COUNT(*) as pending_withdrawals 
    FROM withdrawals 
    WHERE user_id = ? 
    AND status = 'pending'
");
$stmt->execute([$user['id']]);
$withdrawalStats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<style>body input, body select, body textarea, body option{color: black!important;padding:10px;border:1px solid #ccc;}</style> 
<div class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <!-- Top Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-800 dark:text-white">Profile</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-white">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="logout.php" class="text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-white">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Profile Information -->
            <div class="md:col-span-2">
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Profile Information</h2>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                            <input disabled type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                            <input disabled type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Current Password</label>
                            <input type="password" name="current_password" id="current_password" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">New Password</label>
                            <input required type="password" name="new_password" id="new_password" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Account Statistics -->
            <div>
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Account Statistics</h2>
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Total Videos Watched</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo $stats['total_videos']; ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Videos Watched Today</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo $stats['videos_today']; ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Total Earnings</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">PKR <?php echo number_format($stats['total_earnings'], 2); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Pending Withdrawals</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo $withdrawalStats['pending_withdrawals']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once 'includes/footer.php'; ?> 