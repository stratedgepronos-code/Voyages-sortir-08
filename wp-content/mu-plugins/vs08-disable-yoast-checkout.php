<?php
/**
 * VS08 — Désactiver les plugins lourds pendant le checkout WooCommerce.
 *
 * Yoast SEO + autres plugins consomment 768 Mo+ de RAM pendant
 * la création de commande WC. Ce mu-plugin les empêche de se charger.
 *
 * Fichier: wp-content/mu-plugins/vs08-disable-yoast-checkout.php
 */
if (!defined('ABSPATH')) exit;

$is_wc_ajax = !empty($_GET['wc-ajax']);
$is_vs08_rest = (strpos($_SERVER['REQUEST_URI'] ?? '', '/wp-json/vs08') !== false);
$is_wc_checkout_ajax = $is_wc_ajax && (($_GET['wc-ajax'] ?? $_REQUEST['wc-ajax'] ?? '') === 'checkout');

if ($is_wc_ajax || $is_vs08_rest) {
    // Retirer les plugins lourds de la liste AVANT que WordPress les charge
    add_filter('option_active_plugins', function($plugins) use ($is_wc_checkout_ajax) {
        if (!is_array($plugins)) return $plugins;
        if ($is_wc_checkout_ajax) {
            $allow = ['woocommerce', 'paybox', 'vs08-voyages', 'vs08-sejours', 'vs08-circuits'];
            return array_values(array_filter($plugins, function($p) use ($allow) {
                foreach ($allow as $a) {
                    if (stripos($p, $a) !== false) return true;
                }
                return false;
            }));
        }
        $block = ['wordpress-seo', 'yoast', 'elementor', 'jetpack', 'wordfence', 'updraftplus', 'all-in-one-seo', 'rank-math'];
        return array_values(array_filter($plugins, function($p) use ($block) {
            foreach ($block as $b) {
                if (stripos($p, $b) !== false) return false;
            }
            return true;
        }));
    }, 0);

    @ini_set('memory_limit', '1536M');
}
