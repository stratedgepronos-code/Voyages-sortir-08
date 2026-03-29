<?php
/**
 * VS08 — Optimisations mémoire pour le checkout WooCommerce.
 *
 * 1. Désactive HPOS (Custom Order Tables) pendant le checkout AJAX
 *    → force le data store legacy (wp_posts) qui consomme ~400 Mo de moins.
 * 2. Désactive les plugins lourds non essentiels.
 * 3. Monte la limite mémoire.
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

    // ── Désactiver HPOS pendant le checkout ──
    // OrdersTableDataStore consomme ~600 Mo de plus que le legacy data store.
    // On force le retour au stockage posts/postmeta pour la création de commande.
    add_filter('woocommerce_custom_orders_table_enabled', '__return_false', 0);
    add_filter('woocommerce_orders_table_datastore_class', function() {
        return 'WC_Order_Data_Store_CPT';
    }, 0);

    // ── Désactiver WooCommerce Admin / Analytics (200+ Mo) ──
    add_filter('woocommerce_admin_disabled', '__return_true', 0);

    // ── Désactiver Action Scheduler inline ──
    add_filter('action_scheduler_queue_runner_time_limit', '__return_zero', 0);
    add_filter('action_scheduler_queue_runner_batch_size', '__return_zero', 0);

    // ── Bloquer les plugins non essentiels ──
    add_filter('option_active_plugins', function($plugins) {
        if (!is_array($plugins)) return $plugins;
        $allow = ['woocommerce', 'paybox', 'vs08-voyages', 'vs08-sejours', 'vs08-circuits'];
        return array_values(array_filter($plugins, function($p) use ($allow) {
            foreach ($allow as $a) {
                if (stripos($p, $a) !== false) return true;
            }
            return false;
        }));
    }, 0);
}

$_vs08_is_wc_ajax = !empty($_GET['wc-ajax']) || !empty($_REQUEST['wc-ajax']);
$_vs08_is_rest = (strpos($_SERVER['REQUEST_URI'] ?? '', '/wp-json/vs08') !== false);

if (($_vs08_is_wc_ajax || $_vs08_is_rest) && !$_vs08_is_checkout) {
    @ini_set('memory_limit', '1024M');

    add_filter('option_active_plugins', function($plugins) {
        if (!is_array($plugins)) return $plugins;
        $block = ['wordpress-seo', 'yoast', 'elementor', 'jetpack', 'wordfence', 'updraftplus', 'all-in-one-seo', 'rank-math', 'titan-seo'];
        return array_values(array_filter($plugins, function($p) use ($block) {
            foreach ($block as $b) {
                if (stripos($p, $b) !== false) return false;
            }
            return true;
        }));
    }, 0);
}
