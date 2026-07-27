<?php
/**
 * Restaurant Owner Portal - Menu Items Management
 */

$page_title = "Manage Menu Items";
require_once 'includes/restaurant_header.php';

$success_msg = '';
$error_msg = '';

// Handle actions: add, update, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploaded_image = '';
    
    // Process image to base64 if uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            // Compress and encode to base64
            require_once '../includes/functions.php';
            $uploaded_image = get_compressed_base64_image($_FILES['image']['tmp_name'], $ext, 400, 300);
        } else {
            $error_msg = 'Invalid image type. Only JPG, JPEG, PNG, and GIF are allowed.';
        }
    }

    if (empty($error_msg)) {
        global $conn;

        // Action: Add Menu Item
        if (isset($_POST['add_item'])) {
            try {
                $schema = [
                    'name' => ['type' => 'string', 'required' => true, 'min_len' => 2, 'max_len' => 100],
                    'description' => ['type' => 'string', 'default' => '', 'max_len' => 500],
                    'price' => ['type' => 'float', 'required' => true, 'min' => 0.01],
                    'category_id' => ['type' => 'int', 'required' => true, 'min' => 1],
                    'is_vegetarian' => ['type' => 'bool', 'default' => false],
                    'is_spicy' => ['type' => 'bool', 'default' => false],
                    'is_available' => ['type' => 'bool', 'default' => false],
                    'is_featured' => ['type' => 'bool', 'default' => false]
                ];

                $validated = Validator::validate($_POST, $schema, false);
                
                $name = $validated['name'];
                $description = $validated['description'];
                $price = $validated['price'];
                $category_id = $validated['category_id'];
                $is_vegetarian = $validated['is_vegetarian'] ? 1 : 0;
                $is_spicy = $validated['is_spicy'] ? 1 : 0;
                $is_available = $validated['is_available'] ? 1 : 0;
                $is_featured = $validated['is_featured'] ? 1 : 0;

                $stmt = $conn->prepare("INSERT INTO menu_items (restaurant_id, category_id, name, description, price, is_vegetarian, is_spicy, is_available, is_featured, image) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                if ($stmt->execute([$restaurant_id, $category_id, $name, $description, $price, $is_vegetarian, $is_spicy, $is_available, $is_featured, $uploaded_image])) {
                    $success_msg = 'Menu item added successfully.';
                } else {
                    $error_msg = 'Failed to add menu item.';
                }
            } catch (ValidationException $e) {
                $errors = $e->getErrors();
                $error_msg = reset($errors);
            }
        }

        // Action: Update Menu Item
        if (isset($_POST['update_item'])) {
            try {
                $schema = [
                    'item_id' => ['type' => 'int', 'required' => true, 'min' => 1],
                    'name' => ['type' => 'string', 'required' => true, 'min_len' => 2, 'max_len' => 100],
                    'description' => ['type' => 'string', 'default' => '', 'max_len' => 500],
                    'price' => ['type' => 'float', 'required' => true, 'min' => 0.01],
                    'category_id' => ['type' => 'int', 'required' => true, 'min' => 1],
                    'is_vegetarian' => ['type' => 'bool', 'default' => false],
                    'is_spicy' => ['type' => 'bool', 'default' => false],
                    'is_available' => ['type' => 'bool', 'default' => false],
                    'is_featured' => ['type' => 'bool', 'default' => false]
                ];

                $validated = Validator::validate($_POST, $schema, false);
                
                $item_id = $validated['item_id'];
                $name = $validated['name'];
                $description = $validated['description'];
                $price = $validated['price'];
                $category_id = $validated['category_id'];
                $is_vegetarian = $validated['is_vegetarian'] ? 1 : 0;
                $is_spicy = $validated['is_spicy'] ? 1 : 0;
                $is_available = $validated['is_available'] ? 1 : 0;
                $is_featured = $validated['is_featured'] ? 1 : 0;
                
                // Check ownership first
                $stmt_check = $conn->prepare("SELECT restaurant_id, image FROM menu_items WHERE item_id = ?");
                $stmt_check->execute([$item_id]);
                $existing_item = $stmt_check->fetch(PDO::FETCH_ASSOC);

                if ($existing_item && $existing_item['restaurant_id'] == $restaurant_id) {
                    $image_to_save = !empty($uploaded_image) ? $uploaded_image : $existing_item['image'];

                    $stmt = $conn->prepare("UPDATE menu_items 
                                           SET category_id = ?, name = ?, description = ?, price = ?, is_vegetarian = ?, is_spicy = ?, is_available = ?, is_featured = ?, image = ? 
                                           WHERE item_id = ? AND restaurant_id = ?");
                    
                    if ($stmt->execute([$category_id, $name, $description, $price, $is_vegetarian, $is_spicy, $is_available, $is_featured, $image_to_save, $item_id, $restaurant_id])) {
                        $success_msg = 'Menu item updated successfully.';
                    } else {
                        $error_msg = 'Failed to update menu item.';
                    }
                } else {
                    $error_msg = 'Access Denied: You do not own this menu item.';
                }
            } catch (ValidationException $e) {
                $errors = $e->getErrors();
                $error_msg = reset($errors);
            }
        }

        // Action: Delete Menu Item
        if (isset($_POST['delete_item'])) {
            try {
                $schema = [
                    'item_id' => ['type' => 'int', 'required' => true, 'min' => 1]
                ];
                $validated = Validator::validate($_POST, $schema, false);
                $item_id = $validated['item_id'];

                // Check ownership
                $stmt_check = $conn->prepare("SELECT restaurant_id FROM menu_items WHERE item_id = ?");
                $stmt_check->execute([$item_id]);
                $owner_id = $stmt_check->fetchColumn();

                if ($owner_id == $restaurant_id) {
                    $stmt = $conn->prepare("DELETE FROM menu_items WHERE item_id = ? AND restaurant_id = ?");
                    if ($stmt->execute([$item_id, $restaurant_id])) {
                        $success_msg = 'Menu item deleted successfully.';
                    } else {
                        $error_msg = 'Failed to delete menu item.';
                    }
                } else {
                    $error_msg = 'Access Denied: You do not own this menu item.';
                }
            } catch (ValidationException $e) {
                $errors = $e->getErrors();
                $error_msg = reset($errors);
            }
        }
    }
}

