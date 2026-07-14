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
    'facebook_app_id' => get_site_setting('facebook_app_id', '1737107847470255'),
    'google_client_id' => get_site_setting('google_client_id', '213451617569-9s3a4nvk661jhnotfskuf6js4bo9sgpg.apps.googleusercontent.com'),
];

// Return the keys
echo json_encode($api_keys);
?>