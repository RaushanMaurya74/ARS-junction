<?php
chdir(__DIR__);
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please login to update cart']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$cart_id = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

if ($cart_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart item or quantity']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ? AND user_id = ?");
    $stmt->execute([$quantity, $cart_id, $_SESSION['user_id']]);

    $stmt = $conn->prepare("SELECT m.price * c.quantity as item_subtotal
                           FROM cart c
                           JOIN menu_items m ON c.item_id = m.item_id
                           WHERE c.cart_id = ? AND c.user_id = ?");
    $stmt->execute([$cart_id, $_SESSION['user_id']]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get cart total and count
    $stmt = $conn->prepare("SELECT SUM(m.price * c.quantity) as total, SUM(c.quantity) as count 
                           FROM cart c 
                           JOIN menu_items m ON c.item_id = m.item_id 
                           WHERE c.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'item_subtotal' => format_price($item['item_subtotal'] ?? 0),
        'cart_total' => format_price($row['total'] ?? 0),
        'cart_count' => $row['count'] ?? 0
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
}
?>

