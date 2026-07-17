<?php
/**
 * Migration runner endpoint for Vercel deployment.
 */
header('Content-Type: text/plain');
require_once __DIR__ . '/../database/run_promo_migration.php';
?>
