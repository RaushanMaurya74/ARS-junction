<?php
chdir(__DIR__);
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Run auto-assignment of expired pending orders globally on all poll events
try {
    auto_assign_confirmed_orders();
} catch (Exception $e) {
    // Suppress background assignment exceptions so as not to break notifications response
}

$role = isset($_GET['role']) ? clean_input($_GET['role']) : '';

if ($role === 'admin') {
    // Check if admin is logged in
    if (!isset($_SESSION['admin_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    try {
        // Count pending orders
        $stmt = $conn->query("SELECT COUNT(*) as count, MAX(order_id) as latest_id FROM orders WHERE order_status = 'pending'");
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'pending_count' => intval($data['count']),
            'latest_order_id' => intval($data['latest_id'] ?? 0)
        ]);
    } catch (PDOException $e) {
        error_log("Poll notifications admin error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Internal server error.']);
    }
} 
elseif ($role === 'delivery') {
    // Check if delivery boy is logged in
    if (!isset($_SESSION['delivery_boy_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $db_id = $_SESSION['delivery_boy_id'];
    
    try {
        // Count active assigned orders respecting restaurant boundaries
        $stmt = $conn->prepare("SELECT COUNT(*) as count, MAX(o.order_id) as latest_id 
                               FROM orders o 
                               JOIN users db ON o.delivery_boy_id = db.user_id
                               WHERE o.delivery_boy_id = ? 
                                 AND (db.restaurant_id IS NULL OR db.restaurant_id = 0 OR o.restaurant_id = db.restaurant_id)
                                 AND o.order_status IN ('confirmed', 'preparing', 'on the way')");
        $stmt->execute([$db_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'active_count' => intval($data['count']),
            'latest_order_id' => intval($data['latest_id'] ?? 0)
        ]);
    } catch (PDOException $e) {
        error_log("Poll notifications delivery error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Internal server error.']);
    }
} 
elseif ($role === 'restaurant') {
    // Check if restaurant owner is logged in
    if (!isset($_SESSION['restaurant_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $restaurant_id = $_SESSION['restaurant_id'];
    
    try {
        // Count pending orders for this specific restaurant
        $stmt = $conn->prepare("SELECT COUNT(*) as count, MAX(order_id) as latest_id FROM orders WHERE restaurant_id = ? AND order_status = 'pending'");
        $stmt->execute([$restaurant_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'pending_count' => intval($data['count']),
            'latest_order_id' => intval($data['latest_id'] ?? 0)
        ]);
    } catch (PDOException $e) {
        error_log("Poll notifications restaurant error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Internal server error.']);
    }
}
elseif ($role === 'customer') {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    try {
        // Find user's active orders (not delivered and not cancelled)
        $stmt = $conn->prepare("SELECT order_id, order_status FROM orders WHERE user_id = ? AND order_status NOT IN ('delivered', 'cancelled')");
        $stmt->execute([$user_id]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'orders' => $orders
        ]);
    } catch (PDOException $e) {
        error_log("Poll notifications customer error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Internal server error.']);
    }
}
else {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
}
?>