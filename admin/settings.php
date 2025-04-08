<?php
require_once 'includes/utilities.php';
$page_title = "Admin Settings";
require_once '../includes/header.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$conn = getDBConnection();

// Get current settings
$stmt = $conn->prepare("SELECT setting_key, setting_value FROM admin_settings");
$stmt->execute();
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();
        
        // Validate and sanitize settings
        $validSettings = [
            'min_watch_time' => ['type' => 'int', 'min' => 10, 'max' => 300],
            'max_videos_per_day' => ['type' => 'int', 'min' => 1, 'max' => 50],
            'earnings_per_video' => ['type' => 'float', 'min' => 0.01, 'max' => 100],
            'currency_symbol' => ['type' => 'string', 'max_length' => 10],
            'minimum_withdrawal' => ['type' => 'float', 'min' => 10, 'max' => 1000]
        ];
        
        foreach ($_POST['settings'] as $key => $value) {
            if (!isset($validSettings[$key])) {
                throw new Exception("Invalid setting key: $key");
            }
            
            $validation = $validSettings[$key];
            switch ($validation['type']) {
                case 'int':
                    $value = (int)$value;
                    if ($value < $validation['min'] || $value > $validation['max']) {
                        throw new Exception("$key must be between {$validation['min']} and {$validation['max']}");
                    }
                    break;
                case 'float':
                    $value = (float)$value;
                    if ($value < $validation['min'] || $value > $validation['max']) {
                        throw new Exception("$key must be between {$validation['min']} and {$validation['max']}");
                    }
                    break;
                case 'string':
                    $value = substr(trim($value), 0, $validation['max_length']);
                    break;
            }
            
            $stmt = $conn->prepare("
                INSERT INTO admin_settings (setting_key, setting_value) 
                VALUES (:key, :value)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            $stmt->bindParam(':key', $key);
            $stmt->bindParam(':value', $value);
            $stmt->execute();
        }
        
        $conn->commit();
        $success = "Settings updated successfully!";
        
        // Refresh settings after update
        $stmt = $conn->prepare("SELECT setting_key, setting_value FROM admin_settings");
        $stmt->execute();
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Error updating settings: " . $e->getMessage();
    }
}

// Get current video
$stmt = $conn->prepare("SELECT * FROM videos WHERE is_active = 1 LIMIT 1");
$stmt->execute();
$currentVideo = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <style>body input, body select, body textarea, body option{color: black!important;}</style>
    <!-- Admin Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-800 dark:text-white">Admin Settings</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-white">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="users.php" class="text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-white">
                        <i class="fas fa-users"></i> Users
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
            <form method="POST" action="">
                <div class="space-y-6">
                    <!-- Video Settings -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Video Settings</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Minimum Watch Time (seconds)</label>
                                <input type="number" name="settings[min_watch_time]" value="<?php echo htmlspecialchars($settings['min_watch_time'] ?? '30'); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Maximum Videos per Day</label>
                                <input type="number" name="settings[max_videos_per_day]" value="<?php echo htmlspecialchars($settings['max_videos_per_day'] ?? '5'); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                        </div>
                    </div>

                    <!-- Earnings Settings -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Earnings Settings</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Earnings per Video (<?php echo $settings['currency_symbol'] ?? 'PKR'; ?>)</label>
                                <input type="number" step="0.01" name="settings[earnings_per_video]" value="<?php echo htmlspecialchars($settings['earnings_per_video'] ?? '5.00'); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Currency Symbol</label>
                                <input type="text" name="settings[currency_symbol]" value="<?php echo htmlspecialchars($settings['currency_symbol'] ?? 'PKR'); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                        </div>
                    </div>

                    <!-- Withdrawal Settings -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Withdrawal Settings</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Minimum Withdrawal Amount (<?php echo $settings['currency_symbol'] ?? 'PKR'; ?>)</label>
                                <input type="number" step="0.01" name="settings[minimum_withdrawal]" value="<?php echo htmlspecialchars($settings['minimum_withdrawal'] ?? '100.00'); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Save Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Current Video Settings -->
        <div class="mt-4">
            <h3 class="text-md font-medium text-gray-900 dark:text-white mb-2">Current Video</h3>
            <?php if ($currentVideo): ?>
                <div class="relative" style="padding-bottom: 56.25%;">
                    <iframe class="absolute top-0 left-0 w-full h-full rounded-lg" 
                            src="https://www.youtube.com/embed/<?php echo $currentVideo['youtube_id']; ?>" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen></iframe>
                </div>
                <div class="space-y-2">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Title: <?php echo htmlspecialchars($currentVideo['title']); ?></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Duration: <?php echo formatDuration($currentVideo['duration']); ?></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Reward: <?php echo $settings['currency_symbol'] ?? 'PKR'; ?> <?php echo number_format($currentVideo['reward_amount'], 2); ?></p>
                </div>
            <?php else: ?>
                <p class="text-gray-500 dark:text-gray-400">No active video set</p>
            <?php endif; ?>

            <div class="mt-4">
                <h3 class="text-md font-medium text-gray-900 dark:text-white mb-2">Update Video</h3>
                <form method="POST" action="update_video.php" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">YouTube Video ID</label>
                        <input type="text" name="youtube_id" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                               placeholder="e.g., dQw4w9WgXcQ">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reward Amount (PKR)</label>
                        <input type="number" step="0.01" name="reward_amount" 
                               value="<?php echo $settings['earnings_per_video'] ?? '5.00'; ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        Update Video
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>

<?php
require_once '../includes/footer.php';
?> 