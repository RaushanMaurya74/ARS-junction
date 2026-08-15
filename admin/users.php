<?php
$page_title = "Manage Users";
require_once 'admin_header.php';

$success_msg = '';
$error_msg = '';
$restaurants_list = admin_get_all_restaurants(200, 0);

// Handle user addition/deletion/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $result = admin_add_user($_POST);
        if ($result['success']) {
            $success_msg = 'User added successfully.';
        } else {
            $error_msg = $result['message'];
        }
    }
    
    if (isset($_POST['update_user'])) {
        $user_id = (int)$_POST['user_id'];
        $result = admin_update_user($user_id, $_POST);
        if ($result['success']) {
            $success_msg = 'User updated successfully.';
        } else {
            $error_msg = $result['message'];
        }
    }
    
    if (isset($_POST['delete_user'])) {
        $result = admin_delete_user((int)$_POST['user_id']);
        if ($result['success']) {
            $success_msg = 'User deleted successfully.';
        } else {
            $error_msg = $result['message'];
        }
    }
}

// Get users
$limit = 20;
$offset = isset($_GET['page']) ? (intval($_GET['page']) - 1) * $limit : 0;
if ($offset < 0) $offset = 0;

$users = admin_get_all_users($limit, $offset);

// Apply search filter if query is set
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = strtolower(clean_input($_GET['search']));
    $filtered = [];
    foreach ($users as $user) {
        if (stripos($user['name'], $search) !== false ||
            stripos($user['email'], $search) !== false ||
            stripos($user['phone'], $search) !== false ||
            stripos($user['city'], $search) !== false) {
            $filtered[] = $user;
        }
    }
    $users = $filtered;
}

// Get total count of users for pagination
$total_users = count_total_users();
$total_pages = ceil($total_users / $limit);
$current_page = floor($offset / $limit) + 1;

