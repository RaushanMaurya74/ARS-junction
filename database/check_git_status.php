<?php
chdir(dirname(__DIR__));
$output = [];
exec("git status --ignored 2>&1", $output);
echo implode("\n", $output) . "\n\n";

$output = [];
exec("git check-ignore -v images/ars_logo.png 2>&1", $output);
echo "check-ignore result:\n" . implode("\n", $output) . "\n\n";
?>
