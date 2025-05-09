<?php
$page_title = "Restaurant Details";
require_once 'includes/header.php';

// Check if restaurant ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: restaurants.php");
    exit;
}

$restaurant_id = intval($_GET['id']);
$restaurant = get_restaurant_by_id($restaurant_id);

// Redirect if restaurant not found
if (!$restaurant) {
    header("Location: restaurants.php");
    exit;
}

// Get restaurant menu items organized by category
$menu_by_category = get_restaurant_menu_by_category($restaurant_id);

// Get restaurant reviews
$reviews = get_restaurant_reviews($restaurant_id, 5);

// Check if user has already reviewed this restaurant
$has_reviewed = false;
if (is_logged_in()) {
    $has_reviewed = has_user_reviewed($_SESSION['user_id'], $restaurant_id);
}

// Calculate average rating
$avg_rating = 0;
$review_count = 0;

$sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM reviews WHERE restaurant_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $avg_rating = round($row['avg_rating'], 1);
    $review_count = $row['count'];
}

// Extra JS
$extra_js = '<script src="js/cart.js"></script>';

// Restaurant images (use stock photos)
$restaurant_images = [
    "https://pixabay.com/get/g1c4d7b9751b3890559d056283366d15c43871338c0b32e6e87df5f4fb2cda97974c2cab8ffcf0e12aa6594039be164ad5e32d9f2e84936052f08f05731b988e7_1280.jpg",
    "https://pixabay.com/get/g32e7e52c71ac7b6b547bf4311824851318f6ceaf1f1b6608da37564e25220a7cdbd4a66a2f566383b2d6c3772e2cfb099c7ffa98517ec22b54df0296a90d88e0_1280.jpg",
    "https://pixabay.com/get/gb03bfb45830cae2f092729c65eddf6e9bf3056cc1ce7e3f16ada88db82f39493d12d160c84fa25586f91bc9a59ccfa51fbccae0c02111db5816d0e74cb527647_1280.jpg",
    "https://pixabay.com/get/g57892949a204216b3e74332aae863db8ad3ccbdf6375224bc32d4f2683827f08063f70ddefde5e9479934a5f1959f3217b14c99c441d6e284498c759fc9a6b0d_1280.jpg"
];

// Food images
$food_images = [
    "https://pixabay.com/get/g12156b2dbc31bb07d0fc025ac2c9d55a2e380994888ce620bf2df42e2cad88a767656bfd015da042307899468e4fea5a7dac8c5271f4bca3e2557eb58b10cbcc_1280.jpg",
    "https://pixabay.com/get/g00cc480b0593d93c26a23b5426edafddd525a90edb5b4fba09d7860087da5ad4a1db284ab5d9156c7db3337c4f18e99565d714d2f80fa86cdb2ab2521a5ee276_1280.jpg",
    "https://pixabay.com/get/g6dab4081301a35f2eaa4dab90e26e8382b349aff7731c6fbaebdc4d02f74f79bef3ae2efa46e299a9994c2e3c2ca4f47b51a8362d12fe6f673b9ee1d59d222c3_1280.jpg",
    "https://pixabay.com/get/g64e0555caef3f12f1d7115a280d82fb8413f9212dec2d3fb1ea8ab282134b485225984391e3f591e8a535477db67a8b997d34342883959addecd6d8c46a61013_1280.jpg",
    "https://pixabay.com/get/g10786d528a6f89d5e001b5927653ea963c658bdb227414dfae50c760926e0b3ebd7d27197ec9ae9a5bd75db0d41f20fe6a7547c6e439af24d3c04b049fd7a47f_1280.jpg",
    "https://pixabay.com/get/g88c1a943dca246fccfd6cc284add1888dd69d32b088ab38f54080c18c5ed31f80a94daa14599f5e35a3ecf9c9d34b3120088633a604208879589608db6f5c209_1280.jpg",
    "https://pixabay.com/get/g683a2929e4615ce3a6bf61fdf30d6d35b46e9b8b9f3419d63e8dd595c00c12428211e15157d2f63cc00c9f1bf4f19ad28fbd26713abfa85e1670fe5460124258_1280.jpg",
    "https://pixabay.com/get/g73588bd1697fddf3029e16f1b8f2f6cb1361a62e5916feb7dc4c9735d59270447fdeb6a9e0d70a82bef05a35ba6a288e61ce30139f6e1e3be044655518cea643_1280.jpg"
];

