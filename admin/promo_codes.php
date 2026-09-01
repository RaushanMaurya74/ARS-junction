<?php
$page_title = "Manage Promo Codes";
require_once 'admin_header.php';

$success_msg = '';
$error_msg = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add promo code
    if (isset($_POST['add_promo'])) {
        $code = strtoupper(clean_input($_POST['code']));
        $discount_type = clean_input($_POST['discount_type']);
        $discount_value = floatval($_POST['discount_value']);
        $min_order_amount = floatval($_POST['min_order_amount']);
        $max_discount_amount = !empty($_POST['max_discount_amount']) ? floatval($_POST['max_discount_amount']) : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($code) || empty($discount_type) || $discount_value <= 0) {
            $error_msg = "Please fill in all required fields and ensure discount value is positive.";
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO promo_codes (code, discount_type, discount_value, min_order_amount, max_discount_amount, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$code, $discount_type, $discount_value, $min_order_amount, $max_discount_amount, $is_active]);
                $success_msg = "Promo code '{$code}' added successfully.";
            } catch (PDOException $e) {
                if (in_array($e->getCode(), ['23000', '23505'])) {
                    $error_msg = "Promo code '{$code}' already exists.";
                } else {
                    error_log("Error adding promo code: " . $e->getMessage());
                    $error_msg = "Error adding promo code. Please try again.";
                }
            }
        }
    }

    // Edit/Update promo code
    if (isset($_POST['edit_promo'])) {
        $promo_id = intval($_POST['promo_id']);
        $code = strtoupper(clean_input($_POST['code']));
        $discount_type = clean_input($_POST['discount_type']);
        $discount_value = floatval($_POST['discount_value']);
        $min_order_amount = floatval($_POST['min_order_amount']);
        $max_discount_amount = !empty($_POST['max_discount_amount']) ? floatval($_POST['max_discount_amount']) : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($code) || empty($discount_type) || $discount_value <= 0) {
            $error_msg = "Please fill in all required fields.";
        } else {
            try {
                $stmt = $conn->prepare("UPDATE promo_codes SET code = ?, discount_type = ?, discount_value = ?, min_order_amount = ?, max_discount_amount = ?, is_active = ? WHERE promo_id = ?");
                $stmt->execute([$code, $discount_type, $discount_value, $min_order_amount, $max_discount_amount, $is_active, $promo_id]);
                $success_msg = "Promo code '{$code}' updated successfully.";
            } catch (PDOException $e) {
                if (in_array($e->getCode(), ['23000', '23505'])) {
                    $error_msg = "Promo code '{$code}' already exists on another record.";
                } else {
                    error_log("Error updating promo code: " . $e->getMessage());
                    $error_msg = "Error updating promo code. Please try again.";
                }
            }
        }
    }

    // Delete promo code
    if (isset($_POST['delete_promo'])) {
        $promo_id = intval($_POST['promo_id']);
        try {
            $stmt = $conn->prepare("DELETE FROM promo_codes WHERE promo_id = ?");
            $stmt->execute([$promo_id]);
            $success_msg = "Promo code deleted successfully.";
        } catch (PDOException $e) {
            error_log("Error deleting promo code: " . $e->getMessage());
            $error_msg = "Error deleting promo code.";
        }
    }

    // Toggle active status
    if (isset($_POST['toggle_status'])) {
        $promo_id = intval($_POST['promo_id']);
        $current_status = intval($_POST['current_status']);
        $new_status = $current_status === 1 ? 0 : 1;
        try {
            $stmt = $conn->prepare("UPDATE promo_codes SET is_active = ? WHERE promo_id = ?");
            $stmt->execute([$new_status, $promo_id]);
            $success_msg = "Promo code status updated successfully.";
        } catch (PDOException $e) {
            error_log("Error updating status: " . $e->getMessage());
            $error_msg = "Error updating status.";
        }
    }
}

// Search filter
$search_query = "";
$params = [];
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = clean_input($_GET['search']);
    $search_query = " WHERE code LIKE ? ";
    $params[] = "%" . $search . "%";
}

