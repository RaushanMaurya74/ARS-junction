<?php
$page_title = "Menu";
require_once 'includes/header.php';

// Get all categories for filtering
$categories = get_all_categories();

// Get category filter from URL if provided
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;

// Get all menu items
$sql = "SELECT m.*, r.name as restaurant_name, c.name as category_name 
       FROM menu_items m 
       JOIN restaurants r ON m.restaurant_id = r.restaurant_id 
       JOIN categories c ON m.category_id = c.category_id 
        WHERE m.is_available = 1 AND r.is_active = 1";

// Add category filter if selected
if ($category_filter > 0) {
    $sql .= " AND m.category_id = " . $category_filter;
}

$sql .= " ORDER BY m.category_id, m.name";
$stmt = $conn->query($sql);
$menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle search query
$search_query = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = clean_input($_GET['search']);
    // Filter menu items by search query
    $filtered_items = array();
    foreach($menu_items as $item) {
        if (stripos($item['name'], $search_query) !== false || 
            stripos($item['description'], $search_query) !== false || 
            stripos($item['restaurant_name'], $search_query) !== false || 
            stripos($item['category_name'], $search_query) !== false) {
            $filtered_items[] = $item;
        }
    }
    $menu_items = $filtered_items;
}

// Food images
$food_images = [
    "images/food_pizza.jpg",
    "images/food_burger.jpg",
    "images/food_indian.jpg",
    "images/food_chinese.jpg"
];
?>

<!-- Menu Search and Filter Section -->
<section class="mb-5">
    <div class="container">
        <h1 class="mb-4">Our Menu</h1>
        
        <div class="row mb-4">
            <div class="col-md-8">
                <form action="menu.php" method="get" class="food-search-form">
                    <div class="input-group">
                        <input type="text" name="search" id="food-search" class="form-control" placeholder="Search for food..." value="<?php echo $search_query; ?>">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
                    </div>
                </form>
            </div>
            <div class="col-md-4">
                <div class="dropdown float-end">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" id="categoryDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php
                        if ($category_filter > 0) {
                            foreach($categories as $cat) {
                                if ($cat['category_id'] == $category_filter) {
                                    echo $cat['name'];
                                    break;
                                }
                            }
                        } else {
                            echo "All Categories";
                        }
                        ?>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="categoryDropdown">
                        <li><a class="dropdown-item <?php echo ($category_filter == 0) ? 'active' : ''; ?>" href="menu.php">All Categories</a></li>
                        <?php foreach($categories as $category): ?>
                        <li><a class="dropdown-item <?php echo ($category_filter == $category['category_id']) ? 'active' : ''; ?>" href="menu.php?category=<?php echo $category['category_id']; ?>"><?php echo $category['name']; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="mb-4">
            <div class="btn-group food-filter-group">
                <button class="btn btn-outline-primary food-filter active" data-filter="all">All</button>
                <button class="btn btn-outline-success food-filter" data-filter="vegetarian">Vegetarian</button>
                <button class="btn btn-outline-danger food-filter" data-filter="non-vegetarian">Non-Vegetarian</button>
                <button class="btn btn-outline-warning food-filter" data-filter="spicy">Spicy</button>
                <button class="btn btn-outline-info food-filter" data-filter="featured">Featured</button>
            </div>
        </div>
        
        <?php if(empty($menu_items)): ?>
        <div class="alert alert-info">
            <?php if(!empty($search_query)): ?>
                No menu items found matching "<?php echo $search_query; ?>". <a href="menu.php">View all menu items</a>
            <?php else: ?>
                No menu items available in this category at this time. <a href="menu.php">View all menu items</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        
        <div class="row">
            <?php foreach($menu_items as $index => $item): 
                $img_index = $index % 4;
                
                // Add classes for filtering
                $filter_classes = 'food-item';
                if ($item['is_vegetarian']) {
                    $filter_classes .= ' vegetarian';
                } else {
                    $filter_classes .= ' non-vegetarian';
                }
                if ($item['is_spicy']) $filter_classes .= ' spicy';
                if ($item['is_featured']) $filter_classes .= ' featured';
            ?>
            <div class="col-md-6 col-lg-3 mb-4 <?php echo $filter_classes; ?>">
                <div class="card food-card h-100">
                    <div class="food-badges">
                        <?php if($item['is_vegetarian']): ?>
                        <span class="badge bg-success">Veg</span>
                        <?php else: ?>
                        <span class="badge bg-danger">Non-Veg</span>
                        <?php endif; ?>
                        <?php if($item['is_spicy']): ?>
                        <span class="badge bg-warning text-dark">Spicy</span>
                        <?php endif; ?>
                        <?php if($item['is_featured']): ?>
                        <span class="badge bg-info text-dark">Featured</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($item['image']) && file_exists($item['image'])): ?>
                        <img src="<?php echo $item['image']; ?>" class="card-img-top" alt="<?php echo $item['name']; ?>" style="height: 180px; object-fit: cover;">
                    <?php else: ?>
                        <img src="<?php echo $food_images[$img_index]; ?>" class="card-img-top" alt="<?php echo $item['name']; ?>" style="height: 180px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title"><?php echo $item['name']; ?></h5>
                            <span class="price"><?php echo format_price($item['price']); ?></span>
                        </div>
                        <p class="card-text small text-muted"><?php echo substr($item['description'], 0, 60); echo (strlen($item['description']) > 60) ? '...' : ''; ?></p>
                        <p class="card-text small mb-2">
                            <i class="fas fa-utensils me-1"></i> <?php echo $item['restaurant_name']; ?> <br>
                            <span class="text-muted"><i class="fas fa-tag me-1"></i> <?php echo $item['category_name']; ?></span>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="input-group input-group-sm quantity-control" style="width: 110px">
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
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Add JS for cart functionality -->
<script src="js/cart.js"></script>

<?php
require_once 'includes/footer.php';
?>
