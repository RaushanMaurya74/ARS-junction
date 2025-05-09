<?php
// Admin authentication functions

// Check if user is logged in as admin
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

// Admin login
function admin_login($email, $password) {
    global $conn;
    
    // In PostgreSQL, use TRUE for boolean values instead of 1
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_admin = TRUE");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin && password_verify($password, $admin['password'])) {
        // Password is correct, create admin session
        $_SESSION['admin_id'] = $admin['user_id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_email'] = $admin['email'];
        
        return $admin;
    }
    
    return false;
}

// Admin logout
function admin_logout() {
    // Unset admin session variables
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_name']);
    unset($_SESSION['admin_email']);
    
    // Redirect to admin login page
    header("Location: login.php");
    exit;
}

// Require admin login, redirect if not admin
function require_admin() {
    if (!is_admin_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

// Get admin details by ID
function get_admin_by_id($admin_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? AND is_admin = TRUE");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $admin ?: null;
}

// Get all orders for admin
function admin_get_all_orders($limit = 100, $offset = 0, $status = null) {
    global $conn;
    
    $sql = "SELECT o.*, u.name as user_name, r.name as restaurant_name 
           FROM orders o 
           JOIN users u ON o.user_id = u.user_id 
           JOIN restaurants r ON o.restaurant_id = r.restaurant_id ";
    
    if ($status !== null) {
        $sql .= "WHERE o.order_status = ? ";
    }
    
    $sql .= "ORDER BY o.order_date DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    
    if ($status !== null) {
        $stmt->bind_param("sii", $status, $limit, $offset);
    } else {
        $stmt->bind_param("ii", $limit, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = array();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    }
    return $orders;
}

// Count orders by status for admin
function admin_count_orders_by_status() {
    global $conn;
    $sql = "SELECT order_status, COUNT(*) as count FROM orders GROUP BY order_status";
    $result = $conn->query($sql);
    
    $status_counts = array();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $status_counts[$row['order_status']] = $row['count'];
        }
    }
    return $status_counts;
}

// Get all users for admin
function admin_get_all_users($limit = 100, $offset = 0) {
    global $conn;
    // In PostgreSQL, use FALSE for boolean values instead of 0
    $sql = "SELECT * FROM users WHERE is_admin = FALSE ORDER BY created_at DESC LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all restaurants for admin
function admin_get_all_restaurants($limit = 100, $offset = 0) {
    global $conn;
    $sql = "SELECT r.*, 
           (SELECT AVG(rating) FROM reviews WHERE restaurant_id = r.restaurant_id) as avg_rating,
           (SELECT COUNT(*) FROM reviews WHERE restaurant_id = r.restaurant_id) as review_count
           FROM restaurants r 
           ORDER BY r.name 
           LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Update order status for admin
function admin_update_order_status($order_id, $status) {
    global $conn;
    $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    
    if ($stmt->execute([$status, $order_id])) {
        return array('success' => true);
    } else {
        return array('success' => false, 'message' => 'Failed to update order status');
    }
}

// Add new restaurant for admin
function admin_add_restaurant($data) {
    global $conn;
    
    $name = clean_input($data['name']);
    $description = clean_input($data['description']);
    $address = clean_input($data['address']);
    $city = clean_input($data['city']);
    $state = clean_input($data['state']);
    $zip_code = clean_input($data['zip_code']);
    $phone = clean_input($data['phone']);
    $email = clean_input($data['email']);
    $delivery_time = intval($data['delivery_time']);
    $delivery_fee = floatval($data['delivery_fee']);
    $minimum_order = floatval($data['minimum_order']);
    
    $stmt = $conn->prepare("INSERT INTO restaurants (name, description, address, city, state, zip_code, phone, email, delivery_time, delivery_fee, minimum_order) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt->execute([$name, $description, $address, $city, $state, $zip_code, $phone, $email, $delivery_time, $delivery_fee, $minimum_order])) {
        return array('success' => true, 'restaurant_id' => $conn->lastInsertId());
    } else {
        return array('success' => false, 'message' => 'Failed to add restaurant');
    }
}

// Update restaurant for admin
function admin_update_restaurant($restaurant_id, $data) {
    global $conn;
    
    $name = clean_input($data['name']);
    $description = clean_input($data['description']);
    $address = clean_input($data['address']);
    $city = clean_input($data['city']);
    $state = clean_input($data['state']);
    $zip_code = clean_input($data['zip_code']);
    $phone = clean_input($data['phone']);
    $email = clean_input($data['email']);
    $delivery_time = intval($data['delivery_time']);
    $delivery_fee = floatval($data['delivery_fee']);
    $minimum_order = floatval($data['minimum_order']);
    // Use TRUE/FALSE for PostgreSQL boolean values
    $is_active = isset($data['is_active']) ? 'TRUE' : 'FALSE';
    
    $stmt = $conn->prepare("UPDATE restaurants 
                         SET name = ?, description = ?, address = ?, city = ?, state = ?, zip_code = ?, 
                             phone = ?, email = ?, delivery_time = ?, delivery_fee = ?, minimum_order = ?, is_active = ? 
                         WHERE restaurant_id = ?");
    
    if ($stmt->execute([$name, $description, $address, $city, $state, $zip_code, $phone, $email, $delivery_time, $delivery_fee, $minimum_order, $is_active, $restaurant_id])) {
        return array('success' => true);
    } else {
        return array('success' => false, 'message' => 'Failed to update restaurant');
    }
}

// Delete restaurant for admin
function admin_delete_restaurant($restaurant_id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM restaurants WHERE restaurant_id = ?");
    
    if ($stmt->execute([$restaurant_id])) {
        return array('success' => true);
    } else {
        return array('success' => false, 'message' => 'Failed to delete restaurant');
    }
}

// Get all menu items for admin
function admin_get_all_menu_items($limit = 100, $offset = 0, $restaurant_id = null) {
    global $conn;
    
    $sql = "SELECT m.*, r.name as restaurant_name, c.name as category_name 
           FROM menu_items m 
           JOIN restaurants r ON m.restaurant_id = r.restaurant_id 
           JOIN categories c ON m.category_id = c.category_id ";
    
    if ($restaurant_id !== null) {
        $sql .= "WHERE m.restaurant_id = ? ";
    }
    
    $sql .= "ORDER BY m.name 
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    
    if ($restaurant_id !== null) {
        $stmt->bind_param("iii", $restaurant_id, $limit, $offset);
    } else {
        $stmt->bind_param("ii", $limit, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = array();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }
    return $items;
}

// Add new menu item for admin
function admin_add_menu_item($data) {
    global $conn;
    
    $restaurant_id = intval($data['restaurant_id']);
    $category_id = intval($data['category_id']);
    $name = clean_input($data['name']);
    $description = clean_input($data['description']);
    $price = floatval($data['price']);
    $is_vegetarian = isset($data['is_vegetarian']) ? 1 : 0;
    $is_spicy = isset($data['is_spicy']) ? 1 : 0;
    $is_available = isset($data['is_available']) ? 1 : 0;
    $is_featured = isset($data['is_featured']) ? 1 : 0;
    
    $stmt = $conn->prepare("INSERT INTO menu_items (restaurant_id, category_id, name, description, price, is_vegetarian, is_spicy, is_available, is_featured) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissdiiii", $restaurant_id, $category_id, $name, $description, $price, $is_vegetarian, $is_spicy, $is_available, $is_featured);
    
    if ($stmt->execute()) {
        return array('success' => true, 'item_id' => $stmt->insert_id);
    } else {
        return array('success' => false, 'message' => 'Failed to add menu item: ' . $conn->error);
    }
}

// Update menu item for admin
function admin_update_menu_item($item_id, $data) {
    global $conn;
    
    $restaurant_id = intval($data['restaurant_id']);
    $category_id = intval($data['category_id']);
    $name = clean_input($data['name']);
    $description = clean_input($data['description']);
    $price = floatval($data['price']);
    $is_vegetarian = isset($data['is_vegetarian']) ? 1 : 0;
    $is_spicy = isset($data['is_spicy']) ? 1 : 0;
    $is_available = isset($data['is_available']) ? 1 : 0;
    $is_featured = isset($data['is_featured']) ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE menu_items 
                         SET restaurant_id = ?, category_id = ?, name = ?, description = ?, price = ?, 
                             is_vegetarian = ?, is_spicy = ?, is_available = ?, is_featured = ? 
                         WHERE item_id = ?");
    $stmt->bind_param("iissdiiiii", $restaurant_id, $category_id, $name, $description, $price, $is_vegetarian, $is_spicy, $is_available, $is_featured, $item_id);
    
    if ($stmt->execute()) {
        return array('success' => true);
    } else {
        return array('success' => false, 'message' => 'Failed to update menu item: ' . $conn->error);
    }
}

// Delete menu item for admin
function admin_delete_menu_item($item_id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM menu_items WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    
    if ($stmt->execute()) {
        return array('success' => true);
    } else {
        return array('success' => false, 'message' => 'Failed to delete menu item: ' . $conn->error);
    }
}

// Get all reviews for admin
function admin_get_all_reviews($limit = 100, $offset = 0) {
    global $conn;
    $sql = "SELECT r.*, u.name as user_name, res.name as restaurant_name 
           FROM reviews r 
           JOIN users u ON r.user_id = u.user_id 
           JOIN restaurants res ON r.restaurant_id = res.restaurant_id 
           ORDER BY r.created_at DESC 
           LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reviews = array();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }
    }
    return $reviews;
}

// Delete review for admin
function admin_delete_review($review_id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM reviews WHERE review_id = ?");
    $stmt->bind_param("i", $review_id);
    
    if ($stmt->execute()) {
        return array('success' => true);
    } else {
        return array('success' => false, 'message' => 'Failed to delete review: ' . $conn->error);
    }
}
?>
