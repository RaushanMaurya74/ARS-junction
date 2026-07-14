<?php
chdir(__DIR__);
/**
 * API endpoint for handling social login (Facebook/Google)
 */

// Include necessary files
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get the parameters
$provider = isset($_POST['provider']) ? clean_input($_POST['provider']) : null;
$social_id = isset($_POST['social_id']) ? clean_input($_POST['social_id']) : null;
$name = isset($_POST['name']) ? clean_input($_POST['name']) : null;
$email = isset($_POST['email']) ? clean_input($_POST['email']) : null;

// Validate required parameters
if (!$provider || !$social_id || !$name || !$email) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email format'
    ]);
    exit;
}

// Validate provider
if (!in_array($provider, ['facebook', 'google'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid provider'
    ]);
    exit;
}

// Call the social login function
$result = social_login($name, $email, $social_id, $provider);

if ($result['success']) {
    // Determine redirect URL
    $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'index.php';
    unset($_SESSION['redirect_after_login']);
    
    echo json_encode([
        'success' => true,
        'user_id' => $result['user_id'],
        'new_user' => $result['new_user'],
        'redirect' => $redirect
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $result['message'] ?? 'Social login failed'
    ]);
}
?>
