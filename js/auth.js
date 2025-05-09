/**
 * Authentication functionality for ARS JUNCTION Food Ordering Platform
 */

// Parse JWT token (for Google Sign-In)
function parseJwt(token) {
    try {
        const base64Url = token.split('.')[1];
        const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));

        return JSON.parse(jsonPayload);
    } catch (e) {
        console.error('Error parsing JWT:', e);
        return null;
    }
}

// Validate email format
function validateEmail(email) {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}

// Validate phone number format
function validatePhone(phone) {
    const re = /^\d{10}$/; // Basic validation for 10-digit phone number
    return re.test(String(phone));
}

// Toggle password visibility
function togglePasswordVisibility(inputId, toggleBtnId) {
    const passwordInput = document.getElementById(inputId);
    const toggleBtn = document.getElementById(toggleBtnId);
    
    if (passwordInput && toggleBtn) {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i>';
        } else {
            passwordInput.type = 'password';
            toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
        }
    }
}

// Social login (Facebook/Google) handler
function socialLogin(provider, socialId, name, email) {
    // AJAX request to handle social login
    fetch('api/social_login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `provider=${provider}&social_id=${socialId}&name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirect to specified page or default to home
            window.location.href = data.redirect || 'index.php';
        } else {
            showToast(data.message || 'Social login failed', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred during social login', 'danger');
    });
}

// Handle Google Sign-In response
function handleGoogleSignIn(response) {
    // Decode the JWT token to get user info
    const payload = parseJwt(response.credential);
    
    if (payload) {
        // Send to backend for authentication/registration
        socialLogin('google', payload.sub, payload.name, payload.email);
    } else {
        showToast('Failed to process Google login', 'danger');
    }
}

// Setup form handlers
function setupFormHandlers() {
    // Handle login form submission
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            // Validate form
            if (!validateEmail(email)) {
                showToast('Please enter a valid email address', 'danger');
                return;
            }
            
            if (password.trim() === '') {
                showToast('Please enter your password', 'danger');
                return;
            }
            
            // Submit form
            this.submit();
        });
    }
    
    // Handle registration form submission
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            // Validate form
            if (name.trim() === '') {
                showToast('Please enter your name', 'danger');
                return;
            }
            
            if (!validateEmail(email)) {
                showToast('Please enter a valid email address', 'danger');
                return;
            }
            
            if (password.trim() === '') {
                showToast('Please enter a password', 'danger');
                return;
            }
            
            if (password.length < 6) {
                showToast('Password must be at least 6 characters long', 'danger');
                return;
            }
            
            if (password !== confirmPassword) {
                showToast('Passwords do not match', 'danger');
                return;
            }
            
            // Submit form
            this.submit();
        });
    }
    
    // Handle profile update form submission
    const profileForm = document.getElementById('profile-form');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const name = document.getElementById('name').value;
            const phone = document.getElementById('phone').value;
            
            // Validate form
            if (name.trim() === '') {
                showToast('Please enter your name', 'danger');
                return;
            }
            
            if (phone.trim() !== '' && !validatePhone(phone)) {
                showToast('Please enter a valid phone number', 'danger');
                return;
            }
            
            // Submit form
            this.submit();
        });
    }
    
    // Handle password change form submission
    const passwordForm = document.getElementById('password-form');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const currentPassword = document.getElementById('current-password').value;
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            // Validate form
            if (currentPassword.trim() === '') {
                showToast('Please enter your current password', 'danger');
                return;
            }
            
            if (newPassword.trim() === '') {
                showToast('Please enter a new password', 'danger');
                return;
            }
            
            if (newPassword.length < 6) {
                showToast('New password must be at least 6 characters long', 'danger');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                showToast('New passwords do not match', 'danger');
                return;
            }
            
            // Submit form
            this.submit();
        });
    }
    
    // Facebook login button
    const fbLoginBtn = document.getElementById('facebook-login');
    if (fbLoginBtn) {
        fbLoginBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (typeof FB !== 'undefined') {
                FB.login(function(response) {
                    if (response.authResponse) {
                        // Get user info from Facebook
                        FB.api('/me', {fields: 'name,email'}, function(userInfo) {
                            // Send to backend for authentication/registration
                            socialLogin('facebook', userInfo.id, userInfo.name, userInfo.email);
                        });
                    } else {
                        showToast('Facebook login cancelled or failed', 'warning');
                    }
                }, {scope: 'email'});
            } else {
                showToast('Facebook SDK not loaded', 'danger');
            }
        });
    }
}

// Setup Google Sign-In
function setupGoogleSignIn(apiKeys) {
    const googleLoginBtn = document.getElementById('google-login');
    if (googleLoginBtn) {
        // Load Google Identity Services JavaScript library
        const googleScript = document.createElement('script');
        googleScript.src = 'https://accounts.google.com/gsi/client';
        googleScript.async = true;
        googleScript.defer = true;
        document.head.appendChild(googleScript);
        
        // Initialize Google Sign-In when script is loaded
        googleScript.onload = function() {
            if (typeof google !== 'undefined') {
                // Initialize Google Sign-In
                google.accounts.id.initialize({
                    client_id: apiKeys.google_client_id,
                    callback: handleGoogleSignIn,
                    auto_select: false
                });
                
                // Custom button triggers Google Sign-In prompt
                googleLoginBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (typeof google !== 'undefined' && google.accounts) {
                        google.accounts.id.prompt();
                    } else {
                        showToast('Google Sign-In not loaded yet. Please try again in a moment.', 'warning');
                    }
                });
                
                // Also render the standard Google button (hidden, but available as fallback)
                const googleContainer = document.getElementById('google-login-container');
                if (googleContainer) {
                    google.accounts.id.renderButton(
                        googleContainer,
                        { theme: 'outline', size: 'large', width: '100%' }
                    );
                }
            }
        };
    }
}

// Initialize social login providers
function initializeSocialLogins(apiKeys) {
    // Load Facebook SDK asynchronously
    (function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "https://connect.facebook.net/en_US/sdk.js";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
    
    // Facebook SDK initialization
    window.fbAsyncInit = function() {
        FB.init({
            appId      : apiKeys.facebook_app_id,
            cookie     : true,
            xfbml      : true,
            version    : 'v17.0'
        });
    };
    
    // Handle form events and social login buttons
    setupFormHandlers();
    
    // Setup Google Sign-In
    setupGoogleSignIn(apiKeys);
}

// When document is ready
document.addEventListener('DOMContentLoaded', function() {
    // First get API keys from server
    fetch('api/get_api_keys.php')
    .then(response => response.json())
    .then(apiKeys => {
        // Store API keys globally
        window.socialApiKeys = apiKeys;
        
        // Initialize social login providers with API keys
        initializeSocialLogins(apiKeys);
    })
    .catch(error => {
        console.error('Error fetching API keys:', error);
    });
});
