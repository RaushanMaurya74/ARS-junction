<?php
/**
 * Restaurant Owner Portal - Login
 * Premium split-panel redesign
 */

// auth.php now includes db_connect.php via __DIR__ so $conn is always set
require_once 'auth.php';

if (is_restaurant_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error_msg = 'Please fill in all fields.';
    } elseif (!$conn) {
        $error_msg = 'Database connection unavailable. Please try again later.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_restaurant_owner = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            if (empty($user['restaurant_id'])) {
                $error_msg = 'Your account is not assigned to any restaurant. Please contact administration.';
            } else {
                $_SESSION['restaurant_owner_id']    = $user['user_id'];
                $_SESSION['restaurant_id']          = $user['restaurant_id'];
                $_SESSION['restaurant_owner_name']  = $user['name'];
                $_SESSION['restaurant_owner_email'] = $user['email'];

                $stmt_res = $conn->prepare("SELECT name FROM restaurants WHERE restaurant_id = ?");
                $stmt_res->execute([$user['restaurant_id']]);
                $_SESSION['restaurant_name'] = $stmt_res->fetchColumn() ?: 'Restaurant';

                $redirect = 'dashboard.php';
                if (isset($_SESSION['redirect_after_restaurant_login'])) {
                    $redirect = $_SESSION['redirect_after_restaurant_login'];
                    unset($_SESSION['redirect_after_restaurant_login']);
                }
                header("Location: " . $redirect);
                exit;
            }
        } else {
            $error_msg = 'Invalid email or password, or account is not registered as a restaurant partner.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Partner Login - ARS Junction</title>
    <meta name="description" content="Log in to the ARS Junction Restaurant Partner Dashboard to manage your orders, menu, and delivery team.">
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
        html, body { height: 100%; font-family: 'Inter', sans-serif; background: #f3f4f6; }
        .login-wrap { display: flex; height: 100vh; align-items: center; justify-content: center; padding: 1.5rem; }
        .login-card {
            display: flex; width: 100%; max-width: 960px; min-height: 600px;
            border-radius: 24px; overflow: hidden;
            box-shadow: 0 25px 60px rgba(0,0,0,.18);
            animation: fadeUp .5s ease both;
        }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }

        .panel-left {
            flex: 1; position: relative;
            background: url('../images/restaurant_4.jpg') center/cover no-repeat;
            display: flex; flex-direction: column; justify-content: space-between;
            padding: 2.25rem 2rem; color: var(--white); min-width: 0;
        }
        .panel-left::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(160deg, rgba(10,10,10,.78) 0%, rgba(30,10,10,.55) 50%, rgba(185,28,28,.30) 100%);
        }
        .panel-left > * { position: relative; z-index: 1; }
        .brand-logo { display: flex; align-items: center; gap: .6rem; font-size: 1.35rem; font-weight: 800; letter-spacing: -.5px; }
        .brand-logo .icon-wrap {
            width: 38px; height: 38px; border-radius: 10px; background: var(--brand);
            display: flex; align-items: center; justify-content: center; font-size: .95rem;
            box-shadow: 0 4px 14px var(--brand-glow);
        }
        .brand-tagline { margin-top: .5rem; font-size: .82rem; color: rgba(255,255,255,.7); max-width: 220px; line-height: 1.5; }
        .restaurant-badge {
            background: rgba(255,255,255,.12); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(255,255,255,.18); border-radius: 16px; padding: 1.1rem 1.2rem;
        }
        .restaurant-badge .badge-top { display: flex; align-items: center; gap: .85rem; }
        .rest-avatar {
            width: 44px; height: 44px; border-radius: 12px; background: var(--brand);
            display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;
            box-shadow: 0 4px 12px var(--brand-glow);
        }
        .rest-name { font-weight: 700; font-size: .95rem; line-height: 1.2; }
        .rest-tier { font-size: .65rem; font-weight: 700; letter-spacing: .08em; color: rgba(255,255,255,.6); text-transform: uppercase; margin-top: 2px; }
        .status-bar { margin-top: .9rem; }
        .status-label { font-size: .7rem; color: rgba(255,255,255,.55); margin-bottom: .35rem; }
        .status-track { height: 4px; background: rgba(255,255,255,.18); border-radius: 4px; overflow: hidden; }
        .status-fill { height: 100%; width: 0; background: linear-gradient(90deg, #22c55e, #86efac); border-radius: 4px; animation: fillIn 1.2s ease .5s forwards; }
        @keyframes fillIn { to { width: 92%; } }
        .panel-footer { font-size: .7rem; color: rgba(255,255,255,.4); }

        .panel-right { width: 440px; flex-shrink: 0; background: var(--white); display: flex; flex-direction: column; justify-content: center; padding: 3rem 2.8rem; }
        .form-heading { font-size: 1.75rem; font-weight: 800; color: var(--text-dark); line-height: 1.15; }
        .form-subhead { font-size: .85rem; color: var(--text-mid); margin-top: .35rem; margin-bottom: 2rem; }
        .error-banner {
            background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: .75rem 1rem;
            font-size: .82rem; color: #991b1b; display: flex; align-items: center; gap: .6rem;
            margin-bottom: 1.25rem; animation: shake .4s ease;
        }
        @keyframes shake { 0%,100% { transform: translateX(0); } 20%,60% { transform: translateX(-6px); } 40%,80% { transform: translateX(6px); } }
        .field-label {
            font-size: .7rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase;
            color: var(--text-mid); margin-bottom: .45rem; display: flex; justify-content: space-between; align-items: center;
        }
        .field-label a { font-size: .72rem; font-weight: 600; color: var(--brand); text-decoration: none; text-transform: none; letter-spacing: 0; }
        .field-label a:hover { text-decoration: underline; }
        .input-wrap { position: relative; margin-bottom: 1.1rem; }
        .input-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light); font-size: .85rem; pointer-events: none; }
        .form-input {
            width: 100%; height: 48px; padding: 0 1rem 0 2.65rem;
            border: 1.5px solid var(--border); border-radius: 10px; background: var(--input-bg);
            font-family: 'Inter', sans-serif; font-size: .88rem; color: var(--text-dark); outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .form-input::placeholder { color: var(--text-light); }
        .form-input:focus { border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-glow); background: var(--white); }
        .pw-wrap { position: relative; }
        .pw-wrap .form-input { padding-right: 3rem; }
        .pw-toggle { position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-light); cursor: pointer; padding: 0; font-size: .9rem; transition: color .2s; }
        .pw-toggle:hover { color: var(--brand); }
        .remember-row { display: flex; align-items: center; gap: .55rem; margin-bottom: 1.4rem; }
        .remember-row input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--brand); cursor: pointer; }
        .remember-row label { font-size: .82rem; color: var(--text-mid); cursor: pointer; }
        .btn-login {
            width: 100%; height: 50px; background: var(--brand); color: var(--white);
            font-family: 'Inter', sans-serif; font-size: .95rem; font-weight: 700; border: none; border-radius: 10px;
            cursor: pointer; display: flex; align-items: center; justify-content: center; gap: .6rem;
            transition: background .2s, transform .15s, box-shadow .2s; box-shadow: 0 4px 14px var(--brand-glow);
        }
        .btn-login:hover { background: var(--brand-dark); transform: translateY(-2px); box-shadow: 0 8px 20px var(--brand-glow); }
        .btn-login:active { transform: translateY(0); }
        .divider { display: flex; align-items: center; gap: .75rem; margin: 1.6rem 0 1.2rem; color: var(--text-light); font-size: .75rem; font-weight: 500; text-transform: uppercase; letter-spacing: .07em; }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }
        .alt-btns { display: flex; gap: .75rem; }
        .btn-alt { flex: 1; height: 44px; background: var(--white); border: 1.5px solid var(--border); border-radius: 10px; font-family: 'Inter', sans-serif; font-size: .8rem; font-weight: 600; color: var(--text-dark); cursor: pointer; display: flex; align-items: center; justify-content: center; gap: .5rem; transition: border-color .2s, background .2s, transform .15s; }
        .btn-alt:hover { border-color: var(--brand); background: #fef2f2; transform: translateY(-1px); }
        .btn-alt .fa-key { color: var(--brand); }
        .btn-alt .fa-table-cells { color: #16a34a; }
        .register-row { margin-top: 1.5rem; text-align: center; font-size: .82rem; color: var(--text-mid); }
        .register-row a { color: var(--brand); font-weight: 700; text-decoration: none; }
        .register-row a:hover { text-decoration: underline; }
        .page-footer { position: fixed; bottom: 1.25rem; left: 50%; transform: translateX(-50%); display: flex; gap: 1.5rem; font-size: .74rem; color: var(--text-light); }
        .page-footer a { color: var(--text-light); text-decoration: none; display: flex; align-items: center; gap: .35rem; transition: color .2s; }
        .page-footer a:hover { color: var(--brand); }
        @media (max-width: 720px) {
            .login-card { flex-direction: column; max-width: 420px; border-radius: 20px; }
            .panel-left { min-height: 200px; padding: 1.5rem; }
            .panel-right { width: 100%; padding: 2rem 1.5rem; }
            .restaurant-badge { display: none; }
        }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="panel-left">
            <div>
                <div class="brand-logo">
                    <div class="icon-wrap"><i class="fa-solid fa-store"></i></div>
                    ARS Junction
                </div>
                <p class="brand-tagline">Optimizing hospitality operations with surgical precision.</p>
            </div>
            <div class="restaurant-badge">
                <div class="badge-top">
                    <div class="rest-avatar"><i class="fa-solid fa-store"></i></div>
                    <div>
                        <div class="rest-name">Kitchen Central</div>
                        <div class="rest-tier">Premium Branch</div>
                    </div>
                </div>
                <div class="status-bar">
                    <div class="status-label"><i class="fa-solid fa-circle-dot" style="color:#22c55e;font-size:.55rem;vertical-align:middle;margin-right:4px;"></i> System Status: Optimal</div>
                    <div class="status-track"><div class="status-fill"></div></div>
                </div>
            </div>
            <div class="panel-footer">&copy; 2024 ARS Junction Intelligence Systems</div>
        </div>
        <div class="panel-right">
            <h1 class="form-heading">Welcome Back</h1>
            <p class="form-subhead">Enter your credentials to access the partner dashboard.</p>
            <?php if ($error_msg): ?>
                <div class="error-banner"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>
            <form method="post" action="login.php" autocomplete="off">
                <div>
                    <div class="field-label">Email or Mobile Number</div>
                    <div class="input-wrap">
                        <i class="fa-regular fa-circle-user input-icon"></i>
                        <input type="text" id="email" name="email" class="form-input" placeholder="manager@restaurant.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autocomplete="username">
                    </div>
                </div>
                <div>
                    <div class="field-label">Password <a href="#">Forgot Password?</a></div>
                    <div class="input-wrap pw-wrap">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="form-input" placeholder="&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;" required autocomplete="current-password">
                        <button type="button" class="pw-toggle" id="pwToggle"><i class="fa-regular fa-eye" id="pwIcon"></i></button>
                    </div>
                </div>
                <div class="remember-row">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember this device for 30 days</label>
                </div>
                <button type="submit" class="btn-login" id="loginBtn">Login to Dashboard <i class="fa-solid fa-arrow-right"></i></button>
            </form>
            <div class="divider">or connect with</div>
            <div class="alt-btns">
                <button class="btn-alt" type="button"><i class="fa-solid fa-key"></i> SSO</button>
                <button class="btn-alt" type="button"><i class="fa-solid fa-table-cells"></i> Corporate</button>
            </div>
            <div class="register-row">New to the platform? <a href="#">Register your Restaurant</a></div>
        </div>
    </div>
</div>
<footer class="page-footer">
    <a href="#"><i class="fa-regular fa-circle-question"></i> Support Center</a>
    <a href="#"><i class="fa-solid fa-shield-halved"></i> Privacy Protocol</a>
</footer>
<script>
    const pwToggle = document.getElementById('pwToggle');
    const pwInput  = document.getElementById('password');
    const pwIcon   = document.getElementById('pwIcon');
    pwToggle.addEventListener('click', () => {
        const show = pwInput.type === 'password';
        pwInput.type = show ? 'text' : 'password';
        pwIcon.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
    });
    document.querySelector('form').addEventListener('submit', function () {
        const btn = document.getElementById('loginBtn');
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Authenticating...';
        btn.style.opacity = '.85';
        btn.disabled = true;
    });
</script>
</body>
</html>
