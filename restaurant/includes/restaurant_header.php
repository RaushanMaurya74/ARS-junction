<?php
/**
 * Restaurant Owner Portal - Header Template
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Force authentication
require_restaurant_login();

$manager_id = $_SESSION['restaurant_owner_id'];
$restaurant_id = $_SESSION['restaurant_id'];

$manager = get_user_by_id($manager_id);
$restaurant = get_restaurant_by_id($restaurant_id);

if (!$manager || !$restaurant) {
    header("Location: logout.php");
    exit;
}

if (isset($restaurant['is_active']) && $restaurant['is_active'] == 0) {
    header("Location: logout.php?reason=pending");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Partner Portal'; ?> - ARS JUNCTION</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom Stylesheet -->
    <link href="../css/restaurant.css" rel="stylesheet">
    <?php if (isset($extra_css)): echo $extra_css; endif; ?>
</head>
<body>

<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar" class="sidebar">
        <div class="sidebar-header">
            <h3><span class="text-warning fw-bold">ARS</span> Partner</h3>
            <div class="small text-white-50 mt-1"><i class="fas fa-store me-1"></i><?php echo htmlspecialchars($restaurant['name']); ?></div>
        </div>

        <ul class="list-unstyled components">
            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                <a href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard / Orders
                </a>
            </li>
            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'menu_items.php') ? 'active' : ''; ?>">
                <a href="menu_items.php">
                    <i class="fas fa-utensils"></i> Menu Items
                </a>
            </li>
            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'delivery_boys.php') ? 'active' : ''; ?>">
                <a href="delivery_boys.php">
                    <i class="fas fa-truck"></i> Delivery Boys
                </a>
            </li>
            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reviews.php') ? 'active' : ''; ?>">
                <a href="reviews.php">
                    <i class="fas fa-star"></i> Reviews
                </a>
            </li>
            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>">
                <a href="profile.php">
                    <i class="fas fa-store-alt"></i> Profile & Settings
                </a>
            </li>
            <li class="mt-4 pt-4 border-top border-secondary">
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt text-danger"></i> <span class="text-danger">Logout</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column w-100">
        <!-- Topbar -->
        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 shadow-sm px-4">
            <!-- Mobile Toggle -->
            <button id="sidebarToggle" class="btn btn-link d-md-none me-3">
                <i class="fa fa-bars text-dark"></i>
            </button>
            
            <div class="navbar-brand d-none d-sm-block fw-bold text-secondary">
                Partner Dashboard
            </div>

            <!-- Topbar User Info -->
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center text-decoration-none text-dark" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="me-2 d-none d-lg-inline text-gray-600 small">Manager: <strong><?php echo htmlspecialchars($manager['name']); ?></strong></span>
                        <?php if (has_image($manager['profile_image'], true)): ?>
                            <img class="rounded-circle border" src="<?php echo get_image_url($manager['profile_image'], true); ?>" style="width: 38px; height: 38px; object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center fw-bold border" style="width: 38px; height: 38px;">
                                <?php echo strtoupper(substr($manager['name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </a>
                    
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-cog fa-sm fa-fw me-2 text-gray-400"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt fa-sm fa-fw me-2"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </nav>
        
        <!-- Main Content -->
        <div id="content" class="px-4 pb-4">
