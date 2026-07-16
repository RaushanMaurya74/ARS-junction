<?php
/**
 * Restaurant Owner Portal - Authentication Helper
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bootstrap DB connection (uses __DIR__ so the path is always correct
// regardless of which directory the Vercel router chdir'd into).
if (!isset($conn)) {
    require_once __DIR__ . '/../includes/db_connect.php';
}

function is_restaurant_logged_in() {
    return isset($_SESSION['restaurant_owner_id']) && isset($_SESSION['restaurant_id']);
}

function require_restaurant_login() {
    if (!is_restaurant_logged_in()) {
        header("Location: login.php");
        exit;
    }
}
