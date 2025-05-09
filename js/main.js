/**
 * Main JavaScript file for ARS JUNCTION Food Ordering Platform
 */

// Wait for the DOM to be loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            
            if (href !== "#") {
                e.preventDefault();
                
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            }
        });
    });
    
    // Back to top button functionality
    let backToTopBtn = document.getElementById("back-to-top");
    
    if (backToTopBtn) {
        window.onscroll = function() {
            if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
                backToTopBtn.style.display = "block";
            } else {
                backToTopBtn.style.display = "none";
            }
        };
        
        backToTopBtn.addEventListener("click", function() {
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        });
    }
    
    // Food filtering on menu page
    const foodFilterBtns = document.querySelectorAll('.food-filter');
    if (foodFilterBtns.length > 0) {
        foodFilterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const filterValue = this.getAttribute('data-filter');
                
                // Remove active class from all buttons
                foodFilterBtns.forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Filter food items
                const foodItems = document.querySelectorAll('.food-item');
                
                foodItems.forEach(item => {
                    if (filterValue === 'all') {
                        item.style.display = 'block';
                    } else {
                        if (item.classList.contains(filterValue)) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    }
                });
            });
        });
    }
    
    // Restaurant filtering on restaurants page
    const restaurantFilterBtns = document.querySelectorAll('.restaurant-filter');
    if (restaurantFilterBtns.length > 0) {
        restaurantFilterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const filterValue = this.getAttribute('data-filter');
                
                // Remove active class from all buttons
                restaurantFilterBtns.forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Filter restaurants
                const restaurants = document.querySelectorAll('.restaurant-item');
                
                restaurants.forEach(item => {
                    if (filterValue === 'all') {
                        item.style.display = 'block';
                    } else {
                        if (item.getAttribute('data-category') === filterValue) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    }
                });
            });
        });
    }
    
    // Restaurant search functionality
    const restaurantSearchInput = document.getElementById('restaurant-search');
    if (restaurantSearchInput) {
        restaurantSearchInput.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const restaurants = document.querySelectorAll('.restaurant-item');
            
            restaurants.forEach(item => {
                const restaurantName = item.querySelector('.card-title').textContent.toLowerCase();
                
                if (restaurantName.includes(searchValue)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
    
    // Food search functionality
    const foodSearchInput = document.getElementById('food-search');
    if (foodSearchInput) {
        foodSearchInput.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const foodItems = document.querySelectorAll('.food-item');
            
            foodItems.forEach(item => {
                const foodName = item.querySelector('.card-title').textContent.toLowerCase();
                
                if (foodName.includes(searchValue)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
    
    // Star rating functionality
    const ratingInputs = document.querySelectorAll('.rating-input');
    if (ratingInputs.length > 0) {
        ratingInputs.forEach(input => {
            input.addEventListener('change', function() {
                const ratingValue = this.value;
                const ratingStars = this.parentElement.querySelectorAll('label i');
                
                ratingStars.forEach((star, index) => {
                    if (index < ratingValue) {
                        star.classList.add('fas');
                        star.classList.remove('far');
                    } else {
                        star.classList.add('far');
                        star.classList.remove('fas');
                    }
                });
            });
        });
    }
    
    // Quantity input functionality
    const quantityInputs = document.querySelectorAll('.quantity-input');
    if (quantityInputs.length > 0) {
        quantityInputs.forEach(input => {
            const decreaseBtn = input.parentElement.querySelector('.decrease-qty');
            const increaseBtn = input.parentElement.querySelector('.increase-qty');
            
            decreaseBtn.addEventListener('click', function() {
                let value = parseInt(input.value);
                if (value > 1) {
                    value--;
                    input.value = value;
                    
                    // Trigger change event for cart update
                    if (input.classList.contains('cart-qty')) {
                        const event = new Event('change');
                        input.dispatchEvent(event);
                    }
                }
            });
            
            increaseBtn.addEventListener('click', function() {
                let value = parseInt(input.value);
                value++;
                input.value = value;
                
                // Trigger change event for cart update
                if (input.classList.contains('cart-qty')) {
                    const event = new Event('change');
                    input.dispatchEvent(event);
                }
            });
        });
    }
    
    // Address form toggling in checkout
    const differentAddressCheckbox = document.getElementById('different-address');
    if (differentAddressCheckbox) {
        const deliveryAddressForm = document.getElementById('delivery-address-form');
        
        differentAddressCheckbox.addEventListener('change', function() {
            if (this.checked) {
                deliveryAddressForm.style.display = 'block';
            } else {
                deliveryAddressForm.style.display = 'none';
            }
        });
    }
    
    // Payment method toggling in checkout
    const paymentMethods = document.querySelectorAll('.payment-method');
    if (paymentMethods.length > 0) {
        const paymentForms = document.querySelectorAll('.payment-details');
        
        paymentMethods.forEach(method => {
            method.addEventListener('change', function() {
                const paymentType = this.value;
                
                paymentForms.forEach(form => {
                    if (form.id === `${paymentType}-details`) {
                        form.style.display = 'block';
                    } else {
                        form.style.display = 'none';
                    }
                });
            });
        });
    }
});

// Function to update cart after add/remove operations
function updateCartCount(count) {
    const cartCountElement = document.getElementById('cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = count;
        
        // Add a small animation to highlight the change
        cartCountElement.classList.add('animate__animated', 'animate__heartBeat');
        
        setTimeout(() => {
            cartCountElement.classList.remove('animate__animated', 'animate__heartBeat');
        }, 1000);
    }
}

// Function to show toast notifications
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toast-container');
    
    if (!toastContainer) {
        // Create toast container if it doesn't exist
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '5';
        document.body.appendChild(container);
    }
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    // Create toast content
    const toastContent = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toast.innerHTML = toastContent;
    
    // Add toast to container
    document.getElementById('toast-container').appendChild(toast);
    
    // Initialize and show the toast
    const bsToast = new bootstrap.Toast(toast, {
        animation: true,
        autohide: true,
        delay: 3000
    });
    
    bsToast.show();
    
    // Remove toast after it's hidden
    toast.addEventListener('hidden.bs.toast', function() {
        this.remove();
    });
}
