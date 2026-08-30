<?php
header('Content-Type: application/json');

$drivers = PDO::getAvailableDrivers();
$extensions = get_loaded_extensions();

$host = trim(getenv('SUPABASE_DB_HOST') ?: '');
$port = trim(getenv('SUPABASE_DB_PORT') ?: '5432');
$dbname = trim(getenv('SUPABASE_DB_NAME') ?: 'postgres');
$user = trim(getenv('SUPABASE_DB_USER') ?: '');
$pass = trim(getenv('SUPABASE_DB_PASSWORD') ?: '');

$pgsql_status = 'untested';
$pgsql_error = null;

if (in_array('pgsql', $drivers)) {
    try {
        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};options='--client_encoding=UTF8'";
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_TIMEOUT => 4]);
        $pgsql_status = 'connected';
    } catch (Exception $e) {
        $pgsql_status = 'failed';
        $pgsql_error = $e->getMessage();
    }
} else {
    $pgsql_status = 'driver_missing';
}

echo json_encode([
    'php_version' => PHP_VERSION,
    'pdo_drivers' => $drivers,
    'has_pgsql_ext' => extension_loaded('pgsql'),
    'has_pdo_pgsql_ext' => extension_loaded('pdo_pgsql'),
    'has_pdo_mysql_ext' => extension_loaded('pdo_mysql'),
    'supabase_host' => $host,
    'supabase_user' => $user,
    'pgsql_connection' => $pgsql_status,
    'pgsql_error' => $pgsql_error
], JSON_PRETTY_PRINT);