// Fetch all promo codes
$stmt = $conn->prepare("SELECT * FROM promo_codes" . $search_query . " ORDER BY code ASC");
$stmt->execute($params);
$promo_codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get edit promo details if requested
$edit_promo = null;
if (isset($_GET['edit'])) {
    $promo_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM promo_codes WHERE promo_id = ?");
    $stmt->execute([$promo_id]);
    $edit_promo = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-ticket-alt text-primary me-2"></i>Manage Promo Codes</h1>
        <a href="promo_codes.php" class="btn btn-primary btn-sm">Add / Reset Form</a>
    </div>

    <?php if ($success_msg): ?><div class="alert alert-success"><?php echo $success_msg; ?></div><?php endif; ?>
    <?php if ($error_msg): ?><div class="alert alert-danger"><?php echo $error_msg; ?></div><?php endif; ?>

    <div class="row">
        <!-- Add / Edit Promo Code Form -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0 font-weight-bold text-primary"><?php echo $edit_promo ? 'Edit Promo Code' : 'Add New Promo Code'; ?></h6>
                </div>
                <div class="card-body">
                    <form method="post">
                        <?php if ($edit_promo): ?>
                            <input type="hidden" name="promo_id" value="<?php echo $edit_promo['promo_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label font-weight-bold small text-muted">Promo Code</label>
                            <input type="text" class="form-control" name="code" value="<?php echo $edit_promo['code'] ?? ''; ?>" placeholder="e.g. SAVE20" required <?php echo $edit_promo ? 'readonly' : ''; ?>>
                            <div class="form-text small">Users enter this code to apply discounts. Case-insensitive.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-weight-bold small text-muted">Discount Type</label>
                            <select class="form-select" name="discount_type" id="discount_type" required>
                                <option value="percentage" <?php echo (isset($edit_promo) && $edit_promo['discount_type'] === 'percentage') ? 'selected' : ''; ?>>Percentage (%)</option>
                                <option value="fixed" <?php echo (isset($edit_promo) && $edit_promo['discount_type'] === 'fixed') ? 'selected' : ''; ?>>Fixed Amount (₹)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-weight-bold small text-muted">Discount Value</label>
                            <input type="number" step="0.01" class="form-control" name="discount_value" value="<?php echo $edit_promo['discount_value'] ?? ''; ?>" placeholder="e.g. 10 or 50" required>
                            <div class="form-text small">Enter percentage (e.g. 10 for 10%) or fixed value in ₹.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-weight-bold small text-muted">Minimum Order Amount (₹)</label>
                            <input type="number" step="0.01" class="form-control" name="min_order_amount" value="<?php echo $edit_promo['min_order_amount'] ?? '0.00'; ?>" required>
                            <div class="form-text small">Minimum subtotal required to apply this coupon.</div>
                        </div>

                        <div class="mb-3" id="max_discount_container">
                            <label class="form-label font-weight-bold small text-muted">Maximum Discount (₹)</label>
                            <input type="number" step="0.01" class="form-control" name="max_discount_amount" value="<?php echo $edit_promo['max_discount_amount'] ?? ''; ?>" placeholder="Optional (e.g. 100)">
                            <div class="form-text small">Only applies to Percentage type. Caps the maximum discount.</div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?php echo (!isset($edit_promo) || $edit_promo['is_active']) ? 'checked' : ''; ?>>
                            <label class="form-check-label small" for="is_active">
                                Active / Redeemable
                            </label>
                        </div>

                        <button class="btn btn-primary w-100" name="<?php echo $edit_promo ? 'edit_promo' : 'add_promo'; ?>" type="submit">
                            <i class="fas <?php echo $edit_promo ? 'fa-save' : 'fa-plus-circle'; ?> me-1"></i>
                            <?php echo $edit_promo ? 'Update Promo Code' : 'Create Promo Code'; ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- All Promo Codes List -->
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-light">
                    <h6 class="mb-0 font-weight-bold text-primary">All Promo Codes</h6>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th>Code</th>
                                <th>Discount</th>
                                <th>Min Order</th>
                                <th>Max Discount</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($promo_codes)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">No promo codes found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($promo_codes as $promo): ?>
                                    <tr>
                                        <td class="fw-bold text-primary"><?php echo htmlspecialchars($promo['code']); ?></td>
                                        <td>
                                            <?php 
                                            if ($promo['discount_type'] === 'percentage') {
                                                echo htmlspecialchars(floatval($promo['discount_value'])) . '%';
                                            } else {
                                                echo '₹' . number_format($promo['discount_value'], 2);
                                            }
                                            ?>
                                        </td>
                                        <td>₹<?php echo number_format($promo['min_order_amount'], 2); ?></td>
                                        <td>
                                            <?php 
                                            echo $promo['max_discount_amount'] ? '₹' . number_format($promo['max_discount_amount'], 2) : '-';
                                            ?>
                                        </td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="promo_id" value="<?php echo $promo['promo_id']; ?>">
                                                <input type="hidden" name="toggle_status" value="1">
                                                <input type="hidden" name="current_status" value="<?php echo $promo['is_active']; ?>">
                                                <button type="submit" class="btn btn-sm btn-<?php echo $promo['is_active'] ? 'success' : 'danger'; ?>" style="font-size: 0.75rem; font-weight: 600; min-width: 90px;">
                                                    <i class="fas <?php echo $promo['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?> me-1"></i>
                                                    <?php echo $promo['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="text-center">
                                            <a class="btn btn-sm btn-outline-info me-1" href="promo_codes.php?edit=<?php echo $promo['promo_id']; ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete promo code: <?php echo htmlspecialchars($promo['code']); ?>?');">
                                                <input type="hidden" name="promo_id" value="<?php echo $promo['promo_id']; ?>">
                                                <button class="btn btn-sm btn-outline-danger" name="delete_promo" type="submit">
                                                    <i class="fas fa-trash-alt"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const typeSelect = document.getElementById("discount_type");
    const maxDiscountContainer = document.getElementById("max_discount_container");

    function toggleMaxDiscount() {
        if (typeSelect.value === "percentage") {
            maxDiscountContainer.style.display = "block";
        } else {
            maxDiscountContainer.style.display = "none";
        }
    }

    typeSelect.addEventListener("change", toggleMaxDiscount);
    toggleMaxDiscount(); // run on load
});
</script>

<?php require_once 'admin_footer.php'; ?>
