<?php
// Start session and include required files
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect to login if not logged in
if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

// Check if order ID is provided
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header("Location: index.php");
    exit;
}

$order_id = intval($_GET['order_id']);
$user_id = $_SESSION['user_id'];

// Get order details with security check (must belong to the current user)
$order = get_order_details($order_id, $user_id);

// Redirect if order not found or does not belong to current user
if (!$order) {
    header("Location: index.php");
    exit;
}

// Get restaurant details
$restaurant = get_restaurant_by_id($order['restaurant_id']);

// Load global site settings for receipt headers
$site_name = get_site_setting('site_name', 'ARS Junction');
$site_email = get_site_setting('site_email', 'officialarsjunction@gmail.com');
$site_phone = get_site_setting('site_phone', '7979730721');
$site_location = get_site_setting('site_location', 'AT - PIRO, BHOJPUR, BIHAR, INDIA-802207');
$currency_symbol = get_site_setting('currency_symbol', '₹');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $order['order_id']; ?> - <?php echo htmlspecialchars($site_name); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .invoice-card {
            background: #ffffff;
            border: 1px solid #e3e6f0;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .invoice-title {
            color: #4e73df;
            font-weight: 700;
        }
        .receipt-header {
            border-bottom: 2px dashed #e3e6f0;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .table th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
        }
        .totals-table td {
            padding: 6px 12px;
        }
        @media print {
            body {
                background-color: #ffffff;
            }
            .no-print {
                display: none !important;
            }
            .invoice-card {
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }
        }
    </style>
</head>
<body>

<div class="container py-5">
    <!-- Action buttons (hidden on print) -->
    <div class="row justify-content-center mb-4 no-print">
        <div class="col-lg-8 d-flex justify-content-between">
            <a href="order_tracking.php?id=<?php echo $order_id; ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Tracking
            </a>
            <div>
                <button onclick="window.print();" class="btn btn-primary px-4">
                    <i class="fas fa-print me-1"></i> Print / Save as PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Invoice Sheet -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="invoice-card p-5">
                <!-- Header -->
                <div class="receipt-header">
                    <div class="row">
                        <div class="col-sm-6 text-center text-sm-start mb-3 mb-sm-0">
                            <h2 class="invoice-title mb-1"><?php echo htmlspecialchars($site_name); ?></h2>
                            <p class="text-muted small mb-0"><?php echo nl2br(htmlspecialchars($site_location)); ?></p>
                            <p class="text-muted small mb-0"><i class="fas fa-phone-alt me-1"></i> +91 <?php echo htmlspecialchars($site_phone); ?></p>
                            <p class="text-muted small mb-0"><i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($site_email); ?></p>
                        </div>
                        <div class="col-sm-6 text-center text-sm-end">
                            <h4 class="text-primary mb-1">INVOICE</h4>
                            <p class="mb-1 text-dark"><strong>Invoice No:</strong> #<?php echo $order['order_id']; ?></p>
                            <p class="mb-1 text-muted small"><strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($order['order_date'] . ' UTC')); ?></p>
                            <p class="mb-0 text-muted small"><strong>Payment Method:</strong> <?php echo ($order['payment_method'] === 'upi') ? 'UPI' : 'Cash on Delivery'; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Addresses -->
                <div class="row mb-4">
                    <div class="col-sm-6 mb-3 mb-sm-0">
                        <h6 class="text-uppercase text-muted font-weight-bold small mb-2">Order From</h6>
                        <p class="mb-1"><strong><?php echo htmlspecialchars($restaurant['name']); ?></strong></p>
                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($restaurant['address'] . ', ' . $restaurant['city']); ?></p>
                        <p class="text-muted small mb-0"><i class="fas fa-phone me-1"></i> +91 <?php echo htmlspecialchars($restaurant['phone']); ?></p>
                    </div>
                    <div class="col-sm-6">
                        <h6 class="text-uppercase text-muted font-weight-bold small mb-2">Deliver To</h6>
                        <p class="mb-1"><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></p>
                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($order['delivery_address']); ?></p>
                        <p class="text-muted small mb-0"><i class="fas fa-phone me-1"></i> +91 <?php echo htmlspecialchars($order['delivery_phone']); ?></p>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Item Name</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $counter = 1;
                            foreach ($order['items'] as $item): 
                            ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td class="text-center"><?php echo $item['quantity']; ?></td>
                                <td class="text-end"><?php echo format_price($item['price']); ?></td>
                                <td class="text-end"><?php echo format_price($item['price'] * $item['quantity']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Calculations & Stamp -->
                <div class="row mt-4">
                    <div class="col-sm-6 col-md-7 text-start d-flex align-items-end justify-content-start pb-2">
                        <div class="stamp-container text-center mb-2" style="opacity: 0.85;">
                            <img src="images/digital_stamp.png" alt="Digital Stamp" style="max-width: 130px; height: auto; transform: rotate(-3deg);">
                            <div class="text-success small fw-bold mt-1" style="font-size: 0.7rem; letter-spacing: 1px; text-transform: uppercase;">Verified & Signed</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-5">
                        <table class="w-100 totals-table">
                            <tr>
                                <td class="text-muted">Subtotal:</td>
                                <td class="text-end"><?php echo format_price($order['subtotal']); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Delivery Fee:</td>
                                <td class="text-end"><?php echo format_price($order['delivery_fee']); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Tax (5%):</td>
                                <td class="text-end"><?php echo format_price($order['tax']); ?></td>
                            </tr>
                            <?php if (isset($order['discount_amount']) && (float)$order['discount_amount'] > 0): ?>
                            <tr>
                                <td class="text-muted">Discount (<?php echo htmlspecialchars($order['promo_code']); ?>):</td>
                                <td class="text-end text-success">-<?php echo format_price($order['discount_amount']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="border-top">
                                <td class="fw-bold text-dark pt-2">Grand Total:</td>
                                <td class="text-end fw-bold text-primary pt-2 fs-5"><?php echo format_price($order['total_amount']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Footer note -->
                <div class="text-center mt-5 pt-4 border-top">
                    <p class="mb-1 fw-semibold">Thank you for ordering with <?php echo htmlspecialchars($site_name); ?>!</p>
                    <p class="text-muted small mb-0">This is a system generated invoice. No signature required.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto trigger print dialog when page is loaded
    window.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            window.print();
        }, 500);
    });
</script>
</body>
</html>
