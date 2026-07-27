<?php
/**
 * Restaurant Owner Portal - Supplier Management
 */

$page_title = "Suppliers Management";
require_once 'includes/restaurant_header.php';

$success_msg = '';
$error_msg = '';

// Handle actions (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $conn;
    
    // Add / Update Supplier
    if (isset($_POST['save_supplier'])) {
        try {
            $schema = [
                'supplier_id' => ['type' => 'int', 'default' => 0, 'min' => 0],
                'company_name' => ['type' => 'string', 'required' => true, 'min_len' => 2, 'max_len' => 100],
                'owner_name' => ['type' => 'string', 'required' => false, 'max_len' => 100],
                'phone' => ['type' => 'phone', 'required' => false],
                'email' => ['type' => 'email', 'required' => false, 'max_len' => 255],
                'address' => ['type' => 'string', 'required' => false, 'max_len' => 255],
                'gst_number' => ['type' => 'string', 'required' => false, 'max_len' => 15, 'regex' => '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i']
            ];

            $validated = Validator::validate($_POST, $schema, false);
            
            $supplier_id = $validated['supplier_id'];
            $company_name = $validated['company_name'];
            $owner_name = $validated['owner_name'];
            $phone = $validated['phone'];
            $email = $validated['email'];
            $address = $validated['address'];
            $gst_number = $validated['gst_number'];

            try {
                if ($supplier_id > 0) {
                    // Update
                    $stmt = $conn->prepare("UPDATE suppliers SET company_name = ?, owner_name = ?, phone = ?, email = ?, address = ?, gst_number = ?, updated_at = NOW() WHERE supplier_id = ? AND restaurant_id = ?");
                    if ($stmt->execute([$company_name, $owner_name, $phone, $email, $address, $gst_number, $supplier_id, $restaurant_id])) {
                        $success_msg = "Supplier updated successfully.";
                    } else {
                        $error_msg = "Failed to update supplier.";
                    }
                } else {
                    // Insert
                    $stmt = $conn->prepare("INSERT INTO suppliers (restaurant_id, company_name, owner_name, phone, email, address, gst_number) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt->execute([$restaurant_id, $company_name, $owner_name, $phone, $email, $address, $gst_number])) {
                        $success_msg = "Supplier added successfully.";
                    } else {
                        $error_msg = "Failed to add supplier.";
                    }
                }
            } catch (PDOException $e) {
                error_log("Supplier save error: " . $e->getMessage());
                $error_msg = "A database error occurred. Please try again later.";
            }
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $error_msg = reset($errors);
        }
    }
    
    // Delete Supplier
    if (isset($_POST['delete_supplier'])) {
        try {
            $schema = [
                'supplier_id' => ['type' => 'int', 'required' => true, 'min' => 1]
            ];
            $validated = Validator::validate($_POST, $schema, false);
            $supplier_id = $validated['supplier_id'];
            
            try {
                $stmt = $conn->prepare("DELETE FROM suppliers WHERE supplier_id = ? AND restaurant_id = ?");
                if ($stmt->execute([$supplier_id, $restaurant_id])) {
                    $success_msg = "Supplier deleted successfully.";
                } else {
                    $error_msg = "Failed to delete supplier.";
                }
            } catch (PDOException $e) {
                error_log("Supplier delete error: " . $e->getMessage());
                $error_msg = "A database error occurred. Please try again later.";
            }
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $error_msg = reset($errors);
        }
    }
    }
}

// Fetch all suppliers for this restaurant
global $conn;
try {
    $stmt = $conn->prepare("SELECT * FROM suppliers WHERE restaurant_id = ? ORDER BY company_name ASC");
    $stmt->execute([$restaurant_id]);
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $suppliers = [];
    error_log("Failed to fetch suppliers: " . $e->getMessage());
    $error_msg = "Failed to fetch suppliers.";
}

// Check if editing
$edit_supplier = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    foreach ($suppliers as $s) {
        if ($s['supplier_id'] === $edit_id) {
            $edit_supplier = $s;
            break;
        }
    }
}
?>

