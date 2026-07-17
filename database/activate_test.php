<?php
require_once __DIR__ . '/../includes/db_connect.php';

$email = 'final_test_rest_owner@example.com';
$action = $_GET['action'] ?? 'activate';

try {
    $stmt = $conn->prepare("SELECT restaurant_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $rest_id = $stmt->fetchColumn();

    if ($rest_id) {
        if ($action === 'activate') {
            $stmt_up = $conn->prepare("UPDATE restaurants SET is_active = 1 WHERE restaurant_id = ?");
            $stmt_up->execute([$rest_id]);
            echo "Successfully activated restaurant ID: " . $rest_id . "\n";
        } else {
            $stmt_up = $conn->prepare("UPDATE restaurants SET is_active = 0 WHERE restaurant_id = ?");
            $stmt_up->execute([$rest_id]);
            echo "Successfully deactivated restaurant ID: " . $rest_id . "\n";
        }
    } else {
        echo "No restaurant found for user: " . $email . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
