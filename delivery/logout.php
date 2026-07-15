<?php
require_once 'auth.php';

// Set delivery agent as offline in database
if (isset($_SESSION['delivery_boy_id'])) {
    $db_id = $_SESSION['delivery_boy_id'];
    try {
        $stmt = $conn->prepare("UPDATE users SET is_online = 0 WHERE user_id = ?");
        $stmt->execute([$db_id]);
    } catch (PDOException $e) {
        // Ignore DB error during logout redirect
    }
}

// Unset delivery session variables
unset($_SESSION['delivery_boy_id']);
unset($_SESSION['delivery_boy_name']);
unset($_SESSION['delivery_boy_email']);

header("Location: login.php");
exit;
?>
