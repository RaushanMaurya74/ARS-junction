<?php
$page_title = "Manage Reviews";
require_once 'admin_header.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
    $result = admin_delete_review((int)$_POST['review_id']);
    $success_msg = $result['success'] ? 'Review deleted successfully.' : '';
    if (!$result['success']) $error_msg = $result['message'];
}

$reviews = admin_get_all_reviews(500, 0);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Customer Reviews</h1>
    </div>

    <?php if ($success_msg): ?><div class="alert alert-success"><?php echo $success_msg; ?></div><?php endif; ?>
    <?php if ($error_msg): ?><div class="alert alert-danger"><?php echo $error_msg; ?></div><?php endif; ?>

    <div class="card shadow">
        <div class="card-header bg-light"><h6 class="mb-0">All Ratings and Reviews</h6></div>
        <div class="card-body table-responsive">
            <?php if (empty($reviews)): ?>
                <p class="text-center py-3">No reviews found.</p>
            <?php else: ?>
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Customer</th><th>Restaurant</th><th>Rating</th><th>Review</th><th>Date</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($reviews as $review): ?>
                        <tr>
                            <td><?php echo $review['user_name']; ?></td>
                            <td><?php echo $review['restaurant_name']; ?></td>
                            <td>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star text-warning"></i>
                                <?php endfor; ?>
                            </td>
                            <td><?php echo $review['comment']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($review['created_at'])); ?></td>
                            <td>
                                <form method="post" onsubmit="return confirm('Delete this review?');">
                                    <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                    <button class="btn btn-sm btn-danger" name="delete_review" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>
