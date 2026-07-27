<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/admin_auth.php';

// Check if admin is logged in
require_admin();

// Get current admin info
$admin_id = $_SESSION['admin_id'];
$admin = get_admin_by_id($admin_id);

// If admin doesn't exist, log out
if (!$admin) {
    admin_logout();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Admin Panel'; ?> - ARS JUNCTION</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../css/admin.css" rel="stylesheet">
    <?php if (isset($extra_css)): echo $extra_css; endif; ?>
</head>
<body id="page-top">

<!-- Page Wrapper -->
<div class="wrapper d-flex">
    <!-- Sidebar -->
    <nav id="sidebar" class="sidebar">
        <div class="sidebar-header">
            <h3><span class="text-warning fw-bold">ARS</span> JUNCTION</h3>
        </div>

        <ul class="list-unstyled components">
            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                <a href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
            </li>
            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'orders.php') ? 'active' : ''; ?>">
                <a href="orders.php">
                    <i class="fas fa-shopping-cart me-2"></i> Orders
                </a>
            </li>
            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'restaurants.php') ? 'active' : ''; ?>">
                <a href="restaurants.php">
                    <i class="fas fa-store me-2"></i> Restaurants
                </a>
            </li>
            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'menu_items.php') ? 'active' : ''; ?>">
                <a href="menu_items.php">
                    <i class="fas fa-utensils me-2"></i> Menu Items
                </a>
            </li>
            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>">
                <a href="users.php">
                    <i class="fas fa-users me-2"></i> Users
                </a>
            </li>
            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'delivery_boys.php') ? 'active' : ''; ?>">
                <a href="delivery_boys.php">
                    <i class="fas fa-truck me-2"></i> Delivery Boys
                </a>
            </li>
            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'delivery_locations.php') ? 'active' : ''; ?>">
                <a href="delivery_locations.php">
                    <i class="fas fa-map-marker-alt me-2"></i> Delivery Locations
                </a>
            </li>
            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'promo_codes.php') ? 'active' : ''; ?>">
                <a href="promo_codes.php">
                    <i class="fas fa-ticket-alt me-2"></i> Promo Codes
                </a>
            </li>
            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reviews.php') ? 'active' : ''; ?>">
                <a href="reviews.php">
                    <i class="fas fa-star me-2"></i> Reviews
                </a>
            </li>
            <li>
                <a href="../" target="_blank">
                    <i class="fas fa-globe me-2"></i> View Website
                </a>
            </li>
        </ul>
    </nav>

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column w-100">
        <!-- Topbar -->
        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 shadow">
            <!-- Sidebar Toggle (Topbar) -->
            <button id="sidebarToggleTop" class="btn btn-link d-md-none">
                <i class="fa fa-bars"></i>
            </button>

            <!-- Topbar Search -->
            <form action="" method="get" class="d-none d-sm-inline-block form-inline me-auto ms-md-3 my-2 my-md-0 mw-100 navbar-search">
                <div class="input-group">
                    <input type="text" name="search" class="form-control bg-light border-0 small" placeholder="Search current list..." aria-label="Search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search fa-sm"></i>
                    </button>
                </div>
            </form>

            <!-- Topbar Navbar -->
            <ul class="navbar-nav ms-auto">
                <!-- Nav Item - Alerts -->
                <li class="nav-item dropdown no-arrow mx-1">
                    <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-bell fa-fw"></i>
                        <!-- Counter - Alerts -->
                        <span class="badge badge-danger badge-counter">3+</span>
                    </a>
                    <!-- Dropdown - Alerts -->
                    <div class="dropdown-list dropdown-menu dropdown-menu-end shadow animated--grow-in" aria-labelledby="alertsDropdown">
                        <h6 class="dropdown-header">
                            Alerts Center
                        </h6>
                        <a class="dropdown-item d-flex align-items-center" href="#">
                            <div class="me-3">
                                <div class="icon-circle bg-primary">
                                    <i class="fas fa-shopping-cart text-white"></i>
                                </div>
                            </div>
                            <div>
                                <div class="small text-gray-500">April 12, 2023</div>
                                <span class="font-weight-bold">New order #1234 has been placed</span>
                            </div>
                        </a>
                        <a class="dropdown-item d-flex align-items-center" href="#">
                            <div class="me-3">
                                <div class="icon-circle bg-success">
                                    <i class="fas fa-user text-white"></i>
                                </div>
                            </div>
                            <div>
                                <div class="small text-gray-500">April 11, 2023</div>
                                New user registered
                            </div>
                        </a>
                        <a class="dropdown-item d-flex align-items-center" href="#">
                            <div class="me-3">
                                <div class="icon-circle bg-warning">
                                    <i class="fas fa-exclamation-triangle text-white"></i>
                                </div>
                            </div>
                            <div>
                                <div class="small text-gray-500">April 10, 2023</div>
                                Restaurant #5 reported an issue with delivery
                            </div>
                        </a>
                        <a class="dropdown-item text-center small text-gray-500" href="#">Show All Alerts</a>
                    </div>
                </li>

                <div class="topbar-divider d-none d-sm-block"></div>

                <!-- Nav Item - User Information -->
                <li class="nav-item dropdown no-arrow">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="me-2 d-none d-lg-inline text-gray-600 small"><?php echo htmlspecialchars($admin['name']); ?></span>
                        <?php if (has_image($admin['profile_image'], true)): ?>
                            <img class="img-profile rounded-circle" src="<?php echo get_image_url($admin['profile_image'], true); ?>" style="width: 32px; height: 32px; object-fit: cover; border: 1px solid #e3e6f0;">
                        <?php else: ?>
                            <img class="img-profile rounded-circle" src="../images/default_avatar.png" style="width: 32px; height: 32px; object-fit: cover; border: 1px solid #e3e6f0;">
                        <?php endif; ?>
                    </a>
                    <!-- Dropdown - User Information -->
                    <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in" aria-labelledby="userDropdown">
                        <a class="dropdown-item" href="profile.php">
                            <i class="fas fa-user fa-sm fa-fw me-2 text-gray-400"></i>
                            Profile
                        </a>
                        <a class="dropdown-item" href="settings.php">
                            <i class="fas fa-cogs fa-sm fa-fw me-2 text-gray-400"></i>
                            Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                            <i class="fas fa-sign-out-alt fa-sm fa-fw me-2 text-gray-400"></i>
                            Logout
                        </a>
                    </div>
                </li>
            </ul>
        </nav>
        <!-- End of Topbar -->

        <!-- Main Content -->
        <div id="content">
