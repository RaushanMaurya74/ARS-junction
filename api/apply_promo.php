<?php
chdir(__DIR__);
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please login before applying a promo code.']);
    exit;
}

$code = isset($_POST['code']) ? trim(clean_input($_POST['code'])) : '';
$subtotal = isset($_POST['subtotal']) ? floatval($_POST['subtotal']) : 0.0;

if (empty($code)) {
    // Clear applied promo code if empty code is passed
    unset($_SESSION['applied_promo_code']);
    echo json_encode(['success' => true, 'discount_amount' => 0.00, 'message' => 'Promo code cleared.']);
    exit;
}

if ($subtotal <= 0) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
    exit;
}

try {
    // Fetch promo code details
    $stmt = $conn->prepare("SELECT * FROM promo_codes WHERE UPPER(code) = UPPER(?) AND is_active = 1 LIMIT 1");
    $stmt->execute([$code]);
    $promo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$promo) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired promo code.']);
        exit;
    }

    $min_order = floatval($promo['min_order_amount']);
    if ($subtotal < $min_order) {
        $formatted_min = '₹' . number_format($min_order, 2);
        echo json_encode(['success' => false, 'message' => "This code requires a minimum order of {$formatted_min}."]);
        exit;
    }

    $discount = 0.0;
    $val = floatval($promo['discount_value']);

    if ($promo['discount_type'] === 'percentage') {
        $discount = ($subtotal * $val) / 100;
        if (!empty($promo['max_discount_amount'])) {
            $max_d = floatval($promo['max_discount_amount']);
            if ($discount > $max_d) {
                $discount = $max_d;
            }
        }
    } else {
        // Fixed discount
        $discount = $val;
        if ($discount > $subtotal) {
            $discount = $subtotal;
        }
    }

    // Save to session
    $_SESSION['applied_promo_code'] = $promo['code'];

    echo json_encode([
        'success' => true,
        'code' => $promo['code'],
        'discount_type' => $promo['discount_type'],
        'discount_value' => $val,
        'discount_amount' => round($discount, 2),
        'message' => 'Promo code applied successfully!'
    ]);

} catch (PDOException $e) {
    error_log("Apply promo database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Please try again later.']);
}
?>
