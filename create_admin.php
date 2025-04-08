<?php
require_once 'includes/functions.php';

$username = 'Muhammad Hamza';
$email = 'maharhamza200019@gmail.com';
$password = 'Hamza@@221122!@#';

try {
    $conn = getDBConnection();
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert the admin user
    $stmt = $conn->prepare("
        INSERT INTO users (username, email, password, is_admin, is_active) 
        VALUES (?, ?, ?, 1, 1)
    ");
    
    $stmt->execute([$username, $email, $hashedPassword]);
    
    echo "Admin user created successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 