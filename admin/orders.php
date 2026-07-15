<?php
$page_title = "Manage Orders";
require_once 'admin_header.php';

// Handle order status update
if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $status = clean_input($_POST['status']);
    
    $result = admin_update_order_status($order_id, $status);
    
    if ($result['success']) {
        $success_msg = 'Order status updated successfully.';
    } else {
        $error_msg = $result['message'];
    }
}

// Handle payment status update
if (isset($_POST['update_payment_status']) && isset($_POST['order_id']) && isset($_POST['payment_status'])) {
    $order_id = intval($_POST['order_id']);
    $payment_status = clean_input($_POST['payment_status']);
    
    $result = admin_update_payment_status($order_id, $payment_status);
    
    if ($result['success']) {
        $success_msg = 'Payment status updated successfully.';
    } else {
        $error_msg = $result['message'];
    }
}

// Handle delivery boy assignment
if (isset($_POST['assign_delivery']) && isset($_POST['order_id']) && isset($_POST['delivery_boy_id'])) {
    $order_id = intval($_POST['order_id']);
    $delivery_boy_id = intval($_POST['delivery_boy_id']);
    
    $result = admin_assign_delivery_boy($order_id, $delivery_boy_id);
    
    if ($result['success']) {
        $success_msg = 'Delivery boy assigned successfully.';
    } else {
        $error_msg = $result['message'];
    }
}

// Get filter values
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : '';
$limit = 20;
$offset = isset($_GET['page']) ? (intval($_GET['page']) - 1) * $limit : 0;
if ($offset < 0) $offset = 0;

// Get orders with filter
$orders = admin_get_all_orders($limit, $offset, $status_filter ? $status_filter : null);

// Apply search filter if query is set
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = strtolower(clean_input($_GET['search']));
    $filtered = [];
    foreach ($orders as $order) {
        if (stripos((string)$order['order_id'], $search) !== false ||
            stripos($order['user_name'], $search) !== false ||
            stripos($order['restaurant_name'], $search) !== false ||
            stripos($order['payment_method'], $search) !== false ||
            stripos($order['payment_status'], $search) !== false ||
            stripos($order['order_status'], $search) !== false) {
            $filtered[] = $order;
        }
    }
    $orders = $filtered;
}

// Get total count of orders for pagination
$status_counts = admin_count_orders_by_status();
$total_orders = 0;
foreach ($status_counts as $count) {
    $total_orders += $count;
}

// Calculate total pages
$total_pages = ceil($total_orders / $limit);
$current_page = floor($offset / $limit) + 1;

