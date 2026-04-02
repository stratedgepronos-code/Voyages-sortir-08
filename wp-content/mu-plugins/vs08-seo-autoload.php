<?php
/**
 * VS08 SEO — Auto-chargement
 * Force le chargement du plugin vs08-seo même s'il n'est pas activé manuellement.
 * Les mu-plugins sont toujours chargés par WordPress avant les plugins normaux.
 */
if (!defined('ABSPATH')) exit;

$vs08_seo_main = WP_CONTENT_DIR . '/plugins/vs08-seo/vs08-seo.php';
if (file_exists($vs08_seo_main)) {
    require_once $vs08_seo_main;
}
