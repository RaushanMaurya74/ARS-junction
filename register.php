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
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card border-0 shadow-lg overflow-hidden">
                <div class="row g-0">
                    <!-- Image column (visible on medium and larger screens) -->
                    <div class="col-md-5 d-none d-md-block">
                        <div class="bg-primary h-100 d-flex flex-column justify-content-center text-center text-white p-4" style="background: linear-gradient(135deg, var(--secondary-color) 0%, #2E7D32 100%);">
                            <div class="my-4 py-4">
                                <img src="images/logo.png" alt="ARS JUNCTION Logo" class="img-fluid mb-4" style="max-width: 150px;">
                                <h2 class="display-6 fw-bold mb-3">Join Our Community!</h2>
                                <p class="lead">Create an account to unlock a world of delicious food from your favorite restaurants.</p>
                                <p class="mt-4">Easy ordering, fast delivery, and amazing food are just a click away.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Registration form column -->
                    <div class="col-md-7">
                        <div class="auth-container animate__animated animate__fadeIn p-4 p-md-5">
                            <!-- Mobile logo (visible on small screens) -->
                            <div class="text-center mb-4 d-md-none">
                                <img src="images/logo.png" alt="ARS JUNCTION Logo" class="img-fluid mb-3" style="max-width: 120px;">
                                <h2 class="text-secondary">Create an Account</h2>
                            </div>
                            
                            <h3 class="fw-bold mb-4 d-none d-md-block">Sign Up for Free</h3>
                            
                            <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>
                            
                            <?php if (!empty($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                            <?php endif; ?>
                            
                            <form id="register-form" action="register.php" method="post">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                                        <input type="text" class="form-control border-start-0" id="name" name="name" value="<?php echo $name; ?>" placeholder="Your full name" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                                        <input type="email" class="form-control border-start-0" id="email" name="email" value="<?php echo $email; ?>" placeholder="Your email address" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-phone text-muted"></i></span>
                                        <input type="tel" class="form-control border-start-0" id="phone" name="phone" value="<?php echo $phone; ?>" placeholder="Your phone number (optional)">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                                        <input type="password" class="form-control border-start-0 border-end-0" id="password" name="password" placeholder="Create a password" required>
                                        <button class="input-group-text bg-light border-start-0" type="button" id="toggle-password" onclick="togglePasswordVisibility('password', 'toggle-password')">
                                            <i class="fas fa-eye text-muted"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Password must be at least 6 characters long.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm-password" class="form-label">Confirm Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                                        <input type="password" class="form-control border-start-0 border-end-0" id="confirm-password" name="confirm_password" placeholder="Confirm your password" required>
                                        <button class="input-group-text bg-light border-start-0" type="button" id="toggle-confirm-password" onclick="togglePasswordVisibility('confirm-password', 'toggle-confirm-password')">
                                            <i class="fas fa-eye text-muted"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-4 form-check">
                                    <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                    <label class="form-check-label" for="terms">I agree to the <a href="#" class="text-decoration-none">Terms of Service</a> and <a href="#" class="text-decoration-none">Privacy Policy</a></label>
                                </div>
                                <button type="submit" class="btn btn-secondary w-100 py-2 mb-3">Create Account</button>
                            </form>
                            
                            <div class="position-relative text-center my-4">
                                <hr>
                                <span class="position-absolute top-50 start-50 translate-middle px-3 bg-white text-muted">or sign up with</span>
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
                                <p class="mb-0">Already have an account? <a href="login.php" class="text-decoration-none fw-semibold">Sign In</a></p>
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
