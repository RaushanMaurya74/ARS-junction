<?php
// Contains utility functions for the application

// Clean input data to prevent SQL injection and XSS attacks
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Format currency with Indian Rupee symbol
function format_price($price) {
    return '₹' . number_format($price, 2);
}

// Get user details by ID
function get_user_by_id($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch();
}

// Get restaurant details by ID
function get_restaurant_by_id($restaurant_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM restaurants WHERE restaurant_id = :restaurant_id");
    $stmt->bindParam(':restaurant_id', $restaurant_id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch();
}

// Get menu item details by ID
function get_menu_item_by_id($item_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT m.*, r.name as restaurant_name, c.name as category_name 
                          FROM menu_items m 
                          JOIN restaurants r ON m.restaurant_id = r.restaurant_id 
                          JOIN categories c ON m.category_id = c.category_id 
                          WHERE m.item_id = :item_id");
    $stmt->bindParam(':item_id', $item_id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch();
}

// Get all categories
function get_all_categories() {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM categories ORDER BY name");
    $stmt->execute();
    
    return $stmt->fetchAll();
}

// Get featured restaurants
function get_featured_restaurants($limit = 4) {
    global $conn;
    $sql = "SELECT r.*, 
           (SELECT AVG(rating) FROM reviews WHERE restaurant_id = r.restaurant_id) as avg_rating,
           (SELECT COUNT(*) FROM reviews WHERE restaurant_id = r.restaurant_id) as review_count
           FROM restaurants r 
           WHERE r.is_active = TRUE 
           ORDER BY avg_rating DESC, review_count DESC 
           LIMIT :limit";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

// Get featured menu items
function get_featured_menu_items($limit = 8) {
    global $conn;
    $sql = "SELECT m.*, r.name as restaurant_name 
           FROM menu_items m 
           JOIN restaurants r ON m.restaurant_id = r.restaurant_id 
           WHERE m.is_featured = TRUE AND m.is_available = TRUE AND r.is_active = TRUE 
           ORDER BY RANDOM() 
           LIMIT :limit";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

// Get restaurant menu items by category
function get_restaurant_menu_by_category($restaurant_id) {
    global $conn;
    
    // First get all categories that have items for this restaurant
    $sql = "SELECT DISTINCT c.* 
           FROM categories c 
           JOIN menu_items m ON c.category_id = m.category_id 
           WHERE m.restaurant_id = :restaurant_id AND m.is_available = TRUE 
           ORDER BY c.name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':restaurant_id', $restaurant_id, PDO::PARAM_INT);
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    $menu_by_category = array();
    
    foreach ($categories as $category) {
        // For each category, get all menu items
        $sql = "SELECT * FROM menu_items 
               WHERE restaurant_id = :restaurant_id AND category_id = :category_id AND is_available = TRUE 
               ORDER BY name";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':restaurant_id', $restaurant_id, PDO::PARAM_INT);
        $stmt->bindParam(':category_id', $category['category_id'], PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll();
        
        if (!empty($items)) {
            $menu_by_category[] = array(
                'category' => $category,
                'items' => $items
            );
        }
    }
    
    return $menu_by_category;
}

// Get restaurant reviews
function get_restaurant_reviews($restaurant_id, $limit = 5) {
    global $conn;
    $sql = "SELECT r.*, u.name as user_name 
           FROM reviews r 
           JOIN users u ON r.user_id = u.user_id 
           WHERE r.restaurant_id = :restaurant_id 
           ORDER BY r.created_at DESC 
           LIMIT :limit";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':restaurant_id', $restaurant_id, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

// Get user orders
function get_user_orders($user_id) {
    global $conn;
    $sql = "SELECT o.*, r.name as restaurant_name 
           FROM orders o 
           JOIN restaurants r ON o.restaurant_id = r.restaurant_id 
           WHERE o.user_id = :user_id 
           ORDER BY o.order_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

// Get order details with items
function get_order_details($order_id, $user_id = null) {
    global $conn;
    
    // Get order header
    $sql = "SELECT o.*, r.name as restaurant_name 
           FROM orders o 
           JOIN restaurants r ON o.restaurant_id = r.restaurant_id 
           WHERE o.order_id = :order_id";
    
    if ($user_id !== null) {
        $sql .= " AND o.user_id = :user_id";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    
    if ($user_id !== null) {
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $order = $stmt->fetch();
    
    if (!$order) {
        return null;
    }
    
    // Get order items
    $sql = "SELECT oi.*, m.name, m.description 
           FROM order_items oi 
           JOIN menu_items m ON oi.item_id = m.item_id 
           WHERE oi.order_id = :order_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $order['items'] = $stmt->fetchAll();
    return $order;
}

// Check if user has already reviewed a restaurant
function has_user_reviewed($user_id, $restaurant_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE user_id = :user_id AND restaurant_id = :restaurant_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':restaurant_id', $restaurant_id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();
    
    return $row['count'] > 0;
}

// Get user cart items
function get_cart_items($user_id) {
    global $conn;
    $sql = "SELECT c.*, m.name, m.price, m.image, r.name as restaurant_name, r.restaurant_id 
           FROM cart c 
           JOIN menu_items m ON c.item_id = m.item_id 
           JOIN restaurants r ON m.restaurant_id = r.restaurant_id 
           WHERE c.user_id = :user_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

// Calculate cart total
function calculate_cart_total($user_id) {
    global $conn;
    $sql = "SELECT SUM(m.price * c.quantity) as total 
           FROM cart c 
           JOIN menu_items m ON c.item_id = m.item_id 
           WHERE c.user_id = :user_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();
    
    return $row['total'] ? $row['total'] : 0;
}

// Check if user's cart has items from multiple restaurants
function is_cart_from_multiple_restaurants($user_id) {
    global $conn;
    $sql = "SELECT COUNT(DISTINCT r.restaurant_id) as restaurant_count 
           FROM cart c 
           JOIN menu_items m ON c.item_id = m.item_id 
           JOIN restaurants r ON m.restaurant_id = r.restaurant_id 
           WHERE c.user_id = :user_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();
    
    return $row['restaurant_count'] > 1;
}

// Generate a short random reference number for orders
function generate_order_reference() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $ref = '';
    for ($i = 0; $i < 8; $i++) {
        $ref .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $ref;
}

// Check if email exists
function email_exists($email) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch();
    
    return $row['count'] > 0;
}

// Generate random password
function generate_random_password($length = 10) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

// For admin dashboard - count total orders
function count_total_orders() {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders");
    $stmt->execute();
    $row = $stmt->fetch();
    return $row['count'];
}

// For admin dashboard - count total users
function count_total_users() {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE is_admin = FALSE");
    $stmt->execute();
    $row = $stmt->fetch();
    return $row['count'];
}

// For admin dashboard - count total restaurants
function count_total_restaurants() {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM restaurants");
    $stmt->execute();
    $row = $stmt->fetch();
    return $row['count'];
}

// For admin dashboard - calculate total revenue
function calculate_total_revenue() {
    global $conn;
    $stmt = $conn->prepare("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid'");
    $stmt->execute();
    $row = $stmt->fetch();
    return $row['total'] ? $row['total'] : 0;
}

// For admin dashboard - get recent orders
function get_recent_orders($limit = 5) {
    global $conn;
    $sql = "SELECT o.*, u.name as user_name, r.name as restaurant_name 
           FROM orders o 
           JOIN users u ON o.user_id = u.user_id 
           JOIN restaurants r ON o.restaurant_id = r.restaurant_id 
           ORDER BY o.order_date DESC 
           LIMIT :limit";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}
?>
