<?php
// Start session and check authentication before outputting headers
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect to login if not logged in
if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$page_title = "Order Confirmation";
require_once 'includes/header.php';

// Check if order ID is provided
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header("Location: index.php");
    exit;
}

$order_id = intval($_GET['order_id']);
$user_id = $_SESSION['user_id'];

// Get order details with security check (must belong to the current user)
$order = get_order_details($order_id, $user_id);

// Redirect if order not found or does not belong to the current user
if (!$order) {
    header("Location: order_tracking.php");
    exit;
}

// Get restaurant details
$restaurant = get_restaurant_by_id($order['restaurant_id']);
?>

<!-- Order Confirmation Section -->
<section class="mb-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="fas fa-check-circle text-success display-1"></i>
                        </div>
                        <h1 class="mb-3">Order Confirmed!</h1>
                        <p class="lead mb-4">Thank you for your order. Your food is on the way!</p>
                        <div class="d-flex justify-content-center mb-4">
                            <div class="bg-light rounded p-3 px-4 me-3">
                                <h5>Order ID</h5>
                                <p class="mb-0 fw-bold">#<?php echo $order_id; ?></p>
                            </div>
                            <div class="bg-light rounded p-3 px-4">
                                <h5>Estimated Delivery</h5>
                                <p class="mb-0 fw-bold"><?php echo date('h:i A', strtotime($order['order_date'] . ' +' . $restaurant['delivery_time'] . ' minutes')); ?></p>
                            </div>
                        </div>
                        <div class="text-center mb-4">
                            <a href="order_tracking.php?id=<?php echo $order_id; ?>" class="btn btn-primary">
                                <i class="fas fa-map-marker-alt me-2"></i> Track Your Order
                            </a>
                        </div>
                    </div>
                </div>
                
                <?php if ($order['payment_method'] === 'upi' && $order['payment_status'] === 'pending'): 
                    $upi_id = get_site_setting('upi_id', '7979730721@rapl');
                    $payee_name = rawurlencode(get_site_setting('site_name', 'ARS Junction'));
                    $amount = number_format((float)$order['total_amount'], 2, '.', '');
                    $note = rawurlencode('Order #' . $order_id);
                    $upi_string = "upi://pay?pa={$upi_id}&pn={$payee_name}&am={$amount}&cu=INR&tn={$note}";
                    $qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($upi_string);
                ?>
                <!-- UPI QR Code Card -->
                <div class="card mt-4 border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0 text-center"><i class="fas fa-qrcode me-2"></i> Scan & Pay with UPI</h5>
                    </div>
                    <div class="card-body text-center p-4">
                        <p class="mb-3">Scan this QR code using GPay, PhonePe, Paytm, BHIM, or any UPI app to complete your payment.</p>
                        <div class="mb-3">
                            <img src="<?php echo $qr_api_url; ?>" alt="UPI QR Code" class="img-fluid border p-2 bg-white" style="max-width: 220px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                        </div>
                        <h4 class="mb-1 text-primary"><?php echo format_price($order['total_amount']); ?></h4>
                        <p class="text-muted small mb-3">UPI ID: <strong><?php echo htmlspecialchars($upi_id); ?></strong></p>
                        <div class="alert alert-warning d-inline-block py-2 px-3 mb-0" style="font-size: 0.9rem;">
                            <i class="fas fa-spinner fa-spin me-2"></i> After payment, our team/delivery boy will confirm your payment.
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Order Details -->
                <div class="card mt-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Order Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="restaurant-info mb-3">
                            <h6><?php echo $restaurant['name']; ?></h6>
                            <p class="text-muted small mb-0"><?php echo $restaurant['address']; ?>, <?php echo $restaurant['city']; ?></p>
                        </div>
                        
                        <hr>
                        
                        <div class="order-items mb-3">
                            <h6 class="mb-3">Items Ordered</h6>
                            <?php foreach($order['items'] as $item): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?php echo $item['quantity']; ?> × <?php echo $item['name']; ?></span>
                                <span><?php echo format_price($item['price'] * $item['quantity']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <hr>
                        
                        <div class="delivery-info mb-3">
                            <h6 class="mb-2">Delivery Address</h6>
                            <p class="mb-0"><?php echo $order['delivery_address']; ?></p>
                            <p class="mb-0">Phone: <?php echo $order['delivery_phone']; ?></p>
                        </div>
                        
                        <hr>
                        
                        <div class="payment-info mb-3">
                            <h6 class="mb-2">Payment Information</h6>
                            <p class="mb-2">
                                <span class="fw-bold">Method:</span> 
                                <?php 
                                switch($order['payment_method']) {
                                    case 'cash': echo 'Cash on Delivery'; break;
                                    case 'card': echo 'Credit/Debit Card'; break;
                                    case 'wallet': echo 'Digital Wallet'; break;
                                    case 'upi': echo 'UPI QR Code'; break;
                                }
                                ?>
                            </p>
                            <p class="mb-2">
                                <span class="fw-bold">Status:</span> 
                                <span class="badge bg-<?php echo ($order['payment_status'] == 'paid') ? 'success' : (($order['payment_status'] == 'pending') ? 'warning text-dark' : 'danger'); ?>">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </p>
                        </div>
                        
                        <hr>
                        
                        <div class="price-details">
                            <h6 class="mb-2">Price Details</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span><?php echo format_price($order['subtotal']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Delivery Fee:</span>
                                <span><?php echo format_price($order['delivery_fee']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tax:</span>
                                <span><?php echo format_price($order['tax']); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>Total:</strong>
                                <strong><?php echo format_price($order['total_amount']); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <p>Want to order more food?</p>
                    <a href="restaurants.php" class="btn btn-outline-primary me-2">Browse Restaurants</a>
                    <a href="menu.php" class="btn btn-outline-primary">Explore Menu</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require_once 'includes/footer.php';
?>
