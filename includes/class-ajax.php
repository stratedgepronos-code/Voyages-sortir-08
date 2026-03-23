<?php
/**
 * VS08 Voyages — Handlers AJAX (v3.0.0)
 *
 * Contient TOUS les handlers AJAX du plugin :
 * 1. vs08v_get_flight    → Recherche de vols via Duffel
 * 2. vs08v_calculate     → Calcul du devis en temps réel
 * 3. vs08v_get_services  → Options vol (bagages, sièges) avec retry auto
 * 4. vs08v_booking_submit → Soumission de réservation
 */

if (!defined('ABSPATH')) exit;


// ══════════════════════════════════════════════════════════════════
// 1. RECHERCHE DE VOLS (Duffel + SerpApi fusionnés)
// ══════════════════════════════════════════════════════════════════
// Appelé depuis :
//   - single-voyage.php (page produit) → retourne prix le moins cher
//   - booking-steps.php (tunnel résa)  → retourne liste complète des vols
// ══════════════════════════════════════════════════════════════════
add_action('wp_ajax_vs08v_get_flight',        'vs08v_ajax_get_flight');
add_action('wp_ajax_nopriv_vs08v_get_flight', 'vs08v_ajax_get_flight');

/**
 * Tagge chaque vol avec une clé source (duffel / serpapi).
 */
function vs08v_tag_flight_source($flights, $source) {
    if (empty($flights)) return [];
    foreach ($flights as &$f) {
        $f['source'] = $source;
    }
    unset($f);
    return $flights;
}

/**
 * Normalise un numéro de vol pour la déduplication (AT 639 = AT0639 = AT639).
 */
function vs08v_normalize_flight_number($fn) {
    if ($fn === null || $fn === '') return '';
    $fn = preg_replace('/\s+/', '', strtoupper((string) $fn));
    return $fn;
}

/**
 * Déduplique par airline_iata + vol normalisé + horaires (et retour si A/R). Garde le moins cher.
 * Évite les doublons quand le même vol est retourné avec "AT 639" / "AT0639" / "AT639".
 */
function vs08v_dedup_flights($flights) {
    $unique = [];
    foreach ($flights as $f) {
        $outbound = vs08v_normalize_flight_number($f['flight_number'] ?? '');
        $retour   = vs08v_normalize_flight_number($f['retour_flight'] ?? '');
        $key_parts = [
            strtoupper((string) ($f['airline_iata'] ?? '')),
            $outbound,
            trim((string) ($f['depart_time'] ?? '')),
        ];
        if (!empty($f['retour_flight'])) {
            $key_parts[] = $retour;
            $key_parts[] = trim((string) ($f['retour_depart'] ?? ''));
        }
        $key = implode('|', $key_parts);
        if (!isset($unique[$key]) || ($f['price_total'] < $unique[$key]['price_total'])) {
            $unique[$key] = $f;
        }
    }
    $out = array_values($unique);
    usort($out, function ($a, $b) {
        return (int) ($a['price_total'] <=> $b['price_total']);
    });
    $ref = !empty($out) ? ($out[0]['price_per_pax'] ?? 0) : 0;
    foreach ($out as &$o) {
        $o['delta_per_pax'] = round(($o['price_per_pax'] ?? 0) - $ref, 2);
        $o['is_reference']  = ($o['delta_per_pax'] === 0.0);
    }
    unset($o);
    return $out;
}

/**
 * Cache opportuniste prix vol / pers. (14 j) — remplacé si un visiteur trouve moins cher.
 */
