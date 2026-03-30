<?php
/**
 * VS08 Checkout Guard — version finale propre.
 *
 * 2 protections simples pendant le checkout WooCommerce :
 * 1. Bloque Yoast SEO (il hook save_post → boucle mémoire)
 * 2. Désactive les emails WC par défaut (ils font $order->save() interne)
 *    → Les emails VS08 custom (contrat, confirmation) restent actifs.
 *
 * C'est tout. Pas de hack MySQL, pas de coupe-circuit save_post.
 */
if (!defined('ABSPATH')) exit;

// ── Détection requête WC AJAX ──
$is_wc_ajax = !empty($_GET['wc-ajax']) || !empty($_REQUEST['wc-ajax']);
if (!$is_wc_ajax) return;

// ── 1. Bloquer Yoast SEO pendant les requêtes WC AJAX ──
add_filter('option_active_plugins', function($plugins) {
    if (!is_array($plugins)) return $plugins;
    return array_values(array_filter($plugins, function($p) {
        return stripos($p, 'wordpress-seo') === false;
    }));
}, 0);

// ── Le reste ne s'applique qu'au checkout ──
$wc_action = $_GET['wc-ajax'] ?? $_REQUEST['wc-ajax'] ?? '';
if ($wc_action !== 'checkout') return;

// ── 2. Désactiver les emails WC par défaut pendant le checkout ──
// Ces emails font des $order->save() internes qui, avec HPOS sync,
// déclenchent des écritures en cascade → explosion mémoire.
// Les emails VS08 (class-emails.php dispatch) ne sont PAS touchés.
add_filter('woocommerce_email_enabled_new_order',                  '__return_false', 999);
add_filter('woocommerce_email_enabled_cancelled_order',            '__return_false', 999);
add_filter('woocommerce_email_enabled_failed_order',               '__return_false', 999);
add_filter('woocommerce_email_enabled_customer_on_hold_order',     '__return_false', 999);
add_filter('woocommerce_email_enabled_customer_processing_order',  '__return_false', 999);
add_filter('woocommerce_email_enabled_customer_completed_order',   '__return_false', 999);
add_filter('woocommerce_email_enabled_customer_invoice',           '__return_false', 999);

// ── 3. Fix MySQL "Commands out of sync" ──
// WooCommerce HPOS laisse des résultats non lus sur la connexion MySQL.
// Au shutdown, Action Scheduler et wp_cron essaient des requêtes → crash.
// On vide la connexion à plusieurs moments clés pour garantir un état propre.
function vs08_flush_mysqli() {
    global $wpdb;
    if (!$wpdb || !isset($wpdb->dbh) || !($wpdb->dbh instanceof mysqli)) return;
    // Vider les résultats wpdb internes
    if (isset($wpdb->result) && $wpdb->result instanceof mysqli_result) {
        @$wpdb->result->free();
        $wpdb->result = null;
    }
    // Consommer tous les result sets non lus sur la connexion
    while (@$wpdb->dbh->more_results()) {
        @$wpdb->dbh->next_result();
        $r = @$wpdb->dbh->store_result();
        if ($r instanceof mysqli_result) $r->free();
    }
}

// Flush après chaque étape majeure du checkout
add_action('woocommerce_checkout_order_processed', 'vs08_flush_mysqli', PHP_INT_MAX);
add_action('woocommerce_payment_complete',         'vs08_flush_mysqli', PHP_INT_MAX);

// Flush au tout début du shutdown (avant WooCommerce BatchProcessing + Action Scheduler)
add_action('shutdown', 'vs08_flush_mysqli', PHP_INT_MIN);
