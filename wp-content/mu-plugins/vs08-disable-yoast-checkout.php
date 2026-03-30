<?php
/**
 * VS08 — Optimisations mémoire checkout WooCommerce.
 *
 * - Mémoire élevée pour le checkout
 * - Désactive WC Admin/Analytics pendant le checkout
 * - Bloque les plugins lourds (Yoast, etc.) sur AJAX/REST
 *
 * NOTE : on ne touche PAS à HPOS (cause "Commands out of sync").
 * Le chargement minimal dans vs08-voyages.php suffit (4 classes = ~14 Mo).
 */
if (!defined('ABSPATH')) exit;

$_vs08_is_checkout = (
    (!empty($_GET['wc-ajax']) && $_GET['wc-ajax'] === 'checkout') ||
    (!empty($_REQUEST['wc-ajax']) && $_REQUEST['wc-ajax'] === 'checkout')
);

if ($_vs08_is_checkout) {
    @ini_set('memory_limit', '512M');

    add_filter('woocommerce_admin_disabled', '__return_true', 0);
    add_filter('action_scheduler_queue_runner_time_limit', '__return_zero', 0);
    add_filter('action_scheduler_queue_runner_batch_size', '__return_zero', 0);
}

$_vs08_is_wc_ajax = !empty($_GET['wc-ajax']) || !empty($_REQUEST['wc-ajax']);
$_vs08_is_rest = (strpos($_SERVER['REQUEST_URI'] ?? '', '/wp-json/vs08') !== false);

if ($_vs08_is_wc_ajax || $_vs08_is_rest) {
    if (!$_vs08_is_checkout) {
        @ini_set('memory_limit', '512M');
    }
    add_filter('option_active_plugins', function($plugins) {
        if (!is_array($plugins)) return $plugins;
        $block = [
            'wordpress-seo', 'yoast', 'elementor', 'jetpack', 'wordfence',
            'updraftplus', 'all-in-one-seo', 'rank-math', 'titan-seo',
            'wp-cache', 'wps-hide-login', 'litespeed',
        ];
        return array_values(array_filter($plugins, function($p) use ($block) {
            foreach ($block as $b) {
                if (stripos($p, $b) !== false) return false;
            }
            return true;
        }));
    }, 0);
}
