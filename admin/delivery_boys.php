<?php
$page_title = "Manage Delivery Boys";
require_once 'admin_header.php';

// Get all delivery boys
$delivery_boys = admin_get_delivery_boys();

// If viewing details for a specific delivery boy
$view_boy = null;
$boy_orders = array();
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $boy_id = intval($_GET['view']);
    $view_boy = get_user_by_id($boy_id);
    
    if ($view_boy && $view_boy['is_delivery_boy']) {
        // Fetch boy's orders
        $boy_orders = get_delivery_boy_orders($boy_id);
    } else {
        $view_boy = null;
    }
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manage Delivery Boys</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item <?php echo $view_boy ? '' : 'active'; ?>" <?php echo $view_boy ? '' : 'aria-current="page"'; ?>>
                    <?php if ($view_boy): ?>
                        <a href="delivery_boys.php">Delivery Boys</a>
                    <?php else: ?>
                        Delivery Boys
                    <?php endif; ?>
                </li>
                <?php if ($view_boy): ?>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo $view_boy['name']; ?></li>
                <?php endif; ?>
            </ol>
        </nav>
    </div>

    <?php if ($view_boy): ?>
    <!-- Single Delivery Boy View -->
    <div class="row">
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-light">
                    <h6 class="m-0 font-weight-bold text-primary">Delivery Boy Profile</h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="bg-warning text-dark d-inline-block rounded-circle p-3 mb-2" style="font-size: 2.5rem; width: 80px; height: 80px; line-height: 50px;">
                            <i class="fas fa-truck"></i>
                        </div>
                        <h5 class="mb-0"><?php echo htmlspecialchars($view_boy['name'], ENT_QUOTES); ?></h5>
                        <span class="badge bg-warning text-dark">Active Delivery Boy</span>
                    </div>
                    <p class="mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($view_boy['email'], ENT_QUOTES); ?></p>
                    <p class="mb-2"><strong>Phone:</strong> <?php echo !empty($view_boy['phone']) ? htmlspecialchars($view_boy['phone'], ENT_QUOTES) : 'N/A'; ?></p>
                    <p class="mb-2"><strong>Address:</strong> <?php echo !empty($view_boy['address']) ? htmlspecialchars($view_boy['address'], ENT_QUOTES) : 'N/A'; ?></p>
                    <p class="mb-2"><strong>City:</strong> <?php echo !empty($view_boy['city']) ? htmlspecialchars($view_boy['city'], ENT_QUOTES) : 'N/A'; ?></p>
                    <p class="mb-2"><strong>State:</strong> <?php echo !empty($view_boy['state']) ? htmlspecialchars($view_boy['state'], ENT_QUOTES) : 'N/A'; ?></p>
                    <p class="mb-0"><strong>ZIP Code:</strong> <?php echo !empty($view_boy['zip_code']) ? htmlspecialchars($view_boy['zip_code'], ENT_QUOTES) : 'N/A'; ?></p>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Assigned Deliveries History</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <?php if (empty($boy_orders)): ?>
                        <p class="text-center py-3">No orders assigned to this delivery boy yet.</p>
                        <?php else: ?>
                        <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Restaurant</th>
                                    <th>Customer</th>
                                    <th>Delivery Phone</th>
                                    <th>Order Status</th>
                                    <th>Payment Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($boy_orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['order_id']; ?></td>
                                    <td><?php echo $order['restaurant_name']; ?></td>
                                    <td><?php echo $order['customer_name']; ?></td>
                                    <td><?php echo $order['delivery_phone']; ?></td>
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
                                        <a href="orders.php?view=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-info py-0 px-2" style="font-size: 0.8rem;">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Delivery Boys List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-light">
            <h6 class="m-0 font-weight-bold text-primary">All Registered Delivery Boys</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <?php if (empty($delivery_boys)): ?>
                <div class="text-center py-4">
                    <p class="text-muted mb-3">No delivery boys registered yet.</p>
                    <a href="users.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Go to Users to Add Delivery Boy
                    </a>
                </div>
                <?php else: ?>
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Registration Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($delivery_boys as $boy): 
                            // Get statistics for this boy
                            global $conn;
                            // Active deliveries count
                            $stmt_act = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE delivery_boy_id = ? AND order_status NOT IN ('delivered', 'cancelled')");
                            $stmt_act->execute([$boy['user_id']]);
                            $act_count = $stmt_act->fetchColumn();
                            
                            // Completed deliveries count
                            $stmt_comp = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE delivery_boy_id = ? AND order_status = 'delivered'");
                            $stmt_comp->execute([$boy['user_id']]);
                            $comp_count = $stmt_comp->fetchColumn();
                        ?>
                        <tr>
                            <td>#<?php echo $boy['user_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($boy['name'], ENT_QUOTES); ?></strong>
                                <div class="mt-1">
                                    <span class="badge bg-primary"><?php echo $act_count; ?> Active Deliveries</span>
                                    <span class="badge bg-success"><?php echo $comp_count; ?> Completed</span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($boy['email'], ENT_QUOTES); ?></td>
                            <td><?php echo !empty($boy['phone']) ? htmlspecialchars($boy['phone'], ENT_QUOTES) : 'N/A'; ?></td>
                            <td><?php echo !empty($boy['created_at']) ? date('M d, Y', strtotime($boy['created_at'])) : 'N/A'; ?></td>
                            <td>
                                <a href="delivery_boys.php?view=<?php echo $boy['user_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye text-white"></i> View Activity
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
require_once 'admin_footer.php';
?>
