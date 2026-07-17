<?php
/**
 * Restaurant Owner Portal - Authentication Helper
 */

// Bootstrap DB connection first so the custom CookieSessionHandler is registered
if (!isset($conn)) {
    require_once __DIR__ . '/../includes/db_connect.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
