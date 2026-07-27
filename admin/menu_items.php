<?php
$page_title = "Manage Menu Items";
require_once 'admin_header.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploaded_image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            require_once '../includes/functions.php';
            // Compress and encode to base64 (400x300 dimensions)
            $uploaded_image = get_compressed_base64_image($_FILES['image']['tmp_name'], $ext, 400, 300);
        }
    }

    if (isset($_POST['add_menu_item'])) {
        if (!empty($uploaded_image)) {
            $_POST['image'] = $uploaded_image;
        }
        $result = admin_add_menu_item($_POST);
        $success_msg = $result['success'] ? 'Menu item added successfully.' : '';
        if (!$result['success']) $error_msg = $result['message'];
    }

    if (isset($_POST['update_menu_item'])) {
        if (!empty($uploaded_image)) {
            $_POST['image'] = $uploaded_image;
            $old_item = get_menu_item_by_id((int)$_POST['item_id']);
            if ($old_item && !empty($old_item['image']) && file_exists('../' . $old_item['image'])) {
                @unlink('../' . $old_item['image']);
            }
        }
        $result = admin_update_menu_item((int)$_POST['item_id'], $_POST);
        $success_msg = $result['success'] ? 'Menu item updated successfully.' : '';
        if (!$result['success']) $error_msg = $result['message'];
    }

    if (isset($_POST['delete_menu_item'])) {
        $old_item = get_menu_item_by_id((int)$_POST['item_id']);
        if ($old_item && !empty($old_item['image']) && file_exists('../' . $old_item['image'])) {
            @unlink('../' . $old_item['image']);
        }
        $result = admin_delete_menu_item((int)$_POST['item_id']);
        $success_msg = $result['success'] ? 'Menu item deleted successfully.' : '';
        if (!$result['success']) $error_msg = $result['message'];
    }
}

$restaurants = admin_get_all_restaurants(500, 0);
$categories = get_all_categories();
$items = admin_get_all_menu_items(500, 0);

