<?php
require_once '../includes/functions.php';
require_once '../includes/video_handler.php';

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isUserLoggedIn()) {
        throw new Exception('Unauthorized', 401);
    }
    
    // Get POST data
    $raw_data = file_get_contents('php://input');
    error_log("Raw POST data: " . $raw_data);
    $data = json_decode($raw_data, true);
   
    if (!$data) {
        error_log("Invalid JSON data received: " . $raw_data);
        throw new Exception('Invalid JSON data', 400);
    }
    
    // Validate required parameters
    $required_params = ['video_id', 'youtube_id', 'duration'];
    foreach ($required_params as $param) {
        if (!isset($data[$param])) {
            error_log("Missing parameter: " . $param);
            error_log("Received data: " . json_encode($data));
            throw new Exception("Missing required parameter: $param", 400);
        }
    }
    
    // Get current user
    $user = getCurrentUser();
    if (!$user) {
        error_log("User not found for ID: " . $_SESSION['user_id']);
        throw new Exception('User not found', 404);
    }
    
    error_log("Processing request for user: " . json_encode([
        'user_id' => $user['id'],
        'username' => $user['username'],
        'is_active' => $user['is_active']
    ]));
    
    // Initialize video handler
    $videoHandler = new VideoHandler($user['id']);
    if (!$videoHandler) {
        error_log("Failed to initialize video handler for user: " . $user['id']);
        throw new Exception('Failed to initialize video handler', 500);
    }
    
    error_log("Attempting to mark video as watched: " . json_encode([
        'video_id' => $data['video_id'],
        'youtube_id' => $data['youtube_id'],
        'duration' => $data['duration'],
        'user_id' => $user['id']
    ]));
    
    // Mark video as watched
    $result = $videoHandler->markVideoAsWatched(
        $data['video_id'],
        $data['youtube_id'],
        $data['duration']
    );
    
    if (!$result['success']) {
        error_log("Failed to mark video as watched: " . $result['message']);
        error_log("Video details: " . json_encode([
            'video_id' => $data['video_id'],
            'youtube_id' => $data['youtube_id'],
            'duration' => $data['duration'],
            'user_id' => $user['id']
        ]));
        throw new Exception($result['message'], 400);
    }
    
    error_log("Successfully marked video as watched: " . json_encode($result));
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => $result['message'],
        'earnings' => $result['earnings']
    ]);
    
} catch (Exception $e) {
    error_log("Video watch error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}