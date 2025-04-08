<?php
require_once 'includes/utilities.php';
$page_title = "Video Management";
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

// Handle video actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            $conn->beginTransaction();
            
            switch ($_POST['action']) {
                case 'add':
                    // Validate video duration against settings
                    $minDuration = $settings['min_video_duration'] ?? 30;
                    $maxDuration = $settings['max_video_duration'] ?? 600;
                    
                    if ($_POST['duration'] < $minDuration || $_POST['duration'] > $maxDuration) {
                        throw new Exception("Video duration must be between {$minDuration} and {$maxDuration} seconds");
                    }
                    
                    // Validate reward amount
                    $minReward = 0.01;
                    $maxReward = $settings['max_withdrawal'] ?? 1000;
                    
                    if ($_POST['reward_amount'] < $minReward || $_POST['reward_amount'] > $maxReward) {
                        throw new Exception("Reward amount must be between {$minReward} and {$maxReward} " . ($settings['currency_symbol'] ?? 'PKR'));
                    }
                    
                    $stmt = $conn->prepare("
                        INSERT INTO videos (youtube_id, title, description, duration, reward_amount, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_POST['youtube_id'],
                        $_POST['title'],
                        $_POST['description'],
                        $_POST['duration'],
                        $_POST['reward_amount'],
                        isset($_POST['is_active']) ? 1 : 0
                    ]);
                    
                    // Log the action
                    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details) VALUES (?, 'add_video', ?)");
                    $stmt->execute([$_SESSION['user_id'], "Added video: " . $_POST['title']]);
                    
                    $success = "Video added successfully!";
                    break;
                
                case 'update':
                    // Validate video duration against settings
                    $minDuration = $settings['min_video_duration'] ?? 30;
                    $maxDuration = $settings['max_video_duration'] ?? 600;
                    
                    if ($_POST['duration'] < $minDuration || $_POST['duration'] > $maxDuration) {
                        throw new Exception("Video duration must be between {$minDuration} and {$maxDuration} seconds");
                    }
                    
                    // Validate reward amount
                    $minReward = 0.01;
                    $maxReward = $settings['max_withdrawal'] ?? 1000;
                    
                    if ($_POST['reward_amount'] < $minReward || $_POST['reward_amount'] > $maxReward) {
                        throw new Exception("Reward amount must be between {$minReward} and {$maxReward} " . ($settings['currency_symbol'] ?? 'PKR'));
                    }
                    
                    $stmt = $conn->prepare("
                        UPDATE videos 
                        SET title = ?, description = ?, duration = ?, reward_amount = ?, is_active = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $_POST['title'],
                        $_POST['description'],
                        $_POST['duration'],
                        $_POST['reward_amount'],
                        isset($_POST['is_active']) ? 1 : 0,
                        $_POST['video_id']
                    ]);
                    
                    // Log the action
                    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details) VALUES (?, 'update_video', ?)");
                    $stmt->execute([$_SESSION['user_id'], "Updated video ID: " . $_POST['video_id']]);
                    
                    $success = "Video updated successfully!";
                    break;
                
                case 'delete':
                    // Check if video has any views
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM user_activity WHERE details = ? AND activity_type = 'video_watched'");
                    $stmt->execute([$_POST['youtube_id']]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception("Cannot delete video that has been watched");
                    }
                    
                    $stmt = $conn->prepare("DELETE FROM videos WHERE id = ?");
                    $stmt->execute([$_POST['video_id']]);
                    
                    // Log the action
                    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details) VALUES (?, 'delete_video', ?)");
                    $stmt->execute([$_SESSION['user_id'], "Deleted video ID: " . $_POST['video_id']]);
                    
                    $success = "Video deleted successfully!";
                    break;
            }
            
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Error performing action: " . $e->getMessage();
        }
    }
}

