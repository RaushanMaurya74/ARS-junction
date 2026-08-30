<?php
/**
 * ARS Junction - Consolidated API Router
 * Dispatches all /api/* requests to modular handlers in api_handlers/
 * Complies with Vercel Hobby plan Serverless Function limits (< 12 functions)
 */

// Basic CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$rootDir = dirname(__DIR__);
$handlersDir = $rootDir . '/api_handlers';

$rawPath = $_GET['__route_path__'] ?? '';
$path = ltrim($rawPath, '/');
$path = explode('?', $path)[0];

// Security: Prevent directory traversal
if (strpos($path, '..') !== false || strpos($path, '/') !== false || strpos($path, '\\') !== false) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$targetFile = '';

// Check exact match with .php
if (substr($path, -4) === '.php' && is_file($handlersDir . '/' . $path)) {
    $targetFile = $handlersDir . '/' . $path;
} elseif (is_file($handlersDir . '/' . $path . '.php')) {
    $targetFile = $handlersDir . '/' . $path . '.php';
}

if ($targetFile !== '') {
    chdir($handlersDir);
    require $targetFile;
    exit;
}

http_response_code(404);
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'message' => 'API endpoint not found: ' . htmlspecialchars($path)
]);
