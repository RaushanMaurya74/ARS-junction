<?php
$page_title = "Checkout";
require_once 'includes/header.php';

// Redirect to login if not logged in
if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header("Location: login.php");
    exit;
}

// Get cart items
$cart_items = get_cart_items($_SESSION['user_id']);

// Redirect to cart if cart is empty
if (empty($cart_items)) {
    header("Location: cart.php");
    exit;
}

// Check if cart has items from multiple restaurants
$multiple_restaurants = is_cart_from_multiple_restaurants($_SESSION['user_id']);
if ($multiple_restaurants) {
    $_SESSION['error'] = 'Your cart contains items from multiple restaurants. Please checkout with items from a single restaurant.';
    header("Location: cart.php");
    exit;
}

// Get user info for delivery
$user = get_user_by_id($_SESSION['user_id']);

// Calculate totals
$cart_subtotal = calculate_cart_total($_SESSION['user_id']);

// Get delivery fee from the restaurant
$restaurant_id = $cart_items[0]['restaurant_id'];
$restaurant = get_restaurant_by_id($restaurant_id);
$delivery_fee = $restaurant['delivery_fee'];

// Calculate tax (5% of subtotal)
$tax = $cart_subtotal * 0.05;

// Calculate total
$total = $cart_subtotal + $delivery_fee + $tax;

// Extra JS
$extra_js = '<script src="js/cart.js"></script>';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // This will be handled by the AJAX call to place_order.php
}
?>

