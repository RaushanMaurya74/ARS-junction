<?php
// Set working directory to project root
chdir(dirname(__DIR__));

echo "Running git commands...\n";
$output = [];
$return_var = 0;

// Execute git add, commit, and push
exec("git add images/ars_logo.png 2>&1", $output, $return_var);
echo "git add status: " . $return_var . "\n";
echo implode("\n", $output) . "\n\n";

$output = [];
exec("git commit -m \"Upload custom ARS logo\" 2>&1", $output, $return_var);
echo "git commit status: " . $return_var . "\n";
echo implode("\n", $output) . "\n\n";

$output = [];
exec("git push origin main 2>&1", $output, $return_var);
echo "git push status: " . $return_var . "\n";
echo implode("\n", $output) . "\n\n";
?>
