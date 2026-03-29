<?php
/**
 * Polyfill compatibilité passerelles Paybox.
 *
 * show_message() existe dans wp-admin/includes/misc.php (contexte admin).
 * Ce polyfill ne la définit que sur les requêtes FRONT / AJAX / REST
 * où misc.php n'est pas chargé et où Paybox pourrait l'appeler.
 */
if (!defined('ABSPATH')) {
    exit;
}

$_is_wp_admin = defined('WP_ADMIN') && WP_ADMIN;
$_is_ajax     = defined('DOING_AJAX') && DOING_AJAX;

if ($_is_wp_admin && !$_is_ajax) {
    return;
}

if (!function_exists('show_message')) {
    function show_message($message = '') {
        if (is_scalar($message)) {
            return (string) $message;
        }
        if (is_object($message) && method_exists($message, '__toString')) {
            return (string) $message;
        }
        return '';
    }
}
