<?php
// Start session and check authentication before outputting headers
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect to login if not logged in
if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'cart.php';
    header("Location: login.php");
    exit;
}

$page_title = "Shopping Cart";
require_once 'includes/header.php';

// Get cart items
$cart_items = get_cart_items($_SESSION['user_id']);
$cart_subtotal = calculate_cart_total($_SESSION['user_id']);

// Check if cart has items from multiple restaurants
$multiple_restaurants = is_cart_from_multiple_restaurants($_SESSION['user_id']);

// Get user info for delivery
$user = get_user_by_id($_SESSION['user_id']);

// Extra JS
$extra_js = '<script src="js/cart.js"></script>';
?>

<!-- Cart Content Section -->
<section class="mb-5">
    <div class="container">
        <h1 class="mb-4">Your Cart</h1>
        
        <?php if(empty($cart_items)): ?>
        <div class="alert alert-info">
            Your cart is empty. <a href="menu.php">Browse our menu</a> to add items.
        </div>
        <?php else: ?>
        
        <?php if($multiple_restaurants): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i> Your cart contains items from multiple restaurants. Please note that you need to place separate orders for each restaurant.
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8" id="cart-items-container">
                <?php 
                $current_restaurant_id = 0;
                foreach($cart_items as $item): 
                    if($current_restaurant_id != $item['restaurant_id']):
                        $current_restaurant_id = $item['restaurant_id'];
                ?>
                <?php if($current_restaurant_id != $cart_items[0]['restaurant_id']): ?>
                </div>
                <?php endif; ?>
                
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <a href="restaurant_details.php?id=<?php echo $item['restaurant_id']; ?>" class="text-decoration-none">
                                <?php echo $item['restaurant_name']; ?>
                            </a>
                        </h5>
                    </div>
                    <div class="card-body">
                <?php endif; ?>
                
                <div class="cart-item d-flex align-items-center mb-3" data-cart-id="<?php echo $item['cart_id']; ?>">
                    <?php if (!empty($item['image']) && file_exists($item['image'])): ?>
                        <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="cart-item-img me-3" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;">
                    <?php else: ?>
                        <img src="images/food_placeholder.jpg" alt="<?php echo $item['name']; ?>" class="cart-item-img me-3" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;">
                    <?php endif; ?>
                    <div class="flex-grow-1">
                        <h5 class="mb-1"><?php echo $item['name']; ?></h5>
                        <p class="mb-1 text-muted small"><?php echo format_price($item['price']); ?> each</p>
                        <div class="d-flex align-items-center">
                            <div class="input-group input-group-sm quantity-control me-3" style="width: 110px">
                                <button class="btn btn-outline-secondary decrease-qty" type="button">-</button>
                                <input type="number" class="form-control text-center cart-qty" data-cart-id="<?php echo $item['cart_id']; ?>" value="<?php echo $item['quantity']; ?>" min="1" max="10">
                                <button class="btn btn-outline-secondary increase-qty" type="button">+</button>
                            </div>
                            <button class="btn btn-outline-danger btn-sm remove-from-cart-btn" data-cart-id="<?php echo $item['cart_id']; ?>">
                                <i class="fas fa-trash-alt"></i> Remove
                            </button>
                        </div>
                    </div>
                    <div class="text-end ms-3">
                        <span class="cart-item-subtotal fw-bold" data-cart-id="<?php echo $item['cart_id']; ?>">
                            <?php echo format_price($item['price'] * $item['quantity']); ?>
                        </span>
                    </div>
                </div>
                
                <?php 
                    // Check if this is the last item for this restaurant
                    $next_index = array_search($item, $cart_items) + 1;
                    $is_last_item_for_restaurant = ($next_index >= count($cart_items) || 
                                                   $cart_items[$next_index]['restaurant_id'] != $item['restaurant_id']);
                    
                    if ($is_last_item_for_restaurant): 
                ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php endforeach; ?>
            </div>
            
            <div class="col-lg-4">
                <div class="cart-summary">
                    <h5 class="mb-3">Order Summary</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span id="cart-subtotal"><?php echo format_price($cart_subtotal); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Delivery Fee:</span>
                        <span id="delivery-fee"><?php echo format_price(40.00); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tax (5%):</span>
                        <span id="tax-amount"><?php echo format_price($cart_subtotal * 0.05); ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total:</strong>
                        <strong id="total-amount"><?php echo format_price($cart_subtotal + 40.00 + ($cart_subtotal * 0.05)); ?></strong>
                    </div>
                    
                    <div class="mb-3">
                        <form id="promo-code-form">
                            <div class="input-group">
                                <input type="text" id="promo-code" class="form-control" placeholder="Promo Code">
                                <button class="btn btn-outline-secondary" type="submit">Apply</button>
                            </div>
                        </form>
                    </div>
                    
                    <a href="checkout.php" class="btn btn-primary w-100" id="checkout-btn">
                        Proceed to Checkout
                    </a>
                    
                    <div class="text-center mt-3">
                        <a href="menu.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i> Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Toast Container for notifications -->
<div id="toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index: 5;"></div>

<?php
require_once 'includes/footer.php';
?>
