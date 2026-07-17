<?php
$src = "C:/Users/raush/.gemini/antigravity-ide/brain/585769d2-3907-4c2f-b2e2-88f503922020/media__1784273449437.jpg";
$dest = __DIR__ . "/images/app_mockup.jpg";
if (copy($src, $dest)) {
    echo "SUCCESS";
} else {
    echo "FAILED";
}
unlink(__FILE__); // self-destruct for security
?>
