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

// Validate phone number format (detect real Indian mobile numbers starting with 6-9)
function validatePhone(phone) {
    const re = /^[6-9]\d{9}$/;
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

// Social login (Google) handler
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
            const phone = document.getElementById('phone').value;
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
            
            if (phone.trim() === '') {
                showToast('Please enter your phone number', 'danger');
                return;
            }
            
            if (!validatePhone(phone)) {
                showToast('Please enter a valid 10-digit Indian mobile number (starts with 6, 7, 8, or 9)', 'danger');
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
}

// Setup Google Sign-In redirect flow
function triggerGoogleRedirect() {
    const apiKeys = window.socialApiKeys || {};
    const clientId = apiKeys.google_client_id || '213451617569-9s3a4nvk661jhnotfskuf6js4bo9sgpg.apps.googleusercontent.com';
    const redirectUri = window.location.href.split('#')[0].split('?')[0]; // Clean URL without hash or parameters
    
    const oauthUrl = 'https://accounts.google.com/o/oauth2/v2/auth' +
        '?client_id=' + encodeURIComponent(clientId) +
        '&redirect_uri=' + encodeURIComponent(redirectUri) +
        '&response_type=token' +
        '&scope=' + encodeURIComponent('openid email profile') +
        '&state=' + encodeURIComponent(window.location.pathname);
        
    window.location.href = oauthUrl;
}

// Check if redirect has returned with a token in the hash
function checkGoogleHashCallback() {
    if (window.location.hash) {
        const hash = window.location.hash.substring(1);
        const params = new URLSearchParams(hash);
        const accessToken = params.get('access_token');
        
        if (accessToken) {
            // Clear hash from URL immediately for clean UX
            window.history.replaceState(null, null, window.location.pathname);
            
            showToast('Authenticating with Google...', 'info');
            
            fetch('https://www.googleapis.com/oauth2/v3/userinfo', {
                headers: {
                    'Authorization': `Bearer ${accessToken}`
                }
            })
            .then(res => res.json())
            .then(userInfo => {
                if (userInfo && userInfo.sub) {
                    socialLogin('google', userInfo.sub, userInfo.name, userInfo.email);
                } else {
                    showToast('Failed to retrieve Google profile info', 'danger');
                }
            })
            .catch(err => {
                console.error('Google token exchange error:', err);
                showToast('Google authentication failed', 'danger');
            });
        }
    }
}

// Setup Google Sign-In button listeners (with Popup Mode)
function setupGoogleSignIn(apiKeys) {
    const googleContainer = document.getElementById('google-login-container');
    const googleLoginBtn = document.getElementById('google-login');
    
    // Bind click handler for custom button immediately as a fallback redirect
    if (googleLoginBtn) {
        googleLoginBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // If GSI script failed to load or initialize, use redirect flow
            if (typeof google === 'undefined' || !google.accounts) {
                triggerGoogleRedirect();
            } else {
                google.accounts.id.prompt();
            }
        });
    }

    if (googleContainer) {
        // Load Google Identity Services JavaScript library
        const googleScript = document.createElement('script');
        googleScript.src = 'https://accounts.google.com/gsi/client';
        googleScript.async = true;
        googleScript.defer = true;
        document.head.appendChild(googleScript);
        
        googleScript.onload = function() {
            if (typeof google !== 'undefined') {
                // Initialize Google Sign-In with popup
                google.accounts.id.initialize({
                    client_id: apiKeys.google_client_id,
                    callback: handleGoogleSignIn,
                    ux_mode: 'popup',
                    auto_select: false
                });
                
                // Render the official Google Sign-In button
                google.accounts.id.renderButton(
                    googleContainer,
                    { 
                        theme: 'outline', 
                        size: 'large', 
                        width: '100%', 
                        text: 'continue_with',
                        shape: 'rectangular',
                        logo_alignment: 'left'
                    }
                );
                
                // Hide the custom button since the official one is now rendered
                if (googleLoginBtn) {
                    googleLoginBtn.style.setProperty('display', 'none', 'important');
                }
            }
        };
    }
}

// Initialize social login providers
function initializeSocialLogins(apiKeys) {
    // Handle form events
    setupFormHandlers();
    
    // Setup Google Sign-In
    setupGoogleSignIn(apiKeys);
}

// When document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if returning from Google OAuth redirect
    checkGoogleHashCallback();

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
