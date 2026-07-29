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
/**
 * Generates an SVG image Data URI for CAPTCHA (requires no PHP extensions)
 */
function generate_svg_captcha($code) {
    $width = 150;
    $height = 45;
    $colors = ['#1e293b', '#e64a19', '#0284c7', '#16a34a', '#9333ea', '#c026d3', '#d97706'];
    
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'" style="display:block;">';
    $svg .= '<rect width="100%" height="100%" fill="#f8fafc" rx="8"/>';
    
    // Add random noise lines
    for ($i = 0; $i < 6; $i++) {
        $x1 = rand(0, $width); $y1 = rand(0, $height);
        $x2 = rand(0, $width); $y2 = rand(0, $height);
        $stroke = $colors[rand(0, count($colors) - 1)];
        $op = sprintf('%.2f', rand(25, 55) / 100);
        $svg .= '<line x1="'.$x1.'" y1="'.$y1.'" x2="'.$x2.'" y2="'.$y2.'" stroke="'.$stroke.'" stroke-width="1.5" opacity="'.$op.'"/>';
    }
    
    // Add random noise dots
    for ($i = 0; $i < 30; $i++) {
        $cx = rand(0, $width); $cy = rand(0, $height); $r = rand(1, 3);
        $fill = $colors[rand(0, count($colors) - 1)];
        $op = sprintf('%.2f', rand(20, 45) / 100);
        $svg .= '<circle cx="'.$cx.'" cy="'.$cy.'" r="'.$r.'" fill="'.$fill.'" opacity="'.$op.'"/>';
    }
    
    // Draw letters with random rotation and positioning
    $char_width = 24;
    $start_x = 18;
    for ($i = 0; $i < strlen($code); $i++) {
        $x = $start_x + ($i * $char_width);
        $y = rand(28, 33);
        $rot = rand(-18, 18);
        $color = $colors[rand(0, count($colors) - 1)];
        $svg .= '<text x="'.$x.'" y="'.$y.'" fill="'.$color.'" font-size="22" font-weight="800" font-family="Arial, sans-serif" transform="rotate('.$rot.', '.$x.', '.$y.')">'.$code[$i].'</text>';
    }
    
    $svg .= '</svg>';
    // Return raw SVG string for inline embedding (no base64 data URI needed)
    return $svg;
}

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

    // Use inline SVG CAPTCHA - works on all PHP servers including Vercel (no data URI needed)
    return [
        'type' => 'svg',
        'html' => generate_svg_captcha($code)
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
