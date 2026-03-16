<?php
if (!defined('ABSPATH')) exit;

/* ── 1. Calcul du devis en temps réel ── */
add_action('wp_ajax_vs08c_calculate',        'vs08c_ajax_calculate');
add_action('wp_ajax_nopriv_vs08c_calculate', 'vs08c_ajax_calculate');

function vs08c_ajax_calculate() {
    check_ajax_referer('vs08c_nonce', 'nonce');

    $circuit_id = intval($_POST['circuit_id'] ?? 0);
    if (!$circuit_id) wp_send_json_error('Circuit ID manquant.');

    $params = [
        'nb_adultes'  => intval($_POST['nb_adultes'] ?? 2),
        'nb_enfants'  => intval($_POST['nb_enfants'] ?? 0),
        'nb_chambres' => intval($_POST['nb_chambres'] ?? 1),
        'date_depart' => sanitize_text_field($_POST['date_depart'] ?? ''),
        'aeroport'    => strtoupper(sanitize_text_field($_POST['aeroport'] ?? '')),
        'prix_vol'    => floatval($_POST['prix_vol'] ?? 0),
        'rooms'       => $_POST['rooms'] ?? '',
    ];

    $devis = VS08C_Calculator::calculate($circuit_id, $params);
    wp_send_json_success($devis);
}

/* ── 2. Soumission de réservation ── */
add_action('wp_ajax_vs08c_booking_submit',        'vs08c_ajax_booking_submit');
add_action('wp_ajax_nopriv_vs08c_booking_submit', 'vs08c_ajax_booking_submit');

function vs08c_ajax_booking_submit() {
    check_ajax_referer('vs08c_nonce', 'nonce');

    $result = VS08C_Booking::process_submission();

    if (isset($result['error'])) {
        wp_send_json_error($result['error']);
    }

    wp_send_json_success($result);
}

/* ── 3. Recherche de vols (réutilise vs08-voyages si disponible) ── */
add_action('wp_ajax_vs08c_get_flight',        'vs08c_ajax_get_flight');
add_action('wp_ajax_nopriv_vs08c_get_flight', 'vs08c_ajax_get_flight');

function vs08c_ajax_get_flight() {
    // Réutiliser le handler de vs08-voyages s'il existe
    if (function_exists('vs08v_ajax_get_flight')) {
        vs08v_ajax_get_flight();
        return;
    }

    // Sinon, fallback minimal
    check_ajax_referer('vs08c_nonce', 'nonce');
    wp_send_json_error('Recherche de vols non disponible. Installez le plugin VS08 Voyages pour activer cette fonctionnalité.');
}
