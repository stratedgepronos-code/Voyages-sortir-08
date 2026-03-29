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
require_once VS08S_PATH . 'includes/class-vs08s-hotel-scanner.php';

// ── Initialisation ──
add_action('init', ['VS08S_CPT', 'register']);
VS08S_Meta::register();
VS08S_Rest::register();
VS08S_Booking::register();
VS08S_Woo::register();

// ── Booking endpoint: /reservation-sejour/{id}/ ──
add_action('init', function() {
    add_rewrite_rule('^reservation-sejour/([0-9]+)/?$', 'index.php?vs08s_booking=1&vs08s_sejour_id=$matches[1]', 'top');
    add_rewrite_tag('%vs08s_booking%', '([0-9]+)');
    add_rewrite_tag('%vs08s_sejour_id%', '([0-9]+)');
});

add_action('init', function() {
    if (get_option('vs08s_flush_rewrite')) {
        delete_option('vs08s_flush_rewrite');
        flush_rewrite_rules();
    }
    // Auto-flush si la règle n'existe pas encore (déploiement GitHub sans activation WP)
    global $wp_rewrite;
    $rules = $wp_rewrite->wp_rewrite_rules();
    if (!isset($rules['^reservation-sejour/([0-9]+)/?$']) && !isset($rules['reservation-sejour/([0-9]+)/?$'])) {
        flush_rewrite_rules();
        error_log('[VS08S] Rewrite rules auto-flushed for /reservation-sejour/');
    }
}, 99);

add_action('template_redirect', function() {
    if (get_query_var('vs08s_booking')) {
        $tpl = VS08S_PATH . 'templates/booking-sejour.php';
        if (file_exists($tpl)) { include $tpl; exit; }
    }
});

register_activation_hook(__FILE__, function() {
    VS08S_CPT::register();
    add_rewrite_rule('^reservation-sejour/([0-9]+)/?$', 'index.php?vs08s_booking=1&vs08s_sejour_id=$matches[1]', 'top');
    add_rewrite_tag('%vs08s_booking%', '([0-9]+)');
    add_rewrite_tag('%vs08s_sejour_id%', '([0-9]+)');
    flush_rewrite_rules();
    update_option('vs08s_flush_rewrite', 1);
});
VS08S_HotelScanner::register();

/**
 * ID du CPT vs08_sejour affiché (publié, brouillon en prévisualisation, révision).
 * En preview, $post peut être une révision : les métas et l’API doivent utiliser le parent.
 */
function vs08s_get_context_sejour_id() {
    global $post;
    if ($post && !empty($post->ID)) {
        if ($post->post_type === 'vs08_sejour') {
            return (int) $post->ID;
        }
        if ($post->post_type === 'revision' && !empty($post->post_parent)) {
            $parent = (int) $post->post_parent;
            if (get_post_type($parent) === 'vs08_sejour') {
                return $parent;
            }
        }
    }
    $qid = (int) get_queried_object_id();
    if ($qid && get_post_type($qid) === 'vs08_sejour') {
        return $qid;
    }
    if (!empty($_GET['preview_id'])) {
        $pid = absint($_GET['preview_id']);
        if ($pid) {
            $pt = get_post_type($pid);
            if ($pt === 'vs08_sejour') {
                return $pid;
            }
            if ($pt === 'revision') {
                $rev = get_post($pid);
                if ($rev && $rev->post_parent) {
                    $parent_id = (int) $rev->post_parent;
                    if (get_post_type($parent_id) === 'vs08_sejour') {
                        return $parent_id;
                    }
                }
            }
        }
    }
    if (!empty($_GET['p'])) {
        $pid = absint($_GET['p']);
        if ($pid && get_post_type($pid) === 'vs08_sejour') {
            return $pid;
        }
    }
    if (!empty($_GET['page_id'])) {
        $pid = absint($_GET['page_id']);
        if ($pid && get_post_type($pid) === 'vs08_sejour') {
            return $pid;
        }
    }
    $tid = (int) get_the_ID();
    if ($tid && get_post_type($tid) === 'vs08_sejour') {
        return $tid;
    }
    if (!empty($_SERVER['REQUEST_URI'])) {
        $path = strtok((string) $_SERVER['REQUEST_URI'], '?');
        $path = trim($path, '/');
        if ($path !== '') {
            $home_path = parse_url(home_url('/'), PHP_URL_PATH);
            if (is_string($home_path) && $home_path !== '' && $home_path !== '/') {
                $hp = trim($home_path, '/');
                if ($hp !== '' && strpos($path, $hp . '/') === 0) {
                    $path = substr($path, strlen($hp) + 1);
                } elseif ($path === $hp) {
                    $path = '';
                }
            }
            if ($path !== '') {
                foreach ([home_url('/' . $path . '/'), home_url('/' . $path)] as $u) {
                    $found = url_to_postid($u);
                    if ($found && get_post_type((int) $found) === 'vs08_sejour') {
                        return (int) $found;
                    }
                }
            }
        }
    }
    return 0;
}

