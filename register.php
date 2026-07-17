<?php
// Start session and include required files
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

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

// Helper function to check if email is real (not temporary/disposable)
function is_real_email($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    $parts = explode('@', $email);
    $domain = strtolower(end($parts));
    
    $disposable_domains = [
        'mailinator.com', '10minutemail.com', 'tempmail.com', 'temp-mail.org',
        'yopmail.com', 'guerrillamail.com', 'sharklasers.com', 'dispostable.com',
        'getairmail.com', 'maildrop.cc', 'tempmailaddress.com', 'throwawaymail.com',
        'tempmail.net', 'fakeinbox.com', 'trashmail.com'
    ];
    
    if (in_array($domain, $disposable_domains)) {
        return false;
    }
    
    return true;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = clean_input($_POST['name']);
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = clean_input($_POST['phone']);
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($password) || empty($phone)) {
        $error = 'Please fill in all required fields.';
    } elseif (!preg_match('/^[6-9]\d{9}$/', $phone)) {
        $error = 'Please enter a valid 10-digit Indian mobile number (starting with 6, 7, 8, or 9).';
    } elseif (!is_real_email($email)) {
        $error = 'Please enter a real, active email address. Disposable emails are not allowed.';
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
            send_welcome_email($result['user_id']);
            $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'index.php';
            unset($_SESSION['redirect_after_login']);
            header("Location: $redirect");
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — ARS Junction</title>
    <meta name="description" content="Create a new ARS Junction account and start ordering food.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --brand:      #2e7d32; /* Organic Green for Customer Registration */
            --brand-dark: #1b5e20;
            --brand-glow: rgba(46, 125, 50, 0.35);
            --text-dark:  #111827;
            --text-mid:   #6b7280;
            --text-light: #9ca3af;
            --border:     #e5e7eb;
            --input-bg:   #f9fafb;
            --white:      #ffffff;
        }

        html, body {
            height: 100%;
            font-family: 'Inter', sans-serif;
            background: #f3f4f6;
        }

        .login-wrap {
            display: flex;
            height: 100vh;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .login-card {
            display: flex;
            width: 100%;
            max-width: 960px;
            min-height: 640px;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0,0,0,.18);
            animation: fadeUp .5s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── LEFT PANEL ─────────────────────────────── */
        .panel-left {
            flex: 1;
            position: relative;
            background: url('images/restaurant_3.jpg') center/cover no-repeat;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 2.25rem 2rem;
            color: var(--white);
            min-width: 0;
        }

        .panel-left::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                160deg,
                rgba(46, 125, 50, 0.90) 0%,
                rgba(27, 94, 32, 0.75) 50%,
                rgba(130, 200, 135, 0.20) 100%
            );
        }

        .panel-left > * { position: relative; z-index: 1; }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: .6rem;
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: -.5px;
        }

        .brand-tagline {
            margin-top: .5rem;
            font-size: .82rem;
            font-weight: 400;
            color: rgba(255,255,255,.7);
            max-width: 220px;
            line-height: 1.5;
        }

        .customer-badge {
            background: rgba(255,255,255,.12);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 16px;
            padding: 1.1rem 1.2rem;
        }

        .customer-badge .badge-top {
            display: flex;
            align-items: center;
            gap: .85rem;
        }

        .cust-avatar {
            width: 44px; height: 44px;
            border-radius: 12px;
            background: var(--brand);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
            box-shadow: 0 4px 12px var(--brand-glow);
        }

        .cust-name { font-weight: 700; font-size: .95rem; line-height: 1.2; }
        .cust-tier {
            font-size: .65rem;
            font-weight: 700;
            letter-spacing: .08em;
            color: rgba(255,255,255,.6);
            text-transform: uppercase;
            margin-top: 2px;
        }

        .status-bar { margin-top: .9rem; }
        .status-label {
            font-size: .7rem;
            color: rgba(255,255,255,.55);
            margin-bottom: .35rem;
        }
        .status-track {
            height: 4px;
            background: rgba(255,255,255,.18);
            border-radius: 4px;
            overflow: hidden;
        }
        .status-fill {
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, #22c55e, #86efac);
            border-radius: 4px;
            animation: fillIn 1.2s ease .5s forwards;
        }
        @keyframes fillIn { to { width: 100%; } }

        .panel-footer {
            font-size: .7rem;
            color: rgba(255,255,255,.4);
        }

        /* ── RIGHT PANEL ─────────────────────────────── */
        .panel-right {
            width: 440px;
            flex-shrink: 0;
            background: var(--white);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2.2rem 2.8rem;
            overflow-y: auto;
        }

        .form-heading { font-size: 1.75rem; font-weight: 800; color: var(--text-dark); line-height: 1.15; }
        .form-subhead { font-size: .85rem; color: var(--text-mid); margin-top: .35rem; margin-bottom: 1.5rem; }

        .error-banner {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: .75rem 1rem;
            font-size: .82rem;
            color: #991b1b;
            display: flex;
            align-items: center;
            gap: .6rem;
            margin-bottom: 1.25rem;
            animation: shake .4s ease;
        }
        @keyframes shake {
            0%,100% { transform: translateX(0); }
            20%,60%  { transform: translateX(-6px); }
            40%,80%  { transform: translateX(6px); }
        }

        .field-label {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: var(--text-mid);
            margin-bottom: .35rem;
        }

        .input-wrap {
            position: relative;
            margin-bottom: 0.9rem;
        }
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: .85rem;
            pointer-events: none;
        }
        .form-input {
            width: 100%;
            height: 44px;
            padding: 0 1rem 0 2.65rem;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            background: var(--input-bg);
            font-family: 'Inter', sans-serif;
            font-size: .88rem;
            color: var(--text-dark);
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .form-input::placeholder { color: var(--text-light); }
        .form-input:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px var(--brand-glow);
            background: var(--white);
        }

        .pw-wrap { position: relative; }
        .pw-wrap .form-input { padding-right: 3rem; }
        .pw-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            padding: 0;
            font-size: .9rem;
            transition: color .2s;
        }
        .pw-toggle:hover { color: var(--brand); }

        .btn-login {
            width: 100%;
            height: 50px;
            background: var(--brand);
            color: var(--white);
            font-family: 'Inter', sans-serif;
            font-size: .95rem;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .6rem;
            letter-spacing: -.01em;
            transition: background .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 4px 14px var(--brand-glow);
            margin-top: 0.5rem;
        }
        .btn-login:hover {
            background: var(--brand-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px var(--brand-glow);
        }
        .btn-login:active { transform: translateY(0); }

        .register-row {
            margin-top: 1.25rem;
            text-align: center;
            font-size: .82rem;
            color: var(--text-mid);
        }
        .register-row a {
            color: var(--brand);
            font-weight: 700;
            text-decoration: none;
        }
        .register-row a:hover { text-decoration: underline; }

        .divider {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin: 1.5rem 0 1rem;
            color: var(--text-light);
            font-size: .75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .07em;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }
        .social-login-btn {
            border: 1px solid #dadce0;
            background: var(--white);
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 40px;
            cursor: pointer;
            transition: border-color .2s, background .2s;
        }
        .social-login-btn:hover {
            border-color: var(--brand);
            background: #f1f8f2;
        }
        .social-login-btn i { font-size: 0.9rem; }

        @media (max-width: 720px) {
            .login-card { flex-direction: column; max-width: 420px; border-radius: 20px; height: 95vh; }
            .panel-left  { min-height: 160px; padding: 1.2rem; }
            .panel-right { width: 100%; padding: 1.5rem; }
            .customer-badge { display: none; }
        }
    </style>
</head>
<body>

<div class="login-wrap">
    <div class="login-card">

        <!-- ═══ LEFT PANEL ═══════════════════════════════════ -->
        <div class="panel-left">
            <div>
                <div class="brand-logo">
                    <img src="images/ars_logo.png" alt="ARS Logo" style="height: 38px; width: auto; object-fit: contain; border-radius: 4px;">
                    ARS Junction
                </div>
                <p class="brand-tagline">Satisfying your cravings with surgical precision.</p>
            </div>

            <div class="customer-badge">
                <div class="badge-top">
                    <div class="cust-avatar"><i class="fa-solid fa-leaf"></i></div>
                    <div>
                        <div class="cust-name">Fresh Choices</div>
                        <div class="cust-tier">Green & Organic</div>
                    </div>
                </div>
                <div class="status-bar">
                    <div class="status-label">
                        <i class="fa-solid fa-seedling" style="color:#81c784;font-size:.55rem;vertical-align:middle;margin-right:4px;"></i>
                        Healthy Dining Registry: Enabled
                    </div>
                    <div class="status-track"><div class="status-fill"></div></div>
                </div>
            </div>

            <div class="panel-footer">&copy; 2024 ARS Junction Intelligence Systems</div>
        </div>

        <!-- ═══ RIGHT PANEL ══════════════════════════════════ -->
        <div class="panel-right">
            <h1 class="form-heading">Create Account</h1>
            <p class="form-subhead">Join today and discover great food around you.</p>

            <?php if (!empty($error)): ?>
                <div class="error-banner">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="post" autocomplete="off">
                <!-- Name -->
                <div>
                    <div class="field-label">Full Name</div>
                    <div class="input-wrap">
                        <i class="fa-regular fa-user input-icon"></i>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-input"
                            placeholder="John Doe"
                            value="<?php echo htmlspecialchars($name); ?>"
                            required
                        >
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <div class="field-label">Email Address</div>
                    <div class="input-wrap">
                        <i class="fa-regular fa-envelope input-icon"></i>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-input"
                            placeholder="you@example.com"
                            value="<?php echo htmlspecialchars($email); ?>"
                            required
                        >
                    </div>
                </div>

                <!-- Phone -->
                <div>
                    <div class="field-label">Mobile Number</div>
                    <div class="input-wrap">
                        <i class="fa-solid fa-phone input-icon"></i>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            class="form-input"
                            placeholder="9876543210"
                            value="<?php echo htmlspecialchars($phone); ?>"
                            required
                        >
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <div class="field-label">Password</div>
                    <div class="input-wrap pw-wrap">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            placeholder="Min 6 characters"
                            required
                        >
                        <button type="button" class="pw-toggle" id="pwToggle" aria-label="Toggle password visibility">
                            <i class="fa-regular fa-eye" id="pwIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div>
                    <div class="field-label">Confirm Password</div>
                    <div class="input-wrap pw-wrap">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="form-input"
                            placeholder="Repeat password"
                            required
                        >
                        <button type="button" class="pw-toggle" id="pwToggleConfirm" aria-label="Toggle password visibility">
                            <i class="fa-regular fa-eye" id="pwIconConfirm"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-login" id="registerBtn">
                    Create Account <i class="fa-solid fa-user-plus"></i>
                </button>
            </form>

            <!-- Social Logins -->
            <?php 
            $fb_enabled = get_site_setting('facebook_login_enabled', '0');
            $google_enabled = get_site_setting('google_login_enabled', '1');
            
            if ($fb_enabled == '1' || $google_enabled == '1'): 
            ?>
            <div class="divider">or sign up with</div>
            <div class="row g-2 justify-content-center" style="display: flex; gap: 0.5rem;">
                <?php if ($fb_enabled == '1'): ?>
                <div style="flex: 1;">
                    <button id="facebook-login" class="social-login-btn w-100" style="color: #1877f2; border-color: #e5e7eb;">
                        <i class="fab fa-facebook-f" style="margin-right: 6px;"></i> Facebook
                    </button>
                </div>
                <?php endif; ?>
                
                <?php if ($google_enabled == '1'): ?>
                <div style="flex: 1;">
                    <button id="google-login" class="social-login-btn w-100" style="color: #ea4335; border-color: #e5e7eb;">
                        <i class="fab fa-google" style="margin-right: 6px;"></i> Google
                    </button>
                    <div id="google-login-container" style="width: 100%;"></div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Login Link -->
            <div class="register-row">
                Already have an account? <a href="login.php">Log In</a>
            </div>
        </div>

    </div>
</div>

<!-- jQuery and Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Auth JS -->
<script src="js/auth.js"></script>

<script>
    // Password show/hide toggle
    const pwToggle = document.getElementById('pwToggle');
    const pwInput  = document.getElementById('password');
    const pwIcon   = document.getElementById('pwIcon');
    pwToggle.addEventListener('click', () => {
        const show = pwInput.type === 'password';
        pwInput.type = show ? 'text' : 'password';
        pwIcon.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
    });

    const pwToggleConfirm = document.getElementById('pwToggleConfirm');
    const pwInputConfirm  = document.getElementById('confirm_password');
    const pwIconConfirm   = document.getElementById('pwIconConfirm');
    pwToggleConfirm.addEventListener('click', () => {
        const show = pwInputConfirm.type === 'password';
        pwInputConfirm.type = show ? 'text' : 'password';
        pwIconConfirm.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
    });

    // Loading state on submit (ignore if social login triggered)
    document.querySelector('form').addEventListener('submit', function () {
        const btn = document.getElementById('registerBtn');
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Creating Account\u2026';
        btn.style.opacity = '.85';
        btn.disabled = true;
    });
</script>
</body>
</html>
