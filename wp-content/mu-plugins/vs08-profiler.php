<?php
/**
 * VS08 Checkout Profiler v4 — fichier léger, écriture temps réel
 * Produit UNIQUEMENT : wp-content/vs08-profiler-summary.log (petit fichier)
 */
if (!defined('ABSPATH')) exit;

$is_checkout = !empty($_REQUEST['wc-ajax']) && $_REQUEST['wc-ajax'] === 'checkout';
if (!$is_checkout) return;

$GLOBALS['vs08p'] = [
    'start' => microtime(true),
    'file'  => WP_CONTENT_DIR . '/vs08-profiler-summary.log',
    'n'     => 0,
    'alert' => false,
    'last_mem' => 0,
];

function vs08pw($label) {
    $ms = round((microtime(true) - $GLOBALS['vs08p']['start']) * 1000);
    $mem = round(memory_get_usage() / 1024 / 1024, 1);
    @file_put_contents($GLOBALS['vs08p']['file'],
        sprintf("[+%dms | %sMo] %s\n", $ms, $mem, $label),
        FILE_APPEND | LOCK_EX
    );
}

// Header
@file_put_contents($GLOBALS['vs08p']['file'],
    "\n══ CHECKOUT " . date('Y-m-d H:i:s') . " ══\n"
);

// Hook sur TOUTES les actions — mais n'écrit QUE les événements critiques
add_action('all', function() {
    $GLOBALS['vs08p']['n']++;
    $tag = current_filter();
    $mem_mo = memory_get_usage() / 1024 / 1024;
    $n = $GLOBALS['vs08p']['n'];

    // 1. Actions WooCommerce clés → toujours logger
    $key_actions = [
        'woocommerce_checkout_process', 'woocommerce_checkout_create_order',
        'woocommerce_checkout_create_order_line_item',
        'woocommerce_checkout_update_order_meta', 'woocommerce_checkout_order_processed',
        'woocommerce_new_order', 'woocommerce_payment_complete',
        'woocommerce_order_status_changed', 'woocommerce_email',
        'woocommerce_before_checkout_process',
    ];
    if (in_array($tag, $key_actions)) {
        vs08pw("#{$n} {$tag}");
        return;
    }

    // 2. save_post / transition → toujours logger
    if (strpos($tag, 'save_post') === 0 || strpos($tag, 'transition_post') === 0) {
        vs08pw("#{$n} {$tag}");
        return;
    }

    // 3. wp_mail → toujours logger
    if ($tag === 'wp_mail') {
        vs08pw("#{$n} wp_mail");
        return;
    }

    // 4. Mémoire > 25 Mo → logger + première fois > 50 Mo stack trace
    if ($mem_mo > 25 && ($mem_mo - $GLOBALS['vs08p']['last_mem']) > 5) {
        $GLOBALS['vs08p']['last_mem'] = $mem_mo;
        vs08pw("#{$n} {$tag} [MEM SPIKE]");
    }

    if ($mem_mo > 50 && !$GLOBALS['vs08p']['alert']) {
        $GLOBALS['vs08p']['alert'] = true;
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);
        $stack = [];
        foreach ($trace as $f) {
            if (isset($f['file'])) {
                $stack[] = basename($f['file']) . ':' . ($f['line'] ?? '?') . ' → ' . ($f['function'] ?? '?');
            }
        }
        vs08pw("🚨 MÉMOIRE " . round($mem_mo) . "Mo — STACK:\n    " . implode("\n    ", $stack));
    }

    // 5. Compteur toutes les 500 actions (progression)
    if ($n % 500 === 0) {
        vs08pw("... action #{$n} ({$tag}) — " . round($mem_mo) . "Mo");
    }
});

register_shutdown_function(function() {
    $ms = round((microtime(true) - $GLOBALS['vs08p']['start']) * 1000);
    $peak = round(memory_get_peak_usage() / 1024 / 1024, 1);
    vs08pw("═══ FIN — {$ms}ms — peak {$peak}Mo — {$GLOBALS['vs08p']['n']} actions ═══");
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR])) {
        vs08pw("💀 FATAL: {$err['message']}\n    in {$err['file']}:{$err['line']}");
    }
});
