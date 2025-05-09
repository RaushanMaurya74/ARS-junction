<?php
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
    'facebook_app_id' => getenv('FACEBOOK_APP_ID') ?: '000000000000000', // Will be replaced with real key
    'google_client_id' => getenv('GOOGLE_CLIENT_ID') ?: '000000000000-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx.apps.googleusercontent.com', // Will be replaced with real key
];

// Return the keys
echo json_encode($api_keys);
?>