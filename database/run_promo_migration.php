<?php
/**
 * ARS Junction Promo Code Schema Migration Script
 * Runs incremental database changes for Promo Codes system.
 */

require_once __DIR__ . '/../includes/db_connect.php';

if (!isset($conn)) {
    die("Database connection not established. Check your environment configuration.\n");
}

echo "Starting database migration for Promo Codes...\n";

try {
    $driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "Database driver detected: {$driver}\n";

    if ($driver === 'mysql') {
        // 1. Create promo_codes table
        $conn->exec("CREATE TABLE IF NOT EXISTS `promo_codes` (
            `promo_id` INT AUTO_INCREMENT PRIMARY KEY,
            `code` VARCHAR(50) NOT NULL UNIQUE,
            `discount_type` VARCHAR(20) NOT NULL DEFAULT 'percentage',
            `discount_value` DECIMAL(10,2) NOT NULL,
            `min_order_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `max_discount_amount` DECIMAL(10,2) DEFAULT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        echo "Created 'promo_codes' table (MySQL).\n";

        // 2. Add columns to orders table
        $checkCol = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'promo_code'");
        if ($checkCol->rowCount() === 0) {
            $conn->exec("ALTER TABLE `orders` ADD COLUMN `promo_code` VARCHAR(50) NULL DEFAULT NULL AFTER `tax`");
            echo "Added 'promo_code' column to orders table.\n";
        } else {
            echo "'promo_code' column already exists in orders table.\n";
        }

        $checkCol2 = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'discount_amount'");
        if ($checkCol2->rowCount() === 0) {
            $conn->exec("ALTER TABLE `orders` ADD COLUMN `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `promo_code`");
            echo "Added 'discount_amount' column to orders table.\n";
        } else {
            echo "'discount_amount' column already exists in orders table.\n";
        }

    } elseif ($driver === 'pgsql') {
        // PostgreSQL (Supabase) migration
        // 1. Create promo_codes table
        $conn->exec("CREATE TABLE IF NOT EXISTS promo_codes (
            promo_id SERIAL PRIMARY KEY,
            code VARCHAR(50) NOT NULL UNIQUE,
            discount_type VARCHAR(20) NOT NULL DEFAULT 'percentage' CHECK (discount_type IN ('percentage', 'fixed')),
            discount_value DECIMAL(10,2) NOT NULL,
            min_order_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            max_discount_amount DECIMAL(10,2) DEFAULT NULL,
            is_active SMALLINT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );");
        echo "Created 'promo_codes' table (PostgreSQL).\n";

        // 2. Add columns to orders table
        $conn->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS promo_code VARCHAR(50) DEFAULT NULL");
        echo "Added 'promo_code' column to orders table if not exists (PostgreSQL).\n";

        $conn->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) DEFAULT 0.00");
        echo "Added 'discount_amount' column to orders table if not exists (PostgreSQL).\n";
    } else {
        echo "Unsupported database driver: {$driver}\n";
    }

    echo "Migration completed successfully!\n";

} catch (Exception $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
?>
