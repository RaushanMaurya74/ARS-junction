<?php
header('Content-Type: text/plain');
echo "Current directory: " . getcwd() . "\n";
echo "Dirname(__DIR__): " . dirname(__DIR__) . "\n";

$files = glob(dirname(__DIR__) . '/restaurant/*');
echo "Files in restaurant/:\n";
foreach ($files as $file) {
    echo "- " . basename($file) . " (" . (is_file($file) ? "FILE" : "DIR") . ")\n";
}
?>
