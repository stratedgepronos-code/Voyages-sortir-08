<?php
/**
 * VS08 — Désactiver Yoast SEO pendant le checkout WooCommerce.
 *
 * Yoast SEO hook sur save_post/transition_post_status/options
 * consomme 768 Mo+ de RAM pendant la création de commande WC.
 * Ce mu-plugin empêche Yoast de se charger pendant les requêtes
 * AJAX WC et REST API VS08, où le SEO n'est pas nécessaire.
 *
 * Fichier: wp-content/mu-plugins/vs08-disable-yoast-checkout.php
 */
if (!defined('ABSPATH')) exit;

$is_wc_ajax = !empty($_GET['wc-ajax']);
$is_vs08_rest = (strpos($_SERVER['REQUEST_URI'] ?? '', '/wp-json/vs08') !== false);

if ($is_wc_ajax || $is_vs08_rest) {
    // Retirer Yoast de la liste des plugins AVANT que WordPress les charge
    add_filter('option_active_plugins', function($plugins) {
        if (!is_array($plugins)) return $plugins;
        return array_values(array_filter($plugins, function($p) {
            return stripos($p, 'wordpress-seo') === false;
        }));
    }, 0);

    // Augmenter la mémoire par sécurité
    @ini_set('memory_limit', '512M');
}
