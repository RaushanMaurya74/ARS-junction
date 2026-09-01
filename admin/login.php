<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/admin_auth.php';

// Redirect if already logged in
if (is_admin_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$email = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Authenticate admin
    unset($_SESSION['admin_login_error']);
    $admin = admin_login($email, $password);
    
    if ($admin) {
        // Redirect to dashboard
        header("Location: dashboard.php");
        exit;
    } else {
        $error = isset($_SESSION['admin_login_error']) ? $_SESSION['admin_login_error'] : 'Invalid email or password. Please try again.';
        unset($_SESSION['admin_login_error']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal Login — ARS Junction</title>
    <meta name="description" content="Access the ARS Junction Administration & Control Center.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --brand:      #0f172a; /* Slate 900 for Admin */
            --brand-accent: #f59e0b; /* Amber for highlight */
            --brand-glow: rgba(245, 158, 11, 0.25);
            --text-dark:  #1f2937;
            --text-mid:   #4b5563;
            --text-light: #9ca3af;
            --border:     #e5e7eb;
            --input-bg:   #f9fafb;
            --white:      #ffffff;
        }

        html, body {
            height: 100%;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(rgba(15, 23, 42, 0.82), rgba(15, 23, 42, 0.94)), url('https://images.unsplash.com/photo-1504384308090-c894fdcc538d?q=80&w=1600') center/cover no-repeat fixed;
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
            background: url('../images/restaurant_1.jpg') center/cover no-repeat;
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
                rgba(15, 23, 42, 0.90) 0%,
                rgba(30, 41, 59, 0.75) 50%,
                rgba(245, 158, 11, 0.15) 100%
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
            max-width: 240px;
            line-height: 1.5;
        }

        .admin-badge {
            background: rgba(255,255,255,.08);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 16px;
            padding: 1.1rem 1.2rem;
        }

        .admin-badge .badge-top {
            display: flex;
            align-items: center;
            gap: .85rem;
        }

        .admin-avatar {
            width: 44px; height: 44px;
            border-radius: 12px;
            background: var(--brand-accent);
            color: var(--brand);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
            box-shadow: 0 4px 12px var(--brand-glow);
        }

        .admin-name { font-weight: 700; font-size: .95rem; line-height: 1.2; }
        .admin-tier {
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
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
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
        .pw-toggle:hover { color: #3b82f6; }

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
        }
        .btn-login:hover {
            background: #1e293b;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.2);
        }
        .btn-login:active { transform: translateY(0); }

        .back-row {
            margin-top: 1.5rem;
            text-align: center;
            font-size: .82rem;
        }
        .back-row a {
            color: var(--text-mid);
            font-weight: 600;
            text-decoration: none;
            transition: color .2s;
        }
        .back-row a:hover { color: var(--brand-accent); }

        @media (max-width: 720px) {
            .login-card { flex-direction: column; max-width: 420px; border-radius: 20px; }
            .panel-left  { min-height: 200px; padding: 1.5rem; }
            .panel-right { width: 100%; padding: 2rem 1.5rem; }
            .admin-badge { display: none; }
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
                <p class="brand-tagline">Administration, monitoring & orchestration console.</p>
            </div>

            <div class="admin-badge">
                <div class="badge-top">
                    <div class="admin-avatar"><i class="fa-solid fa-user-shield"></i></div>
                    <div>
                        <div class="admin-name">Operations Central</div>
                        <div class="admin-tier">Security & Governance</div>
                    </div>
                </div>
                <div class="status-bar">
                    <div class="status-label">
                        <i class="fa-solid fa-shield-halved" style="color:#22c55e;font-size:.7rem;vertical-align:middle;margin-right:4px;"></i>
                        System Shield: Active
                    </div>
                    <div class="status-track"><div class="status-fill"></div></div>
                </div>
            </div>

            <div class="panel-footer">&copy; 2024 ARS Junction Intelligence Systems</div>
        </div>

        <!-- ═══ RIGHT PANEL ══════════════════════════════════ -->
        <div class="panel-right">
            <h1 class="form-heading">Control Center</h1>
            <p class="form-subhead">Enter your administrative credentials to log in.</p>

            <?php if (!empty($error)): ?>
                <div class="error-banner">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="post" autocomplete="off">
                <!-- Email -->
                <div>
                    <div class="field-label">Administrative Email</div>
                    <div class="input-wrap">
                        <i class="fa-regular fa-envelope input-icon"></i>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-input"
                            placeholder="admin@arsjunction.com"
                            value="<?php echo htmlspecialchars($email); ?>"
                            required
                            autocomplete="username"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <div class="field-label">Secure Password</div>
                    <div class="input-wrap pw-wrap">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            placeholder="&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="pw-toggle" id="pwToggle" aria-label="Toggle password visibility">
                            <i class="fa-regular fa-eye" id="pwIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-login" id="loginBtn">
                    Authenticate Session <i class="fa-solid fa-shield"></i>
                </button>
            </form>

            <div class="back-row">
                <a href="../index.php">
                    <i class="fa-solid fa-arrow-left me-1"></i> Return to main site
                </a>
            </div>
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

    // Loading state on submit
    document.querySelector('form').addEventListener('submit', function () {
        const btn = document.getElementById('loginBtn');
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Authorizing\u2026';
        btn.style.opacity = '.85';
        btn.disabled = true;
    });
</script>
</body>
</html>
