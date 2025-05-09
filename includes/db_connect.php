<?php
// Database connection configuration using environment variables
$host = getenv('PGHOST');
$port = getenv('PGPORT');
$dbname = getenv('PGDATABASE');
$username = getenv('PGUSER');
$password = getenv('PGPASSWORD');

// Create database connection string
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;user=$username;password=$password";

try {
    // Create a PDO instance
    $conn = new PDO($dsn);
    
    // Set error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
