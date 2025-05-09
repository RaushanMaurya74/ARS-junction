<?php
$page_title = "My Profile";
require_once 'includes/header.php';

// Redirect if not logged in
require_login();

$user_id = $_SESSION['user_id'];
$user = get_user_by_id($user_id);

$success_msg = '';
$error_msg = '';

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $name = clean_input($_POST['name']);
    $phone = clean_input($_POST['phone']);
    $address = clean_input($_POST['address']);
    $city = clean_input($_POST['city']);
    $state = clean_input($_POST['state']);
    $zip_code = clean_input($_POST['zip_code']);
    
    $update_data = array(
        'name' => $name,
        'phone' => $phone,
        'address' => $address,
        'city' => $city,
        'state' => $state,
        'zip_code' => $zip_code
    );
    
    $result = update_user_profile($user_id, $update_data);
    
    if ($result['success']) {
        $success_msg = 'Profile updated successfully!';
        $user = get_user_by_id($user_id); // Refresh user data
    } else {
        $error_msg = $result['message'];
    }
}

// Handle password change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $error_msg = 'New passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $error_msg = 'New password must be at least 6 characters long.';
    } else {
        $result = change_password($user_id, $current_password, $new_password);
        
        if ($result['success']) {
            $success_msg = 'Password changed successfully!';
        } else {
            $error_msg = $result['message'];
        }
    }
}

// Get recent orders
$recent_orders = get_user_orders($user_id);

// Extra JS
$extra_js = '<script src="js/auth.js"></script>';
?>

<div class="container py-4">
    <div class="row">
        <!-- Profile Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="profile-sidebar card">
                <div class="card-body text-center">
                    <div class="profile-img-container mb-3">
                        <?php if (!empty($user['profile_image'])): ?>
                        <img src="<?php echo $user['profile_image']; ?>" alt="Profile Picture" class="profile-img">
                        <?php else: ?>
                        <i class="fas fa-user-circle display-1 text-primary"></i>
                        <?php endif; ?>
                    </div>
                    <h5 class="mb-1"><?php echo $user['name']; ?></h5>
                    <p class="text-muted"><?php echo $user['email']; ?></p>
                    
                    <div class="d-grid gap-2 mt-3">
                        <a href="order_tracking.php" class="btn btn-outline-primary">My Orders</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Profile Content -->
        <div class="col-lg-9">
            <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
            <?php endif; ?>
            
            <!-- Profile Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                    <form id="profile-form" action="profile.php" method="post">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo $user['name']; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" value="<?php echo $user['email']; ?>" disabled>
                                <div class="form-text">Email cannot be changed.</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo $user['phone']; ?>">
                            </div>
                        </div>
                        <hr class="my-4">
                        <h6 class="mb-3">Delivery Address</h6>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" name="address" value="<?php echo $user['address']; ?>">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" value="<?php echo $user['city']; ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="state" class="form-label">State</label>
                                <input type="text" class="form-control" id="state" name="state" value="<?php echo $user['state']; ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="zip_code" class="form-label">ZIP Code</label>
                                <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?php echo $user['zip_code']; ?>">
                            </div>
                        </div>
                        <div class="d-grid">
                            <input type="hidden" name="update_profile" value="1">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <form id="password-form" action="profile.php" method="post">
                        <div class="mb-3">
                            <label for="current-password" class="form-label">Current Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="current-password" name="current_password" required>
                                <button class="btn btn-outline-secondary" type="button" id="toggle-current-password" onclick="togglePasswordVisibility('current-password', 'toggle-current-password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="new-password" class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new-password" name="new_password" required>
                                <button class="btn btn-outline-secondary" type="button" id="toggle-new-password" onclick="togglePasswordVisibility('new-password', 'toggle-new-password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">Password must be at least 6 characters long.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm-password" class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm-password" name="confirm_password" required>
                                <button class="btn btn-outline-secondary" type="button" id="toggle-confirm-password" onclick="togglePasswordVisibility('confirm-password', 'toggle-confirm-password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="d-grid">
                            <input type="hidden" name="change_password" value="1">
                            <button type="submit" class="btn btn-primary">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Orders</h5>
                    <a href="order_tracking.php" class="btn btn-outline-primary btn-sm">View All Orders</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_orders)): ?>
                    <div class="text-center py-3">
                        <i class="fas fa-shopping-bag display-4 text-muted mb-3"></i>
                        <p>You haven't placed any orders yet.</p>
                        <a href="restaurants.php" class="btn btn-primary">Browse Restaurants</a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Restaurant</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Display only the 5 most recent orders
                                $recent_orders = array_slice($recent_orders, 0, 5);
                                foreach($recent_orders as $order): 
                                ?>
                                <tr>
                                    <td>#<?php echo $order['order_id']; ?></td>
                                    <td><?php echo $order['restaurant_name']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
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
                                        <a href="order_tracking.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-primary">
                                            View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
