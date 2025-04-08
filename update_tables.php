<?php
require_once 'config/database.php';

try {
    $conn = getDBConnection();
    
    // Add missing columns to users table
    $conn->exec("
        ALTER TABLE users
        ADD COLUMN IF NOT EXISTS balance DECIMAL(10,2) DEFAULT 0.00,
        ADD COLUMN IF NOT EXISTS videos_watched INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS videos_watched_today INT DEFAULT 0
    ");
    
    // Add missing columns to videos table
    $conn->exec("
        ALTER TABLE videos
        ADD COLUMN IF NOT EXISTS watch_count INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS total_earnings DECIMAL(10,2) DEFAULT 0.00
    ");
    
    echo "Database tables updated successfully!";
} catch (PDOException $e) {
    echo "Error updating database tables: " . $e->getMessage();
} 