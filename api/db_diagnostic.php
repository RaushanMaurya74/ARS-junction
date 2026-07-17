<?php
require_once __DIR__ . '/../includes/db_connect.php';

header('Content-Type: text/plain');

echo "=== DELIVERY BOYS ===\n";
$stmt = $conn->prepare("SELECT user_id, name, email, restaurant_id FROM users WHERE is_delivery_boy = 1");
$stmt->execute();
$boys = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($boys);

echo "\n=== RECENT ORDERS ===\n";
$stmt = $conn->prepare("SELECT order_id, restaurant_id, delivery_boy_id, order_status, payment_status FROM orders ORDER BY order_id DESC LIMIT 10");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($orders);
?>
