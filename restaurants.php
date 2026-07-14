<?php
$page_title = "Restaurants";
require_once 'includes/header.php';

// Get all categories for filtering
$categories = get_all_categories();

// Get all restaurants
$sql = "SELECT r.*, 
       (SELECT AVG(rating) FROM reviews WHERE restaurant_id = r.restaurant_id) as avg_rating,
       (SELECT COUNT(*) FROM reviews WHERE restaurant_id = r.restaurant_id) as review_count
       FROM restaurants r 
       WHERE r.is_active = TRUE 
       ORDER BY r.name";
$stmt = $conn->query($sql);
$restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle search query
$search_query = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = clean_input($_GET['search']);
    // Filter restaurants by search query
    $filtered_restaurants = array();
    foreach($restaurants as $restaurant) {
        if (stripos($restaurant['name'], $search_query) !== false || 
            stripos($restaurant['description'], $search_query) !== false || 
            stripos($restaurant['city'], $search_query) !== false) {
            $filtered_restaurants[] = $restaurant;
        }
    }
    $restaurants = $filtered_restaurants;
}

// Interior restaurant stock images
$restaurant_images = [
    "images/restaurant_1.jpg",
    "images/restaurant_2.jpg",
    "images/restaurant_3.jpg",
    "images/restaurant_4.jpg"
];
?>

<!-- Restaurant Search and Filter Section -->
<section class="mb-5">
    <div class="container">
        <h1 class="mb-4">Restaurants</h1>
        
        <div class="row mb-4 g-3">
            <div class="col-md-8">
                <form action="restaurants.php" method="get" class="restaurant-search-form">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" id="restaurant-search" class="form-control border-start-0" placeholder="Search restaurants..." value="<?php echo $search_query; ?>">
                        <button class="btn btn-primary" type="submit">Search</button>
                    </div>
                </form>
            </div>
            <div class="col-md-4 text-end">
                <div class="dropdown d-inline-block w-100">
                    <button class="btn btn-outline-primary dropdown-toggle w-100 py-2 d-flex justify-content-between align-items-center" type="button" id="categoryDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <span><i class="fas fa-filter me-2"></i> All Categories</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end w-100" aria-labelledby="categoryDropdown">
                        <li><button class="dropdown-item restaurant-filter active" data-filter="all">All Categories</button></li>
                        <?php foreach($categories as $category): ?>
                        <li><button class="dropdown-item restaurant-filter" data-filter="<?php echo $category['category_id']; ?>"><?php echo $category['name']; ?></button></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        
        <?php if(empty($restaurants)): ?>
        <div class="alert alert-info">
            <?php if(!empty($search_query)): ?>
                No restaurants found matching "<?php echo $search_query; ?>". <a href="restaurants.php">View all restaurants</a>
            <?php else: ?>
                No restaurants available at this time. Please check back later.
            <?php endif; ?>
        </div>
        <?php else: ?>
        
        <div class="row">
            <?php 
            foreach($restaurants as $index => $restaurant): 
                // Get restaurant categories 
                $stmt = $conn->prepare("SELECT DISTINCT c.category_id FROM categories c 
                                      JOIN menu_items m ON c.category_id = m.category_id 
                                      WHERE m.restaurant_id = ?");
                $stmt->execute([$restaurant['restaurant_id']]);
                $restaurant_categories = array();
                while($cat = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $restaurant_categories[] = $cat['category_id'];
                }
                $category_attr = implode(' ', $restaurant_categories);
                
                // Select a restaurant image
                $img_index = $index % 4;
            ?>
            <div class="col-md-6 col-lg-4 mb-4 restaurant-item" data-category="<?php echo $category_attr; ?>">
                <div class="card restaurant-card h-100">
                    <?php if (!empty($restaurant['image']) && file_exists($restaurant['image'])): ?>
                        <img src="<?php echo $restaurant['image']; ?>" class="card-img-top" alt="<?php echo $restaurant['name']; ?>" style="height: 200px; object-fit: cover;">
                    <?php else: ?>
                        <img src="<?php echo $restaurant_images[$img_index]; ?>" class="card-img-top" alt="<?php echo $restaurant['name']; ?>" style="height: 200px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title"><?php echo $restaurant['name']; ?></h5>
                            <span class="badge bg-<?php echo $restaurant['is_active'] ? 'success' : 'danger'; ?>">
                                <?php echo $restaurant['is_active'] ? 'Open' : 'Closed'; ?>
                            </span>
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
                        <p class="card-text small"><?php echo substr($restaurant['description'], 0, 100); echo (strlen($restaurant['description']) > 100) ? '...' : ''; ?></p>
                        <p class="card-text small text-muted">
                            <i class="fas fa-map-marker-alt me-1"></i> <?php echo $restaurant['address']; ?>, <?php echo $restaurant['city']; ?>
                        </p>
                        <p class="card-text small text-muted">
                            <i class="fas fa-clock me-1"></i> Delivery: <?php echo $restaurant['delivery_time']; ?> mins &bull; 
                            <i class="fas fa-motorcycle me-1"></i> Fee: <?php echo format_price($restaurant['delivery_fee']); ?>
                        </p>
                        <a href="restaurant_details.php?id=<?php echo $restaurant['restaurant_id']; ?>" class="btn btn-primary mt-2">View Menu</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php
require_once 'includes/footer.php';
?>
