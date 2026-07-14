/**
 * Cart functionality for ARS JUNCTION Food Ordering Platform
 */

document.addEventListener('DOMContentLoaded', function() {
    // Handle add to cart buttons
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    
    if (addToCartButtons.length > 0) {
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const itemId = this.getAttribute('data-item-id');
                const quantityInput = document.querySelector(`.quantity-input[data-item-id="${itemId}"]`);
                const quantity = quantityInput ? parseInt(quantityInput.value) : 1;
                
                addToCart(itemId, quantity);
            });
        });
    }
    
    // Handle quantity change in cart
    const cartQuantityInputs = document.querySelectorAll('.cart-qty');
    
    if (cartQuantityInputs.length > 0) {
        cartQuantityInputs.forEach(input => {
            input.addEventListener('change', function() {
                const cartId = this.getAttribute('data-cart-id');
                const quantity = parseInt(this.value);
                
                updateCartItem(cartId, quantity);
            });
        });
    }
    
    // Handle remove from cart buttons
    const removeFromCartButtons = document.querySelectorAll('.remove-from-cart-btn');
    
    if (removeFromCartButtons.length > 0) {
        removeFromCartButtons.forEach(button => {
            button.addEventListener('click', function() {
                const cartId = this.getAttribute('data-cart-id');
                
                removeFromCart(cartId);
            });
        });
    }
    
    // Calculate order summary in checkout page
    calculateOrderSummary();
    
    // Handle promo code form
    const promoCodeForm = document.getElementById('promo-code-form');
    
    if (promoCodeForm) {
        promoCodeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const promoCode = document.getElementById('promo-code').value;
            
            if (promoCode.trim() === '') {
                showToast('Please enter a promo code', 'danger');
                return;
            }
            
            // Mock promo code verification - in real app would check against database
            if (promoCode.toUpperCase() === 'WELCOME10') {
                applyDiscount(10);
                showToast('Promo code applied successfully!');
            } else {
                showToast('Invalid promo code', 'danger');
            }
        });
    }
    // General Decrease Quantity control (for both menu pages and cart page)
    document.querySelectorAll('.decrease-qty').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.cart-qty, .quantity-input');
            if (input) {
                let val = parseInt(input.value) || 1;
                if (val > 1) {
                    input.value = val - 1;
                    input.dispatchEvent(new Event('change'));
                }
            }
        });
    });

    // General Increase Quantity control (for both menu pages and cart page)
    document.querySelectorAll('.increase-qty').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.cart-qty, .quantity-input');
            if (input) {
                let val = parseInt(input.value) || 1;
                if (val < 10) {
                    input.value = val + 1;
                    input.dispatchEvent(new Event('change'));
                }
            }
        });
    });
});

/**
 * Add an item to the cart
 */
function addToCart(itemId, quantity) {
    // AJAX request to add item to cart
    fetch('api/add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `item_id=${itemId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Item added to cart successfully!');
            updateCartCount(data.cart_count);
        } else {
            showToast(data.message || 'Failed to add item to cart', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while adding to cart', 'danger');
    });
}

/**
 * Update a cart item quantity
 */
function updateCartItem(cartId, quantity) {
    // AJAX request to update cart item
    fetch('api/update_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `cart_id=${cartId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the item subtotal
            const itemSubtotalElement = document.querySelector(`.cart-item-subtotal[data-cart-id="${cartId}"]`);
            if (itemSubtotalElement) {
                itemSubtotalElement.innerHTML = data.item_subtotal;
            }
            
            // Update cart summary
            updateCartSummary(data.cart_total, data.cart_count);
            
            showToast('Cart updated successfully!');
        } else {
            showToast(data.message || 'Failed to update cart', 'danger');
            
            // Revert quantity change on failure
            const quantityInput = document.querySelector(`.cart-qty[data-cart-id="${cartId}"]`);
            if (quantityInput && data.original_quantity) {
                quantityInput.value = data.original_quantity;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while updating cart', 'danger');
    });
}

/**
 * Remove an item from the cart
 */
function removeFromCart(cartId) {
    // AJAX request to remove cart item
    fetch('api/remove_from_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `cart_id=${cartId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the item from the UI
            const cartItem = document.querySelector(`.cart-item[data-cart-id="${cartId}"]`);
            if (cartItem) {
                cartItem.remove();
            }
            
            // Update cart summary
            updateCartSummary(data.cart_total, data.cart_count);
            
            // Show empty cart message if cart is empty
            if (data.cart_count === 0) {
                const cartContainer = document.getElementById('cart-items-container');
                if (cartContainer) {
                    cartContainer.innerHTML = '<div class="alert alert-info">Your cart is empty. <a href="menu.php">Browse our menu</a> to add items.</div>';
                }
                
                // Hide checkout button
                const checkoutBtn = document.getElementById('checkout-btn');
                if (checkoutBtn) {
                    checkoutBtn.style.display = 'none';
                }
            }
            
            showToast('Item removed from cart successfully!');
        } else {
            showToast(data.message || 'Failed to remove item from cart', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while removing from cart', 'danger');
    });
}

/**
 * Update cart summary information
 */
function updateCartSummary(total, count) {
    // Update the cart count in the navbar
    updateCartCount(count);
    
    // Update subtotal
    const subtotalElement = document.getElementById('cart-subtotal');
    if (subtotalElement) {
        subtotalElement.innerHTML = total;
    }
    
    // Update total
    calculateOrderSummary();
}

/**
 * Calculate order summary in checkout page
 */
function parsePrice(text) {
    if (!text) return 0;
    // Strip out HTML Rupee entities and unicode symbols to prevent numeric errors (e.g. &#8377; -> 8377)
    let clean = text.replace(/&#8377;/g, '').replace(/₹/g, '').replace(/[^0-9.]/g, '');
    return parseFloat(clean) || 0;
}

function calculateOrderSummary() {
    const subtotalElement = document.getElementById('cart-subtotal');
    const deliveryFeeElement = document.getElementById('delivery-fee');
    const taxElement = document.getElementById('tax-amount');
    const discountElement = document.getElementById('discount-amount');
    const totalElement = document.getElementById('total-amount');
    
    if (subtotalElement && deliveryFeeElement && taxElement && totalElement) {
        // Get values and convert to numbers using safe parsePrice helper
        const subtotal = parsePrice(subtotalElement.textContent || subtotalElement.innerHTML);
        const deliveryFee = parsePrice(deliveryFeeElement.textContent || deliveryFeeElement.innerHTML);
        
        // Calculate 5% tax dynamically and update taxElement
        const tax = subtotal * 0.05;
        taxElement.innerHTML = formatCurrency(tax);
        
        let discount = 0;
        if (discountElement) {
            discount = parsePrice(discountElement.textContent || discountElement.innerHTML);
        }
        
        // Calculate total
        const total = subtotal + deliveryFee + tax - discount;
        
        // Update total element with formatted value
        totalElement.innerHTML = formatCurrency(total);
    }
}

/**
 * Apply discount to order
 */
function applyDiscount(percentDiscount) {
    const subtotalElement = document.getElementById('cart-subtotal');
    const discountElement = document.getElementById('discount-amount');
    
    if (subtotalElement && discountElement) {
        const subtotal = parsePrice(subtotalElement.textContent || subtotalElement.innerHTML);
        const discountAmount = (subtotal * percentDiscount) / 100;
        
        // Update discount amount element
        discountElement.innerHTML = formatCurrency(discountAmount);
        
        // Recalculate total
        calculateOrderSummary();
    }
}

/**
 * Format currency value
 */
function formatCurrency(value) {
    return '₹' + value.toFixed(2);
}
