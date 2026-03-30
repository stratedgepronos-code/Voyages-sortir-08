<?php
/**
 * Plugin Name: VS08 Voyages — Réservation Golf
 * Description: Système complet de création et réservation de voyages golf. Back-office, calcul de prix, tunnel de réservation, WooCommerce.
 * Version: 2.0.0
 * Author: Voyages Sortir 08
 * Requires WooCommerce: true
 */
// Déploiement CI: modification pour déclencher le workflow GitHub Actions

if (!defined('ABSPATH')) exit;

// Polyfill show_message() géré par mu-plugins/paybox-polyfill.php

define('VS08V_PATH', plugin_dir_path(__FILE__));
define('VS08V_URL',  plugin_dir_url(__FILE__));
define('VS08V_VER',  '2.0.0');

// ============================================================
// CHARGEMENT SÉCURISÉ DE LA CLÉ DUFFEL DEPUIS config.cfg
// Le fichier config.cfg doit être à la racine du plugin :
//   public_html/wp-content/plugins/vs08-voyages/config.cfg
// Format attendu dans config.cfg :
//   DUFFEL_API_KEY=duffel_live_XXXXXXXXXXXXXXXX
//   SERPAPI_API_KEY=xxxx   (fortement recommandé en prod : Google Flights / charters hors Duffel)
//   Ce fichier n'est PAS déployé par GitHub Actions (--exclude config.cfg) : à créer/éditer sur le serveur.
//   Alternative : variable d'environnement VS08_SERPAPI_API_KEY ou SERPAPI_API_KEY sur l'hébergeur.
//   Ou dans wp-config.php AVANT wp-settings.php : define('VS08_SERPAPI_API_KEY', '...');
//   CLAUDE_API_KEY=sk-ant-api03-xxxx   (recherche IA hôtel/golf — clé Anthropic, sinon "invalid x-api-key")
//   VS08_SANDBOX_PAYMENT=1   (optionnel : déverrouille la dernière étape si "token invalide" en test)
// ============================================================
$vs08v_config_file = VS08V_PATH . 'config.cfg';
if (!file_exists($vs08v_config_file) && defined('ABSPATH')) {
    $vs08v_config_file = ABSPATH . 'wp-content/plugins/vs08-voyages/config.cfg';
}
if (file_exists($vs08v_config_file)) {
    $raw = @file_get_contents($vs08v_config_file);
    if ($raw !== false) {
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
        $lines = preg_split('/\r\n|\r|\n/', $raw);
    } else {
        $lines = [];
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#' || $line[0] === ';') continue;
        if (strpos($line, '=') !== false) {
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\r\n\"'");
            if ($key === 'DUFFEL_API_KEY' && !defined('VS08_DUFFEL_API_KEY')) {
                define('VS08_DUFFEL_API_KEY', $val);
            }
            if ($key === 'SERPAPI_API_KEY' && !defined('VS08_SERPAPI_API_KEY')) {
                define('VS08_SERPAPI_API_KEY', $val);
            }
            if (($key === 'CLAUDE_API_KEY' || $key === 'VS08V_CLAUDE_KEY') && !defined('VS08V_CLAUDE_KEY')) {
                define('VS08V_CLAUDE_KEY', $val);
            }
            if ($key === 'CLAUDE_MODEL' && !defined('VS08V_CLAUDE_MODEL')) {
                define('VS08V_CLAUDE_MODEL', $val);
            }
            if ($key === 'CLAUDE_MODEL_HOTEL' && !defined('VS08V_CLAUDE_MODEL_HOTEL')) {
                define('VS08V_CLAUDE_MODEL_HOTEL', $val);
            }
            if ($key === 'VS08_SANDBOX_PAYMENT' && !defined('VS08_SANDBOX_PAYMENT')) {
                define('VS08_SANDBOX_PAYMENT', $val === '1' || $val === 'true' || $val === 'yes');
            }
            if ($key === 'PAYBOX_MAIL_APP_KEY' && !defined('VS08_PAYBOX_MAIL_APP_KEY')) {
                define('VS08_PAYBOX_MAIL_APP_KEY', $val);
            }
            if ($key === 'PAYBOX_MAIL_SECRET_KEY' && !defined('VS08_PAYBOX_MAIL_SECRET_KEY')) {
                define('VS08_PAYBOX_MAIL_SECRET_KEY', $val);
            }
        }
    }
}

