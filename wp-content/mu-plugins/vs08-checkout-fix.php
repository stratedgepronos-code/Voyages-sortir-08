<?php
/**
 * VS08 Checkout Fix — mu-plugin
 * 1. Augmente memory_limit pendant AJAX/REST
 * 2. Laisse la désactivation plugins lourds au mu-plugin dédié (sans toucher $wp_filter)
 * 3. Log les erreurs fatales pour débugger les 500
 */

// Détecter AJAX WC ou REST
$is_ajax = defined('DOING_AJAX') && DOING_AJAX;
$is_wc_ajax = isset($_GET['wc-ajax']) || (isset($_REQUEST['wc-ajax']));
$is_rest = (strpos($_SERVER['REQUEST_URI'] ?? '', '/wp-json/') !== false);

if ($is_ajax || $is_wc_ajax || $is_rest) {
    // Mémoire max
    @ini_set('memory_limit', '1024M');

    // Log les erreurs fatales
    register_shutdown_function(function() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            error_log('[VS08 FATAL] ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
        }
    });

    // IMPORTANT: ne jamais modifier $wp_filter ici (risque de corruption callbacks).
    // Le mu-plugin vs08-disable-yoast-checkout.php retire déjà les plugins lourds.
}
