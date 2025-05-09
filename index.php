<?php
$page_title = "Home";
require_once 'includes/header.php';

// Get featured restaurants
$featured_restaurants = get_featured_restaurants(4);

// Get featured menu items
$featured_menu_items = get_featured_menu_items(8);
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <h1 class="hero-title">Delicious Food Delivered To Your Door</h1>
        <p class="hero-subtitle">Order from the best local restaurants with easy, on-demand delivery.</p>
        
        <form action="restaurants.php" method="get" class="search-form">
            <div class="input-group mb-3">
                <input type="text" name="search" class="form-control form-control-lg" placeholder="Search for food or restaurants...">
                <button class="btn btn-warning" type="submit"><i class="fas fa-search"></i> Search</button>
            </div>
        </form>
    </div>
</section>

<!-- Categories Section -->
<section class="mb-5">
    <div class="container">
        <div class="section-title mb-4">
            <h2>Food Categories</h2>
            <p>Explore our wide range of delicious food options</p>
        </div>
        
        <div class="row">
            <?php 
            $categories = get_all_categories();
            $category_icons = [
                'Pizza' => 'fa-pizza-slice',
                'Burger' => 'fa-hamburger',
                'Indian' => 'fa-utensils',
                'Chinese' => 'fa-utensils',
                'Italian' => 'fa-utensils',
                'Desserts' => 'fa-ice-cream',
                'Beverages' => 'fa-coffee'
            ];
            
            foreach($categories as $category): 
                $icon = isset($category_icons[$category['name']]) ? $category_icons[$category['name']] : 'fa-utensils';
            ?>
            <div class="col-6 col-md-4 col-lg-3 mb-4">
                <a href="menu.php?category=<?php echo $category['category_id']; ?>" class="text-decoration-none">
                    <div class="category-item">
                        <div class="category-icon">
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>
                        <h5><?php echo $category['name']; ?></h5>
                        <p class="small text-muted"><?php echo $category['description']; ?></p>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Restaurants Section -->
