<?php
/**
 * ARS Junction CAPTCHA AJAX Refresh Endpoint
 * Returns a new random CAPTCHA in JSON format.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Load database connection to wire up the CookieSessionHandler correctly
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/captcha.php';

$captcha_data = generate_captcha_data();
echo json_encode($captcha_data);
exit;
