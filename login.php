<?php
$page_title = "Login";
require_once 'includes/header.php';

// Redirect if already logged in
if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = '';
$email = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    
    // Authenticate user
    $user = authenticate_user($email, $password);
    
    if ($user) {
        // Redirect to intended page or home
        $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'index.php';
        unset($_SESSION['redirect_after_login']);
        header("Location: $redirect");
        exit;
    } else {
        $error = 'Invalid email or password. Please try again.';
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
                <h2 class="text-center mb-4">Login to Your Account</h2>
                
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form id="login-form" action="login.php" method="post">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggle-password" onclick="togglePasswordVisibility('password', 'toggle-password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
                
                <div class="text-center mt-3">
                    <a href="#" class="text-decoration-none">Forgot Password?</a>
                </div>
                
                <hr class="my-4">
                
                <div class="social-login">
                    <p class="text-center mb-3">Or login with:</p>
                    <button id="facebook-login" class="btn btn-outline-primary w-100 social-login-btn mb-2">
                        <i class="fab fa-facebook-f"></i> Continue with Facebook
                    </button>
                    <button id="google-login" class="btn btn-outline-danger w-100 social-login-btn">
                        <i class="fab fa-google"></i> Continue with Google
                    </button>
                    <div id="google-login-container" class="d-none"></div>
                </div>
                
                <div class="text-center mt-4">
                    <p class="mb-0">Don't have an account? <a href="register.php" class="text-decoration-none">Register Now</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
