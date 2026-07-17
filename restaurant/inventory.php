<?php
/**
 * Restaurant Owner Portal - Inventory ERP
 */

$page_title = "Inventory ERP";
require_once 'includes/restaurant_header.php';

$success_msg = '';
$error_msg = '';

// Handle forms
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $conn;

    // Add / Update Inventory Item
    if (isset($_POST['save_item'])) {
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
        $name = clean_input($_POST['name']);
        $sku = clean_input($_POST['sku']);
        $barcode = clean_input($_POST['barcode']);
        $purchase_price = floatval($_POST['purchase_price']);
        $selling_price = floatval($_POST['selling_price']);
        $unit = clean_input($_POST['unit']);
        $opening_stock = floatval($_POST['opening_stock']);
        $minimum_stock = floatval($_POST['minimum_stock']);
        $supplier_id = isset($_POST['supplier_id']) && intval($_POST['supplier_id']) > 0 ? intval($_POST['supplier_id']) : null;

        if (empty($name) || empty($unit)) {
            $error_msg = "Item name and unit are required.";
        } else {
            try {
                if ($item_id > 0) {
                    // Update
                    $stmt = $conn->prepare("UPDATE inventory_items SET name = ?, sku = ?, barcode = ?, purchase_price = ?, selling_price = ?, unit = ?, minimum_stock = ?, supplier_id = ?, updated_at = NOW() WHERE item_id = ? AND restaurant_id = ?");
                    if ($stmt->execute([$name, $sku, $barcode, $purchase_price, $selling_price, $unit, $minimum_stock, $supplier_id, $item_id, $restaurant_id])) {
                        $success_msg = "Inventory item updated successfully.";
                    } else {
                        $error_msg = "Failed to update item.";
                    }
                } else {
                    // Insert
                    $stmt = $conn->prepare("INSERT INTO inventory_items (restaurant_id, name, sku, barcode, purchase_price, selling_price, unit, opening_stock, current_stock, minimum_stock, supplier_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt->execute([$restaurant_id, $name, $sku, $barcode, $purchase_price, $selling_price, $unit, $opening_stock, $opening_stock, $minimum_stock, $supplier_id])) {
                        $new_item_id = $conn->lastInsertId();
                        
                        // Log initial transaction if opening stock > 0
                        if ($opening_stock > 0) {
                            $stmt_trans = $conn->prepare("INSERT INTO inventory_transactions (item_id, type, quantity, reference, remarks) VALUES (?, 'stock_in', ?, 'Opening Stock', 'Initial stock creation')");
                            $stmt_trans->execute([$new_item_id, $opening_stock]);
                        }
                        $success_msg = "Inventory item registered successfully.";
                    } else {
                        $error_msg = "Failed to add item.";
                    }
                }
            } catch (PDOException $e) {
                $error_msg = "Database error: " . $e->getMessage();
            }
        }
    }

    // Record Stock Adjustment
    if (isset($_POST['record_adjustment'])) {
        $item_id = intval($_POST['item_id']);
        $type = clean_input($_POST['adjustment_type']); // 'stock_in', 'stock_out', 'waste', 'adjustment'
        $quantity = floatval($_POST['quantity']);
        $remarks = clean_input($_POST['remarks']);

        if ($item_id <= 0 || $quantity <= 0 || !in_array($type, ['stock_in', 'stock_out', 'waste', 'adjustment'])) {
            $error_msg = "Invalid adjustment request params.";
        } else {
            try {
                $conn->beginTransaction();

                // Load current stock to check rules
                $stmt_load = $conn->prepare("SELECT current_stock, name FROM inventory_items WHERE item_id = ? AND restaurant_id = ? FOR UPDATE");
                $stmt_load->execute([$item_id, $restaurant_id]);
                $item = $stmt_load->fetch(PDO::FETCH_ASSOC);

                if ($item) {
                    $old_stock = floatval($item['current_stock']);
                    $new_stock = $old_stock;

                    if ($type === 'stock_in') {
                        $new_stock += $quantity;
                    } elseif ($type === 'stock_out' || $type === 'waste') {
                        $new_stock -= $quantity;
                    } elseif ($type === 'adjustment') {
                        // For generic manual correction, specify if adding or removing
                        $dir = clean_input($_POST['adj_direction'] ?? 'add');
                        if ($dir === 'add') {
                            $new_stock += $quantity;
                        } else {
                            $new_stock -= $quantity;
                            // Re-adjust type to match database standards
                            $quantity = -$quantity;
                        }
                    }

                    // Enforce no negative stock
                    if ($new_stock < 0) {
                        $conn->rollBack();
                        $error_msg = "Error: Stock level for '{$item['name']}' cannot fall below zero.";
                    } else {
                        // Update stock
                        $stmt_up = $conn->prepare("UPDATE inventory_items SET current_stock = ?, updated_at = NOW() WHERE item_id = ?");
                        $stmt_up->execute([$new_stock, $item_id]);

                        // Log transaction
                        $stmt_log = $conn->prepare("INSERT INTO inventory_transactions (item_id, type, quantity, reference, remarks) VALUES (?, ?, ?, 'Manual Adjustment', ?)");
                        $stmt_log->execute([$item_id, $type, $quantity, $remarks]);

                        $conn->commit();
                        $success_msg = "Stock adjustment for '{$item['name']}' registered successfully.";
                    }
                } else {
                    $conn->rollBack();
                    $error_msg = "Inventory item not found.";
                }
            } catch (PDOException $e) {
                $conn->rollBack();
                $error_msg = "Transaction failed: " . $e->getMessage();
            }
        }
    }
    
    // Delete Item
    if (isset($_POST['delete_item'])) {
        $item_id = intval($_POST['item_id']);
        try {
            $stmt = $conn->prepare("DELETE FROM inventory_items WHERE item_id = ? AND restaurant_id = ?");
            if ($stmt->execute([$item_id, $restaurant_id])) {
                $success_msg = "Item deleted from inventory.";
            } else {
                $error_msg = "Failed to delete item.";
            }
        } catch (PDOException $e) {
            $error_msg = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch all suppliers for options dropdown
global $conn;
try {
    $stmt = $conn->prepare("SELECT supplier_id, company_name FROM suppliers WHERE restaurant_id = ? ORDER BY company_name ASC");
    $stmt->execute([$restaurant_id]);
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $suppliers = [];
}

// Fetch inventory list
$items = [];
$total_value = 0.00;
$low_stock_count = 0;

try {
    $stmt = $conn->prepare("
        SELECT i.*, s.company_name as supplier_name 
        FROM inventory_items i
        LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id
        WHERE i.restaurant_id = ? 
        ORDER BY i.name ASC
    ");
    $stmt->execute([$restaurant_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        $total_value += ($item['current_stock'] * $item['purchase_price']);
        if ($item['current_stock'] <= $item['minimum_stock']) {
            $low_stock_count++;
        }
    }
} catch (PDOException $e) {
    $error_msg = "Failed to fetch inventory: " . $e->getMessage();
}

// Fetch recent stock ledger transactions (limit 15)
$transactions = [];
try {
    $stmt = $conn->prepare("
        SELECT t.*, i.name as item_name, i.unit 
        FROM inventory_transactions t
        JOIN inventory_items i ON t.item_id = i.item_id
        WHERE i.restaurant_id = ?
        ORDER BY t.created_at DESC
        LIMIT 15
    ");
    $stmt->execute([$restaurant_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Ignore ledger fetch failure
}
?>

<div class="container-fluid animated-fade-in py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-slate-800 fw-bold">Inventory ERP</h1>
        <div>
            <button class="btn btn-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#addItemModal"><i class="fas fa-plus"></i> Add Item</button>
            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#adjustmentModal"><i class="fas fa-sliders-h"></i> Adjust Stock</button>
        </div>
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

    <!-- KPI Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 border-left-primary py-2 h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Stock Value</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">₹<?php echo number_format($total_value, 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-coins fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 border-left-info py-2 h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">Total Ingredients/Items</div>
                            <div class="h5 mb-0 fw-bold text-gray-800"><?php echo count($items); ?> Items</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-carrot fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 border-left-danger py-2 h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs fw-bold text-danger text-uppercase mb-1">Low Stock Alerts</div>
                            <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $low_stock_count; ?> Alerts</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Stock Details Grid -->
        <div class="col-xl-8 col-lg-7 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="m-0 fw-bold text-slate-800"><i class="fas fa-boxes me-2 text-primary"></i>Inventory Stock list</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <?php if (empty($items)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-box-open fa-3x mb-3 text-slate-300"></i>
                                <p class="mb-0 fw-semibold">Inventory is empty.</p>
                                <p class="small">Add raw materials or ingredients to start tracking.</p>
                            </div>
                        <?php else: ?>
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="px-4">Item & SKU</th>
                                        <th>Supplier</th>
                                        <th>Purchase Price</th>
                                        <th>Current Stock</th>
                                        <th>Status</th>
                                        <th class="text-end px-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $i): 
                                        $is_low = ($i['current_stock'] <= $i['minimum_stock']);
                                    ?>
                                        <tr>
                                            <td class="px-4">
                                                <div class="fw-bold text-slate-800"><?php echo htmlspecialchars($i['name']); ?></div>
                                                <div class="text-muted small">SKU: <?php echo !empty($i['sku']) ? htmlspecialchars($i['sku']) : 'N/A'; ?></div>
                                            </td>
                                            <td class="small text-slate-600">
                                                <?php echo htmlspecialchars($i['supplier_name'] ?? 'Direct Buy'); ?>
                                            </td>
                                            <td>₹<?php echo number_format($i['purchase_price'], 2); ?> <span class="small text-muted">/<?php echo htmlspecialchars($i['unit']); ?></span></td>
                                            <td>
                                                <span class="fw-bold <?php echo $is_low ? 'text-danger' : 'text-slate-800'; ?>">
                                                    <?php echo floatval($i['current_stock']); ?> <?php echo htmlspecialchars($i['unit']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($i['current_stock'] <= 0): ?>
                                                    <span class="badge bg-danger">Out of Stock</span>
                                                <?php elseif ($is_low): ?>
                                                    <span class="badge bg-warning text-dark">Low Stock</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">In Stock</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end px-4">
                                                <button class="btn btn-sm btn-outline-primary edit-item-btn me-1" 
                                                        data-id="<?php echo $i['item_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($i['name']); ?>"
                                                        data-sku="<?php echo htmlspecialchars($i['sku']); ?>"
                                                        data-barcode="<?php echo htmlspecialchars($i['barcode']); ?>"
                                                        data-purchase="<?php echo $i['purchase_price']; ?>"
                                                        data-selling="<?php echo $i['selling_price']; ?>"
                                                        data-unit="<?php echo htmlspecialchars($i['unit']); ?>"
                                                        data-minimum="<?php echo $i['minimum_stock']; ?>"
                                                        data-supplier="<?php echo $i['supplier_id']; ?>"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="post" action="inventory.php" class="d-inline" onsubmit="return confirm('Delete this item from inventory?');">
                                                    <input type="hidden" name="item_id" value="<?php echo $i['item_id']; ?>">
                                                    <input type="hidden" name="delete_item" value="1">
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

        <!-- Recent Stock Transactions (Ledger) -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="m-0 fw-bold text-slate-800"><i class="fas fa-history me-2 text-primary"></i>Stock Ledger (Recent)</h5>
                </div>
                <div class="card-body p-0" style="max-height: 480px; overflow-y: auto;">
                    <?php if (empty($transactions)): ?>
                        <div class="text-center py-5 text-muted">
                            <p class="mb-0 small">No stock transactions logged yet.</p>
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush small">
                            <?php foreach ($transactions as $t): 
                                $badge_class = 'bg-secondary';
                                $icon = 'fa-exchange-alt';
                                $type_label = 'Adjustment';
                                if ($t['type'] === 'stock_in') {
                                    $badge_class = 'bg-success';
                                    $icon = 'fa-plus-circle';
                                    $type_label = 'Stock In';
                                } elseif ($t['type'] === 'stock_out') {
                                    $badge_class = 'bg-primary';
                                    $icon = 'fa-minus-circle';
                                    $type_label = 'Stock Out';
                                } elseif ($t['type'] === 'waste') {
                                    $badge_class = 'bg-danger';
                                    $icon = 'fa-trash';
                                    $type_label = 'Wastage';
                                }
                            ?>
                                <li class="list-group-item p-3 border-bottom">
                                    <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                        <h6 class="mb-0 fw-bold text-slate-800"><?php echo htmlspecialchars($t['item_name']); ?></h6>
                                        <span class="badge <?php echo $badge_class; ?>"><i class="fas <?php echo $icon; ?> me-1"></i><?php echo $type_label; ?></span>
                                    </div>
                                    <p class="mb-1 text-slate-600">Qty: <strong><?php echo floatval($t['quantity']); ?> <?php echo htmlspecialchars($t['unit']); ?></strong></p>
                                    <?php if (!empty($t['remarks'])): ?>
                                        <p class="mb-1 text-muted small"><i class="far fa-comment-alt me-1"></i><?php echo htmlspecialchars($t['remarks']); ?></p>
                                    <?php endif; ?>
                                    <small class="text-slate-400 d-block text-end"><?php echo date('d M Y, H:i', strtotime($t['created_at'])); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add/Edit Item -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="addItemModalLabel"><i class="fas fa-plus-circle me-2 text-warning"></i>Add Inventory Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="inventory.php">
                <input type="hidden" name="item_id" id="form_item_id" value="0">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Item Name *</label>
                        <input type="text" name="name" id="form_name" class="form-control" required placeholder="e.g. Basmati Rice">
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label small fw-bold text-muted">SKU / Code</label>
                            <input type="text" name="sku" id="form_sku" class="form-control" placeholder="e.g. BR-001">
                        </div>
                        <div class="col">
                            <label class="form-label small fw-bold text-muted">Barcode</label>
                            <input type="text" name="barcode" id="form_barcode" class="form-control" placeholder="UPC / EAN">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label small fw-bold text-muted">Purchase Price *</label>
                            <input type="number" step="0.01" name="purchase_price" id="form_purchase" class="form-control" required min="0" value="0.00">
                        </div>
                        <div class="col">
                            <label class="form-label small fw-bold text-muted">Selling Price</label>
                            <input type="number" step="0.01" name="selling_price" id="form_selling" class="form-control" min="0" value="0.00">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label small fw-bold text-muted">Unit of Measure *</label>
                            <input type="text" name="unit" id="form_unit" class="form-control" required placeholder="e.g. kg, liter, pc">
                        </div>
                        <div class="col" id="opening_stock_container">
                            <label class="form-label small fw-bold text-muted">Opening Stock</label>
                            <input type="number" step="0.01" name="opening_stock" class="form-control" min="0" value="0.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Minimum Warning Stock *</label>
                        <input type="number" step="0.01" name="minimum_stock" id="form_minimum" class="form-control" required min="0" value="5.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Preferred Supplier</label>
                        <select name="supplier_id" id="form_supplier" class="form-select">
                            <option value="">-- Direct Buy / No Supplier --</option>
                            <?php foreach ($suppliers as $s): ?>
                                <option value="<?php echo $s['supplier_id']; ?>"><?php echo htmlspecialchars($s['company_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="save_item" class="btn btn-primary px-4">Save Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Stock Adjustment -->
<div class="modal fade" id="adjustmentModal" tabindex="-1" aria-labelledby="adjustmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="adjustmentModalLabel"><i class="fas fa-sliders-h me-2 text-warning"></i>Adjust Stock Levels</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="inventory.php">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Select Inventory Item *</label>
                        <select name="item_id" class="form-select" required>
                            <option value="">-- Choose Item --</option>
                            <?php foreach ($items as $item): ?>
                                <option value="<?php echo $item['item_id']; ?>"><?php echo htmlspecialchars($item['name']); ?> (Current: <?php echo floatval($item['current_stock']); ?> <?php echo htmlspecialchars($item['unit']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Adjustment Type *</label>
                        <select name="adjustment_type" id="adj_type" class="form-select" required>
                            <option value="stock_in">Stock In (Purchase/Add)</option>
                            <option value="stock_out">Stock Out (Kitchen Use/Remove)</option>
                            <option value="waste">Wastage / Spoilage</option>
                            <option value="adjustment">Manual Correction (Overhead)</option>
                        </select>
                    </div>
                    
                    <!-- Direction for Manual Correction -->
                    <div class="mb-3 d-none" id="direction_container">
                        <label class="form-label small fw-bold text-muted">Correction Direction *</label>
                        <select name="adj_direction" class="form-select">
                            <option value="add">Add Stock (+)</option>
                            <option value="remove">Remove Stock (-)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Quantity *</label>
                        <input type="number" step="0.01" name="quantity" class="form-control" required min="0.01" placeholder="e.g. 10.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Remarks / Reference *</label>
                        <textarea name="remarks" class="form-control" rows="3" required placeholder="Reason for adjustment, PO reference, invoice details"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="record_adjustment" class="btn btn-warning px-4 text-dark fw-bold">Apply Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit item handler
    const editButtons = document.querySelectorAll('.edit-item-btn');
    editButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('addItemModalLabel').innerHTML = '<i class="fas fa-edit me-2 text-warning"></i>Edit Inventory Item';
            document.getElementById('form_item_id').value = this.dataset.id;
            document.getElementById('form_name').value = this.dataset.name;
            document.getElementById('form_sku').value = this.dataset.sku;
            document.getElementById('form_barcode').value = this.dataset.barcode;
            document.getElementById('form_purchase').value = this.dataset.purchase;
            document.getElementById('form_selling').value = this.dataset.selling;
            document.getElementById('form_unit').value = this.dataset.unit;
            document.getElementById('form_minimum').value = this.dataset.minimum;
            document.getElementById('form_supplier').value = this.dataset.supplier;

            // Hide opening stock container during edits
            document.getElementById('opening_stock_container').classList.add('d-none');
            
            const modal = new bootstrap.Modal(document.getElementById('addItemModal'));
            modal.show();
        });
    });

    // Reset Add Modal on hidden
    const addModalEl = document.getElementById('addItemModal');
    addModalEl.addEventListener('hidden.bs.modal', function () {
        document.getElementById('addItemModalLabel').innerHTML = '<i class="fas fa-plus-circle me-2 text-warning"></i>Add Inventory Item';
        document.getElementById('form_item_id').value = '0';
        document.getElementById('form_name').value = '';
        document.getElementById('form_sku').value = '';
        document.getElementById('form_barcode').value = '';
        document.getElementById('form_purchase').value = '0.00';
        document.getElementById('form_selling').value = '0.00';
        document.getElementById('form_unit').value = '';
        document.getElementById('form_minimum').value = '5.00';
        document.getElementById('form_supplier').value = '';
        document.getElementById('opening_stock_container').classList.remove('d-none');
    });

    // Adjustment Direction listener
    const adjTypeSelect = document.getElementById('adj_type');
    const directionContainer = document.getElementById('direction_container');
    adjTypeSelect.addEventListener('change', function() {
        if (this.value === 'adjustment') {
            directionContainer.classList.remove('d-none');
        } else {
            directionContainer.classList.add('d-none');
        }
    });
});
</script>

<?php require_once 'includes/restaurant_footer.php'; ?>