// Apply search filter if query is set
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = strtolower(clean_input($_GET['search']));
    $filtered = [];
    foreach ($items as $item) {
        if (stripos($item['name'], $search) !== false ||
            stripos($item['description'], $search) !== false ||
            stripos($item['restaurant_name'], $search) !== false ||
            stripos($item['category_name'], $search) !== false) {
            $filtered[] = $item;
        }
    }
    $items = $filtered;
}
$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_item = get_menu_item_by_id((int)$_GET['edit']);
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Menu Items</h1>
        <a href="menu_items.php" class="btn btn-primary btn-sm">Add / Reset Form</a>
    </div>

    <?php if ($success_msg): ?><div class="alert alert-success"><?php echo $success_msg; ?></div><?php endif; ?>
    <?php if ($error_msg): ?><div class="alert alert-danger"><?php echo $error_msg; ?></div><?php endif; ?>

    <div class="row">
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header bg-light"><h6 class="mb-0"><?php echo $edit_item ? 'Edit Menu Item' : 'Add Menu Item'; ?></h6></div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <?php if ($edit_item): ?><input type="hidden" name="item_id" value="<?php echo $edit_item['item_id']; ?>"><?php endif; ?>
                        <div class="mb-2">
                            <label class="form-label">Restaurant</label>
                            <select class="form-select" name="restaurant_id" required>
                                <?php foreach ($restaurants as $restaurant): ?>
                                <option value="<?php echo $restaurant['restaurant_id']; ?>" <?php echo (($edit_item['restaurant_id'] ?? '') == $restaurant['restaurant_id']) ? 'selected' : ''; ?>><?php echo $restaurant['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id" required>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>" <?php echo (($edit_item['category_id'] ?? '') == $category['category_id']) ? 'selected' : ''; ?>><?php echo $category['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2"><label class="form-label">Name</label><input class="form-control" name="name" value="<?php echo $edit_item['name'] ?? ''; ?>" required></div>
                        <div class="mb-2"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"><?php echo $edit_item['description'] ?? ''; ?></textarea></div>
                        
                        <div class="mb-3">
                            <label class="form-label">Food Image</label>
                            <?php if ($edit_item && !empty($edit_item['image'])): ?>
                                <div class="mb-2">
                                    <img src="../<?php echo $edit_item['image']; ?>" class="img-thumbnail" style="max-height: 100px;">
                                </div>
                            <?php endif; ?>
                            <input class="form-control" type="file" name="image" accept="image/*">
                        </div>

                        <div class="mb-3"><label class="form-label">Price</label><input class="form-control" type="number" step="0.01" name="price" value="<?php echo $edit_item['price'] ?? ''; ?>" required></div>
                        <div class="mb-3">
                            <label class="form-label d-block">Food Type</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="is_vegetarian" id="veg" value="1" <?php echo (!$edit_item || !empty($edit_item['is_vegetarian'])) ? 'checked' : ''; ?>>
                                <label class="form-check-label text-success fw-semibold" for="veg"><i class="fas fa-leaf me-1"></i> Vegetarian (Veg)</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="is_vegetarian" id="non_veg" value="0" <?php echo ($edit_item && empty($edit_item['is_vegetarian'])) ? 'checked' : ''; ?>>
                                <label class="form-check-label text-danger fw-semibold" for="non_veg"><i class="fas fa-drumstick-bite me-1"></i> Non-Vegetarian (Non-Veg)</label>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-4"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_spicy" id="spicy" <?php echo (!empty($edit_item['is_spicy'])) ? 'checked' : ''; ?>><label class="form-check-label" for="spicy">Spicy</label></div></div>
                            <div class="col-4"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_available" id="available" <?php echo (!$edit_item || !empty($edit_item['is_available'])) ? 'checked' : ''; ?>><label class="form-check-label" for="available">Available</label></div></div>
                            <div class="col-4"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_featured" id="featured" <?php echo (!empty($edit_item['is_featured'])) ? 'checked' : ''; ?>><label class="form-check-label" for="featured">Featured</label></div></div>
                        </div>
                        <button class="btn btn-primary w-100" name="<?php echo $edit_item ? 'update_menu_item' : 'add_menu_item'; ?>" type="submit">
                            <?php echo $edit_item ? 'Update Item' : 'Add Item'; ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-light"><h6 class="mb-0">All Menu Items</h6></div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead><tr><th>Name</th><th>Restaurant</th><th>Category</th><th>Price</th><th>Flags</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <?php if (has_image($item['image'], true)): ?>
                                        <img src="<?php echo get_image_url($item['image'], true); ?>" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover; vertical-align: middle;">
                                    <?php else: ?>
                                        <span class="d-inline-block rounded me-2 bg-light text-center" style="width: 40px; height: 40px; line-height: 40px; vertical-align: middle;">
                                            <i class="fas fa-utensils text-muted"></i>
                                        </span>
                                    <?php endif; ?>
                                    <?php echo $item['name']; ?>
                                </td>
                                <td><?php echo $item['restaurant_name']; ?></td>
                                <td><?php echo $item['category_name']; ?></td>
                                <td><?php echo format_price($item['price']); ?></td>
                                <td>
                                    <?php if ($item['is_vegetarian']): ?><span class="badge bg-success">Veg</span><?php endif; ?>
                                    <?php if ($item['is_spicy']): ?><span class="badge bg-danger">Spicy</span><?php endif; ?>
                                    <?php if ($item['is_featured']): ?><span class="badge bg-warning text-dark">Featured</span><?php endif; ?>
                                    <span class="badge bg-<?php echo $item['is_available'] ? 'primary' : 'secondary'; ?>"><?php echo $item['is_available'] ? 'Available' : 'Hidden'; ?></span>
                                </td>
                                <td>
                                    <a class="btn btn-sm btn-info" href="menu_items.php?edit=<?php echo $item['item_id']; ?>">Edit</a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this item?');">
                                        <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                        <button class="btn btn-sm btn-danger" name="delete_menu_item" type="submit">Delete</button>
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
