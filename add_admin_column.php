<?php
require_once 'includes/functions.php';

try {
    $conn = getDBConnection();
    
    // Add is_admin column if it doesn't exist
    $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin TINYINT(1) DEFAULT 0");
    
    // Create admin user
    $username = 'Muhammad Hamza';
    $email = 'maharhamza200019@gmail.com';
    $password = 'Hamza@@221122!@#';
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if admin user already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        // Update existing user to admin
        $stmt = $conn->prepare("UPDATE users SET is_admin = 1, password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $email]);
        echo "Existing user updated to admin successfully!";
    } else {
        // Insert new admin user
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, password, is_admin, is_active) 
            VALUES (?, ?, ?, 1, 1)
        ");
        $stmt->execute([$username, $email, $hashedPassword]);
        echo "Admin user created successfully!";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 