// View single order
$view_order = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $order_id = intval($_GET['view']);
    $view_order = get_order_details($order_id);
    
    if ($view_order) {
        $restaurant = get_restaurant_by_id($view_order['restaurant_id']);
        $user = get_user_by_id($view_order['user_id']);
        $delivery_boys = admin_get_delivery_boys();
    }
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manage Orders</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Orders</li>
            </ol>
        </nav>
    </div>
    
    <?php if (isset($success_msg)): ?>
    <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_msg)): ?>
    <div class="alert alert-danger"><?php echo $error_msg; ?></div>
    <?php endif; ?>
    
    <?php if ($view_order): ?>
    <!-- Single Order View -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Order #<?php echo $view_order['order_id']; ?> Details</h6>
            <a href="orders.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Order Information</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><strong>Order ID:</strong> #<?php echo $view_order['order_id']; ?></p>
                            <p class="mb-1"><strong>Date:</strong> <?php echo date('F d, Y h:i A', strtotime($view_order['order_date'] . ' UTC')); ?></p>
                            <p class="mb-1">
                                <strong>Status:</strong> 
                                <span class="badge bg-<?php 
                                    switch($view_order['order_status']) {
                                        case 'pending': echo 'warning text-dark'; break;
                                        case 'confirmed': echo 'info'; break;
                                        case 'preparing': echo 'primary'; break;
                                        case 'on the way': echo 'primary'; break;
                                        case 'delivered': echo 'success'; break;
                                        case 'cancelled': echo 'danger'; break;
                                        default: echo 'secondary';
                                    }
                                ?>">
                                    <?php echo ucfirst($view_order['order_status']); ?>
                                </span>
                            </p>
                            <p class="mb-1">
                                <strong>Payment Method:</strong> 
                                <?php 
                                switch($view_order['payment_method']) {
                                    case 'cash': echo 'Cash on Delivery'; break;
                                    case 'card': echo 'Credit/Debit Card'; break;
                                    case 'wallet': echo 'Digital Wallet'; break;
                                    case 'upi': echo 'UPI QR Code'; break;
                                }
                                ?>
                            </p>
                            <p class="mb-1">
                                <strong>Payment Status:</strong> 
                                <span class="badge bg-<?php echo ($view_order['payment_status'] == 'paid') ? 'success' : (($view_order['payment_status'] == 'pending') ? 'warning text-dark' : 'danger'); ?>">
                                    <?php echo ucfirst($view_order['payment_status']); ?>
                                </span>
                            </p>
                            
                            <hr>
                            
                            <!-- Order Status Update Form -->
                            <form action="orders.php?view=<?php echo $view_order['order_id']; ?>" method="post">
                                <input type="hidden" name="order_id" value="<?php echo $view_order['order_id']; ?>">
                                <div class="row align-items-end">
                                    <div class="col-md-8 mb-2 mb-md-0">
                                        <label for="status" class="form-label">Update Order Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="pending" <?php echo ($view_order['order_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="confirmed" <?php echo ($view_order['order_status'] == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="preparing" <?php echo ($view_order['order_status'] == 'preparing') ? 'selected' : ''; ?>>Preparing</option>
                                            <option value="on the way" <?php echo ($view_order['order_status'] == 'on the way') ? 'selected' : ''; ?>>On the way</option>
                                            <option value="delivered" <?php echo ($view_order['order_status'] == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="cancelled" <?php echo ($view_order['order_status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" name="update_status" class="btn btn-primary w-100">Update</button>
                                    </div>
                                </div>
                            </form>
                            
                            <!-- Payment Status Update Form -->
                            <form action="orders.php?view=<?php echo $view_order['order_id']; ?>" method="post" class="mt-3">
                                <input type="hidden" name="order_id" value="<?php echo $view_order['order_id']; ?>">
                                <div class="row align-items-end">
                                    <div class="col-md-8 mb-2 mb-md-0">
                                        <label for="payment_status" class="form-label">Update Payment Status</label>
                                        <select class="form-select" id="payment_status" name="payment_status">
                                            <option value="pending" <?php echo ($view_order['payment_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="paid" <?php echo ($view_order['payment_status'] == 'paid') ? 'selected' : ''; ?>>Paid</option>
                                            <option value="failed" <?php echo ($view_order['payment_status'] == 'failed') ? 'selected' : ''; ?>>Failed</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" name="update_payment_status" class="btn btn-secondary w-100">Update</button>
                                    </div>
                                </div>
                            </form>
                            
                            <hr>
                            
                            <!-- Assign Delivery Boy Form -->
                            <form action="orders.php?view=<?php echo $view_order['order_id']; ?>" method="post">
                                <input type="hidden" name="order_id" value="<?php echo $view_order['order_id']; ?>">
                                <div class="row align-items-end">
                                    <div class="col-md-8 mb-2 mb-md-0">
                                        <label for="delivery_boy_id" class="form-label">Assign Delivery Boy</label>
                                        <select class="form-select" id="delivery_boy_id" name="delivery_boy_id">
                                            <option value="0">-- Unassigned --</option>
                                            <?php foreach ($delivery_boys as $boy): ?>
                                                <option value="<?php echo $boy['user_id']; ?>" <?php echo ($view_order['delivery_boy_id'] == $boy['user_id']) ? 'selected' : ''; ?>>
                                                    <?php echo $boy['name']; ?> (<?php echo $boy['phone']; ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" name="assign_delivery" class="btn btn-warning text-dark w-100">Assign</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Customer Information</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><strong>Name:</strong> <?php echo $user['name']; ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?php echo $user['email']; ?></p>
                            <p class="mb-1"><strong>Phone:</strong> <?php echo $user['phone']; ?></p>
                            <p class="mb-1"><strong>Delivery Address:</strong> <?php echo $view_order['delivery_address']; ?></p>
                            <p class="mb-1"><strong>Delivery Phone:</strong> <?php echo $view_order['delivery_phone']; ?></p>
                            
                            <?php if (!empty($view_order['notes'])): ?>
                            <hr>
                            <p class="mb-0"><strong>Notes:</strong> <?php echo $view_order['notes']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Restaurant Information</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><strong>Name:</strong> <?php echo $restaurant['name']; ?></p>
                            <p class="mb-1"><strong>Address:</strong> <?php echo $restaurant['address']; ?>, <?php echo $restaurant['city']; ?>, <?php echo $restaurant['state']; ?> - <?php echo $restaurant['zip_code']; ?></p>
                            <p class="mb-1"><strong>Phone:</strong> <?php echo $restaurant['phone']; ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?php echo $restaurant['email']; ?></p>
                            <p class="mb-0"><strong>Delivery Time:</strong> <?php echo $restaurant['delivery_time']; ?> mins</p>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Order Summary</h6>
                        </div>
                        <div class="card-body">
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
                                        <?php foreach($view_order['items'] as $item): ?>
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
                                    <span><?php echo format_price($view_order['subtotal']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Delivery Fee:</span>
                                    <span><?php echo format_price($view_order['delivery_fee']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Tax:</span>
                                    <span><?php echo format_price($view_order['tax']); ?></span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <strong>Total:</strong>
                                    <strong><?php echo format_price($view_order['total_amount']); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Orders List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">All Orders</h6>
            <div>
                <select id="status-filter" class="form-select form-select-sm d-inline-block w-auto me-2">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo ($status_filter == 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="confirmed" <?php echo ($status_filter == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="preparing" <?php echo ($status_filter == 'preparing') ? 'selected' : ''; ?>>Preparing</option>
                    <option value="on the way" <?php echo ($status_filter == 'on the way') ? 'selected' : ''; ?>>On the way</option>
                    <option value="delivered" <?php echo ($status_filter == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                    <option value="cancelled" <?php echo ($status_filter == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <button id="apply-filter" class="btn btn-sm btn-primary">Filter</button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <?php if (empty($orders)): ?>
                <p class="text-center py-3">No orders found.</p>
                <?php else: ?>
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Restaurant</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['order_id']; ?></td>
                            <td><?php echo $order['user_name']; ?></td>
                            <td><?php echo $order['restaurant_name']; ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($order['order_date'] . ' UTC')); ?></td>
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
                                <a href="orders.php?view=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($current_page == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle status filter
        const statusFilter = document.getElementById('status-filter');
        const applyFilter = document.getElementById('apply-filter');
        
        if (statusFilter && applyFilter) {
            applyFilter.addEventListener('click', function() {
                const status = statusFilter.value;
                if (status) {
                    window.location.href = 'orders.php?status=' + status;
                } else {
                    window.location.href = 'orders.php';
                }
            });
        }
    });
</script>

<?php
require_once 'admin_footer.php';
?>
