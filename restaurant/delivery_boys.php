<?php
/**
 * Restaurant Owner Portal - Delivery Boys Management
 */

$page_title = "Manage Delivery Boys";
require_once 'includes/restaurant_header.php';

$success_msg = '';
$error_msg = '';

// Handle actions: add, update, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $conn;

    // Action: Add Delivery Boy
    if (isset($_POST['add_boy'])) {
        $name = clean_input($_POST['name']);
        $email = clean_input($_POST['email']);
        $password = $_POST['password'];
        $phone = clean_input($_POST['phone']);
        $address = clean_input($_POST['address']);
        $city = clean_input($_POST['city']);
        $state = clean_input($_POST['state']);
        $zip_code = clean_input($_POST['zip_code']);

        if (empty($name) || empty($email) || empty($password)) {
            $error_msg = 'Please fill in all required fields.';
        } elseif (email_exists($email)) {
            $error_msg = 'Email address already exists.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, address, city, state, zip_code, is_delivery_boy, restaurant_id) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)");
            
            if ($stmt->execute([$name, $email, $hashed_password, $phone, $address, $city, $state, $zip_code, $restaurant_id])) {
                $success_msg = 'Delivery boy registered successfully.';
            } else {
                $error_msg = 'Failed to register delivery boy.';
            }
        }
    }

    // Action: Update Delivery Boy
    if (isset($_POST['update_boy'])) {
        $boy_id = intval($_POST['user_id']);
        
        // Verify ownership
        $stmt_check = $conn->prepare("SELECT restaurant_id FROM users WHERE user_id = ? AND is_delivery_boy = 1");
        $stmt_check->execute([$boy_id]);
        $owner_res_id = $stmt_check->fetchColumn();

        if ($owner_res_id == $restaurant_id) {
            $name = clean_input($_POST['name']);
            $email = clean_input($_POST['email']);
            $phone = clean_input($_POST['phone']);
            $address = clean_input($_POST['address']);
            $city = clean_input($_POST['city']);
            $state = clean_input($_POST['state']);
            $zip_code = clean_input($_POST['zip_code']);

            if (empty($name) || empty($email)) {
                $error_msg = 'Please fill in name and email fields.';
            } else {
                // Check unique email
                $stmt_email = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
                $stmt_email->execute([$email, $boy_id]);
                if ($stmt_email->fetch()) {
                    $error_msg = 'Email already taken by another user.';
                } else {
                    if (!empty($_POST['password'])) {
                        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ?, phone = ?, address = ?, city = ?, state = ?, zip_code = ? 
                                               WHERE user_id = ? AND restaurant_id = ? AND is_delivery_boy = 1");
                        $success = $stmt->execute([$name, $email, $hashed_password, $phone, $address, $city, $state, $zip_code, $boy_id, $restaurant_id]);
                    } else {
                        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, city = ?, state = ?, zip_code = ? 
                                               WHERE user_id = ? AND restaurant_id = ? AND is_delivery_boy = 1");
                        $success = $stmt->execute([$name, $email, $phone, $address, $city, $state, $zip_code, $boy_id, $restaurant_id]);
                    }

                    if ($success) {
                        $success_msg = 'Delivery boy details updated successfully.';
                    } else {
                        $error_msg = 'Failed to update details.';
                    }
                }
            }
        } else {
            $error_msg = 'Access Denied: You do not manage this delivery boy.';
        }
    }

    // Action: Delete Delivery Boy
    if (isset($_POST['delete_boy'])) {
        $boy_id = intval($_POST['user_id']);

        // Verify ownership
        $stmt_check = $conn->prepare("SELECT restaurant_id FROM users WHERE user_id = ? AND is_delivery_boy = 1");
        $stmt_check->execute([$boy_id]);
        $owner_res_id = $stmt_check->fetchColumn();

        if ($owner_res_id == $restaurant_id) {
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND restaurant_id = ? AND is_delivery_boy = 1");
            if ($stmt->execute([$boy_id, $restaurant_id])) {
                $success_msg = 'Delivery boy removed successfully.';
            } else {
                $error_msg = 'Failed to delete delivery boy.';
            }
        } else {
            $error_msg = 'Access Denied: You do not manage this delivery boy.';
        }
    }
}

// Fetch all delivery boys for this restaurant
global $conn;
$stmt_boys = $conn->prepare("SELECT * FROM users WHERE is_delivery_boy = 1 AND restaurant_id = ? ORDER BY name");
$stmt_boys->execute([$restaurant_id]);
$delivery_boys = $stmt_boys->fetchAll(PDO::FETCH_ASSOC);

