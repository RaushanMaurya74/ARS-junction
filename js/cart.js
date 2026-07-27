/**
 * Cart functionality for ARS JUNCTION Food Ordering Platform
 */

document.addEventListener('DOMContentLoaded', function() {
    // Attach modern button class to initial buttons
    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        if (!btn.classList.contains('animated-add-to-cart-btn')) {
            btn.classList.add('animated-add-to-cart-btn');
        }
    });

    // Global Event Delegation for Add to Cart Buttons
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.add-to-cart-btn');
        if (btn) {
            e.preventDefault();
            
            const itemId = btn.getAttribute('data-item-id');
            const quantityInput = document.querySelector(`.quantity-input[data-item-id="${itemId}"]`);
            const quantity = quantityInput ? parseInt(quantityInput.value) : 1;
            
            // Trigger Fly to Cart animation visually
            triggerFlyToCart(btn);
            
            addToCart(itemId, quantity, btn);
        }
    });
    
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
            const subtotalElement = document.getElementById('cart-subtotal');
            
            // If they enter empty code, clear the coupon
            if (promoCode.trim() === '') {
                fetch('api/apply_promo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `code=&subtotal=0`
                })
                .then(response => response.json())
                .then(data => {
                    const discountElement = document.getElementById('discount-amount');
                    if (discountElement) {
                        discountElement.innerHTML = formatCurrency(0.00);
                        const hiddenPromoInput = document.getElementById('applied-promo-code');
                        if (hiddenPromoInput) {
                            hiddenPromoInput.value = '';
                        }
                    }
                    calculateOrderSummary();
                    showToast('Promo code cleared.');
                });
                return;
            }
            
            if (!subtotalElement) {
                showToast('Unable to determine cart subtotal', 'danger');
                return;
            }
            
            const subtotal = parsePrice(subtotalElement.textContent || subtotalElement.innerHTML);
            
            // AJAX call to apply promo API
            fetch('api/apply_promo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `code=${encodeURIComponent(promoCode)}&subtotal=${subtotal}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const discountElement = document.getElementById('discount-amount');
                    if (discountElement) {
                        discountElement.innerHTML = formatCurrency(data.discount_amount);
                        // Save applied code value in hidden input if on checkout page
                        const hiddenPromoInput = document.getElementById('applied-promo-code');
                        if (hiddenPromoInput) {
                            hiddenPromoInput.value = data.code;
                        }
                    }
                    calculateOrderSummary();
                    showToast(data.message || 'Promo code applied successfully!');
                } else {
                    showToast(data.message || 'Invalid promo code', 'danger');
                }
            })
            .catch(error => {
                console.error('Error applying promo code:', error);
                showToast('An error occurred while applying the promo code', 'danger');
            });
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
            showToast('Item added to cart successfully!', 'success');
            updateCartCount(data.cart_count);
        } else {
            showToast(data.message || 'Failed to add item to cart', 'danger');
            if (data.message && data.message.toLowerCase().includes('login')) {
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 1500);
            }
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

/**
 * Update the navbar cart counter badge with animation pulse
 */
function updateCartCount(count) {
    const cartCountEl = document.getElementById('cart-count');
    if (cartCountEl) {
        cartCountEl.textContent = count;
        cartCountEl.classList.remove('cart-badge-bounce');
        void cartCountEl.offsetWidth; // Trigger reflow for animation restart
        cartCountEl.classList.add('cart-badge-bounce');
    }
}

/**
 * Fly to Cart Animation (JeetSaru Vanilla JS Style)
 * Clones product image from card and animates top/left/width/height/opacity directly into header shopping cart.
 */
