<?php
/**
 * Restaurant Owner Portal - Profile & Settings
 */

$page_title = "Restaurant Profile Settings";
require_once 'includes/restaurant_header.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $conn;

    // Form 1: Update Restaurant Information
    if (isset($_POST['update_restaurant'])) {
        $name = clean_input($_POST['name']);
        $description = clean_input($_POST['description']);
        $address = clean_input($_POST['address']);
        $city = clean_input($_POST['city']);
        $state = clean_input($_POST['state']);
        $zip_code = clean_input($_POST['zip_code']);
        $phone = clean_input($_POST['phone']);
        $email = clean_input($_POST['email']);
        $delivery_time = intval($_POST['delivery_time']);
        $delivery_fee = floatval($_POST['delivery_fee']);
        $minimum_order = floatval($_POST['minimum_order']);
        
        $uploaded_banner = '';
        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                // Compress and encode to base64
                require_once '../includes/functions.php';
                $uploaded_banner = get_compressed_base64_image($_FILES['banner_image']['tmp_name'], $ext, 800, 400);
            } else {
                $error_msg = 'Invalid image format. Only JPG, JPEG, PNG, and GIF allowed.';
            }
        }

        if (empty($error_msg)) {
            if (empty($name) || empty($address) || empty($phone)) {
                $error_msg = 'Please fill in the required restaurant fields (Name, Address, Phone).';
            } else {
                $banner_to_save = !empty($uploaded_banner) ? $uploaded_banner : $restaurant['image'];
                
                $stmt = $conn->prepare("
                    UPDATE restaurants 
                    SET name = ?, description = ?, address = ?, city = ?, state = ?, zip_code = ?, phone = ?, email = ?, delivery_time = ?, delivery_fee = ?, minimum_order = ?, image = ? 
                    WHERE restaurant_id = ?
                ");
                
                if ($stmt->execute([$name, $description, $address, $city, $state, $zip_code, $phone, $email, $delivery_time, $delivery_fee, $minimum_order, $banner_to_save, $restaurant_id])) {
                    $success_msg = 'Restaurant information updated successfully.';
                    // Reload restaurant details
                    $restaurant = get_restaurant_by_id($restaurant_id);
                } else {
                    $error_msg = 'Failed to update restaurant details.';
                }
            }
        }
    }

    // Form 2: Update Manager Login Settings
    if (isset($_POST['update_manager'])) {
        $manager_name = clean_input($_POST['manager_name']);
        $manager_email = clean_input($_POST['manager_email']);
        
        if (empty($manager_name) || empty($manager_email)) {
            $error_msg = 'Manager Name and Email are required.';
        } else {
            // Check unique email
            $stmt_check = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt_check->execute([$manager_email, $manager_id]);
            
            if ($stmt_check->fetch()) {
                $error_msg = 'Email address is already taken by another user.';
            } else {
                if (!empty($_POST['new_password'])) {
                    $hashed_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE user_id = ?");
                    $success = $stmt->execute([$manager_name, $manager_email, $hashed_password, $manager_id]);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
                    $success = $stmt->execute([$manager_name, $manager_email, $manager_id]);
                }

                if ($success) {
                    $success_msg = 'Manager account updated successfully.';
                    // Refresh session and details
                    $_SESSION['restaurant_owner_name'] = $manager_name;
                    $_SESSION['restaurant_owner_email'] = $manager_email;
                    $manager = get_user_by_id($manager_id);
                } else {
                    $error_msg = 'Failed to update manager account.';
                }
            }
        }
    }
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
        <h1 class="h3 mb-0 text-gray-800">Profile & Settings</h1>
    </div>

    <div class="row">
        <!-- Restaurant Information Settings Card -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light py-3">
                    <h6 class="m-0 fw-bold text-primary"><i class="fas fa-store me-2"></i>Restaurant Details</h6>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="update_restaurant" value="1">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Restaurant Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($restaurant['name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Contact Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($restaurant['phone']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Description / Slogan</label>
                            <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($restaurant['description']); ?></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-bold">Street Address <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($restaurant['address']); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Email Address</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($restaurant['email']); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">City</label>
                                <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($restaurant['city']); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">State</label>
                                <input type="text" class="form-control" name="state" value="<?php echo htmlspecialchars($restaurant['state']); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">ZIP Code</label>
                                <input type="text" class="form-control" name="zip_code" value="<?php echo htmlspecialchars($restaurant['zip_code']); ?>" required>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Avg Delivery Time (mins)</label>
                                <input type="number" class="form-control" name="delivery_time" value="<?php echo htmlspecialchars($restaurant['delivery_time']); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Delivery Fee (&#8377;)</label>
                                <input type="number" step="0.01" class="form-control" name="delivery_fee" value="<?php echo htmlspecialchars($restaurant['delivery_fee']); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Minimum Order Amount (&#8377;)</label>
                                <input type="number" step="0.01" class="form-control" name="minimum_order" value="<?php echo htmlspecialchars($restaurant['minimum_order']); ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Restaurant Banner Image</label>
                            <?php if (has_image($restaurant['image'], true)): ?>
                                <div class="mb-2">
                                    <img src="<?php echo get_image_url($restaurant['image'], true); ?>" class="img-thumbnail rounded" style="max-height: 120px; width: 100%; object-fit: cover;">
                                </div>
                            <?php endif; ?>
                            <input class="form-control" type="file" name="banner_image" accept="image/*">
                        </div>

                        <button type="submit" class="btn btn-primary fw-bold px-4">Save Restaurant Settings</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Manager Account Credentials Card -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light py-3">
                    <h6 class="m-0 fw-bold text-primary"><i class="fas fa-user-tie me-2"></i>Manager Profile</h6>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="update_manager" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Manager Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="manager_name" value="<?php echo htmlspecialchars($manager['name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Manager Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="manager_email" value="<?php echo htmlspecialchars($manager['email']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Reset Password</label>
                            <input type="password" class="form-control" name="new_password" placeholder="Enter new password (optional)">
                            <div class="form-text small">Leave blank to keep your current password.</div>
                        </div>

                        <button type="submit" class="btn btn-outline-primary fw-bold w-100 mt-2">Save Profile Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
