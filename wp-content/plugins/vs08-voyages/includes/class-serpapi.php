<?php
/**
 * VS08 Voyages — SerpApi Google Flights Wrapper
 *
 * Complément de Duffel pour les vols Ryanair et low-cost (non distribués via GDS).
 * Même signature et format de sortie que VS08_Duffel_API::search_flights().
 * Stratégie A/R : deux recherches one-way puis appariement par compagnie.
 */

if (!defined('ABSPATH')) exit;

class VS08_SerpApi {

    const API_BASE  = 'https://serpapi.com/search';
    const CACHE_TTL = 1200; // 20 minutes

    /**
     * Recherche de vols — signature identique à VS08_Duffel_API::search_flights().
     *
     * @param string $origin      Code IATA départ
     * @param string $destination Code IATA arrivée
     * @param string $date        Date aller YYYY-MM-DD
     * @param int    $passengers  Nombre de passagers
     * @param string $date_retour Date retour YYYY-MM-DD (vide = aller simple)
     * @param array  $opts        return_origin (open jaw), max_connections (>0 → 1 escale max côté Serp), max_layover_minutes
     * @return array|WP_Error Même structure que Duffel : ['flights' => [...], 'ref_price_per_pax' => ..., 'passengers' => ..., 'origin' => ..., 'destination' => ..., 'date' => ..., 'fetched_at' => ...]
     */
    public static function search_flights($origin, $destination, $date, $passengers = 1, $date_retour = '', $opts = []) {
        $origin      = strtoupper($origin);
        $destination = strtoupper($destination);
        $passengers  = max(1, intval($passengers));
        $is_roundtrip = !empty($date_retour);
        $opts        = is_array($opts) ? $opts : [];
        $opt_sig     = md5(wp_json_encode($opts));

        $cache_key = 'vs08_serpapi_' . md5("{$origin}_{$destination}_{$date}_{$passengers}_{$date_retour}_{$opt_sig}");
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $api_key = defined('VS08_SERPAPI_API_KEY') ? VS08_SERPAPI_API_KEY : '';
        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'Clé SerpApi manquante.');
        }

        if ($is_roundtrip) {
            $result = self::_search_roundtrip($origin, $destination, $date, $date_retour, $passengers, $api_key, $opts);
        } else {
            $result = self::_search_oneway($origin, $destination, $date, $passengers, $api_key, $opts);
        }

        if (is_wp_error($result)) {
            return $result;
        }

        if (empty($result['flights'])) {
            return new WP_Error('no_flights', 'Aucun vol trouvé pour ces critères.');
        }

        $ref_price_pax = $result['flights'][0]['price_per_pax'] ?? 0;
        foreach ($result['flights'] as &$o) {
            $o['delta_per_pax'] = round(($o['price_per_pax'] ?? 0) - $ref_price_pax, 2);
            $o['is_reference']  = ($o['delta_per_pax'] === 0.0);
            $o['source']         = 'serpapi';
        }
        unset($o);

        $result['ref_price_per_pax'] = $ref_price_pax;
        $result['passengers']        = $passengers;
        $result['origin']            = $origin;
        $result['destination']       = $destination;
        $result['date']             = $date;
        $result['fetched_at']        = time();

        set_transient($cache_key, $result, self::CACHE_TTL);
        return $result;
    }

    /**
     * Appel SerpApi one-way (type=2, stops=1).
     *
     * @return array ['items' => [ ['airline_iata' => ..., 'airline_name' => ..., 'flight_number' => ..., 'depart_time' => ..., 'arrive_time' => ..., 'duration_min' => ..., 'price_total' => ... ], ... ]]
     */
    private static function _search_oneway($origin, $destination, $date, $passengers, $api_key, $opts = []) {
        $allow_conn = !empty($opts['max_connections']) && (int) $opts['max_connections'] > 0;
        $stops      = $allow_conn ? '2' : '1'; // 2 = au plus 1 escale (doc SerpApi)

        $params = [
            'engine'        => 'google_flights',
            'api_key'       => $api_key,
            'departure_id'  => $origin,
            'arrival_id'    => $destination,
            'outbound_date' => $date,
            'type'          => '2', // one way
            'stops'         => $stops,
            'adults'        => $passengers,
            'currency'      => 'EUR',
            'gl'            => 'fr',
            'hl'            => 'fr',
            'sort_by'       => '2', // price
        ];

        $url = self::API_BASE . '?' . http_build_query($params);
        $response = wp_remote_get($url, ['timeout' => 30]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200) {
            return new WP_Error('serpapi_http', 'SerpApi HTTP ' . $code);
        }

        if (!empty($body['error'])) {
            return new WP_Error('serpapi_error', $body['error']);
        }

        $max_lay = !empty($opts['max_layover_minutes']) ? (int) $opts['max_layover_minutes'] : 0;
        $items = self::_parse_serpapi_results($body, $passengers, false, $max_lay);
        return ['flights' => $items];
    }

    /**
     * Deux recherches one-way (aller + retour) puis appariement par compagnie.
     * Open jaw : retour depuis return_origin (ex. CUN) vers origin au lieu de destination→origin.
     */
    private static function _search_roundtrip($origin, $destination, $date_out, $date_back, $passengers, $api_key, $opts = []) {
        $out = self::_search_oneway($origin, $destination, $date_out, $passengers, $api_key, $opts);
        if (is_wp_error($out)) {
            return $out;
        }

        $ret_from = !empty($opts['return_origin']) ? strtoupper(sanitize_text_field((string) $opts['return_origin'])) : $destination;
        $back = self::_search_oneway($ret_from, $origin, $date_back, $passengers, $api_key, $opts);
        if (is_wp_error($back)) {
            return $back;
        }

        $out_items  = $out['flights'] ?? [];
        $back_items = $back['flights'] ?? [];
        if (empty($out_items) || empty($back_items)) {
            return ['flights' => []];
        }

        // Grouper les retours par airline_iata, garder le moins cher par compagnie
        $back_by_airline = [];
        foreach ($back_items as $b) {
            $iata = $b['airline_iata'] ?? '';
            if ($iata === '') continue;
            if (!isset($back_by_airline[$iata]) || $b['price_total'] < $back_by_airline[$iata]['price_total']) {
                $back_by_airline[$iata] = $b;
            }
        }

        $combined = [];
        foreach ($out_items as $o) {
            $iata = $o['airline_iata'] ?? '';
            if ($iata === '' || !isset($back_by_airline[$iata])) continue;

            $b = $back_by_airline[$iata];
            $price_total = $o['price_total'] + $b['price_total'];

            $combined[] = [
                'offer_id'        => 'serpapi_' . md5($o['flight_number'] . $o['depart_time'] . $b['flight_number'] . $b['depart_time']),
                'airline_name'   => $o['airline_name'],
                'airline_iata'   => $o['airline_iata'],
                'flight_number'  => $o['flight_number'],
                'depart_at'      => $o['depart_at'],
                'arrive_at'      => $o['arrive_at'],
                'depart_time'    => $o['depart_time'],
                'arrive_time'    => $o['arrive_time'],
                'duration_min'   => $o['duration_min'],
                'retour_flight'  => $b['flight_number'],
                'retour_depart'  => $b['depart_time'],
                'retour_arrive'  => $b['arrive_time'],
                'retour_duration'=> $b['duration_min'],
                'price_total'    => $price_total,
                'price_per_pax'  => $passengers > 0 ? round($price_total / $passengers, 2) : $price_total,
                'currency'       => 'EUR',
                'bags_included'  => false,
                'is_roundtrip'   => true,
                'source'         => 'serpapi',
            ];
        }

        usort($combined, fn($a, $b) => $a['price_total'] <=> $b['price_total']);
        return ['flights' => $combined];
    }

    /**
     * Parse best_flights + other_flights de la réponse SerpApi.
     * Chaque option doit avoir un seul segment (vol direct).
     *
     * @param array $body Réponse JSON SerpApi
     * @param int   $passengers
     * @param bool  $roundtrip
     * @param int   $max_layover_minutes 0 = vol direct uniquement ; >0 = autorise 1 escale si attente ≤ max
     * @return array Liste au format Duffel-like (sans retour si one-way)
     */
    private static function _parse_serpapi_results($body, $passengers, $roundtrip, $max_layover_minutes = 0) {
        $all = array_merge(
            $body['best_flights'] ?? [],
            $body['other_flights'] ?? []
        );

        $items = [];
        foreach ($all as $option) {
            $flights = $option['flights'] ?? [];
            $n       = count($flights);
            if ($max_layover_minutes <= 0 && $n !== 1) {
                continue;
            }
            if ($max_layover_minutes > 0 && ($n < 1 || $n > 2)) {
                continue;
            }
            if ($n === 2) {
                $a0 = $flights[0]['arrival_airport']['time'] ?? '';
                $d1 = $flights[1]['departure_airport']['time'] ?? '';
                $t_arr = $a0 ? strtotime($a0) : 0;
                $t_dep = $d1 ? strtotime($d1) : 0;
                if (!$t_arr || !$t_dep || $t_dep <= $t_arr) {
                    continue;
                }
                $lay = (int) round(($t_dep - $t_arr) / 60);
                if ($lay > $max_layover_minutes) {
                    continue;
                }
            }

            $seg = $flights[0];
            $last = $flights[$n - 1];
            $dep = $seg['departure_airport'] ?? [];
            $arr = $last['arrival_airport'] ?? [];
            $dep_time_str = $dep['time'] ?? '';
            $arr_time_str = $arr['time'] ?? '';

            $flight_number = $seg['flight_number'] ?? '';
            if ($n === 2) {
                $flight_number = trim($flight_number . ' + ' . ($flights[1]['flight_number'] ?? ''));
            }
            $airline_iata  = self::_extract_iata_from_flight_number($seg['flight_number'] ?? '');
            $airline_name  = $seg['airline'] ?? $airline_iata;

            $duration_min = 0;
            foreach ($flights as $f) {
                $duration_min += (int) ($f['duration'] ?? 0);
            }
            $price_total  = (float) ($option['price'] ?? 0);
            if ($price_total <= 0) {
                continue;
            }

            $dep_ts = $dep_time_str ? strtotime($dep_time_str) : 0;
            $arr_ts = $arr_time_str ? strtotime($arr_time_str) : 0;

            $fn_parts = [];
            $segments_detail = [];
            $prev_arr_ts_seg = null;
            foreach ($flights as $fi => $fl) {
                $fn_parts[] = $fl['flight_number'] ?? '';
                $s_dep_str = $fl['departure_airport']['time'] ?? '';
                $s_arr_str = $fl['arrival_airport']['time'] ?? '';
                $s_dep_ts = $s_dep_str ? strtotime($s_dep_str) : 0;
                $s_arr_ts = $s_arr_str ? strtotime($s_arr_str) : 0;
                $layover = 0;
                if ($prev_arr_ts_seg && $s_dep_ts > $prev_arr_ts_seg) {
                    $layover = (int) round(($s_dep_ts - $prev_arr_ts_seg) / 60);
                }
                $segments_detail[] = [
                    'flight'      => $fl['flight_number'] ?? '',
                    'origin'      => strtoupper($fl['departure_airport']['id'] ?? ''),
                    'destination'  => strtoupper($fl['arrival_airport']['id'] ?? ''),
                    'depart_time' => $s_dep_ts ? date('H:i', $s_dep_ts) : '--:--',
                    'arrive_time' => $s_arr_ts ? date('H:i', $s_arr_ts) : '--:--',
                    'layover_before_min' => $layover,
                ];
                $prev_arr_ts_seg = $s_arr_ts;
            }
            $flight_detail = implode(' + ', array_filter($fn_parts));

            $items[] = [
                'offer_id'        => 'serpapi_' . md5($flight_number . $dep_time_str . $price_total),
                'airline_name'    => $airline_name,
                'airline_iata'    => $airline_iata,
                'flight_number'   => $seg['flight_number'] ?? '',
                'flight_detail'   => $flight_detail,
                'segments_detail' => $segments_detail,
                'depart_at'       => $dep_time_str,
                'arrive_at'       => $arr_time_str,
                'depart_time'     => $dep_ts ? date('H:i', $dep_ts) : '--:--',
                'arrive_time'     => $arr_ts ? date('H:i', $arr_ts) : '--:--',
                'duration_min'    => $duration_min,
                'has_connections' => $n > 1,
                'price_total'     => $price_total,
                'price_per_pax'   => $passengers > 0 ? round($price_total / $passengers, 2) : $price_total,
                'currency'        => $body['search_parameters']['currency'] ?? 'EUR',
                'bags_included'   => false,
                'is_roundtrip'    => $roundtrip,
                'source'          => 'serpapi',
            ];
        }

        return $items;
    }

    /**
     * Extrait le code IATA (2 caractères) du flight_number SerpApi (ex: "FR 1234" -> "FR", "NH 962" -> "NH").
     */
    private static function _extract_iata_from_flight_number($flight_number) {
        $flight_number = trim((string) $flight_number);
        if (preg_match('/^([A-Z0-9]{2})\s/i', $flight_number, $m)) {
            return strtoupper($m[1]);
        }
        if (preg_match('/^([A-Z0-9]{2})/i', $flight_number, $m)) {
            return strtoupper($m[1]);
        }
        return '';
    }

    /**
     * Purge du cache SerpApi (appelé depuis VS08_Duffel_API::clear_all_cache ou manuellement).
     */
    public static function clear_cache() {
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_vs08_serpapi_%' OR option_name LIKE '_transient_timeout_vs08_serpapi_%'"
        );
    }
}
