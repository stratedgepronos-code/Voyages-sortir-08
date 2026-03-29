<?php
/**
 * Polyfill compatibilité passerelles Paybox.
 *
 * Certaines versions/implémentations appellent la fonction legacy show_message()
 * (absente en contexte front/checkout). Sans polyfill cela peut provoquer un fatal
 * error pendant ?wc-ajax=checkout (500).
 */
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('show_message')) {
    /**
     * Compat legacy: retourne un message texte sans émettre de sortie HTML.
     * On évite tout echo ici pour ne pas casser la réponse JSON du checkout AJAX.
     *
     * @param mixed $message
     * @return string
     */
    function show_message($message = '') {
        if (is_scalar($message)) {
            return (string) $message;
        }
        if (is_object($message) && method_exists($message, '__toString')) {
            return (string) $message;
        }
        return '';
    }
}