// Get all videos with their stats
$stmt = $conn->prepare("
    SELECT 
        v.*,
        COUNT(DISTINCT ua.id) as total_views,
        COALESCE(SUM(ua.amount), 0) as total_earnings,
        COUNT(DISTINCT CASE WHEN DATE(ua.created_at) = CURDATE() THEN ua.id END) as views_today,
        COUNT(DISTINCT CASE WHEN DATE(ua.created_at) = DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN ua.id END) as views_week
    FROM videos v
    LEFT JOIN user_activity ua ON v.youtube_id = ua.details AND ua.activity_type = 'video_watched'
    GROUP BY v.id
    ORDER BY v.created_at DESC
");
$stmt->execute();
$videos = $stmt->fetchAll();
?>
<style>input,textarea{color: black!important;border: 1px solid #A9A9A9!important;padding:10px;}</style>
<div class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <!-- Admin Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-800 dark:text-white">Video Management</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-white">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="settings.php" class="text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-white">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <a href="users.php" class="text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-white">
                        <i class="fas fa-users"></i> Users
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

        <!-- Add New Video Form -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Add New Video</h2>
            <form method="POST" action="" class="space-y-6">
                <input type="hidden" name="action" value="add">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">YouTube Video ID</label>
                        <input type="text" name="youtube_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Enter the video ID from the YouTube URL (e.g., dQw4w9WgXcQ)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                        <input type="text" name="title" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                        <textarea name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Duration (seconds)</label>
                        <input type="number" name="duration" required min="<?php echo $settings['min_video_duration'] ?? 30; ?>" max="<?php echo $settings['max_video_duration'] ?? 600; ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Must be between <?php echo $settings['min_video_duration'] ?? 30; ?> and <?php echo $settings['max_video_duration'] ?? 600; ?> seconds</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reward Amount (<?php echo $settings['currency_symbol'] ?? 'PKR'; ?>)</label>
                        <input type="number" step="0.01" name="reward_amount" required min="0.01" max="<?php echo $settings['max_withdrawal'] ?? 1000; ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Must be between 0.01 and <?php echo $settings['max_withdrawal'] ?? 1000; ?> <?php echo $settings['currency_symbol'] ?? 'PKR'; ?></p>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" checked class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label class="ml-2 block text-sm text-gray-700 dark:text-gray-300">Active</label>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Add Video
                    </button>
                </div>
            </form>
        </div>

        <!-- Videos List -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Video List</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Video</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Views</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Earnings</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($videos as $video): ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <img class="h-10 w-10 rounded" src="https://img.youtube.com/vi/<?php echo $video['youtube_id']; ?>/default.jpg" alt="">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                <?php echo htmlspecialchars($video['title']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                ID: <?php echo $video['youtube_id']; ?>
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Duration: <?php echo formatDuration($video['duration']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $video['is_active'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'; ?>">
                                        <?php echo $video['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?php echo $video['total_views']; ?> total
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        <?php echo $video['views_today']; ?> today
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        <?php echo $video['views_week']; ?> this week
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?php echo $settings['currency_symbol'] ?? 'PKR'; ?> <?php echo number_format($video['total_earnings'], 2); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="editVideo(<?php echo htmlspecialchars(json_encode($video)); ?>)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-2">
                                        Edit
                                    </button>
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="video_id" value="<?php echo $video['id']; ?>">
                                        <input type="hidden" name="youtube_id" value="<?php echo $video['youtube_id']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" onclick="return confirm('Are you sure you want to delete this video? This action cannot be undone.');">
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

<!-- Edit Video Modal -->
<div id="editVideoModal" class="fixed z-10 inset-0 overflow-y-auto hidden">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST" class="p-6">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="video_id" id="edit_video_id">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">YouTube Video ID</label>
                        <input type="text" name="youtube_id" id="edit_youtube_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Enter the video ID from the YouTube URL (e.g., dQw4w9WgXcQ)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                        <input type="text" name="title" id="edit_title" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                        <textarea name="description" id="edit_description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Duration (seconds)</label>
                        <input type="number" name="duration" id="edit_duration" required min="<?php echo $settings['min_video_duration'] ?? 30; ?>" max="<?php echo $settings['max_video_duration'] ?? 600; ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Must be between <?php echo $settings['min_video_duration'] ?? 30; ?> and <?php echo $settings['max_video_duration'] ?? 600; ?> seconds</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reward Amount (<?php echo $settings['currency_symbol'] ?? 'PKR'; ?>)</label>
                        <input type="number" step="0.01" name="reward_amount" id="edit_reward_amount" required min="0.01" max="<?php echo $settings['max_withdrawal'] ?? 1000; ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Must be between 0.01 and <?php echo $settings['max_withdrawal'] ?? 1000; ?> <?php echo $settings['currency_symbol'] ?? 'PKR'; ?></p>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="edit_is_active" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label class="ml-2 block text-sm text-gray-700 dark:text-gray-300">Active</label>
                    </div>
                </div>
                <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2">
                        Update
                    </button>
                    <button type="button" onclick="closeEditModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editVideo(video) {
    document.getElementById('edit_video_id').value = video.id;
    document.getElementById('edit_youtube_id').value = video.youtube_id;
    document.getElementById('edit_title').value = video.title;
    document.getElementById('edit_description').value = video.description;
    document.getElementById('edit_duration').value = video.duration;
    document.getElementById('edit_reward_amount').value = video.reward_amount;
    document.getElementById('edit_is_active').checked = video.is_active == 1;
    document.getElementById('editVideoModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editVideoModal').classList.add('hidden');
}
</script>

<?php require_once '../includes/footer.php'; ?> 