<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

ob_start();
try {
    require_once __DIR__ . '/../includes/captcha.php';
    echo "GD library loaded: " . (extension_loaded('gd') ? 'yes' : 'no') . "\n";
    
    $data = generate_captcha_data();
    echo "CAPTCHA type generated: " . $data['type'] . "\n";
    echo "CAPTCHA data length: " . strlen($data['html']) . "\n";
    echo "CAPTCHA data preview: " . substr($data['html'], 0, 50) . "...\n";
} catch (Throwable $e) {
    echo "ERROR CAUGHT: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
$output = ob_get_clean();
file_put_contents(__DIR__ . '/captcha_debug_log.txt', $output);
echo "Debug finished. Log written to captcha_debug_log.txt.";
?>
