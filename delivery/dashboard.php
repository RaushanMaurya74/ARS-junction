<?php
require_once 'auth.php';
require_once '../includes/functions.php';

// Protect page
require_delivery_login();

$db_id = $_SESSION['delivery_boy_id'];
$success_msg = '';
$error_msg = '';

// Fetch current user details first to have profile pic access
$db_user = get_user_by_id($db_id);

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Availability Status Toggle
    if (isset($_POST['toggle_status'])) {
        $new_status = (($db_user['is_online'] ?? 0) == 1) ? 0 : 1;
        $stmt = $conn->prepare("UPDATE users SET is_online = ? WHERE user_id = ?");
        if ($stmt->execute([$new_status, $db_id])) {
            $success_msg = 'Availability status updated successfully!';
            $db_user = get_user_by_id($db_id);
        } else {
            $error_msg = 'Failed to update availability status.';
        }
    }

    // Handle Profile Image Upload
    if (isset($_POST['upload_photo'])) {
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_pic']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                // Compress and encode to base64
                $base64_image = get_compressed_base64_image($_FILES['profile_pic']['tmp_name'], $ext, 200, 200);
                
                $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE user_id = ?");
                if ($stmt->execute([$base64_image, $db_id])) {
                    $success_msg = 'Profile photo uploaded successfully!';
                    // Refresh user data
                    $db_user = get_user_by_id($db_id);
                } else {
                    $error_msg = 'Database update failed.';
                }
            } else {
                $error_msg = 'Invalid file format. Only JPG, JPEG, PNG, and GIF allowed.';
            }
        } else {
            $error_msg = 'Error uploading file.';
        }
    }

    // Handle Profile Image Removal
    if (isset($_POST['remove_photo'])) {
        $stmt = $conn->prepare("UPDATE users SET profile_image = NULL WHERE user_id = ?");
        if ($stmt->execute([$db_id])) {
            $success_msg = 'Profile photo removed successfully!';
            // Refresh user data
            $db_user = get_user_by_id($db_id);
        } else {
            $error_msg = 'Failed to remove profile photo.';
        }
    }
    
    // Handle order actions
    if (isset($_POST['action'])) {
        $order_id = intval($_POST['order_id']);
        $action = $_POST['action'];
        $db_restaurant_id = isset($db_user['restaurant_id']) && $db_user['restaurant_id'] !== '' ? intval($db_user['restaurant_id']) : null;
        
        if ($action === 'start_delivery') {
            if ($db_restaurant_id !== null && $db_restaurant_id !== 0) {
                $stmt = $conn->prepare("UPDATE orders SET order_status = 'on the way' WHERE order_id = ? AND delivery_boy_id = ? AND restaurant_id = ?");
                $execute_params = [$order_id, $db_id, $db_restaurant_id];
            } else {
                $stmt = $conn->prepare("UPDATE orders SET order_status = 'on the way' WHERE order_id = ? AND delivery_boy_id = ?");
                $execute_params = [$order_id, $db_id];
            }
            if ($stmt->execute($execute_params)) {
                $success_msg = "Order #{$order_id} is now on the way!";
            } else {
                $error_msg = "Failed to update order status.";
            }
        }
        
        if ($action === 'complete_delivery') {
            if ($db_restaurant_id !== null && $db_restaurant_id !== 0) {
                $stmt = $conn->prepare("UPDATE orders SET order_status = 'delivered' WHERE order_id = ? AND delivery_boy_id = ? AND restaurant_id = ?");
                $execute_params = [$order_id, $db_id, $db_restaurant_id];
            } else {
                $stmt = $conn->prepare("UPDATE orders SET order_status = 'delivered' WHERE order_id = ? AND delivery_boy_id = ?");
                $execute_params = [$order_id, $db_id];
            }
            if ($stmt->execute($execute_params)) {
                $success_msg = "Order #{$order_id} marked as Delivered successfully.";
            } else {
                $error_msg = "Failed to update order status.";
            }
        }
        
        if ($action === 'confirm_payment') {
            if ($db_restaurant_id !== null && $db_restaurant_id !== 0) {
                $stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid' WHERE order_id = ? AND delivery_boy_id = ? AND restaurant_id = ?");
                $execute_params = [$order_id, $db_id, $db_restaurant_id];
            } else {
                $stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid' WHERE order_id = ? AND delivery_boy_id = ?");
                $execute_params = [$order_id, $db_id];
            }
            if ($stmt->execute($execute_params)) {
                $success_msg = "Payment for Order #{$order_id} marked as Paid.";
            } else {
                $error_msg = "Failed to confirm payment.";
            }
        }
    }
}

