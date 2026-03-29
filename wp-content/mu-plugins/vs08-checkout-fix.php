<?php
/**
 * VS08 Checkout Fix — mu-plugin
 * Mémoire + log erreurs fatales pour AJAX/REST.
 * La désactivation des plugins lourds est dans vs08-disable-yoast-checkout.php.
 */
if (
    (defined('DOING_AJAX') && DOING_AJAX) ||
    isset($_GET['wc-ajax']) || isset($_REQUEST['wc-ajax']) ||
    (strpos($_SERVER['REQUEST_URI'] ?? '', '/wp-json/') !== false)
) {
    @ini_set('memory_limit', '1536M');
}
