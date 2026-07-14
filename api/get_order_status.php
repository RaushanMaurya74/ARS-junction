<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$user_id = $_SESSION['user_id'];

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
    exit;
}

// Fetch order details with safety checks
$order = get_order_details($order_id, $user_id);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found.']);
    exit;
}

// Fetch delivery boy info if assigned
$delivery_boy_name = 'Not assigned yet';
$delivery_boy_phone = '';
if (!empty($order['delivery_boy_id'])) {
    $db_boy = get_user_by_id($order['delivery_boy_id']);
    if ($db_boy) {
        $delivery_boy_name = $db_boy['name'];
        $delivery_boy_phone = $db_boy['phone'];
    }
}

echo json_encode([
    'success' => true,
    'order_id' => $order['order_id'],
    'order_status' => $order['order_status'],
    'payment_status' => $order['payment_status'],
    'delivery_boy_name' => $delivery_boy_name,
    'delivery_boy_phone' => $delivery_boy_phone,
    'updated_at' => $order['updated_at']
]);
?>
