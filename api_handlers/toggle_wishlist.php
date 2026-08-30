<?php
/**
 * Toggle Wishlist Item
 * POST: item_id
 * Returns: { success, added, wishlist_count }
 */
chdir(__DIR__);
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please login to use wishlist.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

if ($item_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item.']);
    exit;
}

try {
    // Check if already wishlisted
    $stmt = $conn->prepare("SELECT wishlist_id FROM wishlists WHERE user_id = ? AND item_id = ?");
    $stmt->execute([$user_id, $item_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Remove from wishlist
        $del = $conn->prepare("DELETE FROM wishlists WHERE user_id = ? AND item_id = ?");
        $del->execute([$user_id, $item_id]);
        $added = false;
    } else {
        // Add to wishlist
        $ins = $conn->prepare("INSERT INTO wishlists (user_id, item_id) VALUES (?, ?)");
        $ins->execute([$user_id, $item_id]);
        $added = true;
    }

    // Count total wishlist items
    $cnt = $conn->prepare("SELECT COUNT(*) as cnt FROM wishlists WHERE user_id = ?");
    $cnt->execute([$user_id]);
    $row = $cnt->fetch(PDO::FETCH_ASSOC);
    $wishlist_count = intval($row['cnt'] ?? 0);

    echo json_encode(['success' => true, 'added' => $added, 'wishlist_count' => $wishlist_count]);

} catch (PDOException $e) {
    // Table may not exist yet — return graceful error
    error_log("Toggle wishlist error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Wishlist not available yet. Run migration first.']);
}
?>
