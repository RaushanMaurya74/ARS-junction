<?php
chdir(__DIR__);
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$validated = Validator::validate($_GET, [
    'pincode' => ['type' => 'pincode', 'required' => true]
]);

$pincode = $validated['pincode'];

try {
    $stmt = $conn->prepare("SELECT * FROM delivery_pincodes WHERE pincode = ? AND is_active = 1");
    $stmt->execute([$pincode]);
    $location = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($location) {
        echo json_encode([
            'deliverable' => true,
            'pincode' => $location['pincode'],
            'area_name' => $location['area_name'],
            'delivery_charge' => floatval($location['delivery_charge']),
            'message' => 'We deliver to this location!'
        ]);
    } else {
        echo json_encode([
            'deliverable' => false,
            'message' => 'Sorry, we do not deliver to this area yet.'
        ]);
    }
} catch (PDOException $e) {
    error_log("Pincode verification database error: " . $e->getMessage());
    echo json_encode([
        'deliverable' => false,
        'message' => 'An internal database error occurred. Please try again later.'
    ]);
}
?>

