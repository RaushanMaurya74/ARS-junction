<?php
// Production/Local Database Migration Script for ARS Junction
require_once 'includes/db_connect.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Migration - ARS Junction</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light py-5'>
    <div class='container'>
        <div class='card shadow-sm max-width-600 mx-auto' style='max-width: 600px;'>
            <div class='card-header bg-primary text-white text-center py-3'>
                <h4 class='mb-0'>Database Setup & Migration</h4>
            </div>
            <div class='card-body p-4'>";

try {
    $driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "<div class='alert alert-info py-2'>Connected successfully to active database: <strong>" . htmlspecialchars($dbname) . "</strong> (" . htmlspecialchars($driver) . ")</div>";

    // 1. Check if database is completely empty (no 'users' table)
    $tableExists = false;
    try {
        if ($driver === 'pgsql') {
            $stmt = $conn->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'users')");
            $tableExists = $stmt->fetchColumn();
        } else {
            $stmt = $conn->query("SHOW TABLES LIKE 'users'");
            $tableExists = $stmt->rowCount() > 0;
        }
    } catch (Exception $e) {
        $tableExists = false;
    }

    if (!$tableExists) {
        echo "<div class='alert alert-warning py-2'>Empty database detected. Running full schema initialization...</div>";
        
        $sqlFile = ($driver === 'pgsql') ? 'database/ars_junction_supabase.sql' : 'database/ars_junction.sql';
        if (file_exists($sqlFile)) {
            $sqlContent = file_get_contents($sqlFile);
            
            // Execute the schema
            $conn->exec($sqlContent);
            echo "<div class='text-success mb-2'>✔ Schema initialization from <code>" . htmlspecialchars($sqlFile) . "</code> completed successfully.</div>";
        } else {
            throw new Exception("Schema file <code>" . htmlspecialchars($sqlFile) . "</code> not found! Please make sure it is uploaded.");
        }
    } else {
        echo "<div class='text-muted mb-2'>ℹ Existing tables found. Skipping full schema initialization.</div>";
    }

    // 2. Patch missing columns (is_online in users, confirmed_at in orders)
    echo "<h5 class='mt-4 mb-3'>Checking Incremental Schema Patches:</h5>";

    // A. is_online in users
    if ($driver === 'pgsql') {
        $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_online smallint DEFAULT 0");
        echo "<div class='text-success mb-2'>✔ PostgreSQL check: <code>users.is_online</code> column verified/patched.</div>";
    } else {
        $columns = $conn->query("SHOW COLUMNS FROM users LIKE 'is_online'")->fetchAll();
        if (empty($columns)) {
            $conn->exec("ALTER TABLE users ADD COLUMN is_online tinyint(1) DEFAULT 0 AFTER is_delivery_boy");
            echo "<div class='text-success mb-2'>✔ MySQL check: <code>users.is_online</code> column added successfully.</div>";
        } else {
            echo "<div class='text-muted mb-2'>✔ MySQL check: <code>users.is_online</code> already exists.</div>";
        }
    }

    // B. confirmed_at in orders
    if ($driver === 'pgsql') {
        $conn->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS confirmed_at timestamp DEFAULT NULL");
        echo "<div class='text-success mb-2'>✔ PostgreSQL check: <code>orders.confirmed_at</code> column verified/patched.</div>";
    } else {
        $columns = $conn->query("SHOW COLUMNS FROM orders LIKE 'confirmed_at'")->fetchAll();
        if (empty($columns)) {
            $conn->exec("ALTER TABLE orders ADD COLUMN confirmed_at datetime DEFAULT NULL AFTER order_date");
            echo "<div class='text-success mb-2'>✔ MySQL check: <code>orders.confirmed_at</code> column added successfully.</div>";
        } else {
            echo "<div class='text-muted mb-2'>✔ MySQL check: <code>orders.confirmed_at</code> already exists.</div>";
        }
    }

    // 3. Create table delivery_pincodes if it doesn't exist (legacy migration step)
    if ($driver === 'pgsql') {
        $createTableSQL = "CREATE TABLE IF NOT EXISTS delivery_pincodes (
            pincode_id SERIAL PRIMARY KEY,
            pincode varchar(10) NOT NULL UNIQUE,
            area_name varchar(100) NOT NULL,
            delivery_charge decimal(10,2) NOT NULL DEFAULT '0.00',
            is_active smallint NOT NULL DEFAULT '1',
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
        );";
    } else {
        $createTableSQL = "CREATE TABLE IF NOT EXISTS `delivery_pincodes` (
            `pincode_id` int(11) NOT NULL AUTO_INCREMENT,
            `pincode` varchar(10) NOT NULL UNIQUE,
            `area_name` varchar(100) NOT NULL,
            `delivery_charge` decimal(10,2) NOT NULL DEFAULT '0.00',
            `is_active` tinyint(1) NOT NULL DEFAULT '1',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`pincode_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    }

    $conn->exec($createTableSQL);
    echo "<div class='text-success mb-2'>✔ Table <code>delivery_pincodes</code> checked/created successfully.</div>";

    // 4. Seed test pincodes if empty
    $stmt = $conn->query("SELECT COUNT(*) FROM delivery_pincodes");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $seedSQL = "INSERT INTO delivery_pincodes (pincode, area_name, delivery_charge, is_active) VALUES
            ('802207', 'Piro Main Area', 20.00, 1),
            ('802201', 'Ara City Junction', 40.00, 1),
            ('802202', 'Charpokhari Bazaar', 30.00, 1),
            ('802206', 'Tarari Block', 30.00, 1),
            ('802208', 'Bihsi Village Area', 25.00, 1),
            ('110001', 'Connaught Place (Delhi Dev Mock)', 50.00, 1),
            ('400001', 'Fort Mumbai (Mock Delivery)', 60.00, 1)";
        
        $conn->exec($seedSQL);
        echo "<div class='text-success mb-2'>✔ Seed delivery locations added successfully.</div>";
    } else {
        echo "<div class='text-muted mb-2'>ℹ Table already has {$count} delivery locations registered. Seeding skipped.</div>";
    }

    echo "<div class='alert alert-success mt-4 mb-0'>
            <h5><i class='fas fa-check-circle me-1'></i> Migration Completed!</h5>
            <p class='mb-0 small'>Your database is fully updated. Please delete this <code>db_migrate.php</code> file from your server directory now for security purposes.</p>
          </div>";

} catch (Exception $e) {
    echo "<div class='alert alert-danger mt-3'>
            <h5 class='alert-heading'>✖ Migration Failed!</h5>
            <p class='mb-0 small'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
          </div>";
}

echo "        </div>
            <div class='card-footer text-center text-muted py-2 small'>
                ARS Junction Database Utility
            </div>
        </div>
    </div>
</body>
</html>";
?>
