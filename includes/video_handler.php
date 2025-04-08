<?php
require_once 'functions.php';

class VideoHandler {
    private $conn;
    private $user_id;
    private $settings;

    public function __construct($user_id) {
        $this->conn = getDBConnection();
        $this->user_id = $user_id;
        $this->loadSettings();
    }

    private function loadSettings() {
        $stmt = $this->conn->prepare("SELECT setting_key, setting_value FROM admin_settings");
        $stmt->execute();
        $this->settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->settings[$row['setting_key']] = $row['setting_value'];
        }
        
        // Set default values if settings are missing
        $defaults = [
            'min_watch_time' => 30,
            'max_videos_per_day' => 5,
            'earnings_per_video' => 5.00,
            'currency_symbol' => 'PKR',
            'minimum_withdrawal' => 100.00
        ];
        
        foreach ($defaults as $key => $value) {
            if (!isset($this->settings[$key])) {
                $this->settings[$key] = $value;
            }
        }
    }

    public function getNextVideo() {
        // Get a video that the user hasn't watched today
        $stmt = $this->conn->prepare("
            SELECT v.* 
            FROM videos v
            LEFT JOIN user_activity ua ON v.youtube_id = ua.details 
                AND ua.user_id = ? 
                AND ua.activity_type = 'video_watched'
                AND DATE(ua.created_at) = CURDATE()
            WHERE v.is_active = 1 
            AND ua.id IS NULL
            ORDER BY v.id ASC
            LIMIT 1
        ");
        $stmt->execute([$this->user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function markVideoAsWatched($video_id, $youtube_id, $duration) {
        try {
            error_log("Starting markVideoAsWatched for video_id: {$video_id}, youtube_id: {$youtube_id}, duration: {$duration}");
            
            $this->conn->beginTransaction();

            // Check if video was already watched today
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) 
                FROM user_activity 
                WHERE user_id = ? 
                AND details = ? 
                AND activity_type = 'video_watched'
                AND DATE(created_at) = CURDATE()
            ");
            $stmt->execute([$this->user_id, $youtube_id]);
            $alreadyWatched = $stmt->fetchColumn() > 0;
            error_log("Video already watched today: " . ($alreadyWatched ? 'yes' : 'no'));
            
            if ($alreadyWatched) {
                throw new Exception("You have already watched this video today");
            }

            // Check daily video limit
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) 
                FROM user_activity 
                WHERE user_id = ? 
                AND activity_type = 'video_watched'
                AND DATE(created_at) = CURDATE()
            ");
            $stmt->execute([$this->user_id]);
            $videosToday = $stmt->fetchColumn();
            $maxVideos = (int)($this->settings['max_videos_per_day'] ?? 5);
            
            if ($videosToday >= $maxVideos) {
                throw new Exception("You have reached your daily video limit of {$maxVideos} videos");
            }

            // Get video details
            $stmt = $this->conn->prepare("SELECT * FROM videos WHERE id = ? AND is_active = 1");
            $stmt->execute([$video_id]);
            $video = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$video) {
                error_log("Video not found or inactive: id={$video_id}, youtube_id={$youtube_id}");
                throw new Exception("Video not found or inactive");
            }

            error_log("Video found: " . json_encode($video));

            // Convert duration to seconds if it's in HH:MM:SS format
            if (is_string($duration) && strpos($duration, ':') !== false) {
                $parts = explode(':', $duration);
                if (count($parts) === 2) {
                    $duration = (int)$parts[0] * 60 + (int)$parts[1];
                } elseif (count($parts) === 3) {
                    $duration = (int)$parts[0] * 3600 + (int)$parts[1] * 60 + (int)$parts[2];
                }
                error_log("Converted duration from string: {$duration} seconds");
            }

            // Ensure duration is numeric
            $duration = (float)$duration;
            error_log("Final duration: {$duration} seconds");

            // Verify watch duration
            $minWatchTime = (float)($this->settings['min_watch_time'] ?? 30);
            error_log("Minimum watch time: {$minWatchTime} seconds");
            
            if ($duration < $minWatchTime) {
                error_log("Duration {$duration} is less than minimum {$minWatchTime}");
                throw new Exception("You must watch the video for at least {$minWatchTime} seconds");
            }

            // Calculate earnings
            $earnings = (float)($this->settings['earnings_per_video'] ?? 5.00);
            error_log("Calculated earnings: {$earnings}");

            // Record the activity
            $stmt = $this->conn->prepare("
                INSERT INTO user_activity 
                (user_id, activity_type, details, amount, status, watch_progress) 
                VALUES (?, 'video_watched', ?, ?, 'completed', ?)
            ");
            if (!$stmt->execute([$this->user_id, $youtube_id, $earnings, $duration])) {
                error_log("Failed to record activity for user {$this->user_id}, video {$video_id}");
                throw new Exception("Failed to record activity");
            }

            // Update user's total earnings and balance
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET total_earnings = total_earnings + ?,
                    balance = balance + ?,
                    videos_watched = videos_watched + 1,
                    videos_watched_today = videos_watched_today + 1
                WHERE id = ?
            ");
            if (!$stmt->execute([$earnings, $earnings, $this->user_id])) {
                error_log("Failed to update user earnings for user {$this->user_id}");
                throw new Exception("Failed to update user earnings");
            }

            // Update video watch count
            $stmt = $this->conn->prepare("
                UPDATE videos 
                SET watch_count = watch_count + 1,
                    total_earnings = total_earnings + ?
                WHERE id = ?
            ");
            if (!$stmt->execute([$earnings, $video_id])) {
                error_log("Failed to update video statistics for video {$video_id}");
                throw new Exception("Failed to update video statistics");
            }

            $this->conn->commit();
            return [
                'success' => true,
                'earnings' => $earnings,
                'message' => "You earned {$earnings} " . ($this->settings['currency_symbol'] ?? 'PKR')
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Video watch error in VideoHandler: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getWatchProgress($youtube_id) {
        $stmt = $this->conn->prepare("
            SELECT duration 
            FROM videos 
            WHERE youtube_id = ? 
            AND is_active = 1
        ");
        $stmt->execute([$youtube_id]);
        $video = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$video) {
            return null;
        }

        $stmt = $this->conn->prepare("
            SELECT MAX(watch_progress) as progress 
            FROM user_activity 
            WHERE user_id = ? 
            AND details = ? 
            AND activity_type = 'video_watched'
            AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute([$this->user_id, $youtube_id]);
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_duration' => $video['duration'],
            'current_progress' => $progress['progress'] ?? 0
        ];
    }
} 