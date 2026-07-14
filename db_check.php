<?php
// Database Diagnostic Utility for ARS JUNCTION
require_once 'includes/db_connect.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Diagnostic - ARS JUNCTION</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light py-5'>
    <div class='container'>
        <div class='card shadow-sm max-width-600 mx-auto'>
            <div class='card-header bg-dark text-white text-center py-3'>
                <h4 class='mb-0'>Database Connection Diagnostic</h4>
            </div>
            <div class='card-body p-4'>";

try {
    echo "<div class='alert alert-success mb-3'>
            <h5 class='alert-heading'><i class='fas fa-check-circle me-2'></i> Database Connection: SUCCESS</h5>
            <p class='mb-0'>Connected successfully to host: <strong>" . htmlspecialchars($host) . "</strong></p>
          </div>";

    // List tables
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        echo "<div class='alert alert-danger'>
                <h5 class='alert-heading'>✖ Tables Missing!</h5>
                <p>No tables were found in the database. This means the SQL import failed or was not run.</p>
                <hr>
                <p class='mb-0'><strong>Action:</strong> Open phpMyAdmin on ProFreeHost, select <code>ezyro_38647338_ars_junction</code>, go to the <strong>Import</strong> tab, and import the <code>database/ars_junction.sql</code> file.</p>
              </div>";
    } else {
        echo "<h5 class='mb-3'>Database Tables Summary:</h5>";
        echo "<ul class='list-group mb-3'>";
        foreach ($tables as $table) {
            $countStmt = $conn->query("SELECT COUNT(*) FROM `$table`");
            $rows = $countStmt->fetchColumn();
            echo "<li class='list-group-item d-flex justify-content-between align-items-center'>
                    $table
                    <span class='badge bg-primary rounded-pill'>$rows rows</span>
                  </li>";
        }
        echo "</ul>";
        echo "<div class='alert alert-success py-2 mb-0'>✔ All tables found! Your database schema is populated correctly.</div>";
    }
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>
            <h5 class='alert-heading'>✖ Connection Failed!</h5>
            <p class='mb-1'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <hr>
            <p class='mb-0'>Please check that you have entered the correct username and password in <code>includes/db_connect.php</code>.</p>
          </div>";
}

echo "        </div>
            <div class='card-footer text-center text-muted py-2'>
                ARS JUNCTION Diagnostic tool
            </div>
        </div>
    </div>
</body>
</html>";
?>
