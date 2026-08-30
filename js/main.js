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
    
    // Combined Food Filtering (Search & Type Filters)
    const foodFilterBtns = document.querySelectorAll('.food-filter');
    const foodSearchInput = document.getElementById('food-search');
    
    if (foodFilterBtns.length > 0 || foodSearchInput) {
        function filterFood() {
            const activeBtn = document.querySelector('.food-filter.active');
            const typeFilter = activeBtn ? activeBtn.getAttribute('data-filter') : 'all';
            const searchValue = foodSearchInput ? foodSearchInput.value.toLowerCase() : '';
            
            const foodItems = document.querySelectorAll('.food-item');
            foodItems.forEach(item => {
                const foodName = item.querySelector('.card-title').textContent.toLowerCase();
                const descElement = item.querySelector('.card-text');
                const foodDescription = descElement ? descElement.textContent.toLowerCase() : '';
                
                // Check type match
                let matchesType = false;
                if (typeFilter === 'all') {
                    matchesType = true;
                } else {
                    if (item.classList.contains(typeFilter)) {
                        matchesType = true;
                    }
                }
                
                // Check search match
                const matchesSearch = foodName.includes(searchValue) || foodDescription.includes(searchValue);
                
                if (matchesType && matchesSearch) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        if (foodFilterBtns.length > 0) {
            foodFilterBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    foodFilterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    filterFood();
                });
            });
        }
        
        if (foodSearchInput) {
            foodSearchInput.addEventListener('keyup', filterFood);
            const foodForm = foodSearchInput.closest('form');
            if (foodForm) {
                foodForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    filterFood();
                });
            }
        }
    }
    
    // Combined Restaurant Filtering (Search & Category Filters)
    const restaurantFilterBtns = document.querySelectorAll('.restaurant-filter');
    const restaurantSearchInput = document.getElementById('restaurant-search');
    
    if (restaurantFilterBtns.length > 0 || restaurantSearchInput) {
        function filterRestaurants() {
            const activeBtn = document.querySelector('.restaurant-filter.active');
            const categoryFilter = activeBtn ? activeBtn.getAttribute('data-filter') : 'all';
            const searchValue = restaurantSearchInput ? restaurantSearchInput.value.toLowerCase() : '';
            
            const restaurants = document.querySelectorAll('.restaurant-item');
            restaurants.forEach(item => {
                const restaurantName = item.querySelector('.card-title').textContent.toLowerCase();
                const descElement = item.querySelector('.card-text');
                const restaurantDescription = descElement ? descElement.textContent.toLowerCase() : '';
                
                // Check category match
                let matchesCategory = false;
                if (categoryFilter === 'all') {
                    matchesCategory = true;
                } else {
                    const categories = (item.getAttribute('data-category') || '').split(' ');
                    if (categories.includes(categoryFilter)) {
                        matchesCategory = true;
                    }
                }
                
                // Check search match
                const matchesSearch = restaurantName.includes(searchValue) || restaurantDescription.includes(searchValue);
                
                if (matchesCategory && matchesSearch) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        if (restaurantFilterBtns.length > 0) {
            restaurantFilterBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    restaurantFilterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update dropdown toggle label
                    const dropdownToggle = document.getElementById('categoryDropdown');
                    if (dropdownToggle) {
                        dropdownToggle.innerHTML = `<span><i class="fas fa-filter me-2"></i> ${this.textContent}</span>`;
                    }
                    
                    filterRestaurants();
                });
            });
        }
        
        if (restaurantSearchInput) {
            restaurantSearchInput.addEventListener('keyup', filterRestaurants);
            const searchForm = restaurantSearchInput.closest('form');
            if (searchForm) {
                searchForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    filterRestaurants();
                });
            }
        }
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
    const quantityInputs = document.querySelectorAll('.quantity-input, .cart-qty');
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

// Web Audio Synth for Customer order update chime (Delightful double upward beep)
function playCustomerNotificationSound() {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        playNotificationBeep(audioCtx, 659.25, 0.15, 'sine'); // E5
        setTimeout(() => playNotificationBeep(audioCtx, 880, 0.25, 'sine'), 120); // A5
    } catch (e) {
        console.warn('AudioContext failed:', e);
    }
}

