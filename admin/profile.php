<?php
$page_title = "Admin Profile";
require_once 'admin_header.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_pic']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // Read file content and encode to base64
            $file_data = file_get_contents($_FILES['profile_pic']['tmp_name']);
            $base64_image = 'data:image/' . $ext . ';base64,' . base64_encode($file_data);
            
            $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE user_id = ?");
            if ($stmt->execute([$base64_image, $admin_id])) {
                $success_msg = 'Profile photo updated successfully!';
                $admin = get_admin_by_id($admin_id);
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['upload_photo'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($name) || empty($email) || empty($phone)) {
        $error_msg = "Name, email, and phone fields are required.";
    } else {
        // Validate password changes if requested
        $update_password = false;
        if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error_msg = "Please fill in all password fields to update your password.";
            } elseif ($new_password !== $confirm_password) {
                $error_msg = "New password and confirm password do not match.";
            } elseif (!password_verify($current_password, $admin['password'])) {
                $error_msg = "Current password is incorrect.";
            } else {
                $update_password = true;
            }
        }
        
        if (empty($error_msg)) {
            // Check if email already exists for another user
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
            $stmt->execute([$email, $admin_id]);
            if ($stmt->fetchColumn() > 0) {
                $error_msg = "This email is already in use by another account.";
            } else {
                if ($update_password) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, password = ? WHERE user_id = ?");
                    $result = $stmt->execute([$name, $email, $phone, $hashed_password, $admin_id]);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE user_id = ?");
                    $result = $stmt->execute([$name, $email, $phone, $admin_id]);
                }
                
                if ($result) {
                    $success_msg = "Profile updated successfully.";
                    // Refresh local $admin variable
                    $admin = get_admin_by_id($admin_id);
                } else {
                    $error_msg = "Failed to update profile. Please try again.";
                }
            }
        }
    }
}
?>

<div class="container-fluid px-4 py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4 text-gray-800"><i class="fas fa-user-cog text-primary me-2"></i> Admin Profile Settings</h1>
        </div>
    </div>

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

    <div class="row">
        <!-- Profile Card -->
        <div class="col-xl-4 col-lg-5 mb-4">
            <div class="card shadow border-0 h-100">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <?php if (has_image($admin['profile_image'], true)): ?>
                        <img src="<?php echo get_image_url($admin['profile_image'], true); ?>" alt="Admin Profile Picture" class="profile-img rounded-circle border shadow mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                        <?php else: ?>
                        <i class="fas fa-user-circle text-primary display-1 mb-3"></i>
                        <?php endif; ?>
                    </div>
                    
                    <form method="post" action="profile.php" enctype="multipart/form-data" class="mb-3">
                        <div class="mb-2">
                            <label for="admin_profile_pic" class="form-label btn btn-sm btn-outline-secondary px-3">
                                <i class="fas fa-camera me-1"></i> Update Photo
                            </label>
                            <input type="file" id="admin_profile_pic" name="profile_pic" accept="image/*" class="d-none" onchange="this.form.submit()">
                        </div>
                        <input type="hidden" name="upload_photo" value="1">
                    </form>
                    <h4 class="font-weight-bold text-gray-800"><?php echo htmlspecialchars($admin['name']); ?></h4>
                    <p class="text-muted small mb-3">System Administrator</p>
                    <span class="badge bg-primary px-3 py-2">Full Permissions</span>
                    <hr class="my-4">
                    <div class="text-start px-3">
                        <p class="mb-2 text-muted"><i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($admin['email']); ?></p>
                        <p class="mb-0 text-muted"><i class="fas fa-phone me-2"></i> <?php echo htmlspecialchars($admin['phone']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Profile Form -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Update Profile Details</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="profile.php">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label font-weight-bold small text-muted">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($admin['name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label font-weight-bold small text-muted">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($admin['phone']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="email" class="form-label font-weight-bold small text-muted">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                        </div>

                        <hr class="my-4">
                        
                        <h6 class="font-weight-bold text-primary mb-3">Change Password (Leave blank to keep current password)</h6>
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label font-weight-bold small text-muted">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="new_password" class="form-label font-weight-bold small text-muted">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label font-weight-bold small text-muted">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                        
                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary px-4 fw-bold">
                                <i class="fas fa-save me-2"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'admin_footer.php';
?>
