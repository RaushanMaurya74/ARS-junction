<?php
// Start session and check authentication before outputting headers
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect to login if not logged in
if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header("Location: login.php");
    exit;
}

$page_title = "Checkout";
require_once 'includes/header.php';

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

// Pre-validate applied promo code in session
$discount_amount = 0.00;
$promo_code_value = '';
if (!empty($_SESSION['applied_promo_code'])) {
    $stmt_promo = $conn->prepare("SELECT * FROM promo_codes WHERE UPPER(code) = UPPER(?) AND is_active = 1 LIMIT 1");
    $stmt_promo->execute([$_SESSION['applied_promo_code']]);
    $promo = $stmt_promo->fetch(PDO::FETCH_ASSOC);
    if ($promo && $cart_subtotal >= (float)$promo['min_order_amount']) {
        $promo_code_value = $promo['code'];
        $val = (float)$promo['discount_value'];
        if ($promo['discount_type'] === 'percentage') {
            $discount_amount = ($cart_subtotal * $val) / 100;
            if (!empty($promo['max_discount_amount'])) {
                $max_d = (float)$promo['max_discount_amount'];
                if ($discount_amount > $max_d) {
                    $discount_amount = $max_d;
                }
            }
        } else {
            $discount_amount = $val;
            if ($discount_amount > $cart_subtotal) {
                $discount_amount = $cart_subtotal;
            }
        }
        $discount_amount = round($discount_amount, 2);
    } else {
        unset($_SESSION['applied_promo_code']);
    }
}

// Get delivery fee from the restaurant (fallback)
$restaurant_id = $cart_items[0]['restaurant_id'];
$restaurant = get_restaurant_by_id($restaurant_id);
$delivery_fee = (float)$restaurant['delivery_fee'];

// Query specific delivery charge for user's default zip code if available
if (!empty($user['zip_code'])) {
    $stmt_pin = $conn->prepare("SELECT delivery_charge FROM delivery_pincodes WHERE pincode = ? AND is_active = 1");
    $stmt_pin->execute([$user['zip_code']]);
    $db_charge = $stmt_pin->fetchColumn();
    if ($db_charge !== false) {
        $delivery_fee = (float)$db_charge;
    }
}

// Calculate tax (5% of subtotal)
$tax = $cart_subtotal * 0.05;

// Calculate total
$total = $cart_subtotal + $delivery_fee + $tax - $discount_amount;

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
                <form id="checkout-form" action="api/place_order.php" method="post">
                <!-- Delivery Details -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Delivery Details</h5>
                    </div>
                    <div class="card-body">
                            <input type="hidden" name="restaurant_id" value="<?php echo $restaurant_id; ?>">
                            <input type="hidden" name="promo_code" id="applied-promo-code" value="<?php echo htmlspecialchars($promo_code_value); ?>">
                            
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
                                <input class="form-check-input payment-method" type="radio" name="payment_method" id="payment-upi" value="upi">
                                <label class="form-check-label" for="payment-upi">
                                    <i class="fas fa-qrcode me-2"></i> UPI QR Code
                                </label>
                            </div>
                        </div>
                        
                        <!-- UPI payment details (initially hidden) -->
                        <div id="upi-details" class="payment-details" style="display: none;">
                            <div class="alert alert-info py-2">
                                <i class="fas fa-info-circle me-2"></i> A dynamic UPI QR code will be generated on the next screen for you to scan and pay.
                            </div>
                        </div>
                    </div>
                </div>
                </form>
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
                            <span id="discount-amount"><?php echo format_price($discount_amount); ?></span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total:</strong>
                            <strong id="total-amount"><?php echo format_price($total); ?></strong>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small"><i class="fas fa-tag me-1 text-success"></i> Promo Code</label>
                            <div id="promo-form">
                                <input type="hidden" id="applied-promo-code" name="promo_code" value="<?php echo htmlspecialchars($promo_code_value); ?>">
                                <div class="promo-wrap <?php echo !empty($promo_code_value) ? 'valid' : ''; ?>">
                                    <input type="text" class="promo-input" id="promo-code" placeholder="ENTER CODE"
                                           value="<?php echo htmlspecialchars($promo_code_value); ?>">
                                    <button type="button" class="promo-apply-btn">Apply</button>
                                </div>
                                <div class="promo-status <?php echo !empty($promo_code_value) ? 'success' : ''; ?>">
                                    <?php if (!empty($promo_code_value)): ?>
                                    <i class="fas fa-check-circle"></i> Code applied: <?php echo htmlspecialchars($promo_code_value); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
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
        </div>
    </div>
</section>

<!-- Toast Container for notifications -->
<div id="toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index: 5;"></div>