<div class="container-fluid animated-fade-in py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-slate-800 fw-bold">Suppliers Management</h1>
        <a href="suppliers.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-redo"></i> Refresh</a>
    </div>

    <!-- Alert Messages -->
    <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Add / Edit Form Panel -->
        <div class="col-xl-4 col-lg-5 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="m-0 fw-bold"><i class="fas <?php echo $edit_supplier ? 'fa-edit' : 'fa-plus-circle'; ?> me-2 text-warning"></i><?php echo $edit_supplier ? 'Edit Supplier' : 'Add New Supplier'; ?></h5>
                </div>
                <div class="card-body p-4">
                    <form method="post" action="suppliers.php">
                        <?php if ($edit_supplier): ?>
                            <input type="hidden" name="supplier_id" value="<?php echo $edit_supplier['supplier_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Company Name *</label>
                            <input type="text" name="company_name" class="form-control" required value="<?php echo $edit_supplier ? htmlspecialchars($edit_supplier['company_name']) : ''; ?>" placeholder="e.g. Fresh Veggies Ltd">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Contact Person</label>
                            <input type="text" name="owner_name" class="form-control" value="<?php echo $edit_supplier ? htmlspecialchars($edit_supplier['owner_name']) : ''; ?>" placeholder="e.g. Ramesh Kumar">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo $edit_supplier ? htmlspecialchars($edit_supplier['phone']) : ''; ?>" placeholder="e.g. +91 9876543210">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?php echo $edit_supplier ? htmlspecialchars($edit_supplier['email']) : ''; ?>" placeholder="e.g. supplier@example.com">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">GST Number</label>
                            <input type="text" name="gst_number" class="form-control text-uppercase" value="<?php echo $edit_supplier ? htmlspecialchars($edit_supplier['gst_number']) : ''; ?>" placeholder="e.g. 10AAAAA0000A1Z1">
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">Office Address</label>
                            <textarea name="address" class="form-control" rows="3" placeholder="Street, City, Zip Code"><?php echo $edit_supplier ? htmlspecialchars($edit_supplier['address']) : ''; ?></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="save_supplier" class="btn btn-primary btn-submit">
                                <i class="fas fa-save me-2"></i> <?php echo $edit_supplier ? 'Update Supplier' : 'Register Supplier'; ?>
                            </button>
                            <?php if ($edit_supplier): ?>
                                <a href="suppliers.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i> Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Suppliers List Grid Panel -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="m-0 fw-bold text-slate-800"><i class="fas fa-list me-2 text-primary"></i>Registered Suppliers</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <?php if (empty($suppliers)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-handshake fa-3x mb-3 text-slate-300"></i>
                                <p class="mb-0 fw-semibold">No suppliers registered yet.</p>
                                <p class="small">Add a supplier using the panel on the left to begin.</p>
                            </div>
                        <?php else: ?>
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="px-4">Company & Contact</th>
                                        <th>Details</th>
                                        <th>GSTIN</th>
                                        <th class="text-end px-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($suppliers as $s): ?>
                                        <tr>
                                            <td class="px-4">
                                                <div class="fw-bold text-slate-800"><?php echo htmlspecialchars($s['company_name']); ?></div>
                                                <?php if (!empty($s['owner_name'])): ?>
                                                    <div class="text-muted small"><i class="fas fa-user-tie me-1 text-slate-400"></i><?php echo htmlspecialchars($s['owner_name']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($s['phone'])): ?>
                                                    <div class="small"><i class="fas fa-phone me-1 text-slate-400"></i><?php echo htmlspecialchars($s['phone']); ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($s['email'])): ?>
                                                    <div class="small"><i class="fas fa-envelope me-1 text-slate-400"></i><?php echo htmlspecialchars($s['email']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <code class="text-uppercase"><?php echo !empty($s['gst_number']) ? htmlspecialchars($s['gst_number']) : 'N/A'; ?></code>
                                            </td>
                                            <td class="text-end px-4">
                                                <a href="suppliers.php?edit=<?php echo $s['supplier_id']; ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="post" action="suppliers.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this supplier? This may affect items referencing them.');">
                                                    <input type="hidden" name="supplier_id" value="<?php echo $s['supplier_id']; ?>">
                                                    <input type="hidden" name="delete_supplier" value="1">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                        <i class="fas fa-trash-alt"></i>
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
</div>

<?php require_once 'includes/restaurant_footer.php'; ?>
