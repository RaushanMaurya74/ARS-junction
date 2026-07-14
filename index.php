<?php
$page_title = "Home";
require_once 'includes/header.php';

// Local fallbacks
$restaurant_images = [
    "images/restaurant_1.jpg",
    "images/restaurant_2.jpg",
    "images/restaurant_3.jpg",
    "images/restaurant_4.jpg"
];

$stock_images = [
    "images/food_pizza.jpg",
    "images/food_burger.jpg",
    "images/food_indian.jpg",
    "images/food_chinese.jpg"
];

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
                <select class="form-select form-select-lg" style="max-width: 150px; flex: none; border-top-left-radius: 8px; border-bottom-left-radius: 8px; font-size: 1rem;" onchange="this.form.action = this.value === 'dishes' ? 'menu.php' : 'restaurants.php';">
                    <option value="restaurants">Restaurants</option>
                    <option value="dishes">Dishes</option>
                </select>
                <input type="text" name="search" class="form-control form-control-lg" placeholder="Search for food or restaurants..." required>
                <button class="btn btn-warning px-4" type="submit" style="border-top-right-radius: 8px; border-bottom-right-radius: 8px;"><i class="fas fa-search"></i> Search</button>
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

<!-- Delivery Pincode Checker Section -->
<section class="mb-5 py-4 bg-white rounded shadow-sm border">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-5 mb-4 mb-lg-0">
                <h3 class="fw-bold text-dark"><i class="fas fa-map-marked-alt text-primary me-2"></i>Check Delivery Availability</h3>
                <p class="text-muted">Enter your local Indian pincode to instantly check if ARS Junction delivers to your doorstep, see delivery rates, and view your area on the map.</p>
                <form id="pincode-search-form" class="mt-3">
                    <div class="input-group">
                        <input type="text" id="search-pincode" class="form-control form-control-lg border-primary" placeholder="Enter 6-digit Pincode (e.g. 802207)" required pattern="[0-9]{6}">
                        <button class="btn btn-primary px-4 fw-bold" type="submit"><i class="fas fa-search me-1"></i> Check</button>
                    </div>
                </form>
                <div id="pincode-result" class="mt-3" style="display: none;"></div>
            </div>
            <div class="col-lg-7">
                <div class="pincode-map-container rounded overflow-hidden border shadow-sm" style="min-height: 250px; background-color: #eee; position: relative;">
                    <!-- Placeholder Map -->
                    <div id="map-placeholder" class="d-flex flex-column justify-content-center align-items-center position-absolute w-100 h-100 bg-light text-center p-3">
                        <i class="fas fa-map-marker-alt fa-3x text-muted mb-2 animate__animated animate__pulse animate__infinite"></i>
                        <h6 class="text-muted mb-0">Enter a pincode to load the Google Map reference</h6>
                    </div>
                    <iframe id="pincode-map-iframe" width="100%" height="300" style="border:0; display: none;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pinForm = document.getElementById('pincode-search-form');
    if (pinForm) {
        pinForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const pincode = document.getElementById('search-pincode').value.trim();
            const resultDiv = document.getElementById('pincode-result');
            const mapPlaceholder = document.getElementById('map-placeholder');
            const mapIframe = document.getElementById('pincode-map-iframe');
            
            if (!/^[0-9]{6}$/.test(pincode)) {
                resultDiv.className = 'alert alert-danger';
                resultDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Please enter a valid 6-digit numeric pincode.';
                resultDiv.style.display = 'block';
                return;
            }
            
            resultDiv.className = 'alert alert-info';
            resultDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Checking availability...';
            resultDiv.style.display = 'block';
            
            fetch('api/check_pincode.php?pincode=' + encodeURIComponent(pincode))
            .then(response => response.json())
            .then(data => {
                if (data.deliverable) {
                    resultDiv.className = 'alert alert-success border-2 animate__animated animate__fadeIn';
                    resultDiv.innerHTML = `
                        <h6 class="alert-heading fw-bold mb-1"><i class="fas fa-check-circle me-1"></i>We Deliver Here!</h6>
                        <p class="mb-1 small"><strong>Area:</strong> ${data.area_name}</p>
                        <p class="mb-0 small"><strong>Delivery Fee:</strong> ₹${data.delivery_charge.toFixed(2)}</p>
                    `;
                } else {
                    resultDiv.className = 'alert alert-danger border-2 animate__animated animate__fadeIn';
                    resultDiv.innerHTML = `<h6 class="alert-heading fw-bold mb-1"><i class="fas fa-times-circle me-1"></i>No Delivery Yet</h6><p class="mb-0 small">${data.message}</p>`;
                }
                
                // Load Google Map reference for the pincode
                mapPlaceholder.style.setProperty('display', 'none', 'important');
                mapIframe.src = `https://maps.google.com/maps?q=${encodeURIComponent(pincode)}+India&t=&z=14&ie=UTF8&iwloc=&output=embed`;
                mapIframe.style.display = 'block';
            })
            .catch(error => {
                console.error('Error:', error);
                resultDiv.className = 'alert alert-warning';
                resultDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Error connecting to the server. Please try again.';
            });
        });
    }
});
</script>

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
                    <?php if (!empty($restaurant['image']) && file_exists($restaurant['image'])): ?>
                        <img src="<?php echo $restaurant['image']; ?>" class="card-img-top" alt="<?php echo $restaurant['name']; ?>" style="height: 180px; object-fit: cover;">
                    <?php else: ?>
                        <img src="<?php echo $restaurant_images[$restaurant['restaurant_id'] % 4]; ?>" class="card-img-top" alt="<?php echo $restaurant['name']; ?>" style="height: 180px; object-fit: cover;">
                    <?php endif; ?>
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
                        <?php else: ?>
                        <span class="badge bg-danger">Non-Veg</span>
                        <?php endif; ?>
                        <?php if($item['is_spicy']): ?>
                        <span class="badge bg-danger">Spicy</span>
                        <?php endif; ?>
                    </div>
                    <?php 
                    $img_index = $item['item_id'] % 4;
                    ?>
                    <?php if (!empty($item['image']) && file_exists($item['image'])): ?>
                        <img src="<?php echo $item['image']; ?>" class="card-img-top" alt="<?php echo $item['name']; ?>" style="height: 180px; object-fit: cover;">
                    <?php else: ?>
                        <img src="<?php echo $stock_images[$img_index]; ?>" class="card-img-top" alt="<?php echo $item['name']; ?>" style="height: 180px; object-fit: cover;">
                    <?php endif; ?>
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
                <div class="d-flex flex-wrap mt-4">
                    <a href="https://play.google.com/store" target="_blank" class="btn btn-dark btn-lg me-3 mb-2 px-3 py-2 text-start" style="border-radius: 8px;">
                        <div class="d-flex align-items-center">
                            <i class="fab fa-google-play fa-2x me-3 text-warning"></i>
                            <div>
                                <div class="small text-muted" style="font-size: 0.65rem; text-transform: uppercase; line-height: 1;">Get it on</div>
                                <div class="fw-bold fs-6" style="line-height: 1.2;">Google Play</div>
                            </div>
                        </div>
                    </a>
                    <a href="https://www.apple.com/app-store/" target="_blank" class="btn btn-dark btn-lg mb-2 px-3 py-2 text-start" style="border-radius: 8px;">
                        <div class="d-flex align-items-center">
                            <i class="fab fa-apple fa-2x me-3 text-light"></i>
                            <div>
                                <div class="small text-muted" style="font-size: 0.65rem; text-transform: uppercase; line-height: 1;">Download on the</div>
                                <div class="fw-bold fs-6" style="line-height: 1.2;">App Store</div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-md-6 text-center">
                 <img src="https://images.unsplash.com/photo-1512152272829-e3139592d56f?auto=format&fit=crop&w=800&q=80" class="img-fluid rounded shadow-sm" alt="Mobile App" style="max-height: 400px; border-radius: 12px; object-fit: cover; width: 100%;">
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