// Fetch all assigned orders
$all_orders = get_delivery_boy_orders($db_id);

$active_orders = [];
$completed_orders = [];

foreach ($all_orders as $order) {
    if (in_array($order['order_status'], ['delivered', 'cancelled'])) {
        $completed_orders[] = $order;
    } else {
        $active_orders[] = $order;
    }
}

// Handle tab selection via query parameter
$active_tab = isset($_GET['tab']) && $_GET['tab'] === 'history' ? 'history' : 'active';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Portal - Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar-custom {
            background-color: #212529;
        }
        .order-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .order-card:hover {
            transform: translateY(-2px);
        }
        .qr-container img {
            max-width: 180px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<!-- Navigation Header -->
<nav class="navbar navbar-dark navbar-custom navbar-expand-lg mb-4">
    <div class="container">
        <!-- Logo click navigates back to dashboard and reloads data -->
        <a class="navbar-brand fw-bold text-warning" href="dashboard.php"><i class="fas fa-truck-moving me-2"></i> Delivery Panel</a>
        <div class="d-flex align-items-center">
            <span class="navbar-text text-white me-3 d-none d-sm-inline">
                <?php if (has_image($db_user['profile_image'], true)): ?>
                    <img src="<?php echo get_image_url($db_user['profile_image'], true); ?>" class="rounded-circle me-1 border" style="width: 32px; height: 32px; object-fit: cover;">
                <?php else: ?>
                    <i class="fas fa-user-circle me-1"></i>
                <?php endif; ?>
                Welcome, <strong><?php echo htmlspecialchars($db_user['name']); ?></strong>
            </span>
            <a href="logout.php" class="btn btn-outline-danger btn-sm fw-bold">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="container py-2" style="min-height: 80vh;">
    <!-- Welcome message for mobile -->
    <div class="row d-sm-none mb-3">
        <div class="col-12 text-center">
            <p class="text-muted">Logged in as: <strong><?php echo htmlspecialchars($db_user['name']); ?></strong></p>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if ($success_msg): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $success_msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Delivery Boy Profile Card -->
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm text-center p-3 h-100">
                <div class="card-body">
                    <div class="mb-3 position-relative d-inline-block">
                        <?php if (has_image($db_user['profile_image'], true)): ?>
                            <img src="<?php echo get_image_url($db_user['profile_image'], true); ?>" class="rounded-circle border shadow-sm" style="width: 100px; height: 100px; object-fit: cover;">
                        <?php else: ?>
                            <i class="fas fa-user-circle text-secondary display-3"></i>
                        <?php endif; ?>
                        
                        <form method="post" action="dashboard.php" enctype="multipart/form-data" class="position-absolute bottom-0 end-0">
                            <label for="db_profile_pic" class="btn btn-warning btn-sm rounded-circle p-1 border shadow-sm" style="width: 28px; height: 28px; cursor: pointer;">
                                <i class="fas fa-camera text-dark small" style="vertical-align: top;"></i>
                            </label>
                            <input type="file" id="db_profile_pic" name="profile_pic" accept="image/*" class="d-none" onchange="this.form.submit()">
                            <input type="hidden" name="upload_photo" value="1">
                        </form>
                    </div>
                    <?php if (has_image($db_user['profile_image'], true)): ?>
                        <form method="post" action="dashboard.php" class="mb-2 text-center">
                            <input type="hidden" name="remove_photo" value="1">
                            <button type="submit" class="btn btn-xs btn-outline-danger py-1 px-2" style="font-size: 0.72rem; border-radius: 4px;">
                                <i class="fas fa-trash-alt me-1"></i> Remove Photo
                            </button>
                        </form>
                    <?php endif; ?>
                    <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($db_user['name']); ?></h5>
                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($db_user['email']); ?></p>
                    <div class="mb-3">
                        <span class="badge bg-primary text-white">Delivery Partner</span>
                        <?php if (($db_user['is_online'] ?? 0) == 1): ?>
                            <span class="badge bg-success"><i class="fas fa-circle text-white me-1 animate-pulse" style="font-size: 0.5rem;"></i>Online</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Offline</span>
                        <?php endif; ?>
                    </div>
                    
                    <form method="post" action="dashboard.php" class="mt-2">
                        <button type="submit" name="toggle_status" class="btn btn-sm <?php echo (($db_user['is_online'] ?? 0) == 1) ? 'btn-danger' : 'btn-success'; ?> w-100 fw-bold shadow-sm">
                            <i class="fas <?php echo (($db_user['is_online'] ?? 0) == 1) ? 'fa-toggle-on' : 'fa-toggle-off'; ?> me-1"></i>
                            Go <?php echo (($db_user['is_online'] ?? 0) == 1) ? 'Offline' : 'Online'; ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Deliveries Section -->
        <div class="col-lg-9">
            <!-- Navigation Tabs -->
            <ul class="nav nav-tabs mb-4" id="deliveryTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo ($active_tab === 'active') ? 'active' : ''; ?> position-relative fw-bold text-dark" id="active-tab" type="button" onclick="window.location.href='dashboard.php?tab=active'">
                        Active Deliveries
                        <?php if (count($active_orders) > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.75rem;">
                                <?php echo count($active_orders); ?>
                            </span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo ($active_tab === 'history') ? 'active' : ''; ?> fw-bold text-dark" id="history-tab" type="button" onclick="window.location.href='dashboard.php?tab=history'">
                        Delivery History
                    </button>
                </li>
            </ul>

            <!-- Tab Contents -->
            <div class="tab-content" id="deliveryTabsContent">
                
                <!-- Active Deliveries Pane -->
                <div class="tab-pane fade <?php echo ($active_tab === 'active') ? 'show active' : ''; ?>" id="active-pane" role="tabpanel" aria-labelledby="active-tab">
                    <?php if (empty($active_orders)): ?>
                        <div class="card p-5 text-center shadow-sm">
                            <div class="mb-3">
                                <i class="fas fa-clipboard-list text-muted display-4"></i>
                            </div>
                            <h4>No active deliveries</h4>
                            <p class="text-muted mb-0">There are currently no active orders assigned to you.</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($active_orders as $order): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card order-card border-start border-4 border-warning h-100">
                                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0 text-primary fw-bold">Order #<?php echo $order['order_id']; ?></h5>
                                        <span class="badge bg-<?php echo ($order['order_status'] === 'on the way') ? 'info text-dark' : 'secondary'; ?> text-uppercase">
                                            <?php echo $order['order_status']; ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <!-- Restaurant Info -->
                                        <div class="mb-3">
                                            <h6 class="text-muted small fw-bold mb-1"><i class="fas fa-store me-1"></i> PICK UP FROM:</h6>
                                            <p class="mb-0 fw-bold fs-6 text-dark"><?php echo $order['restaurant_name']; ?></p>
                                        </div>
                                        
                                        <hr class="my-2 text-muted">
                                        
                                        <!-- Customer Info -->
                                        <div class="mb-3">
                                            <h6 class="text-muted small fw-bold mb-1"><i class="fas fa-user me-1"></i> DELIVER TO:</h6>
                                            <p class="mb-1 fw-bold text-dark"><?php echo $order['customer_name']; ?></p>
                                            <p class="mb-1 text-secondary"><i class="fas fa-map-marker-alt text-danger me-1"></i> <?php echo $order['delivery_address']; ?></p>
                                            <p class="mb-0 fw-bold text-dark"><i class="fas fa-phone text-success me-1"></i> <?php echo $order['delivery_phone']; ?></p>
                                        </div>
                                        
                                        <hr class="my-2 text-muted">
                                        
                                        <!-- Payment Info -->
                                        <div class="mb-3 d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="text-muted small fw-bold mb-1">TOTAL AMOUNT:</h6>
                                                <h5 class="mb-0 text-success fw-bold"><?php echo format_price($order['total_amount']); ?></h5>
                                            </div>
                                            <div class="text-end">
                                                <h6 class="text-muted small fw-bold mb-1">METHOD:</h6>
                                                <span class="badge bg-dark"><?php echo ($order['payment_method'] === 'upi') ? 'UPI QR Code' : 'Cash on Delivery'; ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <h6 class="text-muted small fw-bold mb-1">PAYMENT STATUS:</h6>
                                            <span class="badge bg-<?php echo ($order['payment_status'] === 'paid') ? 'success' : 'warning text-dark'; ?>">
                                                <?php echo ucfirst($order['payment_status']); ?>
                                            </span>
                                        </div>

                                        <!-- Collapsible UPI QR Code for pending payment -->
                                        <?php if ($order['payment_status'] !== 'paid'): 
                                            $upi_id = get_site_setting('upi_id', '7979730721@rapl');
                                            $payee_name = rawurlencode(get_site_setting('site_name', 'ARS Junction'));
                                            $amount = number_format((float)$order['total_amount'], 2, '.', '');
                                            $note = rawurlencode('Order #' . $order['order_id']);
                                            $upi_string = "upi://pay?pa={$upi_id}&pn={$payee_name}&am={$amount}&cu=INR&tn={$note}";
                                            $qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=" . urlencode($upi_string);
                                        ?>
                                        <div class="mt-3">
                                            <button class="btn btn-outline-primary btn-sm w-100" type="button" data-bs-toggle="collapse" data-bs-target="#qr-collapse-<?php echo $order['order_id']; ?>">
                                                <i class="fas fa-qrcode me-1"></i> Show Customer Payment QR Code
                                            </button>
                                            <div class="collapse mt-2" id="qr-collapse-<?php echo $order['order_id']; ?>">
                                                <div class="card card-body text-center bg-white border p-3">
                                                    <p class="small text-muted mb-2">Show this QR code to the customer to scan and pay</p>
                                                    <div class="qr-container mb-2">
                                                        <img src="<?php echo $qr_api_url; ?>" alt="UPI QR" class="img-fluid border p-1 bg-white">
                                                    </div>
                                                    <div class="small fw-bold text-primary">Amount: <?php echo format_price($order['total_amount']); ?></div>
                                                    <div class="small text-muted">UPI ID: <?php echo htmlspecialchars($upi_id); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer bg-white border-top-0 pb-3">
                                        <div class="d-grid gap-2">
                                            <?php if ($order['order_status'] !== 'on the way'): ?>
                                                <!-- Start delivery button -->
                                                <form method="post" action="dashboard.php?tab=active">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                    <input type="hidden" name="action" value="start_delivery">
                                                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                                                        <i class="fas fa-play me-1"></i> Start Delivery (On the Way)
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <!-- Complete delivery button -->
                                                <form method="post" action="dashboard.php?tab=active">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                    <input type="hidden" name="action" value="complete_delivery">
                                                    <button type="submit" class="btn btn-success w-100 mb-2 fw-bold">
                                                        <i class="fas fa-check-circle me-1"></i> Mark as Delivered
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($order['payment_status'] !== 'paid'): ?>
                                                <!-- Confirm payment button -->
                                                <form method="post" action="dashboard.php?tab=active">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                    <input type="hidden" name="action" value="confirm_payment">
                                                    <button type="submit" class="btn btn-outline-success w-100 fw-bold" onclick="return confirm('Confirm that payment of <?php echo format_price($order['total_amount']); ?> is collected?');">
                                                        <i class="fas fa-money-bill-wave me-1"></i> Confirm Payment Received
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- History Pane -->
                <div class="tab-pane fade <?php echo ($active_tab === 'history') ? 'show active' : ''; ?>" id="history-pane" role="tabpanel" aria-labelledby="history-tab">
                    <?php if (empty($completed_orders)): ?>
                        <div class="card p-5 text-center shadow-sm">
                            <div class="mb-3">
                                <i class="fas fa-history text-muted display-4"></i>
                            </div>
                            <h4>No history found</h4>
                            <p class="text-muted mb-0">You haven't completed any deliveries yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="card shadow-sm border-0">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Restaurant</th>
                                                <th>Customer</th>
                                                <th>Delivery Location</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Payment</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($completed_orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo $order['order_id']; ?></td>
                                                <td><?php echo $order['restaurant_name']; ?></td>
                                                <td><?php echo $order['customer_name']; ?></td>
                                                <td><?php echo $order['delivery_address']; ?></td>
                                                <td class="fw-bold text-success"><?php echo format_price($order['total_amount']); ?></td>
                                                <td>
                                                    <span class="badge bg-success">
                                                        <?php echo ucfirst($order['order_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success">
                                                        <?php echo ucfirst($order['payment_status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </div>
</div>

<footer class="bg-dark text-white text-center py-3 mt-5">
    <div class="container">
        <small class="text-muted">&copy; 2026 ARS Junction Delivery System. All rights reserved.</small>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Web Audio Synth for Delivery Boy (Upward triplet chime: C5 -> E5 -> G5)
function playDeliveryNotificationSound() {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        playBeep(audioCtx, 523.25, 0.12, 'sine'); // C5
        setTimeout(() => playBeep(audioCtx, 659.25, 0.12, 'sine'), 120); // E5
        setTimeout(() => playBeep(audioCtx, 783.99, 0.20, 'sine'), 240); // G5
    } catch (e) {
        console.warn('AudioContext failed:', e);
    }
}

function playBeep(ctx, frequency, duration, type) {
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.type = type;
    osc.frequency.value = frequency;
    gain.gain.setValueAtTime(0.12, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.00001, ctx.currentTime + duration);
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.start();
    osc.stop(ctx.currentTime + duration);
}

document.addEventListener('DOMContentLoaded', function() {
    let lastActiveCount = null;
    let lastLatestOrderId = null;

    function pollDeliveryOrders() {
        fetch('../api/poll_notifications.php?role=delivery')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (lastLatestOrderId === null) {
                    lastLatestOrderId = data.latest_order_id;
                    lastActiveCount = data.active_count;
                    return;
                }

                if (data.latest_order_id > lastLatestOrderId || data.active_count > lastActiveCount) {
                    playDeliveryNotificationSound();
                    
                    // Show a floating delivery assignment banner
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-info border-primary alert-dismissible fade show position-fixed top-0 end-0 m-3 shadow-lg';
                    alertDiv.style.zIndex = '9999';
                    alertDiv.innerHTML = `
                        <strong><i class="fas fa-truck text-primary me-2"></i>New Assignment!</strong> You have been assigned a new delivery order #${data.latest_order_id}.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.body.appendChild(alertDiv);
                    
                    // Auto reload portal so they see it in active list
                    setTimeout(() => {
                        window.location.reload();
                    }, 3000);
                }

                lastLatestOrderId = data.latest_order_id;
                lastActiveCount = data.active_count;
            }
        })
        .catch(err => console.error('Error polling delivery notifications:', err));
    }

    // Poll every 5 seconds
    setInterval(pollDeliveryOrders, 5000);
    // Initial check
    setTimeout(pollDeliveryOrders, 1000);

    // HTML5 Geolocation Watcher for live location updates
    let watchId = null;
    function startLocationTracking() {
        if ('geolocation' in navigator) {
            watchId = navigator.geolocation.watchPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    const formData = new FormData();
                    formData.append('latitude', lat);
                    formData.append('longitude', lng);
                    
                    fetch('../api/update_location.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (!data.success) {
                            console.warn('Location update failed:', data.message);
                        }
                    })
                    .catch(err => console.error('Error sending GPS coords:', err));
                },
                function(error) {
                    console.warn('Geolocation error:', error.message);
                },
                {
                    enableHighAccuracy: true,
                    maximumAge: 10000,
                    timeout: 5000
                }
            );
        } else {
            console.warn('Geolocation not supported by this browser.');
        }
    }
    
    // Start tracking if there are active deliveries displayed on dashboard
    const hasActiveOrders = document.querySelectorAll('span.badge.bg-warning, span.badge.bg-info, span.badge.bg-primary').length > 0;
    if (hasActiveOrders) {
        startLocationTracking();
    }
});
</script>

</body>
</html>
