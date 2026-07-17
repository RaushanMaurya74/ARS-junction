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
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} 