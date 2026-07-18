<?php
/**
 * Restaurant Owner Portal - Forgot Password with Email OTP Verification
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_restaurant_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$error_msg = '';
$success_msg = '';
$step = isset($_SESSION['reset_step']) ? (int)$_SESSION['reset_step'] : 1; 
// Step 1: Request Email
// Step 2: Verify OTP
// Step 3: Set New Password

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
                <p style='margin: 5px 0 0 0; font-size: 14px; opacity: 0.9;'>Verification code for your Partner Portal account</p>
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
    $email_dir = dirname(__DIR__) . '/uploads/emails';
    if (!file_exists($email_dir)) {
        @mkdir($email_dir, 0777, true);
    }
    $timestamp = time();
    $preview_file = $email_dir . "/otp_reset_{$timestamp}.html";
    @file_put_contents($preview_file, $htmlContent);

    // Send email via custom SMTP
    return send_smtp_email($email, "Password Reset OTP - {$site_name}", $htmlContent);
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $conn;

    // STEP 1: Enter Email & Request OTP
    if (isset($_POST['request_otp'])) {
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error_msg = 'Please enter your registered email address.';
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_restaurant_owner = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Generate OTP
                $otp = mt_rand(100000, 999999);
                $_SESSION['reset_otp_email'] = $email;
                $_SESSION['reset_otp_code'] = $otp;
                $_SESSION['reset_otp_expiry'] = time() + 900; // 15 mins
                $_SESSION['reset_user_id'] = $user['user_id'];
                $_SESSION['reset_step'] = 2;
                $step = 2;

                // Send Email
                send_otp_email($email, $user['name'], $otp);
                $success_msg = 'A verification OTP has been sent to your email address.';
            } else {
                $error_msg = 'No restaurant owner account found with that email address.';
            }
        }
    }

    // STEP 2: Verify OTP
    if (isset($_POST['verify_otp'])) {
        $entered_otp = trim($_POST['otp'] ?? '');
        
        if (empty($entered_otp)) {
            $error_msg = 'Please enter the verification OTP.';
        } elseif (!isset($_SESSION['reset_otp_code']) || time() > $_SESSION['reset_otp_expiry']) {
            $error_msg = 'OTP has expired. Please request a new one.';
            $_SESSION['reset_step'] = 1;
            $step = 1;
        } elseif ($entered_otp != $_SESSION['reset_otp_code']) {
            $error_msg = 'Invalid OTP. Please try again.';
        } else {
            $_SESSION['reset_otp_verified'] = true;
            $_SESSION['reset_step'] = 3;
            $step = 3;
            $success_msg = 'OTP verified successfully. You can now set your new password.';
        }
    }

    // STEP 3: Reset Password
    if (isset($_POST['reset_password'])) {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (!isset($_SESSION['reset_otp_verified']) || !$_SESSION['reset_otp_verified']) {
            $error_msg = 'Session expired. Please start over.';
            $_SESSION['reset_step'] = 1;
            $step = 1;
        } elseif (empty($new_password) || empty($confirm_password)) {
            $error_msg = 'Please fill in all password fields.';
        } elseif (strlen($new_password) < 6) {
            $error_msg = 'Password must be at least 6 characters.';
        } elseif ($new_password !== $confirm_password) {
            $error_msg = 'Passwords do not match.';
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $user_id = $_SESSION['reset_user_id'];

            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            if ($stmt->execute([$hashed, $user_id])) {
                // Clear reset session variables
                unset($_SESSION['reset_otp_email']);
                unset($_SESSION['reset_otp_code']);
                unset($_SESSION['reset_otp_expiry']);
                unset($_SESSION['reset_otp_verified']);
                unset($_SESSION['reset_user_id']);
                unset($_SESSION['reset_step']);
                
                header("Location: login.php?reset=success");
                exit;
            } else {
                $error_msg = 'Failed to reset password. Please try again.';
            }
        }
    }
}

// Handle Cancel / Restart Actions
if (isset($_GET['action']) && $_GET['action'] === 'cancel') {
    unset($_SESSION['reset_otp_email']);
    unset($_SESSION['reset_otp_code']);
    unset($_SESSION['reset_otp_expiry']);
    unset($_SESSION['reset_otp_verified']);
    unset($_SESSION['reset_user_id']);
    unset($_SESSION['reset_step']);
    header("Location: forgot_password.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — ARS Partner</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --brand:      #b91c1c;
            --brand-dark: #991b1b;
            --brand-glow: rgba(185, 28, 28, 0.35);
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
            background: linear-gradient(rgba(15, 23, 42, 0.70), rgba(15, 23, 42, 0.85)), url('../images/restaurant_2.jpg') center/cover no-repeat fixed;
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

        .panel-left {
            flex: 1;
            position: relative;
            background: url('../images/restaurant_2.jpg') center/cover no-repeat;
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
                rgba(10,10,10,.78) 0%,
                rgba(30,10,10,.55) 50%,
                rgba(185,28,28,.30) 100%
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

        .panel-footer {
            font-size: .7rem;
            color: rgba(255,255,255,.4);
        }

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
            margin-bottom: 1.25rem;
        }

        .success-banner {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: .75rem 1rem;
            font-size: .82rem;
            color: #166534;
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

        .form-input:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px var(--brand-glow);
            background: var(--white);
        }

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
            transition: background .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 4px 14px var(--brand-glow);
        }

        .btn-login:hover {
            background: var(--brand-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px var(--brand-glow);
        }

        .back-row {
            margin-top: 1.5rem;
            text-align: center;
            font-size: .82rem;
        }

        .back-row a {
            color: var(--brand);
            font-weight: 700;
            text-decoration: none;
        }
        
        .otp-display {
            font-size: 1.5rem;
            letter-spacing: 8px;
            text-align: center;
            font-weight: bold;
        }

        @media (max-width: 720px) {
            .login-card { flex-direction: column; max-width: 420px; border-radius: 20px; }
            .panel-left  { min-height: 200px; padding: 1.5rem; }
            .panel-right { width: 100%; padding: 2rem 1.5rem; }
        }
    </style>
</head>
<body>

<div class="login-wrap">
    <div class="login-card">

        <!-- Left Panel -->
        <div class="panel-left">
            <div>
                <div class="brand-logo">
                    <img src="../images/ars_logo.png" alt="ARS Logo" style="height: 38px; width: auto; object-fit: contain; border-radius: 4px;">
                    ARS Junction
                </div>
            </div>
            <div class="panel-footer">&copy; 2024 ARS Junction Intelligence Systems</div>
        </div>

        <!-- Right Panel -->
        <div class="panel-right">
            <h1 class="form-heading">Reset Password</h1>
            <p class="form-subhead">Verification & recovery via registered Email OTP.</p>

            <?php if ($error_msg): ?>
                <div class="error-banner"><i class="fas fa-exclamation-circle me-2"></i><?php echo $error_msg; ?></div>
            <?php endif; ?>
            <?php if ($success_msg): ?>
                <div class="success-banner"><i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?></div>
                <?php 
                $smtp_pw = get_site_setting('smtp_password', '');
                if (empty($smtp_pw) && isset($_SESSION['reset_otp_code']) && $step === 2): 
                ?>
                <div style="background: #fffbeb; border: 1px solid #fef3c7; color: #b45309; border-radius: 10px; padding: 0.75rem 1rem; font-size: 0.82rem; margin-bottom: 1.25rem; line-height: 1.4;">
                    <i class="fa-solid fa-bug" style="margin-right: 6px; color: #d97706;"></i> 
                    <strong>Sandbox Mode:</strong> Since your SMTP password is not set in the Admin Panel, you can verify using this OTP: 
                    <strong style="font-size: 1.15rem; color: #d97706; display: block; margin-top: 6px; letter-spacing: 2px; text-align: center; background: #fff; padding: 6px; border-radius: 6px; border: 1px dashed #d97706;"><?php echo $_SESSION['reset_otp_code']; ?></strong>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <!-- STEP 1: Enter Email -->
                <form method="post" action="forgot_password.php">
                    <div class="field-label">Registered Email Address</div>
                    <div class="input-wrap">
                        <i class="fa-regular fa-envelope input-icon"></i>
                        <input type="email" name="email" class="form-input" placeholder="owner@restaurant.com" required>
                    </div>
                    
                    <button type="submit" name="request_otp" class="btn-login">
                        Send Verification OTP <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            <?php elseif ($step === 2): ?>
                <!-- STEP 2: Verify OTP -->
                <form method="post" action="forgot_password.php">
                    <div class="field-label">Enter 6-Digit OTP</div>
                    <div class="input-wrap">
                        <i class="fa-solid fa-key input-icon"></i>
                        <input type="text" name="otp" class="form-input otp-display" placeholder="123456" maxlength="6" required autocomplete="off">
                    </div>
                    
                    <button type="submit" name="verify_otp" class="btn-login mb-2">
                        Verify Code <i class="fa-solid fa-circle-check"></i>
                    </button>
                    <div class="text-center mt-2">
                        <a href="forgot_password.php?action=cancel" class="text-muted small">Change Email / Start Over</a>
                    </div>
                </form>
            <?php elseif ($step === 3): ?>
                <!-- STEP 3: Enter New Password -->
                <form method="post" action="forgot_password.php">
                    <div class="field-label">New Password</div>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" name="new_password" class="form-input" placeholder="Min 6 characters" required>
                    </div>
                    
                    <div class="field-label">Confirm New Password</div>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" name="confirm_password" class="form-input" placeholder="Verify password" required>
                    </div>
                    
                    <button type="submit" name="reset_password" class="btn-login">
                        Update Password <i class="fa-solid fa-lock-open"></i>
                    </button>
                </form>
            <?php endif; ?>

            <div class="back-row">
                <a href="login.php"><i class="fa-solid fa-arrow-left me-1"></i> Back to Login</a>
            </div>
        </div>

    </div>
</div>

</body>
</html>
