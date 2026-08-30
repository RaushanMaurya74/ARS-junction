<?php
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/includes/db_connect.php';

global $conn;

if ($conn) {
    try {
        $stmt = $conn->query("SELECT COUNT(*) FROM restaurants");
        $count = $stmt->fetchColumn();
        echo json_encode([
            'status' => 'healthy',
            'database' => 'connected',
            'driver' => $conn->getAttribute(PDO::ATTR_DRIVER_NAME),
            'restaurants_count' => (int)$count
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'database' => 'query_failed',
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'database' => 'connection_failed'
    ]);
}

