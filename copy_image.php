<?php
/**
 * Helper to copy the uploaded mockup image to the correct destination.
 */
$src = "C:/Users/raush/.gemini/antigravity-ide/brain/585769d2-3907-4c2f-b2e2-88f503922020/media__1784273449437.jpg";
$dest = __DIR__ . "/images/app_mockup.jpg";

if (!file_exists($src)) {
    // Try to search in common brain directories if conversation ID is different
    $brain_dir = "C:/Users/raush/.gemini/antigravity-ide/brain/";
    if (is_dir($brain_dir)) {
        $files = scandir($brain_dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $possible_src = $brain_dir . $file . "/media__1784273449437.jpg";
                if (file_exists($possible_src)) {
                    $src = $possible_src;
                    break;
                }
            }
        }
    }
}

if (file_exists($src)) {
    if (copy($src, $dest)) {
        echo "<h3>Success! Premium App Mockup image has been copied to <pre>images/app_mockup.jpg</pre></h3>";
    } else {
        echo "<h3>Failed to copy image. Permission error or file write failed.</h3>";
    }
} else {
    echo "<h3>Error: Source image not found. Please ensure the file exists at:<br><pre>$src</pre></h3>";
}
?>
