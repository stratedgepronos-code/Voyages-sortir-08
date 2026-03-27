<?php
/**
 * Plugin Name: VS08 Séjours All Inclusive
 * Description: Gestion des séjours à forfait (vol + hôtel all inclusive) avec API Bedsonline/Hotelbeds.
 * Version: 1.0.0
 * Author: Voyages Sortir 08
 * Text Domain: vs08-sejours
 */
if (!defined('ABSPATH')) exit;

define('VS08S_PATH', plugin_dir_path(__FILE__));
define('VS08S_URL',  plugin_dir_url(__FILE__));
define('VS08S_VERSION', '1.0.0');

// ── Configuration API Bedsonline ──
$vs08s_config_file = VS08S_PATH . 'config.cfg';
if (file_exists($vs08s_config_file)) {
    $vs08s_cfg = parse_ini_file($vs08s_config_file);
    define('VS08S_BEDS_API_KEY',    $vs08s_cfg['beds_api_key']    ?? '');
    define('VS08S_BEDS_API_SECRET', $vs08s_cfg['beds_api_secret'] ?? '');
    define('VS08S_BEDS_SANDBOX',    ($vs08s_cfg['beds_sandbox']   ?? '1') === '1');
} else {
    define('VS08S_BEDS_API_KEY',    '');
    define('VS08S_BEDS_API_SECRET', '');
    define('VS08S_BEDS_SANDBOX',    true);
}

// ── Chargement des classes ──
require_once VS08S_PATH . 'includes/class-vs08s-cpt.php';
require_once VS08S_PATH . 'includes/class-vs08s-meta.php';
require_once VS08S_PATH . 'includes/class-vs08s-bedsonline.php';
require_once VS08S_PATH . 'includes/class-vs08s-calculator.php';
require_once VS08S_PATH . 'includes/class-vs08s-rest.php';
require_once VS08S_PATH . 'includes/class-vs08s-booking.php';
require_once VS08S_PATH . 'includes/class-vs08s-woo.php';
require_once VS08S_PATH . 'includes/class-vs08s-contract.php';
require_once VS08S_PATH . 'includes/class-vs08s-emails.php';

// ── Initialisation ──
add_action('init', ['VS08S_CPT', 'register']);
VS08S_Meta::register();
VS08S_Rest::register();
VS08S_Booking::register();
VS08S_Woo::register();

// ── Assets frontend ──
add_action('wp_enqueue_scripts', function() {
    if (!is_singular('vs08_sejour')) return;
    wp_enqueue_style('vs08s-front', VS08S_URL . 'assets/css/front.css', [], VS08S_VERSION);
    wp_enqueue_script('vs08s-front', VS08S_URL . 'assets/js/front.js', ['jquery'], VS08S_VERSION, true);
    wp_localize_script('vs08s-front', 'vs08s', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'rest_url' => rest_url('vs08s/v1/'),
        'nonce'    => wp_create_nonce('wp_rest'),
    ]);
});

// ── Template single ──
add_filter('single_template', function($template) {
    global $post;
    if ($post && $post->post_type === 'vs08_sejour') {
        $custom = VS08S_PATH . 'templates/single-vs08_sejour.php';
        if (file_exists($custom)) return $custom;
    }
    return $template;
});

// ── Hooks WooCommerce ──
add_action('woocommerce_payment_complete', function($order_id) {
    VS08S_Emails::dispatch($order_id);
});
add_action('woocommerce_order_status_changed', function($order_id, $old_status, $new_status) {
    if (in_array($new_status, ['processing', 'completed'])) {
        VS08S_Emails::dispatch($order_id);
    }
}, 10, 3);
add_action('woocommerce_thankyou', function($order_id) {
    if (!$order_id) return;
    VS08S_Emails::dispatch($order_id);
}, 1);

// ── Intégrer dans l'espace admin (dossiers) ──
add_filter('vs08v_dossier_extra_order_ids', function($ids) {
    if (!function_exists('wc_get_orders')) return $ids;
    $sejour_orders = wc_get_orders([
        'limit' => -1, 'return' => 'ids',
        'status' => array_keys(wc_get_order_statuses()),
        'meta_key' => '_vs08s_booking_data', 'meta_compare' => 'EXISTS',
    ]);
    return array_unique(array_merge($ids, $sejour_orders));
});