<section class="mb-5">
    <div class="container">
        <div class="section-title mb-4">
            <h2>Popular Restaurants</h2>
            <p>Top rated restaurants with the best food just for you</p>
        </div>
        
        <div class="row">
            <?php foreach($featured_restaurants as $restaurant): ?>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card restaurant-card">
                    <img src="https://pixabay.com/get/g57892949a204216b3e74332aae863db8ad3ccbdf6375224bc32d4f2683827f08063f70ddefde5e9479934a5f1959f3217b14c99c441d6e284498c759fc9a6b0d_1280.jpg" class="card-img-top" alt="<?php echo $restaurant['name']; ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title"><?php echo $restaurant['name']; ?></h5>
                            <span class="badge bg-success"><?php echo $restaurant['is_active'] ? 'Open' : 'Closed'; ?></span>
                        </div>
                        <div class="mb-2">
                            <span class="rating">
                                <?php
                                $rating = isset($restaurant['avg_rating']) ? round($restaurant['avg_rating'], 1) : 0;
                                for($i = 1; $i <= 5; $i++) {
                                    if($i <= $rating) {
                                        echo '<i class="fas fa-star"></i>';
                                    } else if($i <= $rating + 0.5) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                                <span class="ms-1"><?php echo $rating; ?></span>
                                <span class="text-muted small">(<?php echo isset($restaurant['review_count']) ? $restaurant['review_count'] : 0; ?> reviews)</span>
                            </span>
                        </div>
                        <p class="card-text small text-muted">
                            <i class="fas fa-map-marker-alt me-1"></i> <?php echo $restaurant['city']; ?> &bull; 
                            <i class="fas fa-clock me-1"></i> <?php echo $restaurant['delivery_time']; ?> mins
                        </p>
                        <a href="restaurant_details.php?id=<?php echo $restaurant['restaurant_id']; ?>" class="btn btn-outline-primary btn-sm mt-2">View Menu</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-3">
            <a href="restaurants.php" class="btn btn-primary">View All Restaurants</a>
        </div>
    </div>
</section>

<!-- Featured Food Items Section -->
<section class="mb-5">
    <div class="container">
        <div class="section-title mb-4">
            <h2>Popular Dishes</h2>
            <p>Try our most ordered and loved dishes</p>
        </div>
        
        <div class="row">
            <?php foreach($featured_menu_items as $item): ?>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card food-card">
                    <div class="food-badges">
                        <?php if($item['is_vegetarian']): ?>
                        <span class="badge bg-success">Veg</span>
                        <?php endif; ?>
                        <?php if($item['is_spicy']): ?>
                        <span class="badge bg-danger">Spicy</span>
                        <?php endif; ?>
                    </div>
                    <?php 
                    // Using the provided stock images based on item index to simulate different food items
                    $stock_images = [
                        "https://pixabay.com/get/g12156b2dbc31bb07d0fc025ac2c9d55a2e380994888ce620bf2df42e2cad88a767656bfd015da042307899468e4fea5a7dac8c5271f4bca3e2557eb58b10cbcc_1280.jpg",
                        "https://pixabay.com/get/g00cc480b0593d93c26a23b5426edafddd525a90edb5b4fba09d7860087da5ad4a1db284ab5d9156c7db3337c4f18e99565d714d2f80fa86cdb2ab2521a5ee276_1280.jpg",
                        "https://pixabay.com/get/g6dab4081301a35f2eaa4dab90e26e8382b349aff7731c6fbaebdc4d02f74f79bef3ae2efa46e299a9994c2e3c2ca4f47b51a8362d12fe6f673b9ee1d59d222c3_1280.jpg",
                        "https://pixabay.com/get/g64e0555caef3f12f1d7115a280d82fb8413f9212dec2d3fb1ea8ab282134b485225984391e3f591e8a535477db67a8b997d34342883959addecd6d8c46a61013_1280.jpg",
                        "https://pixabay.com/get/g10786d528a6f89d5e001b5927653ea963c658bdb227414dfae50c760926e0b3ebd7d27197ec9ae9a5bd75db0d41f20fe6a7547c6e439af24d3c04b049fd7a47f_1280.jpg",
                        "https://pixabay.com/get/g88c1a943dca246fccfd6cc284add1888dd69d32b088ab38f54080c18c5ed31f80a94daa14599f5e35a3ecf9c9d34b3120088633a604208879589608db6f5c209_1280.jpg",
                        "https://pixabay.com/get/g683a2929e4615ce3a6bf61fdf30d6d35b46e9b8b9f3419d63e8dd595c00c12428211e15157d2f63cc00c9f1bf4f19ad28fbd26713abfa85e1670fe5460124258_1280.jpg",
                        "https://pixabay.com/get/g73588bd1697fddf3029e16f1b8f2f6cb1361a62e5916feb7dc4c9735d59270447fdeb6a9e0d70a82bef05a35ba6a288e61ce30139f6e1e3be044655518cea643_1280.jpg"
                    ];
                    $img_index = $item['item_id'] % 8;
                    ?>
                    <img src="<?php echo $stock_images[$img_index]; ?>" class="card-img-top" alt="<?php echo $item['name']; ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title"><?php echo $item['name']; ?></h5>
                            <span class="price"><?php echo format_price($item['price']); ?></span>
                        </div>
                        <p class="card-text small text-muted"><?php echo substr($item['description'], 0, 60); echo (strlen($item['description']) > 60) ? '...' : ''; ?></p>
                        <p class="card-text small mb-3"><i class="fas fa-utensils me-1"></i> <?php echo $item['restaurant_name']; ?></p>
                        <button class="btn btn-primary btn-sm add-to-cart-btn" data-item-id="<?php echo $item['item_id']; ?>">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-3">
            <a href="menu.php" class="btn btn-primary">Browse Full Menu</a>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="mb-5 bg-light py-5">
    <div class="container">
        <div class="section-title mb-4 text-center">
            <h2>How It Works</h2>
            <p>Order food online in just 3 easy steps</p>
        </div>
        
        <div class="row text-center">
            <div class="col-md-4 mb-4 mb-md-0">
                <div class="p-4">
                    <div class="display-4 text-primary mb-3">
                        <i class="fas fa-store"></i>
                    </div>
                    <h4>Choose a Restaurant</h4>
                    <p>Browse through our wide selection of restaurants and cuisines</p>
                </div>
            </div>
            <div class="col-md-4 mb-4 mb-md-0">
                <div class="p-4">
                    <div class="display-4 text-primary mb-3">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h4>Select Your Dishes</h4>
                    <p>Choose from hundreds of delicious dishes and add to cart</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4">
                    <div class="display-4 text-primary mb-3">
                        <i class="fas fa-truck"></i>
                    </div>
                    <h4>Fast Delivery</h4>
                    <p>Get your food delivered right to your doorstep</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Download App Section -->
<section class="mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 mb-4 mb-md-0">
                <h2>Get The ARS JUNCTION App</h2>
                <p class="lead">Download our mobile app for a better experience</p>
                <p>Order faster, track your delivery in real-time, and get exclusive app-only offers</p>
                <div class="d-flex mt-4">
                    <a href="#" class="me-3">
                        <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/icons/google-play.svg" alt="Google Play" height="40">
                    </a>
                    <a href="#">
                        <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/icons/apple.svg" alt="App Store" height="40">
                    </a>
                </div>
            </div>
            <div class="col-md-6">
                <img src="https://pixabay.com/get/geb103443b270c26dae13bc5ca7a7ffb4b841f92c183b4f98ee25fc31eb092c34258adc02368cc63b9adb2d86ccdae78c2ff5ac74352c4d077bfe5945da7d75cd_1280.jpg" class="img-fluid rounded" alt="Mobile App">
            </div>
        </div>
    </div>
</section>

<!-- Back to top button -->
<button id="back-to-top" class="btn btn-primary btn-sm position-fixed bottom-0 end-0 m-4" style="display: none;">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- Add JS for cart functionality -->
<script src="js/cart.js"></script>

<?php
// Include the footer
require_once 'includes/footer.php';
?>
