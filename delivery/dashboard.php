    // Handle order actions
    if (isset($_POST['action'])) {
        $order_id = intval($_POST['order_id']);
        $action = $_POST['action'];
        $db_restaurant_id = isset($db_user['restaurant_id']) && $db_user['restaurant_id'] !== '' ? intval($db_user['restaurant_id']) : null;
        
        if ($action === 'start_delivery') {
            if ($db_restaurant_id !== null && $db_restaurant_id !== 0) {
                $stmt = $conn->prepare("UPDATE orders SET order_status = 'on the way' WHERE order_id = ? AND delivery_boy_id = ? AND restaurant_id = ?");
                $execute_params = [$order_id, $db_id, $db_restaurant_id];
            } else {
                $stmt = $conn->prepare("UPDATE orders SET order_status = 'on the way' WHERE order_id = ? AND delivery_boy_id = ?");
                $execute_params = [$order_id, $db_id];
            }
            if ($stmt->execute($execute_params)) {
                $success_msg = "Order #{$order_id} is now on the way!";
            } else {
                $error_msg = "Failed to update order status.";
            }
        }
        
        if ($action === 'complete_delivery') {
            if ($db_restaurant_id !== null && $db_restaurant_id !== 0) {
                $stmt = $conn->prepare("UPDATE orders SET order_status = 'delivered' WHERE order_id = ? AND delivery_boy_id = ? AND restaurant_id = ?");
                $execute_params = [$order_id, $db_id, $db_restaurant_id];
            } else {
                $stmt = $conn->prepare("UPDATE orders SET order_status = 'delivered' WHERE order_id = ? AND delivery_boy_id = ?");
                $execute_params = [$order_id, $db_id];
            }
            if ($stmt->execute($execute_params)) {
                $success_msg = "Order #{$order_id} marked as Delivered successfully.";
            } else {
                $error_msg = "Failed to update order status.";
            }
        }
        
        if ($action === 'confirm_payment') {
            if ($db_restaurant_id !== null && $db_restaurant_id !== 0) {
                $stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid' WHERE order_id = ? AND delivery_boy_id = ? AND restaurant_id = ?");
                $execute_params = [$order_id, $db_id, $db_restaurant_id];
            } else {
                $stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid' WHERE order_id = ? AND delivery_boy_id = ?");
                $execute_params = [$order_id, $db_id];
            }
            if ($stmt->execute($execute_params)) {
                $success_msg = "Payment for Order #{$order_id} marked as Paid.";
            } else {
                $error_msg = "Failed to confirm payment.";
            }
        }
    }