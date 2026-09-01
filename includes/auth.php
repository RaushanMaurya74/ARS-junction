<?php
function authenticate_user($email, $password) {
    global $conn;

    // Check rate limits with exponential backoff (per-IP & per-account)
    $rateLimit = RateLimiter::checkAuth($email, 'login');
    if (!$rateLimit['allowed']) {
        $_SESSION['login_error'] = $rateLimit['reason'];
        return false;
    }

    // Get the user
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (password_verify($password, $user['password'])) {
            // Reset attempt counters on success
            RateLimiter::clearAuthSuccess($email, 'login');
            $stmt_reset = $conn->prepare("UPDATE users SET login_attempts = 0, lockout_until = NULL WHERE user_id = ?");
            $stmt_reset->execute([$user['user_id']]);

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['is_admin'] = $user['is_admin'];
            $_SESSION['is_delivery_boy'] = $user['is_delivery_boy'];
            $_SESSION['is_restaurant_owner'] = $user['is_restaurant_owner'] ?? 0;
            return true;
        } else {
            // Record failed attempt in RateLimiter for exponential backoff
            RateLimiter::recordAuthFailure($email, 'login');
            $postCheck = RateLimiter::checkAuth($email, 'login');

            if (!$postCheck['allowed']) {
                $_SESSION['login_error'] = $postCheck['reason'];
            } else {
                $_SESSION['login_error'] = "Invalid credentials. Please check your email and password.";
            }
            return false;
        }
    } else {
        // Record failed attempt for non-existent account per-IP to prevent user enumeration attacks
        RateLimiter::recordAuthFailure($email, 'login');
        $_SESSION['login_error'] = "Invalid credentials. Please check your email and password.";
        return false;
    }
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

if (!function_exists('require_admin')) {
    function require_admin() {
        if (!is_admin()) {
            header("Location: ../index.php");
            exit;
        }
    }
}

function logout() {
    $_SESSION = array();
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function social_login($name, $email, $social_id, $social_type) {
    global $conn;

    $stmt = $conn->prepare("SELECT * FROM users WHERE social_id = ? AND social_type = ?");
    $stmt->execute([$social_id, $social_type]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['is_delivery_boy'] = $user['is_delivery_boy'];
        $_SESSION['is_restaurant_owner'] = $user['is_restaurant_owner'] ?? 0;

        return array('success' => true, 'user_id' => $user['user_id'], 'new_user' => false);
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $stmt = $conn->prepare("UPDATE users SET social_id = ?, social_type = ? WHERE user_id = ?");
            $stmt->execute([$social_id, $social_type, $user['user_id']]);

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['is_admin'] = $user['is_admin'];
            $_SESSION['is_delivery_boy'] = $user['is_delivery_boy'];
            $_SESSION['is_restaurant_owner'] = $user['is_restaurant_owner'] ?? 0;

            return array('success' => true, 'user_id' => $user['user_id'], 'new_user' => false);
        } else {
            $placeholder_password = password_hash(generate_random_password(), PASSWORD_DEFAULT);
            $driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'pgsql') {
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, social_id, social_type, is_admin, is_delivery_boy, is_restaurant_owner) VALUES (?, ?, ?, ?, ?, 0, 0, 0) RETURNING user_id");
                $stmt->execute([$name, $email, $placeholder_password, $social_id, $social_type]);
                $user_id = (int)$stmt->fetchColumn();
            } else {
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, social_id, social_type, is_admin, is_delivery_boy, is_restaurant_owner) VALUES (?, ?, ?, ?, ?, 0, 0, 0)");
                $stmt->execute([$name, $email, $placeholder_password, $social_id, $social_type]);
                $user_id = (int)$conn->lastInsertId();
            }

            if ($user_id <= 0) {
                $stmt_find = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
                $stmt_find->execute([$email]);
                $user_id = (int)$stmt_find->fetchColumn();
            }

            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['is_admin'] = 0;
            $_SESSION['is_delivery_boy'] = 0;
            $_SESSION['is_restaurant_owner'] = 0;

            return array('success' => true, 'user_id' => $user_id, 'new_user' => true);
        }
    }
}

function change_password($user_id, $current_password, $new_password) {
    global $conn;

    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        if (password_verify($current_password, $result['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$hashed_password, $user_id]);

            if ($stmt->rowCount() > 0) {
                return array('success' => true);
            }
            return array('success' => false, 'message' => 'Password update failed');
        }
        return array('success' => false, 'message' => 'Current password is incorrect');
    }
    return array('success' => false, 'message' => 'User not found');
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: login.php");
        exit;
    }
}

function is_delivery_boy() {
    return isset($_SESSION['is_delivery_boy']) && $_SESSION['is_delivery_boy'] == 1;
}

function require_delivery_boy() {
    if (!is_delivery_boy()) {
        header("Location: index.php");
        exit;
    }
}
