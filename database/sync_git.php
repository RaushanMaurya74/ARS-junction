<?php
// Set working directory to project root
chdir(dirname(__DIR__));

echo "Cleaning up local files to prepare for git pull...\n";
if (file_exists('restaurant/register.php')) {
    unlink('restaurant/register.php');
    echo "Removed local register.php to avoid pull collision\n";
}

$output = [];
$return_var = 0;

exec("git reset --hard HEAD 2>&1", $output, $return_var);
echo "git reset status: " . $return_var . "\n";
echo implode("\n", $output) . "\n\n";

$output = [];
exec("git pull origin main 2>&1", $output, $return_var);
echo "git pull status: " . $return_var . "\n";
echo implode("\n", $output) . "\n\n";

$output = [];
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
