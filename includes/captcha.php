<?php
/**
 * ARS Junction CAPTCHA Verification Helper
 * Generates and validates self-contained visual or mathematical CAPTCHAs.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generates CAPTCHA data (either a base64 encoded PNG image or a math question)
 * @return array Array containing 'type' ('image'|'math') and 'html' (image source / question text)
 */
function generate_captcha_data() {
    // Generate random 5-character security code
    // Exclude confusing characters: 0, O, 1, I, l
    $allowed_chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    $char_len = strlen($allowed_chars);
    for ($i = 0; $i < 5; $i++) {
        $code .= $allowed_chars[rand(0, $char_len - 1)];
    }
    
    $_SESSION['captcha_code'] = $code;

    // Try GD image generation if extension is loaded
    if (extension_loaded('gd')) {
        try {
            $width = 150;
            $height = 45;
            
            $image = @imagecreatetruecolor($width, $height);
            if ($image) {
                // Background color (very light grey/white)
                $bg_color = imagecolorallocate($image, 245, 247, 250);
                imagefill($image, 0, 0, $bg_color);
                
                // Add random lines for noise
                for ($i = 0; $i < 5; $i++) {
                    $line_color = imagecolorallocate($image, rand(160, 220), rand(160, 220), rand(160, 220));
                    imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $line_color);
                }
                
                // Add random dots for noise
                for ($i = 0; $i < 80; $i++) {
                    $pixel_color = imagecolorallocate($image, rand(130, 210), rand(130, 210), rand(130, 210));
                    imagesetpixel($image, rand(0, $width), rand(0, $height), $pixel_color);
                }
                
                // Draw the characters
                $char_width = 22;
                $start_x = 20;
                for ($i = 0; $i < strlen($code); $i++) {
                    // Dark color for text
                    $char_color = imagecolorallocate($image, rand(15, 95), rand(15, 95), rand(15, 95));
                    // Built-in GD font size 5 (9x15)
                    imagechar($image, 5, $start_x + ($i * $char_width), rand(10, 20), $code[$i], $char_color);
                }
                
                // Capture output
                ob_start();
                imagepng($image);
                $image_data = ob_get_clean();
                
                // Free memory
                if (PHP_VERSION_ID < 80000) {
                    imagedestroy($image);
                }
                
                return [
                    'type' => 'image',
                    'html' => 'data:image/png;base64,' . base64_encode($image_data)
                ];
            }
        } catch (Throwable $e) {
            // Fall through to math captcha fallback
        }
    }

    // Fallback: Math challenge
    $num1 = rand(2, 9);
    $num2 = rand(2, 9);
    $ops = ['+', '*'];
    $op = $ops[rand(0, 1)];
    
    $question = "{$num1} {$op} {$num2} = ?";
    $answer = ($op === '+') ? ($num1 + $num2) : ($num1 * $num2);
    
    $_SESSION['captcha_code'] = (string)$answer;
    
    return [
        'type' => 'math',
        'html' => $question
    ];
}

/**
 * Validates the user submitted CAPTCHA value
 * @param string $input User submitted CAPTCHA value
 * @return bool True if verification matches, false otherwise
 */
function verify_captcha_code($input) {
    if (!isset($_SESSION['captcha_code'])) {
        return false;
    }
    
    $actual = trim($_SESSION['captcha_code']);
    $user_input = trim($input);
    
    // Case insensitive comparison
    $is_valid = (strcasecmp($actual, $user_input) === 0);
    
    // Self-destruct CAPTCHA code so it cannot be re-used
    unset($_SESSION['captcha_code']);
    
    return $is_valid;
}