// Fetch all menu items for this restaurant
global $conn;
$stmt_items = $conn->prepare("SELECT m.*, c.name as category_name 
                            FROM menu_items m 
                            JOIN categories c ON m.category_id = c.category_id 
                            WHERE m.restaurant_id = ? 
                            ORDER BY m.name");
$stmt_items->execute([$restaurant_id]);
$menu_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories for forms
$categories = get_all_categories();

// Check if editing
$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt_edit = $conn->prepare("SELECT * FROM menu_items WHERE item_id = ? AND restaurant_id = ?");
    $stmt_edit->execute([$edit_id, $restaurant_id]);
    $edit_item = $stmt_edit->fetch(PDO::FETCH_ASSOC);
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
        <h1 class="h3 mb-0 text-gray-800">Menu Items</h1>
        <a href="menu_items.php" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i> Add / Reset Form</a>
    </div>

    <div class="row">
        <!-- Form Column (Add/Edit) -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light py-3">
                    <h6 class="m-0 fw-bold text-primary"><?php echo $edit_item ? 'Edit Menu Item' : 'Add New Menu Item'; ?></h6>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <?php if ($edit_item): ?>
                            <input type="hidden" name="item_id" value="<?php echo $edit_item['item_id']; ?>">
                            <input type="hidden" name="update_item" value="1">
                        <?php else: ?>
                            <input type="hidden" name="add_item" value="1">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Item Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($edit_item['name'] ?? ''); ?>" required placeholder="e.g. Garlic Bread">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Price (&#8377;) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="price" value="<?php echo htmlspecialchars($edit_item['price'] ?? ''); ?>" required placeholder="e.g. 149.00">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Category <span class="text-danger">*</span></label>
                            <select class="form-select" name="category_id" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>" <?php echo (isset($edit_item['category_id']) && $edit_item['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Describe the taste, ingredients, or size..."><?php echo htmlspecialchars($edit_item['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Item Banner Image</label>
                            <?php if ($edit_item && has_image($edit_item['image'], true)): ?>
                                <div class="mb-2">
                                    <img src="<?php echo get_image_url($edit_item['image'], true); ?>" class="img-thumbnail" style="max-height: 80px; object-fit: cover;">
                                </div>
                            <?php endif; ?>
                            <input class="form-control" type="file" name="image" accept="image/*">
                            <div class="form-text small">Image will be saved in cloud base64 format.</div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_vegetarian" id="is_vegetarian" <?php echo (!isset($edit_item) || (isset($edit_item['is_vegetarian']) && $edit_item['is_vegetarian'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label small" for="is_vegetarian">Vegetarian</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_spicy" id="is_spicy" <?php echo (isset($edit_item['is_spicy']) && $edit_item['is_spicy']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label small" for="is_spicy">Spicy</label>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_available" id="is_available" <?php echo (!isset($edit_item) || (isset($edit_item['is_available']) && $edit_item['is_available'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label small" for="is_available">In Stock / Available</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" <?php echo (isset($edit_item['is_featured']) && $edit_item['is_featured']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label small" for="is_featured">Featured Item</label>
                                </div>
                            </div>
                        </div>

                        <button class="btn btn-primary w-100 fw-bold" type="submit">
                            <?php echo $edit_item ? 'Update Menu Item' : 'Add Item to Menu'; ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Table Listing Column -->
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light py-3">
                    <h6 class="m-0 fw-bold text-primary">All Registered Menu Items</h6>
                </div>
                <div class="card-body p-0 table-responsive">
                    <?php if (empty($menu_items)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-utensils fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No items found in your restaurant menu yet.</p>
                        </div>
                    <?php else: ?>
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Item Details</th>
                                    <th>Category</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-center">Flags</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end px-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($menu_items as $item): ?>
                                    <tr>
                                        <td class="px-3">
                                            <div class="d-flex align-items-center">
                                                <?php if (has_image($item['image'], true)): ?>
                                                    <img src="<?php echo get_image_url($item['image'], true); ?>" class="rounded border me-2" style="width: 45px; height: 45px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="rounded border bg-light text-center me-2 d-flex align-items-center justify-content-center text-muted" style="width: 45px; height: 45px;">
                                                        <i class="fas fa-utensils"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($item['name']); ?></div>
                                                    <div class="text-muted small text-truncate" style="max-width: 180px;"><?php echo htmlspecialchars($item['description'] ?: 'No description'); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($item['category_name']); ?></span>
                                        </td>
                                        <td class="text-end fw-semibold">
                                            <?php echo format_price($item['price']); ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <?php if ($item['is_vegetarian']): ?>
                                                    <span class="badge bg-success" title="Vegetarian">Veg</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger" title="Non-Vegetarian">Non-Veg</span>
                                                <?php endif; ?>
                                                
                                                <?php if ($item['is_spicy']): ?>
                                                    <span class="badge bg-warning text-dark" title="Spicy"><i class="fas fa-pepper-hot"></i></span>
                                                <?php endif; ?>

                                                <?php if ($item['is_featured']): ?>
                                                    <span class="badge bg-info text-white" title="Featured"><i class="fas fa-star"></i></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-<?php echo $item['is_available'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $item['is_available'] ? 'Available' : 'Out of Stock'; ?>
                                            </span>
                                        </td>
                                        <td class="text-end px-4" style="white-space: nowrap;">
                                            <a href="menu_items.php?edit=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Delete this menu item?');">
                                                <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                                <input type="hidden" name="delete_item" value="1">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
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
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/restaurant_footer.php'; ?>
