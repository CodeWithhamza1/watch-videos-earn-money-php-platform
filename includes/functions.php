<?php
session_start();
require_once __DIR__ . '/../config/database.php';

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateDeviceFingerprint() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $ip = $_SERVER['REMOTE_ADDR'];
    return md5($userAgent . $ip);
}

function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function redirect($path) {
    header("Location: $path");
    exit();
}

function getCurrentUser() {
    if (!isUserLoggedIn()) return null;
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT 
            id, username, email, password, is_active, is_admin,
            total_earnings, balance, videos_watched, videos_watched_today,
            created_at, updated_at, last_login, device_fingerprint, ip_address
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function logSuspiciousActivity($userId, $activityType, $details) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO suspicious_logs (user_id, activity_type, details) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $activityType, $details]);
}

function getTotalEarnings($userId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT total_earnings FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['total_earnings'] : 0;
}

function updateUserEarnings($userId, $amount) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE users SET total_earnings = total_earnings + ? WHERE id = ?");
    $stmt->execute([$amount, $userId]);
}

function isAdmin() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user && $user['is_admin'] == 1;
}
?> 