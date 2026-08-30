<?php
chdir(__DIR__);
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please login before placing an order.']);
    exit;
}

// Enforce authenticated rate limits
$rlRes = RateLimiter::checkAuthenticated($_SESSION['user_id'] ?? null);
if (!$rlRes['allowed']) {
    RateLimiter::enforceOrBlock($rlRes, true);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$validated = Validator::validate($_POST, [
    'restaurant_id' => ['type' => 'int', 'required' => true, 'min' => 1],
    'payment_method' => ['type' => 'string', 'required' => true, 'enum' => ['cash', 'upi']],
    'phone' => ['type' => 'phone', 'required' => true],
    'delivery_instructions' => ['type' => 'string', 'default' => '', 'max_len' => 500],
    'different_address' => ['type' => 'string']
]);

$restaurant_id = $validated['restaurant_id'];
$payment_method = $validated['payment_method'];
$phone = $validated['phone'];
$notes = $validated['delivery_instructions'];

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
    $addressSchema = [
        'delivery_address' => ['type' => 'string', 'required' => true, 'max_len' => 255],
        'delivery_city' => ['type' => 'string', 'required' => true, 'max_len' => 100],
        'delivery_state' => ['type' => 'string', 'required' => true, 'max_len' => 100],
        'delivery_zip' => ['type' => 'pincode', 'required' => true]
    ];
    $validatedAddress = Validator::validate($_POST, $addressSchema);
    
    $address = $validatedAddress['delivery_address'];
    $city = $validatedAddress['delivery_city'];
    $state = $validatedAddress['delivery_state'];
    $zip = $validatedAddress['delivery_zip'];
    
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
if ($subtotal <= 0) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
    exit;
}
// Use the location specific delivery charge
$delivery_fee = (float)$location['delivery_charge'];
$tax = round($subtotal * 0.05, 2);

// Re-validate and calculate discount server-side using applied promo code from session
$discount = 0.00;
$applied_code = null;
if (!empty($_SESSION['applied_promo_code'])) {
    $stmt_promo = $conn->prepare("SELECT * FROM promo_codes WHERE UPPER(code) = UPPER(?) AND is_active = 1 LIMIT 1");
    $stmt_promo->execute([$_SESSION['applied_promo_code']]);
    $promo = $stmt_promo->fetch(PDO::FETCH_ASSOC);
    if ($promo && $subtotal >= (float)$promo['min_order_amount']) {
        $applied_code = $promo['code'];
        $val = (float)$promo['discount_value'];
        if ($promo['discount_type'] === 'percentage') {
            $discount = ($subtotal * $val) / 100;
            if (!empty($promo['max_discount_amount'])) {
                $max_d = (float)$promo['max_discount_amount'];
                if ($discount > $max_d) {
                    $discount = $max_d;
                }
            }
        } else {
            $discount = $val;
            if ($discount > $subtotal) {
                $discount = $subtotal;
            }
        }
        $discount = round($discount, 2);
    }
}

$total = $subtotal + $delivery_fee + $tax - $discount;
$payment_status = ($payment_method === 'cash' || $payment_method === 'upi') ? 'pending' : 'paid';

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("INSERT INTO orders (user_id, restaurant_id, delivery_address, delivery_phone, subtotal, delivery_fee, tax, promo_code, discount_amount, total_amount, payment_method, payment_status, order_status, notes)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
    $stmt->execute([$user_id, $restaurant_id, $delivery_address, $phone, $subtotal, $delivery_fee, $tax, $applied_code, $discount, $total, $payment_method, $payment_status, $notes]);
    $order_id = $conn->lastInsertId();

    $stmt = $conn->prepare("INSERT INTO order_items (order_id, item_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($cart_items as $item) {
        $stmt->execute([$order_id, $item['item_id'], $item['quantity'], $item['price']]);
    }

    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);

    $conn->commit();
    
    // Clear applied promo code from session upon successful order
    unset($_SESSION['applied_promo_code']);
    
    echo json_encode(['success' => true, 'order_id' => $order_id]);
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Unable to place order. Please try again.']);
}
?>
