<?php
/**
 * Restaurant Owner Portal - Self Registration
 * Creates a restaurant + owner user account (pending admin approval)
 */

require_once 'auth.php';

// Already logged in? Go to dashboard
if (is_restaurant_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$error_msg   = '';
$success_msg = '';
$step        = 'form'; // 'form' | 'success'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ── Sanitise inputs ────────────────────────────────────────────────
    $owner_name       = trim($_POST['owner_name']       ?? '');
    $owner_email      = trim($_POST['owner_email']      ?? '');
    $owner_phone      = trim($_POST['owner_phone']      ?? '');
    $owner_password   = trim($_POST['owner_password']   ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    $rest_name        = trim($_POST['rest_name']        ?? '');
    $rest_address     = trim($_POST['rest_address']     ?? '');
    $rest_city        = trim($_POST['rest_city']        ?? '');
    $rest_state       = trim($_POST['rest_state']       ?? '');
    $rest_zip         = trim($_POST['rest_zip']         ?? '');
    $rest_phone       = trim($_POST['rest_phone']       ?? '');
    $rest_email       = trim($_POST['rest_email']       ?? '');
    $rest_description = trim($_POST['rest_description'] ?? '');
    $captcha_input    = trim($_POST['captcha']          ?? '');

    // Check rate limit for restaurant registration
    $rateLimit = RateLimiter::checkAuth($owner_email, 'restaurant_register');
    require_once dirname(__DIR__) . '/includes/captcha.php';
    if (!$rateLimit['allowed']) {
        $error_msg = $rateLimit['reason'];
    } elseif (!verify_captcha_code($captcha_input)) {
        $error_msg = 'Invalid security verification code. Please try again.';
    } elseif (!$conn) {
        $error_msg = 'Database connection unavailable. Please try again later.';
    } elseif (empty($owner_name) || empty($owner_email) || empty($owner_phone) ||
              empty($owner_password) || empty($rest_name) || empty($rest_address) ||
              empty($rest_city) || empty($rest_state) || empty($rest_zip) || empty($rest_phone)) {
        $error_msg = 'Please fill in all required fields.';
    } elseif (!filter_var($owner_email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Please enter a valid email address.';
    } elseif (strlen($owner_password) < 8) {
        $error_msg = 'Password must be at least 8 characters long.';
    } elseif ($owner_password !== $confirm_password) {
        $error_msg = 'Passwords do not match.';
    } else {
        // ── Check email uniqueness ──────────────────────────────────────
        $chk = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $chk->execute([$owner_email]);
        if ($chk->fetch()) {
            $error_msg = 'An account with this email already exists. Please log in instead.';
        } else {
            // Start transaction
            $conn->beginTransaction();
            try {
                // ── Insert restaurant (inactive until admin approves) ───────
                if ($conn->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
                    $stmt_rest = $conn->prepare(
                        "INSERT INTO restaurants (name, description, address, city, state, zip_code, phone, email, is_active)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0) RETURNING restaurant_id"
                    );
                    $stmt_rest->execute([
                        $rest_name, $rest_description ?: null,
                        $rest_address, $rest_city, $rest_state, $rest_zip,
                        $rest_phone, $rest_email ?: null
                    ]);
                    $restaurant_id = $stmt_rest->fetchColumn();
                } else {
                    $stmt_rest = $conn->prepare(
                        "INSERT INTO restaurants (name, description, address, city, state, zip_code, phone, email, is_active)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)"
                    );
                    $stmt_rest->execute([
                        $rest_name, $rest_description ?: null,
                        $rest_address, $rest_city, $rest_state, $rest_zip,
                        $rest_phone, $rest_email ?: null
                    ]);
                    $restaurant_id = $conn->lastInsertId();
                }

                if (!$restaurant_id) {
                    throw new Exception("Could not retrieve generated restaurant ID.");
                }

                // ── Insert owner user ───────────────────────────────────────
                $hashed_pw = password_hash($owner_password, PASSWORD_DEFAULT);
                $stmt_user = $conn->prepare(
                    "INSERT INTO users (name, email, password, phone, is_restaurant_owner, restaurant_id)
                     VALUES (?, ?, ?, ?, 1, ?)"
                );
                $stmt_user->execute([$owner_name, $owner_email, $hashed_pw, $owner_phone, $restaurant_id]);

                $conn->commit();
                $step = 'success';
            } catch (Exception $e) {
                $conn->rollBack();
                error_log('Restaurant registration failed: ' . $e->getMessage());
                $error_msg = 'Registration failed. Please try again later.';
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
    <title>Register Your Restaurant — ARS Junction</title>
    <meta name="description" content="Partner with ARS Junction. Register your restaurant and start receiving orders today.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --brand:      #b91c1c;
            --brand-dark: #991b1b;
            --brand-glow: rgba(185, 28, 28, 0.30);
            --text-dark:  #111827;
            --text-mid:   #6b7280;
            --text-light: #9ca3af;
            --border:     #e5e7eb;
            --input-bg:   #f9fafb;
            --white:      #ffffff;
            --success:    #15803d;
            --success-bg: #f0fdf4;
        }

        html, body {
            min-height: 100%;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(rgba(15, 23, 42, 0.70), rgba(15, 23, 42, 0.85)), url('../images/restaurant_about.jpg') center/cover no-repeat fixed;
        }

        /* ── Outer wrap ─────────────────────────────────────── */
        .page-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        /* ── Card ────────────────────────────────────────────── */
        .reg-card {
            display: flex;
            width: 100%;
            max-width: 1020px;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0,0,0,.15);
            animation: fadeUp .5s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Left branding panel ─────────────────────────────── */
        .panel-left {
            width: 300px;
            flex-shrink: 0;
            position: relative;
            background: url('../images/restaurant_4.jpg') center/cover no-repeat;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 2.25rem 1.75rem;
            color: var(--white);
        }
        .panel-left::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                160deg,
                rgba(10,10,10,.82) 0%,
                rgba(30,10,10,.60) 50%,
                rgba(185,28,28,.35) 100%
            );
        }
        .panel-left > * { position: relative; z-index: 1; }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: .55rem;
            font-size: 1.2rem;
            font-weight: 800;
            letter-spacing: -.4px;
        }
        .brand-logo .icon-wrap {
            width: 34px; height: 34px;
            border-radius: 9px;
            background: var(--brand);
            display: flex; align-items: center; justify-content: center;
            font-size: .85rem;
            box-shadow: 0 4px 12px var(--brand-glow);
        }
        .brand-tagline {
            margin-top: .5rem;
            font-size: .78rem;
            color: rgba(255,255,255,.65);
            line-height: 1.55;
        }

        /* Benefits list */
        .benefits { list-style: none; display: flex; flex-direction: column; gap: .7rem; }
        .benefits li {
            display: flex;
            align-items: flex-start;
            gap: .6rem;
            font-size: .78rem;
            color: rgba(255,255,255,.85);
            line-height: 1.4;
        }
        .benefits li .bi {
            width: 22px; height: 22px;
            border-radius: 6px;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.18);
            display: flex; align-items: center; justify-content: center;
            font-size: .65rem;
            flex-shrink: 0;
            margin-top: 1px;
            color: #86efac;
        }
        .panel-footer { font-size: .68rem; color: rgba(255,255,255,.35); }

        /* ── Right form panel ────────────────────────────────── */
        .panel-right {
            flex: 1;
            background: var(--white);
            padding: 2.5rem 2.5rem 2rem;
            overflow-y: auto;
        }

        .form-heading { font-size: 1.5rem; font-weight: 800; color: var(--text-dark); }
        .form-subhead { font-size: .83rem; color: var(--text-mid); margin-top: .3rem; margin-bottom: 1.75rem; }

        /* Section label */
        .section-label {
            font-size: .65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--brand);
            border-bottom: 2px solid #fef2f2;
            padding-bottom: .4rem;
            margin-bottom: 1rem;
            margin-top: 1.5rem;
        }
        .section-label:first-of-type { margin-top: 0; }

        /* Grid */
        .field-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .85rem 1.1rem;
        }
        .field-grid .full { grid-column: 1 / -1; }

        /* Field */
        .field-wrap { display: flex; flex-direction: column; gap: .3rem; }
        .field-label {
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--text-mid);
        }
        .field-label .req { color: var(--brand); }

        .form-input {
            height: 44px;
            padding: 0 .85rem;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            background: var(--input-bg);
            font-family: 'Inter', sans-serif;
            font-size: .85rem;
            color: var(--text-dark);
            outline: none;
            transition: border-color .2s, box-shadow .2s;
            width: 100%;
        }
        .form-input::placeholder { color: var(--text-light); }
        .form-input:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px var(--brand-glow);
            background: var(--white);
        }

        textarea.form-input {
            height: 80px;
            padding-top: .65rem;
            resize: vertical;
        }

        /* Password input with toggle */
        .pw-wrap { position: relative; }
        .pw-wrap .form-input { padding-right: 2.75rem; }
        .pw-toggle {
            position: absolute;
            right: .85rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            padding: 0;
            font-size: .85rem;
            transition: color .2s;
        }
        .pw-toggle:hover { color: var(--brand); }

        /* Error / success banners */
        .alert-banner {
            border-radius: 10px;
            padding: .8rem 1rem;
            font-size: .82rem;
            display: flex;
            align-items: flex-start;
            gap: .6rem;
            margin-bottom: 1.25rem;
        }
        .alert-banner.error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            animation: shake .4s ease;
        }
        .alert-banner.success {
            background: var(--success-bg);
            border: 1px solid #bbf7d0;
            color: var(--success);
        }
        @keyframes shake {
            0%,100% { transform: translateX(0); }
            20%,60%  { transform: translateX(-5px); }
            40%,80%  { transform: translateX(5px); }
        }

        /* Strength meter */
        .pw-strength { margin-top: .3rem; height: 3px; background: var(--border); border-radius: 3px; overflow: hidden; }
        .pw-strength-bar { height: 100%; width: 0; border-radius: 3px; transition: width .3s, background .3s; }

        /* Submit row */
        .submit-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 1.5rem;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .submit-row .back-link {
            font-size: .82rem;
            color: var(--text-mid);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: .4rem;
            transition: color .2s;
        }
        .submit-row .back-link:hover { color: var(--brand); }

        .btn-register {
            height: 48px;
            padding: 0 2rem;
            background: var(--brand);
            color: var(--white);
            font-family: 'Inter', sans-serif;
            font-size: .92rem;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: .55rem;
            transition: background .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 4px 14px var(--brand-glow);
        }
        .btn-register:hover { background: var(--brand-dark); transform: translateY(-2px); box-shadow: 0 8px 20px var(--brand-glow); }
        .btn-register:active { transform: translateY(0); }

        /* ── Success screen ──────────────────────────────────── */
        .success-screen {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 400px;
            text-align: center;
            padding: 2rem;
        }
        .success-icon {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: var(--success-bg);
            border: 2px solid #bbf7d0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--success);
            margin-bottom: 1.25rem;
            animation: popIn .5s cubic-bezier(.34,1.56,.64,1) both;
        }
        @keyframes popIn {
            from { opacity: 0; transform: scale(.5); }
            to   { opacity: 1; transform: scale(1); }
        }
        .success-title { font-size: 1.5rem; font-weight: 800; color: var(--text-dark); margin-bottom: .5rem; }
        .success-body  { font-size: .88rem; color: var(--text-mid); max-width: 380px; line-height: 1.6; }
        .btn-back-login {
            margin-top: 1.75rem;
            height: 46px; padding: 0 1.75rem;
            background: var(--brand); color: var(--white);
            font-family: 'Inter', sans-serif; font-size: .88rem; font-weight: 700;
            border: none; border-radius: 10px; cursor: pointer;
            display: flex; align-items: center; gap: .5rem;
            text-decoration: none;
            transition: background .2s, transform .15s;
            box-shadow: 0 4px 14px var(--brand-glow);
        }
        .btn-back-login:hover { background: var(--brand-dark); transform: translateY(-2px); }

        /* ── Footer ──────────────────────────────────────────── */
        .page-footer {
            position: fixed;
            bottom: 1rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 1.5rem;
            font-size: .72rem;
            color: var(--text-light);
        }
        .page-footer a { color: var(--text-light); text-decoration: none; display: flex; align-items: center; gap: .3rem; transition: color .2s; }
        .page-footer a:hover { color: var(--brand); }

        /* ── Responsive ──────────────────────────────────────── */
        @media (max-width: 820px) {
            .reg-card { flex-direction: column; }
            .panel-left { width: 100%; min-height: 180px; padding: 1.5rem; }
            .benefits { flex-direction: row; flex-wrap: wrap; }
            .panel-right { padding: 1.75rem 1.25rem; }
            .field-grid { grid-template-columns: 1fr; }
            .field-grid .full { grid-column: 1; }
        }
    </style>
