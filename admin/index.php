<?php
// Redirect to dashboard if already logged in
require_once '../includes/db_connect.php';
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
} else {
    // Redirect to login page
    header("Location: login.php");
    exit;
}
?>
