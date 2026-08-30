<?php
header('Content-Type: application/json');

$drivers = PDO::getAvailableDrivers();
$host = trim(getenv('SUPABASE_DB_HOST') ?: 'aws-0-ap-northeast-1.pooler.supabase.com');
$port = trim(getenv('SUPABASE_DB_PORT') ?: '5432');
$dbname = trim(getenv('SUPABASE_DB_NAME') ?: 'postgres');
$pass = 'Maurya1055@';

$users_to_test = [
    'ars_app.pdmabiiomlfdwqxatxyv',
    'postgres.pdmabiiomlfdwqxatxyv',
    'ars_app',
    'postgres'
];

$results = [];

foreach ($users_to_test as $u) {
    try {
        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};options='--client_encoding=UTF8'";
        $pdo = new PDO($dsn, $u, $pass, [
            PDO::ATTR_TIMEOUT => 4,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $stmt = $pdo->query("SELECT COUNT(*) FROM restaurants");
        $count = $stmt->fetchColumn();
        $results[$u] = [
            'status' => 'connected_success',
            'restaurants_count' => $count
        ];
    } catch (Exception $e) {
        $results[$u] = [
            'status' => 'failed',
            'error' => $e->getMessage()
        ];
    }
}

echo json_encode([
    'php_version' => PHP_VERSION,
    'host' => $host,
    'port' => $port,
    'results' => $results
], JSON_PRETTY_PRINT);
