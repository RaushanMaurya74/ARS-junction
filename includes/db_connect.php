<?php
// MySQL database connection for ARS JUNCTION.
// Override these values with environment variables on your server if needed.
// DATABASE CONFIGURATION (Change these when deploying to ProFreeHost)
// Auto-detect environment (Localhost vs Ezyro Hosting)
$is_local = false;
$server_name = $_SERVER['SERVER_NAME'] ?? '';
$http_host = $_SERVER['HTTP_HOST'] ?? '';
$server_addr = $_SERVER['SERVER_ADDR'] ?? '';

// Check if running on localhost, local IP range, or CLI context
if (
    empty($server_name) || 
    $server_name === 'localhost' || 
    $server_name === '127.0.0.1' || 
    $server_name === '[::1]' ||
    strpos($http_host, 'localhost') !== false ||
    strpos($http_host, '127.0.0.1') !== false ||
    (filter_var($server_name, FILTER_VALIDATE_IP) && filter_var($server_name, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) === false) ||
    (filter_var($server_addr, FILTER_VALIDATE_IP) && filter_var($server_addr, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) === false)
) {
    $is_local = true;
}

if ($is_local) {
    // Local Database (XAMPP)
    $host = '127.0.0.1';
    $port = '3306';
    $dbname = 'ars_junction';
    $username = 'root';
    $password = '';
} else {
    // Ezyro Production Database
    $host = 'sql109.ezyro.com';
    $port = '3306';
    $dbname = 'ezyro_38647338_ars_junction';
    $username = 'ezyro_38647338';
    $password = 'Maurya1055@';
}

$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

try {
    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // If it is an API request, do not die; let the API return a clean JSON error response
    if (strpos($_SERVER['REQUEST_URI'] ?? '', 'api/') !== false || strpos($_SERVER['PHP_SELF'] ?? '', 'api/') !== false) {
        $conn = null;
    } else {
        die("Database connection failed: " . $e->getMessage());
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
