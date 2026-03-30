<?php
/**
 * VS08 Checkout Guard
 *
 * 1. Désactive HPOS (High-Performance Order Storage) de WooCommerce.
 *    → C'est la cause racine de TOUS les problèmes : crash mémoire 2 Go,
 *      erreurs "Commands out of sync", sessions non sauvegardées.
 *    → HPOS fait une double écriture (nouvelles tables + anciennes tables)
 *      qui empoisonne la connexion MySQL sur Hostinger mutualisé.
 *    → Le site fonctionnait parfaitement sans HPOS le 21 mars 2026.
 *
 * 2. Bloque Yoast SEO pendant les requêtes WC AJAX.
 *    → Yoast hook save_post et consomme de la RAM inutilement.
 *
 * 3. Désactive les emails WC par défaut pendant le checkout.
 *    → Nos emails VS08 custom les remplacent.
 */
if (!defined('ABSPATH')) exit;

// ═══════════════════════════════════════════════════════════════════
// 1. DÉSACTIVER HPOS — forcer le stockage legacy (wp_posts/wp_postmeta)
// ═══════════════════════════════════════════════════════════════════
// Cette option dit à WooCommerce : "utilise l'ancien stockage, pas HPOS".
// On force la valeur AVANT que WooCommerce la lise dans la base.
// Ça désactive la double écriture qui cause les Commands out of sync.
add_filter('pre_option_woocommerce_custom_orders_table_enabled', function() {
    return 'no';
});
// Désactiver aussi le mode compatibilité (sync entre tables)
add_filter('pre_option_woocommerce_custom_orders_table_data_sync_enabled', function() {
    return 'no';
});

// ═══════════════════════════════════════════════════════════════════
// 2. BLOQUER YOAST SEO pendant les requêtes WC AJAX
// ═══════════════════════════════════════════════════════════════════
$is_wc_ajax = !empty($_GET['wc-ajax']) || !empty($_REQUEST['wc-ajax']);
if ($is_wc_ajax) {
    add_filter('option_active_plugins', function($plugins) {
        if (!is_array($plugins)) return $plugins;
        return array_values(array_filter($plugins, function($p) {
            return stripos($p, 'wordpress-seo') === false;
        }));
    }, 0);
}

// ═══════════════════════════════════════════════════════════════════
// 3. DÉSACTIVER LES EMAILS WC PAR DÉFAUT pendant le checkout
// ═══════════════════════════════════════════════════════════════════
$wc_action = $_GET['wc-ajax'] ?? $_REQUEST['wc-ajax'] ?? '';
if ($wc_action === 'checkout') {
    add_filter('woocommerce_email_enabled_new_order',                  '__return_false', 999);
    add_filter('woocommerce_email_enabled_cancelled_order',            '__return_false', 999);
    add_filter('woocommerce_email_enabled_failed_order',               '__return_false', 999);
    add_filter('woocommerce_email_enabled_customer_on_hold_order',     '__return_false', 999);
    add_filter('woocommerce_email_enabled_customer_processing_order',  '__return_false', 999);
    add_filter('woocommerce_email_enabled_customer_completed_order',   '__return_false', 999);
    add_filter('woocommerce_email_enabled_customer_invoice',           '__return_false', 999);
}
