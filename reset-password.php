<?php
$page_title = "Reset Password";
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
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
        $notification = showNotification($error, 'error');
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
        $notification = showNotification($error, 'error');
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
        $notification = showNotification($error, 'error');
    } else {
        try {
            $conn = getDBConnection();
            
            // Verify token
            $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = :token AND expires_at > NOW() AND used = 0");
            $stmt->bindParam(':token', $token);
            $stmt->execute();

            if ($stmt->rowCount() === 1) {
                $reset = $stmt->fetch(PDO::FETCH_ASSOC);
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Update password
                $stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :user_id");
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':user_id', $reset['user_id']);
                
                if ($stmt->execute()) {
                    // Mark token as used
                    $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = :token");
                    $stmt->bindParam(':token', $token);
                    $stmt->execute();

                    $notification = showNotification('Password has been reset successfully. You can now login with your new password.', 'success');
                    header('Refresh: 3; URL=login.php');
                    exit();
                } else {
                    $error = 'Failed to reset password. Please try again.';
                    $notification = showNotification($error, 'error');
                }
            } else {
                $error = 'Invalid or expired reset link. Please request a new one.';
                $notification = showNotification($error, 'error');
                header('Refresh: 3; URL=forgot-password.php');
                exit();
            }
        } catch (PDOException $e) {
            $error = 'Database error occurred. Please try again later.';
            $notification = showNotification($error, 'error');
        }
    }
}
?>

<div class="min-h-screen flex items-center justify-center relative overflow-hidden">
    <!-- Background Image with Overlay -->
    <div class="absolute inset-0 z-0">
        <img src="assets/images/reset-password-bg.jpg" alt="Background" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-black bg-opacity-50"></div>
    </div>

    <div class="max-w-md w-full space-y-8 z-10">
        <div class="text-center">
            <h2 class="mt-6 text-3xl font-extrabold text-white">
                Reset Your Password
            </h2>
            <p class="mt-2 text-sm text-gray-300">
                Enter your new password below.
            </p>
        </div>
        <div class="mt-8 bg-white/10 backdrop-blur-lg py-8 px-4 shadow-lg rounded-lg sm:px-10 glass-effect">
            <?php if ($error): ?>
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            <form class="mt-8 space-y-6" action="reset-password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST" id="resetPasswordForm">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-200">New Password</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input id="password" name="password" type="password" required 
                                   class="appearance-none block w-full pl-10 px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm bg-white/10 text-white" 
                                   placeholder="Enter your new password">
                        </div>
                    </div>
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-200">Confirm New Password</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input id="confirm_password" name="confirm_password" type="password" required 
                                   class="appearance-none block w-full pl-10 px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm bg-white/10 text-white" 
                                   placeholder="Confirm your new password">
                        </div>
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-lg hover:shadow-xl">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-key text-blue-500 group-hover:text-blue-400"></i>
                        </span>
                        Reset Password
                    </button>
                </div>

                <div class="text-center">
                    <a href="login.php" class="font-medium text-blue-400 hover:text-blue-300">
                        Back to Login
                    </a>
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