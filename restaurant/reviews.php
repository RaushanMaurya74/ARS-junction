<?php
/**
 * Restaurant Owner Portal - Customer Reviews
 */

$page_title = "Customer Reviews";
require_once 'includes/restaurant_header.php';

global $conn;

// 1. Fetch rating stats
$stmt_stats = $conn->prepare("
    SELECT 
        COUNT(*) as total_count,
        COALESCE(AVG(rating), 0) as avg_rating,
        COUNT(CASE WHEN rating = 5 THEN 1 END) as count_5,
        COUNT(CASE WHEN rating = 4 THEN 1 END) as count_4,
        COUNT(CASE WHEN rating = 3 THEN 1 END) as count_3,
        COUNT(CASE WHEN rating = 2 THEN 1 END) as count_2,
        COUNT(CASE WHEN rating = 1 THEN 1 END) as count_1
    FROM reviews 
    WHERE restaurant_id = ?
");
$stmt_stats->execute([$restaurant_id]);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// 2. Fetch all reviews list
$stmt_list = $conn->prepare("
    SELECT r.*, u.name as customer_name, u.profile_image
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.restaurant_id = ?
    ORDER BY r.created_at DESC
");
$stmt_list->execute([$restaurant_id]);
$reviews = $stmt_list->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid animated-fade-in">
    <!-- Title Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Customer Reviews</h1>
    </div>

    <!-- Rating Summary Dashboard -->
    <div class="row mb-4">
        <!-- Overview Score Card -->
        <div class="col-lg-4 mb-3">
            <div class="card shadow-sm h-100 text-center p-4">
                <div class="card-body d-flex flex-column justify-content-center">
                    <h5 class="text-muted fw-bold mb-2">Average Score</h5>
                    <div class="display-3 fw-bold text-warning mb-2">
                        <?php echo number_format($stats['avg_rating'], 1); ?>
                    </div>
                    <div class="mb-2 text-warning" style="font-size: 1.25rem;">
                        <?php 
                        $full_stars = floor($stats['avg_rating']);
                        $half_star = ($stats['avg_rating'] - $full_stars) >= 0.5 ? 1 : 0;
                        $empty_stars = 5 - $full_stars - $half_star;
                        
                        for ($i = 0; $i < $full_stars; $i++) echo '<i class="fas fa-star"></i>';
                        if ($half_star) echo '<i class="fas fa-star-half-alt"></i>';
                        for ($i = 0; $i < $empty_stars; $i++) echo '<i class="far fa-star"></i>';
                        ?>
                    </div>
                    <p class="text-muted small mb-0">Based on <?php echo $stats['total_count']; ?> customer reviews</p>
                </div>
            </div>
        </div>

        <!-- Rating Breakdown Card -->
        <div class="col-lg-8 mb-3">
            <div class="card shadow-sm h-100 p-4">
                <div class="card-body">
                    <h6 class="fw-bold text-secondary mb-3">Rating Breakdown</h6>
                    
                    <?php 
                    $ratings_breakdown = [
                        5 => $stats['count_5'],
                        4 => $stats['count_4'],
                        3 => $stats['count_3'],
                        2 => $stats['count_2'],
                        1 => $stats['count_1']
                    ];
                    
                    foreach ($ratings_breakdown as $stars_count => $num):
                        $percent = $stats['total_count'] > 0 ? ($num / $stats['total_count']) * 100 : 0;
                    ?>
                        <div class="row align-items-center mb-2">
                            <div class="col-auto text-muted small" style="width: 60px;">
                                <?php echo $stars_count; ?> stars
                            </div>
                            <div class="col">
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $percent; ?>%;" aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            <div class="col-auto text-muted small" style="width: 40px; text-align: right;">
                                <?php echo $num; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Reviews Listing -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light py-3">
            <h6 class="m-0 fw-bold text-secondary">Customer Feedback List (<?php echo count($reviews); ?> reviews)</h6>
        </div>
        <div class="card-body p-4">
            <?php if (empty($reviews)): ?>
                <div class="text-center py-5">
                    <i class="far fa-star fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No reviews or ratings received yet.</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($reviews as $rev): ?>
                        <div class="col-12 mb-3 border-bottom pb-3">
                            <div class="d-flex align-items-start">
                                <!-- Profile avatar -->
                                <?php if (has_image($rev['profile_image'], true)): ?>
                                    <img src="<?php echo get_image_url($rev['profile_image'], true); ?>" class="rounded-circle border me-3" style="width: 45px; height: 45px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-circle bg-light border text-secondary d-flex align-items-center justify-content-center fw-bold me-3" style="width: 45px; height: 45px;">
                                        <?php echo strtoupper(substr($rev['customer_name'] ?? 'C', 0, 1)); ?>
                                    </div>
                                <?php endif; ?>

                                <div class="w-100">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($rev['customer_name'] ?? 'Anonymous Customer'); ?></h6>
                                        <small class="text-muted"><i class="fas fa-calendar-alt me-1"></i><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></small>
                                    </div>
                                    
                                    <!-- Stars Display -->
                                    <div class="text-warning mb-2 small">
                                        <?php 
                                        for ($i = 0; $i < 5; $i++) {
                                            if ($i < $rev['rating']) {
                                                echo '<i class="fas fa-star"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        ?>
                                    </div>

                                    <!-- Comment Text -->
                                    <p class="text-muted mb-0 small" style="line-height: 1.5;">
                                        "<?php echo htmlspecialchars($rev['comment'] ?: 'No written comment provided.'); ?>"
                                    </p>
                                    
                                    <?php if (!empty($rev['order_id'])): ?>
                                        <div class="mt-2">
                                            <span class="badge bg-light text-muted border small">Order #<?php echo $rev['order_id']; ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
