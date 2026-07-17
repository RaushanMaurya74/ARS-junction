// Get all orders assigned to a delivery boy (restricted by restaurant boundary)
function get_delivery_boy_orders($delivery_boy_id) {
    global $conn;
    $sql = "SELECT o.*, u.name as customer_name, r.name as restaurant_name 
           FROM orders o 
           JOIN users u ON o.user_id = u.user_id 
           JOIN restaurants r ON o.restaurant_id = r.restaurant_id 
           JOIN users db ON o.delivery_boy_id = db.user_id
           WHERE o.delivery_boy_id = :delivery_boy_id 
             AND (db.restaurant_id IS NULL OR db.restaurant_id = 0 OR o.restaurant_id = db.restaurant_id)
           ORDER BY o.order_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':delivery_boy_id', $delivery_boy_id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