function vs08v_maybe_update_vol_min_cache($voyage_id, $prix_per_pax) {
    $voyage_id = (int) $voyage_id;
    $prix      = round(floatval($prix_per_pax), 2);
    if ($voyage_id <= 0 || $prix <= 0) {
        return;
    }
    $cache_key  = '_vs08v_vol_min_cache';
    $existing   = get_post_meta($voyage_id, $cache_key, true);
    $old_prix   = isset($existing['prix']) ? floatval($existing['prix']) : 0;
    $old_ts     = isset($existing['ts']) ? intval($existing['ts']) : 0;
    $expires    = 14 * DAY_IN_SECONDS;
    $is_expired = (time() - $old_ts) > $expires;
    $is_cheaper = ($old_prix <= 0 || $prix < $old_prix);
    if ($is_expired || $is_cheaper) {
        update_post_meta($voyage_id, $cache_key, ['prix' => $prix, 'ts' => time()]);
    }
}

/**
 * Logique métier recherche vols — retourne ['success' => bool, 'data' => ...] pour AJAX et REST.
 * Fusionne Duffel + SerpApi, déduplique et trie par prix.
 * @param array $input Clé/valeurs (voyage_id, date, date_retour, aeroport, passengers, destination).
 */
function vs08v_get_flight_result($input = null) {
    $post = $input !== null ? $input : $_POST;
    $voyage_id   = intval(isset($post['voyage_id']) ? $post['voyage_id'] : 0);
    $date        = sanitize_text_field(isset($post['date']) ? $post['date'] : '');
    $date_retour = sanitize_text_field(isset($post['date_retour']) ? $post['date_retour'] : '');
    $aeroport    = strtoupper(sanitize_text_field(isset($post['aeroport']) ? $post['aeroport'] : ''));
    $passengers  = max(1, intval(isset($post['passengers']) ? $post['passengers'] : 1));
    $destination_override = strtoupper(sanitize_text_field(isset($post['destination']) ? $post['destination'] : ''));

    if (!$voyage_id || !$date || !$aeroport) {
        return ['success' => false, 'data' => 'Paramètres manquants (voyage_id, date, aeroport).'];
    }
    if (get_post_type($voyage_id) !== 'vs08_voyage') {
        return ['success' => false, 'data' => 'Voyage invalide.'];
    }
    if (!class_exists('VS08V_MetaBoxes')) {
        return ['success' => false, 'data' => 'Service indisponible.'];
    }

    $m         = VS08V_MetaBoxes::get($voyage_id);
    $iata_dest = strtoupper(isset($m['iata_dest']) ? $m['iata_dest'] : '');
    if (empty($iata_dest)) {
        return ['success' => false, 'data' => 'Code IATA destination non configuré pour ce voyage.'];
    }

    $origin       = $aeroport;
    $destination  = !empty($destination_override) ? $destination_override : $iata_dest;
    $prix_vol_base = floatval(isset($m['prix_vol_base']) ? $m['prix_vol_base'] : 0);

    if (!class_exists('VS08_Duffel_API')) {
        if ($prix_vol_base > 0) {
            vs08v_maybe_update_vol_min_cache($voyage_id, $prix_vol_base);
            return ['success' => true, 'data' => ['prix' => $prix_vol_base, 'note' => 'estimate', 'flights' => []]];
        }
        return ['success' => false, 'data' => 'Service vols indisponible. Réessayez plus tard.'];
    }

    $all_flights = [];

    // ── Duffel ──
    try {
        $duffel_result = VS08_Duffel_API::search_flights($origin, $destination, $date, $passengers, $date_retour);
        if (!is_wp_error($duffel_result) && !empty($duffel_result['flights'])) {
            $all_flights = array_merge($all_flights, vs08v_tag_flight_source($duffel_result['flights'], 'duffel'));
        }
    } catch (\Throwable $e) {
        if (function_exists('error_log')) {
            error_log('[VS08 get_flight Duffel] ' . $e->getMessage());
        }
    }

    // ── SerpApi (Ryanair / low-cost) ──
    if (class_exists('VS08_SerpApi') && defined('VS08_SERPAPI_API_KEY') && VS08_SERPAPI_API_KEY !== '') {
        try {
            $serpapi_result = VS08_SerpApi::search_flights($origin, $destination, $date, $passengers, $date_retour);
            if (!is_wp_error($serpapi_result) && !empty($serpapi_result['flights'])) {
                $all_flights = array_merge($all_flights, $serpapi_result['flights']);
            }
        } catch (\Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[VS08 get_flight SerpApi] ' . $e->getMessage());
            }
        }
    }

    $flights = vs08v_dedup_flights($all_flights);

    if (empty($flights)) {
        if ($prix_vol_base > 0) {
            vs08v_maybe_update_vol_min_cache($voyage_id, $prix_vol_base);
            return ['success' => true, 'data' => ['prix' => $prix_vol_base, 'note' => 'estimate', 'flights' => []]];
        }
        return ['success' => false, 'data' => 'Aucun vol direct trouvé pour cette date / cet aéroport.'];
    }

    $prix = $flights[0]['price_per_pax'] ?? 0;
    $ref  = $flights[0]['price_per_pax'] ?? $prix;
    if ($prix > 0) {
        vs08v_maybe_update_vol_min_cache($voyage_id, $prix);
    }

    return [
        'success' => true,
        'data'    => [
            'prix'              => round($prix, 2),
            'ref_price_per_pax' => round($ref, 2),
            'note'              => 'realtime',
            'is_roundtrip'      => !empty($date_retour),
            'flights'           => $flights,
        ],
    ];
}

