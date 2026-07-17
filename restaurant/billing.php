<?php
/**
 * Restaurant Owner Portal - Billing & Invoices
 */

$page_title = "Billing & Invoices";
require_once 'includes/restaurant_header.php';

$success_msg = '';
$error_msg = '';

// Default tax settings: GST is 5% in India for normal restaurants (CGST 2.5%, SGST 2.5%)
$default_gst_rate = 0.05; 
$commission_rate = 0.05; // 5% platform commission

// Fetch completed (delivered) orders for billing
global $conn;
$orders = [];
$total_revenue = 0.00;
$total_tax = 0.00;
$total_discount = 0.00;

try {
    $stmt = $conn->prepare("
        SELECT o.*, u.name as customer_name 
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        WHERE o.restaurant_id = ? AND o.order_status = 'delivered'
        ORDER BY o.order_date DESC
    ");
    $stmt->execute([$restaurant_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($orders as $o) {
        $total_revenue += floatval($o['total_amount']);
        $total_tax += floatval($o['tax']);
        $total_discount += floatval($o['discount_amount'] ?? 0);
    }
} catch (PDOException $e) {
    $error_msg = "Database error: " . $e->getMessage();
}

// Fetch single invoice details if requested
$invoice_order = null;
$invoice_items = [];
if (isset($_GET['view_invoice'])) {
    $invoice_id = intval($_GET['view_invoice']);
    try {
        $stmt = $conn->prepare("
            SELECT o.*, u.name as customer_name, u.email as customer_email, r.name as res_name, r.address as res_address, r.phone as res_phone 
            FROM orders o
            JOIN users u ON o.user_id = u.user_id
            JOIN restaurants r ON o.restaurant_id = r.restaurant_id
            WHERE o.order_id = ? AND o.restaurant_id = ? AND o.order_status = 'delivered'
        ");
        $stmt->execute([$invoice_id, $restaurant_id]);
        $invoice_order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($invoice_order) {
            $stmt_items = $conn->prepare("
                SELECT oi.*, m.name as item_name 
                FROM order_items oi
                JOIN menu_items m ON oi.menu_item_id = m.item_id
                WHERE oi.order_id = ?
            ");
            $stmt_items->execute([$invoice_id]);
            $invoice_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $error_msg = "Failed to fetch invoice details: " . $e->getMessage();
    }
}
?>

<div class="container-fluid animated-fade-in py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-slate-800 fw-bold">Billing & Invoices</h1>
        <a href="billing.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-sync-alt"></i> Refresh</a>
    </div>

    <!-- Alert Messages -->
    <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Overview Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card shadow-sm border-0 border-left-primary py-2">
                <div class="card-body">
                    <div class="text-xs fw-bold text-primary text-uppercase mb-1">Gross Revenue</div>
                    <div class="h5 mb-0 fw-bold text-gray-800">₹<?php echo number_format($total_revenue, 2); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card shadow-sm border-0 border-left-success py-2">
                <div class="card-body">
                    <div class="text-xs fw-bold text-success text-uppercase mb-1">GST Collected (5%)</div>
                    <div class="h5 mb-0 fw-bold text-gray-800">₹<?php echo number_format($total_tax, 2); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card shadow-sm border-0 border-left-danger py-2">
                <div class="card-body">
                    <div class="text-xs fw-bold text-danger text-uppercase mb-1">Platform Commission</div>
                    <div class="h5 mb-0 fw-bold text-gray-800">₹<?php echo number_format($total_revenue * $commission_rate, 2); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card shadow-sm border-0 border-left-info py-2">
                <div class="card-body">
                    <div class="text-xs fw-bold text-info text-uppercase mb-1">Est. Net Settlement</div>
                    <div class="h5 mb-0 fw-bold text-gray-800">₹<?php echo number_format($total_revenue - ($total_revenue * $commission_rate), 2); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoices List -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="m-0 fw-bold text-slate-800"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Delivered Sales & Tax Invoices</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <?php if (empty($orders)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-receipt fa-3x mb-3 text-slate-300"></i>
                        <p class="mb-0 fw-semibold">No settled orders found.</p>
                        <p class="small">Invoices will appear here as soon as orders are delivered successfully.</p>
                    </div>
                <?php else: ?>
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4">Order ID</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Subtotal</th>
                                <th>Discount</th>
                                <th>GST (Tax)</th>
                                <th>Grand Total</th>
                                <th class="text-end px-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td class="px-4 fw-bold text-slate-800">#<?php echo $o['order_id']; ?></td>
                                    <td class="small"><?php echo date('d M Y, H:i', strtotime($o['order_date'] . ' UTC')); ?></td>
                                    <td class="fw-semibold text-slate-700"><?php echo htmlspecialchars($o['customer_name']); ?></td>
                                    <td>₹<?php echo number_format($o['subtotal'], 2); ?></td>
                                    <td class="text-danger">-₹<?php echo number_format($o['discount_amount'] ?? 0, 2); ?></td>
                                    <td class="text-success">₹<?php echo number_format($o['tax'], 2); ?></td>
                                    <td class="fw-bold">₹<?php echo number_format($o['total_amount'], 2); ?></td>
                                    <td class="text-end px-4">
                                        <a href="billing.php?view_invoice=<?php echo $o['order_id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye me-1"></i> View Invoice
                                        </a>
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

<!-- Modal / Overlay: Printable Invoice View -->
<?php if ($invoice_order): 
    $invoice_subtotal = floatval($invoice_order['subtotal']);
    $invoice_discount = floatval($invoice_order['discount_amount'] ?? 0);
    $invoice_taxable = $invoice_subtotal - $invoice_discount;
    
    // Split GST into CGST (2.5%) and SGST (2.5%)
    $cgst_rate = 0.025;
    $sgst_rate = 0.025;
    $cgst_amt = $invoice_taxable * $cgst_rate;
    $sgst_amt = $invoice_taxable * $sgst_rate;
?>
<div class="modal fade show" id="invoiceModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-invoice-dollar me-2 text-warning"></i>Tax Invoice - Order #<?php echo $invoice_order['order_id']; ?></h5>
                <a href="billing.php" class="btn-close btn-close-white" aria-label="Close"></a>
            </div>
            <div class="modal-body p-5" id="printableInvoice">
                <!-- Branding & Invoice Info -->
                <div class="row mb-4">
                    <div class="col-sm-6">
                        <h4 class="fw-bold text-primary mb-1"><?php echo htmlspecialchars($invoice_order['res_name']); ?></h4>
                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($invoice_order['res_address']); ?></p>
                        <p class="text-muted small mb-0">Phone: <?php echo htmlspecialchars($invoice_order['res_phone']); ?></p>
                        <p class="text-slate-800 small fw-semibold mt-2">GSTIN: <code class="text-uppercase">10AAAAA0000A1Z1</code> (Composite)</p>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <h5 class="fw-bold text-uppercase text-slate-700">TAX INVOICE</h5>
                        <p class="mb-0 small"><strong>Invoice No:</strong> ARS-INV-<?php echo $invoice_order['order_id']; ?></p>
                        <p class="mb-0 small"><strong>Date:</strong> <?php echo date('d-M-Y, H:i', strtotime($invoice_order['order_date'] . ' UTC')); ?></p>
                        <p class="mb-0 small"><strong>Payment Method:</strong> <span class="badge bg-secondary text-uppercase"><?php echo htmlspecialchars($invoice_order['payment_method']); ?></span></p>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Bill To & Delivery Details -->
                <div class="row mb-4">
                    <div class="col-sm-6">
                        <h6 class="fw-bold text-muted text-uppercase mb-2" style="font-size: 0.75rem;">BILL TO:</h6>
                        <h6 class="fw-bold text-slate-800 mb-1"><?php echo htmlspecialchars($invoice_order['customer_name']); ?></h6>
                        <p class="text-muted small mb-0">Phone: <?php echo htmlspecialchars($invoice_order['delivery_phone'] ?: 'N/A'); ?></p>
                        <p class="text-muted small mb-0">Email: <?php echo htmlspecialchars($invoice_order['customer_email']); ?></p>
                    </div>
                    <div class="col-sm-6">
                        <h6 class="fw-bold text-muted text-uppercase mb-2" style="font-size: 0.75rem;">DELIVERY ADDRESS:</h6>
                        <p class="text-slate-700 small mb-0"><?php echo htmlspecialchars($invoice_order['delivery_address']); ?></p>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Dish / Item Description</th>
                                <th class="text-center">Rate</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Taxable Value</th>
                                <th class="text-end">CGST (2.5%)</th>
                                <th class="text-end">SGST (2.5%)</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $idx = 1;
                            foreach ($invoice_items as $item): 
                                $item_qty = intval($item['quantity']);
                                $item_price = floatval($item['price']);
                                $item_subtotal = $item_qty * $item_price;
                                $item_cgst = $item_subtotal * $cgst_rate;
                                $item_sgst = $item_subtotal * $sgst_rate;
                                $item_total = $item_subtotal + $item_cgst + $item_sgst;
                            ?>
                                <tr>
                                    <td><?php echo $idx++; ?></td>
                                    <td>
                                        <div class="fw-bold text-slate-800"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                    </td>
                                    <td class="text-center">₹<?php echo number_format($item_price, 2); ?></td>
                                    <td class="text-center"><?php echo $item_qty; ?></td>
                                    <td class="text-end">₹<?php echo number_format($item_subtotal, 2); ?></td>
                                    <td class="text-end">₹<?php echo number_format($item_cgst, 2); ?></td>
                                    <td class="text-end">₹<?php echo number_format($item_sgst, 2); ?></td>
                                    <td class="text-end fw-bold">₹<?php echo number_format($item_total, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Summary Panel -->
                <div class="row">
                    <div class="col-md-6">
                        <p class="text-muted small">Thank you for your order! If you have any inquiries regarding this invoice, please feel free to reach out to the support line.</p>
                    </div>
                    <div class="col-md-6">
                        <div class="card shadow-none border bg-light p-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-slate-600">Subtotal:</span>
                                <span class="fw-bold text-slate-800">₹<?php echo number_format($invoice_subtotal, 2); ?></span>
                            </div>
                            <?php if ($invoice_discount > 0): ?>
                                <div class="d-flex justify-content-between mb-2 text-danger">
                                    <span>Discount (Promo):</span>
                                    <span>-₹<?php echo number_format($invoice_discount, 2); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-slate-600">Taxable Amount:</span>
                                <span class="fw-bold text-slate-800">₹<?php echo number_format($invoice_taxable, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-slate-600">CGST (2.5%):</span>
                                <span class="fw-bold text-slate-800">₹<?php echo number_format($cgst_amt, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-slate-600">SGST (2.5%):</span>
                                <span class="fw-bold text-slate-800">₹<?php echo number_format($sgst_amt, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-slate-600">Delivery Fee:</span>
                                <span class="fw-bold text-slate-800">₹<?php echo number_format($invoice_order['delivery_fee'], 2); ?></span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between text-slate-900 fw-bold">
                                <span>Grand Total:</span>
                                <span class="h5 mb-0 fw-bold text-primary">₹<?php echo number_format($invoice_order['total_amount'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <a href="billing.php" class="btn btn-outline-secondary">Close</a>
                <button type="button" onclick="printInvoiceSection()" class="btn btn-success px-4"><i class="fas fa-print me-1"></i> Print Invoice</button>
            </div>
        </div>
    </div>
</div>

<script>
function printInvoiceSection() {
    var printContents = document.getElementById('printableInvoice').innerHTML;
    var originalContents = document.body.innerHTML;
    
    // Simple custom print layout wrapper
    document.body.innerHTML = "<html><head><title>Invoice</title><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'></head><body class='p-5'>" + printContents + "</body></html>";
    
    window.print();
    
    // Restore original DOM
    document.body.innerHTML = originalContents;
    window.location.reload();
}
</script>
<?php endif; ?>

<?php require_once 'includes/restaurant_footer.php'; ?>
