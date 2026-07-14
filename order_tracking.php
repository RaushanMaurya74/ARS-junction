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

$page_title = "Order Tracking";
$extra_css = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />';
$extra_js = '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>';
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$success_msg = '';
$error_msg = '';

// Handle Order Cancellation POST
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'cancel_order') {
    if ($order_id > 0) {
        $check_order = get_order_details($order_id, $user_id);
        if ($check_order) {
            $elapsed = time() - strtotime($check_order['order_date']);
            
            if ($check_order['order_status'] === 'pending' && $elapsed <= 180) {
                $stmt = $conn->prepare("UPDATE orders SET order_status = 'cancelled' WHERE order_id = ?");
                if ($stmt->execute([$order_id])) {
                    $success_msg = "Your order #{$order_id} has been cancelled successfully.";
                } else {
                    $error_msg = "Failed to cancel the order. Please try again.";
                }
            } else {
                $error_msg = "Order cannot be cancelled. The 3-minute cancellation window has expired, or the order is already being prepared.";
            }
        } else {
            $error_msg = "Order not found.";
        }
    }
}

// If a specific order ID is provided, get details for that order
$single_order = false;
$seconds_elapsed = 0;
$can_cancel = false;

if ($order_id > 0) {
    $order = get_order_details($order_id, $user_id);
    if ($order) {
        $single_order = true;
        $restaurant = get_restaurant_by_id($order['restaurant_id']);
        
        // Calculate seconds elapsed for frontend cancellation countdown timer
        $seconds_elapsed = time() - strtotime($order['order_date']);
        $can_cancel = ($order['order_status'] === 'pending' && $seconds_elapsed <= 180);
    } else {
        $order_id = 0;
    }
}

// If no specific order or order not found, get all user orders
if (!$single_order) {
    $orders = get_user_orders($user_id);
}
?>