// View single user
$view_user = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $user_id = intval($_GET['view']);
    $view_user = get_user_by_id($user_id);
    
    if ($view_user) {
        // Get user orders
        $user_orders = get_user_orders($user_id);
    }
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manage Users</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Users</li>
            </ol>
        </nav>
    </div>
    
    <?php if ($success_msg): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success_msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($error_msg): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error_msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($view_user): ?>
    <!-- Single User View -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">User Details</h6>
            <a href="users.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <?php if (!empty($view_user['profile_image'])): ?>
                                <img src="<?php echo $view_user['profile_image']; ?>" alt="Profile Picture" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                                <?php else: ?>
                                <div class="display-4 text-primary mb-3">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            <h5 class="mb-1"><?php echo $view_user['name']; ?></h5>
                            <p class="text-muted mb-3"><?php echo $view_user['email']; ?></p>
                            <p class="mb-1">
                                <span class="badge bg-<?php echo $view_user['is_admin'] ? 'danger' : 'primary'; ?>">
                                    <?php echo $view_user['is_admin'] ? 'Admin' : 'Customer'; ?>
                                </span>
                            </p>
                            <p class="mb-0 small text-muted">
                                Joined: <?php echo date('F d, Y', strtotime($view_user['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Contact Information</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-1">
                                <strong><i class="fas fa-envelope me-2"></i> Email:</strong>
                                <?php echo $view_user['email']; ?>
                            </p>
                            <p class="mb-1">
                                <strong><i class="fas fa-phone me-2"></i> Phone:</strong>
                                <?php echo !empty($view_user['phone']) ? $view_user['phone'] : 'Not provided'; ?>
                            </p>
                            <p class="mb-0">
                                <strong><i class="fas fa-map-marker-alt me-2"></i> Address:</strong><br>
                                <?php 
                                if (!empty($view_user['address'])) {
                                    echo $view_user['address'];
                                    if (!empty($view_user['city']) || !empty($view_user['state']) || !empty($view_user['zip_code'])) {
                                        echo '<br>';
                                        echo (!empty($view_user['city']) ? $view_user['city'] . ', ' : '');
                                        echo (!empty($view_user['state']) ? $view_user['state'] . ' ' : '');
                                        echo (!empty($view_user['zip_code']) ? $view_user['zip_code'] : '');
                                    }
                                } else {
                                    echo 'Not provided';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Account Information</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-1">
                                <strong>Registration Date:</strong><br>
                                <?php echo date('F d, Y h:i A', strtotime($view_user['created_at'])); ?>
                            </p>
                            <p class="mb-1">
                                <strong>Last Updated:</strong><br>
                                <?php echo date('F d, Y h:i A', strtotime($view_user['updated_at'])); ?>
                            </p>
                            <p class="mb-0">
                                <strong>Login Type:</strong><br>
                                <?php 
                                 switch($view_user['social_type']) {
                                     case 'google': echo 'Google'; break;
                                     default: echo 'Email & Password';
                                 }
                                ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Edit User Details & Roles</h6>
                        </div>
                        <div class="card-body">
                            <form method="post" action="users.php?view=<?php echo $view_user['user_id']; ?>">
                                <input type="hidden" name="update_user" value="1">
                                <input type="hidden" name="user_id" value="<?php echo $view_user['user_id']; ?>">
                                
                                <div class="mb-2">
                                    <label class="form-label small fw-bold">Full Name</label>
                                    <input type="text" class="form-control form-control-sm" name="name" value="<?php echo htmlspecialchars($view_user['name']); ?>" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-bold">Email Address</label>
                                    <input type="email" class="form-control form-control-sm" name="email" value="<?php echo htmlspecialchars($view_user['email']); ?>" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-bold">New Password (leave blank to keep current)</label>
                                    <input type="password" class="form-control form-control-sm" name="password" placeholder="Enter new password">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-bold">Phone</label>
                                    <input type="tel" class="form-control form-control-sm" name="phone" value="<?php echo htmlspecialchars($view_user['phone'] ?? ''); ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-bold">Address</label>
                                    <input type="text" class="form-control form-control-sm" name="address" value="<?php echo htmlspecialchars($view_user['address'] ?? ''); ?>">
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">City</label>
                                        <input type="text" class="form-control form-control-sm" name="city" value="<?php echo htmlspecialchars($view_user['city'] ?? ''); ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">State</label>
                                        <input type="text" class="form-control form-control-sm" name="state" value="<?php echo htmlspecialchars($view_user['state'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-bold">ZIP Code</label>
                                    <input type="text" class="form-control form-control-sm" name="zip_code" value="<?php echo htmlspecialchars($view_user['zip_code'] ?? ''); ?>">
                                </div>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="is_admin" id="edit_is_admin" <?php echo $view_user['is_admin'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label small" for="edit_is_admin">Is Admin / Staff</label>
                                </div>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="is_delivery_boy" id="edit_is_delivery_boy" <?php echo $view_user['is_delivery_boy'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label small" for="edit_is_delivery_boy">Is Delivery Boy</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_restaurant_owner" id="edit_is_restaurant_owner" <?php echo ($view_user['is_restaurant_owner'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label small" for="edit_is_restaurant_owner">Is Restaurant Owner</label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Associated Restaurant</label>
                                    <select class="form-select form-select-sm" name="restaurant_id">
                                        <option value="">-- Select Restaurant --</option>
                                        <?php foreach ($restaurants_list as $res): ?>
                                            <option value="<?php echo $res['restaurant_id']; ?>" <?php echo (($view_user['restaurant_id'] ?? null) == $res['restaurant_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($res['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-sm btn-primary w-100">Update User Settings</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Order History</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($user_orders)): ?>
                            <p class="text-center py-3">This user has not placed any orders yet.</p>
                            <?php else: ?>
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
                                        <?php foreach($user_orders as $order): ?>
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
                                                <a href="orders.php?view=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> View
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
    </div>
    
    <?php else: ?>
    <!-- Users List with Add User Form -->
    <div class="row">
        <!-- Add User Column -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-light">
                    <h6 class="m-0 font-weight-bold text-primary">Add New User</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="users.php">
                        <input type="hidden" name="add_user" value="1">
                        <div class="mb-2">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="address">
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" name="city">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">State</label>
                                <input type="text" class="form-control" name="state">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ZIP Code</label>
                            <input type="text" class="form-control" name="zip_code">
                        </div>
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" name="is_delivery_boy" id="is_delivery_boy">
                            <label class="form-check-label" for="is_delivery_boy">
                                Is Delivery Boy
                            </label>
                        </div>
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" name="is_restaurant_owner" id="is_restaurant_owner">
                            <label class="form-check-label" for="is_restaurant_owner">
                                Is Restaurant Owner
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="is_admin" id="is_admin">
                            <label class="form-check-label" for="is_admin">
                                Is Admin / Staff
                            </label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Associated Restaurant</label>
                            <select class="form-select" name="restaurant_id">
                                <option value="">-- Select Restaurant (for Owner/Delivery) --</option>
                                <?php foreach ($restaurants_list as $res): ?>
                                    <option value="<?php echo $res['restaurant_id']; ?>"><?php echo htmlspecialchars($res['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Create User</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Users Table Column -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">All Users</h6>
                    <div class="input-group w-50">
                        <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search users...">
                        <button class="btn btn-sm btn-outline-secondary" type="button" id="searchButton">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <?php if (empty($users)): ?>
                        <p class="text-center py-3">No users found.</p>
                        <?php else: ?>
                        <table class="table table-bordered table-hover" id="usersTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Type</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($users as $user): ?>
                                <tr>
                                    <td>#<?php echo $user['user_id']; ?></td>
                                    <td>
                                        <?php echo $user['name']; ?>
                                        <?php if ($user['is_admin']): ?>
                                            <span class="badge bg-danger">Admin</span>
                                        <?php endif; ?>
                                        <?php if ($user['is_delivery_boy']): ?>
                                            <span class="badge bg-warning text-dark">Delivery</span>
                                        <?php endif; ?>
                                        <?php if ($user['is_restaurant_owner'] ?? 0): ?>
                                            <span class="badge bg-success">Owner</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td><?php echo !empty($user['phone']) ? $user['phone'] : 'N/A'; ?></td>
                                    <td>
                                        <?php 
                                         switch($user['social_type']) {
                                             case 'google': echo '<i class="fab fa-google text-danger"></i> Google'; break;
                                             default: echo '<i class="fas fa-envelope"></i> Email';
                                         }
                                        ?>
                                    </td>
                                    <td style="white-space: nowrap;">
                                        <a href="users.php?view=<?php echo $user['user_id']; ?>" class="btn btn-xs btn-info py-0 px-1" style="font-size: 0.8rem;">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <form method="post" action="users.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                            <input type="hidden" name="delete_user" value="1">
                                            <button class="btn btn-xs btn-danger py-0 px-1" style="font-size: 0.8rem;" type="submit">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </form>
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
                        <ul class="pagination pagination-sm justify-content-center">
                            <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            
                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($current_page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page + 1; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle search functionality
        const searchInput = document.getElementById('searchInput');
        const searchButton = document.getElementById('searchButton');
        const usersTable = document.getElementById('usersTable');
        
        if (searchInput && searchButton && usersTable) {
            const searchUsers = function() {
                const searchText = searchInput.value.toLowerCase();
                const rows = usersTable.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const name = row.cells[1].textContent.toLowerCase();
                    const email = row.cells[2].textContent.toLowerCase();
                    const phone = row.cells[3].textContent.toLowerCase();
                    
                    if (name.includes(searchText) || email.includes(searchText) || phone.includes(searchText)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            };
            
            searchButton.addEventListener('click', searchUsers);
            searchInput.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    searchUsers();
                }
            });
        }
    });
</script>

<?php
require_once 'admin_footer.php';
?>
