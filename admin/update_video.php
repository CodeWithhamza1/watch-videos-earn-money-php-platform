<?php
require_once '../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $youtubeId = $_POST['youtube_id'] ?? '';
    $rewardAmount = $_POST['reward_amount'] ?? 5.00;

    if (empty($youtubeId)) {
        header('Location: settings.php?error=Please provide a YouTube video ID');
        exit();
    }

    $conn = getDBConnection();

    try {
        // Get video details from YouTube API
        $videoDetails = getYouTubeVideoDetails($youtubeId);
        
        if (!$videoDetails) {
            header('Location: settings.php?error=Invalid YouTube video ID');
            exit();
        }

        // Start transaction
        $conn->beginTransaction();

        // Deactivate current active video
        $stmt = $conn->prepare("UPDATE videos SET is_active = 0 WHERE is_active = 1");
        $stmt->execute();

        // Insert new video
        $stmt = $conn->prepare("
            INSERT INTO videos (youtube_id, title, description, duration, reward_amount, is_active) 
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $youtubeId,
            $videoDetails['title'],
            $videoDetails['description'],
            $videoDetails['duration'],
            $rewardAmount
        ]);

        // Log the change
        $stmt = $conn->prepare("
            INSERT INTO admin_logs (admin_id, action, details) 
            VALUES (?, 'video_update', ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            json_encode([
                'youtube_id' => $youtubeId,
                'title' => $videoDetails['title'],
                'reward_amount' => $rewardAmount
            ])
        ]);

        // Commit transaction
        $conn->commit();

        header('Location: settings.php?success=Video updated successfully');
        exit();
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        header('Location: settings.php?error=' . urlencode($e->getMessage()));
        exit();
    }
} else {
    header('Location: settings.php');
    exit();
}

function getYouTubeVideoDetails($videoId) {
    // You'll need to implement this function to fetch video details from YouTube API
    // For now, return dummy data
    return [
        'title' => 'Sample Video',
        'description' => 'Sample Description',
        'duration' => 300 // 5 minutes in seconds
    ];
}
?> 