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
        'nb_enfants'  => 0,
        'nb_chambres' => intval($_POST['nb_chambres'] ?? 1),
        'date_depart' => sanitize_text_field($_POST['date_depart'] ?? ''),
        'aeroport'    => strtoupper(sanitize_text_field($_POST['aeroport'] ?? '')),
        'prix_vol'    => floatval($_POST['prix_vol'] ?? 0),
        'rooms'       => $_POST['rooms'] ?? '',
        'options'     => isset($_POST['options']) ? wp_unslash($_POST['options']) : '',
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
    if (isset($result['error'])) wp_send_json_error($result['error']);
    wp_send_json_success($result);
}

/* ══════════════════════════════════════════════════════════════
   3. RECHERCHE DE VOLS CIRCUITS — Duffel + SerpAPI
   Même logique que vs08v_get_flight mais pour les circuits.
   Le JS front envoie: circuit_id, date, aeroport, passengers.
   On calcule la date retour = date + durée nuits du circuit.
   On cherche le vol A/R le moins cher sur Duffel puis SerpAPI.
   ══════════════════════════════════════════════════════════════ */
add_action('wp_ajax_vs08c_get_flight',        'vs08c_ajax_get_flight');
add_action('wp_ajax_nopriv_vs08c_get_flight', 'vs08c_ajax_get_flight');

function vs08c_ajax_get_flight() {
    check_ajax_referer('vs08c_nonce', 'nonce');

    $circuit_id = intval($_POST['circuit_id'] ?? 0);
    $date       = sanitize_text_field($_POST['date'] ?? '');
    $aeroport   = strtoupper(sanitize_text_field($_POST['aeroport'] ?? ''));
    $passengers = max(1, intval($_POST['passengers'] ?? 1));

    if (!$circuit_id || !$date || !$aeroport) {
        wp_send_json_error('Paramètres manquants.');
    }

    $m             = VS08C_Meta::get($circuit_id);
    $iata_dest     = strtoupper($m['iata_dest'] ?? '');
    $duree         = intval($m['duree'] ?? 7);
    $prix_vol_base = floatval($m['prix_vol_base'] ?? 0);

    // Si pas de code IATA destination → fallback prix de base
    if (empty($iata_dest)) {
        if ($prix_vol_base > 0) {
            wp_send_json_success(['prix' => $prix_vol_base, 'note' => 'estimate', 'flights' => []]);
        }
        wp_send_json_error('Code IATA destination non configuré.');
    }

    // Calculer date retour = date aller + nombre de nuits
    $date_retour = sanitize_text_field($_POST['date_retour'] ?? '');
    if (empty($date_retour) && $date) {
        $ts = strtotime($date);
        if ($ts) $date_retour = date('Y-m-d', strtotime('+' . $duree . ' days', $ts));
    }

    $origin      = $aeroport;
    $destination_override = strtoupper(sanitize_text_field($_POST['destination'] ?? ''));
    $destination = !empty($destination_override) ? $destination_override : $iata_dest;

    // Vérifier que Duffel est disponible (chargé par le plugin vs08-voyages)
    if (!class_exists('VS08_Duffel_API')) {
        if ($prix_vol_base > 0) {
            wp_send_json_success(['prix' => $prix_vol_base, 'note' => 'estimate', 'flights' => []]);
        }
        wp_send_json_error('Service vols indisponible (Duffel non chargé).');
    }

    $all_flights = [];

    // ── Recherche Duffel ──
    try {
        $duffel_result = VS08_Duffel_API::search_flights($origin, $destination, $date, $passengers, $date_retour);
        if (!is_wp_error($duffel_result) && !empty($duffel_result['flights'])) {
            foreach ($duffel_result['flights'] as &$f) { $f['source'] = 'duffel'; }
            unset($f);
            $all_flights = array_merge($all_flights, $duffel_result['flights']);
        }
    } catch (\Throwable $e) {
        error_log('[VS08C Duffel] ' . $e->getMessage());
    }

    // ── Recherche SerpAPI (Ryanair, low-cost via Google Flights) ──
    if (class_exists('VS08V_SerpApi')) {
        try {
            $serp_result = VS08V_SerpApi::search_flights($origin, $destination, $date, $date_retour, $passengers);
            if (!is_wp_error($serp_result) && !empty($serp_result['flights'])) {
                foreach ($serp_result['flights'] as &$f) { $f['source'] = 'serpapi'; }
                unset($f);
                $all_flights = array_merge($all_flights, $serp_result['flights']);
            }
        } catch (\Throwable $e) {
            error_log('[VS08C SerpApi] ' . $e->getMessage());
        }
    }

    // ── Dédupliquer et trier (utilise la fonction du plugin golf si dispo) ──
    if (function_exists('vs08v_dedup_flights')) {
        $all_flights = vs08v_dedup_flights($all_flights);
    } else {
        usort($all_flights, function($a, $b) {
            return ($a['price_per_pax'] ?? 9999) <=> ($b['price_per_pax'] ?? 9999);
        });
    }

    // ── Résultat ──
    if (empty($all_flights)) {
        if ($prix_vol_base > 0) {
            wp_send_json_success(['prix' => $prix_vol_base, 'note' => 'estimate', 'flights' => []]);
        }
        wp_send_json_error('Aucun vol trouvé pour cette date.');
    }

    $cheapest = $all_flights[0];
    $prix     = floatval($cheapest['price_per_pax'] ?? $prix_vol_base);

    wp_send_json_success([
        'prix'    => $prix,
        'note'    => 'live',
        'flights' => $all_flights,
    ]);
}
