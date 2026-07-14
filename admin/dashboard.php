<?php
$page_title = "Admin Dashboard";
require_once 'admin_header.php';

// Get statistics for dashboard
$total_orders = count_total_orders();
$total_users = count_total_users();
$total_restaurants = count_total_restaurants();
$total_revenue = calculate_total_revenue();

// Get order counts by status
$order_status_counts = admin_count_orders_by_status();

// Get recent orders
$recent_orders = get_recent_orders(10);

// Fetch all delivery boys for status sidebar
$stmt_boys = $conn->prepare("SELECT * FROM users WHERE is_delivery_boy = 1 ORDER BY is_online DESC, name ASC");
$stmt_boys->execute();
$delivery_agents = $stmt_boys->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Dashboard</h1>
        <div>
            <span class="text-muted me-3">Today: <?php echo date('F d, Y'); ?></span>
            <a href="export_expenditure.php" class="btn btn-sm btn-success shadow-sm">
                <i class="fas fa-download fa-sm text-white-50 me-1"></i> Export Monthly Report
            </a>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Orders</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_orders; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Revenue</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo format_price($total_revenue); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-rupee-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Users
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_users; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Total Restaurants</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_restaurants; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-store fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Order Status and Revenue Chart -->
    <div class="row">
        <!-- Order Status -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Orders by Status</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="orderStatusChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="mr-2">
                            <i class="fas fa-circle text-warning"></i> Pending
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-info"></i> Confirmed
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-primary"></i> Preparing
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-primary"></i> On the way
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-success"></i> Delivered
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-danger"></i> Cancelled
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Revenue Chart -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Revenue Overview</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Orders & Delivery Boy Status -->
    <div class="row">
        <!-- Recent Orders (col-lg-8) -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Orders</h6>
                    <a href="orders.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body" style="min-height: 400px;">
                    <div class="table-responsive">
                        <?php if (empty($recent_orders)): ?>
                        <p class="text-center">No orders found.</p>
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
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['restaurant_name']); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
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
                </div>
            </div>
        </div>

        <!-- Delivery Agents Status (col-lg-4) -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Delivery Partner Status</h6>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <?php if (empty($delivery_agents)): ?>
                    <p class="text-center text-muted my-4">No delivery agents registered.</p>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach($delivery_agents as $agent): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                            <div class="d-flex align-items-center">
                                <?php if (!empty($agent['profile_image'])): ?>
                                    <img src="../<?php echo $agent['profile_image']; ?>" class="rounded-circle border me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-circle bg-light border d-flex justify-content-center align-items-center me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-user text-secondary"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h6 class="mb-0 fw-bold" style="font-size: 0.9rem;"><?php echo htmlspecialchars($agent['name']); ?></h6>
                                    <small class="text-muted" style="font-size: 0.75rem;"><i class="fas fa-phone-alt me-1"></i>+91 <?php echo htmlspecialchars($agent['phone']); ?></small>
                                </div>
                            </div>
                            <div>
                                <?php if (($agent['is_online'] ?? 0) == 1): ?>
                                    <span class="badge bg-success rounded-pill px-2.5 py-1.5"><i class="fas fa-circle me-1 animate-pulse" style="font-size: 0.45rem;"></i>Online</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary rounded-pill px-2.5 py-1.5">Offline</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Create charts when document is ready
    document.addEventListener('DOMContentLoaded', function() {
        // Order Status Chart
        const orderStatusData = {
            labels: [
                'Pending', 
                'Confirmed', 
                'Preparing', 
                'On the way', 
                'Delivered', 
                'Cancelled'
            ],
            datasets: [{
                data: [
                    <?php echo isset($order_status_counts['pending']) ? $order_status_counts['pending'] : 0; ?>,
                    <?php echo isset($order_status_counts['confirmed']) ? $order_status_counts['confirmed'] : 0; ?>,
                    <?php echo isset($order_status_counts['preparing']) ? $order_status_counts['preparing'] : 0; ?>,
                    <?php echo isset($order_status_counts['on the way']) ? $order_status_counts['on the way'] : 0; ?>,
                    <?php echo isset($order_status_counts['delivered']) ? $order_status_counts['delivered'] : 0; ?>,
                    <?php echo isset($order_status_counts['cancelled']) ? $order_status_counts['cancelled'] : 0; ?>
                ],
                backgroundColor: [
                    '#ffc107', // warning
                    '#17a2b8', // info
                    '#007bff', // primary
                    '#007bff', // primary
                    '#28a745', // success
                    '#dc3545'  // danger
                ],
                hoverOffset: 4
            }]
        };
        
        const orderStatusChart = new Chart(
            document.getElementById('orderStatusChart'),
            {
                type: 'doughnut',
                data: orderStatusData,
                options: {
                    maintainAspectRatio: false,
                    tooltips: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyFontColor: "#858796",
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: false,
                        caretPadding: 10,
                    },
                    legend: {
                        display: false
                    },
                    cutoutPercentage: 80,
                }
            }
        );
        
        // Revenue Chart (Mock data - in a real app, this would come from the database)
        // Get last 6 months
        const months = [];
        const monthlyRevenue = [];
        
        for (let i = 5; i >= 0; i--) {
            const d = new Date();
            d.setMonth(d.getMonth() - i);
            months.push(d.toLocaleString('default', { month: 'short' }));
            
            // For demo purposes, generate random revenue between 5000 and 50000
            const revenue = Math.floor(Math.random() * (50000 - 5000 + 1)) + 5000;
            monthlyRevenue.push(revenue);
        }
        
        const revenueData = {
            labels: months,
            datasets: [{
                label: 'Revenue',
                data: monthlyRevenue,
                fill: false,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        };
        
        const revenueChart = new Chart(
            document.getElementById('revenueChart'),
            {
                type: 'line',
                data: revenueData,
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₹' + value;
                                }
                            }
                        }
                    },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                return '₹' + tooltipItem.yLabel.toFixed(2);
                            }
                        }
                    }
                }
            }
        );
    });
</script>

<?php
require_once 'admin_footer.php';
?>
