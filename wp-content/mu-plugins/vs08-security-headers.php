<?php
/**
 * VS08 Security Headers — mu-plugin production
 * Headers de sécurité + suppression infos serveur
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
    // Empêcher l'exposition de la version WP
    header_remove('X-Powered-By');
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
