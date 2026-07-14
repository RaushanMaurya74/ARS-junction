<?php
$page_title = "Manage Delivery Locations";
require_once 'admin_header.php';

$success_msg = '';
$error_msg = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add location
    if (isset($_POST['add_location'])) {
        $pincode = clean_input($_POST['pincode']);
        $area_name = clean_input($_POST['area_name']);
        $delivery_charge = floatval($_POST['delivery_charge']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($pincode) || empty($area_name)) {
            $error_msg = "Please fill in all required fields.";
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO delivery_pincodes (pincode, area_name, delivery_charge, is_active) VALUES (?, ?, ?, ?)");
                $stmt->execute([$pincode, $area_name, $delivery_charge, $is_active]);
                $success_msg = "Delivery location added successfully.";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error_msg = "Pincode '{$pincode}' already exists.";
                } else {
                    $error_msg = "Error adding delivery location: " . $e->getMessage();
                }
            }
        }
    }

    // Edit/Update location
    if (isset($_POST['edit_location'])) {
        $pincode_id = intval($_POST['pincode_id']);
        $pincode = clean_input($_POST['pincode']);
        $area_name = clean_input($_POST['area_name']);
        $delivery_charge = floatval($_POST['delivery_charge']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($pincode) || empty($area_name)) {
            $error_msg = "Please fill in all required fields.";
        } else {
            try {
                $stmt = $conn->prepare("UPDATE delivery_pincodes SET pincode = ?, area_name = ?, delivery_charge = ?, is_active = ? WHERE pincode_id = ?");
                $stmt->execute([$pincode, $area_name, $delivery_charge, $is_active, $pincode_id]);
                $success_msg = "Delivery location updated successfully.";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error_msg = "Pincode '{$pincode}' already exists on another location.";
                } else {
                    $error_msg = "Error updating location: " . $e->getMessage();
                }
            }
        }
    }

    // Delete location
    if (isset($_POST['delete_location'])) {
        $pincode_id = intval($_POST['pincode_id']);
        try {
            $stmt = $conn->prepare("DELETE FROM delivery_pincodes WHERE pincode_id = ?");
            $stmt->execute([$pincode_id]);
            $success_msg = "Delivery location deleted successfully.";
        } catch (PDOException $e) {
            $error_msg = "Error deleting location: " . $e->getMessage();
        }
    }

    // Toggle active status
    if (isset($_POST['toggle_status'])) {
        $pincode_id = intval($_POST['pincode_id']);
        $current_status = intval($_POST['current_status']);
        $new_status = $current_status === 1 ? 0 : 1;
        try {
            $stmt = $conn->prepare("UPDATE delivery_pincodes SET is_active = ? WHERE pincode_id = ?");
            $stmt->execute([$new_status, $pincode_id]);
            $success_msg = "Location status toggled successfully.";
        } catch (PDOException $e) {
            $error_msg = "Error toggling status: " . $e->getMessage();
        }
    }
}

// Fetch all delivery pincodes
$stmt = $conn->prepare("SELECT * FROM delivery_pincodes ORDER BY pincode ASC");
$stmt->execute();
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-map-marker-alt text-primary me-2"></i>Manage Delivery Areas (Pincodes)</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLocationModal">
            <i class="fas fa-plus-circle me-1"></i> Add New Area
        </button>
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

    <div class="card shadow">
        <div class="card-header bg-light">
            <h6 class="mb-0 font-weight-bold text-primary">All Serviced Pincodes and Delivery Charges</h6>
        </div>
        <div class="card-body table-responsive">
            <?php if (empty($locations)): ?>
                <p class="text-center py-3 text-muted">No delivery areas configured yet. Click "Add New Area" to get started.</p>
            <?php else: ?>
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Pincode</th>
                            <th>Area/Location Name</th>
                            <th>Delivery Charge</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($locations as $loc): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($loc['pincode']); ?></td>
                                <td><?php echo htmlspecialchars($loc['area_name']); ?></td>
                                <td><?php echo format_price($loc['delivery_charge']); ?></td>
                                <td>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="pincode_id" value="<?php echo $loc['pincode_id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $loc['is_active']; ?>">
                                        <button type="submit" name="toggle_status" class="btn btn-sm border-0 p-0">
                                            <span class="badge bg-<?php echo $loc['is_active'] ? 'success' : 'danger'; ?>" style="cursor: pointer;">
                                                <?php echo $loc['is_active'] ? 'Active / Delivering' : 'Inactive / Suspended'; ?>
                                            </span>
                                        </button>
                                    </form>
                                </td>
                                <td><?php echo date('M d, Y h:i A', strtotime($loc['created_at'])); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-info text-white me-1 edit-btn" 
                                            data-id="<?php echo $loc['pincode_id']; ?>"
                                            data-pincode="<?php echo htmlspecialchars($loc['pincode']); ?>"
                                            data-name="<?php echo htmlspecialchars($loc['area_name']); ?>"
                                            data-charge="<?php echo $loc['delivery_charge']; ?>"
                                            data-active="<?php echo $loc['is_active']; ?>"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editLocationModal">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    
                                    <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this delivery location? Any orders referencing this pincode will remain intact but new orders will not be possible.');">
                                        <input type="hidden" name="pincode_id" value="<?php echo $loc['pincode_id']; ?>">
                                        <button type="submit" name="delete_location" class="btn btn-sm btn-danger">
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

<!-- Add Location Modal -->
<div class="modal fade" id="addLocationModal" tabindex="-1" aria-labelledby="addLocationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addLocationModalLabel">Add Delivery Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_pincode" class="form-label">ZIP / Pincode <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="add_pincode" name="pincode" placeholder="e.g. 802207" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_area_name" class="form-label">Area / Location Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="add_area_name" name="area_name" placeholder="e.g. Piro Main Road" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_delivery_charge" class="form-label">Delivery Charge (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" id="add_delivery_charge" name="delivery_charge" value="0.00" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="add_is_active" name="is_active" checked>
                        <label class="form-check-label" for="add_is_active">Enable deliveries for this location</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_location" class="btn btn-primary">Add Area</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Location Modal -->
<div class="modal fade" id="editLocationModal" tabindex="-1" aria-labelledby="editLocationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editLocationModalLabel">Edit Delivery Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <input type="hidden" id="edit_id" name="pincode_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_pincode" class="form-label">ZIP / Pincode <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_pincode" name="pincode" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_area_name" class="form-label">Area / Location Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_area_name" name="area_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_delivery_charge" class="form-label">Delivery Charge (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" id="edit_delivery_charge" name="delivery_charge" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_is_active" name="is_active">
                        <label class="form-check-label" for="edit_is_active">Enable deliveries for this location</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="edit_location" class="btn btn-info text-white">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Populate Edit Modal
    const editButtons = document.querySelectorAll('.edit-btn');
    editButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const pincode = this.getAttribute('data-pincode');
            const name = this.getAttribute('data-name');
            const charge = this.getAttribute('data-charge');
            const active = this.getAttribute('data-active') === '1';

            document.getElementById('edit_id').value = id;
            document.getElementById('edit_pincode').value = pincode;
            document.getElementById('edit_area_name').value = name;
            document.getElementById('edit_delivery_charge').value = charge;
            document.getElementById('edit_is_active').checked = active;
        });
    });
});
</script>

<?php require_once 'admin_footer.php'; ?>
