<?php
chdir(__DIR__);
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = $_SERVER['HTTP_REFERER'] ?? '../restaurants.php';
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../restaurants.php');
    exit;
}

try {
    $validated = Validator::validate($_POST, [
        'restaurant_id' => ['type' => 'int', 'required' => true, 'min' => 1],
        'rating' => ['type' => 'int', 'required' => true, 'min' => 1, 'max' => 5],
        'comment' => ['type' => 'string', 'required' => true, 'max_len' => 1000]
    ], false);
    
    $restaurant_id = $validated['restaurant_id'];
    $rating = $validated['rating'];
    $comment = $validated['comment'];
} catch (ValidationException $e) {
    $restaurant_id = isset($_POST['restaurant_id']) ? intval($_POST['restaurant_id']) : 0;
    header('Location: ../restaurant_details.php?id=' . $restaurant_id . '#reviews');
    exit;
}

$user_id = $_SESSION['user_id'];

if (!has_user_reviewed($user_id, $restaurant_id)) {
    $stmt = $conn->prepare("INSERT INTO reviews (user_id, restaurant_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $restaurant_id, $rating, $comment]);
    header('Location: ../restaurant_details.php?id=' . $restaurant_id . '&review=success#reviews');
} else {
    header('Location: ../restaurant_details.php?id=' . $restaurant_id . '&review=duplicate#reviews');
}
exit;
?>

