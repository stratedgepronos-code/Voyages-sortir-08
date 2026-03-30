<?php
/**
 * VS08 Checkout Profiler v3 — écriture temps réel
 * Écrit chaque étape immédiatement dans le log (pas au shutdown).
 * Même si PHP crash, on a les données jusqu'au crash.
 */
if (!defined('ABSPATH')) exit;

$is_checkout = !empty($_REQUEST['wc-ajax']) && $_REQUEST['wc-ajax'] === 'checkout';
if (!$is_checkout) return; // Ne tracer QUE le checkout

$GLOBALS['vs08p_start'] = microtime(true);
$GLOBALS['vs08p_file'] = WP_CONTENT_DIR . '/vs08-profiler.log';
$GLOBALS['vs08p_n'] = 0;
$GLOBALS['vs08p_mem_alert'] = false;

// Écrire le header
@file_put_contents($GLOBALS['vs08p_file'],
    "\n══ CHECKOUT " . date('H:i:s') . " ══════════════════════════════\n",
    FILE_APPEND | LOCK_EX
);

function vs08p($label) {
    $ms = round((microtime(true) - $GLOBALS['vs08p_start']) * 1000);
    $mem = round(memory_get_usage() / 1024 / 1024, 1);
    $line = sprintf("  [+%dms | %sMo] %s\n", $ms, $mem, $label);
    @file_put_contents($GLOBALS['vs08p_file'], $line, FILE_APPEND | LOCK_EX);
}

// Hook sur TOUTES les actions — écriture immédiate
add_action('all', function() {
    $GLOBALS['vs08p_n']++;
    $n = $GLOBALS['vs08p_n'];
    $tag = current_filter();
    $mem = memory_get_usage() / 1024 / 1024;

    // Logger : actions WC/order + mémoire > 25 Mo + toutes les 200 actions
    $dominated = (strpos($tag, 'woocommerce_') === 0)
              || (strpos($tag, 'save_post') === 0)
              || (strpos($tag, 'transition_post') === 0)
              || (strpos($tag, 'wp_mail') === 0)
              || (strpos($tag, 'order') !== false)
              || ($mem > 25)
              || ($n % 200 === 0);

    if ($dominated) {
        vs08p("#{$n} {$tag}");
    }

    // ALERTE mémoire > 100 Mo : stack trace
    if ($mem > 100 && !$GLOBALS['vs08p_mem_alert']) {
        $GLOBALS['vs08p_mem_alert'] = true;
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);
        $callers = [];
        foreach ($trace as $f) {
            if (isset($f['file'])) {
                $callers[] = basename($f['file']) . ':' . ($f['line'] ?? '?') . ' → ' . ($f['function'] ?? '?');
            }
        }
        vs08p("🚨 ALERTE MÉMOIRE " . round($mem) . "Mo — STACK:\n    " . implode("\n    ", $callers));
    }
});

register_shutdown_function(function() {
    $ms = round((microtime(true) - $GLOBALS['vs08p_start']) * 1000);
    $peak = round(memory_get_peak_usage() / 1024 / 1024, 1);
    vs08p("SHUTDOWN — total {$ms}ms — peak {$peak}Mo — {$GLOBALS['vs08p_n']} actions");
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR])) {
        vs08p("FATAL ERROR: {$err['message']} in {$err['file']}:{$err['line']}");
    }
});
