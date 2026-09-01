<?php
// Start session and check authentication before outputting headers
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect if not logged in
require_login();

$page_title = "My Profile";
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];
$user = get_user_by_id($user_id);

$success_msg = '';
$error_msg = '';

// Handle Profile Image Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_photo'])) {
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_pic']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // Compress and encode to base64
            $base64_image = get_compressed_base64_image($_FILES['profile_pic']['tmp_name'], $ext, 200, 200);
            
            $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE user_id = ?");
            if ($stmt->execute([$base64_image, $user_id])) {
                $success_msg = 'Profile photo uploaded successfully!';
                $user = get_user_by_id($user_id);
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
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_photo'])) {
    $stmt = $conn->prepare("UPDATE users SET profile_image = NULL WHERE user_id = ?");
    if ($stmt->execute([$user_id])) {
        $success_msg = 'Profile photo removed successfully!';
        $user = get_user_by_id($user_id); // Refresh user data
    } else {
        $error_msg = 'Failed to remove profile photo.';
    }
}

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    try {
        $schema = [
            'name' => ['type' => 'string', 'required' => true, 'min_len' => 2, 'max_len' => 100],
            'phone' => ['type' => 'phone', 'required' => true],
            'address' => ['type' => 'string', 'required' => true, 'max_len' => 255],
            'city' => ['type' => 'string', 'required' => true, 'max_len' => 100],
            'state' => ['type' => 'string', 'required' => true, 'max_len' => 100],
            'zip_code' => ['type' => 'pincode', 'required' => true]
        ];

        $validated = Validator::validate($_POST, $schema, false);
        
        $update_data = array(
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'zip_code' => $validated['zip_code']
        );
        
        $result = update_user_profile($user_id, $update_data);
        
        if ($result['success']) {
            $success_msg = 'Profile updated successfully!';
            $user = get_user_by_id($user_id); // Refresh user data
        } else {
            $error_msg = $result['message'];
        }
    } catch (ValidationException $e) {
        $errors = $e->getErrors();
        $error_msg = reset($errors);
    }
}

// Handle password change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    try {
        $schema = [
            'current_password' => ['type' => 'string', 'required' => true, 'min_len' => 1],
            'new_password' => ['type' => 'string', 'required' => true, 'min_len' => 6, 'max_len' => 100],
            'confirm_password' => ['type' => 'string', 'required' => true, 'min_len' => 6, 'max_len' => 100]
        ];

        $validated = Validator::validate($_POST, $schema, false);
        
        $current_password = $validated['current_password'];
        $new_password = $validated['new_password'];
        $confirm_password = $validated['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            $error_msg = 'New passwords do not match.';
        } else {
            $result = change_password($user_id, $current_password, $new_password);
            
            if ($result['success']) {
                $success_msg = 'Password changed successfully!';
            } else {
                $error_msg = $result['message'];
            }
        }
    } catch (ValidationException $e) {
        $errors = $e->getErrors();
        $error_msg = reset($errors);
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
                    <div class="profile-img-container mb-3 text-center">
                        <?php if (!empty($user['profile_image'])): ?>
                        <img src="<?php echo $user['profile_image']; ?>" alt="Profile Picture" class="profile-img rounded-circle border shadow" style="width: 120px; height: 120px; object-fit: cover;">
                        <?php else: ?>
                        <i class="fas fa-user-circle display-1 text-primary"></i>
                        <?php endif; ?>
                    </div>
                    
                    <form method="post" action="profile.php" enctype="multipart/form-data" class="mb-2">
                        <div class="mb-2">
                            <label for="profile_pic" class="form-label btn btn-sm btn-outline-secondary w-100 mb-0">
                                <i class="fas fa-camera me-1"></i> Choose Photo
                            </label>
                            <input type="file" id="profile_pic" name="profile_pic" accept="image/*" class="d-none" onchange="this.form.submit()">
                        </div>
                        <input type="hidden" name="upload_photo" value="1">
                    </form>
                    
                    <?php if (!empty($user['profile_image'])): ?>
                    <form method="post" action="profile.php" class="mb-3">
                        <input type="hidden" name="remove_photo" value="1">
                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                            <i class="fas fa-trash-alt me-1"></i> Remove Photo
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <h5 class="mb-1"><?php echo htmlspecialchars($user['name'], ENT_QUOTES); ?></h5>
                    <p class="text-muted small"><?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?></p>
                    
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
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name'], ENT_QUOTES); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?>" disabled>
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
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'] . ' UTC')); ?></td>
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
