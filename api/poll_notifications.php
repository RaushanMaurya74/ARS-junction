<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$role = isset($_GET['role']) ? clean_input($_GET['role']) : '';

if ($role === 'admin') {
    // Check if admin is logged in
    if (!isset($_SESSION['admin_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    try {
        // Trigger auto-assignment of confirmed orders
        auto_assign_confirmed_orders();

        // Count pending orders
        $stmt = $conn->query("SELECT COUNT(*) as count, MAX(order_id) as latest_id FROM orders WHERE order_status = 'pending'");
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'pending_count' => intval($data['count']),
            'latest_order_id' => intval($data['latest_id'] ?? 0)
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
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
        // Count active assigned orders (status: confirmed, preparing, on the way)
        $stmt = $conn->prepare("SELECT COUNT(*) as count, MAX(order_id) as latest_id FROM orders WHERE delivery_boy_id = ? AND order_status IN ('confirmed', 'preparing', 'on the way')");
        $stmt->execute([$db_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'active_count' => intval($data['count']),
            'latest_order_id' => intval($data['latest_id'] ?? 0)
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} 
else {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
}
?>
