<?php
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

$user_id = $_SESSION['user_id'];
$restaurant_id = isset($_POST['restaurant_id']) ? intval($_POST['restaurant_id']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$comment = isset($_POST['comment']) ? clean_input($_POST['comment']) : '';

if ($restaurant_id <= 0 || $rating < 1 || $rating > 5 || empty($comment)) {
    header('Location: ../restaurant_details.php?id=' . $restaurant_id . '#reviews');
    exit;
}

if (!has_user_reviewed($user_id, $restaurant_id)) {
    $stmt = $conn->prepare("INSERT INTO reviews (user_id, restaurant_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $restaurant_id, $rating, $comment]);
}

header('Location: ../restaurant_details.php?id=' . $restaurant_id . '#reviews');
exit;
?>
