<?php
/**
 * Restaurant Owner Portal - Login
 */

require_once 'auth.php';

if (is_restaurant_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (empty($email) || empty($password)) {
        $error_msg = 'Please fill in all fields.';
    } else {
        global $conn;
        // Verify email, password and ensure they are marked as restaurant owner with assigned restaurant
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_restaurant_owner = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            if (empty($user['restaurant_id'])) {
                $error_msg = 'Your account is not assigned to any restaurant. Please contact administration.';
            } else {
                $_SESSION['restaurant_owner_id'] = $user['user_id'];
                $_SESSION['restaurant_id'] = $user['restaurant_id'];
                $_SESSION['restaurant_owner_name'] = $user['name'];
                $_SESSION['restaurant_owner_email'] = $user['email'];
                
                // Fetch restaurant name for session
                $stmt_res = $conn->prepare("SELECT name FROM restaurants WHERE restaurant_id = ?");
                $stmt_res->execute([$user['restaurant_id']]);
                $_SESSION['restaurant_name'] = $stmt_res->fetchColumn() ?: 'Restaurant';
                
                // Redirect logic
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
    <title>Restaurant Partner Portal - Login</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #ff5722;
            --accent-color: #e64a19;
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --text-color: #f1f5f9;
        }
        
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            overflow: hidden;
            position: relative;
        }

        /* Decorative background shapes */
        .circle-bg {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, #3b82f6 100%);
            filter: blur(80px);
            opacity: 0.15;
            z-index: 1;
        }
        .circle-1 {
            width: 350px;
            height: 350px;
            top: -100px;
            left: -100px;
        }
        .circle-2 {
            width: 450px;
            height: 450px;
            bottom: -150px;
            right: -150px;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            background-color: var(--card-bg);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            z-index: 10;
            backdrop-filter: blur(10px);
            padding: 2.5rem 2rem;
            transition: transform 0.3s ease;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
        }

        .logo-area {
            font-size: 3rem;
            background: linear-gradient(135deg, #ffc107 0%, #ff5722 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-align: center;
            margin-bottom: 0.5rem;
            animation: pulse 2s infinite;
        }

        .form-control {
            background-color: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
            border-radius: 8px;
            padding: 0.75rem 1rem;
        }

        .form-control:focus {
            background-color: #0f172a;
            border-color: var(--primary-color);
            color: #fff;
            box-shadow: 0 0 0 0.25rem rgba(255, 87, 34, 0.25);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            border: none;
            color: white;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-submit:hover {
            opacity: 0.95;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 87, 34, 0.3);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>

<div class="circle-bg circle-1"></div>
<div class="circle-bg circle-2"></div>

<div class="card login-card">
    <div class="logo-area">
        <i class="fas fa-store"></i>
    </div>
    <h3 class="text-center mb-1 fw-bold">ARS Junction</h3>
    <p class="text-center text-muted mb-4 small">Restaurant Manager Portal</p>
    
    <?php if ($error_msg): ?>
        <div class="alert alert-danger p-2 small border-0 bg-danger bg-opacity-25 text-danger-emphasis">
            <i class="fas fa-exclamation-circle me-1"></i> <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>
    
    <form method="post" action="login.php">
        <div class="mb-3">
            <label for="email" class="form-label small fw-semibold text-muted">Email Address</label>
            <input type="email" class="form-control" id="email" name="email" required placeholder="manager@example.com">
        </div>
        
        <div class="mb-4">
            <label for="password" class="form-label small fw-semibold text-muted">Password</label>
            <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
        </div>
        
        <button type="submit" class="btn btn-submit w-100 mt-2">
            <i class="fas fa-sign-in-alt me-1"></i> Log In to Dashboard
        </button>
    </form>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
