<?php
/**
 * VS08 Checkout Fix — mu-plugin
 * 1. Augmente memory_limit pendant AJAX/REST
 * 2. Désactive les hooks Yoast SEO sur save_post pendant checkout
 * 3. Log les erreurs fatales pour débugger les 500
 */

// Détecter AJAX WC ou REST
$is_ajax = defined('DOING_AJAX') && DOING_AJAX;
$is_wc_ajax = isset($_GET['wc-ajax']) || (isset($_REQUEST['wc-ajax']));
$is_rest = (strpos($_SERVER['REQUEST_URI'] ?? '', '/wp-json/') !== false);

if ($is_ajax || $is_wc_ajax || $is_rest) {
    // Mémoire max
    @ini_set('memory_limit', '1024M');

    // Log les erreurs fatales
    register_shutdown_function(function() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            error_log('[VS08 FATAL] ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
        }
    });

    // Désactiver Yoast sur save_post — méthode agressive
    // On empêche Yoast d'enregistrer ses hooks en filtrant les options
    add_action('muplugins_loaded', function() {
        // Retirer le hook le plus tôt possible
        add_action('wp_loaded', function() {
            global $wp_filter;
            $targets = ['save_post', 'wp_insert_post', 'save_post_product', 'save_post_shop_order'];
            foreach ($targets as $tag) {
                if (empty($wp_filter[$tag])) continue;
                foreach ($wp_filter[$tag]->callbacks as $priority => &$hooks) {
                    foreach ($hooks as $key => $hook) {
                        $remove = false;
                        // Check par nom de clé
                        if (is_string($key) && (stripos($key, 'wpseo') !== false || stripos($key, 'yoast') !== false)) {
                            $remove = true;
                        }
                        // Check par classe
                        $fn = $hook['function'] ?? null;
                        if (is_array($fn) && isset($fn[0])) {
                            $cls = is_object($fn[0]) ? get_class($fn[0]) : (is_string($fn[0]) ? $fn[0] : '');
                            if (stripos($cls, 'WPSEO') !== false || stripos($cls, 'Yoast') !== false) {
                                $remove = true;
                            }
                        }
                        // Check par closure/string
                        if (is_string($fn) && (stripos($fn, 'wpseo') !== false || stripos($fn, 'yoast') !== false)) {
                            $remove = true;
                        }
                        if ($remove) {
                            unset($hooks[$key]);
                        }
                    }
                }
            }
        }, 9999); // Après que Yoast a tout enregistré
    });
}
