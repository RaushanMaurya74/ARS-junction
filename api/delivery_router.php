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

// Redirect directories to trailing slash version to ensure correct asset resolution
if (is_dir($file)) {
    header("Location: /" . $file . "/");
    exit;
}

// Only allow executing files ending with .php
$isPhp = (substr($file, -4) === '.php');

// If the requested file exists, require it
if ($isPhp && is_file($file)) {
    chdir(dirname(__DIR__) . '/delivery');
    require basename($file);
    exit;
}

// Check if it exists with .php extension
if (is_file($file . '.php')) {
    chdir(dirname(__DIR__) . '/delivery');
    require basename($file) . '.php';
    exit;
}

// Fallback to delivery/index.php
if (is_file('delivery/index.php')) {
    chdir(dirname(__DIR__) . '/delivery');
    require 'index.php';
    exit;
}

http_response_code(404);
echo "404 Delivery Page Not Found";
