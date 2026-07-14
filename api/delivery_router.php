<?php
// Set working directory to project root so all relative includes work correctly
chdir(dirname(__DIR__));

$path = $_GET['__route_path__'] ?? 'index.php';
$path = ltrim($path, '/');

// Default to index.php if empty
if ($path === '') {
    $path = 'index.php';
}

// Clean query string parameters if any
$path = explode('?', $path)[0];

// Basic security check to prevent directory traversal
if (strpos($path, '..') !== false) {
    http_response_code(403);
    die('Forbidden');
}

$file = 'delivery/' . $path;

// If the requested file exists, require it
if (file_exists($file)) {
    require $file;
    exit;
}

// Check if it exists with .php extension
if (file_exists($file . '.php')) {
    require $file . '.php';
    exit;
}

// Fallback to delivery/index.php
if (file_exists('delivery/index.php')) {
    require 'delivery/index.php';
    exit;
}

http_response_code(404);
echo "404 Delivery Page Not Found";
