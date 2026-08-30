<?php
chdir(__DIR__);
/**
 * API endpoint to get API keys for social login
 * This is a safer approach than hardcoding the API keys in JS files
 */

// Include necessary files
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Define the API keys
$api_keys = [
    'google_client_id' => get_site_setting('google_client_id') ?: getenv('GOOGLE_CLIENT_ID') ?: '',
];

// Return the keys
echo json_encode($api_keys);
?>