<!-- Checkout Section -->
<section class="mb-5">
    <div class="container">
        <h1 class="mb-4">Checkout</h1>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Delivery Details -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Delivery Details</h5>
                    </div>
                    <div class="card-body">
                        <form id="checkout-form" action="api/place_order.php" method="post">
                            <input type="hidden" name="restaurant_id" value="<?php echo $restaurant_id; ?>">
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo $user['name']; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo $user['phone']; ?>" required>
                                <div class="form-text">We'll contact you on this number for delivery updates.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>" required readonly>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="different-address" name="different_address">
                                <label class="form-check-label" for="different-address">Deliver to a different address</label>
                            </div>
                            
                            <div id="delivery-address-form" style="display: none;">
                                <div class="mb-3">
                                    <label for="delivery-address" class="form-label">Delivery Address</label>
                                    <input type="text" class="form-control" id="delivery-address" name="delivery_address">
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="delivery-city" class="form-label">City</label>
                                        <input type="text" class="form-control" id="delivery-city" name="delivery_city">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="delivery-state" class="form-label">State</label>
                                        <input type="text" class="form-control" id="delivery-state" name="delivery_state">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="delivery-zip" class="form-label">ZIP Code</label>
                                        <input type="text" class="form-control" id="delivery-zip" name="delivery_zip">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="delivery-instructions" class="form-label">Delivery Instructions (Optional)</label>
                                <textarea class="form-control" id="delivery-instructions" name="delivery_instructions" rows="2" placeholder="e.g., Ring the doorbell, leave at the door, etc."></textarea>
                            </div>
                    </div>
                </div>
                
                <!-- Payment Method -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Payment Method</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input payment-method" type="radio" name="payment_method" id="payment-cash" value="cash" checked>
                                <label class="form-check-label" for="payment-cash">
                                    <i class="fas fa-money-bill-wave me-2"></i> Cash on Delivery
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input payment-method" type="radio" name="payment_method" id="payment-card" value="card">
                                <label class="form-check-label" for="payment-card">
                                    <i class="fas fa-credit-card me-2"></i> Credit/Debit Card
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input payment-method" type="radio" name="payment_method" id="payment-wallet" value="wallet">
                                <label class="form-check-label" for="payment-wallet">
                                    <i class="fas fa-wallet me-2"></i> Digital Wallet
                                </label>
                            </div>
                        </div>
                        
                        <!-- Card payment details (initially hidden) -->
                        <div id="card-details" class="payment-details" style="display: none;">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="card-name" class="form-label">Name on Card</label>
                                    <input type="text" class="form-control" id="card-name" name="card_name">
                                </div>
                                <div class="col-md-6">
                                    <label for="card-number" class="form-label">Card Number</label>
                                    <input type="text" class="form-control" id="card-number" name="card_number" placeholder="XXXX XXXX XXXX XXXX">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="card-expiry-month" class="form-label">Expiry Month</label>
                                    <select class="form-select" id="card-expiry-month" name="card_expiry_month">
                                        <?php for($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?php echo sprintf('%02d', $i); ?>"><?php echo sprintf('%02d', $i); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="card-expiry-year" class="form-label">Expiry Year</label>
                                    <select class="form-select" id="card-expiry-year" name="card_expiry_year">
                                        <?php $current_year = date('Y'); for($i = $current_year; $i <= $current_year + 10; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="card-cvv" class="form-label">CVV</label>
                                    <input type="text" class="form-control" id="card-cvv" name="card_cvv" placeholder="XXX">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Wallet payment details (initially hidden) -->
                        <div id="wallet-details" class="payment-details" style="display: none;">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="wallet-type" class="form-label">Wallet Type</label>
                                    <select class="form-select" id="wallet-type" name="wallet_type">
                                        <option value="paytm">Paytm</option>
                                        <option value="phonepe">PhonePe</option>
                                        <option value="googlepay">Google Pay</option>
                                        <option value="amazonpay">Amazon Pay</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="wallet-number" class="form-label">Mobile Number</label>
                                    <input type="text" class="form-control" id="wallet-number" name="wallet_number" placeholder="Enter mobile number linked to wallet">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Order Summary -->
                <div class="card mb-4 sticky-top" style="top: 85px;">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="restaurant-info mb-3">
                            <h6><?php echo $restaurant['name']; ?></h6>
                            <p class="text-muted small mb-0"><?php echo $restaurant['address']; ?>, <?php echo $restaurant['city']; ?></p>
                        </div>
                        
                        <hr>
                        
                        <div class="order-items">
                            <?php foreach($cart_items as $item): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?php echo $item['quantity']; ?> × <?php echo $item['name']; ?></span>
                                <span><?php echo format_price($item['price'] * $item['quantity']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span id="cart-subtotal"><?php echo format_price($cart_subtotal); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Delivery Fee:</span>
                            <span id="delivery-fee"><?php echo format_price($delivery_fee); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax (5%):</span>
                            <span id="tax-amount"><?php echo format_price($tax); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Discount:</span>
                            <span id="discount-amount">₹0.00</span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total:</strong>
                            <strong id="total-amount"><?php echo format_price($total); ?></strong>
                        </div>
                        
                        <div class="mb-3">
                            <form id="promo-code-form">
                                <div class="input-group">
                                    <input type="text" id="promo-code" class="form-control" placeholder="Promo Code">
                                    <button class="btn btn-outline-secondary" type="submit">Apply</button>
                                </div>
                            </form>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100" form="checkout-form">
                            Place Order
                        </button>
                        
                        <div class="text-center mt-3">
                            <a href="cart.php" class="text-decoration-none">
                                <i class="fas fa-arrow-left me-1"></i> Back to Cart
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            </form> <!-- Closing form tag for checkout-form -->
        </div>
    </div>
</section>

<!-- Toast Container for notifications -->
<div id="toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index: 5;"></div>

<script>
    // Handle checkout form submission
    document.addEventListener('DOMContentLoaded', function() {
        const checkoutForm = document.getElementById('checkout-form');
        if (checkoutForm) {
            checkoutForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Validate form
                let isValid = true;
                
                // Required fields validation
                const name = document.getElementById('name').value;
                const phone = document.getElementById('phone').value;
                
                if (name.trim() === '') {
                    showToast('Please enter your full name', 'danger');
                    isValid = false;
                }
                
                if (phone.trim() === '') {
                    showToast('Please enter your phone number', 'danger');
                    isValid = false;
                }
                
                // Check if different address is selected
                const differentAddress = document.getElementById('different-address').checked;
                if (differentAddress) {
                    const deliveryAddress = document.getElementById('delivery-address').value;
                    const deliveryCity = document.getElementById('delivery-city').value;
                    const deliveryState = document.getElementById('delivery-state').value;
                    const deliveryZip = document.getElementById('delivery-zip').value;
                    
                    if (deliveryAddress.trim() === '' || deliveryCity.trim() === '' || 
                        deliveryState.trim() === '' || deliveryZip.trim() === '') {
                        showToast('Please fill in all delivery address fields', 'danger');
                        isValid = false;
                    }
                }
                
                // Check payment method
                const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
                
                if (paymentMethod === 'card') {
                    const cardName = document.getElementById('card-name').value;
                    const cardNumber = document.getElementById('card-number').value;
                    const cardCvv = document.getElementById('card-cvv').value;
                    
                    if (cardName.trim() === '' || cardNumber.trim() === '' || cardCvv.trim() === '') {
                        showToast('Please fill in all card details', 'danger');
                        isValid = false;
                    }
                }
                
                if (paymentMethod === 'wallet') {
                    const walletNumber = document.getElementById('wallet-number').value;
                    
                    if (walletNumber.trim() === '') {
                        showToast('Please enter your wallet mobile number', 'danger');
                        isValid = false;
                    }
                }
                
                if (isValid) {
                    // Submit form using AJAX
                    const formData = new FormData(checkoutForm);
                    
                    fetch('api/place_order.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Redirect to order confirmation page
                            window.location.href = 'order_confirmation.php?order_id=' + data.order_id;
                        } else {
                            showToast(data.message || 'Failed to place order. Please try again.', 'danger');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('An error occurred while placing your order. Please try again.', 'danger');
                    });
                }
            });
        }
    });
</script>

<?php
require_once 'includes/footer.php';
?>
