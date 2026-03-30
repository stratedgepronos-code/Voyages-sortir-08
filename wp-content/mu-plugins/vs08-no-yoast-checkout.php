<?php
/**
 * VS08 Checkout Memory Guard
 * 
 * Problème : WooCommerce + HPOS sync mode + Yoast + emails = 2 Go RAM → crash.
 * 
 * Ce mu-plugin fait 3 choses pendant le checkout AJAX :
 * 1. Bloque Yoast SEO (hook save_post → boucle infinie)
 * 2. Désactive les emails WooCommerce par défaut (new_order, customer_processing_order)
 *    → Ils font des $order->save() internes qui re-déclenchent la sync HPOS
 *    → Les emails VS08 custom (class-emails.php) restent actifs
 * 3. Empêche la ré-entrance sur save_post pour les shop_order
 * 
 * Ne touche PAS à memory_limit. Ne bloque AUCUN autre plugin que Yoast.
 * S'active UNIQUEMENT sur /?wc-ajax=checkout.
 */
if (!defined('ABSPATH')) exit;

// Détection large : GET ou REQUEST (WC envoie en POST avec wc-ajax dans l'URL)
$is_wc_ajax = !empty($_GET['wc-ajax']) || !empty($_REQUEST['wc-ajax']);
$is_checkout = $is_wc_ajax && (($_GET['wc-ajax'] ?? $_REQUEST['wc-ajax'] ?? '') === 'checkout');

if (!$is_wc_ajax) return;

// ─── 1. BLOQUER YOAST SEO ───────────────────────────────────────────
// Yoast hook sur save_post et entre en boucle sur les options autoload.
// On le retire AVANT qu'il soit chargé (mu-plugin = avant les plugins).
add_filter('option_active_plugins', function($plugins) {
    if (!is_array($plugins)) return $plugins;
    return array_values(array_filter($plugins, function($p) {
        return stripos($p, 'wordpress-seo') === false;
    }));
}, 0);

if (!$is_checkout) return;

// ─── 2. DÉSACTIVER LES EMAILS WC PAR DÉFAUT PENDANT LE CHECKOUT ────
// WooCommerce envoie "new_order" (admin) et "customer_processing_order" (client)
// pendant le checkout. Ces emails appellent $order->save() en interne,
// ce qui avec HPOS sync → save_post → re-sync → explosion mémoire.
// Les emails VS08 custom (dispatch/dispatch_pre_reservation) ne sont PAS touchés.
add_filter('woocommerce_email_enabled_new_order', '__return_false', 999);
add_filter('woocommerce_email_enabled_cancelled_order', '__return_false', 999);
add_filter('woocommerce_email_enabled_failed_order', '__return_false', 999);
add_filter('woocommerce_email_enabled_customer_on_hold_order', '__return_false', 999);
add_filter('woocommerce_email_enabled_customer_processing_order', '__return_false', 999);
add_filter('woocommerce_email_enabled_customer_completed_order', '__return_false', 999);
add_filter('woocommerce_email_enabled_customer_invoice', '__return_false', 999);

// ─── 3. COUPE-CIRCUIT : bloquer la ré-entrance save_post sur shop_order ─
// Avec HPOS sync, chaque écriture dans wp_posts déclenche save_post.
// Si un plugin (ou WC lui-même) re-save dans un save_post → boucle.
// Ce compteur coupe au bout de 3 appels (WC en fait max 1-2 légitimes).
$GLOBALS['vs08_save_post_guard'] = 0;
add_action('save_post', function($post_id, $post) {
    if (!isset($post->post_type) || $post->post_type !== 'shop_order') return;
    $GLOBALS['vs08_save_post_guard']++;
    if ($GLOBALS['vs08_save_post_guard'] > 3) {
        // Log + couper tous les hooks save_post pour cette requête
        error_log('[VS08 Guard] save_post shop_order appelé ' . $GLOBALS['vs08_save_post_guard'] . ' fois — boucle coupée (post_id=' . $post_id . ')');
        remove_all_actions('save_post');
    }
}, 0, 2);

// ─── 4. FIX MySQL "Commands out of sync" ────────────────────────────
// WooCommerce HPOS peut laisser des résultats non lus sur la connexion
// MySQL. Quand Action Scheduler fait ses requêtes au shutdown, il crashe
// avec "Commands out of sync". On vide la connexion à 2 moments :
// - Juste après la création de la commande (pendant la requête)
// - Au tout début du shutdown (avant Action Scheduler)

function vs08_flush_mysql_connection() {
    global $wpdb;
    if (!$wpdb || empty($wpdb->dbh)) return;

    // Méthode 1 : flush WordPress (vide les résultats internes)
    $wpdb->flush();

    // Méthode 2 : flush mysqli natif (vide les résultats non lus)
    if ($wpdb->dbh instanceof mysqli) {
        while (@$wpdb->dbh->more_results()) {
            @$wpdb->dbh->next_result();
            $res = @$wpdb->dbh->store_result();
            if ($res instanceof mysqli_result) {
                $res->free();
            }
        }
    }
}

// Flush après création de la commande WC (le moment où HPOS sync laisse des résultats)
add_action('woocommerce_checkout_order_processed', function() {
    vs08_flush_mysql_connection();
}, 9999);

// Flush juste avant la fin de la requête PHP (avant Action Scheduler / cron)
register_shutdown_function(function() {
    vs08_flush_mysql_connection();
});
