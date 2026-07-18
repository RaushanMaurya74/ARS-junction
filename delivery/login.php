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
    
    if (empty($email) || empty($password)) {
        $error_msg = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_delivery_boy = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Check if account is locked
            if (!empty($user['lockout_until'])) {
                $lockout_time = strtotime($user['lockout_until']);
                if (time() < $lockout_time) {
                    $error_msg = "Account locked due to 5 failed login attempts. Please try again after 2 hours.";
                } else {
                    // Lockout period has passed, reset attempts
                    $stmt_reset = $conn->prepare("UPDATE users SET login_attempts = 0, lockout_until = NULL WHERE user_id = ?");
                    $stmt_reset->execute([$user['user_id']]);
                    $user['login_attempts'] = 0;
                    $user['lockout_until'] = null;
                }
            }

            if (empty($error_msg)) {
                if (password_verify($password, $user['password'])) {
                    // Reset attempts on successful login
                    $stmt_reset = $conn->prepare("UPDATE users SET login_attempts = 0, lockout_until = NULL WHERE user_id = ?");
                    $stmt_reset->execute([$user['user_id']]);

                    $_SESSION['delivery_boy_id'] = $user['user_id'];
                    $_SESSION['delivery_boy_name'] = $user['name'];
                    $_SESSION['delivery_boy_email'] = $user['email'];
                    header("Location: dashboard.php");
                    exit;
                } else {
                    // Increment failed attempts
                    $attempts = (int)$user['login_attempts'] + 1;
                    if ($attempts >= 5) {
                        $lockout_until = date('Y-m-d H:i:s', time() + 7200); // 2 hours
                        $stmt_lock = $conn->prepare("UPDATE users SET login_attempts = ?, lockout_until = ? WHERE user_id = ?");
                        $stmt_lock->execute([$attempts, $lockout_until, $user['user_id']]);
                        $error_msg = "Account locked due to 5 failed login attempts. Please try again after 2 hours.";
                    } else {
                        $stmt_inc = $conn->prepare("UPDATE users SET login_attempts = ? WHERE user_id = ?");
                        $stmt_inc->execute([$attempts, $user['user_id']]);
                        $remaining_attempts = 5 - $attempts;
                        $error_msg = "Invalid credentials. You have {$remaining_attempts} login attempts remaining.";
                    }
                }
            }
        } else {
            $error_msg = 'Invalid email or password, or account is not registered as a delivery boy.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Portal - Login</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .logo-area {
            font-size: 2.5rem;
            color: #ffc107;
            text-align: center;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

<div class="card login-card p-4">
    <div class="card-body">
        <div class="logo-area">
            <i class="fas fa-truck-moving"></i>
        </div>
        <h4 class="text-center mb-4 fw-bold">Delivery Boy Login</h4>
        
        <?php if ($error_msg): ?>
            <div class="alert alert-danger p-2 small">
                <i class="fas fa-exclamation-circle me-1"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="login.php">
            <div class="mb-3">
                <label for="email" class="form-label small fw-bold">Email address</label>
                <input type="email" class="form-control" id="email" name="email" required placeholder="Enter delivery email">
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label small fw-bold">Password</label>
                <input type="password" class="form-control" id="password" name="password" required placeholder="Enter password">
            </div>
            
            <button type="submit" class="btn btn-warning w-100 fw-bold text-dark mt-2">
                <i class="fas fa-sign-in-alt me-1"></i> Login to Portal
            </button>
        </form>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
