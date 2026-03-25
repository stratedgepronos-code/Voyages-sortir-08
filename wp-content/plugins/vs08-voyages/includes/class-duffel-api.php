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

    /**
     * Convertit un montant dans la devise du site (EUR) si l'API Duffel renvoie une autre devise
     * (ex. facturation Duffel en GBP → affichage correct en € sur le site).
     *
     * @param float  $amount   Montant brut (total_amount = total pour tous les passagers)
     * @param string $currency Code devise (ex. GBP, USD)
     * @return float Montant en EUR
     */
    private static function _amount_to_eur($amount, $currency) {
        $currency = strtoupper((string) $currency);
        if ($currency === 'EUR' || $currency === '') {
            return $amount;
        }
        $converted = apply_filters('vs08_duffel_convert_to_eur', $amount, $currency);
        if (is_numeric($converted) && (float) $converted !== (float) $amount) {
            return round((float) $converted, 2);
        }
        // Taux indicatifs (à mettre à jour ou utiliser le filtre ci‑dessus pour un taux live)
        $rates = [
            'GBP' => 1.18,
            'USD' => 0.93,
            'CHF' => 1.05,
        ];
        $rate = $rates[$currency] ?? 1;
        return round($amount * $rate, 2);
    }

    /**
     * Vérifie escales : nombre de segments et attente max entre deux vols (même slice).
     *
     * @param int $max_connections      0 = direct uniquement ; 1 = au plus 1 escale (2 segments)
     * @param int $max_layover_minutes  Ignoré si max_connections === 0
     */
    private static function _slice_valid_for_connection_rules(array $slice, int $max_connections, int $max_layover_minutes): bool {
        $segs = $slice['segments'] ?? [];
        $n    = count($segs);
        if ($n < 1) {
            return false;
        }
        if ($max_connections === 0) {
            return $n === 1;
        }
        if ($n > $max_connections + 1) {
            return false;
        }
        if ($n === 1) {
            return true;
        }
        for ($i = 0; $i < $n - 1; $i++) {
            $arr = strtotime($segs[$i]['arriving_at'] ?? '');
            $dep = strtotime($segs[$i + 1]['departing_at'] ?? '');
            if (!$arr || !$dep || $dep <= $arr) {
                return false;
            }
            $mins = (int) round(($dep - $arr) / 60);
            if ($mins > $max_layover_minutes) {
                return false;
            }
        }
        return true;
    }

    /**
     * Même compagnie marketing sur tous les segments des deux slices (A/R).
     */
    private static function _roundtrip_same_marketing_carrier_all_segments(array $slice_aller, array $slice_retour): bool {
        $codes = [];
        foreach ([$slice_aller, $slice_retour] as $slice) {
            foreach ($slice['segments'] ?? [] as $seg) {
                $c = $seg['marketing_carrier']['iata_code'] ?? '';
                if ($c === '') {
                    return false;
                }
                $codes[] = strtoupper($c);
            }
        }
        if (empty($codes)) {
            return false;
        }
        $first = $codes[0];
        foreach ($codes as $c) {
            if ($c !== $first) {
                return false;
            }
        }
        return true;
    }

    /**
     * Résumé affichage d'un slice (premier départ, dernière arrivée, 1er n° de vol).
     *
     * @return array{dep:string,arr:string,depart_time:string,arrive_time:string,duration_min:int,flight_number:string,flight_numbers_all:string,has_connections:bool}|null
     */
    private static function _slice_route_summary(array $slice): ?array {
        $segs = $slice['segments'] ?? [];
        if (empty($segs)) {
            return null;
        }
        $first = reset($segs);
        $last  = end($segs);
        $dep   = $first['departing_at'] ?? '';
        $arr   = $last['arriving_at'] ?? '';
        $fn_parts = [];
        $segments_detail = [];
        $prev_arr_ts = null;
        foreach ($segs as $i => $s) {
            $ac = $s['marketing_carrier']['iata_code'] ?? '';
            $fn = $s['marketing_carrier_flight_number'] ?? '';
            $fn_parts[] = trim($ac . $fn);

            $s_dep = $s['departing_at'] ?? '';
            $s_arr = $s['arriving_at'] ?? '';
            $s_dep_ts = $s_dep ? strtotime($s_dep) : 0;
            $s_arr_ts = $s_arr ? strtotime($s_arr) : 0;

            $layover_min = 0;
            if ($prev_arr_ts && $s_dep_ts > $prev_arr_ts) {
                $layover_min = (int) round(($s_dep_ts - $prev_arr_ts) / 60);
            }

            $segments_detail[] = [
                'flight'      => trim($ac . $fn),
                'origin'      => strtoupper($s['origin']['iata_code'] ?? ''),
                'destination'  => strtoupper($s['destination']['iata_code'] ?? ''),
                'depart_time' => $s_dep ? date('H:i', $s_dep_ts) : '--:--',
                'arrive_time' => $s_arr ? date('H:i', $s_arr_ts) : '--:--',
                'layover_before_min' => $layover_min,
            ];
            $prev_arr_ts = $s_arr_ts;
        }
        return [
            'dep'              => $dep,
            'arr'              => $arr,
            'depart_time'      => $dep ? date('H:i', strtotime($dep)) : '--:--',
            'arrive_time'      => $arr ? date('H:i', strtotime($arr)) : '--:--',
            'duration_min'     => !empty($slice['duration']) ? self::iso_to_minutes($slice['duration']) : 0,
            'flight_number'    => $fn_parts[0] ?? '',
            'flight_numbers_all' => implode(' + ', array_filter($fn_parts)),
            'has_connections'  => count($segs) > 1,
            'segments_detail'  => $segments_detail,
        ];
    }

    /* ──────────────────────────────────────────────
     * RECHERCHE DE VOLS
     * $opts : return_origin (IATA) = open jaw (retour depuis autre aéroport que l’arrivée aller)
     *         max_connections (int) = 0 direct, 1 = 1 escale max par tronçon
     *         max_layover_minutes (int) = attente max entre deux vols (ex. 300 = 5 h)
     * ────────────────────────────────────────────── */
    public static function search_flights($origin, $destination, $date, $passengers = 1, $date_retour = '', $opts = []) {

        $opts       = is_array($opts) ? $opts : [];
        $opt_sig    = md5(wp_json_encode($opts));
        $cache_key  = 'vs08_duffel_' . md5("{$origin}_{$destination}_{$date}_{$passengers}_{$date_retour}_{$opt_sig}");
        $cached    = get_transient($cache_key);
        if ($cached !== false) {
            // Convertir les montants en EUR si le cache contient une autre devise (ex. ancien cache en GBP)
            $pax = isset($cached['passengers']) ? max(1, (int) $cached['passengers']) : 1;
            if (!empty($cached['flights'])) {
                foreach ($cached['flights'] as &$f) {
                    if (!empty($f['currency']) && strtoupper($f['currency']) !== 'EUR') {
                        $total = self::_amount_to_eur($f['price_total'] ?? 0, $f['currency']);
                        $f['price_total']   = $total;
                        $f['price_per_pax'] = $pax > 0 ? round($total / $pax, 2) : $total;
                        $f['currency']      = 'EUR';
                    }
                }
                unset($f);
            }
            return $cached;
        }

        $api_key = defined('VS08_DUFFEL_API_KEY') ? VS08_DUFFEL_API_KEY : '';
        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'Clé Duffel manquante.');
        }

        $max_connections      = isset($opts['max_connections']) ? max(0, min(2, (int) $opts['max_connections'])) : 0;
        $max_layover_minutes  = isset($opts['max_layover_minutes']) ? max(30, (int) $opts['max_layover_minutes']) : 300;
        $return_origin        = !empty($opts['return_origin']) ? strtoupper(sanitize_text_field((string) $opts['return_origin'])) : '';

        $slices = [[
            'origin'          => strtoupper($origin),
            'destination'     => strtoupper($destination),
            'departure_date'  => $date,
            'max_connections' => $max_connections,
        ]];
        if (!empty($date_retour)) {
            $ret_depart_from = $return_origin !== '' ? $return_origin : strtoupper($destination);
            $slices[] = [
                'origin'          => $ret_depart_from,
                'destination'     => strtoupper($origin),
                'departure_date'  => $date_retour,
                'max_connections' => $max_connections,
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
            return new WP_Error('no_flights', 'Aucun vol trouvé pour ces critères.');
        }

        $is_roundtrip = !empty($date_retour);
        $offers       = [];

        foreach ($raw_offers as $offer) {
            $slices = $offer['slices'] ?? [];

            // ── ALLER-RETOUR : vérifier les 2 slices ──
            if ($is_roundtrip) {
                if (count($slices) !== 2) {
                    continue;
                }

                $slice_aller  = $slices[0];
                $slice_retour = $slices[1];

                if (!self::_slice_valid_for_connection_rules($slice_aller, $max_connections, $max_layover_minutes)) {
                    continue;
                }
                if (!self::_slice_valid_for_connection_rules($slice_retour, $max_connections, $max_layover_minutes)) {
                    continue;
                }
                if (!self::_roundtrip_same_marketing_carrier_all_segments($slice_aller, $slice_retour)) {
                    continue;
                }

                $sum_aller  = self::_slice_route_summary($slice_aller);
                $sum_retour = self::_slice_route_summary($slice_retour);
                if (!$sum_aller || !$sum_retour) {
                    continue;
                }

                $seg_aller  = $slice_aller['segments'][0];
                $airline    = $seg_aller['marketing_carrier'] ?? [];
                $offer_currency = $offer['total_currency'] ?? 'EUR';
                $price_raw      = floatval($offer['total_amount'] ?? 0);
                $price_raw      = self::_amount_to_eur($price_raw, $offer_currency);

                $offers[] = [
                    'offer_id'        => $offer['id'],
                    'airline_name'    => $airline['name'] ?? 'Compagnie inconnue',
                    'airline_iata'    => $airline['iata_code'] ?? '',
                    'flight_number'   => $sum_aller['flight_number'],
                    'flight_detail'   => $sum_aller['flight_numbers_all'],
                    'depart_at'       => $sum_aller['dep'],
                    'arrive_at'       => $sum_aller['arr'],
                    'depart_time'     => $sum_aller['depart_time'],
                    'arrive_time'     => $sum_aller['arrive_time'],
                    'duration_min'    => $sum_aller['duration_min'],
                    'has_connections' => $sum_aller['has_connections'] || $sum_retour['has_connections'],
                    'segments_detail' => $sum_aller['segments_detail'],
                    'retour_flight'   => $sum_retour['flight_number'],
                    'retour_flights_detail' => $sum_retour['flight_numbers_all'],
                    'retour_segments_detail' => $sum_retour['segments_detail'],
                    'retour_depart'   => $sum_retour['depart_time'],
                    'retour_arrive'   => $sum_retour['arrive_time'],
                    'retour_duration' => $sum_retour['duration_min'],
                    'open_jaw'        => ($return_origin !== ''),
                    'price_total'     => $price_raw,
                    'price_per_pax'   => $passengers > 0 ? round($price_raw / $passengers, 2) : $price_raw,
                    'currency'        => 'EUR',
                    'bags_included'   => !empty($offer['conditions']['change_before_departure']['allowed']),
                    'is_roundtrip'    => true,
                ];

            // ── ALLER SIMPLE ──
            } else {
                $slice = $slices[0] ?? null;
                if (!$slice) {
                    continue;
                }
                if (!self::_slice_valid_for_connection_rules($slice, $max_connections, $max_layover_minutes)) {
                    continue;
                }
                $sum = self::_slice_route_summary($slice);
                if (!$sum) {
                    continue;
                }
                $segment = $slice['segments'][0];
                $airline = $segment['marketing_carrier'] ?? [];
                $offer_currency = $offer['total_currency'] ?? 'EUR';
                $price_raw      = floatval($offer['total_amount'] ?? 0);
                $price_raw      = self::_amount_to_eur($price_raw, $offer_currency);

                $offers[] = [
                    'offer_id'        => $offer['id'],
                    'airline_name'    => $airline['name'] ?? 'Compagnie inconnue',
                    'airline_iata'    => $airline['iata_code'] ?? '',
                    'flight_number'   => $sum['flight_number'],
                    'flight_detail'   => $sum['flight_numbers_all'],
                    'depart_at'       => $sum['dep'],
                    'arrive_at'       => $sum['arr'],
                    'depart_time'     => $sum['depart_time'],
                    'arrive_time'     => $sum['arrive_time'],
                    'duration_min'    => $sum['duration_min'],
                    'has_connections' => $sum['has_connections'],
                    'segments_detail' => $sum['segments_detail'],
                    'price_total'     => $price_raw,
                    'price_per_pax'   => $passengers > 0 ? round($price_raw / $passengers, 2) : $price_raw,
                    'currency'        => 'EUR',
                    'bags_included'   => !empty($offer['conditions']['change_before_departure']['allowed']),
                    'is_roundtrip'    => false,
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
            return new WP_Error('no_flights', 'Aucun vol ne correspond (direct, escales ou même compagnie A/R).');
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

        $date_retour_retry = sanitize_text_field($search_params['date_retour'] ?? '');
        $duffel_opts_retry = isset($search_params['duffel_opts']) && is_array($search_params['duffel_opts']) ? $search_params['duffel_opts'] : [];
        $opt_sig_retry     = md5(wp_json_encode($duffel_opts_retry));
        // ── 6) Supprimer le cache de recherche pour forcer une requête fraîche ──
        $search_cache_key = 'vs08_duffel_' . md5("{$origin}_{$destination}_{$date}_{$passengers}_{$date_retour_retry}_{$opt_sig_retry}");
        delete_transient($search_cache_key);
        // Supprimer aussi le cache services de l'ancien offer_id
        delete_transient($cache_key);

        // ── 7) Relancer la recherche de vols ──────────────────────────
        $fresh_search = self::search_flights($origin, $destination, $date, $passengers, $date_retour_retry, $duffel_opts_retry);

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
