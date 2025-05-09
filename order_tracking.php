<?php
$page_title = "Order Tracking";
require_once 'includes/header.php';

// Redirect to login if not logged in
if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// If a specific order ID is provided, get details for that order
$single_order = false;
if ($order_id > 0) {
    $order = get_order_details($order_id, $user_id);
    if ($order) {
        $single_order = true;
        $restaurant = get_restaurant_by_id($order['restaurant_id']);
    } else {
        // Order not found or does not belong to this user
        $order_id = 0;
    }
}

// If no specific order or order not found, get all user orders
if (!$single_order) {
    $orders = get_user_orders($user_id);
}
?>

<div class="container py-4">
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
                
                <div class="card mb-4">
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
                        ?>">
                            <?php echo ucfirst($order['order_status']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="restaurant-info mb-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-store fa-2x text-primary me-3"></i>
                                <div>
                                    <h5 class="mb-0"><?php echo $restaurant['name']; ?></h5>
                                    <p class="text-muted mb-0"><?php echo $restaurant['address']; ?>, <?php echo $restaurant['city']; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="order-timeline mb-4">
                            <?php
                            $statuses = ['pending', 'confirmed', 'preparing', 'on the way', 'delivered'];
                            $current_status_index = array_search($order['order_status'], $statuses);
                            
                            // If order is cancelled, set a special case
                            if ($order['order_status'] == 'cancelled') {
                                $current_status_index = -1;
                            }
                            
                            foreach($statuses as $index => $status):
                                $is_active = $index <= $current_status_index;
                                $status_class = $is_active ? 'status-active' : '';
                                
                                // For cancelled orders, show only the pending status as active
                                if ($order['order_status'] == 'cancelled' && $index > 0) {
                                    $status_class = '';
                                }
                            ?>
                            <div class="order-status <?php echo $status_class; ?>">
                                <div class="status-circle"></div>
                                <h6><?php echo ucfirst($status); ?></h6>
                                <?php if ($index === $current_status_index && $order['order_status'] != 'cancelled'): ?>
                                <p class="text-muted small">
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
                                            echo 'Your food is on the way! Estimated delivery time: ' . 
                                                 date('h:i A', strtotime($order['order_date'] . ' +' . $restaurant['delivery_time'] . ' minutes'));
                                            break;
                                        case 'delivered':
                                            echo 'Your order has been delivered. Enjoy your meal!';
                                            break;
                                    }
                                    ?>
                                </p>
                                <?php elseif ($order['order_status'] == 'cancelled' && $index === 0): ?>
                                <p class="text-danger small">Your order has been cancelled.</p>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if ($order['order_status'] != 'delivered' && $order['order_status'] != 'cancelled'): ?>
                        <div class="estimated-delivery text-center mb-3">
                            <h5>Estimated Delivery Time</h5>
                            <p class="mb-0 lead">
                                <?php echo date('h:i A', strtotime($order['order_date'] . ' +' . $restaurant['delivery_time'] . ' minutes')); ?>
                            </p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($order['order_status'] == 'on the way'): ?>
                        <div class="text-center mb-3">
                            <p class="text-muted small">
                                <i class="fas fa-info-circle me-1"></i> 
                                Our delivery partner is on the way with your order. You'll receive a call when they arrive.
                            </p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($order['order_status'] == 'delivered'): ?>
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

<?php
require_once 'includes/footer.php';
?>
