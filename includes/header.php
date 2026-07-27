<?php
require_once 'db_connect.php';
require_once 'functions.php';
require_once 'auth.php';

// Enforce appropriate rate limits: looser limits for logged-in users, moderate for public visitors
if (is_logged_in()) {
    $rlRes = RateLimiter::checkAuthenticated($_SESSION['user_id'] ?? null);
} else {
    $rlRes = RateLimiter::checkPublic();
}
RateLimiter::enforceOrBlock($rlRes);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ARS JUNCTION' : 'ARS JUNCTION - Online Food Ordering Platform'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/style.css?v=3.0" rel="stylesheet">
    <!-- Dark mode flash prevention -->
    <script>(function(){var t=localStorage.getItem('ars_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
    <?php if (isset($extra_css)): echo $extra_css; endif; ?>
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="images/logo.png" alt="ARS JUNCTION Logo" height="40" class="me-2">
            <span class="text-warning fw-bold">ARS</span> JUNCTION
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'restaurants.php') ? 'active' : ''; ?>" href="restaurants.php">Restaurants</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'menu.php') ? 'active' : ''; ?>" href="menu.php">Menu</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'active' : ''; ?>" href="about.php">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'contact.php') ? 'active' : ''; ?>" href="contact.php">Contact</a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto align-items-center">
                <?php if (is_logged_in()): ?>
                <!-- Notification Bell -->
                <li class="nav-item notif-bell-wrap">
                    <button class="notif-bell-btn" id="notif-bell-btn" title="Notifications" aria-label="Notifications">
                        <i class="fas fa-bell"></i>
                        <span id="notif-count">0</span>
                    </button>
                    <div class="notif-dropdown" id="notif-dropdown">
                        <div class="notif-dropdown-header">
                            <span><i class="fas fa-bell me-1"></i> Notifications</span>
                            <button class="notif-mark-read" id="notif-mark-read">Mark all read</button>
                        </div>
                        <div class="notif-dropdown-body" id="notif-body">
                            <div class="notif-empty"><i class="fas fa-bell-slash" style="font-size:2rem;color:#eee;display:block;margin-bottom:10px;"></i>No notifications yet</div>
                        </div>
                    </div>
                </li>
                <?php endif; ?>
                <!-- Cart -->
                <li class="nav-item">
                    <a class="nav-link position-relative <?php echo (basename($_SERVER['PHP_SELF']) == 'cart.php') ? 'active' : ''; ?>" href="cart.php">
                        <i class="fas fa-shopping-cart"></i> Cart
                        <span class="cart-count position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cart-count">
                            <?php
                            $cart_count = 0;
                            if (isset($_SESSION['user_id'])) {
                                $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
                                $stmt->execute([$_SESSION['user_id']]);
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                $cart_count = $row && isset($row['count']) ? $row['count'] : 0;
                            } else if (isset($_SESSION['cart'])) {
                                foreach($_SESSION['cart'] as $qty) {
                                    $cart_count += intval($qty);
                                }
                            }
                            echo $cart_count;
                            ?>
                        </span>
                    </a>
                </li>
                <!-- Dark Mode Toggle -->
                <li class="nav-item d-flex align-items-center">
                    <button class="dark-mode-toggle" id="dark-mode-toggle" title="Toggle dark mode" aria-label="Toggle dark mode">
                        <i class="fas fa-moon"></i>
                    </button>
                </li>
                <?php if (is_logged_in()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo $_SESSION['user_name']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="order_tracking.php"><i class="fas fa-box me-2"></i>My Orders</a></li>
                            <li><a class="dropdown-item" href="wishlist.php"><i class="fas fa-heart me-2 text-danger"></i>My Wishlist</a></li>
                            <?php if (is_admin()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="admin/dashboard.php">Admin Dashboard</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'login.php') ? 'active' : ''; ?>" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'register.php') ? 'active' : ''; ?>" href="register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content Container -->
<div class="container py-4">