// ── Assets frontend ──
add_action('wp_enqueue_scripts', function() {
    $sid = vs08s_get_context_sejour_id();
    if (!$sid && !is_singular('vs08_sejour')) {
        return;
    }
    wp_enqueue_style('vs08s-front', VS08S_URL . 'assets/css/front.css', [], VS08S_VERSION);
    wp_enqueue_script('vs08s-front', VS08S_URL . 'assets/js/front.js', ['jquery'], VS08S_VERSION, true);
    wp_localize_script('vs08s-front', 'vs08s', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'rest_url' => rest_url('vs08s/v1/'),
        'nonce'    => wp_create_nonce('wp_rest'),
    ]);
});

// Moins de bruit console : la fiche séjour n’utilise pas VS08V (calcul inline + vs08s).
add_action('wp_enqueue_scripts', function() {
    if (!vs08s_get_context_sejour_id() && !is_singular('vs08_sejour')) {
        return;
    }
    wp_dequeue_script('vs08v-front');
    wp_dequeue_style('vs08v-front');
}, 30);

// ── Template single ──
add_filter('single_template', function($template) {
    global $post;
    if (!$post) {
        return $template;
    }
    $pt  = $post->post_type;
    $pid = (int) $post->ID;
    if ($pt === 'revision' && !empty($post->post_parent)) {
        $pid = (int) $post->post_parent;
        $pt  = get_post_type($pid);
    }
    if ($pt === 'vs08_sejour') {
        $custom = VS08S_PATH . 'templates/single-vs08_sejour.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    return $template;
});

// ── Hooks WooCommerce ──
add_action('woocommerce_payment_complete', function($order_id) {
    if (!empty($_GET['wc-ajax'])) return; // différé via cron (vs08-voyages.php)
    VS08S_Emails::dispatch($order_id);
});
add_action('woocommerce_order_status_changed', function($order_id, $old_status, $new_status) {
    if (!empty($_GET['wc-ajax'])) return; // différé via cron
    if (in_array($new_status, ['processing', 'completed'])) {
        VS08S_Emails::dispatch($order_id);
    }
}, 10, 3);

// ── Intégrer dans la recherche globale ──
add_filter('vs08v_search_post_types', function($types) {
    $types[] = 'vs08_sejour';
    return $types;
});

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

// ── Copier _vs08s_booking_data — PAS pendant le checkout (crash mémoire) ──
// Le hook golf (class-woo.php) copie déjà _vs08v_booking_data pendant le checkout.
// On copie _vs08s_booking_data uniquement sur la page merci ou via cron.
add_action('woocommerce_thankyou', function($order_id) {
    if (!$order_id) return;
    $existing = get_post_meta($order_id, '_vs08s_booking_data', true);
    if (!empty($existing)) return;
    $order = wc_get_order($order_id);
    if (!$order) return;
    foreach ($order->get_items() as $item) {
        $pid = $item->get_product_id();
        if (!$pid) continue;
        $data = get_post_meta($pid, '_vs08s_booking_data', true);
        if (!empty($data) && is_array($data)) {
            update_post_meta($order_id, '_vs08s_booking_data', $data);
            error_log('[VS08S] Booking data copié sur commande VS08-' . $order_id . ' (thankyou)');
            break;
        }
    }
    // Dispatch emails séjour maintenant (page merci, pas checkout AJAX)
    VS08S_Emails::dispatch($order_id);
}, 5);
