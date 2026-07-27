<?php
require_once 'auth.php';

if (is_delivery_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $captcha_input = trim($_POST['captcha'] ?? '');
    
    if (empty($email) || empty($password) || empty($captcha_input)) {
        $error_msg = 'Please fill in all fields.';
    } else {
        require_once dirname(__DIR__) . '/includes/captcha.php';
        if (!verify_captcha_code($captcha_input)) {
            $error_msg = 'Invalid security verification code. Please try again.';
        } else {
            // Check rate limits with exponential backoff
            $rateLimit = RateLimiter::checkAuth($email, 'delivery_login');
            if (!$rateLimit['allowed']) {
                $error_msg = $rateLimit['reason'];
            } else {
                $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_delivery_boy = 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    if (password_verify($password, $user['password'])) {
                        // Reset attempt counters on success
                        RateLimiter::clearAuthSuccess($email, 'delivery_login');
                        $stmt_reset = $conn->prepare("UPDATE users SET login_attempts = 0, lockout_until = NULL WHERE user_id = ?");
                        $stmt_reset->execute([$user['user_id']]);

                        $_SESSION['delivery_boy_id'] = $user['user_id'];
                        $_SESSION['delivery_boy_name'] = $user['name'];
                        $_SESSION['delivery_boy_email'] = $user['email'];
                        header("Location: dashboard.php");
                        exit;
                    } else {
                        // Record failed attempt for exponential backoff
                        RateLimiter::recordAuthFailure($email, 'delivery_login');
                        $postCheck = RateLimiter::checkAuth($email, 'delivery_login');
                        if (!$postCheck['allowed']) {
                            $error_msg = $postCheck['reason'];
                        } else {
                            $error_msg = "Invalid email or password.";
                        }
                    }
                } else {
                    RateLimiter::recordAuthFailure($email, 'delivery_login');
                    $error_msg = 'Invalid email or password, or account is not registered as a delivery boy.';
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
    <title>Delivery Portal Login — ARS Junction</title>
    <meta name="description" content="Access the ARS Junction Delivery & Dispatch Center.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --brand:      #d97706; /* Amber for Courier/Delivery */
            --brand-dark: #b45309;
            --brand-glow: rgba(217, 119, 6, 0.35);
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
            background: linear-gradient(rgba(15, 23, 42, 0.70), rgba(15, 23, 42, 0.85)), url('https://images.unsplash.com/photo-1566576721346-d4a3b4eaeb55?q=80&w=1600') center/cover no-repeat fixed;
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
            background: url('https://images.unsplash.com/photo-1524661135-423995f22d0b?q=80&w=1600') center/cover no-repeat;
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
                rgba(245, 158, 11, 0.90) 0%,
                rgba(217, 119, 6, 0.75) 50%,
                rgba(251, 191, 36, 0.20) 100%
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
                    <img src="../images/ars_logo.png" alt="ARS Logo" style="height: 38px; width: auto; object-fit: contain; border-radius: 4px;">
                    ARS Junction
                </div>
                <p class="brand-tagline">Satisfying your cravings with surgical precision.</p>
            </div>

            <div class="customer-badge">
                <div class="badge-top">
                    <div class="cust-avatar"><i class="fa-solid fa-truck-fast"></i></div>
                    <div>
                        <div class="cust-name">Rapid Dispatch</div>
                        <div class="cust-tier">Active Courier Node</div>
                    </div>
                </div>
                <div class="status-bar">
                    <div class="status-label">
                        <i class="fa-solid fa-signal" style="color:#a8e6cf;font-size:.7rem;vertical-align:middle;margin-right:4px;"></i>
                        Logistics Sync Protocol: Active
                    </div>
                    <div class="status-track"><div class="status-fill"></div></div>
                </div>
            </div>

            <div class="panel-footer">&copy; 2024 ARS Junction Intelligence Systems</div>
        </div>

        <!-- ═══ RIGHT PANEL ══════════════════════════════════ -->
        <div class="panel-right">
            <h1 class="form-heading">Courier Access</h1>
            <p class="form-subhead">Log in below to retrieve pending routes and assign targets.</p>

            <?php if ($error_msg): ?>
                <div class="error-banner">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="login.php" autocomplete="off">
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
                            placeholder="courier@arsjunction.com"
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
                            placeholder="&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;"
                            required
                        >
                        <button type="button" class="pw-toggle" id="pwToggle" aria-label="Toggle password visibility">
                            <i class="fa-regular fa-eye" id="pwIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Security CAPTCHA Check -->
                <div>
                    <div class="field-label">
                        <span>Security Verification</span>
                        <button type="button" id="refreshCaptcha" style="background: none; border: none; color: var(--brand); cursor: pointer; font-size: 0.78rem; display: flex; align-items: center; gap: 0.25rem; font-weight: 600; text-transform: none;">
                            <i class="fa-solid fa-arrows-rotate"></i> Refresh
                        </button>
                    </div>
                    <div style="display: flex; gap: 0.75rem; align-items: center; margin-bottom: 1.1rem;">
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
                        <div class="input-wrap" style="flex: 1; margin-bottom: 0;">
                            <i class="fa-solid fa-shield-halved input-icon"></i>
                            <input
                                type="text"
                                id="captcha"
                                name="captcha"
                                class="form-input"
                                placeholder="Security Code"
                                required
                                autocomplete="off"
                            >
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-login" id="loginBtn">
                    Sign In <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
        </div>

    </div>
</div>

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
    document.querySelector('form').addEventListener('submit', function () {
        const btn = document.getElementById('loginBtn');
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Authenticating\u2026';
        btn.style.opacity = '.85';
        btn.disabled = true;
    });
</script>
</body>
</html>
