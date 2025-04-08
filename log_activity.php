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
$activityType = $data['type'] ?? '';

if (!in_array($activityType, ['tab_switch', 'inactivity', 'multiple_devices', 'page_refresh'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid activity type']);
    exit();
}

// Log the suspicious activity
logSuspiciousActivity($userId, $activityType, json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT']
]));

echo json_encode(['success' => true]);
?> 