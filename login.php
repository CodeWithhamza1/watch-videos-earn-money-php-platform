<?php
$page_title = "Login";
require_once 'includes/header.php';
require_once 'includes/functions.php';
require_once 'includes/notifications.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$notification = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
        $notification = showNotification($error, 'error');
    } else {
        try {
            // Debug: Log the attempt
            error_log("Login attempt for username: " . $username);
            
            $conn = getDBConnection();
            
            // Debug: Log successful connection
            error_log("Database connection successful");
            
            // Get user data
            $stmt = $conn->prepare("SELECT id, username, password, is_active FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            // Debug: Log query result
            error_log("Query executed. Row count: " . $stmt->rowCount());
            
            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Debug: Log user data (excluding password)
                error_log("User found. ID: " . $user['id'] . ", Username: " . $user['username'] . ", Active: " . $user['is_active']);
                
                if (!$user['is_active']) {
                    $error = 'Your account has been deactivated. Please contact support.';
                    $notification = showNotification($error, 'error');
                } elseif (password_verify($password, $user['password'])) {
                    // Get client IP address
                    $ip_address = $_SERVER['REMOTE_ADDR'];
                    
                    // Generate a simple device fingerprint
                    $device_fingerprint = md5($_SERVER['HTTP_USER_AGENT'] . $ip_address);
                    
                    // Debug: Log update attempt
                    error_log("Attempting to update login info for user ID: " . $user['id']);
                    
                    // Update user login information
                    $updateStmt = $conn->prepare("
                        UPDATE users 
                        SET last_login = NOW(),
                            device_fingerprint = :device_fingerprint,
                            ip_address = :ip_address
                        WHERE id = :id
                    ");
                    
                    $updateStmt->bindParam(':device_fingerprint', $device_fingerprint);
                    $updateStmt->bindParam(':ip_address', $ip_address);
                    $updateStmt->bindParam(':id', $user['id']);
                    
                    if ($updateStmt->execute()) {
                        // Debug: Log successful update
                        error_log("Login info updated successfully for user ID: " . $user['id']);
                        
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        
                        $notification = showNotification('Login successful! Redirecting...', 'success');
                        echo $notification;
                        header('Refresh: 2; URL=dashboard.php');
                        exit();
                    } else {
                        // Debug: Log update failure
                        error_log("Failed to update login info for user ID: " . $user['id']);
                        error_log("Update error info: " . print_r($updateStmt->errorInfo(), true));
                        throw new Exception("Failed to update login information");
                    }
                } else {
                    $error = 'Invalid username or password';
                    $notification = showNotification($error, 'error');
                }
            } else {
                $error = 'Invalid username or password';
                $notification = showNotification($error, 'error');
            }
        } catch (PDOException $e) {
            // Debug: Log detailed PDO error
            error_log("PDO Error during login: " . $e->getMessage());
            error_log("SQL State: " . $e->getCode());
            error_log("Error Info: " . print_r($conn->errorInfo(), true));
            $error = 'Database error occurred. Please try again later.';
            $notification = showNotification($error, 'error');
        } catch (Exception $e) {
            // Debug: Log general error
            error_log("General error during login: " . $e->getMessage());
            $error = 'An error occurred during login. Please try again.';
            $notification = showNotification($error, 'error');
        }
    }
}
?>

<div class="min-h-screen flex items-center justify-center relative overflow-hidden">
    <!-- Background Image with Overlay -->
    <div class="absolute inset-0 z-0">
        <img src="assets/images/login-bg.jpg" alt="Background" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-black bg-opacity-50"></div>
    </div>

    <div class="max-w-md w-full space-y-8 z-10">
        <div class="text-center">
            <h2 class="mt-6 text-3xl font-extrabold text-white">
                Sign in to your account
            </h2>
            <p class="mt-2 text-sm text-gray-300">
                Or
                <a href="register.php" class="font-medium text-blue-400 hover:text-blue-300">
                    create a new account
                </a>
            </p>
        </div>
        <div class="mt-8 bg-white/10 backdrop-blur-lg py-8 px-4 shadow-lg rounded-lg sm:px-10 glass-effect">
            <?php if ($error): ?>
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            <form class="mt-8 space-y-6" action="login.php" method="POST" id="loginForm">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div class="mb-4">
                        <label for="username" class="block text-sm font-medium text-gray-200">Username</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input id="username" name="username" type="text" required 
                                   class="appearance-none block w-full pl-10 px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm bg-white/20 text-black" 
                                   placeholder="Enter your username"
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-200">Password</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input id="password" name="password" type="password" required 
                                   class="appearance-none block w-full pl-10 px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm bg-white/20 text-black" 
                                   placeholder="Enter your password">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="remember-me" class="ml-2 block text-sm text-gray-200">
                            Remember me
                        </label>
                    </div>

                    <div class="text-sm">
                        <a href="forgot-password.php" class="font-medium text-blue-400 hover:text-blue-300">
                            Forgot your password?
                        </a>
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-lg hover:shadow-xl">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-sign-in-alt text-blue-500 group-hover:text-blue-400"></i>
                        </span>
                        Sign in
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
if (isset($notification)) {
    echo $notification;
}
require_once 'includes/footer.php'; 
?> 