<?php
/**
 * Plugin Name: VS08 Circuits — Voyages Sortir 08
 * Description: Création et réservation de circuits voyage. Back-office intuitif, page produit luxe, tunnel de réservation, paiement WooCommerce, emails, dossiers centralisés.
 * Version: 1.0.0
 * Author: Voyages Sortir 08
 * Requires WooCommerce: true
 */

if (!defined('ABSPATH')) exit;

define('VS08C_PATH', plugin_dir_path(__FILE__));
define('VS08C_URL',  plugin_dir_url(__FILE__));
define('VS08C_VER',  '1.2.1');

/* ─── Load all modules ─── */
require_once VS08C_PATH . 'includes/class-vs08c-cpt.php';
require_once VS08C_PATH . 'includes/class-vs08c-meta.php';
require_once VS08C_PATH . 'includes/class-vs08c-calculator.php';
require_once VS08C_PATH . 'includes/class-vs08c-booking.php';
require_once VS08C_PATH . 'includes/class-vs08c-woo.php';
require_once VS08C_PATH . 'includes/class-vs08c-contract.php';
require_once VS08C_PATH . 'includes/class-vs08c-emails.php';
require_once VS08C_PATH . 'includes/class-vs08c-ajax.php';
require_once VS08C_PATH . 'includes/class-vs08c-checkout.php';

/* ─── Init ─── */
add_action('init', ['VS08C_CPT', 'register']);

/* Admin meta boxes */
add_action('add_meta_boxes', ['VS08C_Meta', 'register']);
add_action('save_post', ['VS08C_Meta', 'save'], 10, 2);

/* Admin assets */
add_action('admin_enqueue_scripts', 'vs08c_admin_assets');
function vs08c_admin_assets($hook) {
    if (!in_array($hook, ['post.php', 'post-new.php'])) return;
    global $post;
    if (!$post || $post->post_type !== 'vs08_circuit') return;
    wp_enqueue_media();
    $admin_deps = ['jquery', 'jquery-ui-sortable'];
    if (defined('VS08V_URL')) {
        wp_enqueue_style('vs08-calendar', VS08V_URL . 'assets/css/vs08-calendar.css', [], '1.5.0');
        wp_enqueue_script('vs08-calendar', VS08V_URL . 'assets/js/vs08-calendar.js', [], '1.5.0', true);
        $admin_deps[] = 'vs08-calendar';
    }
    wp_enqueue_style('vs08c-admin', VS08C_URL . 'admin/css/admin.css', [], VS08C_VER);
    wp_enqueue_script('vs08c-admin', VS08C_URL . 'admin/js/admin.js', $admin_deps, VS08C_VER, true);
    wp_localize_script('vs08c-admin', 'vs08c_admin', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('vs08c_nonce'),
    ]);
}

/* Front-end assets */
add_action('wp_enqueue_scripts', 'vs08c_frontend_assets');
function vs08c_frontend_assets() {
    if (!is_singular('vs08_circuit') && !get_query_var('vs08c_booking')) return;
    $css_ver = file_exists(VS08C_PATH . 'assets/css/front.css') ? filemtime(VS08C_PATH . 'assets/css/front.css') : VS08C_VER;
    $js_ver  = file_exists(VS08C_PATH . 'assets/js/front.js') ? filemtime(VS08C_PATH . 'assets/js/front.js') : VS08C_VER;
    wp_enqueue_style('vs08c-front', VS08C_URL . 'assets/css/front.css', [], $css_ver);
    wp_enqueue_script('vs08c-front', VS08C_URL . 'assets/js/front.js', ['jquery'], $js_ver, true);
    wp_localize_script('vs08c-front', 'vs08c', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('vs08c_nonce'),
    ]);
    // Charger aussi le calendrier VS08 si disponible
    if (defined('VS08V_URL')) {
        wp_enqueue_style('vs08-calendar', VS08V_URL . 'assets/css/vs08-calendar.css', [], '1.5.0');
        wp_enqueue_script('vs08-calendar', VS08V_URL . 'assets/js/vs08-calendar.js', [], '1.5.0', true);
    }
}