// SerpApi : repli si config.cfg ne définit pas la clé (ex. Hostinger sans ligne SERPAPI)
if (!defined('VS08_SERPAPI_API_KEY')) {
    $serp_env = getenv('VS08_SERPAPI_API_KEY');
    if ($serp_env !== false && $serp_env !== '') {
        define('VS08_SERPAPI_API_KEY', $serp_env);
    } else {
        $serp_env = getenv('SERPAPI_API_KEY');
        if ($serp_env !== false && $serp_env !== '') {
            define('VS08_SERPAPI_API_KEY', $serp_env);
        }
    }
}

// ════════════════════════════════════════════════════════════════════
// CHARGEMENT INTELLIGENT DES MODULES
// Avant : 27 fichiers PHP chargés sur CHAQUE requête (13 000 lignes)
// Après : seulement les fichiers nécessaires au contexte
// ════════════════════════════════════════════════════════════════════

// ── TOUJOURS (core WordPress / WooCommerce hooks) ──
require_once VS08V_PATH . 'includes/class-post-type.php';      // CPT registration
require_once VS08V_PATH . 'includes/class-woo.php';             // hooks checkout/thankyou
require_once VS08V_PATH . 'includes/class-checkout.php';        // affichage page checkout
require_once VS08V_PATH . 'includes/class-booking.php';         // tunnel de réservation
require_once VS08V_PATH . 'includes/class-search.php';          // recherche AJAX (front+admin)
require_once VS08V_PATH . 'includes/class-rest.php';            // REST API
require_once VS08V_PATH . 'includes/class-traveler-space.php';  // espace voyageur (front)
require_once VS08V_PATH . 'includes/class-emails.php';          // dispatch emails (hooks WC)
require_once VS08V_PATH . 'includes/class-ajax.php';            // AJAX front

// ── ADMIN SEULEMENT (back-office WordPress) ──
if (is_admin()) {
    require_once VS08V_PATH . 'includes/class-meta-boxes.php';       // meta boxes voyage
    require_once VS08V_PATH . 'includes/class-hotel-box.php';        // meta box hôtel
    require_once VS08V_PATH . 'includes/class-hotel-scanner.php';    // scan Bedsonline
    require_once VS08V_PATH . 'includes/class-golf-box.php';         // meta box golf
    require_once VS08V_PATH . 'includes/class-compris-box.php';      // meta box "compris"
    require_once VS08V_PATH . 'includes/class-admin-dossiers.php';   // gestion dossiers
    require_once VS08V_PATH . 'includes/class-admin-espace-ajax.php';// AJAX admin
    require_once VS08V_PATH . 'includes/class-homepage-editor.php';  // éditeur homepage
    require_once VS08V_PATH . 'includes/class-duplicate-voyage.php'; // dupliquer voyage
    require_once VS08V_PATH . 'includes/class-insurance.php';        // page assurances admin
    VS08V_Homepage_Editor::register();
    VS08V_Admin_Dossiers::register();
    VS08V_Admin_Espace_Ajax::register();
    VS08V_Duplicate_Voyage::register();
}

// ── CHARGEMENT À LA DEMANDE (jamais au boot) ──
// Ces classes n'ont AUCUN hook WordPress. Elles sont appelées par
// d'autres classes quand elles en ont besoin. On les charge via
// un autoloader léger.
//
// class-calculator.php    → appelé par booking-steps.php
// class-serpapi.php        → appelé par class-ajax.php (recherche vols Ryanair)
// class-duffel-api.php     → appelé par class-ajax.php (recherche vols Duffel)
// class-contract.php       → appelé par class-emails.php (génération contrat)
// class-paybox-mail.php    → appelé par class-woo.php (notification Paybox)

