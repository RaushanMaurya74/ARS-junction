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
    echo "<div class='alert alert-info py-2'>Connected successfully to active database: <strong>" . htmlspecialchars($dbname) . "</strong></div>";

    // 1. Create table delivery_pincodes if it doesn't exist
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

    $conn->exec($createTableSQL);
    echo "<div class='text-success mb-2'>✔ Table <code>delivery_pincodes</code> checked/created successfully.</div>";

    // 2. Check if table has data. If empty, seed test pincodes
    $stmt = $conn->query("SELECT COUNT(*) FROM `delivery_pincodes`");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $seedSQL = "INSERT INTO `delivery_pincodes` (`pincode`, `area_name`, `delivery_charge`, `is_active`) VALUES
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

} catch (PDOException $e) {
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