/* Template override */
add_filter('single_template', function($template) {
    if (get_post_type() === 'vs08_circuit') {
        $custom = VS08C_PATH . 'templates/single-circuit.php';
        if (file_exists($custom)) return $custom;
    }
    return $template;
});

/* Booking endpoint: /reservation-circuit/{id}/ */
function vs08c_register_rewrite_rules() {
    add_rewrite_rule('^reservation-circuit/([0-9]+)/?$', 'index.php?vs08c_booking=1&vs08c_circuit_id=$matches[1]', 'top');
    add_rewrite_tag('%vs08c_booking%', '([0-9]+)');
    add_rewrite_tag('%vs08c_circuit_id%', '([0-9]+)');
}
add_action('init', 'vs08c_register_rewrite_rules');

/* Auto-flush rewrite rules after activation (runs once on next page load) */
add_action('init', function() {
    if (get_option('vs08c_flush_rewrite')) {
        delete_option('vs08c_flush_rewrite');
        flush_rewrite_rules();
    }
}, 99);

add_action('template_redirect', function() {
    if (get_query_var('vs08c_booking')) {
        include VS08C_PATH . 'templates/booking-circuit.php';
        exit;
    }
});

/* WooCommerce cart reconstruction (same pattern as vs08-voyages) */
add_action('template_redirect', function() {
    if (empty($_GET['vs08c_cart'])) return;
    if (!function_exists('is_checkout') || !is_checkout()) return;

    $token = sanitize_text_field($_GET['vs08c_cart']);
    $product_id = get_transient('vs08c_cart_' . $token);
    if (!$product_id) return;
    delete_transient('vs08c_cart_' . $token);

    if (!function_exists('WC') || !WC()) return;
    if (is_null(WC()->session) && method_exists(WC(), 'initialize_session')) WC()->initialize_session();
    if (is_null(WC()->cart)) {
        if (function_exists('wc_load_cart')) wc_load_cart();
        elseif (method_exists(WC(), 'initialize_cart')) WC()->initialize_cart();
    }
    if (!WC()->cart) return;

    WC()->cart->empty_cart();
    WC()->cart->add_to_cart((int) $product_id, 1);
    WC()->cart->calculate_totals();
    if (WC()->session) {
        if (!WC()->session->has_session()) WC()->session->set_customer_session_cookie(true);
        WC()->cart->set_session();
        WC()->cart->maybe_set_cart_cookies();
        if (method_exists(WC()->session, 'save_data')) WC()->session->save_data();
    }
}, 5);

/* Emails post-paiement */
add_action('woocommerce_payment_complete', function($order_id) {
    VS08C_Emails::dispatch($order_id);
});
add_action('woocommerce_order_status_changed', function($order_id, $old_status, $new_status) {
    if (in_array($new_status, ['processing', 'completed'])) {
        VS08C_Emails::dispatch($order_id);
    }
}, 10, 3);

/* Intégrer les circuits dans le menu Gestion Dossiers existant */
add_filter('vs08v_dossier_extra_order_ids', function($ids) {
    if (!function_exists('wc_get_orders')) return $ids;
    $circuit_ids = wc_get_orders([
        'limit'    => -1,
        'return'   => 'ids',
        'type'     => 'shop_order',
        'status'   => array_keys(wc_get_order_statuses()),
        'meta_key' => '_vs08c_booking_data',
    ]);
    return array_unique(array_merge($ids, is_array($circuit_ids) ? $circuit_ids : []));
});

/* Admin bar */
add_action('admin_bar_menu', function($wp_admin_bar) {
    if (!current_user_can('manage_options')) return;
    $wp_admin_bar->add_node([
        'id'     => 'vs08c-circuits',
        'title'  => '🗺️ Circuits',
        'href'   => admin_url('edit.php?post_type=vs08_circuit'),
        'meta'   => ['title' => 'Gestion des circuits'],
    ]);
}, 6);

/* Activation */
register_activation_hook(__FILE__, function() {
    VS08C_CPT::register();
    vs08c_register_rewrite_rules();
    flush_rewrite_rules();
    // Flag pour re-flush au prochain chargement (sécurité)
    update_option('vs08c_flush_rewrite', 1);
});
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});
