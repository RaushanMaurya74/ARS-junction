<?php
chdir(dirname(__DIR__));
$output = [];
exec("git ls-files images/ars_logo.png 2>&1", $output);
echo "git ls-files result:\n" . implode("\n", $output) . "\n\n";

$output = [];
exec("git ls-files --others --exclude-standard 2>&1", $output);
echo "All untracked files:\n" . implode("\n", $output) . "\n\n";
?>
