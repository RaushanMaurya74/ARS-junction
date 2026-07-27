<?php
$page_title = "My Wishlist";
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);

// Load wishlist items with menu info
try {
    $stmt = $conn->prepare(
        "SELECT w.wishlist_id, w.item_id, w.created_at,
                mi.name, mi.description, mi.price, mi.image, mi.is_vegetarian, mi.is_spicy, mi.is_available,
                r.name AS restaurant_name, r.restaurant_id,
                c.name AS category_name
         FROM wishlists w
         JOIN menu_items mi ON w.item_id = mi.item_id
         JOIN restaurants r ON mi.restaurant_id = r.restaurant_id
         JOIN categories c ON mi.category_id = c.category_id
         WHERE w.user_id = ?
         ORDER BY w.created_at DESC"
    );
    $stmt->execute([$user_id]);
    $wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $wishlist_items = [];
}

$food_images = ["images/food_pizza.jpg","images/food_burger.jpg","images/food_indian.jpg","images/food_chinese.jpg"];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0"><i class="fas fa-heart text-danger me-2"></i> My Wishlist
        <span class="badge bg-danger ms-2" style="font-size:0.7rem;"><?php echo count($wishlist_items); ?></span>
    </h1>
    <a href="menu.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-arrow-left me-1"></i> Browse Menu</a>
</div>

<?php if (empty($wishlist_items)): ?>
<div class="wishlist-empty">
    <i class="fas fa-heart-broken"></i>
    <h4>Your wishlist is empty</h4>
    <p>Save your favourite dishes by clicking the ❤️ heart button on any food item</p>
    <a href="menu.php" class="btn btn-primary mt-3"><i class="fas fa-utensils me-1"></i> Explore Menu</a>
</div>
<?php else: ?>

<div class="wishlist-grid">
<?php foreach ($wishlist_items as $idx => $item): 
    $img = has_image($item['image'], false) ? get_image_url($item['image'], false) : $food_images[$idx % 4];
?>
    <div class="card food-card h-100" style="position:relative;" id="wishlist-card-<?php echo $item['item_id']; ?>">
        <!-- Wishlist remove button -->
        <button class="wishlist-btn active" data-item-id="<?php echo $item['item_id']; ?>" title="Remove from Wishlist">
            <i class="fas fa-heart"></i>
        </button>
        <img src="<?php echo $img; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($item['name']); ?>" style="height:170px;object-fit:cover;">
        <div class="food-badges" style="position:absolute;top:10px;left:10px;">
            <span class="badge <?php echo $item['is_vegetarian'] ? 'bg-success' : 'bg-danger'; ?>">
                <?php echo $item['is_vegetarian'] ? 'Veg' : 'Non-Veg'; ?>
            </span>
            <?php if ($item['is_spicy']): ?><span class="badge bg-warning text-dark">Spicy</span><?php endif; ?>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <h6 class="card-title mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                <span class="price fw-bold text-primary"><?php echo format_price($item['price']); ?></span>
            </div>
            <p class="card-text small text-muted mb-1">
                <i class="fas fa-utensils me-1"></i><?php echo htmlspecialchars($item['restaurant_name']); ?>
            </p>
            <p class="card-text small text-muted mb-3">
                <?php echo substr(htmlspecialchars($item['description']), 0, 55); ?><?php echo strlen($item['description']) > 55 ? '...' : ''; ?>
            </p>
            <?php if ($item['is_available']): ?>
            <button class="btn btn-primary btn-sm add-to-cart-btn animated-add-to-cart-btn w-100"
                    data-item-id="<?php echo $item['item_id']; ?>">
                <i class="fas fa-cart-plus"></i> Add to Cart
            </button>
            <?php else: ?>
            <button class="btn btn-secondary btn-sm w-100" disabled>Currently Unavailable</button>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
