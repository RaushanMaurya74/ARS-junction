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
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card border-0 shadow-lg overflow-hidden">
                <div class="row g-0">
                    <!-- Image column (visible on medium and larger screens) -->
                    <div class="col-md-5 d-none d-md-block">
                        <div class="bg-primary h-100 d-flex flex-column justify-content-center text-center text-white p-4" style="background: linear-gradient(135deg, var(--primary-color) 0%, #ff7e47 100%);">
                            <div class="my-4 py-4">
                                <img src="images/logo.png" alt="ARS JUNCTION Logo" class="img-fluid mb-4" style="max-width: 150px;">
                                <h2 class="display-6 fw-bold mb-3">Welcome Back!</h2>
                                <p class="lead">Access your account to enjoy delicious food from the best restaurants around you.</p>
                                <p class="mt-4">Your food journey is just a login away.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Login form column -->
                    <div class="col-md-7">
                        <div class="auth-container animate__animated animate__fadeIn p-4 p-md-5">
                            <!-- Mobile logo (visible on small screens) -->
                            <div class="text-center mb-4 d-md-none">
                                <img src="images/logo.png" alt="ARS JUNCTION Logo" class="img-fluid mb-3" style="max-width: 120px;">
                                <h2 class="text-primary">Welcome Back!</h2>
                            </div>
                            
                            <h3 class="fw-bold mb-4 d-none d-md-block">Sign In to Your Account</h3>
                            
                            <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>
                            
                            <form id="login-form" action="login.php" method="post">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                                        <input type="email" class="form-control border-start-0" id="email" name="email" value="<?php echo $email; ?>" placeholder="Your email address" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                                        <input type="password" class="form-control border-start-0 border-end-0" id="password" name="password" placeholder="Your password" required>
                                        <button class="input-group-text bg-light border-start-0" type="button" id="toggle-password" onclick="togglePasswordVisibility('password', 'toggle-password')">
                                            <i class="fas fa-eye text-muted"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                        <label class="form-check-label" for="remember">Remember me</label>
                                    </div>
                                    <a href="#" class="text-decoration-none text-primary fw-semibold">Forgot Password?</a>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 py-2 mb-3">Sign In</button>
                            </form>
                            
                            <div class="position-relative text-center my-4">
                                <hr>
                                <span class="position-absolute top-50 start-50 translate-middle px-3 bg-white text-muted">or continue with</span>
                            </div>
                            
                            <div class="social-login mb-4">
                                <div class="row">
                                    <div class="col-sm-6 mb-2 mb-sm-0">
                                        <button id="facebook-login" class="btn btn-outline-primary w-100 social-login-btn">
                                            <i class="fab fa-facebook-f"></i> Facebook
                                        </button>
                                    </div>
                                    <div class="col-sm-6">
                                        <button id="google-login" class="btn btn-outline-danger w-100 social-login-btn">
                                            <i class="fab fa-google"></i> Google
                                        </button>
                                        <div id="google-login-container" class="d-none"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <p class="mb-0">Don't have an account? <a href="register.php" class="text-decoration-none fw-semibold">Register Now</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
