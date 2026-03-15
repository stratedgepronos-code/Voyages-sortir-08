<?php
/**
 * VS08 Voyages — Duffel API Wrapper (GOLD v3.0.0)
 * 
 * CHANGEMENTS v3.0.0 :
 * - get_offer_services() gère l'expiration des offer_id
 * - Si Duffel renvoie "resource does not exist", on relance une recherche fraîche
 * - On retrouve le même vol (flight_number + depart_time) dans les nouveaux résultats
 * - On appelle /available_services avec le nouvel offer_id frais
 * - Pas de cache sur les services en erreur
 */

if (!defined('ABSPATH')) exit;

class VS08_Duffel_API {

    const API_BASE  = 'https://api.duffel.com';
    const CACHE_TTL = 1200; // 20 * 60 (évite MINUTE_IN_SECONDS si non défini)

    /* ──────────────────────────────────────────────
     * RECHERCHE DE VOLS (inchangée)
     * ────────────────────────────────────────────── */
    public static function search_flights($origin, $destination, $date, $passengers = 1, $date_retour = '') {

        $cache_key = 'vs08_duffel_' . md5("{$origin}_{$destination}_{$date}_{$passengers}_{$date_retour}");
        $cached    = get_transient($cache_key);
        if ($cached !== false) return $cached;

        $api_key = defined('VS08_DUFFEL_API_KEY') ? VS08_DUFFEL_API_KEY : '';
        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'Clé Duffel manquante.');
        }

        $slices = [[
            'origin'          => strtoupper($origin),
            'destination'     => strtoupper($destination),
            'departure_date'  => $date,
            'max_connections' => 0,
        ]];
        if (!empty($date_retour)) {
            $slices[] = [
                'origin'          => strtoupper($destination),
                'destination'     => strtoupper($origin),
                'departure_date'  => $date_retour,
                'max_connections' => 0,
            ];
        }

        $payload = ['data' => [
            'slices'      => $slices,
            'passengers'  => array_fill(0, max(1, intval($passengers)), ['type' => 'adult']),
            'cabin_class' => 'economy',
        ]];

        $request_url = self::API_BASE . '/air/offer_requests?return_offers=true';

        $response = wp_remote_post($request_url, [
            'timeout' => 45,
            'headers' => [
                'Authorization'  => 'Bearer ' . $api_key,
                'Content-Type'   => 'application/json',
                'Accept'         => 'application/json',
                'Duffel-Version' => 'v2',
            ],
            'body' => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) return $response;

        $http_code = wp_remote_retrieve_response_code($response);
        $body      = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($body['errors'])) {
            return new WP_Error('duffel_error', $body['errors'][0]['message'] ?? 'Erreur API Duffel');
        }
        if ($http_code !== 200 && $http_code !== 201) {
            return new WP_Error('duffel_http', 'Erreur serveur vols (HTTP ' . $http_code . ')');
        }

        $raw_offers = $body['data']['offers'] ?? [];
        if (empty($raw_offers)) {
            return new WP_Error('no_flights', 'Aucun vol direct trouvé.');
        }

        $is_roundtrip = !empty($date_retour);
        $offers = [];

        foreach ($raw_offers as $offer) {
            $slices = $offer['slices'] ?? [];

            // ── ALLER-RETOUR : vérifier les 2 slices ──
            if ($is_roundtrip) {
                // Il faut exactement 2 slices (aller + retour)
                if (count($slices) !== 2) continue;

                $slice_aller  = $slices[0];
                $slice_retour = $slices[1];

                // Les 2 doivent être directs (1 seul segment chacun)
                if (count($slice_aller['segments'] ?? []) !== 1) continue;
                if (count($slice_retour['segments'] ?? []) !== 1) continue;

                $seg_aller  = $slice_aller['segments'][0];
                $seg_retour = $slice_retour['segments'][0];

                // Même compagnie sur l'aller ET le retour
                $airline_aller  = $seg_aller['marketing_carrier']['iata_code'] ?? '';
                $airline_retour = $seg_retour['marketing_carrier']['iata_code'] ?? '';
                if ($airline_aller !== $airline_retour) continue;

                $airline   = $seg_aller['marketing_carrier'] ?? [];
                $dep       = $seg_aller['departing_at'] ?? '';
                $arr       = $seg_aller['arriving_at'] ?? '';
                $dep_ret   = $seg_retour['departing_at'] ?? '';
                $arr_ret   = $seg_retour['arriving_at'] ?? '';
                $price_raw = floatval($offer['total_amount'] ?? 0);

                $offers[] = [
                    'offer_id'       => $offer['id'],
                    'airline_name'   => $airline['name'] ?? 'Compagnie inconnue',
                    'airline_iata'   => $airline['iata_code'] ?? '',
                    'flight_number'  => ($airline['iata_code'] ?? '') . ($seg_aller['marketing_carrier_flight_number'] ?? ''),
                    'depart_at'      => $dep,
                    'arrive_at'      => $arr,
                    'depart_time'    => $dep ? date('H:i', strtotime($dep)) : '--:--',
                    'arrive_time'    => $arr ? date('H:i', strtotime($arr)) : '--:--',
                    'duration_min'   => !empty($slice_aller['duration']) ? self::iso_to_minutes($slice_aller['duration']) : 0,
                    // Retour
                    'retour_flight'  => ($airline['iata_code'] ?? '') . ($seg_retour['marketing_carrier_flight_number'] ?? ''),
                    'retour_depart'  => $dep_ret ? date('H:i', strtotime($dep_ret)) : '--:--',
                    'retour_arrive'  => $arr_ret ? date('H:i', strtotime($arr_ret)) : '--:--',
                    'retour_duration'=> !empty($slice_retour['duration']) ? self::iso_to_minutes($slice_retour['duration']) : 0,
                    // Prix = total A/R pour tous les passagers
                    'price_total'    => $price_raw,
                    'price_per_pax'  => $passengers > 0 ? round($price_raw / $passengers, 2) : $price_raw,
                    'currency'       => $offer['total_currency'] ?? 'EUR',
                    'bags_included'  => !empty($offer['conditions']['change_before_departure']['allowed']),
                    'is_roundtrip'   => true,
                ];

            // ── ALLER SIMPLE (comportement original) ──
            } else {
                $slice = $slices[0] ?? null;
                if (!$slice) continue;
                if (count($slice['segments'] ?? []) !== 1) continue;
                $segment = $slice['segments'][0];

                $airline   = $segment['marketing_carrier'] ?? [];
                $dep       = $segment['departing_at'] ?? '';
                $arr       = $segment['arriving_at'] ?? '';
                $price_raw = floatval($offer['total_amount'] ?? 0);

                $offers[] = [
                    'offer_id'      => $offer['id'],
                    'airline_name'  => $airline['name']      ?? 'Compagnie inconnue',
                    'airline_iata'  => $airline['iata_code'] ?? '',
                    'flight_number' => ($airline['iata_code'] ?? '') . ($segment['marketing_carrier_flight_number'] ?? ''),
                    'depart_at'     => $dep,
                    'arrive_at'     => $arr,
                    'depart_time'   => $dep ? date('H:i', strtotime($dep)) : '--:--',
                    'arrive_time'   => $arr ? date('H:i', strtotime($arr)) : '--:--',
                    'duration_min'  => !empty($slice['duration']) ? self::iso_to_minutes($slice['duration']) : 0,
                    'price_total'   => $price_raw,
                    'price_per_pax' => $passengers > 0 ? round($price_raw / $passengers, 2) : $price_raw,
                    'currency'      => $offer['total_currency'] ?? 'EUR',
                    'bags_included' => !empty($offer['conditions']['change_before_departure']['allowed']),
                    'is_roundtrip'  => false,
                ];
            }
        }

        // Dédoublonnage : même vol physique, garder le moins cher
        $unique = [];
        foreach ($offers as $o) {
            $key_parts = [$o['airline_iata'], $o['flight_number'], $o['depart_time'], $o['arrive_time']];
            // En A/R : inclure le vol retour dans la clé de dédup
            if (!empty($o['retour_flight'])) {
                $key_parts[] = $o['retour_flight'];
                $key_parts[] = $o['retour_depart'];
            }
            $key = implode('|', $key_parts);
            if (!isset($unique[$key]) || $o['price_total'] < $unique[$key]['price_total']) {
                $unique[$key] = $o;
            }
        }
        $offers = array_values($unique);
        usort($offers, fn($a, $b) => $a['price_total'] <=> $b['price_total']);

        if (empty($offers)) {
            return new WP_Error('no_flights', 'Aucun vol direct disponible.');
        }

        $ref_price_pax = $offers[0]['price_per_pax'] ?? 0;
        foreach ($offers as &$o) {
            $o['delta_per_pax'] = round($o['price_per_pax'] - $ref_price_pax, 2);
            $o['is_reference']  = ($o['delta_per_pax'] === 0.0);
        }
        unset($o);

        $result = [
            'flights'           => $offers,
            'ref_price_per_pax' => $ref_price_pax,
            'passengers'        => $passengers,
            'origin'            => $origin,
            'destination'       => $destination,
            'date'              => $date,
            'fetched_at'        => time(),
        ];

        set_transient($cache_key, $result, self::CACHE_TTL);
        return $result;
    }


    /* ──────────────────────────────────────────────
     * SERVICES VOL (bagages, sièges)
     * 
     * LOGIQUE DE RETRY :
     * 1. On essaie avec l'offer_id reçu
     * 2. Si Duffel dit "resource does not exist" (= expiré)
     *    → on relance search_flights() SANS CACHE pour avoir des offer_id frais
     *    → on cherche dans les résultats le vol correspondant (flight_number + depart_time)
     *    → on réessaie /available_services avec le nouvel offer_id
     * ────────────────────────────────────────────── */

    /**
     * Récupère les services disponibles (bagages, sièges) pour une offre Duffel.
     *
     * @param string $offer_id     L'ID de l'offre (peut être expiré)
     * @param int    $passengers   Nombre de passagers
     * @param array  $search_params Paramètres pour re-chercher si offer_id expiré :
     *                             ['origin', 'destination', 'date', 'flight_number', 'airline_iata', 'depart_time']
     */
    public static function get_offer_services($offer_id, $passengers = 1, $search_params = []) {
        $offer_id = sanitize_text_field($offer_id);
        if (empty($offer_id)) {
            return new WP_Error('missing_offer', 'offer_id manquant.');
        }

        // ── 1) Vérifier le cache services ─────────────────────────────
        $cache_key = 'vs08_services_' . md5($offer_id . '_' . $passengers);
        $cached    = get_transient($cache_key);
        if ($cached !== false) {
            error_log('[VS08] Services cache HIT pour ' . $offer_id);
            return $cached;
        }

        $api_key = defined('VS08_DUFFEL_API_KEY') ? VS08_DUFFEL_API_KEY : '';
        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'Clé Duffel manquante.');
        }

        error_log('[VS08] Services : appel Duffel pour offer_id=' . $offer_id);

        // ── 2) Premier essai avec l'offer_id reçu ─────────────────────
        $services_result = self::_call_available_services($offer_id, $passengers, $api_key);

        // ── 3) Si ça a marché → parser et retourner ───────────────────
        if (!is_wp_error($services_result)) {
            error_log('[VS08] Services OK pour ' . $offer_id . ' → ' . count($services_result) . ' service(s) brut(s)');
            $parsed = self::_parse_services($services_result);
            // Ne mettre en cache que si on a des données utiles
            if (!empty($parsed['baggage']) || !empty($parsed['seat'])) {
                set_transient($cache_key, $parsed, self::CACHE_TTL);
            }
            return $parsed;
        }

        // ── 4) Détecter si c'est une erreur d'expiration ──────────────
        $error_code = $services_result->get_error_code();
        $error_msg  = $services_result->get_error_message();
        error_log('[VS08] Services ERREUR pour ' . $offer_id . ' : [' . $error_code . '] ' . $error_msg);

        $is_expired = (
            $error_code === 'duffel_services_404' ||
            stripos($error_msg, 'does not exist')  !== false ||
            stripos($error_msg, 'not found')       !== false ||
            stripos($error_msg, 'has expired')     !== false ||
            stripos($error_msg, 'no longer')       !== false
        );

        if (!$is_expired) {
            // Autre erreur → on abandonne sans mettre en cache
            return $services_result;
        }

        // ── 5) Vérifier qu'on a les paramètres pour re-chercher ───────
        $origin        = $search_params['origin']        ?? '';
        $destination   = $search_params['destination']   ?? '';
        $date          = $search_params['date']          ?? '';
        $flight_number = $search_params['flight_number'] ?? '';
        $depart_time   = $search_params['depart_time']   ?? '';

        if (empty($origin) || empty($destination) || empty($date) || empty($flight_number)) {
            error_log('[VS08] offer_id expiré mais params manquants : origin=' . $origin . ' dest=' . $destination . ' date=' . $date . ' flight=' . $flight_number);
            return new WP_Error('expired_no_params', 'Offre expirée. Veuillez relancer la recherche de vols.');
        }

        error_log('[VS08] offer_id expiré → RETRY : re-recherche ' . $flight_number . ' ' . $origin . '→' . $destination . ' le ' . $date);

        // ── 6) Supprimer le cache de recherche pour forcer une requête fraîche ──
        $search_cache_key = 'vs08_duffel_' . md5("{$origin}_{$destination}_{$date}_{$passengers}_");
        delete_transient($search_cache_key);
        // Supprimer aussi le cache services de l'ancien offer_id
        delete_transient($cache_key);

        // ── 7) Relancer la recherche de vols ──────────────────────────
        $fresh_search = self::search_flights($origin, $destination, $date, $passengers);

        if (is_wp_error($fresh_search)) {
            error_log('[VS08] Re-recherche échouée : ' . $fresh_search->get_error_message());
            return new WP_Error('refresh_failed', 'Impossible de rafraîchir les vols : ' . $fresh_search->get_error_message());
        }

        $fresh_flights = $fresh_search['flights'] ?? [];
        error_log('[VS08] Re-recherche OK : ' . count($fresh_flights) . ' vol(s) trouvé(s)');

        if (empty($fresh_flights)) {
            return new WP_Error('flight_not_found', 'Vol introuvable dans la nouvelle recherche.');
        }

        // ── 8) Retrouver le même vol dans les résultats frais ─────────
        $new_offer_id = null;
        foreach ($fresh_flights as $f) {
            $match_flight = (strcasecmp($f['flight_number'], $flight_number) === 0);

            $match_time = true;
            if (!empty($depart_time) && !empty($f['depart_time'])) {
                $match_time = ($f['depart_time'] === $depart_time);
            }

            if ($match_flight && $match_time) {
                $new_offer_id = $f['offer_id'];
                error_log('[VS08] Vol retrouvé : ' . $f['flight_number'] . ' ' . $f['depart_time'] . ' → new offer_id=' . $new_offer_id);
                break;
            }
        }

        if (empty($new_offer_id)) {
            // Log les vols disponibles pour debug
            $available = [];
            foreach ($fresh_flights as $f) { $available[] = $f['flight_number'] . '@' . $f['depart_time']; }
            error_log('[VS08] Vol ' . $flight_number . '@' . $depart_time . ' introuvable. Vols dispo : ' . implode(', ', $available));
            return new WP_Error('flight_not_found', 'Ce vol n\'est plus disponible. Veuillez sélectionner un autre vol.');
        }

        // ── 9) Réessayer /available_services avec le nouvel offer_id ──
        $retry_result = self::_call_available_services($new_offer_id, $passengers, $api_key);

        if (is_wp_error($retry_result)) {
            error_log('[VS08] Retry services échoué : ' . $retry_result->get_error_message());
            return $retry_result;
        }

        // ── 10) Parser, mettre en cache et retourner ──────────────────
        error_log('[VS08] Retry services OK → ' . count($retry_result) . ' service(s) brut(s)');
        $parsed = self::_parse_services($retry_result);
        $new_cache_key = 'vs08_services_' . md5($new_offer_id . '_' . $passengers);
        if (!empty($parsed['baggage']) || !empty($parsed['seat'])) {
            set_transient($new_cache_key, $parsed, self::CACHE_TTL);
        }

        // Ajouter le nouvel offer_id pour que le JS puisse le mettre à jour
        $parsed['refreshed_offer_id'] = $new_offer_id;

        return $parsed;
    }


    /* ──────────────────────────────────────────────
     * MÉTHODE PRIVÉE : Appel brut à /available_services
     * Retourne les données brutes ou un WP_Error
     * ────────────────────────────────────────────── */
    private static function _call_available_services($offer_id, $passengers, $api_key) {
        $passengers_arr = array_fill(0, max(1, intval($passengers)), ['type' => 'adult']);
        $url = self::API_BASE . '/air/offers/' . $offer_id . '/available_services';

        $response = wp_remote_post($url, [
            'timeout' => 30,
            'headers' => [
                'Authorization'  => 'Bearer ' . $api_key,
                'Content-Type'   => 'application/json',
                'Accept'         => 'application/json',
                'Duffel-Version' => 'v2',
            ],
            'body' => wp_json_encode(['data' => ['passengers' => $passengers_arr]]),
        ]);

        if (is_wp_error($response)) {
            error_log('[VS08] _call_available_services WP_Error : ' . $response->get_error_message());
            return $response;
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $raw_body  = wp_remote_retrieve_body($response);
        $body      = json_decode($raw_body, true);

        error_log('[VS08] _call_available_services HTTP ' . $http_code . ' pour ' . $offer_id . ' (body length: ' . strlen($raw_body) . ')');

        // Offer expiré / introuvable
        if ($http_code === 404 || $http_code === 410 || $http_code === 422) {
            $err = $body['errors'][0]['message'] ?? 'Resource not found (HTTP ' . $http_code . ')';
            return new WP_Error('duffel_services_404', $err);
        }

        if ($http_code !== 200) {
            $err = $body['errors'][0]['message'] ?? 'HTTP ' . $http_code;
            // Certaines erreurs Duffel arrivent en 400 mais signifient "expired"
            if (stripos($err, 'does not exist') !== false || stripos($err, 'not found') !== false) {
                return new WP_Error('duffel_services_404', $err);
            }
            return new WP_Error('duffel_services', $err);
        }

        return $body['data'] ?? [];
    }


    /* ──────────────────────────────────────────────
     * MÉTHODE PRIVÉE : Parser les services bruts
     * (bagages, sièges) — même logique qu'avant
     * ────────────────────────────────────────────── */
    private static function _parse_services($raw_services) {
        if (empty($raw_services)) {
            return ['baggage' => [], 'seat' => []];
        }

        $baggage_seen = [];
        $baggage      = [];
        $seat_price   = null;

        foreach ($raw_services as $svc) {
            $type          = $svc['type'] ?? '';
            $price_per_pax = floatval($svc['total_amount'] ?? $svc['amount'] ?? 0);

            if ($type === 'baggage') {
                $meta      = $svc['metadata'] ?? [];
                $bag_type  = $meta['type'] ?? $meta['baggage_type'] ?? 'checked';
                $weight_kg = intval($meta['maximum_weight_kg'] ?? $meta['maximum_weight'] ?? 0);
                $is_cabin  = ($bag_type === 'carry_on');
                $dedup_key = $bag_type . '|' . $weight_kg;

                if (!isset($baggage_seen[$dedup_key])) {
                    $baggage_seen[$dedup_key] = true;
                    if ($is_cabin) {
                        $label = 'Bagage cabine supplémentaire';
                        $icon  = '🎒';
                        $desc  = $weight_kg > 0 ? 'max ' . $weight_kg . ' kg' : '';
                    } else {
                        $icon  = '🧳';
                        $label = $weight_kg > 0 ? 'Bagage soute ' . $weight_kg . ' kg' : 'Bagage soute';
                        $desc  = $weight_kg > 0 ? 'En soute · ' . $weight_kg . ' kg max' : 'En soute';
                    }
                    $baggage[] = [
                        'service_id'    => $svc['id'] ?? '',
                        'type'          => $bag_type,
                        'weight_kg'     => $weight_kg,
                        'label'         => $label,
                        'icon'          => $icon,
                        'desc'          => $desc,
                        'price_per_pax' => $price_per_pax,
                        'currency'      => $svc['total_currency'] ?? 'EUR',
                    ];
                }
            } elseif ($type === 'seat') {
                $seat_price = ($seat_price === null) ? $price_per_pax : min($seat_price, $price_per_pax);
            }
        }

        usort($baggage, function($a, $b) {
            if ($a['type'] !== $b['type']) return $a['type'] === 'carry_on' ? -1 : 1;
            return $a['weight_kg'] - $b['weight_kg'];
        });

        return [
            'baggage' => $baggage,
            'seat'    => $seat_price !== null ? [[
                'label'         => 'Sélection de siège',
                'icon'          => '💺',
                'desc'          => 'Choix de votre siège à bord',
                'price_per_pax' => $seat_price,
                'currency'      => 'EUR',
            ]] : [],
        ];
    }


    /* ──────────────────────────────────────────────
     * UTILITAIRES
     * ────────────────────────────────────────────── */
    private static function iso_to_minutes($iso) {
        preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?/', $iso, $m);
        return (intval($m[1] ?? 0) * 60) + intval($m[2] ?? 0);
    }

    public static function clear_all_cache() {
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_vs08_duffel_%' OR option_name LIKE '_transient_timeout_vs08_duffel_%' OR option_name LIKE '_transient_vs08_services_%' OR option_name LIKE '_transient_timeout_vs08_services_%'"
        );
        if (class_exists('VS08_SerpApi')) {
            VS08_SerpApi::clear_cache();
        }
    }
}
