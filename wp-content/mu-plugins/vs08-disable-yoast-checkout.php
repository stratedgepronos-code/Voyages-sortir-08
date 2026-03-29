<?php
/**
 * VS08 — Optimisations mémoire checkout WooCommerce.
 *
 * Force le data store legacy (CPT/postmeta) pendant wc-ajax=checkout
 * en interceptant les options WooCommerce HPOS au niveau WordPress.
 *
 * Fichier: wp-content/mu-plugins/vs08-disable-yoast-checkout.php
 */
if (!defined('ABSPATH')) exit;

$_vs08_is_checkout = (
    (!empty($_GET['wc-ajax']) && $_GET['wc-ajax'] === 'checkout') ||
    (!empty($_REQUEST['wc-ajax']) && $_REQUEST['wc-ajax'] === 'checkout')
);

if ($_vs08_is_checkout) {
    @ini_set('memory_limit', '2048M');

    // ── Forcer le data store legacy en interceptant get_option() ──
    // WooCommerce lit ces options pour décider d'utiliser HPOS ou CPT.
    // En retournant 'no' AVANT que Woo ne charge, on force le stockage CPT.
    add_filter('pre_option_woocommerce_custom_orders_table_enabled', function() {
        return 'no';
    }, 0);
    add_filter('pre_option_woocommerce_custom_orders_table_data_sync_enabled', function() {
        return 'no';
    }, 0);

    // Filtre Woo direct (belt & suspenders)
    add_filter('woocommerce_custom_orders_table_enabled', '__return_false', 0);
    add_filter('woocommerce_orders_table_datastore_class', function() {
        return 'WC_Order_Data_Store_CPT';
    }, 0);

    // ── Désactiver WooCommerce Admin / Analytics ──
    add_filter('woocommerce_admin_disabled', '__return_true', 0);
    add_filter('action_scheduler_queue_runner_time_limit', '__return_zero', 0);
    add_filter('action_scheduler_queue_runner_batch_size', '__return_zero', 0);
}

// ── Plugins lourds : bloquer sur toute requête AJAX/REST ──
$_vs08_is_wc_ajax = !empty($_GET['wc-ajax']) || !empty($_REQUEST['wc-ajax']);
$_vs08_is_rest = (strpos($_SERVER['REQUEST_URI'] ?? '', '/wp-json/vs08') !== false);

if ($_vs08_is_wc_ajax || $_vs08_is_rest) {
    if (!$_vs08_is_checkout) {
        @ini_set('memory_limit', '1024M');
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
