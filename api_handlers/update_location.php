<?php
chdir(__DIR__);
require_once '../includes/db_connect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if a delivery boy is logged in
if (!isset($_SESSION['delivery_boy_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$delivery_boy_id = $_SESSION['delivery_boy_id'];
$validated = Validator::validate($_POST, [
    'latitude' => ['type' => 'float', 'required' => true, 'min' => -90, 'max' => 90],
    'longitude' => ['type' => 'float', 'required' => true, 'min' => -180, 'max' => 180]
]);

$latitude = $validated['latitude'];
$longitude = $validated['longitude'];

if ($latitude == 0.0 || $longitude == 0.0) {
    echo json_encode(['success' => false, 'message' => 'Invalid coordinates']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE users SET latitude = ?, longitude = ?, location_updated_at = NOW() WHERE user_id = ? AND is_delivery_boy = 1");
    $stmt->execute([$latitude, $longitude, $delivery_boy_id]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("Update location database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An internal server error occurred.']);
}
?>
