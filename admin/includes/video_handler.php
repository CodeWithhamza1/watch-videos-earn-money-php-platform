<?php
require_once __DIR__ . '/../../includes/functions.php';

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
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("You have already watched this video today");
            }

            // Get video details
            $stmt = $this->conn->prepare("SELECT * FROM videos WHERE id = ? AND is_active = 1");
            $stmt->execute([$video_id]);
            $video = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$video) {
                throw new Exception("Video not found or inactive");
            }

            // Verify watch duration
            $minWatchTime = $this->settings['min_watch_time'] ?? 30;
            if ($duration < $minWatchTime) {
                throw new Exception("You must watch the video for at least {$minWatchTime} seconds");
            }

            // Calculate earnings
            $earnings = $video['reward_amount'];

            // Record the activity
            $stmt = $this->conn->prepare("
                INSERT INTO user_activity (user_id, activity_type, details, amount) 
                VALUES (?, 'video_watched', ?, ?)
            ");
            $stmt->execute([$this->user_id, $youtube_id, $earnings]);

            // Update user's balance
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET balance = balance + ? 
                WHERE id = ?
            ");
            $stmt->execute([$earnings, $this->user_id]);

            $this->conn->commit();
            return [
                'success' => true,
                'earnings' => $earnings,
                'message' => "You earned {$earnings} " . ($this->settings['currency_symbol'] ?? 'PKR')
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
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