// Check if editing
$edit_boy = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt_edit = $conn->prepare("SELECT * FROM users WHERE user_id = ? AND restaurant_id = ? AND is_delivery_boy = 1");
    $stmt_edit->execute([$edit_id, $restaurant_id]);
    $edit_boy = $stmt_edit->fetch(PDO::FETCH_ASSOC);
}
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

    <!-- Title Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manage Delivery Boys</h1>
        <a href="delivery_boys.php" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i> Add / Reset Form</a>
    </div>

    <div class="row">
        <!-- Form Column (Add/Edit) -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><?php echo $edit_boy ? 'Edit Delivery Boy' : 'Register Delivery Boy'; ?></h6>
                </div>
                <div class="card-body">
                    <form method="post">
                        <?php if ($edit_boy): ?>
                            <input type="hidden" name="user_id" value="<?php echo $edit_boy['user_id']; ?>">
                            <input type="hidden" name="update_boy" value="1">
                        <?php else: ?>
                            <input type="hidden" name="add_boy" value="1">
                        <?php endif; ?>

                        <div class="mb-2">
                            <label class="form-label small fw-bold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="name" value="<?php echo htmlspecialchars($edit_boy['name'] ?? ''); ?>" required placeholder="e.g. Ramesh Kumar">
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-bold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control form-control-sm" name="email" value="<?php echo htmlspecialchars($edit_boy['email'] ?? ''); ?>" required placeholder="e.g. ramesh@example.com">
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-bold">Password <?php echo $edit_boy ? '(leave blank to keep current)' : '<span class="text-danger">*</span>'; ?></label>
                            <input type="password" class="form-control form-control-sm" name="password" <?php echo $edit_boy ? '' : 'required'; ?> placeholder="••••••••">
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-bold">Phone Number</label>
                            <input type="tel" class="form-control form-control-sm" name="phone" value="<?php echo htmlspecialchars($edit_boy['phone'] ?? ''); ?>" placeholder="e.g. 9876543210">
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-bold">Address</label>
                            <input type="text" class="form-control form-control-sm" name="address" value="<?php echo htmlspecialchars($edit_boy['address'] ?? ''); ?>" placeholder="e.g. Piro Road">
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-bold">City</label>
                                <input type="text" class="form-control form-control-sm" name="city" value="<?php echo htmlspecialchars($edit_boy['city'] ?? 'Piro'); ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">State</label>
                                <input type="text" class="form-control form-control-sm" name="state" value="<?php echo htmlspecialchars($edit_boy['state'] ?? 'Bihar'); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">ZIP Code</label>
                            <input type="text" class="form-control form-control-sm" name="zip_code" value="<?php echo htmlspecialchars($edit_boy['zip_code'] ?? '802207'); ?>">
                        </div>

                        <button class="btn btn-primary w-100 fw-bold btn-sm py-2" type="submit">
                            <?php echo $edit_boy ? 'Update Details' : 'Register Rider'; ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Table Listing Column -->
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light py-3">
                    <h6 class="m-0 fw-bold text-primary">Your Delivery Crew</h6>
                </div>
                <div class="card-body p-0 table-responsive">
                    <?php if (empty($delivery_boys)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-motorcycle fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No delivery boys registered for your restaurant yet.</p>
                        </div>
                    <?php else: ?>
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th class="text-center">Deliveries</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end px-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($delivery_boys as $boy): 
                                    // Query active and completed counts
                                    $stmt_act = $conn->prepare("SELECT COUNT(*) FROM orders WHERE delivery_boy_id = ? AND order_status NOT IN ('delivered', 'cancelled')");
                                    $stmt_act->execute([$boy['user_id']]);
                                    $active_deliveries = $stmt_act->fetchColumn();

                                    $stmt_comp = $conn->prepare("SELECT COUNT(*) FROM orders WHERE delivery_boy_id = ? AND order_status = 'delivered'");
                                    $stmt_comp->execute([$boy['user_id']]);
                                    $completed_deliveries = $stmt_comp->fetchColumn();
                                ?>
                                    <tr>
                                        <td class="px-3">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center fw-bold border me-2" style="width: 38px; height: 38px;">
                                                    <i class="fas fa-truck-pickup"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($boy['name']); ?></div>
                                                    <div class="text-muted small">Registered: <?php echo date('M d, Y', strtotime($boy['created_at'])); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small"><i class="fas fa-envelope me-1 text-muted"></i><?php echo htmlspecialchars($boy['email']); ?></div>
                                            <?php if (!empty($boy['phone'])): ?>
                                                <div class="small"><i class="fas fa-phone me-1 text-muted"></i><?php echo htmlspecialchars($boy['phone']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary" title="Active Deliveries"><?php echo $active_deliveries; ?> Active</span>
                                            <span class="badge bg-success" title="Completed Deliveries"><?php echo $completed_deliveries; ?> Done</span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($boy['is_online']): ?>
                                                <span class="badge bg-success"><i class="fas fa-circle text-white me-1 animate-pulse" style="font-size: 0.5rem;"></i>Online</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Offline</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end px-4" style="white-space: nowrap;">
                                            <a href="delivery_boys.php?edit=<?php echo $boy['user_id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Remove this delivery boy from your crew?');">
                                                <input type="hidden" name="user_id" value="<?php echo $boy['user_id']; ?>">
                                                <input type="hidden" name="delete_boy" value="1">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-user-minus"></i> Remove
                                                </button>
                                            </form>
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