function vs08v_ajax_get_flight() {
    while (ob_get_level()) { ob_end_clean(); }
    @header('Content-Type: application/json; charset=' . get_option('blog_charset'));
    try {
        if (!check_ajax_referer('vs08v_nonce', 'nonce', false)) {
            wp_send_json_error('Session expirée. Rechargez la page.');
            return;
        }
        $r = vs08v_get_flight_result($_POST);
        if ($r['success']) {
            wp_send_json_success($r['data']);
        } else {
            wp_send_json_error($r['data']);
        }
    } catch (\Throwable $e) {
        if (function_exists('error_log')) {
            error_log('[VS08 get_flight] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }
        wp_send_json_error('Erreur serveur lors de la recherche de vols. Réessayez ou contactez-nous.');
    }
}


// ══════════════════════════════════════════════════════════════════
// 2. CALCUL DU DEVIS EN TEMPS RÉEL
// ══════════════════════════════════════════════════════════════════
// Appelé depuis single-voyage.php quand l'utilisateur modifie
// ses paramètres (date, nb golfeurs, chambre, etc.)
// ══════════════════════════════════════════════════════════════════
add_action('wp_ajax_vs08v_calculate',        'vs08v_ajax_calculate');
add_action('wp_ajax_nopriv_vs08v_calculate', 'vs08v_ajax_calculate');

/**
 * Logique métier calcul devis — retourne ['success' => bool, 'data' => ...] pour AJAX et REST.
 */
function vs08v_calculate_result($input = null) {
    $post = $input !== null ? $input : $_POST;
    $voyage_id = intval(isset($post['voyage_id']) ? $post['voyage_id'] : 0);
    if (!$voyage_id || get_post_type($voyage_id) !== 'vs08_voyage') {
        return ['success' => false, 'data' => 'Voyage introuvable.'];
    }
    $params = [
        'date_depart'    => sanitize_text_field(isset($post['date_depart']) ? $post['date_depart'] : ''),
        'aeroport'       => sanitize_text_field(isset($post['aeroport']) ? $post['aeroport'] : ''),
        'nb_golfeurs'    => intval(isset($post['nb_golfeurs']) ? $post['nb_golfeurs'] : 1),
        'nb_nongolfeurs' => intval(isset($post['nb_nongolfeurs']) ? $post['nb_nongolfeurs'] : 0),
        'type_chambre'   => sanitize_text_field(isset($post['type_chambre']) ? $post['type_chambre'] : 'double'),
        'nb_chambres'    => intval(isset($post['nb_chambres']) ? $post['nb_chambres'] : 1),
        'prix_vol'       => floatval(isset($post['prix_vol']) ? $post['prix_vol'] : 0),
        'rooms'          => isset($post['rooms']) ? $post['rooms'] : '',
        'airline_iata'   => sanitize_text_field(isset($post['airline_iata']) ? $post['airline_iata'] : ''),
    ];
    try {
        $devis = VS08V_Calculator::calculate($voyage_id, $params);
        $pv    = floatval($params['prix_vol'] ?? 0);
        if ($pv > 0) {
            vs08v_maybe_update_vol_min_cache($voyage_id, $pv);
        }
        return ['success' => true, 'data' => $devis];
    } catch (\Throwable $e) {
        if (function_exists('error_log')) error_log('[VS08 calculate] ' . $e->getMessage());
        return ['success' => false, 'data' => 'Erreur lors du calcul. Réessayez.'];
    }
}

function vs08v_ajax_calculate() {
    if (!check_ajax_referer('vs08v_nonce', 'nonce', false)) {
        wp_send_json_error('Session expirée. Rechargez la page.');
        return;
    }
    $r = vs08v_calculate_result($_POST);
    if ($r['success']) {
        wp_send_json_success($r['data']);
    } else {
        wp_send_json_error($r['data']);
    }
}


// ══════════════════════════════════════════════════════════════════
// 3. SERVICES VOL — Bagages & Sièges (Duffel) avec RETRY AUTO
// ══════════════════════════════════════════════════════════════════
// Appelé depuis booking-steps.php quand l'utilisateur sélectionne
// un vol. Si l'offer_id est expiré (~30 min), le système relance
// automatiquement une recherche fraîche pour obtenir un nouvel offer_id.
// ══════════════════════════════════════════════════════════════════
add_action('wp_ajax_vs08v_get_services',        'vs08v_ajax_get_services');
add_action('wp_ajax_nopriv_vs08v_get_services', 'vs08v_ajax_get_services');

function vs08v_ajax_get_services() {
    if (!check_ajax_referer('vs08v_nonce', 'nonce', false)) {
        wp_send_json_error('Session expirée.');
        return;
    }
    try {
        $airline_iata = strtoupper(sanitize_text_field($_POST['airline_iata'] ?? ''));

        if (empty($airline_iata)) {
            wp_send_json_success(['options' => [], 'unavailable' => true]);
            return;
        }

        $all = get_option('vs08_airline_services', []);

        if (empty($all[$airline_iata]['options'])) {
            wp_send_json_success(['options' => [], 'unavailable' => true]);
            return;
        }

        wp_send_json_success([
            'options'      => $all[$airline_iata]['options'],
            'airline_name' => $all[$airline_iata]['label'] ?? $airline_iata,
            'unavailable'  => false,
        ]);
    } catch (\Throwable $e) {
        if (function_exists('error_log')) {
            error_log('[VS08 get_services] ' . $e->getMessage());
        }
        wp_send_json_success(['options' => [], 'unavailable' => true]);
    }
}


// ══════════════════════════════════════════════════════════════════
// 4. SOUMISSION DE RÉSERVATION
// ══════════════════════════════════════════════════════════════════
// Appelé depuis booking-steps.php (étape finale)
// ══════════════════════════════════════════════════════════════════
add_action('wp_ajax_vs08v_booking_submit',        'vs08v_ajax_booking_submit');
add_action('wp_ajax_nopriv_vs08v_booking_submit', 'vs08v_ajax_booking_submit');

function vs08v_ajax_booking_submit() {
    if (!check_ajax_referer('vs08v_nonce', 'nonce', false)) {
        wp_send_json_error('Session expirée. Rechargez la page et réessayez.');
        return;
    }
    try {
        $result = VS08V_Booking::process_submission();

        if (!empty($result['error'])) {
            wp_send_json_error($result['error']);
            return;
        }

        wp_send_json_success($result);
    } catch (\Throwable $e) {
        if (function_exists('error_log')) {
            error_log('[VS08 booking_submit] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }
        wp_send_json_error('Erreur lors de l\'envoi. Réessayez ou contactez-nous.');
    }
}
