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

// If the requested path is a PHP file that exists in the root, require it
if (file_exists($path)) {
    require $path;
    exit;
}

// Check if it exists with .php extension
if (file_exists($path . '.php')) {
    require $path . '.php';
    exit;
}

// Fallback to index.php if the path matches nothing
if (file_exists('index.php')) {
    require 'index.php';
    exit;
}

http_response_code(404);
echo "404 Not Found";
