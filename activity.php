<?php
$page_title = "My Activity";
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isUserLoggedIn()) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();
$user = getCurrentUser();

// Get admin settings for currency
$stmt = $conn->prepare("SELECT setting_key, setting_value FROM admin_settings");
$stmt->execute();
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get total activities count
$stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM user_activity 
    WHERE user_id = ?
");
$stmt->execute([$user['id']]);
$totalActivities = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalActivities / $perPage);

// Get user activities with pagination
$stmt = $conn->prepare("
    SELECT 
        ua.*,
        v.title as video_title
    FROM user_activity ua
    LEFT JOIN videos v ON ua.details = v.youtube_id AND ua.activity_type = 'video_watched'
    WHERE ua.user_id = ?
    ORDER BY ua.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$user['id'], $perPage, $offset]);
$activities = $stmt->fetchAll();

// Calculate total earnings
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) as total_earnings
    FROM user_activity
    WHERE user_id = ?
    AND activity_type = 'video_watched'
");
$stmt->execute([$user['id']]);
$totalEarnings = $stmt->fetch(PDO::FETCH_ASSOC)['total_earnings'];
?>

<div class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Activity Overview -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Activity Overview</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Activities</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $totalActivities; ?></p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Earnings</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        <?php echo $settings['currency_symbol'] ?? 'PKR'; ?> <?php echo number_format($totalEarnings, 2); ?>
                    </p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Videos Watched</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        <?php 
                        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_activity WHERE user_id = ? AND activity_type = 'video_watched'");
                        $stmt->execute([$user['id']]);
                        echo $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Activity List -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Activity History</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Activity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($activities as $activity): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?php 
                                        $activityType = str_replace('_', ' ', $activity['activity_type']);
                                        if ($activity['activity_type'] == 'video_watched' && $activity['video_title']) {
                                            echo "Watched: " . htmlspecialchars($activity['video_title']);
                                        } else {
                                            echo ucfirst($activityType);
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?php echo $settings['currency_symbol'] ?? 'PKR'; ?> <?php echo number_format($activity['amount'], 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="mt-4 flex justify-center">
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <span class="sr-only">Previous</span>
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white dark:bg-gray-700 text-sm font-medium <?php echo $i == $page ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <span class="sr-only">Next</span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 