<div class="container py-4">
    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($single_order && $order): ?>
        <!-- Single Order Tracking -->
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Track Order #<?php echo $order_id; ?></h1>
                    <a href="order_tracking.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> All Orders
                    </a>
                </div>
                
                <div class="card mb-4" id="order-tracking-card">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Order Status</h5>
                        <span class="badge bg-<?php 
                            switch($order['order_status']) {
                                case 'pending': echo 'warning text-dark'; break;
                                case 'confirmed': echo 'info'; break;
                                case 'preparing': echo 'primary'; break;
                                case 'on the way': echo 'primary'; break;
                                case 'delivered': echo 'success'; break;
                                case 'cancelled': echo 'danger'; break;
                                default: echo 'secondary';
                            }
                        ?>" id="order-status-badge">
                            <?php echo ucfirst($order['order_status']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="restaurant-info mb-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-store fa-2x text-primary me-3"></i>
                                <div>
                                    <h5 class="mb-0"><?php echo htmlspecialchars($restaurant['name']); ?></h5>
                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($restaurant['address']); ?>, <?php echo htmlspecialchars($restaurant['city']); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Delivery Boy Info Container (Visible when assigned) -->
                        <div id="delivery-boy-info-box" class="alert alert-info border-primary mb-4 p-3 animate__animated animate__fadeIn" style="<?php echo empty($order['delivery_boy_id']) ? 'display: none;' : ''; ?>">
                            <?php if (!empty($order['delivery_boy_id'])): 
                                $db_boy = get_user_by_id($order['delivery_boy_id']);
                            ?>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-truck text-primary fa-lg me-2"></i>
                                    <strong>Delivery Agent:</strong>
                                    <span class="ms-1"><?php echo htmlspecialchars($db_boy['name']); ?></span>
                                </div>
                                <?php if (!empty($db_boy['phone'])): ?>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-phone text-success fa-lg me-2"></i>
                                    <strong>Contact:</strong>
                                    <a href="tel:<?php echo $db_boy['phone']; ?>" class="ms-1 text-decoration-none fw-bold"><?php echo htmlspecialchars($db_boy['phone']); ?></a>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Stepper Timeline Wrapper -->
                        <div class="order-timeline mb-4" id="order-timeline-wrapper">
                            <?php
                            $statuses = ['pending', 'confirmed', 'preparing', 'on the way', 'delivered'];
                            $current_status_index = array_search($order['order_status'], $statuses);
                            
                            if ($order['order_status'] == 'cancelled') {
                                $current_status_index = -1;
                            }
                            
                            foreach($statuses as $index => $status):
                                $is_active = $index <= $current_status_index;
                                $status_class = $is_active ? 'status-active' : '';
                                
                                if ($order['order_status'] == 'cancelled' && $index > 0) {
                                    $status_class = '';
                                }
                            ?>
                            <div class="order-status <?php echo $status_class; ?>" style="position: relative; padding-left: 70px; padding-bottom: 20px;">
                                <div class="status-circle" style="position: absolute; left: 25px; top: 0; width: 12px; height: 12px; border-radius: 50%; background-color: <?php echo $is_active ? 'var(--success-color)' : 'var(--gray-color)'; ?>; <?php echo ($index === $current_status_index && $order['order_status'] != 'cancelled') ? 'box-shadow: 0 0 0 5px rgba(40,167,69,0.3);' : ''; ?> z-index: 1;"></div>
                                <h6 class="fw-bold mb-1"><?php echo ucfirst($status); ?></h6>
                                <?php if ($index === $current_status_index && $order['order_status'] != 'cancelled'): ?>
                                <p class="text-muted small mb-0">
                                    <?php
                                    switch($status) {
                                        case 'pending':
                                            echo 'Your order has been received and is being processed.';
                                            break;
                                        case 'confirmed':
                                            echo 'Your order has been confirmed by the restaurant.';
                                            break;
                                        case 'preparing':
                                            echo 'The restaurant is preparing your delicious food.';
                                            break;
                                        case 'on the way':
                                            echo 'Your food is on the way! Our delivery agent is en route.';
                                            break;
                                        case 'delivered':
                                            echo 'Your order has been delivered. Enjoy your meal!';
                                            break;
                                    }
                                    ?>
                                </p>
                                <?php elseif ($order['order_status'] == 'cancelled' && $index === 0): ?>
                                <p class="text-danger small mb-0">Your order has been cancelled.</p>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div id="est-delivery-wrapper">
                            <?php if ($order['order_status'] != 'delivered' && $order['order_status'] != 'cancelled'): ?>
                            <div class="estimated-delivery text-center mb-3">
                                <h5>Estimated Delivery Time</h5>
                                <p class="mb-0 lead fw-bold text-primary">
                                    <?php echo date('h:i A', strtotime($order['order_date'] . ' +' . $restaurant['delivery_time'] . ' minutes')); ?>
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Live GPS Tracking Map -->
                        <div id="live-map-card" class="card mb-4 border-primary shadow-sm" style="display: none;">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2.5">
                                <h6 class="mb-0 fw-bold"><i class="fas fa-map-marked-alt me-2 animate__animated animate__pulse animate__infinite"></i>Live GPS Delivery Tracker</h6>
                                <span class="badge bg-light text-primary" id="gps-status-badge">Locating...</span>
                            </div>
                            <div class="card-body p-0">
                                <div id="live-gps-map" style="height: 350px; width: 100%;"></div>
                            </div>
                            <div class="card-footer bg-light py-2">
                                <div class="row align-items-center small">
                                    <div class="col-8">
                                        <i class="fas fa-motorcycle text-primary me-1"></i> <span id="tracker-boy-name">Your delivery agent</span> is en route.
                                    </div>
                                    <div class="col-4 text-end">
                                        <a href="#" id="call-boy-btn" class="btn btn-xs btn-outline-primary py-1 px-2.5 rounded"><i class="fas fa-phone-alt"></i> Call</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div id="delivered-actions-wrapper" style="<?php echo ($order['order_status'] === 'delivered') ? '' : 'display: none;'; ?>">
                            <?php if ($order['order_status'] == 'delivered'): ?>
                            <div class="text-center mb-4 border-bottom pb-4">
                                <h5 class="text-success fw-bold mb-2"><i class="fas fa-check-circle me-1"></i> Order Delivered!</h5>
                                <p class="text-muted small mb-3">Your receipt is now available. Click below to view and download your printable bill.</p>
                                <a href="download_invoice.php?order_id=<?php echo $order_id; ?>" target="_blank" class="btn btn-success px-4 py-2 fw-bold">
                                    <i class="fas fa-file-invoice me-2"></i> View & Download Bill
                                </a>
                            </div>
                            
                            <div class="text-center mb-3">
                                <p class="mb-3">How was your food? Please rate your experience!</p>
                                <?php if (!has_user_reviewed($user_id, $order['restaurant_id'])): ?>
                                <a href="restaurant_details.php?id=<?php echo $order['restaurant_id']; ?>#reviews" class="btn btn-primary">
                                    <i class="fas fa-star me-1"></i> Write a Review
                                </a>
                                <?php else: ?>
                                <p class="text-success">
                                    <i class="fas fa-check-circle me-1"></i> Thanks for your review!
                                </p>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div id="upi-payment-wrapper">
                    <?php if ($order['payment_method'] === 'upi' && $order['payment_status'] === 'pending'): 
                        $upi_id = get_site_setting('upi_id', '7979730721@rapl');
                        $payee_name = rawurlencode(get_site_setting('site_name', 'ARS Junction'));
                        $amount = number_format((float)$order['total_amount'], 2, '.', '');
                        $note = rawurlencode('Order #' . $order_id);
                        $upi_string = "upi://pay?pa={$upi_id}&pn={$payee_name}&am={$amount}&cu=INR&tn={$note}";
                        $qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($upi_string);
                    ?>
                    <!-- UPI QR Code Card -->
                    <div class="card mb-4 border-primary">
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
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Order Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <p class="mb-1"><strong>Order Date:</strong> <?php echo date('F d, Y h:i A', strtotime($order['order_date'])); ?></p>
                            <p class="mb-1"><strong>Payment Method:</strong> 
                                <?php 
                                switch($order['payment_method']) {
                                    case 'cash': echo 'Cash on Delivery'; break;
                                    case 'card': echo 'Credit/Debit Card'; break;
                                    case 'wallet': echo 'Digital Wallet'; break;
                                    case 'upi': echo 'UPI QR Code'; break;
                                }
                                ?>
                            </p>
                            <p class="mb-0"><strong>Payment Status:</strong> 
                                <span class="badge bg-<?php echo ($order['payment_status'] == 'paid') ? 'success' : (($order['payment_status'] == 'pending') ? 'warning text-dark' : 'danger'); ?>">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </p>
                        </div>
                        
                        <hr>
                        
                        <h6 class="mb-3">Items Ordered</h6>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($order['items'] as $item): ?>
                                    <tr>
                                        <td><?php echo $item['name']; ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td class="text-end"><?php echo format_price($item['price']); ?></td>
                                        <td class="text-end"><?php echo format_price($item['price'] * $item['quantity']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr>
                        
                        <div class="price-details">
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
                
                <div class="delivery-address card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Delivery Address</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><?php echo $order['delivery_address']; ?></p>
                        <p class="mb-0">Phone: <?php echo $order['delivery_phone']; ?></p>
                        <?php if (!empty($order['notes'])): ?>
                        <hr>
                        <h6>Notes</h6>
                        <p class="mb-0"><?php echo $order['notes']; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($can_cancel): ?>
                <div class="card mb-4 border-danger animate__animated animate__fadeIn">
                    <div class="card-body text-center p-4">
                        <h5 class="text-danger fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> Want to cancel your order?</h5>
                        <p class="text-muted mb-3">You can cancel your order within 3 minutes of placing it. Time remaining: <strong id="cancel-timer" class="text-danger fs-5">--:--</strong></p>
                        <form method="post" action="order_tracking.php?id=<?php echo $order_id; ?>" onsubmit="return confirm('Are you sure you want to cancel this order? This action cannot be undone.');">
                            <input type="hidden" name="action" value="cancel_order">
                            <button type="submit" class="btn btn-danger px-4 fw-bold">
                                <i class="fas fa-times-circle me-1"></i> Cancel Order
                            </button>
                        </form>
                    </div>
                </div>
                
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        let secondsElapsed = <?php echo $seconds_elapsed; ?>;
                        let maxSeconds = 180;
                        let timerElement = document.getElementById("cancel-timer");
                        
                        function updateTimer() {
                            let remaining = maxSeconds - secondsElapsed;
                            if (remaining <= 0) {
                                timerElement.innerHTML = "Expired";
                                setTimeout(function() {
                                    window.location.reload();
                                }, 1000);
                                return;
                            }
                            
                            let mins = Math.floor(remaining / 60);
                            let secs = remaining % 60;
                            timerElement.innerHTML = mins + ":" + (secs < 10 ? "0" : "") + secs;
                            secondsElapsed++;
                            setTimeout(updateTimer, 1000);
                        }
                        updateTimer();
                    });
                </script>
                <?php endif; ?>

                <?php if ($order['order_status'] != 'delivered' && $order['order_status'] != 'cancelled'): ?>
                <div class="text-center mb-4">
                    <p>Need help with your order?</p>
                    <a href="contact.php" class="btn btn-outline-primary">Contact Support</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- All Orders List -->
        <h1 class="mb-4">My Orders</h1>
        
        <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-bag display-4 text-muted mb-3"></i>
            <h4>No Orders Yet</h4>
            <p>You haven't placed any orders. Start exploring our delicious menu!</p>
            <a href="restaurants.php" class="btn btn-primary mt-2">Browse Restaurants</a>
        </div>
        <?php else: ?>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Restaurant</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['order_id']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                <td><?php echo $order['restaurant_name']; ?></td>
                                <td><?php echo format_price($order['total_amount']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        switch($order['order_status']) {
                                            case 'pending': echo 'warning text-dark'; break;
                                            case 'confirmed': echo 'info'; break;
                                            case 'preparing': echo 'primary'; break;
                                            case 'on the way': echo 'primary'; break;
                                            case 'delivered': echo 'success'; break;
                                            case 'cancelled': echo 'danger'; break;
                                            default: echo 'secondary';
                                        }
                                    ?>">
                                        <?php echo ucfirst($order['order_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo ($order['payment_status'] == 'paid') ? 'success' : (($order['payment_status'] == 'pending') ? 'warning text-dark' : 'danger'); ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="order_tracking.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-primary">
                                        Track Order
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
// Web Audio Synth for Chimes (requiring no assets/files)
function playNotificationSound(type = 'success') {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        if (type === 'new_order') {
            playBeep(audioCtx, 587.33, 0.15, 'triangle'); // D5 alert
            setTimeout(() => playBeep(audioCtx, 880, 0.25, 'sine'), 150); // A5 high alert
        } else if (type === 'assigned') {
            playBeep(audioCtx, 523.25, 0.12, 'sine'); // C5
            setTimeout(() => playBeep(audioCtx, 659.25, 0.12, 'sine'), 120); // E5
            setTimeout(() => playBeep(audioCtx, 783.99, 0.2, 'sine'), 240); // G5
        } else {
            playBeep(audioCtx, 523.25, 0.15, 'sine'); // C5 sweet success
            setTimeout(() => playBeep(audioCtx, 783.99, 0.3, 'sine'), 150); // G5
        }
    } catch (e) {
        console.warn('AudioContext failed:', e);
    }
}

function playBeep(ctx, frequency, duration, type) {
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.type = type;
    osc.frequency.value = frequency;
    gain.gain.setValueAtTime(0.15, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.00001, ctx.currentTime + duration);
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.start();
    osc.stop(ctx.currentTime + duration);
}

document.addEventListener('DOMContentLoaded', function() {
    <?php if ($single_order && $order): ?>
    const orderId = <?php echo $order_id; ?>;
    let currentStatus = <?php echo json_encode($order['order_status']); ?>;
    let currentPayment = <?php echo json_encode($order['payment_status']); ?>;
    let currentDeliveryBoy = <?php echo json_encode($order['delivery_boy_id'] ?? ''); ?>;

    // --- Live GPS Tracking Leaflet Integration ---
    let gpsMap = null;
    let deliveryBoyMarker = null;
    let restaurantMarker = null;
    let customerMarker = null;
    let gpsInterval = null;

    function initGpsTracking() {
        if (typeof L === 'undefined') {
            console.warn('Leaflet library not loaded.');
            return;
        }

        const mapContainer = document.getElementById('live-gps-map');
        if (!mapContainer) return;

        fetch('api/get_delivery_location.php?order_id=' + orderId)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.assigned && data.delivery_boy.latitude && data.delivery_boy.longitude) {
                document.getElementById('live-map-card').style.display = 'block';
                document.getElementById('tracker-boy-name').textContent = escapeHtml(data.delivery_boy.name);
                
                const callBtn = document.getElementById('call-boy-btn');
                if (callBtn && data.delivery_boy.phone) {
                    callBtn.href = 'tel:' + data.delivery_boy.phone;
                }

                const dbLat = data.delivery_boy.latitude;
                const dbLng = data.delivery_boy.longitude;
                const resLat = data.restaurant.lat;
                const resLng = data.restaurant.lng;

                // Mock customer location slightly offset near restaurant for the tracking visualization
                const custLat = resLat + 0.005;
                const custLng = resLng + 0.005;

                gpsMap = L.map('live-gps-map').setView([dbLat, dbLng], 14);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(gpsMap);

                const bikeIcon = L.divIcon({
                    html: '<div style="background-color: #0d6efd; color: white; border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"><i class="fas fa-motorcycle" style="font-size: 16px;"></i></div>',
                    className: '',
                    iconSize: [35, 35],
                    iconAnchor: [17, 17]
                });

                const shopIcon = L.divIcon({
                    html: '<div style="background-color: #dc3545; color: white; border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"><i class="fas fa-store" style="font-size: 16px;"></i></div>',
                    className: '',
                    iconSize: [35, 35],
                    iconAnchor: [17, 17]
                });

                const homeIcon = L.divIcon({
                    html: '<div style="background-color: #198754; color: white; border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"><i class="fas fa-home" style="font-size: 16px;"></i></div>',
                    className: '',
                    iconSize: [35, 35],
                    iconAnchor: [17, 17]
                });

                restaurantMarker = L.marker([resLat, resLng], {icon: shopIcon}).addTo(gpsMap).bindPopup('Restaurant');
                customerMarker = L.marker([custLat, custLng], {icon: homeIcon}).addTo(gpsMap).bindPopup('Your Delivery Address');
                deliveryBoyMarker = L.marker([dbLat, dbLng], {icon: bikeIcon}).addTo(gpsMap).bindPopup('Delivery Agent: ' + escapeHtml(data.delivery_boy.name));

                const group = new L.featureGroup([restaurantMarker, customerMarker, deliveryBoyMarker]);
                gpsMap.fitBounds(group.getBounds().pad(0.1));

                document.getElementById('gps-status-badge').textContent = 'Live Tracking';
                document.getElementById('gps-status-badge').className = 'badge bg-success';

                startGpsPolling();
            }
        })
        .catch(err => console.error('Error initializing GPS tracking:', err));
    }

    function startGpsPolling() {
        gpsInterval = setInterval(function() {
            fetch('api/get_delivery_location.php?order_id=' + orderId)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.assigned && data.delivery_boy.latitude && data.delivery_boy.longitude) {
                    const lat = data.delivery_boy.latitude;
                    const lng = data.delivery_boy.longitude;
                    
                    if (deliveryBoyMarker) {
                        deliveryBoyMarker.setLatLng([lat, lng]);
                    }
                    
                    if (gpsMap && restaurantMarker && customerMarker && deliveryBoyMarker) {
                        const group = new L.featureGroup([restaurantMarker, customerMarker, deliveryBoyMarker]);
                        gpsMap.fitBounds(group.getBounds().pad(0.1));
                    }
                }
            })
            .catch(err => console.error('Error updating GPS tracking:', err));
        }, 5000);
    }

    if (orderId > 0 && currentStatus !== 'delivered' && currentStatus !== 'cancelled') {
        initGpsTracking();
    }

    if (orderId > 0 && currentStatus !== 'delivered' && currentStatus !== 'cancelled') {
        const interval = setInterval(function() {
            fetch('api/get_order_status.php?order_id=' + orderId)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    let changed = false;
                    
                    if (data.order_status !== currentStatus) {
                        currentStatus = data.order_status;
                        changed = true;
                        playNotificationSound('success');
                        showToast('Order status updated: ' + currentStatus.toUpperCase(), 'success');
                    }
                    
                    if (data.payment_status !== currentPayment) {
                        currentPayment = data.payment_status;
                        changed = true;
                        playNotificationSound('success');
                        showToast('Payment status updated: ' + currentPayment.toUpperCase(), 'success');
                    }
                    
                    if (data.delivery_boy_phone !== '' && currentDeliveryBoy === '') {
                        currentDeliveryBoy = 'assigned';
                        changed = true;
                        playNotificationSound('assigned');
                        showToast('Delivery agent assigned to your order!', 'info');
                    }

                    if (changed) {
                        updateTrackingUI(data);
                    }
                    
                    if (data.order_status === 'delivered' || data.order_status === 'cancelled') {
                        clearInterval(interval);
                    }
                }
            })
            .catch(err => console.error('Error polling status:', err));
        }, 3000);
    }

    function updateTrackingUI(data) {
        // 1. Update status badge
        const badge = document.getElementById('order-status-badge');
        if (badge) {
            badge.textContent = data.order_status.charAt(0).toUpperCase() + data.order_status.slice(1);
            badge.className = 'badge bg-' + getStatusBadgeClass(data.order_status);
        }

        // 2. Update payment status badge
        const pBadge = document.getElementById('payment-status-badge');
        if (pBadge) {
            pBadge.textContent = data.payment_status.charAt(0).toUpperCase() + data.payment_status.slice(1);
            pBadge.className = 'badge bg-' + (data.payment_status === 'paid' ? 'success' : 'warning text-dark');
        }

        // 3. Update delivery boy info card
        const dbBox = document.getElementById('delivery-boy-info-box');
        if (dbBox) {
            if (data.delivery_boy_name !== 'Not assigned yet') {
                dbBox.innerHTML = `
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-truck text-primary fa-lg me-2"></i>
                    <strong>Delivery Agent:</strong>
                    <span class="ms-1">${escapeHtml(data.delivery_boy_name)}</span>
                </div>
                ${data.delivery_boy_phone ? `
                <div class="d-flex align-items-center">
                    <i class="fas fa-phone text-success fa-lg me-2"></i>
                    <strong>Contact:</strong>
                    <a href="tel:${data.delivery_boy_phone}" class="ms-1 text-decoration-none fw-bold">${escapeHtml(data.delivery_boy_phone)}</a>
                </div>` : ''}`;
                dbBox.style.display = 'block';
            } else {
                dbBox.style.display = 'none';
            }
        }

        // 4. Rebuild Stepper Timeline
        const timeline = document.getElementById('order-timeline-wrapper');
        if (timeline) {
            const statuses = ['pending', 'confirmed', 'preparing', 'on the way', 'delivered'];
            const currentIndex = statuses.indexOf(data.order_status);
            
            let timelineHtml = '';
            statuses.forEach((status, idx) => {
                const isActive = idx <= currentIndex;
                const isCurrent = idx === currentIndex;
                const statusClass = isActive ? 'status-active' : '';
                
                let circleStyle = isActive ? 'background-color: var(--success-color);' : 'background-color: var(--gray-color);';
                if (isCurrent && data.order_status !== 'delivered') {
                    circleStyle += ' box-shadow: 0 0 0 5px rgba(40, 167, 69, 0.3);';
                }

                let textHtml = '';
                if (isCurrent) {
                    switch(status) {
                        case 'pending': textHtml = 'Your order has been received and is being processed.'; break;
                        case 'confirmed': textHtml = 'Your order has been confirmed by the restaurant.'; break;
                        case 'preparing': textHtml = 'The restaurant is preparing your delicious food.'; break;
                        case 'on the way': textHtml = 'Your food is on the way! Our delivery agent is en route.'; break;
                        case 'delivered': textHtml = 'Your order has been delivered. Enjoy your meal!'; break;
                    }
                }

                timelineHtml += `
                <div class="order-status ${statusClass}" style="position: relative; padding-left: 70px; padding-bottom: 20px;">
                    <div class="status-circle" style="position: absolute; left: 25px; top: 0; width: 12px; height: 12px; border-radius: 50%; ${circleStyle} z-index: 1;"></div>
                    <h6 class="fw-bold mb-1">${status.charAt(0).toUpperCase() + status.slice(1)}</h6>
                    ${textHtml ? `<p class="text-muted small mb-0">${textHtml}</p>` : ''}
                </div>`;
            });
            timeline.innerHTML = timelineHtml;
        }

        // 5. Hide UPI QR if paid
        const upiBox = document.getElementById('upi-payment-wrapper');
        if (upiBox && data.payment_status === 'paid') {
            upiBox.style.transition = 'all 0.5s ease';
            upiBox.style.opacity = '0';
            upiBox.style.transform = 'scale(0.9)';
            setTimeout(() => upiBox.remove(), 500);
        }

        // 6. Handle Delivery completion actions
        if (data.order_status === 'delivered') {
            if (gpsInterval) {
                clearInterval(gpsInterval);
            }
            const mapCard = document.getElementById('live-map-card');
            if (mapCard) mapCard.remove();
            
            const estBox = document.getElementById('est-delivery-wrapper');
            if (estBox) estBox.remove();
            
            const actionsBox = document.getElementById('delivered-actions-wrapper');
            if (actionsBox) {
                actionsBox.innerHTML = `
                <div class="text-center mb-4 border-bottom pb-4 animate__animated animate__bounceIn">
                    <h5 class="text-success fw-bold mb-2"><i class="fas fa-check-circle me-1"></i> Order Delivered!</h5>
                    <p class="text-muted small mb-3">Your receipt is now available. Click below to view and download your printable bill.</p>
                    <a href="download_invoice.php?order_id=${data.order_id}" target="_blank" class="btn btn-success px-4 py-2 fw-bold">
                        <i class="fas fa-file-invoice me-2"></i> View & Download Bill
                    </a>
                </div>
                <div class="text-center mb-3">
                    <p class="mb-3">How was your food? Please rate your experience!</p>
                    <a href="restaurant_details.php?id=<?php echo $order['restaurant_id']; ?>#reviews" class="btn btn-primary">
                        <i class="fas fa-star me-1"></i> Write a Review
                    </a>
                </div>`;
                actionsBox.style.display = 'block';
            }

            const cancelBox = document.getElementById('cancel-order-card');
            if (cancelBox) cancelBox.remove();
        }
    }

    function getStatusBadgeClass(status) {
        switch(status) {
            case 'pending': return 'warning text-dark';
            case 'confirmed': return 'info';
            case 'preparing': return 'primary';
            case 'on the way': return 'primary';
            case 'delivered': return 'success';
            case 'cancelled': return 'danger';
            default: return 'secondary';
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    <?php endif; ?>
});
</script>

<?php
require_once 'includes/footer.php';
?>
