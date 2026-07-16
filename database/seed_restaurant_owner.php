<?php
/**
 * Seed Demo Restaurant Owner and assign Delivery Boy
 */

require_once __DIR__ . '/../includes/db_connect.php';

if (!isset($conn)) {
    die("Database connection failed.\n");
}

try {
    // 1. Create or update manager@arsjunction.com
    $email = 'manager@arsjunction.com';
    $password = 'password';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user_id = $stmt->fetchColumn();
    
    if ($user_id) {
        $stmt_up = $conn->prepare("UPDATE users SET is_restaurant_owner = 1, restaurant_id = 1, password = ? WHERE user_id = ?");
        $stmt_up->execute([$hashed_password, $user_id]);
        echo "Updated existing user to be Restaurant Owner for Pizza Paradise (ID: 1).\n";
    } else {
        $stmt_in = $conn->prepare("INSERT INTO users (name, email, password, phone, address, city, state, zip_code, is_restaurant_owner, restaurant_id) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 1)");
        $stmt_in->execute(['Pizza Paradise Manager', $email, $hashed_password, '9876543210', '123 Pizza Street', 'Piro', 'Bihar', '802207']);
        echo "Created new user manager@arsjunction.com as Restaurant Owner for Pizza Paradise (ID: 1).\n";
    }

    // 2. Assign delivery boy aniket (user_id = 4) to Pizza Paradise (restaurant_id = 1)
    $stmt_boy = $conn->prepare("UPDATE users SET restaurant_id = 1 WHERE user_id = 4 AND is_delivery_boy = 1");
    $stmt_boy->execute();
    echo "Assigned delivery boy aniket (ID: 4) to Pizza Paradise (ID: 1).\n";

    echo "Seeding completed successfully!\n";

} catch (Exception $e) {
    die("Seeding failed: " . $e->getMessage() . "\n");
}
?>
