<?php
/**
 * ARS Junction - Rate Limit Tables Migration Script
 * Provisions rate_limits and auth_attempts tables without modifying or dropping any existing tables.
 */

require_once __DIR__ . '/../includes/db_connect.php';

if (!isset($conn) || !$conn) {
    die("Database connection not established. Check your environment configuration.\n");
}

echo "Starting Rate Limit Migration...\n";

try {
    $driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "Database driver: {$driver}\n";

    $sql = file_get_contents(__DIR__ . '/rate_limit_schema.sql');
    $conn->exec($sql);

    echo "Rate limiting tables ('rate_limits', 'auth_attempts') successfully provisioned!\n";
} catch (Exception $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