// Select a random restaurant image
$img_index = $restaurant_id % 4;
?>

<!-- Restaurant Header -->
<div class="container mb-4">
    <div class="restaurant-header">
        <img src="<?php echo $restaurant_images[$img_index]; ?>" alt="<?php echo $restaurant['name']; ?>" class="restaurant-cover">
        
        <div class="restaurant-badge">
            <span class="badge bg-<?php echo $restaurant['is_active'] ? 'success' : 'danger'; ?> p-2">
                <?php echo $restaurant['is_active'] ? 'Open Now' : 'Closed'; ?>
            </span>
        </div>
        
        <div class="restaurant-details">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2"><?php echo $restaurant['name']; ?></h1>
                    <p class="mb-2"><?php echo $restaurant['description']; ?></p>
                    
                    <div class="mb-3">
                        <span class="rating">
                            <?php
                            for($i = 1; $i <= 5; $i++) {
                                if($i <= $avg_rating) {
                                    echo '<i class="fas fa-star"></i>';
                                } else if($i <= $avg_rating + 0.5) {
                                    echo '<i class="fas fa-star-half-alt"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            ?>
                            <span class="ms-1"><?php echo $avg_rating; ?></span>
                            <span class="text-muted small">(<?php echo $review_count; ?> reviews)</span>
                        </span>
                    </div>
                    
                    <div class="restaurant-info">
                        <p class="mb-1"><i class="fas fa-map-marker-alt me-2"></i> <?php echo $restaurant['address']; ?>, <?php echo $restaurant['city']; ?>, <?php echo $restaurant['state']; ?> - <?php echo $restaurant['zip_code']; ?></p>
                        <p class="mb-1"><i class="fas fa-phone me-2"></i> <?php echo $restaurant['phone']; ?></p>
                        <p class="mb-1"><i class="fas fa-envelope me-2"></i> <?php echo $restaurant['email']; ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="restaurant-meta text-md-end mt-3 mt-md-0">
                        <p class="mb-1"><i class="fas fa-clock me-2"></i> Delivery Time: <?php echo $restaurant['delivery_time']; ?> mins</p>
                        <p class="mb-1"><i class="fas fa-motorcycle me-2"></i> Delivery Fee: <?php echo format_price($restaurant['delivery_fee']); ?></p>
                        <p><i class="fas fa-shopping-basket me-2"></i> Min. Order: <?php echo format_price($restaurant['minimum_order']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <!-- Menu Categories Navigation -->
        <div class="col-lg-3 mb-4">
            <div class="sticky-top" style="top: 85px;">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Menu Categories</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach($menu_by_category as $index => $category_menu): ?>
                            <a href="#category-<?php echo $category_menu['category']['category_id']; ?>" class="list-group-item list-group-item-action">
                                <?php echo $category_menu['category']['name']; ?>
                                <span class="badge bg-secondary float-end"><?php echo count($category_menu['items']); ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Opening Hours</h5>
                        <p class="card-text">
                            <?php if($restaurant['opening_time'] && $restaurant['closing_time']): ?>
                                <?php echo date('g:i A', strtotime($restaurant['opening_time'])); ?> - 
                                <?php echo date('g:i A', strtotime($restaurant['closing_time'])); ?>
                            <?php else: ?>
                                Not specified
                            <?php endif; ?>
                        </p>
                        <hr>
                        <h5 class="card-title">Contact Info</h5>
                        <p class="card-text mb-1"><i class="fas fa-phone me-2"></i> <?php echo $restaurant['phone']; ?></p>
                        <p class="card-text"><i class="fas fa-envelope me-2"></i> <?php echo $restaurant['email']; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Menu Items -->
        <div class="col-lg-9">
            <?php if(empty($menu_by_category)): ?>
            <div class="alert alert-info">This restaurant has no menu items available at this time.</div>
            <?php else: ?>
            
            <!-- Menu Categories and Items -->
            <?php foreach($menu_by_category as $index => $category_menu): ?>
            <div id="category-<?php echo $category_menu['category']['category_id']; ?>" class="menu-category mb-4">
                <h3 class="mb-3"><?php echo $category_menu['category']['name']; ?></h3>
                
                <?php foreach($category_menu['items'] as $item_index => $item): 
                    $food_img_index = ($item['item_id'] + $index) % 8;
                ?>
                <div class="menu-item row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo $food_images[$food_img_index]; ?>" alt="<?php echo $item['name']; ?>" class="menu-item-img me-3">
                            <div>
                                <h5 class="mb-1">
                                    <?php echo $item['name']; ?>
                                    <?php if($item['is_vegetarian']): ?>
                                    <span class="badge bg-success">Veg</span>
                                    <?php endif; ?>
                                    <?php if($item['is_spicy']): ?>
                                    <span class="badge bg-danger">Spicy</span>
                                    <?php endif; ?>
                                </h5>
                                <p class="mb-1 text-muted small"><?php echo $item['description']; ?></p>
                                <p class="mb-0 price"><?php echo format_price($item['price']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-end mt-3 mt-md-0">
                        <div class="d-flex justify-content-end align-items-center">
                            <div class="input-group input-group-sm quantity-control me-2" style="width: 110px">
                                <button class="btn btn-outline-secondary decrease-qty" type="button">-</button>
                                <input type="number" class="form-control text-center quantity-input" data-item-id="<?php echo $item['item_id']; ?>" value="1" min="1" max="10">
                                <button class="btn btn-outline-secondary increase-qty" type="button">+</button>
                            </div>
                            <button class="btn btn-primary btn-sm add-to-cart-btn" data-item-id="<?php echo $item['item_id']; ?>">
                                <i class="fas fa-cart-plus"></i> Add
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
            
            <?php endif; ?>
            
            <!-- Reviews Section -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Customer Reviews</h4>
                    <div>
                        <span class="rating">
                            <?php
                            for($i = 1; $i <= 5; $i++) {
                                if($i <= $avg_rating) {
                                    echo '<i class="fas fa-star"></i>';
                                } else if($i <= $avg_rating + 0.5) {
                                    echo '<i class="fas fa-star-half-alt"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            ?>
                            <span class="ms-1"><?php echo $avg_rating; ?></span>
                        </span>
                        <span class="text-muted">(<?php echo $review_count; ?> reviews)</span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if(is_logged_in() && !$has_reviewed): ?>
                    <div class="add-review-form mb-4">
                        <h5 class="mb-3">Write a Review</h5>
                        <form action="api/add_review.php" method="post">
                            <input type="hidden" name="restaurant_id" value="<?php echo $restaurant_id; ?>">
                            <div class="mb-3">
                                <label class="form-label">Rating</label>
                                <div class="rating-stars mb-2">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input rating-input" type="radio" name="rating" id="rating<?php echo $i; ?>" value="<?php echo $i; ?>" <?php echo ($i == 5) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="rating<?php echo $i; ?>">
                                            <i class="far fa-star"></i>
                                        </label>
                                    </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="review-comment" class="form-label">Your Review</label>
                                <textarea class="form-control" id="review-comment" name="comment" rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Review</button>
                        </form>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(empty($reviews)): ?>
                    <div class="text-center py-3">
                        <p>No reviews yet. Be the first to review this restaurant!</p>
                    </div>
                    <?php else: ?>
                    
                    <?php foreach($reviews as $review): ?>
                    <div class="review-item">
                        <div class="d-flex align-items-center mb-2">
                            <div class="reviewer-img bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <h6 class="mb-0"><?php echo $review['user_name']; ?></h6>
                                <p class="text-muted small mb-0"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></p>
                            </div>
                        </div>
                        <div class="mb-2 star-rating">
                            <?php
                            for($i = 1; $i <= 5; $i++) {
                                if($i <= $review['rating']) {
                                    echo '<i class="fas fa-star"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            ?>
                        </div>
                        <p class="mb-0"><?php echo $review['comment']; ?></p>
                    </div>
                    <hr>
                    <?php endforeach; ?>
                    
                    <?php if($review_count > 5): ?>
                    <div class="text-center">
                        <button class="btn btn-outline-primary" id="load-more-reviews">Load More Reviews</button>
                    </div>
                    <?php endif; ?>
                    
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container for notifications -->
<div id="toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index: 5;"></div>

<?php
require_once 'includes/footer.php';
?>