<script>
    // Handle checkout form submission
    document.addEventListener('DOMContentLoaded', function() {
        const checkoutForm = document.getElementById('checkout-form');
        
        // Define profile address vars
        const profileAddress = <?php echo json_encode($user['address'] ?? ''); ?>;
        const profileZip = <?php echo json_encode($user['zip_code'] ?? ''); ?>;
        const differentAddressCheckbox = document.getElementById('different-address');
        const deliveryZipInput = document.getElementById('delivery-zip');
        
        function updateDeliveryFee(pincode) {
            if (!/^[0-9]{6}$/.test(pincode)) {
                return;
            }
            
            fetch('api/check_pincode.php?pincode=' + encodeURIComponent(pincode))
            .then(response => response.json())
            .then(data => {
                if (data.deliverable) {
                    const subtotal = parseFloat(<?php echo json_encode($cart_subtotal); ?>);
                    const deliveryFee = parseFloat(data.delivery_charge);
                    const tax = Math.round(subtotal * 0.05 * 100) / 100;
                    const discountEl = document.getElementById('discount-amount');
                    const discount = discountEl ? parsePrice(discountEl.textContent || discountEl.innerHTML) : 0;
                    const total = subtotal + deliveryFee + tax - discount;
                    
                    const deliveryFeeEl = document.getElementById('delivery-fee');
                    if (deliveryFeeEl) deliveryFeeEl.textContent = '₹' + deliveryFee.toFixed(2);
                    
                    const taxAmountEl = document.getElementById('tax-amount');
                    if (taxAmountEl) taxAmountEl.textContent = '₹' + tax.toFixed(2);
                    
                    const totalAmountEl = document.getElementById('total-amount');
                    if (totalAmountEl) totalAmountEl.textContent = '₹' + total.toFixed(2);
                }
            })
            .catch(err => console.error('Error updating delivery fee:', err));
        }

        // Listen for toggling between profile address and custom address
        if (differentAddressCheckbox) {
            differentAddressCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    updateDeliveryFee(deliveryZipInput.value.trim());
                } else {
                    updateDeliveryFee(profileZip.trim());
                }
            });
        }

        // Listen for typing inside custom zip input
        if (deliveryZipInput) {
            deliveryZipInput.addEventListener('input', function() {
                const pincode = this.value.trim();
                if (pincode.length === 6) {
                    updateDeliveryFee(pincode);
                }
            });
        }
        
        if (checkoutForm) {
            checkoutForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Get and disable submit button to prevent double-submits
                const submitBtn = document.querySelector('button[form="checkout-form"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Placing Order...';
                }
                
                function enableSubmitBtn() {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Place Order';
                    }
                }
                
                // Validate form
                let isValid = true;
                
                // Required fields validation
                const name = document.getElementById('name').value;
                const phone = document.getElementById('phone').value;
                
                if (name.trim() === '') {
                    showToast('Please enter your full name', 'danger');
                    enableSubmitBtn();
                    return;
                }
                
                if (phone.trim() === '') {
                    showToast('Please enter your phone number', 'danger');
                    enableSubmitBtn();
                    return;
                }
                
                // Check address selection and validity
                const differentAddress = document.getElementById('different-address').checked;
                let finalZip = '';
                
                if (differentAddress) {
                    const deliveryAddress = document.getElementById('delivery-address').value;
                    const deliveryCity = document.getElementById('delivery-city').value;
                    const deliveryState = document.getElementById('delivery-state').value;
                    const deliveryZip = document.getElementById('delivery-zip').value;
                    
                    if (deliveryAddress.trim() === '' || deliveryCity.trim() === '' || 
                        deliveryState.trim() === '' || deliveryZip.trim() === '') {
                        showToast('Please fill in all delivery address fields. Address is compulsory!', 'danger');
                        enableSubmitBtn();
                        return;
                    }
                    finalZip = deliveryZip.trim();
                } else {
                    // Profile address validation
                    if (profileAddress.trim() === '' || profileZip.trim() === '') {
                        showToast('Your default profile address is incomplete. Please enter a delivery address.', 'warning');
                        // Automatically open different address form
                        document.getElementById('different-address').checked = true;
                        document.getElementById('different-address').dispatchEvent(new Event('change'));
                        enableSubmitBtn();
                        return;
                    }
                    finalZip = profileZip.trim();
                }
                
                // Check if finalZip is valid
                if (finalZip === '') {
                    showToast('ZIP/Pincode is compulsory for delivery verification.', 'danger');
                    enableSubmitBtn();
                    return;
                }
                
                // Query server via AJAX to verify delivery pincode
                showToast('Verifying delivery location...', 'info');
                
                fetch('api/check_pincode.php?pincode=' + encodeURIComponent(finalZip))
                .then(response => response.json())
                .then(data => {
                    if (data.deliverable) {
                        // Place order using AJAX
                        const formData = new FormData(checkoutForm);
                        
                        fetch('api/place_order.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(orderData => {
                            if (orderData.success) {
                                showToast('Order placed successfully! Redirecting...', 'success');
                                setTimeout(() => {
                                    window.location.href = 'order_confirmation.php?order_id=' + orderData.order_id;
                                }, 1000);
                            } else {
                                showToast(orderData.message || 'Failed to place order. Please try again.', 'danger');
                                enableSubmitBtn();
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showToast('An error occurred while placing your order.', 'danger');
                            enableSubmitBtn();
                        });
                    } else {
                        showToast('Delivery not available to ' + finalZip + '. ' + data.message, 'danger');
                        enableSubmitBtn();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error verifying delivery location. Please try again.', 'danger');
                    enableSubmitBtn();
                });
            });
        }
    });
</script>

<?php
require_once 'includes/footer.php';
?>
