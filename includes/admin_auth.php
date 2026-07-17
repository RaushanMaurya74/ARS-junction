<?php
// Admin authentication functions

// Check if user is logged in as admin
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

// Admin login
function admin_login($email, $password) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_admin = 1");
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
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? AND is_admin = 1");
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
        $sql .= "WHERE o.order_status = :status ";
    }
    
    $sql .= "ORDER BY o.order_date DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($sql);
    if ($status !== null) {
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Count orders by status for admin
function admin_count_orders_by_status() {
    global $conn;
    $sql = "SELECT order_status, COUNT(*) as count FROM orders GROUP BY order_status";
    $stmt = $conn->query($sql);

    $status_counts = array();
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status_counts[$row['order_status']] = $row['count'];
    }
    return $status_counts;
}

// Get all users for admin
function admin_get_all_users($limit = 100, $offset = 0) {
    global $conn;
    $sql = "SELECT user_id, name, email, phone, address, city, state, zip_code, social_id, social_type, is_admin, is_delivery_boy, is_online, created_at, updated_at, is_restaurant_owner, restaurant_id FROM users WHERE is_admin = 0 ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
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
           LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Update order status for admin
function admin_update_order_status($order_id, $status) {
    global $conn;
    $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    
    if ($stmt->execute([$status, $order_id])) {
        // Trigger email if the status has transitioned to 'confirmed'
        if ($status === 'confirmed') {
            $stmt_time = $conn->prepare("UPDATE orders SET confirmed_at = NOW() WHERE order_id = ?");
            $stmt_time->execute([$order_id]);
            send_order_confirmation_email($order_id);
        }
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
    $image = isset($data['image']) ? clean_input($data['image']) : '';
    
    $stmt = $conn->prepare("INSERT INTO restaurants (name, description, address, city, state, zip_code, phone, email, delivery_time, delivery_fee, minimum_order, image) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt->execute([$name, $description, $address, $city, $state, $zip_code, $phone, $email, $delivery_time, $delivery_fee, $minimum_order, $image])) {
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
    $is_active = isset($data['is_active']) ? 1 : 0;
    $image = isset($data['image']) ? clean_input($data['image']) : '';
    
    if (!empty($image)) {
        $stmt = $conn->prepare("UPDATE restaurants 
                             SET name = ?, description = ?, address = ?, city = ?, state = ?, zip_code = ?, 
                                 phone = ?, email = ?, delivery_time = ?, delivery_fee = ?, minimum_order = ?, is_active = ?, image = ? 
                             WHERE restaurant_id = ?");
        $success = $stmt->execute([$name, $description, $address, $city, $state, $zip_code, $phone, $email, $delivery_time, $delivery_fee, $minimum_order, $is_active, $image, $restaurant_id]);
    } else {
        $stmt = $conn->prepare("UPDATE restaurants 
                             SET name = ?, description = ?, address = ?, city = ?, state = ?, zip_code = ?, 
                                 phone = ?, email = ?, delivery_time = ?, delivery_fee = ?, minimum_order = ?, is_active = ? 
                             WHERE restaurant_id = ?");
        $success = $stmt->execute([$name, $description, $address, $city, $state, $zip_code, $phone, $email, $delivery_time, $delivery_fee, $minimum_order, $is_active, $restaurant_id]);
    }
    
    if ($success) {
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
        $sql .= "WHERE m.restaurant_id = :restaurant_id ";
    }
    
    $sql .= "ORDER BY m.name 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($sql);
    if ($restaurant_id !== null) {
        $stmt->bindValue(':restaurant_id', (int)$restaurant_id, PDO::PARAM_INT);
    }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Add new menu item for admin
function admin_add_menu_item($data) {
    global $conn;
    
    $restaurant_id = intval($data['restaurant_id']);
    $category_id = intval($data['category_id']);
    $name = clean_input($data['name']);
    $description = clean_input($data['description']);
    $price = floatval($data['price']);
    $is_vegetarian = (isset($data['is_vegetarian']) && intval($data['is_vegetarian']) == 1) ? 1 : 0;
    $is_spicy = isset($data['is_spicy']) ? 1 : 0;
    $is_available = isset($data['is_available']) ? 1 : 0;
    $is_featured = isset($data['is_featured']) ? 1 : 0;
    $image = isset($data['image']) ? clean_input($data['image']) : '';
    
    $stmt = $conn->prepare("INSERT INTO menu_items (restaurant_id, category_id, name, description, price, is_vegetarian, is_spicy, is_available, is_featured, image) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt->execute([$restaurant_id, $category_id, $name, $description, $price, $is_vegetarian, $is_spicy, $is_available, $is_featured, $image])) {
        return array('success' => true, 'item_id' => $conn->lastInsertId());
    } else {
        return array('success' => false, 'message' => 'Failed to add menu item');
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
    $is_vegetarian = (isset($data['is_vegetarian']) && intval($data['is_vegetarian']) == 1) ? 1 : 0;
    $is_spicy = isset($data['is_spicy']) ? 1 : 0;
    $is_available = isset($data['is_available']) ? 1 : 0;
    $is_featured = isset($data['is_featured']) ? 1 : 0;
    $image = isset($data['image']) ? clean_input($data['image']) : '';
    
    if (!empty($image)) {
        $stmt = $conn->prepare("UPDATE menu_items 
                             SET restaurant_id = ?, category_id = ?, name = ?, description = ?, price = ?, 
                                 is_vegetarian = ?, is_spicy = ?, is_available = ?, is_featured = ?, image = ? 
                             WHERE item_id = ?");
        $success = $stmt->execute([$restaurant_id, $category_id, $name, $description, $price, $is_vegetarian, $is_spicy, $is_available, $is_featured, $image, $item_id]);
    } else {
        $stmt = $conn->prepare("UPDATE menu_items 
                             SET restaurant_id = ?, category_id = ?, name = ?, description = ?, price = ?, 
                                 is_vegetarian = ?, is_spicy = ?, is_available = ?, is_featured = ? 
                             WHERE item_id = ?");
        $success = $stmt->execute([$restaurant_id, $category_id, $name, $description, $price, $is_vegetarian, $is_spicy, $is_available, $is_featured, $item_id]);
    }
    
    if ($success) {
        return array('success' => true);
    } else {
        return array('success' => false, 'message' => 'Failed to update menu item');
    }
}

// Delete menu item for admin
function admin_delete_menu_item($item_id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM menu_items WHERE item_id = ?");
    
    if ($stmt->execute([$item_id])) {
        return array('success' => true);
    } else {
        return array('success' => false, 'message' => 'Failed to delete menu item');
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
           LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Delete review for admin
function admin_delete_review($review_id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM reviews WHERE review_id = ?");
    
    if ($stmt->execute([$review_id])) {
        return array('success' => true);
    } else {
        return array('success' => false, 'message' => 'Failed to delete review');
    }
}

// Add new user for admin
function admin_add_user($data) {
    global $conn;
    
    $name = clean_input($data['name']);
    $email = clean_input($data['email']);
    $password = $data['password'];
    $phone = clean_input($data['phone']);
    $address = clean_input($data['address']);
    $city = clean_input($data['city']);
    $state = clean_input($data['state']);
    $zip_code = clean_input($data['zip_code']);
    $is_admin = isset($data['is_admin']) ? 1 : 0;
    $is_delivery_boy = isset($data['is_delivery_boy']) ? 1 : 0;
    $is_restaurant_owner = isset($data['is_restaurant_owner']) ? 1 : 0;
    $restaurant_id = (isset($data['restaurant_id']) && $data['restaurant_id'] !== '') ? intval($data['restaurant_id']) : null;
    
    if (empty($name) || empty($email) || empty($password)) {
        return array('success' => false, 'message' => 'Please fill in all required fields.');
    }
    
    if (email_exists($email)) {
        return array('success' => false, 'message' => 'Email already exists.');
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, address, city, state, zip_code, is_admin, is_delivery_boy, is_restaurant_owner, restaurant_id) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt->execute([$name, $email, $hashed_password, $phone, $address, $city, $state, $zip_code, $is_admin, $is_delivery_boy, $is_restaurant_owner, $restaurant_id])) {
        return array('success' => true);
    } else {
        return array('success' => false, 'message' => 'Failed to add user.');
    }
}

// Update user for admin
function admin_update_user($user_id, $data) {
    global $conn;
    
    $name = clean_input($data['name']);
    $email = clean_input($data['email']);
    $phone = clean_input($data['phone']);
    $address = clean_input($data['address']);
    $city = clean_input($data['city']);
    $state = clean_input($data['state']);
    $zip_code = clean_input($data['zip_code']);
    
    $is_admin = isset($data['is_admin']) ? 1 : 0;
    $is_delivery_boy = isset($data['is_delivery_boy']) ? 1 : 0;
    $is_restaurant_owner = isset($data['is_restaurant_owner']) ? 1 : 0;
    $restaurant_id = (isset($data['restaurant_id']) && $data['restaurant_id'] !== '') ? intval($data['restaurant_id']) : null;
    
    if (empty($name) || empty($email)) {
        return array('success' => false, 'message' => 'Please fill in all required fields.');
    }
    
    // Check if email exists for another user
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        return array('success' => false, 'message' => 'Email is already taken by another user.');
    }
    
    // If password is provided, update it
    if (!empty($data['password'])) {
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ?, phone = ?, address = ?, city = ?, state = ?, zip_code = ?, is_admin = ?, is_delivery_boy = ?, is_restaurant_owner = ?, restaurant_id = ? WHERE user_id = ?");
        $params = [$name, $email, $hashed_password, $phone, $address, $city, $state, $zip_code, $is_admin, $is_delivery_boy, $is_restaurant_owner, $restaurant_id, $user_id];
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, city = ?, state = ?, zip_code = ?, is_admin = ?, is_delivery_boy = ?, is_restaurant_owner = ?, restaurant_id = ? WHERE user_id = ?");
        $params = [$name, $email, $phone, $address, $city, $state, $zip_code, $is_admin, $is_delivery_boy, $is_restaurant_owner, $restaurant_id, $user_id];
    }
    
    if ($stmt->execute($params)) {
        return array('success' => true);
    } else {
        return array('success' => false, 'message' => 'Failed to update user.');
    }
}


// Delete user for admin
function admin_delete_user($user_id) {
    global $conn;
    // Don't delete admin users
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND is_admin = 0");
    
    if ($stmt->execute([$user_id])) {
        return array('success' => true);
    } else {
        return array('success' => false, 'message' => 'Failed to delete user.');
    }
}

// Update payment status for admin
function admin_update_payment_status($order_id, $payment_status) {
    global $conn;
    $stmt = $conn->prepare("UPDATE orders SET payment_status = ? WHERE order_id = ?");
    
    if ($stmt->execute([$payment_status, $order_id])) {
        return array('success' => true);
    } else {
        return array('success' => false, 'message' => 'Failed to update payment status');
    }
}

// Get all delivery boys for admin
function admin_get_delivery_boys() {
    global $conn;
    $stmt = $conn->prepare("SELECT user_id, name, email, phone, address, city, state, zip_code, is_online, latitude, longitude, location_updated_at, restaurant_id, created_at FROM users WHERE is_delivery_boy = 1 ORDER BY name");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Assign delivery boy to order for admin
function admin_assign_delivery_boy($order_id, $delivery_boy_id) {
    global $conn;
    
    // If delivery_boy_id is 0, set to NULL
    $db_id = ($delivery_boy_id > 0) ? $delivery_boy_id : null;
    
    $stmt = $conn->prepare("UPDATE orders SET delivery_boy_id = ? WHERE order_id = ?");
    
    if ($stmt->execute([$db_id, $order_id])) {
        // Automatically transition order_status to 'confirmed' if it was 'pending' and boy assigned
        $stmt_status = $conn->prepare("SELECT order_status FROM orders WHERE order_id = ?");
        $stmt_status->execute([$order_id]);
        $ord = $stmt_status->fetch();
        if ($ord && $ord['order_status'] === 'pending' && $db_id !== null) {
            $stmt_up = $conn->prepare("UPDATE orders SET order_status = 'confirmed', confirmed_at = NOW() WHERE order_id = ?");
            if ($stmt_up->execute([$order_id])) {
                send_order_confirmation_email($order_id);
            }
        }
        return array('success' => true);
    } else {
        return array('success' => false, 'message' => 'Failed to assign delivery boy');
    }
}
?>
