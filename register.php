<?php
$page_title = "Register";
require_once 'includes/header.php';

// Redirect if already logged in
if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';
$name = '';
$email = '';
$phone = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = clean_input($_POST['name']);
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = clean_input($_POST['phone']);
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (email_exists($email)) {
        $error = 'Email already exists. Please login or use a different email.';
    } else {
        // Register user
        $result = register_user($name, $email, $password, $phone);
        
        if ($result['success']) {
            $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'index.php';
            unset($_SESSION['redirect_after_login']);
            header("Location: $redirect");
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// Extra CSS and JS
$extra_css = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">';
$extra_js = '<script src="js/auth.js"></script>';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="auth-container animate__animated animate__fadeIn">
                <h2 class="text-center mb-4">Create an Account</h2>
                
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form id="register-form" action="register.php" method="post">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $name; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo $phone; ?>" placeholder="Optional">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggle-password" onclick="togglePasswordVisibility('password', 'toggle-password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Password must be at least 6 characters long.</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm-password" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm-password" name="confirm_password" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggle-confirm-password" onclick="togglePasswordVisibility('confirm-password', 'toggle-confirm-password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">I agree to the <a href="#" class="text-decoration-none">Terms of Service</a> and <a href="#" class="text-decoration-none">Privacy Policy</a></label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Register</button>
                </form>
                
                <hr class="my-4">
                
                <div class="social-login">
                    <p class="text-center mb-3">Or register with:</p>
                    <button id="facebook-login" class="btn btn-outline-primary w-100 social-login-btn mb-2">
                        <i class="fab fa-facebook-f"></i> Continue with Facebook
                    </button>
                    <button id="google-login" class="btn btn-outline-danger w-100 social-login-btn">
                        <i class="fab fa-google"></i> Continue with Google
                    </button>
                    <div id="google-login-container" class="d-none"></div>
                </div>
                
                <div class="text-center mt-4">
                    <p class="mb-0">Already have an account? <a href="login.php" class="text-decoration-none">Login Now</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
