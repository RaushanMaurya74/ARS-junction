<?php
/**
 * Smart Search Suggestions
 * GET ?q=keyword
 * Returns JSON array: [{ item_id, name, price, image, restaurant_name, category_name, is_vegetarian }]
 */
chdir(__DIR__);
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$q = isset($_GET['q']) ? trim(clean_input($_GET['q'])) : '';

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $search = '%' . $q . '%';
    $stmt = $conn->prepare(
        "SELECT mi.item_id, mi.name, mi.price, mi.image, mi.is_vegetarian, mi.is_spicy,
                r.name AS restaurant_name,
                c.name AS category_name
         FROM menu_items mi
         JOIN restaurants r ON mi.restaurant_id = r.restaurant_id
         JOIN categories c ON mi.category_id = c.category_id
         WHERE mi.is_available = 1
           AND r.is_active = 1
           AND (mi.name LIKE ? OR mi.description LIKE ? OR c.name LIKE ?)
         ORDER BY mi.is_featured DESC, mi.name ASC
         LIMIT 8"
    );
    $stmt->execute([$search, $search, $search]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format price and image
    foreach ($results as &$row) {
        $row['price']        = floatval($row['price']);
        $row['is_vegetarian'] = (bool)$row['is_vegetarian'];
        $row['is_spicy']     = (bool)$row['is_spicy'];
        // Image path — use placeholder if null
        $row['image'] = $row['image'] ?: 'images/food_placeholder.jpg';
    }
    unset($row);

    echo json_encode($results);

} catch (PDOException $e) {
    error_log("Search suggestions error: " . $e->getMessage());
    echo json_encode([]);
}
?>
