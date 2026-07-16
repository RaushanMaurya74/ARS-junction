<?php
/**
 * Restaurant Owner Portal - Logout
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset restaurant session variables
unset($_SESSION['restaurant_owner_id']);
unset($_SESSION['restaurant_id']);
unset($_SESSION['restaurant_owner_name']);
unset($_SESSION['restaurant_owner_email']);
unset($_SESSION['restaurant_name']);

$reason = isset($_GET['reason']) ? '?reason=' . urlencode($_GET['reason']) : '';

// Redirect to login
header("Location: login.php" . $reason);
exit;
