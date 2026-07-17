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
$step = isset($_SESSION['reset_step']) ? (int)$_SESSION['reset_step'] : 1; 

// Email OTP Sender helper
function send_otp_email($email, $name, $otp) {
    $site_name = get_site_setting('site_name', 'ARS Junction');
    $site_email = get_site_setting('site_email', 'officialarsjunction@gmail.com');
    $site_phone = get_site_setting('site_phone', '7979730721');
    $site_location = get_site_setting('site_location', 'AT - PIRO, BHOJPUR, BIHAR, INDIA-802207');

    $htmlContent = "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Password Reset OTP - {$site_name}</title>
        <style>
            body { font-family: 'Segoe UI', Helvetica, Arial, sans-serif; background-color: #f6f9fc; margin: 0; padding: 0; color: #333333; }
            .email-container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border: 1px solid #e1e6eb; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
            .header { background: linear-gradient(135deg, #FF5722 0%, #E64A19 100%); color: #ffffff; padding: 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 0.5px; }
            .content { padding: 30px; }
            .greeting { font-size: 18px; font-weight: 600; margin-top: 0; color: #111111; }
            .otp-code { font-size: 32px; font-weight: bold; text-align: center; color: #E64A19; letter-spacing: 5px; padding: 15px; margin: 20px 0; background: #f8f9fa; border-radius: 6px; border: 1px dashed #E64A19; }
            .footer { background-color: #f1f4f7; text-align: center; padding: 20px; font-size: 12px; color: #666666; border-top: 1px solid #e1e6eb; }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <h1>Password Reset Request</h1>
                <p style='margin: 5px 0 0 0; font-size: 14px; opacity: 0.9;'>Verification code for your Customer account</p>
            </div>
            <div class='content'>
                <p class='greeting'>Hello {$name},</p>
                <p>We received a request to reset your password. Use the following One-Time Password (OTP) to proceed. This OTP is valid for 15 minutes.</p>
                
                <div class='otp-code'>{$otp}</div>

                <p style='color: #666; font-size: 13px;'>If you did not request a password reset, please ignore this email or secure your account.</p>
            </div>
            <div class='footer'>
                <p style='margin: 0 0 5px 0;'><strong>{$site_name} Support</strong></p>
                <p style='margin: 0 0 10px 0;'>{$site_location} | Phone: +91 {$site_phone}</p>
                <p style='margin: 0; font-size: 11px; color: #999999;'>This is an automated system email. Please do not reply directly.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    // Write file copy for fallback/preview
    $email_dir = __DIR__ . '/uploads/emails';
    if (!file_exists($email_dir)) {
        @mkdir($email_dir, 0777, true);
    }
    $timestamp = time();
    $preview_file = $email_dir . "/otp_reset_{$timestamp}.html";
    @file_put_contents($preview_file, $htmlContent);

    // Send email
    $to = $email;
    $subject = "Password Reset OTP - {$site_name}";
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: {$site_name} <noreply@arsjunction.com>" . "\r\n";
    
    return @mail($to, $subject, $htmlContent, $headers);
}

// Handle Cancel Action
if (isset($_GET['action']) && $_GET['action'] === 'cancel') {
    unset($_SESSION['reset_otp_email']);
    unset($_SESSION['reset_otp_code']);
    unset($_SESSION['reset_otp_expiry']);
    unset($_SESSION['reset_otp_verified']);
    unset($_SESSION['reset_user_id']);
    unset($_SESSION['reset_user_name']);
    unset($_SESSION['reset_step']);
    header("Location: forgot_password.php");
    exit;
}

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // STEP 1: Enter Email & Request OTP
    if (isset($_POST['action']) && $_POST['action'] === 'request_otp') {
        $email = clean_input($_POST['email']);
        
        if (empty($email)) {
            $error = "Please enter your registered email address.";
        } else {
            // Find user by email
            $stmt = $conn->prepare("SELECT user_id, name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Generate OTP
                $otp = mt_rand(100000, 999999);
                $_SESSION['reset_otp_email'] = $email;
                $_SESSION['reset_otp_code'] = $otp;
                $_SESSION['reset_otp_expiry'] = time() + 900; // 15 mins
                $_SESSION['reset_user_id'] = $user['user_id'];
                $_SESSION['reset_user_name'] = $user['name'];
                $_SESSION['reset_step'] = 2;
                $step = 2;

                // Send Email
                send_otp_email($email, $user['name'], $otp);
                $success = 'A verification OTP has been sent to your email address.';
            } else {
                $error = "No customer account found with that email address.";
            }
        }
    }
    
    // STEP 2: Verify OTP
    elseif (isset($_POST['action']) && $_POST['action'] === 'verify_otp') {
        $entered_otp = trim($_POST['otp'] ?? '');
        
        if (empty($entered_otp)) {
            $error = 'Please enter the verification OTP.';
        } elseif (!isset($_SESSION['reset_otp_code']) || time() > $_SESSION['reset_otp_expiry']) {
            $error = 'OTP has expired. Please request a new one.';
            $_SESSION['reset_step'] = 1;
            $step = 1;
        } elseif ($entered_otp != $_SESSION['reset_otp_code']) {
            $error = 'Invalid OTP. Please try again.';
        } else {
            $_SESSION['reset_otp_verified'] = true;
            $_SESSION['reset_step'] = 3;
            $step = 3;
            $success = 'OTP verified successfully. You can now set your new password.';
        }
    }
    
    // STEP 3: Reset Password
    elseif (isset($_POST['action']) && $_POST['action'] === 'reset_password') {
        if (!isset($_SESSION['reset_otp_verified']) || !$_SESSION['reset_otp_verified']) {
            $error = 'Session expired. Please start over.';
            $_SESSION['reset_step'] = 1;
            $step = 1;
        } else {
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (empty($new_password) || empty($confirm_password)) {
                $error = "Please fill in all fields.";
            } elseif (strlen($new_password) < 6) {
                $error = "Password must be at least 6 characters long.";
            } elseif ($new_password !== $confirm_password) {
                $error = "Passwords do not match.";
            } else {
                $user_id = $_SESSION['reset_user_id'];
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password in DB
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                if ($stmt->execute([$hashed_password, $user_id])) {
                    // Clear reset session variables
                    unset($_SESSION['reset_otp_email']);
                    unset($_SESSION['reset_otp_code']);
                    unset($_SESSION['reset_otp_expiry']);
                    unset($_SESSION['reset_otp_verified']);
                    unset($_SESSION['reset_user_id']);
                    unset($_SESSION['reset_user_name']);
                    unset($_SESSION['reset_step']);
                    
                    $success = "Your password has been reset successfully! You can now log in.";
                    $step = 4;
                } else {
                    $error = "Failed to reset password. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — ARS Junction</title>
    <meta name="description" content="Recover your secure customer password on ARS Junction.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --brand:      #e64a19; /* Warm Orange/Red for Customers */
            --brand-dark: #d84315;
            --brand-glow: rgba(230, 74, 25, 0.35);
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
            min-height: 600px;
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
            background: url('images/restaurant_2.jpg') center/cover no-repeat;
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
                rgba(230, 74, 25, 0.90) 0%,
                rgba(216, 67, 21, 0.75) 50%,
                rgba(255, 126, 71, 0.20) 100%
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
            padding: 3rem 2.8rem;
        }

        .form-heading { font-size: 1.75rem; font-weight: 800; color: var(--text-dark); line-height: 1.15; }
        .form-subhead { font-size: .85rem; color: var(--text-mid); margin-top: .35rem; margin-bottom: 2rem; }

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

        .success-banner {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: .75rem 1rem;
            font-size: .82rem;
            color: #166534;
            display: flex;
            align-items: center;
            gap: .6rem;
            margin-bottom: 1.25rem;
        }

        .field-label {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: var(--text-mid);
            margin-bottom: .45rem;
        }

        .input-wrap {
            position: relative;
            margin-bottom: 1.1rem;
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
            height: 48px;
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
        }
        .btn-login:hover {
            background: var(--brand-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px var(--brand-glow);
        }
        .btn-login:active { transform: translateY(0); }

        .back-row {
            margin-top: 1.5rem;
            text-align: center;
            font-size: .82rem;
            color: var(--text-mid);
        }
        .back-row a {
            color: var(--brand);
            font-weight: 700;
            text-decoration: none;
        }
        .back-row a:hover { text-decoration: underline; }

        @media (max-width: 720px) {
            .login-card { flex-direction: column; max-width: 420px; border-radius: 20px; }
            .panel-left  { min-height: 200px; padding: 1.5rem; }
            .panel-right { width: 100%; padding: 2rem 1.5rem; }
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
                    <div class="cust-avatar"><i class="fa-solid fa-utensils"></i></div>
                    <div>
                        <div class="cust-name">Account Recovery</div>
                        <div class="cust-tier">Verified Customer</div>
                    </div>
                </div>
                <div class="status-bar">
                    <div class="status-label">
                        <i class="fa-solid fa-key" style="color:#a8e6cf;font-size:.7rem;vertical-align:middle;margin-right:4px;"></i>
                        Credential Security Protocol: Active
                    </div>
                    <div class="status-track"><div class="status-fill"></div></div>
                </div>
            </div>

            <div class="panel-footer">&copy; 2024 ARS Junction Intelligence Systems</div>
        </div>

        <!-- ═══ RIGHT PANEL ══════════════════════════════════ -->
        <div class="panel-right">
            <h1 class="form-heading">Recover Password</h1>
            <p class="form-subhead">Verification & recovery using secure Email OTP code.</p>

            <?php if (!empty($error)): ?>
                <div class="error-banner">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="success-banner">
                    <i class="fa-solid fa-circle-check"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <!-- STEP 1: Enter Email -->
                <form action="forgot_password.php" method="post" autocomplete="off">
                    <input type="hidden" name="action" value="request_otp">
                    
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
                                required
                            >
                        </div>
                    </div>

                    <button type="submit" class="btn-login" id="requestOtpBtn">
                        Send Verification Code <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            <?php elseif ($step === 2): ?>
                <!-- STEP 2: Verify OTP -->
                <form action="forgot_password.php" method="post" autocomplete="off">
                    <input type="hidden" name="action" value="verify_otp">
                    
                    <div>
                        <div class="field-label">6-Digit Verification Code</div>
                        <div class="input-wrap">
                            <i class="fa-solid fa-shield-halved input-icon"></i>
                            <input
                                type="text"
                                id="otp"
                                name="otp"
                                class="form-input"
                                placeholder="Enter 6-digit OTP"
                                required
                                maxlength="6"
                                autocomplete="one-time-code"
                            >
                        </div>
                    </div>

                    <button type="submit" class="btn-login" id="verifyOtpBtn">
                        Verify Code <i class="fa-solid fa-circle-check"></i>
                    </button>

                    <div class="text-center mt-3">
                        <a href="forgot_password.php?action=cancel" class="text-muted small">Start Over</a>
                    </div>
                </form>
            <?php elseif ($step === 3): ?>
                <!-- STEP 3: Reset Password -->
                <form action="forgot_password.php" method="post" autocomplete="off">
                    <input type="hidden" name="action" value="reset_password">
                    
                    <div>
                        <div class="field-label">New Password</div>
                        <div class="input-wrap pw-wrap">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input
                                type="password"
                                id="new_password"
                                name="new_password"
                                class="form-input"
                                placeholder="Min 6 characters"
                                required
                            >
                            <button type="button" class="pw-toggle" id="pwToggle" aria-label="Toggle password visibility">
                                <i class="fa-regular fa-eye" id="pwIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <div class="field-label">Confirm New Password</div>
                        <div class="input-wrap pw-wrap">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                class="form-input"
                                placeholder="Repeat new password"
                                required
                            >
                            <button type="button" class="pw-toggle" id="pwToggleConfirm" aria-label="Toggle password visibility">
                                <i class="fa-regular fa-eye" id="pwIconConfirm"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login" id="resetBtn">
                        Update Password <i class="fa-solid fa-key"></i>
                    </button>
                    
                    <div class="text-center mt-3">
                        <a href="forgot_password.php?action=cancel" class="text-muted small">Start Over</a>
                    </div>
                </form>
            <?php elseif ($step === 4): ?>
                <!-- STEP 4: Completed Successfully -->
                <div class="text-center py-4">
                    <i class="fa-solid fa-circle-check fa-4x text-success mb-3 animate__animated animate__bounceIn"></i>
                    <h5 class="fw-bold mb-2">Password Updated!</h5>
                    <p class="text-muted small mb-4">Your credential recovery is fully complete. You can now use your new password to sign in.</p>
                    <a href="login.php" class="btn-login text-decoration-none">
                        Sign In Now <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($step !== 4): ?>
            <div class="back-row">
                <a href="login.php"><i class="fa-solid fa-arrow-left me-1"></i> Back to Login</a>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
    // Password show/hide toggle
    const pwToggle = document.getElementById('pwToggle');
    const pwInput  = document.getElementById('new_password');
    const pwIcon   = document.getElementById('pwIcon');
    if (pwToggle && pwInput && pwIcon) {
        pwToggle.addEventListener('click', () => {
            const show = pwInput.type === 'password';
            pwInput.type = show ? 'text' : 'password';
            pwIcon.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
        });
    }

    const pwToggleConfirm = document.getElementById('pwToggleConfirm');
    const pwInputConfirm  = document.getElementById('confirm_password');
    const pwIconConfirm   = document.getElementById('pwIconConfirm');
    if (pwToggleConfirm && pwInputConfirm && pwIconConfirm) {
        pwToggleConfirm.addEventListener('click', () => {
            const show = pwInputConfirm.type === 'password';
            pwInputConfirm.type = show ? 'text' : 'password';
            pwIconConfirm.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
        });
    }

    // Loading state on submit
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function () {
            const btn = document.getElementById('requestOtpBtn') || document.getElementById('verifyOtpBtn') || document.getElementById('resetBtn');
            if (btn) {
                btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing\u2026';
                btn.style.opacity = '.85';
                btn.disabled = true;
            }
        });
    }
</script>
</body>
</html>
