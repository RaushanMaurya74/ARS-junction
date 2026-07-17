<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/admin_auth.php';

// Check if admin is logged in
if (!is_admin_logged_in()) {
    header("Location: login.php");
    exit;
}

// Set up dates for the current month
$start_of_month = date('Y-m-01 00:00:00');
$end_of_month = date('Y-m-t 23:59:59');
$month_name = date('F_Y');

// Fetch orders for the current month
$stmt = $conn->prepare("SELECT o.*, u.name as customer_name, r.name as restaurant_name 
                      FROM orders o
                      JOIN users u ON o.user_id = u.user_id
                      JOIN restaurants r ON o.restaurant_id = r.restaurant_id
                      WHERE o.order_date >= ? AND o.order_date <= ?
                      ORDER BY o.order_date DESC");
$stmt->execute([$start_of_month, $end_of_month]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="monthly_expenditure_report_' . $month_name . '.csv"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Output UTF-8 BOM for proper rendering in Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Output the column headings
fputcsv($output, [
    'Order ID', 
    'Customer Name', 
    'Restaurant Name', 
    'Order Date', 
    'Payment Method', 
    'Payment Status', 
    'Order Status', 
    'Subtotal (INR)', 
    'Delivery Fee (INR)', 
    'Tax (INR)', 
    'Total Amount (INR)'
]);

// Initialize totals
$total_subtotal = 0.0;
$total_delivery_fee = 0.0;
$total_tax = 0.0;
$total_amount = 0.0;

// Loop over the rows, outputting them and accumulating totals
foreach ($orders as $order) {
    fputcsv($output, [
        '#' . $order['order_id'],
        $order['customer_name'],
        $order['restaurant_name'],
        date('M d, Y h:i A', strtotime($order['order_date'] . ' UTC')),
        strtoupper($order['payment_method']),
        ucfirst($order['payment_status']),
        ucfirst($order['order_status']),
        number_format((float)$order['subtotal'], 2, '.', ''),
        number_format((float)$order['delivery_fee'], 2, '.', ''),
        number_format((float)$order['tax'], 2, '.', ''),
        number_format((float)$order['total_amount'], 2, '.', '')
    ]);
    
    // Accumulate only if the order isn't cancelled
    if ($order['order_status'] !== 'cancelled') {
        $total_subtotal += (float)$order['subtotal'];
        $total_delivery_fee += (float)$order['delivery_fee'];
        $total_tax += (float)$order['tax'];
        $total_amount += (float)$order['total_amount'];
    }
}

// Add a spacer row
fputcsv($output, []);

// Add totals summary row
fputcsv($output, [
    'TOTALS (Excluding Cancelled)', 
    '', 
    '', 
    '', 
    '', 
    '', 
    '', 
    number_format($total_subtotal, 2, '.', ''),
    number_format($total_delivery_fee, 2, '.', ''),
    number_format($total_tax, 2, '.', ''),
    number_format($total_amount, 2, '.', '')
]);

fclose($output);
exit;
?>
