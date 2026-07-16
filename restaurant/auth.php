<?php
/**
 * Restaurant Owner Portal - Authentication Helper
 */

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
