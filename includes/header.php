<?php
require_once 'db_connect.php';
require_once 'functions.php';
require_once 'auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ARS JUNCTION' : 'ARS JUNCTION - Online Food Ordering Platform'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
    <?php if (isset($extra_css)): echo $extra_css; endif; ?>
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
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
            <ul class="navbar-nav ms-auto">
                <?php if (is_logged_in()): ?>
                    <li class="nav-item">
                        <a class="nav-link position-relative <?php echo (basename($_SERVER['PHP_SELF']) == 'cart.php') ? 'active' : ''; ?>" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                            <span class="cart-count position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cart-count">
                                <?php
                                $cart_count = 0;
                                if (isset($_SESSION['user_id'])) {
                                    $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
                                    $stmt->bind_param("i", $_SESSION['user_id']);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    if ($result->num_rows > 0) {
                                        $row = $result->fetch_assoc();
                                        $cart_count = $row['count'] ? $row['count'] : 0;
                                    }
                                }
                                echo $cart_count;
                                ?>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo $_SESSION['user_name']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                            <li><a class="dropdown-item" href="order_tracking.php">My Orders</a></li>
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
