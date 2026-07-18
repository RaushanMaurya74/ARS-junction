<?php
$page_title = "Manage Restaurants";
require_once 'admin_header.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploaded_image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            // Compress and encode to base64 (800x400 banner dimensions)
            $uploaded_image = get_compressed_base64_image($_FILES['image']['tmp_name'], $ext, 800, 400);
        }
    }

    if (isset($_POST['add_restaurant'])) {
        if (!empty($uploaded_image)) {
            $_POST['image'] = $uploaded_image;
        }
        $result = admin_add_restaurant($_POST);
        $success_msg = $result['success'] ? 'Restaurant added successfully.' : '';
        if (!$result['success']) $error_msg = $result['message'];
    }

    if (isset($_POST['update_restaurant'])) {
        if (!empty($uploaded_image)) {
            $_POST['image'] = $uploaded_image;
            $old_res = get_restaurant_by_id((int)$_POST['restaurant_id']);
            if ($old_res && !empty($old_res['image']) && file_exists('../' . $old_res['image'])) {
                @unlink('../' . $old_res['image']);
            }
        }
        $result = admin_update_restaurant((int)$_POST['restaurant_id'], $_POST);
        $success_msg = $result['success'] ? 'Restaurant updated successfully.' : '';
        if (!$result['success']) $error_msg = $result['message'];
    }

    if (isset($_POST['delete_restaurant'])) {
        $old_res = get_restaurant_by_id((int)$_POST['restaurant_id']);
        if ($old_res && !empty($old_res['image']) && file_exists('../' . $old_res['image'])) {
            @unlink('../' . $old_res['image']);
        }
        $result = admin_delete_restaurant((int)$_POST['restaurant_id']);
        $success_msg = $result['success'] ? 'Restaurant deleted successfully.' : '';
        if (!$result['success']) $error_msg = $result['message'];
    }

    if (isset($_POST['toggle_status'])) {
        $res_id = (int)$_POST['restaurant_id'];
        $new_status = $_POST['current_status'] == '1' ? 0 : 1;
        
        global $conn;
        $stmt = $conn->prepare("UPDATE restaurants SET is_active = :is_active WHERE restaurant_id = :id");
        $stmt->bindValue(':is_active', $new_status, PDO::PARAM_INT);
        $stmt->bindValue(':id', $res_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $success_msg = 'Restaurant status updated successfully.';
    }
}

$restaurants = admin_get_all_restaurants(200, 0);

