<?php
/**
 * Restaurant Owner Portal - Reports & Analytics
 */

$page_title = "ERP Reports & Analytics";
require_once 'includes/restaurant_header.php';

$success_msg = '';
$error_msg = '';

// Handle Date Range Filtering
$filter_range = isset($_GET['range']) ? clean_input($_GET['range']) : 'month';
$start_date = '';
$end_date = '';

if ($filter_range === 'today') {
    $start_date = date('Y-m-d 00:00:00');
    $end_date = date('Y-m-d 23:59:59');
} elseif ($filter_range === 'week') {
    $start_date = date('Y-m-d 00:00:00', strtotime('-7 days'));
    $end_date = date('Y-m-d 23:59:59');
} elseif ($filter_range === 'month') {
    $start_date = date('Y-m-01 00:00:00');
    $end_date = date('Y-m-t 23:59:59');
} elseif ($filter_range === 'custom') {
    $start_date = isset($_GET['start_date']) ? clean_input($_GET['start_date']) . ' 00:00:00' : date('Y-m-01 00:00:00');
    $end_date = isset($_GET['end_date']) ? clean_input($_GET['end_date']) . ' 23:59:59' : date('Y-m-t 23:59:59');
} else {
    // Default to last 30 days
    $start_date = date('Y-m-d 00:00:00', strtotime('-30 days'));
    $end_date = date('Y-m-d 23:59:59');
}

global $conn;

// 1. Fetch Sales and Tax Performance
$sales_summary = ['total_sales' => 0.00, 'total_tax' => 0.00, 'order_count' => 0, 'avg_bill' => 0.00];
try {
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(total_amount), 0.00) as total_sales,
            COALESCE(SUM(tax), 0.00) as total_tax,
            COUNT(order_id) as order_count,
            COALESCE(AVG(total_amount), 0.00) as avg_bill
        FROM orders 
        WHERE restaurant_id = ? AND order_status = 'delivered' AND order_date >= ? AND order_date <= ?
    ");
    $stmt->execute([$restaurant_id, $start_date, $end_date]);
    $sales_summary = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_msg = "Failed to fetch sales summary: " . $e->getMessage();
}

// 2. Fetch Product Performance (Top Selling Dishes)
$top_dishes = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            m.name as item_name,
            SUM(oi.quantity) as qty_sold,
            SUM(oi.quantity * oi.price) as revenue
        FROM order_items oi
        JOIN menu_items m ON oi.menu_item_id = m.item_id
        JOIN orders o ON oi.order_id = o.order_id
        WHERE o.restaurant_id = ? AND o.order_status = 'delivered' AND o.order_date >= ? AND o.order_date <= ?
        GROUP BY m.name
        ORDER BY qty_sold DESC
        LIMIT 10
    ");
    $stmt->execute([$restaurant_id, $start_date, $end_date]);
    $top_dishes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Ignore product fetch failure
}

// 3. Fetch Stock consumption log summary
$stock_ledger = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            t.type,
            COUNT(t.transaction_id) as count,
            SUM(ABS(t.quantity)) as total_qty,
            i.unit
        FROM inventory_transactions t
        JOIN inventory_items i ON t.item_id = i.item_id
        WHERE i.restaurant_id = ? AND t.created_at >= ? AND t.created_at <= ?
        GROUP BY t.type, i.unit
    ");
    $stmt->execute([$restaurant_id, $start_date, $end_date]);
    $stock_ledger = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Ignore stock fetch failure
}
?>