</head>
<body>

<div class="page-wrap">
    <div class="reg-card">

        <!-- ═══ LEFT PANEL ═══════════════════════════════════════ -->
        <div class="panel-left">
            <div>
                <div class="brand-logo">
                    <img src="../images/ars_logo.png" alt="ARS Logo" style="height: 38px; width: auto; object-fit: contain; border-radius: 4px;">
                    ARS Junction
                </div>
                <p class="brand-tagline">Join hundreds of restaurant partners growing with us.</p>
            </div>

            <ul class="benefits">
                <li>
                    <div class="bi"><i class="fa-solid fa-check"></i></div>
                    Reach thousands of customers in your area instantly.
                </li>
                <li>
                    <div class="bi"><i class="fa-solid fa-check"></i></div>
                    Manage orders, menu and delivery from one dashboard.
                </li>
                <li>
                    <div class="bi"><i class="fa-solid fa-check"></i></div>
                    Real-time order tracking &amp; delivery boy management.
                </li>
                <li>
                    <div class="bi"><i class="fa-solid fa-check"></i></div>
                    No setup fee. Get activated within 24 hours.
                </li>
            </ul>

            <div class="panel-footer">&copy; 2024 ARS Junction Intelligence Systems</div>
        </div>

        <!-- ═══ RIGHT PANEL ══════════════════════════════════════ -->
        <div class="panel-right">

            <?php if ($step === 'success'): ?>
            <!-- ── SUCCESS STATE ── -->
            <div class="success-screen">
                <div class="success-icon"><i class="fa-solid fa-check"></i></div>
                <div class="success-title">Registration Submitted!</div>
                <p class="success-body">
                    Your restaurant <strong><?php echo htmlspecialchars($rest_name); ?></strong> has been registered successfully.
                    Our team will review your application and activate your account within <strong>24 hours</strong>.
                    You'll be able to log in once your account is approved.
                </p>
                <a href="login.php" class="btn-back-login">
                    <i class="fa-solid fa-arrow-left"></i> Back to Login
                </a>
            </div>

            <?php else: ?>
            <!-- ── FORM STATE ── -->
            <h1 class="form-heading">Register Your Restaurant</h1>
            <p class="form-subhead">Fill in the details below to join the ARS Junction partner network.</p>

            <?php if ($error_msg): ?>
                <div class="alert-banner error">
                    <i class="fa-solid fa-circle-exclamation" style="margin-top:2px;flex-shrink:0;"></i>
                    <span><?php echo htmlspecialchars($error_msg); ?></span>
                </div>
            <?php endif; ?>

            <form method="post" action="register.php" id="regForm" autocomplete="off">

                <!-- SECTION 1: Owner Details -->
                <div class="section-label"><i class="fa-solid fa-user me-1"></i> Owner / Manager Details</div>
                <div class="field-grid">
                    <div class="field-wrap full">
                        <label class="field-label" for="owner_name">Full Name <span class="req">*</span></label>
                        <input type="text" id="owner_name" name="owner_name" class="form-input"
                            placeholder="Raushan Maurya"
                            value="<?php echo htmlspecialchars($_POST['owner_name'] ?? ''); ?>"
                            required>
                    </div>
                    <div class="field-wrap">
                        <label class="field-label" for="owner_email">Email Address <span class="req">*</span></label>
                        <input type="email" id="owner_email" name="owner_email" class="form-input"
                            placeholder="manager@example.com"
                            value="<?php echo htmlspecialchars($_POST['owner_email'] ?? ''); ?>"
                            required>
                    </div>
                    <div class="field-wrap">
                        <label class="field-label" for="owner_phone">Phone Number <span class="req">*</span></label>
                        <input type="tel" id="owner_phone" name="owner_phone" class="form-input"
                            placeholder="9876543210"
                            value="<?php echo htmlspecialchars($_POST['owner_phone'] ?? ''); ?>"
                            required>
                    </div>
                    <div class="field-wrap">
                        <label class="field-label" for="owner_password">Password <span class="req">*</span></label>
                        <div class="pw-wrap">
                            <input type="password" id="owner_password" name="owner_password" class="form-input"
                                placeholder="Min. 8 characters" required autocomplete="new-password"
                                oninput="updateStrength(this.value)">
                            <button type="button" class="pw-toggle" onclick="togglePw('owner_password', this)">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                        <div class="pw-strength"><div class="pw-strength-bar" id="pwBar"></div></div>
                    </div>
                    <div class="field-wrap">
                        <label class="field-label" for="confirm_password">Confirm Password <span class="req">*</span></label>
                        <div class="pw-wrap">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input"
                                placeholder="Re-enter password" required autocomplete="new-password">
                            <button type="button" class="pw-toggle" onclick="togglePw('confirm_password', this)">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: Restaurant Details -->
                <div class="section-label"><i class="fa-solid fa-store me-1"></i> Restaurant Details</div>
                <div class="field-grid">
                    <div class="field-wrap full">
                        <label class="field-label" for="rest_name">Restaurant Name <span class="req">*</span></label>
                        <input type="text" id="rest_name" name="rest_name" class="form-input"
                            placeholder="e.g. Kitchen Central"
                            value="<?php echo htmlspecialchars($_POST['rest_name'] ?? ''); ?>"
                            required>
                    </div>
                    <div class="field-wrap full">
                        <label class="field-label" for="rest_address">Street Address <span class="req">*</span></label>
                        <input type="text" id="rest_address" name="rest_address" class="form-input"
                            placeholder="123 Main Street, Near Town Hall"
                            value="<?php echo htmlspecialchars($_POST['rest_address'] ?? ''); ?>"
                            required>
                    </div>
                    <div class="field-wrap">
                        <label class="field-label" for="rest_city">City <span class="req">*</span></label>
                        <input type="text" id="rest_city" name="rest_city" class="form-input"
                            placeholder="Piro"
                            value="<?php echo htmlspecialchars($_POST['rest_city'] ?? ''); ?>"
                            required>
                    </div>
                    <div class="field-wrap">
                        <label class="field-label" for="rest_state">State <span class="req">*</span></label>
                        <input type="text" id="rest_state" name="rest_state" class="form-input"
                            placeholder="Bihar"
                            value="<?php echo htmlspecialchars($_POST['rest_state'] ?? ''); ?>"
                            required>
                    </div>
                    <div class="field-wrap">
                        <label class="field-label" for="rest_zip">PIN Code <span class="req">*</span></label>
                        <input type="text" id="rest_zip" name="rest_zip" class="form-input"
                            placeholder="802207"
                            value="<?php echo htmlspecialchars($_POST['rest_zip'] ?? ''); ?>"
                            maxlength="10" required>
                    </div>
                    <div class="field-wrap">
                        <label class="field-label" for="rest_phone">Restaurant Phone <span class="req">*</span></label>
                        <input type="tel" id="rest_phone" name="rest_phone" class="form-input"
                            placeholder="9876543210"
                            value="<?php echo htmlspecialchars($_POST['rest_phone'] ?? ''); ?>"
                            required>
                    </div>
                    <div class="field-wrap full">
                        <label class="field-label" for="rest_email">Restaurant Email</label>
                        <input type="email" id="rest_email" name="rest_email" class="form-input"
                            placeholder="info@myrestaurant.com"
                            value="<?php echo htmlspecialchars($_POST['rest_email'] ?? ''); ?>">
                    </div>
                    <div class="field-wrap full">
                        <label class="field-label" for="rest_description">Short Description</label>
                        <textarea id="rest_description" name="rest_description" class="form-input"
                            placeholder="Tell customers what makes your restaurant special…"><?php echo htmlspecialchars($_POST['rest_description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Security CAPTCHA Check -->
                <div style="margin-bottom: 1.5rem;">
                    <div class="field-label" style="display: flex; justify-content: space-between; align-items: center;">
                        <span>Security Verification <span class="req">*</span></span>
                        <button type="button" id="refreshCaptcha" style="background: none; border: none; color: var(--brand); cursor: pointer; font-size: 0.78rem; display: flex; align-items: center; gap: 0.25rem; font-weight: 600; text-transform: none;">
                            <i class="fa-solid fa-arrows-rotate"></i> Refresh
                        </button>
                    </div>
                    <div style="display: flex; gap: 0.75rem; align-items: center;">
                        <div id="captchaContainer" style="background: var(--input-bg); border: 1.5px solid var(--border); border-radius: 10px; height: 48px; display: flex; align-items: center; justify-content: center; overflow: hidden; width: 140px; flex-shrink: 0; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
                            <?php 
                            require_once dirname(__DIR__) . '/includes/captcha.php';
                            $captcha = generate_captcha_data();
                            if ($captcha['type'] === 'image'):
                            ?>
                                <img src="<?php echo $captcha['html']; ?>" id="captchaImg" alt="CAPTCHA" style="width: 100%; height: 100%; object-fit: contain;">
                            <?php else: ?>
                                <span id="captchaMath" style="font-weight: 700; color: var(--text-dark); font-size: 1.05rem; letter-spacing: 2px;"><?php echo $captcha['html']; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="input-wrap" style="flex: 1; margin-bottom: 0; position: relative;">
                            <i class="fa-solid fa-shield-halved input-icon" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light); pointer-events: none;"></i>
                            <input
                                type="text"
                                id="captcha"
                                name="captcha"
                                class="form-input"
                                placeholder="Security Code"
                                required
                                autocomplete="off"
                                style="padding-left: 2.65rem;"
                            >
                        </div>
                    </div>
                </div>

                <div class="submit-row">
                    <a href="login.php" class="back-link">
                        <i class="fa-solid fa-arrow-left"></i> Already have an account?
                    </a>
                    <button type="submit" class="btn-register" id="regBtn">
                        <i class="fa-solid fa-store"></i> Register Restaurant
                    </button>
                </div>

            </form>
            <?php endif; ?>

        </div>
    </div>
</div>

<footer class="page-footer">
    <a href="#"><i class="fa-regular fa-circle-question"></i> Support Center</a>
    <a href="#"><i class="fa-solid fa-shield-halved"></i> Privacy Protocol</a>
</footer>

<script>
    // Password toggle
    function togglePw(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon  = btn.querySelector('i');
        const show  = input.type === 'password';
        input.type  = show ? 'text' : 'password';
        icon.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
    }

    // Password strength bar
    function updateStrength(val) {
        const bar = document.getElementById('pwBar');
        if (!bar) return;
        let score = 0;
        if (val.length >= 8)              score++;
        if (/[A-Z]/.test(val))            score++;
        if (/[0-9]/.test(val))            score++;
        if (/[^A-Za-z0-9]/.test(val))     score++;
        const colours = ['#ef4444','#f97316','#eab308','#22c55e'];
        const widths  = ['25%','50%','75%','100%'];
        bar.style.width      = score ? widths[score - 1] : '0';
        bar.style.background = score ? colours[score - 1] : '';
    }

    // CAPTCHA Refresh
    const refreshBtn = document.getElementById('refreshCaptcha');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => {
            const icon = refreshBtn.querySelector('i');
            if (icon) icon.classList.add('fa-spin');
            
            const isSubdir = window.location.pathname.includes('/admin/') || 
                             window.location.pathname.includes('/restaurant/') || 
                             window.location.pathname.includes('/delivery/');
            const apiUrl = isSubdir ? '../api/get_captcha.php' : 'api/get_captcha.php';

            fetch(apiUrl)
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('captchaContainer');
                    if (data.type === 'image') {
                        container.innerHTML = `<img src="${data.html}" id="captchaImg" alt="CAPTCHA" style="width: 100%; height: 100%; object-fit: contain;">`;
                    } else {
                        container.innerHTML = `<span id="captchaMath" style="font-weight: 700; color: var(--text-dark); font-size: 1.05rem; letter-spacing: 2px;">${data.html}</span>`;
                    }
                    document.getElementById('captcha').value = '';
                })
                .catch(err => console.error('Error refreshing captcha:', err))
                .finally(() => {
                    if (icon) icon.classList.remove('fa-spin');
                });
        });
    }

    // Loading state on submit
    const regForm = document.getElementById('regForm');
    if (regForm) {
        regForm.addEventListener('submit', function (e) {
            // Quick client-side password match check
            const pw  = document.getElementById('owner_password').value;
            const cpw = document.getElementById('confirm_password').value;
            if (pw !== cpw) {
                e.preventDefault();
                alert('Passwords do not match. Please re-enter.');
                return;
            }
            const btn = document.getElementById('regBtn');
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Submitting…';
            btn.style.opacity = '.85';
            btn.disabled = true;
        });
    }
</script>
</body>
</html>
