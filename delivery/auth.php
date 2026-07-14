<?php
require_once '../includes/db_connect.php';

function is_delivery_logged_in() {
    return isset($_SESSION['delivery_boy_id']);
}

function require_delivery_login() {
    if (!is_delivery_logged_in()) {
        header("Location: login.php");
        exit;
    }
}
?>
