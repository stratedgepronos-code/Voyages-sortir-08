<?php
// Empêcher Yoast SEO de se charger pendant le checkout WooCommerce.
// Yoast hook save_post → boucle infinie → 2 Go RAM → crash.
// Ne touche PAS à memory_limit. Ne bloque AUCUN autre plugin.
if (!empty($_GET['wc-ajax']) || !empty($_REQUEST['wc-ajax'])) {
    add_filter('option_active_plugins', function($plugins) {
        if (!is_array($plugins)) return $plugins;
        return array_values(array_filter($plugins, function($p) {
            return stripos($p, 'wordpress-seo') === false;
        }));
    }, 0);
}
