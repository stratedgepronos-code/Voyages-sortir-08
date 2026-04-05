<?php
/**
 * VS08 Security Headers — mu-plugin production
 * Headers de sécurité + suppression infos serveur + hardening
 */
if (!defined('ABSPATH')) exit;

add_action('send_headers', function() {
    // Empêcher le clickjacking (iframe embedding)
    header('X-Frame-Options: SAMEORIGIN');
    // Empêcher le MIME sniffing
    header('X-Content-Type-Options: nosniff');
    // Protection XSS navigateur
    header('X-XSS-Protection: 1; mode=block');
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // HSTS — force HTTPS pendant 1 an
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://unpkg.com https://maps.googleapis.com https://tpeweb1.paybox.com https://tpeweb.paybox.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; connect-src 'self' https://api.duffel.com https://fonts.googleapis.com; frame-src https://tpeweb1.paybox.com https://tpeweb.paybox.com; frame-ancestors 'self';");
    // Permissions Policy — désactiver les APIs inutiles
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(self), payment=(self)');
    // Empêcher l'exposition de la version WP/PHP
    header_remove('X-Powered-By');
    header_remove('Server');
});

// Supprimer la version WordPress du HTML
remove_action('wp_head', 'wp_generator');
// Supprimer les liens REST API du <head> (évite la découverte d'endpoints)
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('wp_head', 'wp_oembed_add_discovery_links');
// Supprimer le lien Windows Live Writer
remove_action('wp_head', 'wlwmanifest_link');
// Supprimer le lien RSD (Really Simple Discovery)
remove_action('wp_head', 'rsd_link');
// Supprimer les shortlinks
remove_action('wp_head', 'wp_shortlink_wp_head');

// Bloquer l'accès à xmlrpc.php (souvent ciblé par les bots)
add_filter('xmlrpc_enabled', '__return_false');

// Désactiver l'éditeur de fichiers dans l'admin WP
if (!defined('DISALLOW_FILE_EDIT')) {
    define('DISALLOW_FILE_EDIT', true);
}

// Rediriger wp-login.php vers /connexion/ (sauf admin déjà connecté)
add_action('login_init', function() {
    if (!is_user_logged_in() && !isset($_GET['action'])) {
        wp_redirect(home_url('/connexion/'));
        exit;
    }
});

// Masquer les utilisateurs via REST API
add_filter('rest_endpoints', function($endpoints) {
    if (isset($endpoints['/wp/v2/users'])) {
        unset($endpoints['/wp/v2/users']);
    }
    if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'])) {
        unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
    }
    return $endpoints;
});
