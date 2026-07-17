<?php
/**
 * Database Profile Image Compressor
 * Scans the users table for large Base64 images and compresses them using GD.
 */

require_once __DIR__ . '/../includes/db_connect.php';

if (!isset($conn)) {
    die("Database connection not established.\n");
}

echo "Scanning users table for large profile images...\n";

try {
    // Select all users who have a non-null profile_image
    $stmt = $conn->query("SELECT user_id, name, email, profile_image FROM users WHERE profile_image IS NOT NULL AND profile_image != ''");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updated_count = 0;

    foreach ($users as $user) {
        $img = $user['profile_image'];
        $len = strlen($img);

        // If the image is not base64 or is small (< 100KB), skip it
        if (strpos($img, 'data:image/') !== 0 || $len < 102400) {
            echo "Skipping User #{$user['user_id']} ({$user['name']}) - size: " . round($len / 1024, 2) . " KB\n";
            continue;
        }

        echo "Compressing User #{$user['user_id']} ({$user['name']}) - current size: " . round($len / 1024, 2) . " KB\n";

        // Parse base64
        if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $img, $matches)) {
            $ext = strtolower($matches[1]);
            $data = base64_decode($matches[2]);

            if ($data === false) {
                echo "Failed to decode base64 for User #{$user['user_id']}\n";
                continue;
            }

            // Create GD image from string
            $src_img = @imagecreatefromstring($data);
            if (!$src_img) {
                echo "Failed to create GD image from string for User #{$user['user_id']}\n";
                continue;
            }

            $width = imagesx($src_img);
            $height = imagesy($src_img);

            // Target dimensions max 200x200
            $max_width = 200;
            $max_height = 200;

            $ratio = $width / $height;
            if ($width > $max_width || $height > $max_height) {
                if ($max_width / $max_height > $ratio) {
                    $new_width = $max_height * $ratio;
                    $new_height = $max_height;
                } else {
                    $new_height = $max_width / $ratio;
                    $new_width = $max_width;
                }
            } else {
                $new_width = $width;
                $new_height = $height;
            }

            $dst_img = imagecreatetruecolor($new_width, $new_height);

            // Handle transparency for PNG
            if ($ext === 'png') {
                imagealphablending($dst_img, false);
                imagesavealpha($dst_img, true);
            }

            imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

            // Output compressed image to memory buffer
            ob_start();
            if ($ext === 'png') {
                imagepng($dst_img, null, 7); // Compression level 7
            } else {
                imagejpeg($dst_img, null, 75); // Quality 75%
            }
            $compressed_data = ob_get_clean();

            // Destroy GD images (imagedestroy is deprecated in PHP 8.5+ and has no effect since PHP 8.0)
            if (PHP_VERSION_ID < 80000) {
                imagedestroy($src_img);
                imagedestroy($dst_img);
            }

            $compressed_base64 = 'data:image/' . ($ext === 'png' ? 'png' : 'jpeg') . ';base64,' . base64_encode($compressed_data);
            $new_len = strlen($compressed_base64);

            echo "-> Compressed size: " . round($new_len / 1024, 2) . " KB (Saved " . round((1 - $new_len / $len) * 100, 2) . "%)\n";

            // Update database
            $update_stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE user_id = ?");
            $update_stmt->execute([$compressed_base64, $user['user_id']]);
            $updated_count++;
        } else {
            echo "Invalid Base64 pattern for User #{$user['user_id']}\n";
        }
    }

    echo "Cleanup complete! Compressed {$updated_count} user profile images.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
