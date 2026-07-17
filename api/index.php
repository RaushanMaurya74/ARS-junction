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

// Basic security check to prevent directory traversal or accessing database scripts
if (strpos($path, '..') !== false || strpos($path, 'database/') === 0) {
    http_response_code(403);
    die('Forbidden');
}

// Redirect directories to trailing slash version to ensure correct asset resolution
if (is_dir($path)) {
    header("Location: /" . $path . "/");
    exit;
}

// Only allow executing files ending with .php
$isPhp = (substr($path, -4) === '.php');

// If the requested path is a PHP file that exists in the root, require it
if ($isPhp && is_file($path)) {
    require $path;
    exit;
}

// Check if it exists with .php extension
if (is_file($path . '.php')) {
    require $path . '.php';
    exit;
}

// Fallback to index.php if the path matches nothing
if (is_file('index.php')) {
    require 'index.php';
    exit;
}

http_response_code(404);
echo "404 Not Found";