function playNotificationBeep(ctx, frequency, duration, type) {
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.type = type;
    osc.frequency.value = frequency;
    gain.gain.setValueAtTime(0.08, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.00001, ctx.currentTime + duration);
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.start();
    osc.stop(ctx.currentTime + duration);
}

// Polling Customer Order Updates
document.addEventListener('DOMContentLoaded', function() {
    let lastOrderStatuses = {}; // Store order_id -> status mapping
    let pollIntervalId = null;

    function pollCustomerOrders() {
        // Find path prefix dynamically depending on page location
        const pathPrefix = window.location.pathname.includes('/admin/') || window.location.pathname.includes('/delivery/') ? '../' : '';
        fetch(pathPrefix + 'api/poll_notifications.php?role=customer')
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                if (data.message === 'Unauthorized') {
                    // Stop polling if unauthorized (not logged in)
                    if (pollIntervalId) {
                        clearInterval(pollIntervalId);
                    }
                }
                return;
            }

            const currentOrders = data.orders || [];
            let statusChanged = false;

            currentOrders.forEach(order => {
                const orderId = order.order_id;
                const newStatus = order.order_status;
                const oldStatus = lastOrderStatuses[orderId];

                if (oldStatus !== undefined && oldStatus !== newStatus) {
                    statusChanged = true;
                    
                    // Show visual floating banner
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-info border-primary alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x m-3 shadow-lg';
                    alertDiv.style.zIndex = '9999';
                    alertDiv.style.minWidth = '300px';
                    alertDiv.innerHTML = `
                        <strong><i class="fas fa-utensils text-primary me-2"></i>Order Update!</strong> 
                        Your order #${orderId} is now <span class="badge bg-primary text-uppercase">${newStatus}</span>.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.body.appendChild(alertDiv);
                    
                    // Auto close alert
                    setTimeout(() => {
                        alertDiv.remove();
                    }, 6000);

                    // Refresh page if on order_tracking.php
                    const currentFile = window.location.pathname.split("/").pop();
                    if (currentFile === 'order_tracking.php') {
                        setTimeout(() => {
                            window.location.reload();
                        }, 2500);
                    }
                }
                
                // Save the new status
                lastOrderStatuses[orderId] = newStatus;
            });

            // Update statuses and play sound if changed
            if (statusChanged) {
                playCustomerNotificationSound();
            }

            // Also clean up any orders that are no longer active (delivered/cancelled)
            const activeIds = currentOrders.map(o => o.order_id);
            Object.keys(lastOrderStatuses).forEach(id => {
                if (!activeIds.includes(parseInt(id))) {
                    delete lastOrderStatuses[id];
                }
            });
        })
        .catch(err => console.error('Error polling customer orders:', err));
    }

    // Start polling if not in admin or delivery portals
    const currentPath = window.location.pathname;
    if (!currentPath.includes('/admin/') && !currentPath.includes('/delivery/')) {
        // Poll every 6 seconds
        pollIntervalId = setInterval(pollCustomerOrders, 6000);
        // Run initial check
        setTimeout(pollCustomerOrders, 1000);
    }

    // Initialize Modern UI Animations
    initOtpVerificationV2();
    initAnimatedSearchBars();
});

/* ==========================================
   OTP Verification V2 & Search Bar Enhancements
   ========================================== */
function initOtpVerificationV2() {
    const otpContainer = document.querySelector('.otp-card-v2');
    if (!otpContainer) return;

    const inputs = otpContainer.querySelectorAll('.otp-digit-input');
    if (inputs.length === 0) return;

    inputs.forEach((input, index) => {
        // Auto select on focus
        input.addEventListener('focus', () => {
            input.select();
            input.classList.add('active');
        });

        input.addEventListener('blur', () => {
            input.classList.remove('active');
        });

        // Keydown handling for numbers, backspace, arrows
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace') {
                if (!input.value && index > 0) {
                    inputs[index - 1].focus();
                }
            } else if (e.key === 'ArrowLeft' && index > 0) {
                inputs[index - 1].focus();
            } else if (e.key === 'ArrowRight' && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });

        // Input change auto-advance
        input.addEventListener('input', () => {
            const val = input.value.replace(/[^0-9]/g, '');
            input.value = val ? val[val.length - 1] : '';

            if (input.value && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }

            // Check if full digits entered
            checkOtpComplete();
        });

        // Paste support
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pasteData = (e.clipboardData || window.clipboardData).getData('text').trim();
            const digits = pasteData.replace(/[^0-9]/g, '').split('');

            if (digits.length > 0) {
                inputs.forEach((inp, i) => {
                    if (digits[i]) {
                        inp.value = digits[i];
                    }
                });
                const nextIndex = Math.min(digits.length, inputs.length - 1);
                inputs[nextIndex].focus();
                checkOtpComplete();
            }
        });
    });

    function checkOtpComplete() {
        const code = Array.from(inputs).map(i => i.value).join('');
        if (code.length === inputs.length && !code.includes('')) {
            const verifyEvent = new CustomEvent('otpSubmit', { detail: { code } });
            otpContainer.dispatchEvent(verifyEvent);
        }
    }
}

// Global Shake Error trigger for OTP V2
window.triggerOtpShake = function() {
    const card = document.querySelector('.otp-card-v2');
    if (card) {
        card.classList.remove('otp-shake-anim');
        void card.offsetWidth;
        card.classList.add('otp-shake-anim');
        setTimeout(() => card.classList.remove('otp-shake-anim'), 600);
    }
};

// Animated Search Bar Typewriter Placeholders
function initAnimatedSearchBars() {
    const searchInputs = document.querySelectorAll('.animated-gradient-search-bar input');
    if (searchInputs.length === 0) return;

    const placeholders = ["Search 'Classic Pizza'", "Search 'Biryani'", "Search 'Cold Coffee'", "Search 'Burger'"];
    searchInputs.forEach(input => {
        let currentIdx = 0;
        let charIdx = 0;
        let isDeleting = false;
        
        function typeEffect() {
            if (document.activeElement === input || input.value !== '') return;

            const currentText = placeholders[currentIdx];
            if (isDeleting) {
                input.placeholder = currentText.substring(0, charIdx - 1);
                charIdx--;
            } else {
                input.placeholder = currentText.substring(0, charIdx + 1);
                charIdx++;
            }

            let typeSpeed = isDeleting ? 40 : 80;

            if (!isDeleting && charIdx === currentText.length) {
                typeSpeed = 2000;
                isDeleting = true;
            } else if (isDeleting && charIdx === 0) {
                isDeleting = false;
                currentIdx = (currentIdx + 1) % placeholders.length;
                typeSpeed = 400;
            }

            setTimeout(typeEffect, typeSpeed);
        }

        setTimeout(typeEffect, 1000);
    });
}


/* ═══════════════════════════════════════════════════════════════
   FEATURE 9: DARK MODE
   ═══════════════════════════════════════════════════════════════ */
function initDarkMode() {
    const toggleBtns = document.querySelectorAll('.dark-mode-toggle, #dark-mode-toggle');
    const html = document.documentElement;
    const saved = localStorage.getItem('ars_theme') || 'light';
    
    html.setAttribute('data-theme', saved);
    html.setAttribute('data-bs-theme', saved);

    function updateIcons(theme) {
        toggleBtns.forEach(btn => {
            btn.innerHTML = theme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
            btn.setAttribute('title', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
            btn.setAttribute('aria-label', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
        });
    }

    updateIcons(saved);

    toggleBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const current = html.getAttribute('data-theme') || 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', next);
            html.setAttribute('data-bs-theme', next);
            localStorage.setItem('ars_theme', next);
            updateIcons(next);
            this.style.transform = 'rotate(360deg)';
            setTimeout(() => { this.style.transform = ''; }, 400);
        });
    });
}

/* ═══════════════════════════════════════════════════════════════
   FEATURE 5: SMART SEARCH DROPDOWN
   ═══════════════════════════════════════════════════════════════ */
function initSmartSearch() {
    const searchInputs = document.querySelectorAll('.smart-search-input');
    searchInputs.forEach(function(input) {
        const wrapper = input.closest('.search-wrapper');
        if (!wrapper) return;

        let dropdown = wrapper.querySelector('.search-dropdown');
        if (!dropdown) {
            dropdown = document.createElement('div');
            dropdown.className = 'search-dropdown';
            wrapper.appendChild(dropdown);
        }

        let debounceTimer;
        let highlighted = -1;

        input.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const q = this.value.trim();
            if (q.length < 2) { dropdown.classList.remove('visible'); return; }
            debounceTimer = setTimeout(function() { fetchSuggestions(q, dropdown, input); }, 280);
        });

        input.addEventListener('keydown', function(e) {
            const items = dropdown.querySelectorAll('.search-dropdown-item');
            if (e.key === 'ArrowDown') { highlighted = Math.min(highlighted + 1, items.length - 1); highlightItem(items, highlighted); e.preventDefault(); }
            else if (e.key === 'ArrowUp') { highlighted = Math.max(highlighted - 1, 0); highlightItem(items, highlighted); e.preventDefault(); }
            else if (e.key === 'Enter' && highlighted >= 0) { items[highlighted] && items[highlighted].click(); e.preventDefault(); }
            else if (e.key === 'Escape') { dropdown.classList.remove('visible'); highlighted = -1; }
        });

        document.addEventListener('click', function(e) {
            if (!wrapper.contains(e.target)) { dropdown.classList.remove('visible'); highlighted = -1; }
        });
    });
}

function highlightItem(items, idx) {
    items.forEach((el, i) => el.classList.toggle('highlighted', i === idx));
}

function fetchSuggestions(q, dropdown, input) {
    fetch('api/search_suggestions.php?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(function(data) {
            dropdown.innerHTML = '';
            if (!data || data.length === 0) {
                dropdown.innerHTML = '<div class="search-dropdown-empty"><i class="fas fa-search"></i> No results for "' + q + '"</div>';
                dropdown.classList.add('visible');
                return;
            }
            data.forEach(function(item) {
                const dot = item.is_vegetarian ? 'veg' : 'nonveg';
                const el = document.createElement('div');
                el.className = 'search-dropdown-item';
                el.innerHTML = '<img src="' + item.image + '" alt="' + item.name + '" onerror="this.src=\'images/food_placeholder.jpg\'">' +
                    '<div class="search-item-info">' +
                    '<div class="search-item-name"><span class="search-veg-dot ' + dot + '"></span>' + item.name + '</div>' +
                    '<div class="search-item-meta">' + item.restaurant_name + ' &bull; ' + item.category_name + '</div>' +
                    '</div>' +
                    '<div class="search-item-price">&#8377;' + item.price.toFixed(0) + '</div>';
                el.addEventListener('click', function() {
                    input.value = item.name;
                    dropdown.classList.remove('visible');
                    // Trigger food filter if on menu page
                    const filterFn = window.filterFood || null;
                    if (filterFn) filterFn();
                    else { const ev = new Event('input', { bubbles: true }); input.dispatchEvent(ev); }
                });
                dropdown.appendChild(el);
            });
            dropdown.classList.add('visible');
        })
        .catch(function() { dropdown.classList.remove('visible'); });
}

/* ═══════════════════════════════════════════════════════════════
   FEATURE 4: WISHLIST HEARTS
   ═══════════════════════════════════════════════════════════════ */
function initWishlistButtons() {
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.wishlist-btn');
        if (!btn) return;
        e.preventDefault();
        const itemId = btn.getAttribute('data-item-id');
        const formData = new FormData();
        formData.append('item_id', itemId);

        fetch('api/toggle_wishlist.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(function(res) {
                if (!res.success) {
                    if (typeof showToast === 'function') showToast(res.message || 'Login to use wishlist', 'info');
                    return;
                }
                btn.classList.toggle('active', res.added);
                const icon = btn.querySelector('i');
                if (icon) icon.className = res.added ? 'fas fa-heart' : 'far fa-heart';
                btn.classList.add('pop');
                setTimeout(() => btn.classList.remove('pop'), 450);
                // Update wishlist count badge if present
                const badge = document.getElementById('wishlist-count');
                if (badge) badge.textContent = res.wishlist_count;
                if (typeof showToast === 'function') showToast(res.added ? '❤️ Added to wishlist!' : 'Removed from wishlist', res.added ? 'success' : 'info');
            })
            .catch(function() {
                if (typeof showToast === 'function') showToast('Please login to use wishlist', 'info');
            });
    });
}

/* ═══════════════════════════════════════════════════════════════
   FEATURE 8: NOTIFICATION BELL
   ═══════════════════════════════════════════════════════════════ */
function initNotificationBell() {
    const bellBtn = document.getElementById('notif-bell-btn');
    const dropdown = document.getElementById('notif-dropdown');
    const badge = document.getElementById('notif-count');
    if (!bellBtn || !dropdown) return;

    let lastUnread = 0;

    function pollNotifs() {
        fetch('api/poll_notifications.php?role=customer_notif')
            .then(r => r.json())
            .then(function(res) {
                if (!res.success) return;
                const unread = res.unread || 0;
                badge.textContent = unread;
                badge.classList.toggle('visible', unread > 0);
                if (unread > lastUnread) {
                    bellBtn.classList.add('ringing');
                    setTimeout(() => bellBtn.classList.remove('ringing'), 600);
                }
                lastUnread = unread;
                renderNotifDropdown(res.notifications || []);
            })
            .catch(function() {});
    }

    function renderNotifDropdown(items) {
        const body = document.getElementById('notif-body');
        if (!body) return;
        if (items.length === 0) {
            body.innerHTML = '<div class="notif-empty"><i class="fas fa-bell-slash" style="font-size:2rem;color:#eee;display:block;margin-bottom:10px;"></i>No notifications yet</div>';
            return;
        }
        const typeIcons = { order: 'fas fa-receipt', promo: 'fas fa-tag', system: 'fas fa-info-circle' };
        body.innerHTML = items.map(function(n) {
            const cls = n.is_read === '1' || n.is_read === 1 ? '' : 'unread';
            const dot = cls ? '<div class="notif-unread-dot"></div>' : '';
            const href = n.link || '#';
            return '<a href="' + href + '" class="notif-item ' + cls + '">' +
                '<div class="notif-icon-wrap ' + n.type + '"><i class="' + (typeIcons[n.type] || typeIcons.system) + '"></i></div>' +
                '<div style="flex:1;min-width:0">' +
                '<div class="notif-item-title">' + n.title + '</div>' +
                '<div class="notif-item-msg">' + n.message + '</div>' +
                '<div class="notif-item-time">' + n.time_fmt + '</div>' +
                '</div>' + dot + '</a>';
        }).join('');
    }

    bellBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.classList.toggle('open');
        if (dropdown.classList.contains('open')) {
            // Mark read
            fetch('api/poll_notifications.php?role=mark_read').catch(function(){});
            badge.classList.remove('visible');
            lastUnread = 0;
        }
    });

    document.addEventListener('click', function(e) {
        if (!dropdown.contains(e.target) && e.target !== bellBtn) dropdown.classList.remove('open');
    });

    const markReadBtn = document.getElementById('notif-mark-read');
    if (markReadBtn) {
        markReadBtn.addEventListener('click', function() {
            fetch('api/poll_notifications.php?role=mark_read').catch(function(){});
            badge.classList.remove('visible');
            lastUnread = 0;
            document.querySelectorAll('.notif-item.unread').forEach(el => { el.classList.remove('unread'); });
            document.querySelectorAll('.notif-unread-dot').forEach(el => el.remove());
        });
    }

    pollNotifs();
    setInterval(pollNotifs, 30000);
}

/* ═══════════════════════════════════════════════════════════════
   FEATURE 2: INTERACTIVE STAR RATING
   ═══════════════════════════════════════════════════════════════ */
function initStarRating() {
    document.querySelectorAll('.star-rating-widget').forEach(function(widget) {
        const stars = widget.querySelectorAll('.star');
        const hiddenInput = widget.querySelector('input[type="hidden"]');
        let currentRating = parseInt(hiddenInput ? hiddenInput.value : 0) || 0;

        stars.forEach(function(star, idx) {
            star.addEventListener('mouseenter', function() {
                stars.forEach((s, i) => s.classList.toggle('hovered', i <= idx));
            });
            star.addEventListener('mouseleave', function() {
                stars.forEach(s => s.classList.remove('hovered'));
                stars.forEach((s, i) => s.classList.toggle('selected', i < currentRating));
            });
            star.addEventListener('click', function() {
                currentRating = idx + 1;
                if (hiddenInput) hiddenInput.value = currentRating;
                stars.forEach((s, i) => s.classList.toggle('selected', i < currentRating));
                widget.dispatchEvent(new CustomEvent('ratingChange', { detail: currentRating }));
            });
        });
        // Initialise display
        stars.forEach((s, i) => s.classList.toggle('selected', i < currentRating));
    });
}

/* ═══════════════════════════════════════════════════════════════
   FEATURE 3: PROMO CODE ANIMATED VALIDATION
   ═══════════════════════════════════════════════════════════════ */
function initPromoCode() {
    const promoForm = document.getElementById('promo-form');
    if (!promoForm) return;
    const wrap   = promoForm.querySelector('.promo-wrap');
    const input  = promoForm.querySelector('.promo-input');
    const status = promoForm.querySelector('.promo-status');
    const btn    = promoForm.querySelector('.promo-apply-btn');
    const discountRow = document.getElementById('discount-row');
    const discountAmt  = document.getElementById('discount-amount');
    const totalEl      = document.getElementById('order-total-display');

    if (!wrap || !input || !btn) return;

    btn.addEventListener('click', function() {
        const code = input.value.trim().toUpperCase();
        if (!code) { wrap.classList.add('invalid'); setTimeout(() => wrap.classList.remove('invalid'), 500); return; }

        btn.textContent = 'Checking...';
        btn.disabled = true;

        fetch('api/apply_promo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'promo_code=' + encodeURIComponent(code)
        })
        .then(r => r.json())
        .then(function(res) {
            btn.textContent = 'Apply';
            btn.disabled = false;
            wrap.classList.remove('valid', 'invalid');
            status.classList.remove('success', 'error');
            if (res.success) {
                wrap.classList.add('valid');
                status.classList.add('success');
                status.innerHTML = '<i class="fas fa-check-circle"></i> ' + res.message;
                if (discountRow) discountRow.style.display = 'flex';
                if (discountAmt) discountAmt.textContent = '-\u20B9' + parseFloat(res.discount || 0).toFixed(0);
                // Store for form submission
                const hiddenCode = document.getElementById('applied-promo-code');
                if (hiddenCode) hiddenCode.value = code;
            } else {
                wrap.classList.add('invalid');
                status.classList.add('error');
                status.innerHTML = '<i class="fas fa-times-circle"></i> ' + (res.message || 'Invalid promo code');
            }
        })
        .catch(function() {
            btn.textContent = 'Apply';
            btn.disabled = false;
            status.classList.add('error');
            status.innerHTML = '<i class="fas fa-times-circle"></i> Could not verify code. Try again.';
        });
    });

    // Remove error styling on new input
    input.addEventListener('input', function() {
        wrap.classList.remove('valid', 'invalid');
        status.innerHTML = '';
        status.classList.remove('success', 'error');
    });
}

/* ═══════════════════════════════════════════════════════════════
   FEATURE 6: ORDER TIMELINE HELPER (used by order_tracking.php)
   ═══════════════════════════════════════════════════════════════ */
window.buildOrderTimeline = function(status) {
    const steps = ['pending', 'confirmed', 'preparing', 'on the way', 'delivered'];
    const icons = ['fas fa-clock', 'fas fa-check', 'fas fa-utensils', 'fas fa-motorcycle', 'fas fa-flag-checkered'];
    const labels = ['Placed', 'Confirmed', 'Preparing', 'On the Way', 'Delivered'];
    const cancelledHTML = '<div class="timeline-steps"><div class="timeline-step active"><div class="timeline-step-icon" style="background:#dc3545;color:#fff"><i class="fas fa-times"></i></div><div class="timeline-step-label">Cancelled</div></div></div>';
    if (status === 'cancelled') return cancelledHTML;

    const activeIdx = steps.indexOf(status);
    const progressPct = activeIdx >= 0 ? (activeIdx / (steps.length - 1)) * 100 : 0;

    let stepsHTML = steps.map(function(s, i) {
        const done   = i < activeIdx;
        const active = i === activeIdx;
        const cls    = done ? 'done' : (active ? 'active' : '');
        return '<div class="timeline-step ' + cls + '">' +
            '<div class="timeline-step-icon"><i class="' + icons[i] + '"></i></div>' +
            '<div class="timeline-step-label">' + labels[i] + '</div></div>';
    }).join('');

    return '<div class="timeline-steps"><div class="timeline-progress" style="width:' + progressPct + '%"></div>' + stepsHTML + '</div>';
};

/* ═══════════════════════════════════════════════════════════════
   INIT ALL NEW FEATURES ON DOMContentLoaded
   ═══════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function() {
    initDarkMode();
    initSmartSearch();
    initWishlistButtons();
    initNotificationBell();
    initStarRating();
    initPromoCode();
    initAnimatedSearchBars();
});
