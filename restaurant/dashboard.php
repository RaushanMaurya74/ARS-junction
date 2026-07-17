<?php
/**
 * Restaurant Owner Portal - Dashboard & Orders Management
 */

$page_title = "Restaurant Dashboard";
require_once 'includes/restaurant_header.php';

$success_msg = '';
$error_msg = '';

// Handle order status updates and assignments
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $order_id = intval($_POST['order_id']);
        $action = $_POST['action'];
        
        // Load order details first to verify ownership
        $order = get_order_details($order_id);
        
        if ($order && $order['restaurant_id'] == $restaurant_id) {
            global $conn;
            
            if ($action === 'prepare') {
                // Transition Pending/Confirmed to Preparing
                $stmt = $conn->prepare("UPDATE orders SET order_status = 'preparing', confirmed_at = COALESCE(confirmed_at, NOW()) WHERE order_id = ? AND restaurant_id = ?");
                if ($stmt->execute([$order_id, $restaurant_id])) {
                    $success_msg = "Order #{$order_id} is now in preparation.";
                } else {
                    $error_msg = "Failed to update order status.";
                }
            } elseif ($action === 'cancel') {
                // Cancel order
                $stmt = $conn->prepare("UPDATE orders SET order_status = 'cancelled' WHERE order_id = ? AND restaurant_id = ?");
                if ($stmt->execute([$order_id, $restaurant_id])) {
                    $success_msg = "Order #{$order_id} has been cancelled.";
                } else {
                    $error_msg = "Failed to cancel order.";
                }
            } elseif ($action === 'assign_delivery') {
                $delivery_boy_id = intval($_POST['delivery_boy_id']);
                
                // Verify that delivery boy belongs to this restaurant
                $stmt_db = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND is_delivery_boy = 1 AND restaurant_id = ?");
                $stmt_db->execute([$delivery_boy_id, $restaurant_id]);
                
                if ($delivery_boy_id === 0 || $stmt_db->fetch()) {
                    $db_val = ($delivery_boy_id > 0) ? $delivery_boy_id : null;
                    
                    $stmt = $conn->prepare("UPDATE orders SET delivery_boy_id = ? WHERE order_id = ? AND restaurant_id = ?");
                    if ($stmt->execute([$db_val, $order_id, $restaurant_id])) {
                        $success_msg = "Delivery boy assigned successfully.";
                        
                        // Automatically transition order_status to 'confirmed' if it was 'pending'
                        if ($order['order_status'] === 'pending' && $db_val !== null) {
                            $stmt_up = $conn->prepare("UPDATE orders SET order_status = 'confirmed', confirmed_at = NOW() WHERE order_id = ?");
                            if ($stmt_up->execute([$order_id])) {
                                send_order_confirmation_email($order_id);
                            }
                        }
                    } else {
                        $error_msg = "Failed to assign delivery boy.";
                    }
                } else {
                    $error_msg = "Invalid delivery boy assignment.";
                }
            }
        } else {
            $error_msg = "Access Denied: Order does not belong to your restaurant.";
        }
    }
}

// Fetch dashboard stats (strictly scoped)
global $conn;

// 1. Pending orders count
$stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE restaurant_id = ? AND order_status = 'pending'");
$stmt->execute([$restaurant_id]);
$stats_pending = $stmt->fetchColumn();

// 2. Preparing orders count
$stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE restaurant_id = ? AND order_status = 'preparing'");
$stmt->execute([$restaurant_id]);
$stats_preparing = $stmt->fetchColumn();

// 3. Delivered orders count
$stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE restaurant_id = ? AND order_status = 'delivered'");
$stmt->execute([$restaurant_id]);
$stats_delivered = $stmt->fetchColumn();

// 4. Total revenue
$stmt = $conn->prepare("SELECT SUM(total_amount) FROM orders WHERE restaurant_id = ? AND order_status = 'delivered'");
$stmt->execute([$restaurant_id]);
$stats_revenue = $stmt->fetchColumn() ?: 0.00;

// 5. Active assigned delivery boys
$stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE restaurant_id = ? AND is_delivery_boy = 1 AND is_online = 1");
$stmt->execute([$restaurant_id]);
$stats_active_boys = $stmt->fetchColumn();


// Filter orders by status
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : 'all';
$valid_statuses = ['pending', 'confirmed', 'preparing', 'on the way', 'delivered', 'cancelled'];

$sql_orders = "SELECT o.*, u.name as customer_name 
               FROM orders o 
               JOIN users u ON o.user_id = u.user_id 
               WHERE o.restaurant_id = :restaurant_id";

if (in_array($status_filter, $valid_statuses)) {
    $sql_orders .= " AND o.order_status = :status";
}
$sql_orders .= " ORDER BY o.order_date DESC";

$stmt_orders = $conn->prepare($sql_orders);
$stmt_orders->bindValue(':restaurant_id', $restaurant_id, PDO::PARAM_INT);
if (in_array($status_filter, $valid_statuses)) {
    $stmt_orders->bindValue(':status', $status_filter, PDO::PARAM_STR);
}
$stmt_orders->execute();
$orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);