<div class="container-fluid animated-fade-in py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-slate-800 fw-bold">ERP Reports & Analytics</h1>
        
        <!-- Date Filter Toolbar -->
        <form method="get" action="reports.php" class="d-flex align-items-center">
            <select name="range" id="rangeSelect" class="form-select form-select-sm me-2" style="width: auto;" onchange="toggleCustomDates()">
                <option value="today" <?php echo ($filter_range === 'today') ? 'selected' : ''; ?>>Today</option>
                <option value="week" <?php echo ($filter_range === 'week') ? 'selected' : ''; ?>>Last 7 Days</option>
                <option value="month" <?php echo ($filter_range === 'month') ? 'selected' : ''; ?>>This Month</option>
                <option value="custom" <?php echo ($filter_range === 'custom') ? 'selected' : ''; ?>>Custom Range</option>
            </select>
            
            <div id="customDateInputs" class="d-flex align-items-center me-2 <?php echo ($filter_range !== 'custom') ? 'd-none' : ''; ?>">
                <input type="date" name="start_date" class="form-control form-control-sm me-1" value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : date('Y-m-01'); ?>">
                <span class="me-1 text-muted small">to</span>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : date('Y-m-t'); ?>">
            </div>
            
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i> Filter</button>
        </form>
    </div>

    <!-- Alert Messages -->
    <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Sales & Tax Performance -->
    <div class="row mb-4">
        <!-- Sales Card -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100 p-3 bg-primary text-white">
                <h6 class="text-uppercase small fw-bold text-white-50">Gross Revenue</h6>
                <div class="h2 fw-bold">₹<?php echo number_format($sales_summary['total_sales'], 2); ?></div>
                <small class="text-white-50">Settled Order Sales</small>
            </div>
        </div>
        <!-- Order Count Card -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100 p-3 bg-success text-white">
                <h6 class="text-uppercase small fw-bold text-white-50">Orders Delivered</h6>
                <div class="h2 fw-bold"><?php echo $sales_summary['order_count']; ?></div>
                <small class="text-white-50">Total volume</small>
            </div>
        </div>
        <!-- Average Order Value -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100 p-3 bg-info text-white">
                <h6 class="text-uppercase small fw-bold text-white-50">Average Bill Value</h6>
                <div class="h2 fw-bold">₹<?php echo number_format($sales_summary['avg_bill'], 2); ?></div>
                <small class="text-white-50">Revenue / order ratio</small>
            </div>
        </div>
        <!-- GST Tax Collected -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100 p-3 bg-dark text-white">
                <h6 class="text-uppercase small fw-bold text-warning">CGST & SGST (5%)</h6>
                <div class="h2 fw-bold">₹<?php echo number_format($sales_summary['total_tax'], 2); ?></div>
                <small class="text-white-50">Splitting: CGST (2.5%) / SGST (2.5%)</small>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Dish Sales Performance (Top Selling) -->
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="m-0 fw-bold text-slate-800"><i class="fas fa-utensils me-2 text-primary"></i>Top Selling Dishes (Product Performance)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <?php if (empty($top_dishes)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-pizza-slice fa-3x mb-3 text-slate-200"></i>
                                <p class="mb-0">No item sales records found for this period.</p>
                            </div>
                        <?php else: ?>
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="px-4">Dish Name</th>
                                        <th class="text-center">Quantity Sold</th>
                                        <th class="text-end px-4">Sales Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_dishes as $dish): ?>
                                        <tr>
                                            <td class="px-4 fw-bold text-slate-800"><?php echo htmlspecialchars($dish['item_name']); ?></td>
                                            <td class="text-center fw-semibold"><?php echo intval($dish['qty_sold']); ?> units</td>
                                            <td class="text-end px-4 fw-bold text-primary">₹<?php echo number_format($dish['revenue'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inventory Consumption & Stock Summary -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="m-0 fw-bold text-slate-800"><i class="fas fa-boxes me-2 text-primary"></i>Inventory Stock Summary</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <?php if (empty($stock_ledger)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-dolly-flatbed fa-3x mb-3 text-slate-200"></i>
                                <p class="mb-0">No inventory transactions logged in this range.</p>
                            </div>
                        <?php else: ?>
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="px-4">Transaction Type</th>
                                        <th class="text-center">Count</th>
                                        <th class="text-end px-4">Cumulative Volume</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stock_ledger as $row): 
                                        $type_lbl = 'Adjustment';
                                        $type_class = 'text-secondary';
                                        if ($row['type'] === 'stock_in') {
                                            $type_lbl = 'Stock Add / Purchase';
                                            $type_class = 'text-success fw-bold';
                                        } elseif ($row['type'] === 'stock_out') {
                                            $type_lbl = 'Kitchen Deduction';
                                            $type_class = 'text-primary fw-bold';
                                        } elseif ($row['type'] === 'waste') {
                                            $type_lbl = 'Damages / Wastage';
                                            $type_class = 'text-danger fw-bold';
                                        }
                                    ?>
                                        <tr>
                                            <td class="px-4 <?php echo $type_class; ?>"><?php echo $type_lbl; ?></td>
                                            <td class="text-center"><?php echo $row['count']; ?> events</td>
                                            <td class="text-end px-4 fw-bold text-slate-700"><?php echo floatval($row['total_qty']); ?> <?php echo htmlspecialchars($row['unit']); ?></td>
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

<script>
function toggleCustomDates() {
    const rangeSelect = document.getElementById('rangeSelect');
    const customDateInputs = document.getElementById('customDateInputs');
    if (rangeSelect.value === 'custom') {
        customDateInputs.classList.remove('d-none');
    } else {
        customDateInputs.classList.add('d-none');
    }
}
</script>

<?php require_once 'includes/restaurant_footer.php'; ?>
