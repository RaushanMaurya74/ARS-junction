<?php
/**
 * ARS Junction Schema Migration Script
 * Runs incremental database changes for Restaurant Owner and Restaurant ID fields.
 */

require_once __DIR__ . '/../includes/db_connect.php';

if (!isset($conn)) {
    die("Database connection not established. Check your environment configuration.\n");
}

echo "Starting database migration...\n";

try {
    $driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "Database driver detected: {$driver}\n";

    if ($driver === 'mysql') {
        // 1. Check and add is_restaurant_owner column
        $checkCol = $conn->query("SHOW COLUMNS FROM `users` LIKE 'is_restaurant_owner'");
        if ($checkCol->rowCount() === 0) {
            $conn->exec("ALTER TABLE `users` ADD COLUMN `is_restaurant_owner` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_delivery_boy`");
            echo "Added 'is_restaurant_owner' column to users table.\n";
        } else {
            echo "'is_restaurant_owner' column already exists.\n";
        }

        // 2. Check and add restaurant_id column
        $checkCol2 = $conn->query("SHOW COLUMNS FROM `users` LIKE 'restaurant_id'");
        if ($checkCol2->rowCount() === 0) {
            $conn->exec("ALTER TABLE `users` ADD COLUMN `restaurant_id` INT(11) NULL DEFAULT NULL AFTER `is_restaurant_owner`");
            echo "Added 'restaurant_id' column to users table.\n";
        } else {
            echo "'restaurant_id' column already exists.\n";
        }

        // 3. Add foreign key index and constraint
        try {
            // Check if constraint exists already by trying to add it, or checking keys
            // Simple way: check if restaurant_id has a key/constraint
            $conn->exec("ALTER TABLE `users` ADD CONSTRAINT `fk_users_restaurant` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`restaurant_id`) ON DELETE SET NULL");
            echo "Added foreign key constraint fk_users_restaurant.\n";
        } catch (PDOException $ex) {
            // Ignore if key/constraint already exists
            echo "Constraint fk_users_restaurant might already exist. Details: " . $ex->getMessage() . "\n";
        }

    } elseif ($driver === 'pgsql') {
        // PostgreSQL (Supabase) migration
        // 1. Add is_restaurant_owner column if not exists
        $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_restaurant_owner SMALLINT DEFAULT 0");
        echo "Added 'is_restaurant_owner' column (Postgres) if it did not exist.\n";

        // 2. Add restaurant_id column if not exists
        $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS restaurant_id INT DEFAULT NULL");
        echo "Added 'restaurant_id' column (Postgres) if it did not exist.\n";

        // 3. Add foreign key constraint
        try {
            $conn->exec("ALTER TABLE users ADD CONSTRAINT fk_users_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants (restaurant_id) ON DELETE SET NULL");
            echo "Added foreign key constraint fk_users_restaurant (Postgres).\n";
        } catch (PDOException $ex) {
            echo "Constraint fk_users_restaurant might already exist in Postgres. Details: " . $ex->getMessage() . "\n";
        }
    } else {
        echo "Unsupported database driver: {$driver}\n";
    }

    echo "Migration completed successfully!\n";

} catch (Exception $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
?>