// Apply search filter if query is set
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = strtolower(clean_input($_GET['search']));
    $filtered = [];
    foreach ($restaurants as $r) {
        if (stripos($r['name'], $search) !== false ||
            stripos($r['description'], $search) !== false ||
            stripos($r['city'], $search) !== false ||
            stripos($r['phone'], $search) !== false) {
            $filtered[] = $r;
        }
    }
    $restaurants = $filtered;
}
$edit_restaurant = null;
if (isset($_GET['edit'])) {
    $edit_restaurant = get_restaurant_by_id((int)$_GET['edit']);
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Restaurants</h1>
        <a href="restaurants.php" class="btn btn-primary btn-sm">Add / Reset Form</a>
    </div>

    <?php if ($success_msg): ?><div class="alert alert-success"><?php echo $success_msg; ?></div><?php endif; ?>
    <?php if ($error_msg): ?><div class="alert alert-danger"><?php echo $error_msg; ?></div><?php endif; ?>

    <div class="row">
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><?php echo $edit_restaurant ? 'Edit Restaurant' : 'Add Restaurant'; ?></h6>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <?php if ($edit_restaurant): ?>
                            <input type="hidden" name="restaurant_id" value="<?php echo $edit_restaurant['restaurant_id']; ?>">
                        <?php endif; ?>
                        <div class="mb-2"><label class="form-label">Name</label><input class="form-control" name="name" value="<?php echo $edit_restaurant['name'] ?? ''; ?>" required></div>
                        <div class="mb-2"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"><?php echo $edit_restaurant['description'] ?? ''; ?></textarea></div>
                        
                        <div class="mb-3">
                            <label class="form-label">Restaurant Image Banner</label>
                            <?php 
                            if ($edit_restaurant && !empty($edit_restaurant['image'])): 
                                $edit_image_url = get_image_url($edit_restaurant['image'], true);
                                if (!empty($edit_image_url)):
                            ?>
                                <div class="mb-2">
                                    <img src="<?php echo $edit_image_url; ?>" class="img-thumbnail" style="max-height: 100px;">
                                </div>
                            <?php 
                                endif;
                            endif; 
                            ?>
                            <input class="form-control" type="file" name="image" accept="image/*">
                        </div>

                        <div class="mb-2"><label class="form-label">Address</label><input class="form-control" name="address" value="<?php echo $edit_restaurant['address'] ?? ''; ?>" required></div>
                        <div class="row">
                            <div class="col-md-4 mb-2"><label class="form-label">City</label><input class="form-control" name="city" value="<?php echo $edit_restaurant['city'] ?? 'Piro'; ?>" required></div>
                            <div class="col-md-4 mb-2"><label class="form-label">State</label><input class="form-control" name="state" value="<?php echo $edit_restaurant['state'] ?? 'Bihar'; ?>" required></div>
                            <div class="col-md-4 mb-2"><label class="form-label">Zip</label><input class="form-control" name="zip_code" value="<?php echo $edit_restaurant['zip_code'] ?? '802207'; ?>" required></div>
                        </div>
                        <div class="mb-2"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?php echo $edit_restaurant['phone'] ?? ''; ?>" required></div>
                        <div class="mb-2"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="<?php echo $edit_restaurant['email'] ?? ''; ?>"></div>
                        <div class="row">
                            <div class="col-md-4 mb-2"><label class="form-label">Delivery mins</label><input class="form-control" type="number" name="delivery_time" value="<?php echo $edit_restaurant['delivery_time'] ?? '30'; ?>" required></div>
                            <div class="col-md-4 mb-2"><label class="form-label">Fee</label><input class="form-control" type="number" step="0.01" name="delivery_fee" value="<?php echo $edit_restaurant['delivery_fee'] ?? '20.00'; ?>" required></div>
                            <div class="col-md-4 mb-2"><label class="form-label">Min Order</label><input class="form-control" type="number" step="0.01" name="minimum_order" value="<?php echo $edit_restaurant['minimum_order'] ?? '100.00'; ?>" required></div>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?php echo (!$edit_restaurant || $edit_restaurant['is_active']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">Active / Open</label>
                        </div>
                        <button class="btn btn-primary w-100" name="<?php echo $edit_restaurant ? 'update_restaurant' : 'add_restaurant'; ?>" type="submit">
                            <?php echo $edit_restaurant ? 'Update Restaurant' : 'Add Restaurant'; ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-light"><h6 class="mb-0">All Restaurants</h6></div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead><tr><th>Name</th><th>City</th><th>Rating</th><th>Status</th><th>Delivery</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($restaurants as $restaurant): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $res_image_url = get_image_url($restaurant['image'], true);
                                    if (!empty($res_image_url)): 
                                    ?>
                                        <img src="<?php echo $res_image_url; ?>" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover; vertical-align: middle;">
                                    <?php else: ?>
                                        <span class="d-inline-block rounded me-2 bg-light text-center" style="width: 40px; height: 40px; line-height: 40px; vertical-align: middle;">
                                            <i class="fas fa-store text-muted"></i>
                                        </span>
                                    <?php endif; ?>
                                    <?php echo $restaurant['name']; ?>
                                </td>
                                <td><?php echo $restaurant['city']; ?></td>
                                <td><?php echo number_format((float)$restaurant['avg_rating'], 1); ?> (<?php echo $restaurant['review_count']; ?>)</td>
                                <td>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="restaurant_id" value="<?php echo $restaurant['restaurant_id']; ?>">
                                        <input type="hidden" name="toggle_status" value="1">
                                        <input type="hidden" name="current_status" value="<?php echo $restaurant['is_active']; ?>">
                                        <button type="submit" class="btn btn-sm btn-<?php echo $restaurant['is_active'] ? 'success' : 'danger'; ?>" style="font-size: 0.75rem; font-weight: 600; min-width: 90px;" title="Click to toggle status">
                                            <i class="fas <?php echo $restaurant['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?> me-1"></i>
                                            <?php echo $restaurant['is_active'] ? 'Open (ON)' : 'Closed (OFF)'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td><?php echo $restaurant['delivery_time']; ?> mins / <?php echo format_price($restaurant['delivery_fee']); ?></td>
                                <td>
                                    <a class="btn btn-sm btn-info" href="restaurants.php?edit=<?php echo $restaurant['restaurant_id']; ?>">Edit</a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this restaurant?');">
                                        <input type="hidden" name="restaurant_id" value="<?php echo $restaurant['restaurant_id']; ?>">
                                        <button class="btn btn-sm btn-danger" name="delete_restaurant" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>
