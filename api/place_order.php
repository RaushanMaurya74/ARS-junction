<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please login before placing an order.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$restaurant_id = isset($_POST['restaurant_id']) ? intval($_POST['restaurant_id']) : 0;
$payment_method = isset($_POST['payment_method']) ? clean_input($_POST['payment_method']) : 'cash';
$phone = isset($_POST['phone']) ? clean_input($_POST['phone']) : '';
$notes = isset($_POST['delivery_instructions']) ? clean_input($_POST['delivery_instructions']) : '';

if ($restaurant_id <= 0 || empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Please complete the delivery details.']);
    exit;
}

if (!in_array($payment_method, ['cash', 'upi'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment method.']);
    exit;
}

$cart_items = get_cart_items($user_id);
if (empty($cart_items)) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
    exit;
}

foreach ($cart_items as $item) {
    if ((int)$item['restaurant_id'] !== $restaurant_id) {
        echo json_encode(['success' => false, 'message' => 'Please order from one restaurant at a time.']);
        exit;
    }
}

$user = get_user_by_id($user_id);
$restaurant = get_restaurant_by_id($restaurant_id);

if (!$restaurant) {
    echo json_encode(['success' => false, 'message' => 'Restaurant not found.']);
    exit;
}

$pincode = '';
if (isset($_POST['different_address'])) {
    $address = clean_input($_POST['delivery_address'] ?? '');
    $city = clean_input($_POST['delivery_city'] ?? '');
    $state = clean_input($_POST['delivery_state'] ?? '');
    $zip = clean_input($_POST['delivery_zip'] ?? '');
    
    if (empty($address) || empty($city) || empty($state) || empty($zip)) {
        echo json_encode(['success' => false, 'message' => 'All delivery address fields are compulsory.']);
        exit;
    }
    $delivery_address = trim($address . ', ' . $city . ', ' . $state . ' - ' . $zip, " ,-");
    $pincode = $zip;
} else {
    $pincode = $user['zip_code'] ?? '';
    $address = $user['address'] ?? '';
    
    if (empty($address) || empty($pincode)) {
        echo json_encode(['success' => false, 'message' => 'Your default address is incomplete. Please enter a delivery address.']);
        exit;
    }
    $delivery_address = trim(($user['address'] ?? '') . ', ' . ($user['city'] ?? '') . ', ' . ($user['state'] ?? '') . ' - ' . ($user['zip_code'] ?? ''), " ,-");
}

// Verify delivery pincode in DB
$stmt_pin = $conn->prepare("SELECT * FROM delivery_pincodes WHERE pincode = ? AND is_active = 1");
$stmt_pin->execute([$pincode]);
$location = $stmt_pin->fetch(PDO::FETCH_ASSOC);

if (!$location) {
    echo json_encode(['success' => false, 'message' => "Sorry, we do not deliver to pincode '{$pincode}' yet."]);
    exit;
}

$subtotal = calculate_cart_total($user_id);
// Use the location specific delivery charge
$delivery_fee = (float)$location['delivery_charge'];
$tax = round($subtotal * 0.05, 2);
$total = $subtotal + $delivery_fee + $tax;
$payment_status = ($payment_method === 'cash' || $payment_method === 'upi') ? 'pending' : 'paid';

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("INSERT INTO orders (user_id, restaurant_id, delivery_address, delivery_phone, subtotal, delivery_fee, tax, total_amount, payment_method, payment_status, order_status, notes)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
    $stmt->execute([$user_id, $restaurant_id, $delivery_address, $phone, $subtotal, $delivery_fee, $tax, $total, $payment_method, $payment_status, $notes]);
    $order_id = $conn->lastInsertId();

    $stmt = $conn->prepare("INSERT INTO order_items (order_id, item_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($cart_items as $item) {
        $stmt->execute([$order_id, $item['item_id'], $item['quantity'], $item['price']]);
    }

    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);

    $conn->commit();
    echo json_encode(['success' => true, 'order_id' => $order_id]);
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Unable to place order. Please try again.']);
}
?>