spl_autoload_register(function($class) {
    $map = [
        'VS08V_Calculator'    => 'class-calculator.php',
        'VS08_SerpApi'        => 'class-serpapi.php',
        'VS08_Duffel_API'     => 'class-duffel-api.php',
        'VS08V_Contract'      => 'class-contract.php',
        'VS08V_Paybox_Mail'   => 'class-paybox-mail.php',
        'VS08V_Insurance'     => 'class-insurance.php',
        'VS08V_MetaBoxes'     => 'class-meta-boxes.php',
        'VS08V_HotelBox'      => 'class-hotel-box.php',
        'VS08V_HotelScanner'  => 'class-hotel-scanner.php',
        'VS08V_GolfBox'       => 'class-golf-box.php',
        'VS08V_ComprisBox'    => 'class-compris-box.php',
    ];
    if (isset($map[$class])) {
        $file = VS08V_PATH . 'includes/' . $map[$class];
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Init des modules toujours chargés
VS08V_Search::register();
VS08V_REST::register();
VS08V_Traveler_Space::register();

add_action('woocommerce_loaded', function() {
    require_once VS08V_PATH . 'includes/class-gateway-agence.php';
    require_once VS08V_PATH . 'includes/class-vs08-checkout-payment.php';
    VS08_Checkout_Payment::register();
});
add_action('init', ['VS08V_PostType', 'register']);

add_action('wp_mail_failed', function($wp_error) {
    if (!($wp_error instanceof WP_Error)) {
        return;
    }
    $message = $wp_error->get_error_message();
    $data = $wp_error->get_error_data();
    if (is_array($data)) {
        $summary = [];
        foreach (['to', 'subject', 'phpmailer_exception_code'] as $key) {
            if (!empty($data[$key])) {
                $summary[] = $key . '=' . (is_array($data[$key]) ? implode(',', $data[$key]) : $data[$key]);
            }
        }
        if (!empty($summary)) {
            $message .= ' [' . implode(' | ', $summary) . ']';
        }
    }
    error_log('[VS08 wp_mail_failed] ' . $message);
}, 10, 1);

// Titres admin pour nos pages custom (complète le mu-plugin fix-php81-deprecations)
add_action('admin_enqueue_scripts', function($hook_suffix) {
    if (!isset($_GET['page'])) return;
    $page = sanitize_text_field(wp_unslash($_GET['page'] ?? ''));
    if (strpos($page, 'vs08-dossiers') !== 0) return;
    global $title;
    if ($title === null || $title === '') {
        if ($page === 'vs08-dossiers-edit') $title = 'Modifier le dossier';
        elseif ($page === 'vs08-dossiers-list') $title = 'Dossiers';
        else $title = 'Gestion Dossiers Voyages';
    }
}, 1);
add_filter('document_title_parts', function($parts) {
    if (!is_array($parts)) return $parts;
    foreach (['title', 'page', 'tagline'] as $k) {
        if (array_key_exists($k, $parts) && $parts[$k] === null) $parts[$k] = '';
    }
    return $parts;
}, 1);
// Meta boxes + hotel scanner : ADMIN SEULEMENT
// add_meta_boxes ne fire qu'en admin, mais HotelScanner::register() est un appel direct
if (is_admin()) {
    add_action('add_meta_boxes', ['VS08V_MetaBoxes', 'register']);
    add_action('add_meta_boxes', ['VS08V_HotelBox', 'register']);
    add_action('add_meta_boxes', ['VS08V_GolfBox', 'register']);
    add_action('add_meta_boxes', ['VS08V_ComprisBox', 'register']);
    VS08V_HotelScanner::register();
    add_action('wp_ajax_vs08v_scan_golf_pdf', ['VS08V_HotelScanner', 'ajax_scan_golf_pdf']);
    add_action('save_post', ['VS08V_MetaBoxes', 'save'], 10, 2);
}
add_action('wp_enqueue_scripts', 'vs08v_frontend_assets');
add_action('wp_enqueue_scripts', function () {
    /* Thème vs08 : handle réel = vs08-footer-terminal (l’ancien footer-terminal ne matchait pas → wttr.in partait encore) */
    wp_dequeue_script('vs08-footer-terminal');
    wp_dequeue_style('vs08-footer-terminal');
    wp_deregister_script('vs08-footer-terminal');
    wp_deregister_style('vs08-footer-terminal');
    wp_dequeue_script('footer-terminal');
    wp_deregister_script('footer-terminal');
}, 999);
add_action('wp_print_scripts', function () {
    wp_dequeue_script('vs08-footer-terminal');
    wp_dequeue_script('footer-terminal');
    wp_deregister_script('vs08-footer-terminal');
    wp_deregister_script('footer-terminal');
}, 100);
add_action('admin_enqueue_scripts', 'vs08v_admin_assets');

function vs08v_frontend_assets() {
    wp_dequeue_script('vs08-footer-terminal');
    wp_dequeue_style('vs08-footer-terminal');
    wp_dequeue_script('footer-terminal');
    wp_deregister_script('footer-terminal');

    wp_enqueue_style('vs08v-front', VS08V_URL . 'assets/css/front.css', [], VS08V_VER);
    wp_enqueue_script('vs08v-front', VS08V_URL . 'assets/js/front.js', ['jquery'], VS08V_VER, true);
    $rest_base = rest_url(VS08V_REST::NAMESPACE);
    wp_localize_script('vs08v-front', 'vs08v', [
        'ajax_url'       => admin_url('admin-ajax.php'),
        'rest_flight'    => $rest_base . '/flight',
        'rest_calculate' => $rest_base . '/calculate',
        'nonce'      => wp_create_nonce('vs08v_nonce'),
    ]);

    // ── VS08 Calendar Premium ──
    wp_enqueue_style('vs08-calendar', VS08V_URL . 'assets/css/vs08-calendar.css', [], '1.5.0');
    wp_enqueue_script('vs08-calendar', VS08V_URL . 'assets/js/vs08-calendar.js', [], '1.5.0', true);
}

function vs08v_admin_assets($hook) {
    if (!in_array($hook, ['post.php', 'post-new.php'])) return;
    global $post;
    if (!$post || $post->post_type !== 'vs08_voyage') return;
    wp_enqueue_media();
    wp_enqueue_style('vs08v-admin', VS08V_URL . 'assets/css/admin.css', [], VS08V_VER);
    wp_enqueue_script('vs08v-admin', VS08V_URL . 'assets/js/admin.js', ['jquery'], VS08V_VER, true);
    wp_localize_script('vs08v-admin', 'vs08v_admin', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('vs08v_nonce'),
    ]);

    // ── VS08 Calendar Premium (admin) ──
    wp_enqueue_style('vs08-calendar', VS08V_URL . 'assets/css/vs08-calendar.css', [], '1.5.0');
    wp_enqueue_script('vs08-calendar', VS08V_URL . 'assets/js/vs08-calendar.js', [], '1.5.0', true);
}

// Barre admin VS08
add_action('admin_bar_menu', function($wp_admin_bar) {
    if (!current_user_can('manage_options')) return;
    $wp_admin_bar->add_node([
        'id'     => 'vs08v-logo',
        'title'  => '✈️ VS08 Voyages',
        'href'   => admin_url('edit.php?post_type=vs08_voyage'),
        'meta'   => ['title' => 'Voyages Sortir 08 — Tableau de bord'],
    ]);
}, 5);

add_action('admin_enqueue_scripts', function() {
    echo '<style>
    #wp-admin-bar-vs08v-logo { }
    #wp-admin-bar-vs08v-logo > .ab-item { padding:0 10px!important;display:flex!important;align-items:center!important;height:32px!important }
    #wp-admin-bar-vs08v-logo > .ab-item:hover { background:rgba(255,255,255,.1)!important;border-radius:4px }
    </style>';
});
add_action('wp_head', function() {
    if (!is_admin_bar_showing()) return;
    echo '<style>
    #wp-admin-bar-vs08v-logo > .ab-item { padding:0 10px!important;display:flex!important;align-items:center!important;height:32px!important }
    #wp-admin-bar-vs08v-logo > .ab-item:hover { background:rgba(255,255,255,.1)!important;border-radius:4px }
    </style>';
});

// ============================================================
// RECONSTRUCTION DU PANIER SUR LA PAGE CHECKOUT
// Résout le problème « panier vide » : les cookies de session définis
// dans une réponse AJAX/REST ne sont pas toujours enregistrés par le
// navigateur avant la redirection JS. On utilise un token transient
// passé en query string pour reconstruire le panier côté serveur,
// dans la même requête HTTP que le rendu de la page checkout.
// ============================================================
add_action('template_redirect', function() {
    if (empty($_GET['vs08_cart'])) return;
    if (!function_exists('is_checkout') || !is_checkout()) return;

    $token = sanitize_text_field($_GET['vs08_cart']);
    $transient_key = 'vs08_cart_' . $token;
    $raw = get_transient($transient_key);
    list($product_id, $pay_mode) = class_exists('VS08_Checkout_Payment')
        ? VS08_Checkout_Payment::parse_cart_transient_payload($raw)
        : [is_array($raw) ? (int) ($raw['product_id'] ?? 0) : (int) $raw, (is_array($raw) && (($raw['payment_mode'] ?? '') === 'agency')) ? 'agency' : 'card'];

    if (!$product_id) {
        return;
    }

    delete_transient($transient_key);

    if (!function_exists('WC') || !WC()) return;

    if (is_null(WC()->session) && method_exists(WC(), 'initialize_session')) {
        WC()->initialize_session();
    }
    if (is_null(WC()->cart)) {
        if (function_exists('wc_load_cart')) { wc_load_cart(); }
        elseif (method_exists(WC(), 'initialize_cart')) { WC()->initialize_cart(); }
    }
    if (!WC()->cart) return;

    if (WC()->session) {
        $sk = class_exists('VS08_Checkout_Payment') ? VS08_Checkout_Payment::SESSION_KEY : 'vs08_checkout_payment_mode';
        WC()->session->set($sk, $pay_mode === 'agency' ? 'agency' : 'card');
    }

    WC()->cart->empty_cart();
    WC()->cart->add_to_cart((int) $product_id, 1);
    WC()->cart->calculate_totals();
    if (WC()->session) {
        if (!WC()->session->has_session()) {
            WC()->session->set_customer_session_cookie(true);
        }
        WC()->cart->set_session();
        WC()->cart->maybe_set_cart_cookies();
        if (method_exists(WC()->session, 'save_data')) {
            WC()->session->save_data();
        }
    }
}, 5); // Priorité 5 = avant le rendu du template checkout

// Endpoint réservation
add_action('init', function() {
    add_rewrite_rule('^reservation/([0-9]+)/?$', 'index.php?vs08_booking=1&vs08_voyage_id=$matches[1]', 'top');
    add_rewrite_tag('%vs08_booking%', '([0-9]+)');
    add_rewrite_tag('%vs08_voyage_id%', '([0-9]+)');
});
add_action('template_redirect', function() {
    if (get_query_var('vs08_booking')) {
        include VS08V_PATH . 'templates/booking-steps.php';
        exit;
    }
});

// ============================================================
// EMAILS POST-PAIEMENT (contrat de vente admin + client)
// ============================================================
// EMAILS POST-PAIEMENT
// ============================================================
// IMPORTANT : on NE dispatch PAS pendant le checkout AJAX.
// Le checkout doit répondre en <3 secondes. Les emails SMTP prennent
// 1-2 minutes chacun sur Hostinger mutualisé.
// Les emails sont envoyés sur la page merci (woocommerce_thankyou)
// qui est une requête GET séparée, sans contrainte de temps.

add_action('woocommerce_payment_complete', function($order_id) {
    // Ne PAS envoyer pendant le checkout AJAX (trop lent)
    if (!empty($_REQUEST['wc-ajax'])) return;
    VS08V_Emails::dispatch($order_id);
});

add_action('woocommerce_order_status_changed', function($order_id, $old_status, $new_status) {
    // Ne PAS envoyer pendant le checkout AJAX (trop lent)
    if (!empty($_REQUEST['wc-ajax'])) return;
    if (in_array($new_status, ['processing', 'completed'])) {
        VS08V_Emails::dispatch($order_id);
    }
}, 10, 3);

// Helper : retourne l'ID de dossier espace membre cible (commande principale ou parent solde), sinon 0.
function vs08v_get_target_order_id_for_espace($order) {
    if (!$order || !is_object($order)) return 0;
    $order_id = (int) $order->get_id();
    if (!$order_id) return 0;

    // Solde : rediriger vers la commande parent.
    $parent_id = (int) $order->get_meta('_vs08v_order_solde_parent');
    if ($parent_id > 0) return $parent_id;

    // Réservation golf.
    if ($order->get_meta('_vs08v_booking_data')) return $order_id;

    // Réservation circuit sur la commande.
    if ($order->get_meta('_vs08c_booking_data')) return $order_id;

    // Réservation circuit/golf sur les lignes (fallback).
    foreach ($order->get_items() as $item) {
        if ($item->get_meta('_vs08c_booking_data') || $item->get_meta('_vs08v_booking_data')) {
            return $order_id;
        }
        $pid = (int) $item->get_product_id();
        if ($pid > 0) {
            if (get_post_meta($pid, '_vs08c_booking_data', true) || get_post_meta($pid, '_vs08v_booking_data', true)) {
                return $order_id;
            }
        }
    }

    return 0;
}

function vs08v_get_espace_url_for_order($order) {
    $target_order_id = vs08v_get_target_order_id_for_espace($order);
    if ($target_order_id <= 0) return '';
    return VS08V_Traveler_Space::voyage_url($target_order_id);
}

// ═══════════════════════════════════════════════════════════════════
// REDIRECTION VERS ESPACE VOYAGEUR
// ═══════════════════════════════════════════════════════════════════
// IMPORTANT : on ne redirige PAS côté serveur (exit). On laisse la
// page merci (order-received) charger normalement pour que les hooks
// woocommerce_thankyou se déclenchent (copie données + envoi emails).
// La redirection vers l'espace voyageur se fait via JS APRÈS les hooks.

// Secours : redirection JS sur la page thank you.
// Priorité 5 = APRÈS copie données (0) + pré-resa emails (2)
add_action('woocommerce_thankyou', function($order_id) {
    if (!$order_id) return;
    // Filet de sécurité : dispatch email si pas encore fait
    VS08V_Emails::dispatch($order_id);
    if (class_exists('VS08C_Emails')) VS08C_Emails::dispatch($order_id);
    $order = wc_get_order($order_id);
    $target = vs08v_get_espace_url_for_order($order);
    if (!$target) return;
    echo '<script>window.location.replace("' . esc_js(esc_url($target)) . '");</script>';
}, 5);

// Cron rappel solde (J-14 et J-3)
add_action('vs08v_solde_reminder_cron', function() {
    VS08V_Emails::run_solde_reminders();
});
add_action('init', function() {
    if (defined('DOING_CRON') && DOING_CRON) {
        return;
    }
    if (get_transient('vs08v_cron_solde_scheduled')) {
        return;
    }
    if (!wp_next_scheduled('vs08v_solde_reminder_cron')) {
        wp_schedule_event(time(), 'daily', 'vs08v_solde_reminder_cron');
        set_transient('vs08v_cron_solde_scheduled', 1, DAY_IN_SECONDS);
    }
});

// Activation
register_activation_hook(__FILE__, function() {
    VS08V_PostType::register();
    flush_rewrite_rules();
    VS08V_Insurance::create_defaults();
    if (!wp_next_scheduled('vs08v_solde_reminder_cron')) {
        wp_schedule_event(time(), 'daily', 'vs08v_solde_reminder_cron');
    }
});
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
    wp_clear_scheduled_hook('vs08v_solde_reminder_cron');
});
