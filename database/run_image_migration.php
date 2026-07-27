<?php
/**
 * ARS Junction Schema Migration - Image Columns Upgrade
 * Non-destructively increases size of profile_image and image columns to support Base64 strings.
 */

require_once __DIR__ . '/../includes/db_connect.php';

if (!isset($conn)) {
    die("Database connection not established. Check your environment configuration.\n");
}

echo "<h3>Starting database schema migration for image columns...</h3>";
flush();

try {
    $driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "<p>Database driver detected: <strong>{$driver}</strong></p>";

    if ($driver === 'mysql') {
        // MySQL uses MEDIUMTEXT (holds up to 16MB)
        $queries = [
            "ALTER TABLE `users` MODIFY COLUMN `profile_image` MEDIUMTEXT NULL DEFAULT NULL",
            "ALTER TABLE `categories` MODIFY COLUMN `image` MEDIUMTEXT NULL DEFAULT NULL",
            "ALTER TABLE `restaurants` MODIFY COLUMN `image` MEDIUMTEXT NULL DEFAULT NULL",
            "ALTER TABLE `menu_items` MODIFY COLUMN `image` MEDIUMTEXT NULL DEFAULT NULL"
        ];
    } elseif ($driver === 'pgsql') {
        // PostgreSQL uses TEXT (variable length, up to 1GB)
        $queries = [
            "ALTER TABLE users ALTER COLUMN profile_image TYPE TEXT",
            "ALTER TABLE categories ALTER COLUMN image TYPE TEXT",
            "ALTER TABLE restaurants ALTER COLUMN image TYPE TEXT",
            "ALTER TABLE menu_items ALTER COLUMN image TYPE TEXT"
        ];
    } else {
        throw new Exception("Unsupported database driver: {$driver}");
    }

    foreach ($queries as $sql) {
        echo "<p>Executing: <code>" . htmlspecialchars($sql) . "</code> ... ";
        $conn->exec($sql);
        echo "<span style='color: green; font-weight: bold;'>OK</span></p>";
        flush();
    }

    echo "<h3>Migration completed successfully!</h3>";

} catch (Exception $e) {
    echo "<h3><span style='color: red;'>Migration failed:</span> " . htmlspecialchars($e->getMessage()) . "</h3>";
}
?>
