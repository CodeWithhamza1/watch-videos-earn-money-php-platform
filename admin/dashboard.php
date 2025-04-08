<?php
$page_title = "Admin Dashboard";
require_once '../includes/header.php';
require_once '../includes/functions.php';
require_once 'includes/video_handler.php';

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

// Get real-time statistics
$stats = [
    'total_users' => 0,
    'active_users' => 0,
    'total_earnings' => 0,
    'today_earnings' => 0,
    'total_videos' => 0,
    'active_videos' => 0,
    'total_withdrawals' => 0,
    'pending_withdrawals' => 0
];

// Total and Active Users
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
    FROM users
");
$stmt->execute();
$userStats = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['total_users'] = $userStats['total'];
$stats['active_users'] = $userStats['active'];

// Earnings Statistics
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(amount), 0) as total,
        COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN amount ELSE 0 END), 0) as today
    FROM user_activity 
    WHERE activity_type = 'video_watched'
");
$stmt->execute();
$earningStats = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['total_earnings'] = $earningStats['total'];
$stats['today_earnings'] = $earningStats['today'];

// Video Statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
    FROM videos
");
$stmt->execute();
$videoStats = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['total_videos'] = $videoStats['total'];
$stats['active_videos'] = $videoStats['active'];

// Withdrawal Statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
    FROM withdrawals
");
$stmt->execute();
$withdrawalStats = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['total_withdrawals'] = $withdrawalStats['total'];
$stats['pending_withdrawals'] = $withdrawalStats['pending'];

// Get recent activity
$stmt = $conn->prepare("
    SELECT 
        ua.*,
        u.username,
        v.title as video_title
    FROM user_activity ua
    LEFT JOIN users u ON ua.user_id = u.id
    LEFT JOIN videos v ON ua.details = v.youtube_id AND ua.activity_type = 'video_watched'
    ORDER BY ua.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recentActivity = $stmt->fetchAll();

// Get user growth data for chart
$stmt = $conn->prepare("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as count
    FROM users
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$stmt->execute();
$userGrowth = $stmt->fetchAll();

// Get earnings data for chart
$stmt = $conn->prepare("
    SELECT 
        DATE(created_at) as date,
        SUM(amount) as total
    FROM user_activity
    WHERE activity_type = 'video_watched'
    AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$stmt->execute();
$earningsData = $stmt->fetchAll();
?>

<div class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <!-- Admin Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-800 dark:text-white">Admin Dashboard</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="settings.php" class="text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-white">
                        <i class="fas fa-cog"></i> Settings
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
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Total Users</h3>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400"><?php echo $stats['total_users']; ?></p>
                <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo $stats['active_users']; ?> Active</p>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Total Earnings</h3>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400"><?php echo $settings['currency_symbol'] ?? 'PKR'; ?> <?php echo number_format($stats['total_earnings'], 2); ?></p>
                <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo $settings['currency_symbol'] ?? 'PKR'; ?> <?php echo number_format($stats['today_earnings'], 2); ?> Today</p>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Videos</h3>
                <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400"><?php echo $stats['total_videos']; ?></p>
                <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo $stats['active_videos']; ?> Active</p>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Withdrawals</h3>
                <p class="text-3xl font-bold text-purple-600 dark:text-purple-400"><?php echo $stats['total_withdrawals']; ?></p>
                <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo $stats['pending_withdrawals']; ?> Pending</p>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- User Growth Chart -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">User Growth (Last 30 Days)</h3>
                <canvas id="userGrowthChart"></canvas>
            </div>
            <!-- Earnings Chart -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Earnings (Last 30 Days)</h3>
                <canvas id="earningsChart"></canvas>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Recent Activity</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Activity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($recentActivity as $activity): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($activity['username']); ?></div>
                                </td>
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
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// User Growth Chart
const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
new Chart(userGrowthCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($userGrowth, 'date')); ?>,
        datasets: [{
            label: 'New Users',
            data: <?php echo json_encode(array_column($userGrowth, 'count')); ?>,
            borderColor: 'rgb(59, 130, 246)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Earnings Chart
const earningsCtx = document.getElementById('earningsChart').getContext('2d');
new Chart(earningsCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($earningsData, 'date')); ?>,
        datasets: [{
            label: 'Earnings',
            data: <?php echo json_encode(array_column($earningsData, 'total')); ?>,
            backgroundColor: 'rgb(34, 197, 94)',
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '<?php echo $settings['currency_symbol'] ?? 'PKR'; ?> ' + value.toFixed(2);
                    }
                }
            }
        }
    }
});

// Auto-refresh dashboard every 5 minutes
setInterval(() => {
    location.reload();
}, 300000);
</script>

<?php
require_once '../includes/footer.php';
?> 