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
$result = $conn->query($sql);
$menu_items = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $menu_items[] = $row;
    }
}

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
    "https://pixabay.com/get/g12156b2dbc31bb07d0fc025ac2c9d55a2e380994888ce620bf2df42e2cad88a767656bfd015da042307899468e4fea5a7dac8c5271f4bca3e2557eb58b10cbcc_1280.jpg",
    "https://pixabay.com/get/g00cc480b0593d93c26a23b5426edafddd525a90edb5b4fba09d7860087da5ad4a1db284ab5d9156c7db3337c4f18e99565d714d2f80fa86cdb2ab2521a5ee276_1280.jpg",
    "https://pixabay.com/get/g6dab4081301a35f2eaa4dab90e26e8382b349aff7731c6fbaebdc4d02f74f79bef3ae2efa46e299a9994c2e3c2ca4f47b51a8362d12fe6f673b9ee1d59d222c3_1280.jpg",
    "https://pixabay.com/get/g64e0555caef3f12f1d7115a280d82fb8413f9212dec2d3fb1ea8ab282134b485225984391e3f591e8a535477db67a8b997d34342883959addecd6d8c46a61013_1280.jpg",
    "https://pixabay.com/get/g10786d528a6f89d5e001b5927653ea963c658bdb227414dfae50c760926e0b3ebd7d27197ec9ae9a5bd75db0d41f20fe6a7547c6e439af24d3c04b049fd7a47f_1280.jpg",
    "https://pixabay.com/get/g88c1a943dca246fccfd6cc284add1888dd69d32b088ab38f54080c18c5ed31f80a94daa14599f5e35a3ecf9c9d34b3120088633a604208879589608db6f5c209_1280.jpg",
    "https://pixabay.com/get/g683a2929e4615ce3a6bf61fdf30d6d35b46e9b8b9f3419d63e8dd595c00c12428211e15157d2f63cc00c9f1bf4f19ad28fbd26713abfa85e1670fe5460124258_1280.jpg",
    "https://pixabay.com/get/g73588bd1697fddf3029e16f1b8f2f6cb1361a62e5916feb7dc4c9735d59270447fdeb6a9e0d70a82bef05a35ba6a288e61ce30139f6e1e3be044655518cea643_1280.jpg"
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
                <button class="btn btn-outline-danger food-filter" data-filter="spicy">Spicy</button>
                <button class="btn btn-outline-warning food-filter" data-filter="featured">Featured</button>
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
                $img_index = $index % 8;
                
                // Add classes for filtering
                $filter_classes = 'food-item';
                if ($item['is_vegetarian']) $filter_classes .= ' vegetarian';
                if ($item['is_spicy']) $filter_classes .= ' spicy';
                if ($item['is_featured']) $filter_classes .= ' featured';
            ?>
            <div class="col-md-6 col-lg-3 mb-4 <?php echo $filter_classes; ?>">
                <div class="card food-card h-100">
                    <div class="food-badges">
                        <?php if($item['is_vegetarian']): ?>
                        <span class="badge bg-success">Veg</span>
                        <?php endif; ?>
                        <?php if($item['is_spicy']): ?>
                        <span class="badge bg-danger">Spicy</span>
                        <?php endif; ?>
                        <?php if($item['is_featured']): ?>
                        <span class="badge bg-warning text-dark">Featured</span>
                        <?php endif; ?>
                    </div>
                    <img src="<?php echo $food_images[$img_index]; ?>" class="card-img-top" alt="<?php echo $item['name']; ?>">
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
