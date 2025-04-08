<?php
$page_title = "Dashboard";
require_once 'includes/headers.php';
require_once 'includes/header.php';
require_once 'includes/functions.php';
require_once 'includes/video_handler.php';
require_once 'admin/includes/utilities.php';

// Check if user is logged in
if (!isUserLoggedIn()) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();
$user = getCurrentUser();
$videoHandler = new VideoHandler($user['id']);

// Check if user still exists in database
if (!$user) {
    // User has been deleted, destroy session and redirect to login
    session_destroy();
    header('Location: login.php?error=Your account has been deleted');
    exit();
}

// Check if user is active
if (!$user['is_active']) {
    session_destroy();
    header('Location: login.php?error=Your account has been deactivated');
    exit();
}

$totalEarnings = getTotalEarnings($user['id']);

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

// Get current video
$currentVideo = $videoHandler->getNextVideo();

// Get recent activity
$stmt = $conn->prepare("
    SELECT * FROM user_activity 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$user['id']]);
$recentActivity = $stmt->fetchAll();

// Get withdrawal eligibility
$stmt = $conn->prepare("
    SELECT COUNT(*) as video_count 
    FROM user_activity 
    WHERE user_id = ? 
    AND activity_type = 'video_watched' 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
$stmt->execute([$user['id']]);
$dailyVideos = $stmt->fetch(PDO::FETCH_ASSOC)['video_count'];
$canWithdraw = $dailyVideos >= 5 && $totalEarnings >= 10;

// Get settings
$stmt = $conn->prepare("SELECT setting_key, setting_value FROM admin_settings");
$stmt->execute();
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Get pending withdrawals
$stmt = $conn->prepare("
    SELECT COUNT(*) as pending_withdrawals 
    FROM withdrawals 
    WHERE user_id = :user_id 
    AND status = 'pending'
");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$withdrawalStats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <!-- Top Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-800 dark:text-white">Dashboard</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="profile.php" class="text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-white">
                        <i class="fas fa-user"></i> Profile
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
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Total Videos Watched</h3>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400"><?php echo $stats['total_videos']; ?></p>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Videos Watched Today</h3>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400"><?php echo $stats['videos_today']; ?></p>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Total Earnings</h3>
                <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400"><?php echo $settings['currency_symbol'] ?? 'PKR'; ?> <?php echo number_format($stats['total_earnings'], 2); ?></p>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Current Balance</h3>
                <p class="text-3xl font-bold text-purple-600 dark:text-purple-400"><?php echo $settings['currency_symbol'] ?? 'PKR'; ?> <?php echo number_format($user['balance'], 2); ?></p>
            </div>
        </div>

        <!-- Current Video -->
        <?php if ($currentVideo): ?>
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Current Video</h2>
            <div class="relative" style="padding-bottom: 56.25%;">
                <iframe 
                    id="videoPlayer"
                    class="absolute top-0 left-0 w-full h-full"
                    src="https://www.youtube.com/embed/<?php echo $currentVideo['youtube_id']; ?>?enablejsapi=1"
                    frameborder="0"
                    allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen>
                </iframe>
            </div>
            <div class="mt-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white"><?php echo $currentVideo['title']; ?></h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Duration: <?php echo formatDuration($currentVideo['duration']); ?></p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Reward: <?php echo $settings['currency_symbol'] ?? 'PKR'; ?> <?php echo number_format($currentVideo['reward_amount'], 2); ?></p>
            </div>
            <div class="mt-4">
                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                    <div id="progressBar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                </div>
                <p id="progressText" class="text-sm text-gray-500 dark:text-gray-400 mt-1">0% watched</p>
            </div>
        </div>

        <script>
            // Load YouTube API
            var tag = document.createElement('script');
            tag.src = "https://www.youtube.com/iframe_api";
            var firstScriptTag = document.getElementsByTagName('script')[0];
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

            let player;
            let progressInterval;
            let isVideoMarked = false;
            let totalDuration = 0;
            let playerReady = false;

            function onYouTubeIframeAPIReady() {
                console.log('YouTube API is ready');
                if (!document.getElementById('videoPlayer')) {
                    console.error('Video player container not found');
                    return;
                }

                player = new YT.Player('videoPlayer', {
                    height: '100%',
                    width: '100%',
                    videoId: '<?php echo $currentVideo['youtube_id']; ?>',
                    playerVars: {
                        'autoplay': 0,
                        'controls': 1,
                        'rel': 0,
                        'showinfo': 0,
                        'modestbranding': 1,
                        'enablejsapi': 1,
                        'origin': window.location.origin,
                        'widget_referrer': window.location.href,
                        'iv_load_policy': 3,
                        'fs': 0,
                        'playsinline': 1,
                        'disablekb': 1,
                        'cc_load_policy': 0,
                        'hl': 'en',
                        'cc_lang_pref': 'en',
                        'host': window.location.hostname
                    },
                    events: {
                        'onReady': onPlayerReady,
                        'onStateChange': onPlayerStateChange,
                        'onError': onPlayerError
                    }
                });
            }

            function onPlayerReady(event) {
                console.log('Player is ready');
                playerReady = true;
                totalDuration = player.getDuration();
                console.log('Total duration:', totalDuration);
                startProgressTracking();
            }

            function onPlayerStateChange(event) {
                console.log('Player state changed:', event.data);
                
                if (!playerReady) {
                    console.log('Player not ready yet');
                    return;
                }

                switch(event.data) {
                    case YT.PlayerState.PLAYING:
                        console.log('Video is playing');
                        startProgressTracking();
                        break;
                    case YT.PlayerState.PAUSED:
                        console.log('Video is paused');
                        stopProgressTracking();
                        break;
                    case YT.PlayerState.ENDED:
                        console.log('Video has ended');
                        stopProgressTracking();
                        if (!isVideoMarked) {
                            markVideoAsWatched();
                        }
                        break;
                }
            }

            function onPlayerError(event) {
                console.error('Player error:', event.data);
                alert('An error occurred while playing the video. Please try again.');
            }

            function startProgressTracking() {
                if (!progressInterval && playerReady) {
                    progressInterval = setInterval(updateProgress, 1000);
                    console.log('Started progress tracking');
                }
            }

            function stopProgressTracking() {
                if (progressInterval) {
                    clearInterval(progressInterval);
                    progressInterval = null;
                    console.log('Stopped progress tracking');
                }
            }

            function updateProgress() {
                if (player && player.getCurrentTime && playerReady) {
                    const currentTime = player.getCurrentTime();
                    const progress = (currentTime / totalDuration) * 100;
                    
                    // Update progress bar
                    const progressBar = document.getElementById('progressBar');
                    if (progressBar) {
                        progressBar.style.width = progress + '%';
                        progressBar.setAttribute('aria-valuenow', progress);
                    }

                    // Update progress text
                    const progressText = document.getElementById('progressText');
                    if (progressText) {
                        progressText.textContent = Math.round(progress) + '% watched';
                    }

                    console.log('Progress:', progress, '%');
                    
                    // Mark video as watched if progress reaches 90% and not already marked
                    if (progress >= 90 && !isVideoMarked) {
                        markVideoAsWatched();
                    }
                }
            }

            function markVideoAsWatched() {
    if (isVideoMarked || !playerReady) {
        console.log('Video already marked as watched or player not ready');
        return;
    }
    
    isVideoMarked = true;
    stopProgressTracking();
    
    const videoId = '<?php echo $currentVideo['id']; ?>';
    const youtubeId = '<?php echo $currentVideo['youtube_id']; ?>';
    const duration = player.getCurrentTime();

    console.log('Marking video as watched:', {
        video_id: videoId,
        youtube_id: youtubeId,
        duration: duration
    });

    fetch('api/mark_video_watched.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            video_id: videoId,
            youtube_id: youtubeId,
            duration: duration
        }),
        credentials: 'same-origin' // Include cookies if using session authentication
    })
    .then(response => {
        console.log('Status code:', response.status);
        // Clone the response so we can both check status and parse JSON
        const clonedResponse = response.clone();
        
        // Log the raw response text for debugging
        clonedResponse.text().then(text => {
            console.log('Raw response:', text);
        });
        
        if (!response.ok) {
            return response.json().then(errorData => {
                throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
            }).catch(e => {
                // If JSON parsing fails, throw the HTTP status
                throw new Error(`HTTP error! status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            // Update balance display
            const balanceElement = document.getElementById('user-balance');
            if (balanceElement) {
                const currentBalance = parseFloat(balanceElement.textContent);
                const newBalance = currentBalance + data.earnings;
                balanceElement.textContent = newBalance.toFixed(2);
            }
            
            // Show success message
            alert(data.message);
            
            // Redirect to next video after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            console.error('API returned error:', data.message);
            alert(data.message || 'An error occurred while marking the video as watched');
            isVideoMarked = false;
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('An error occurred while marking the video as watched. Please try again.');
        isVideoMarked = false;
    });
}
        </script>
        <?php else: ?>
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <p class="text-gray-500 dark:text-gray-400">No videos available to watch at the moment. Please check back later.</p>
        </div>
        <?php endif; ?>

        <!-- Recent Activity -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Recent Activity</h2>
                <a href="activity.php" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-500">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($recentActivity as $activity): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <i class="fas <?php echo getActivityIcon($activity['activity_type']); ?> text-gray-500 dark:text-gray-400 mr-2"></i>
                                        <span class="text-sm text-gray-900 dark:text-white"><?php echo ucfirst(str_replace('_', ' ', $activity['activity_type'])); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?php echo $settings['currency_symbol'] ?? 'PKR'; ?> <?php echo number_format($activity['amount'], 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusColor($activity['status']); ?>">
                                        <?php echo ucfirst($activity['status']); ?>
                                    </span>
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

<?php require_once 'includes/footer.php'; ?>

<script>
// Theme Toggle
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            html.classList.toggle('dark');
            const isDark = html.classList.contains('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateThemeIcon();
        });

        function updateThemeIcon() {
            const icon = themeToggle.querySelector('i');
            if (icon) {
                if (html.classList.contains('dark')) {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                } else {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                }
            }
        }

        // Initialize theme
        if (localStorage.getItem('theme') === 'dark' || 
            (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            html.classList.add('dark');
        }
        updateThemeIcon();
    }
});

// User Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    const userMenuButton = document.getElementById('userMenuButton');
    const userMenu = document.getElementById('userMenu');

    if (userMenuButton && userMenu) {
        userMenuButton.addEventListener('click', () => {
            userMenu.classList.toggle('hidden');
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
                userMenu.classList.add('hidden');
            }
        });
    }
});
</script>

<?php
// Helper functions
function getActivityIcon($type) {
    switch ($type) {
        case 'video_watched':
            return 'fa-play-circle';
        case 'withdrawal':
            return 'fa-money-bill-wave';
        case 'referral':
            return 'fa-user-plus';
        default:
            return 'fa-circle';
    }
}

function getStatusColor($status) {
    switch ($status) {
        case 'completed':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'pending':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
        case 'failed':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
    }
}

function getNextVideoId() {
    // This should be replaced with your actual video selection logic
    return 'dQw4w9WgXcQ'; // Example video ID
}
?> 