function triggerFlyToCart(buttonElement) {
    if (!buttonElement) return;

    let shopping_cart = document.getElementById('cart-count') || document.querySelector('.fa-shopping-cart') || document.querySelector('.navbar-brand');
    let cart_top = shopping_cart ? shopping_cart.getBoundingClientRect().top : 20;
    let cart_left = shopping_cart ? shopping_cart.getBoundingClientRect().left : window.innerWidth - 60;

    const card = buttonElement.closest('.card') || buttonElement.closest('.food-item') || buttonElement.closest('.menu-item') || buttonElement.parentElement;
    const sourceImg = card ? card.querySelector('img') : null;

    let img_clone;
    let img_top = 100, img_left = 100, img_width = 100, img_height = 100;

    if (sourceImg) {
        // JeetSaru method: img.cloneNode(true)
        img_clone = sourceImg.cloneNode(true);
        const rect = sourceImg.getBoundingClientRect();
        img_top = rect.top;
        img_left = rect.left;
        img_width = rect.width || sourceImg.offsetWidth || 100;
        img_height = rect.height || sourceImg.offsetHeight || 100;
    } else {
        // Fallback food icon element if img tag is missing
        img_clone = document.createElement('div');
        img_clone.innerHTML = '<i class="fas fa-utensils text-white fs-4"></i>';
        img_clone.style.backgroundColor = '#ff5722';
        img_clone.style.display = 'flex';
        img_clone.style.alignItems = 'center';
        img_clone.style.justifyContent = 'center';
        
        const btnRect = buttonElement.getBoundingClientRect();
        img_top = btnRect.top - 20;
        img_left = btnRect.left;
        img_width = 60;
        img_height = 60;
    }

    img_clone.classList.add('img-clone');

    img_clone.style.position = 'fixed';
    img_clone.style.zIndex = '999999';
    img_clone.style.top = img_top + 'px';
    img_clone.style.left = img_left + 'px';
    img_clone.style.width = img_width + 'px';
    img_clone.style.height = img_height + 'px';
    img_clone.style.opacity = '1';
    img_clone.style.pointerEvents = 'none';

    document.body.appendChild(img_clone);

    if (shopping_cart && shopping_cart.classList) {
        shopping_cart.classList.add('active');
    }

    // Force reflow for smooth start
    void img_clone.offsetWidth;

    setTimeout(() => {
        img_clone.style.top = cart_top + 'px';
        img_clone.style.left = cart_left + 'px';
        img_clone.style.width = '35px';
        img_clone.style.height = '35px';
        img_clone.style.opacity = '0.15';
        img_clone.style.borderRadius = '50%';
        img_clone.style.transform = 'rotate(360deg)';
    }, 40);

    setTimeout(() => {
        if (img_clone && img_clone.parentNode) {
            img_clone.remove();
        }
        if (shopping_cart && shopping_cart.classList) {
            shopping_cart.classList.remove('active');
        }
        const cartCountEl = document.getElementById('cart-count');
        updateCartCount(cartCountEl ? cartCountEl.textContent : 0);
    }, 850);
}

/**
 * Interactive Drag to Cart Initialization (Drag food image straight into navbar cart icon)
 */
function initDragAndDropToCart() {
    const foodCards = document.querySelectorAll('.card, .food-item, .menu-item');
    const cartCountEl = document.getElementById('cart-count');
    const cartNavLink = cartCountEl ? cartCountEl.closest('a') : document.querySelector('.fa-shopping-cart')?.closest('a');

    if (!cartNavLink) return;

    foodCards.forEach(card => {
        const img = card.querySelector('img');
        const btn = card.querySelector('.add-to-cart-btn');
        if (!img || !btn) return;

        const itemId = btn.getAttribute('data-item-id');
        if (!itemId) return;

        img.setAttribute('draggable', 'true');

        // Add subtle hover badge hint "Drag to Cart 🛒"
        if (!card.querySelector('.drag-cart-hint') && card.classList.contains('food-card')) {
            const hint = document.createElement('span');
            hint.className = 'drag-cart-hint';
            hint.innerHTML = '<i class="fas fa-hand-pointer me-1"></i>Drag to Cart';
            card.style.position = 'relative';
            card.appendChild(hint);
        }

        // HTML5 Drag Events
        img.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('text/plain', itemId);
            e.dataTransfer.effectAllowed = 'copy';
            cartNavLink.classList.add('cart-drop-target-active');
        });

        img.addEventListener('dragend', () => {
            cartNavLink.classList.remove('cart-drop-target-active');
        });
    });

    // Cart Drop Zone listeners
    cartNavLink.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
        cartNavLink.classList.add('cart-drop-target-active');
    });

    cartNavLink.addEventListener('dragleave', () => {
        cartNavLink.classList.remove('cart-drop-target-active');
    });

    cartNavLink.addEventListener('drop', (e) => {
        e.preventDefault();
        cartNavLink.classList.remove('cart-drop-target-active');
        const itemId = e.dataTransfer.getData('text/plain');

        if (itemId) {
            const btn = document.querySelector(`.add-to-cart-btn[data-item-id="${itemId}"]`);
            if (btn) {
                triggerFlyToCart(btn);
            }
            addToCart(itemId, 1);
            showToast('Item dragged into cart successfully!', 'success');
        }
    });
}

// Call drag and drop setup when DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDragAndDropToCart);
} else {
    initDragAndDropToCart();
}


