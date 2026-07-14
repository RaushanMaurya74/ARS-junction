<?php
require_once 'auth.php';

// Unset delivery session variables
unset($_SESSION['delivery_boy_id']);
unset($_SESSION['delivery_boy_name']);
unset($_SESSION['delivery_boy_email']);

header("Location: login.php");
exit;
?>
