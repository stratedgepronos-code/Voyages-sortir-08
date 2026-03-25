<?php
if (!defined('ABSPATH')) exit;

/**
 * Cache opportuniste prix vol / pers. pour circuits (14 j) — même principe que les séjours golf.
 */
function vs08c_maybe_update_vol_min_cache($circuit_id, $prix_per_pax) {
    $circuit_id = (int) $circuit_id;
    $prix       = round(floatval($prix_per_pax), 2);
    if ($circuit_id <= 0 || $prix <= 0) {
        return;
    }
    $cache_key  = '_vs08c_vol_min_cache';
    $existing   = get_post_meta($circuit_id, $cache_key, true);
    $old_prix   = isset($existing['prix']) ? floatval($existing['prix']) : 0;
    $old_ts     = isset($existing['ts']) ? intval($existing['ts']) : 0;
    $expires    = 14 * DAY_IN_SECONDS;
    $is_expired = (time() - $old_ts) > $expires;
    $is_cheaper = ($old_prix <= 0 || $prix < $old_prix);
    if ($is_expired || $is_cheaper) {
        update_post_meta($circuit_id, $cache_key, ['prix' => $prix, 'ts' => time()]);
    }
}

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
    $pv    = floatval($params['prix_vol'] ?? 0);
    if ($pv > 0) {
        vs08c_maybe_update_vol_min_cache($circuit_id, $pv);
    }
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
            vs08c_maybe_update_vol_min_cache($circuit_id, $prix_vol_base);
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

    $flight_opts = [];
    if (!empty($m['vol_open_jaw']) && !empty($m['iata_retour_depart'])) {
        $flight_opts['return_origin'] = strtoupper(sanitize_text_field($m['iata_retour_depart']));
    }
    if (!empty($m['vol_escales_autorisees'])) {
        $flight_opts['max_connections'] = 1;
        $h = isset($m['vol_escale_max_heures']) ? floatval($m['vol_escale_max_heures']) : 5;
        if ($h <= 0) {
            $h = 5;
        }
        $flight_opts['max_layover_minutes'] = max(60, (int) round($h * 60));
    }

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
        $duffel_result = VS08_Duffel_API::search_flights($origin, $destination, $date, $passengers, $date_retour, $flight_opts);
        if (!is_wp_error($duffel_result) && !empty($duffel_result['flights'])) {
            foreach ($duffel_result['flights'] as &$f) { $f['source'] = 'duffel'; }
            unset($f);
            $all_flights = array_merge($all_flights, $duffel_result['flights']);
        }
    } catch (\Throwable $e) {
        error_log('[VS08C Duffel] ' . $e->getMessage());
    }

    // ── Recherche SerpAPI (Ryanair, low-cost via Google Flights) ──
    // Charger la classe depuis vs08-voyages si pas encore chargée (ordre des plugins)
    if (!class_exists('VS08_SerpApi') && defined('VS08V_PATH') && file_exists(VS08V_PATH . 'includes/class-serpapi.php')) {
        require_once VS08V_PATH . 'includes/class-serpapi.php';
    }
    $serp_ok = class_exists('VS08_SerpApi') && defined('VS08_SERPAPI_API_KEY') && VS08_SERPAPI_API_KEY !== '';
    if (!$serp_ok && (class_exists('VS08_SerpApi') || defined('VS08V_PATH'))) {
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            $reason = !class_exists('VS08_SerpApi') ? 'classe absente' : (empty(VS08_SERPAPI_API_KEY) ? 'clé SERPAPI manquante (config.cfg)' : 'clé vide');
            error_log('[VS08C SerpApi] Ignoré: ' . $reason . '. Ajoutez SERPAPI_API_KEY=xxx dans wp-content/plugins/vs08-voyages/config.cfg');
        }
    }
    if ($serp_ok) {
        try {
            $serp_result = VS08_SerpApi::search_flights($origin, $destination, $date, $passengers, $date_retour, $flight_opts);
            if (is_wp_error($serp_result)) {
                if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                    error_log('[VS08C SerpApi] ' . $serp_result->get_error_code() . ': ' . $serp_result->get_error_message());
                }
            } elseif (!empty($serp_result['flights'])) {
                foreach ($serp_result['flights'] as &$f) { $f['source'] = 'serpapi'; }
                unset($f);
                $all_flights = array_merge($all_flights, $serp_result['flights']);
            }
        } catch (\Throwable $e) {
            error_log('[VS08C SerpApi] ' . $e->getMessage());
        }
    }

    if (function_exists('vs08v_try_serpapi_relaxed')) {
        $all_flights = vs08v_try_serpapi_relaxed($all_flights, $origin, $destination, $date, $passengers, $date_retour, $flight_opts);
    }
    if (function_exists('vs08v_try_serpapi_loose_layover')) {
        $all_flights = vs08v_try_serpapi_loose_layover($all_flights, $origin, $destination, $date, $passengers, $date_retour, $flight_opts);
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
            vs08c_maybe_update_vol_min_cache($circuit_id, $prix_vol_base);
            wp_send_json_success(['prix' => $prix_vol_base, 'note' => 'estimate', 'flights' => []]);
        }
        wp_send_json_error('Aucun vol trouvé pour cette date.');
    }

    $cheapest = $all_flights[0];
    // Toujours utiliser price_per_pax (prix par personne) : total_amount Duffel est pour tous les passagers
    $prix = floatval($cheapest['price_per_pax'] ?? $prix_vol_base);
    $currency = $cheapest['currency'] ?? 'EUR';
    if ($prix > 0) {
        vs08c_maybe_update_vol_min_cache($circuit_id, $prix);
    }

    wp_send_json_success([
        'prix'     => $prix,
        'currency' => $currency,
        'note'     => 'live',
        'flights'  => $all_flights,
    ]);
}
