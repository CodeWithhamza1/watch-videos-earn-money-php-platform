<?php
$page_title = "Forgot Password";
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
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    if (empty($email)) {
        $error = 'Please enter your email address';
        $notification = showNotification($error, 'error');
    } else {
        try {
            $conn = getDBConnection();
            
            // Check if email exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Store the token
                $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires)");
                $stmt->bindParam(':user_id', $user['id']);
                $stmt->bindParam(':token', $token);
                $stmt->bindParam(':expires', $expires);
                
                if ($stmt->execute()) {
                    // Send email
                    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token=" . $token;
                    $to = $email;
                    $subject = "Password Reset Request";
                    $message = "Hello,\n\n";
                    $message .= "You have requested to reset your password. Click the link below to reset it:\n\n";
                    $message .= $reset_link . "\n\n";
                    $message .= "This link will expire in 1 hour.\n\n";
                    $message .= "If you did not request this password reset, please ignore this email.\n\n";
                    $message .= "Best regards,\nYouTube Watch & Earn Team";

                    $headers = "From: noreply@youtube-watch-earn.com\r\n";
                    $headers .= "Reply-To: noreply@youtube-watch-earn.com\r\n";
                    $headers .= "X-Mailer: PHP/" . phpversion();

                    if (mail($to, $subject, $message, $headers)) {
                        $notification = showNotification('Password reset link has been sent to your email. Please check your inbox.', 'success');
                        header('Refresh: 5; URL=login.php');
                    } else {
                        $error = 'Failed to send reset email. Please try again.';
                        $notification = showNotification($error, 'error');
                    }
                } else {
                    $error = 'Failed to process your request. Please try again.';
                    $notification = showNotification($error, 'error');
                }
            } else {
                $error = 'No account found with that email address.';
                $notification = showNotification($error, 'error');
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
        <img src="assets/images/forgot-password-bg.jpg" alt="Background" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-black bg-opacity-50"></div>
    </div>

    <div class="max-w-md w-full space-y-8 z-10">
        <div class="text-center">
            <h2 class="mt-6 text-3xl font-extrabold text-white">
                Reset Your Password
            </h2>
            <p class="mt-2 text-sm text-gray-300">
                Enter your email address and we'll send you a link to reset your password.
            </p>
        </div>
        <div class="mt-8 bg-white/10 backdrop-blur-lg py-8 px-4 shadow-lg rounded-lg sm:px-10 glass-effect">
            <?php if ($error): ?>
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            <form class="mt-8 space-y-6" action="forgot-password.php" method="POST" id="forgotPasswordForm">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-200">Email address</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input id="email" name="email" type="email" required 
                                   class="appearance-none block w-full pl-10 px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm bg-white/10 text-white" 
                                   placeholder="Enter your email address"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-lg hover:shadow-xl">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-paper-plane text-blue-500 group-hover:text-blue-400"></i>
                        </span>
                        Send Reset Link
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