<?php
// Start session and include required files
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$page_title = "Forgot Password";
$error = '';
$success = '';
$step = 1; // 1: Verify Email/Phone, 2: Reset Password

// Handle Cancel Action
if (isset($_GET['action']) && $_GET['action'] === 'cancel') {
    unset($_SESSION['reset_user_id']);
    unset($_SESSION['reset_user_name']);
    header("Location: forgot_password.php");
    exit;
}

// Redirect if already logged in
if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] === 'verify') {
        $email = clean_input($_POST['email']);
        $phone = clean_input($_POST['phone']);
        
        if (empty($email) || empty($phone)) {
            $error = "Please fill in all fields.";
        } else {
            // Find user by email and phone
            $stmt = $conn->prepare("SELECT user_id, name FROM users WHERE email = ? AND phone = ?");
            $stmt->execute([$email, $phone]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $_SESSION['reset_user_id'] = $user['user_id'];
                $_SESSION['reset_user_name'] = $user['name'];
                $step = 2;
            } else {
                $error = "No account found with that email and phone number combination.";
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'reset') {
        if (!isset($_SESSION['reset_user_id'])) {
            header("Location: forgot_password.php");
            exit;
        }
        
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($new_password) || empty($confirm_password)) {
            $error = "Please fill in all fields.";
            $step = 2;
        } elseif (strlen($new_password) < 6) {
            $error = "Password must be at least 6 characters long.";
            $step = 2;
        } elseif ($new_password !== $confirm_password) {
            $error = "Passwords do not match.";
            $step = 2;
        } else {
            $user_id = $_SESSION['reset_user_id'];
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password in DB
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            if ($stmt->execute([$hashed_password, $user_id])) {
                $success = "Your password has been reset successfully! You can now log in.";
                unset($_SESSION['reset_user_id']);
                unset($_SESSION['reset_user_name']);
                $step = 3; // Finished step
            } else {
                $error = "Failed to reset password. Please try again.";
                $step = 2;
            }
        }
    }
} else {
    // If arriving via GET and already verified, check session
    if (isset($_SESSION['reset_user_id'])) {
        $step = 2;
    }
}

// Extra CSS
$extra_css = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">';

// Include header
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-lg p-4 p-md-5 animate__animated animate__fadeIn">
                <div class="text-center mb-4">
                    <img src="images/logo.png" alt="ARS JUNCTION Logo" class="img-fluid mb-3" style="max-width: 120px;">
                    <h2 class="fw-bold text-primary">Forgot Password?</h2>
                    <p class="text-muted">Recover your account credentials quickly and securely</p>
                </div>
                
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i> <?php echo $success; ?></div>
                <?php endif; ?>

                <?php if ($step === 1): ?>
                <!-- Step 1: Verify Email/Phone -->
                <form id="verify-form" action="forgot_password.php" method="post">
                    <input type="hidden" name="action" value="verify">
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Registered Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="e.g. admin@arsjunction.com" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Registered Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-phone text-muted"></i></span>
                            <input type="text" class="form-control" id="phone" name="phone" placeholder="e.g. 7979730721" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2 mb-3">Verify Details</button>
                    <div class="text-center">
                        <a href="login.php" class="text-decoration-none fw-semibold"><i class="fas fa-arrow-left me-1"></i> Back to Login</a>
                    </div>
                </form>

                <?php elseif ($step === 2): ?>
                <!-- Step 2: Reset Password -->
                <div class="alert alert-info py-2 mb-4">
                    Hi, <strong><?php echo htmlspecialchars($_SESSION['reset_user_name'] ?? ''); ?></strong>. Please set your new password below.
                </div>
                
                <form id="reset-form" action="forgot_password.php" method="post">
                    <input type="hidden" name="action" value="reset">
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="At least 6 characters" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-type new password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2 mb-3">Reset Password</button>
                    <div class="text-center">
                        <a href="forgot_password.php?action=cancel" class="text-decoration-none fw-semibold">Cancel and Start Over</a>
                    </div>
                </form>

                <?php elseif ($step === 3): ?>
                <!-- Step 3: Success state -->
                <div class="text-center">
                    <a href="login.php" class="btn btn-primary px-4 py-2 mt-2">Log In Now</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
