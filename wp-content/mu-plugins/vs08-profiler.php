<?php
/**
 * VS08 Checkout Profiler
 * 
 * Chronomètre chaque étape du checkout pour trouver ce qui est lent.
 * Résultats dans : wp-content/vs08-profiler.log
 * 
 * À SUPPRIMER après diagnostic.
 */
if (!defined('ABSPATH')) exit;

// S'active sur les requêtes WC AJAX + la page merci
$is_wc_ajax = !empty($_REQUEST['wc-ajax']);
$is_checkout = $is_wc_ajax && ($_REQUEST['wc-ajax'] === 'checkout');

// Toujours actif pour tracer le flux complet
$GLOBALS['vs08_profiler'] = [
    'start' => microtime(true),
    'steps' => [],
    'request' => $is_checkout ? 'CHECKOUT_AJAX' : ($is_wc_ajax ? 'WC_AJAX_' . $_REQUEST['wc-ajax'] : 'PAGE'),
];

function vs08_profile($label) {
    $elapsed = round((microtime(true) - $GLOBALS['vs08_profiler']['start']) * 1000);
    $mem = round(memory_get_usage() / 1024 / 1024, 1);
    $GLOBALS['vs08_profiler']['steps'][] = sprintf('[+%dms | %sMo] %s', $elapsed, $mem, $label);
}

function vs08_profile_dump() {
    $p = $GLOBALS['vs08_profiler'];
    $total = round((microtime(true) - $p['start']) * 1000);
    $peak = round(memory_get_peak_usage() / 1024 / 1024, 1);
    
    $log = "\n══════════════════════════════════════════════════\n";
    $log .= date('Y-m-d H:i:s') . ' — ' . $p['request'] . " — TOTAL: {$total}ms — Peak: {$peak}Mo\n";
    $log .= "URI: " . ($_SERVER['REQUEST_URI'] ?? '?') . "\n";
    $log .= "──────────────────────────────────────────────────\n";
    foreach ($p['steps'] as $step) {
        $log .= "  $step\n";
    }
    $log .= "══════════════════════════════════════════════════\n";
    
    @file_put_contents(WP_CONTENT_DIR . '/vs08-profiler.log', $log, FILE_APPEND | LOCK_EX);
}

// Profiler shutdown
register_shutdown_function('vs08_profile_dump');

// ── HOOKS DE PROFILING ──

// Plugins chargés
add_action('plugins_loaded', function() { vs08_profile('plugins_loaded'); }, PHP_INT_MAX);

// WooCommerce chargé
add_action('woocommerce_loaded', function() { vs08_profile('woocommerce_loaded'); }, PHP_INT_MAX);

// Init WordPress
add_action('init', function() { vs08_profile('init'); }, PHP_INT_MAX);

// Checkout : validation
add_action('woocommerce_checkout_process', function() { vs08_profile('checkout_process (validation)'); }, PHP_INT_MAX);

// Checkout : création commande
add_action('woocommerce_checkout_create_order', function($order) { 
    vs08_profile('checkout_create_order — début'); 
}, 1);

// Checkout : line items
add_action('woocommerce_checkout_create_order_line_item', function() { 
    vs08_profile('checkout_create_order_line_item'); 
}, PHP_INT_MAX, 0);

// Checkout : commande traitée
add_action('woocommerce_checkout_order_processed', function($order_id) { 
    vs08_profile("checkout_order_processed (order #$order_id)");
}, 1);
add_action('woocommerce_checkout_order_processed', function($order_id) { 
    vs08_profile("checkout_order_processed — FIN");
}, PHP_INT_MAX);

// Changement statut
add_action('woocommerce_order_status_changed', function($oid, $old, $new) { 
    vs08_profile("order_status_changed #$oid: $old → $new"); 
}, 1, 3);

// Payment complete
add_action('woocommerce_payment_complete', function($oid) { 
    vs08_profile("payment_complete #$oid"); 
}, 1);

// Emails dispatch
add_action('woocommerce_email', function() { vs08_profile('woocommerce_email (mailer init)'); }, 1);

// Page merci (thankyou)
add_action('woocommerce_thankyou', function($oid) { 
    vs08_profile("thankyou #$oid — début"); 
}, PHP_INT_MIN);
add_action('woocommerce_thankyou', function($oid) { 
    vs08_profile("thankyou #$oid — fin"); 
}, PHP_INT_MAX);

// Template redirect (page merci)
add_action('template_redirect', function() {
    if (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received')) {
        vs08_profile('template_redirect: order-received page');
    }
}, 1);

// save_post (détecte les boucles HPOS)
$GLOBALS['vs08_save_post_count'] = 0;
add_action('save_post', function($post_id, $post) {
    $GLOBALS['vs08_save_post_count']++;
    $n = $GLOBALS['vs08_save_post_count'];
    $type = $post->post_type ?? '?';
    if ($type === 'shop_order' || $n <= 3 || $n % 10 === 0) {
        vs08_profile("save_post #{$n} (post_id=$post_id, type=$type)");
    }
}, 1, 2);

// wp_mail (chaque envoi)
add_filter('wp_mail', function($args) {
    $to = is_array($args['to']) ? implode(',', $args['to']) : $args['to'];
    vs08_profile("wp_mail → $to — sujet: " . substr($args['subject'] ?? '', 0, 50));
    return $args;
}, 1);

// Action Scheduler
add_action('action_scheduler_begin_execute', function($action_id) {
    vs08_profile("Action Scheduler exécute action #$action_id");
}, 1);

// ── TRAÇAGE DU TROU NOIR : log TOUTE action quand la mémoire dépasse 30 Mo ──
// Pendant le checkout, quelque chose bouffe 1.4 Go entre save_post et shutdown.
// On intercepte TOUTES les actions WordPress pour trouver le coupable.
if ($is_checkout) {
    $GLOBALS['vs08_all_actions_count'] = 0;
    add_action('all', function() {
        $mem = memory_get_usage() / 1024 / 1024;
        $GLOBALS['vs08_all_actions_count']++;
        $tag = current_filter();
        
        // Toujours logger les actions WooCommerce checkout
        $is_wc = (strpos($tag, 'woocommerce_') === 0);
        $is_order = (strpos($tag, 'order') !== false || strpos($tag, 'save_post') === 0 || strpos($tag, 'transition_post') === 0);
        
        // Logger si : mémoire > 30 Mo OU action WC OU action order OU toutes les 500 actions
        if ($mem > 30 || $is_wc || $is_order || $GLOBALS['vs08_all_actions_count'] % 500 === 0) {
            vs08_profile("ACTION #{$GLOBALS['vs08_all_actions_count']}: {$tag}");
        }
        
        // SÉCURITÉ : si mémoire > 500 Mo → ARRÊTER et logger la stack trace
        if ($mem > 500 && !isset($GLOBALS['vs08_mem_dumped'])) {
            $GLOBALS['vs08_mem_dumped'] = true;
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
            $callers = [];
            foreach ($trace as $f) {
                if (isset($f['file'])) {
                    $callers[] = basename($f['file']) . ':' . ($f['line'] ?? '?') . ' → ' . ($f['function'] ?? '?');
                }
            }
            vs08_profile("ALERTE MÉMOIRE 500Mo+ — STACK TRACE :\n    " . implode("\n    ", $callers));
        }
    });
}

// Shutdown
add_action('shutdown', function() { vs08_profile('shutdown'); }, 1);
