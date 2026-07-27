<?php
chdir(__DIR__);
require_once '../includes/db_connect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

try {
    // Get the order details to verify ownership and find the delivery boy ID
    $stmt = $conn->prepare("SELECT delivery_boy_id, restaurant_id, order_status FROM orders WHERE order_id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    if (empty($order['delivery_boy_id'])) {
        echo json_encode([
            'success' => true,
            'assigned' => false,
            'message' => 'Delivery agent not yet assigned'
        ]);
        exit;
    }

    $delivery_boy_id = $order['delivery_boy_id'];

    // Fetch delivery boy's live coordinates
    $stmt_boy = $conn->prepare("SELECT name, phone, latitude, longitude, location_updated_at FROM users WHERE user_id = ? AND is_delivery_boy = 1");
    $stmt_boy->execute([$delivery_boy_id]);
    $boy = $stmt_boy->fetch(PDO::FETCH_ASSOC);

    if (!$boy) {
        echo json_encode(['success' => false, 'message' => 'Delivery agent details not found']);
        exit;
    }

    // Also get restaurant coordinates if available, or fall back to a mock default near Piro
    // Burger Junction = 25.3356, 84.4124
    // Pizza Paradise = 25.3375, 84.4150
    // Spice Garden = 25.3360, 84.4110
    // Dragon Wok = 25.3400, 84.4200
    $restaurant_id = $order['restaurant_id'];
    $lat_longs = [
        1 => ['lat' => 25.3375, 'lng' => 84.4150], // Pizza Paradise
        2 => ['lat' => 25.3356, 'lng' => 84.4124], // Burger Junction
        3 => ['lat' => 25.3360, 'lng' => 84.4110], // Spice Garden
        4 => ['lat' => 25.3400, 'lng' => 84.4200]  // Dragon Wok
    ];
    $restaurant_coords = $lat_longs[$restaurant_id] ?? ['lat' => 25.3356, 'lng' => 84.4124];

    echo json_encode([
        'success' => true,
        'assigned' => true,
        'order_status' => $order['order_status'],
        'delivery_boy' => [
            'name' => $boy['name'],
            'phone' => $boy['phone'],
            'latitude' => $boy['latitude'] ? floatval($boy['latitude']) : null,
            'longitude' => $boy['longitude'] ? floatval($boy['longitude']) : null,
            'updated_at' => $boy['location_updated_at']
        ],
        'restaurant' => $restaurant_coords
    ]);
} catch (PDOException $e) {
    error_log("Get delivery location database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An internal server error occurred.']);
}
?>
