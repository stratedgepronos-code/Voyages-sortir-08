<?php
/**
 * VS08 Mobile Responsive Fixes
 * mu-plugin : toujours chargé, pas besoin de functions.php
 * Injecte le CSS mobile directement dans le <head>
 */
if (!defined('ABSPATH')) exit;

add_action('wp_head', function() {
    $css_url = content_url('/themes/vs08-theme/assets/css/mobile-fixes.css');
    echo '<link rel="stylesheet" id="vs08-mobile-fixes-css" href="' . esc_url($css_url) . '?v=' . date('YmdH') . '" media="all">' . "\n";
    // Favicon
    $logo = content_url('/themes/vs08-theme/assets/img/logo.png');
    echo '<link rel="icon" href="' . esc_url($logo) . '" type="image/png">' . "\n";
}, 999);