// Fetch all delivery boys for this restaurant to populate the dropdowns
$stmt_boys = $conn->prepare("SELECT user_id, name, is_online FROM users WHERE is_delivery_boy = 1 AND restaurant_id = ? ORDER BY name");
$stmt_boys->execute([$restaurant_id]);
$delivery_boys = $stmt_boys->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid animated-fade-in">
    <!-- Messages -->
    <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Dashboard Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard & Orders</h1>
        <a href="dashboard.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-sync-alt me-1"></i> Refresh Data</a>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <!-- Revenue Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow-sm py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Revenue (Delivered)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo format_price($stats_revenue); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-indian-rupee-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Orders Card -->
        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-warning shadow-sm py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Orders</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats_pending; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preparing Card -->
        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-primary shadow-sm py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Preparing</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats_preparing; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-fire-burner fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Completed Card -->
        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-info shadow-sm py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Delivered</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats_delivered; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-circle-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Online Delivery Boys -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow-sm py-2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Online Delivery Partners</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats_active_boys; ?> <span class="text-muted small">/ <?php echo count($delivery_boys); ?></span></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-truck-ramp-box fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Filter Navigation -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h6 class="mb-0 fw-bold text-secondary">Filter Orders</h6>
        </div>
        <div class="card-body py-2">
            <ul class="nav nav-pills small">
                <li class="nav-item me-2">
                    <a class="nav-link <?php echo $status_filter === 'all' ? 'active' : ''; ?>" href="dashboard.php?status=all">All Orders</a>
                </li>
                <li class="nav-item me-2">
                    <a class="nav-link <?php echo $status_filter === 'pending' ? 'active' : ''; ?>" href="dashboard.php?status=pending">
                        Pending <span class="badge bg-warning text-dark"><?php echo $stats_pending; ?></span>
                    </a>
                </li>
                <li class="nav-item me-2">
                    <a class="nav-link <?php echo $status_filter === 'preparing' ? 'active' : ''; ?>" href="dashboard.php?status=preparing">
                        Preparing <span class="badge bg-primary"><?php echo $stats_preparing; ?></span>
                    </a>
                </li>
                <li class="nav-item me-2">
                    <a class="nav-link <?php echo $status_filter === 'confirmed' ? 'active' : ''; ?>" href="dashboard.php?status=confirmed">Confirmed</a>
                </li>
                <li class="nav-item me-2">
                    <a class="nav-link <?php echo $status_filter === 'on the way' ? 'active' : ''; ?>" href="dashboard.php?status=on the way">On the Way</a>
                </li>
                <li class="nav-item me-2">
                    <a class="nav-link <?php echo $status_filter === 'delivered' ? 'active' : ''; ?>" href="dashboard.php?status=delivered">Delivered</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>" href="dashboard.php?status=cancelled">Cancelled</a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Orders List -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light py-3"><h6 class="m-0 fw-bold text-secondary">Orders Listing (<?php echo count($orders); ?> orders found)</h6></div>
        <div class="card-body">
            <?php if (empty($orders)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No orders found matching the filter.</p>
                </div>
            <?php else: ?>
                <div class="accordion" id="ordersAccordion">
                    <?php foreach ($orders as $index => $order_header): 
                        // Fetch details with items
                        $order = get_order_details($order_header['order_id']);
                    ?>
                        <div class="accordion-item mb-3 border rounded">
                            <h2 class="accordion-header" id="heading-<?php echo $order['order_id']; ?>">
                                <button class="accordion-button collapsed px-4" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $order['order_id']; ?>" aria-expanded="false" aria-controls="collapse-<?php echo $order['order_id']; ?>">
                                    <div class="row w-100 align-items-center">
                                        <div class="col-md-2 col-6">
                                            <strong>#<?php echo $order['order_id']; ?></strong>
                                        </div>
                                        <div class="col-md-3 col-6 text-muted small">
                                            <i class="fas fa-calendar-alt me-1"></i><?php echo date('M d, Y h:i A', strtotime($order['order_date'] . ' UTC')); ?>
                                        </div>
                                        <div class="col-md-3 col-6 mt-2 mt-md-0">
                                            <span class="small">Customer:</span> <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                        </div>
                                        <div class="col-md-2 col-6 mt-2 mt-md-0 text-md-center">
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
                                            ?>"><?php echo ucfirst($order['order_status']); ?></span>
                                        </div>
                                        <div class="col-md-2 col-12 mt-2 mt-md-0 text-md-end font-weight-bold text-dark">
                                            <?php echo format_price($order['total_amount']); ?>
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse-<?php echo $order['order_id']; ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?php echo $order['order_id']; ?>" data-bs-parent="#ordersAccordion">
                                <div class="accordion-body bg-light p-4">
                                    <div class="row">
                                        <!-- Customer details & Delivery address -->
                                        <div class="col-md-5 mb-4 mb-md-0">
                                            <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary">Delivery & Customer Details</h6>
                                            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                            <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($order['delivery_phone']); ?></p>
                                            <p class="mb-2"><strong>Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                                            
                                            <?php if (!empty($order['notes'])): ?>
                                                <div class="alert alert-warning p-2 small mt-2">
                                                    <strong><i class="fas fa-sticky-note me-1"></i> Customer Note:</strong><br>
                                                    <?php echo htmlspecialchars($order['notes']); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="mt-3 p-2 border rounded bg-white">
                                                <p class="mb-1 small"><strong>Payment Method:</strong> <?php echo strtoupper($order['payment_method']); ?></p>
                                                <p class="mb-0 small"><strong>Payment Status:</strong> 
                                                    <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : ($order['payment_status'] === 'failed' ? 'danger' : 'warning text-dark'); ?>">
                                                        <?php echo ucfirst($order['payment_status']); ?>
                                                    </span>
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Items List -->
                                        <div class="col-md-7">
                                            <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary">Order Items</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered bg-white">
                                                    <thead>
                                                        <tr>
                                                            <th>Item Name</th>
                                                            <th class="text-center" style="width: 80px;">Qty</th>
                                                            <th class="text-end" style="width: 100px;">Price</th>
                                                            <th class="text-end" style="width: 100px;">Subtotal</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($order['items'] as $item): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                                <td class="text-center"><?php echo $item['quantity']; ?></td>
                                                                <td class="text-end"><?php echo format_price($item['price']); ?></td>
                                                                <td class="text-end"><?php echo format_price($item['price'] * $item['quantity']); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                        <tr>
                                                            <td colspan="3" class="text-end small">Subtotal:</td>
                                                            <td class="text-end small"><?php echo format_price($order['subtotal']); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="3" class="text-end small">Delivery Fee:</td>
                                                            <td class="text-end small"><?php echo format_price($order['delivery_fee']); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="3" class="text-end small">Tax (GST):</td>
                                                            <td class="text-end small"><?php echo format_price($order['tax']); ?></td>
                                                        </tr>
                                                        <?php if (isset($order['discount_amount']) && (float)$order['discount_amount'] > 0): ?>
                                                        <tr>
                                                            <td colspan="3" class="text-end small text-success">Discount (<?php echo htmlspecialchars($order['promo_code']); ?>):</td>
                                                            <td class="text-end small text-success">-<?php echo format_price($order['discount_amount']); ?></td>
                                                        </tr>
                                                        <?php endif; ?>
                                                        <tr class="fw-bold bg-light">
                                                            <td colspan="3" class="text-end">Total Amount:</td>
                                                            <td class="text-end text-primary"><?php echo format_price($order['total_amount']); ?></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <!-- Order Actions Block -->
                                            <div class="d-flex flex-wrap gap-2 mt-4 p-3 bg-white border rounded justify-content-between align-items-center">
                                                <div>
                                                    <span class="small text-muted">Manage Order Status:</span>
                                                    <div class="mt-1 d-flex gap-2">
                                                        <?php if (in_array($order['order_status'], ['pending', 'confirmed'])): ?>
                                                            <form method="post" class="d-inline">
                                                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                                <input type="hidden" name="action" value="prepare">
                                                                <button type="submit" class="btn btn-sm btn-primary">
                                                                    <i class="fas fa-fire-burner me-1"></i> Start Preparing
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (in_array($order['order_status'], ['pending', 'confirmed', 'preparing'])): ?>
                                                            <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                                <input type="hidden" name="action" value="cancel">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                    <i class="fas fa-times me-1"></i> Cancel Order
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (in_array($order['order_status'], ['on the way', 'delivered', 'cancelled'])): ?>
                                                            <span class="text-muted small italic">No active status actions remaining.</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <!-- Delivery boy assignment section -->
                                                <?php if (!in_array($order['order_status'], ['delivered', 'cancelled'])): ?>
                                                    <div style="min-width: 250px;">
                                                        <form method="post" class="row g-2 align-items-center justify-content-end">
                                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                            <input type="hidden" name="action" value="assign_delivery">
                                                            <div class="col-8">
                                                                <select class="form-select form-select-sm" name="delivery_boy_id" required>
                                                                    <option value="0">-- Unassigned --</option>
                                                                    <?php foreach ($delivery_boys as $boy): ?>
                                                                        <option value="<?php echo $boy['user_id']; ?>" <?php echo ($order['delivery_boy_id'] == $boy['user_id']) ? 'selected' : ''; ?>>
                                                                            <?php echo htmlspecialchars($boy['name']); ?> <?php echo $boy['is_online'] ? '(Online 🟢)' : '(Offline 🔴)'; ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-4">
                                                                <button type="submit" class="btn btn-sm btn-outline-primary w-100">Assign</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                <?php else: ?>
                                                    <div>
                                                        <span class="small text-muted">Assigned Rider:</span><br>
                                                        <strong class="small">
                                                            <?php 
                                                            if (!empty($order['delivery_boy_id'])) {
                                                                $assigned_boy = get_user_by_id($order['delivery_boy_id']);
                                                                echo htmlspecialchars($assigned_boy['name'] ?? 'Unknown');
                                                            } else {
                                                                echo 'None';
                                                            }
                                                            ?>
                                                        </strong>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/restaurant_footer.php'; ?>
