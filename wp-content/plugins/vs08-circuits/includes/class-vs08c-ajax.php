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
        'options'     => $_POST['options'] ?? '',
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

/* ── 3. Recherche de vols (Duffel + SerpApi, comme séjours golf) ── */
add_action('wp_ajax_vs08c_get_flight',        'vs08c_ajax_get_flight');
add_action('wp_ajax_nopriv_vs08c_get_flight', 'vs08c_ajax_get_flight');

function vs08c_ajax_get_flight() {
    while (ob_get_level()) { ob_end_clean(); }
    @header('Content-Type: application/json; charset=' . get_option('blog_charset'));
    try {
        if (!check_ajax_referer('vs08c_nonce', 'nonce', false)) {
            wp_send_json_error('Session expirée. Rechargez la page.');
            return;
        }
        $circuit_id = intval($_POST['circuit_id'] ?? 0);
        $date       = sanitize_text_field($_POST['date'] ?? '');
        $aeroport   = strtoupper(sanitize_text_field($_POST['aeroport'] ?? ''));
        $passengers = max(1, intval($_POST['passengers'] ?? 1));

        if (!$circuit_id || !$date || !$aeroport) {
            wp_send_json_error('Paramètres manquants (date, aéroport).');
            return;
        }
        if (get_post_type($circuit_id) !== 'vs08_circuit') {
            wp_send_json_error('Circuit invalide.');
            return;
        }

        $m          = VS08C_Meta::get($circuit_id);
        $iata_dest  = strtoupper(sanitize_text_field($m['iata_dest'] ?? ''));
        $duree_j    = max(1, intval($m['duree_jours'] ?? 8));
        $prix_base  = floatval($m['prix_vol_base'] ?? 0);

        if (empty($iata_dest)) {
            if ($prix_base > 0) {
                wp_send_json_success(['prix' => $prix_base, 'note' => 'estimate', 'flights' => []]);
                return;
            }
            wp_send_json_error('Code IATA destination non configuré pour ce circuit.');
            return;
        }

        $origin      = $aeroport;
        $destination = $iata_dest;
        $date_retour = date('Y-m-d', strtotime($date . ' +' . $duree_j . ' days'));

        if (!class_exists('VS08_Duffel_API')) {
            if ($prix_base > 0) {
                wp_send_json_success(['prix' => $prix_base, 'note' => 'estimate', 'flights' => []]);
                return;
            }
            wp_send_json_error('Recherche de vols temporairement indisponible. Utilisez le prix estimé ou réessayez plus tard.');
            return;
        }

        $all_flights = [];

        // ── Duffel ──
        try {
            $duffel_result = VS08_Duffel_API::search_flights($origin, $destination, $date, $passengers, $date_retour);
            if (!is_wp_error($duffel_result) && !empty($duffel_result['flights'])) {
                $tagged = function_exists('vs08v_tag_flight_source')
                    ? vs08v_tag_flight_source($duffel_result['flights'], 'duffel')
                    : $duffel_result['flights'];
                $all_flights = array_merge($all_flights, $tagged);
            }
        } catch (\Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[VS08c get_flight Duffel] ' . $e->getMessage());
            }
        }

        // ── SerpApi (Ryanair / low-cost, comme produit golf) ──
        if (class_exists('VS08_SerpApi') && defined('VS08_SERPAPI_API_KEY') && VS08_SERPAPI_API_KEY !== '') {
            try {
                $serpapi_result = VS08_SerpApi::search_flights($origin, $destination, $date, $passengers, $date_retour);
                if (!is_wp_error($serpapi_result) && !empty($serpapi_result['flights'])) {
                    $all_flights = array_merge($all_flights, $serpapi_result['flights']);
                }
            } catch (\Throwable $e) {
                if (function_exists('error_log')) {
                    error_log('[VS08c get_flight SerpApi] ' . $e->getMessage());
                }
            }
        }

        // Dédupliquer et trier par prix (même logique que vs08v_get_flight_result)
        $flights = function_exists('vs08v_dedup_flights') ? vs08v_dedup_flights($all_flights) : vs08c_dedup_flights_fallback($all_flights);

        if (empty($flights)) {
            if ($prix_base > 0) {
                wp_send_json_success(['prix' => $prix_base, 'note' => 'estimate', 'flights' => []]);
                return;
            }
            wp_send_json_error('Aucun vol direct trouvé pour cette date / cet aéroport.');
            return;
        }

        $prix = $flights[0]['price_per_pax'] ?? $prix_base;
        wp_send_json_success([
            'prix'    => round((float) $prix, 2),
            'note'    => 'realtime',
            'flights' => $flights,
        ]);
    } catch (\Throwable $e) {
        if (function_exists('error_log')) {
            error_log('[VS08c get_flight] ' . $e->getMessage());
        }
        wp_send_json_error('Erreur lors de la recherche de vols. Réessayez.');
    }
}

/**
 * Fallback déduplication si le plugin Voyages n’est pas chargé (même logique que vs08v_dedup_flights).
 */
function vs08c_dedup_flights_fallback($flights) {
    $unique = [];
    foreach ($flights as $f) {
        $key_parts = [
            $f['airline_iata'] ?? '',
            $f['flight_number'] ?? '',
            $f['depart_time'] ?? '',
        ];
        if (!empty($f['retour_flight'])) {
            $key_parts[] = $f['retour_flight'];
            $key_parts[] = $f['retour_depart'] ?? '';
        }
        $key = implode('|', $key_parts);
        if (!isset($unique[$key]) || (isset($f['price_total']) && isset($unique[$key]['price_total']) && $f['price_total'] < $unique[$key]['price_total'])) {
            $unique[$key] = $f;
        }
    }
    $out = array_values($unique);
    usort($out, function ($a, $b) {
        return (int) (($a['price_total'] ?? 0) <=> ($b['price_total'] ?? 0));
    });
    $ref = !empty($out) ? ($out[0]['price_per_pax'] ?? 0) : 0;
    foreach ($out as &$o) {
        $o['delta_per_pax'] = round(($o['price_per_pax'] ?? 0) - $ref, 2);
        $o['is_reference']  = (($o['delta_per_pax'] ?? 0) === 0.0);
    }
    unset($o);
    return $out;
}
