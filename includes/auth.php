<?php
// User authentication functions

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function is_admin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == true;
}

// Authenticate user
function authenticate_user($email, $password) {
    global $conn;

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Password is correct, create session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['is_admin'] = $user['is_admin'];

        return $user;
    }

    return false;
}

// Register a new user
function register_user($name, $email, $password, $phone = null) {
    global $conn;

    // Check if email already exists
    if (email_exists($email)) {
        return array('success' => false, 'message' => 'Email already exists');
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $hashed_password, $phone]);

    if ($stmt->rowCount() > 0) {
        $user_id = $conn->lastInsertId();

        // Create session for the new user
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['is_admin'] = false;

        return array('success' => true, 'user_id' => $user_id);
    } else {
        return array('success' => false, 'message' => 'Registration failed: ' . $conn->error);
    }
}

// Social login (Google/Facebook)
function social_login($name, $email, $social_id, $social_type) {
    global $conn;

    // Check if user exists with this social id
    $stmt = $conn->prepare("SELECT * FROM users WHERE social_id = ? AND social_type = ?");
    $stmt->execute([$social_id, $social_type]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // User exists, log them in
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['is_admin'] = $user['is_admin'];

        return array('success' => true, 'user_id' => $user['user_id'], 'new_user' => false);
    } else {
        // Check if email exists but without social login
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Email exists, update with social info
            $stmt = $conn->prepare("UPDATE users SET social_id = ?, social_type = ? WHERE user_id = ?");
            $stmt->execute([$social_id, $social_type, $user['user_id']]);

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['is_admin'] = $user['is_admin'];

            return array('success' => true, 'user_id' => $user['user_id'], 'new_user' => false);
        } else {
            // New user, create account
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, social_id, social_type) VALUES (?, ?, '', ?, ?)");

            if ($stmt->execute([$name, $email, $social_id, $social_type])) {
                $user_id = $conn->lastInsertId();

                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['is_admin'] = false;

                return array('success' => true, 'user_id' => $user_id, 'new_user' => true);
            } else {
                return array('success' => false, 'message' => 'Social login failed');
            }
        }
    }
}

// Logout user
function logout() {
    // Unset all session variables
    $_SESSION = array();

    // Destroy the session
    session_destroy();

    // Redirect to home page
    header("Location: index.php");
    exit;
}

// Update user profile
function update_user_profile($user_id, $data) {
    global $conn;

    $name = isset($data['name']) ? clean_input($data['name']) : null;
    $phone = isset($data['phone']) ? clean_input($data['phone']) : null;
    $address = isset($data['address']) ? clean_input($data['address']) : null;
    $city = isset($data['city']) ? clean_input($data['city']) : null;
    $state = isset($data['state']) ? clean_input($state['state']) : null;
    $zip_code = isset($data['zip_code']) ? clean_input($data['zip_code']) : null;

    $sql = "UPDATE users SET ";
    $params = array();
    $types = "";

    if ($name !== null) {
        $sql .= "name = ?, ";
        $params[] = $name;
        $types .= "s";
        // Update session name too
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
            $_SESSION['user_name'] = $name;
        }
    }

    if ($phone !== null) {
        $sql .= "phone = ?, ";
        $params[] = $phone;
        $types .= "s";
    }

    if ($address !== null) {
        $sql .= "address = ?, ";
        $params[] = $address;
        $types .= "s";
    }

    if ($city !== null) {
        $sql .= "city = ?, ";
        $params[] = $city;
        $types .= "s";
    }

    if ($state !== null) {
        $sql .= "state = ?, ";
        $params[] = $state;
        $types .= "s";
    }

    if ($zip_code !== null) {
        $sql .= "zip_code = ?, ";
        $params[] = $zip_code;
        $types .= "s";
    }

    // Remove the trailing comma and space
    $sql = rtrim($sql, ", ");

    $sql .= " WHERE user_id = ?";
    $params[] = $user_id;
    $types .= "i";

    $stmt = $conn->prepare($sql);

    // Dynamically bind parameters
   // $stmt->bind_param($types, ...$params);
   //The fix is not correct.

    if ($stmt->execute($params)) {
        return array('success' => true);
    } else {
        return array('success' => false, 'message' => 'Profile update failed: ' . $conn->error);
    }
}

// Change password
function change_password($user_id, $current_password, $new_password) {
    global $conn;

    // Get the current user
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
      //  $user = $result->fetch_assoc();

        // Verify current password
        if (password_verify($current_password, $result['password'])) {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update password
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
           // $stmt->bind_param("si", $hashed_password, $user_id);
           $stmt->execute([$hashed_password, $user_id]);

            if ($stmt->rowCount() > 0) {
                return array('success' => true);
            } else {
                return array('success' => false, 'message' => 'Password update failed: ' . $conn->error);
            }
        } else {
            return array('success' => false, 'message' => 'Current password is incorrect');
        }
    } else {
        return array('success' => false, 'message' => 'User not found');
    }
}

// Require login, redirect if not logged in
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: login.php");
        exit;
    }
}
?>