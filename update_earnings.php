<?php
require_once 'includes/functions.php';

if (!isUserLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];
$watchTime = $data['watchTime'] ?? 0;
$earnings = $data['earnings'] ?? 0;

if ($watchTime > 0 && $earnings > 0) {
    $conn = getDBConnection();
    
    // Check if user has reached the 300 PKR limit
    $stmt = $conn->prepare("SELECT total_earnings FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user['total_earnings'] >= 300) {
        echo json_encode(['message' => 'Earnings limit reached']);
        exit();
    }
    
    // Update watch log
    $stmt = $conn->prepare("INSERT INTO watch_logs (user_id, watch_duration, earnings) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $watchTime, $earnings]);
    
    // Update total earnings
    updateUserEarnings($userId, $earnings);
    
    echo json_encode(['success' => true]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
}
?> 