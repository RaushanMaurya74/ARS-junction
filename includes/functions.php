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
    return '&#8377;' . number_format((float)$price, 2);
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
           ORDER BY RAND() 
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
    $sql = "SELECT o.*, r.name as restaurant_name, u.name as customer_name, u.email as customer_email 
           FROM orders o 
           JOIN restaurants r ON o.restaurant_id = r.restaurant_id 
           JOIN users u ON o.user_id = u.user_id
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

// Register a customer and log them in immediately.
function register_user($name, $email, $password, $phone = '') {
    global $conn;

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, social_type, is_admin) VALUES (?, ?, ?, ?, 'normal', 0)");
        $stmt->execute([$name, $email, $hashed_password, $phone]);
        $user_id = $conn->lastInsertId();

        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['is_admin'] = 0;

        return ['success' => true, 'user_id' => $user_id];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

// Update profile fields editable by customers.
function update_user_profile($user_id, $data) {
    global $conn;

    try {
        $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, address = ?, city = ?, state = ?, zip_code = ? WHERE user_id = ?");
        $stmt->execute([
            $data['name'],
            $data['phone'],
            $data['address'],
            $data['city'],
            $data['state'],
            $data['zip_code'],
            $user_id
        ]);

        $_SESSION['user_name'] = $data['name'];
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Profile update failed. Please try again.'];
    }
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
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE is_admin = 0");
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

// Get all orders assigned to a delivery boy
function get_delivery_boy_orders($delivery_boy_id) {
    global $conn;
    $sql = "SELECT o.*, u.name as customer_name, r.name as restaurant_name 
           FROM orders o 
           JOIN users u ON o.user_id = u.user_id 
           JOIN restaurants r ON o.restaurant_id = r.restaurant_id 
           WHERE o.delivery_boy_id = :delivery_boy_id 
           ORDER BY o.order_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':delivery_boy_id', $delivery_boy_id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get a site setting by key
function get_site_setting($key, $default = '') {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return ($value !== false) ? $value : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

// Update a site setting by key
function update_site_setting($key, $value) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) 
                          ON DUPLICATE KEY UPDATE setting_value = ?");
    return $stmt->execute([$key, $value, $value]);
}

// Send professional order confirmation email to the buyer
function send_order_confirmation_email($order_id) {
    // Get full order details (which now includes customer_name and customer_email)
    $order = get_order_details($order_id);
    if (!$order) {
        return false;
    }

    $site_name = get_site_setting('site_name', 'ARS Junction');
    $site_email = get_site_setting('site_email', 'officialarsjunction@gmail.com');
    $site_phone = get_site_setting('site_phone', '7979730721');
    $site_location = get_site_setting('site_location', 'AT - PIRO, BHOJPUR, BIHAR, INDIA-802207');

    $buyer_name = htmlspecialchars($order['customer_name']);
    $buyer_email = htmlspecialchars($order['customer_email']);
    $order_date = date('F d, Y h:i A', strtotime($order['order_date']));
    $payment_method = ($order['payment_method'] === 'upi') ? 'UPI QR Code' : 'Cash on Delivery';
    
    // Build HTML email template
    $htmlContent = "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Order Confirmation #{$order_id}</title>
        <style>
            body { font-family: 'Segoe UI', Helvetica, Arial, sans-serif; background-color: #f6f9fc; margin: 0; padding: 0; color: #333333; }
            .email-container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border: 1px solid #e1e6eb; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
            .header { background: linear-gradient(135deg, #FF5722 0%, #E64A19 100%); color: #ffffff; padding: 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 0.5px; }
            .content { padding: 30px; }
            .greeting { font-size: 18px; font-weight: 600; margin-top: 0; color: #111111; }
            .order-summary { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .order-summary th { text-align: left; padding: 12px; border-bottom: 2px solid #eef2f5; font-weight: 600; color: #666; font-size: 13px; text-transform: uppercase; }
            .order-summary td { padding: 12px; border-bottom: 1px solid #f1f4f7; font-size: 14px; }
            .order-summary tr:last-child td { border-bottom: none; }
            .totals-table { width: 100%; margin-top: 15px; border-top: 2px dashed #eef2f5; padding-top: 15px; }
            .totals-table td { padding: 5px 0; font-size: 14px; }
            .totals-table .grand-total { font-size: 18px; font-weight: bold; color: #FF5722; padding-top: 10px; }
            .details-card { background-color: #f8f9fa; border-radius: 6px; padding: 15px; margin: 20px 0; font-size: 13.5px; line-height: 1.5; border: 1px solid #eaedf1; }
            .details-card h4 { margin: 0 0 10px 0; color: #E64A19; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
            .footer { background-color: #f1f4f7; text-align: center; padding: 20px; font-size: 12px; color: #666666; border-top: 1px solid #e1e6eb; }
            .btn { display: inline-block; padding: 12px 24px; background-color: #FF5722; color: #ffffff !important; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 14px; margin-top: 15px; }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <!-- Header -->
            <div class='header'>
                <h1>Order Confirmed!</h1>
                <p style='margin: 5px 0 0 0; font-size: 14px; opacity: 0.9;'>Thank you for ordering with {$site_name}</p>
            </div>
            
            <!-- Body -->
            <div class='content'>
                <p class='greeting'>Dear {$buyer_name},</p>
                <p>We are delighted to confirm that your order <strong>#{$order_id}</strong> from <strong>" . htmlspecialchars($order['restaurant_name']) . "</strong> has been accepted by the restaurant and is currently being prepared! 🍳😋</p>
                
                <h3 style='font-size: 15px; border-bottom: 2px solid #FF5722; padding-bottom: 5px; color: #111;'>Order Summary</h3>
                <table class='order-summary'>
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th style='text-align: center;'>Qty</th>
                            <th style='text-align: right;'>Price</th>
                            <th style='text-align: right;'>Total</th>
                        </tr>
                    </thead>
                    <tbody>";
                    
                    foreach ($order['items'] as $item) {
                        $item_total = $item['price'] * $item['quantity'];
                        $htmlContent .= "
                        <tr>
                            <td>" . htmlspecialchars($item['name']) . "</td>
                            <td style='text-align: center;'>{$item['quantity']}</td>
                            <td style='text-align: right;'>&#8377;" . number_format($item['price'], 2) . "</td>
                            <td style='text-align: right;'>&#8377;" . number_format($item_total, 2) . "</td>
                        </tr>";
                    }
                    
                    $subtotal = number_format($order['subtotal'], 2);
                    $delivery_fee = number_format($order['delivery_fee'], 2);
                    $tax = number_format($order['tax'], 2);
                    $grand_total = number_format($order['total_amount'], 2);

    $htmlContent .= "
                    </tbody>
                </table>
                
                <!-- Calculations -->
                <table class='totals-table'>
                    <tr>
                        <td style='color: #666;'>Subtotal:</td>
                        <td style='text-align: right;'>&#8377;{$subtotal}</td>
                    </tr>
                    <tr>
                        <td style='color: #666;'>Delivery Fee:</td>
                        <td style='text-align: right;'>&#8377;{$delivery_fee}</td>
                    </tr>
                    <tr>
                        <td style='color: #666;'>Tax (5%):</td>
                        <td style='text-align: right;'>&#8377;{$tax}</td>
                    </tr>
                    <tr class='grand-total'>
                        <td>Grand Total:</td>
                        <td style='text-align: right;'>&#8377;{$grand_total}</td>
                    </tr>
                </table>
                
                <!-- Details grid -->
                <div class='details-card'>
                    <h4>Delivery Details</h4>
                    <p style='margin: 0 0 5px 0;'><strong>Address:</strong> " . htmlspecialchars($order['delivery_address']) . "</p>
                    <p style='margin: 0;'><strong>Phone:</strong> +91 " . htmlspecialchars($order['delivery_phone']) . "</p>
                </div>
                
                <div class='details-card'>
                    <h4>Payment Information</h4>
                    <p style='margin: 0 0 5px 0;'><strong>Method:</strong> {$payment_method}</p>
                    <p style='margin: 0;'><strong>Status:</strong> " . ucfirst($order['payment_status']) . "</p>
                </div>

                <div style='text-align: center;'>
                    <a href='" . get_site_setting('site_url', 'http://localhost/ARS/') . "order_tracking.php?id={$order_id}' class='btn'>Track Your Order Live</a>
                </div>
            </div>
            
            <!-- Footer -->
            <div class='footer'>
                <p style='margin: 0 0 5px 0;'><strong>{$site_name} Support</strong></p>
                <p style='margin: 0 0 10px 0;'>{$site_location} | Phone: +91 {$site_phone}</p>
                <p style='margin: 0; font-size: 11px; color: #999999;'>This is a system generated order notification email. Please do not reply directly to this mail.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    // 1. Write the HTML preview file for local verification
    $email_dir = dirname(__DIR__) . '/uploads/emails';
    if (!file_exists($email_dir)) {
        @mkdir($email_dir, 0777, true);
    }
    $preview_file = $email_dir . "/order_confirm_{$order_id}.html";
    @file_put_contents($preview_file, $htmlContent);

    // 2. Trigger the actual PHP mail() function
    $to = $buyer_email;
    $subject = "Order Confirmed #{$order_id} - {$site_name}";
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: {$site_name} <noreply@arsjunction.com>" . "\r\n";
    $headers .= "Reply-To: {$site_email}" . "\r\n";

    return @mail($to, $subject, $htmlContent, $headers);
}

// Automatically assign confirmed orders to online delivery boys after 3 minutes
function auto_assign_confirmed_orders() {
    global $conn;
    
    // 1. Find all online delivery boys
    $stmt_boys = $conn->query("SELECT user_id FROM users WHERE role = 'delivery' AND is_online = 1");
    $online_boys = $stmt_boys->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($online_boys)) {
        return; // No online delivery boys to assign to
    }
    
    // 2. Find all orders that are 'confirmed', have NO delivery boy assigned, and were confirmed at least 3 minutes ago
    // We check: confirmed_at IS NOT NULL AND confirmed_at <= NOW() - INTERVAL 3 MINUTE
    $stmt_orders = $conn->query("SELECT order_id FROM orders 
                                 WHERE order_status = 'confirmed' 
                                 AND delivery_boy_id IS NULL 
                                 AND confirmed_at IS NOT NULL 
                                 AND confirmed_at <= NOW() - INTERVAL 3 MINUTE");
    $unassigned_orders = $stmt_orders->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($unassigned_orders)) {
        return; // No orders waiting for auto-assignment
    }
    
    // 3. Assign orders to online delivery boys using a load-balancing strategy:
    // For each order, we find the online delivery boy who currently has the fewest active orders assigned to them!
    foreach ($unassigned_orders as $order_id) {
        $min_orders = null;
        $best_boy_id = null;
        
        foreach ($online_boys as $boy_id) {
            $stmt_count = $conn->prepare("SELECT COUNT(*) FROM orders 
                                         WHERE delivery_boy_id = ? 
                                         AND order_status IN ('confirmed', 'preparing', 'on the way')");
            $stmt_count->execute([$boy_id]);
            $active_count = $stmt_count->fetchColumn();
            
            if ($min_orders === null || $active_count < $min_orders) {
                $min_orders = $active_count;
                $best_boy_id = $boy_id;
            }
        }
        
        if ($best_boy_id !== null) {
            // Assign order to the best boy
            $stmt_assign = $conn->prepare("UPDATE orders SET delivery_boy_id = ? WHERE order_id = ?");
            $stmt_assign->execute([$best_boy_id, $order_id]);
        }
    }
}
?>
