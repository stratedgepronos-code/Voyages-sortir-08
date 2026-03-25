<?php
/**
 * VS08 Voyages — SerpApi Google Flights Wrapper
 *
 * Complément de Duffel pour les vols Ryanair et low-cost (non distribués via GDS).
 * Même signature et format de sortie que VS08_Duffel_API::search_flights().
 * Stratégie A/R : requête Google Flights type=1 + departure_token (prix A/R cohérent),
 * sinon open jaw ou échec → 2 one-way avec appariement par « famille » compagnie (ex. TUI/BY/X3).
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

        $cache_key = 'vs08_serpapi_' . md5("{$origin}_{$destination}_{$date}_{$passengers}_{$date_retour}_{$opt_sig}_v3");
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
     * Appel SerpApi one-way (type=2).
     * stops=0 = tous vols ; le filtrage connections / layover se fait dans _parse_serpapi_results.
     */
    private static function _search_oneway($origin, $destination, $date, $passengers, $api_key, $opts = []) {
        $max_conn = !empty($opts['max_connections']) ? (int) $opts['max_connections'] : 0;
        $max_lay  = !empty($opts['max_layover_minutes']) ? (int) $opts['max_layover_minutes'] : 0;

        $params = [
            'engine'        => 'google_flights',
            'api_key'       => $api_key,
            'departure_id'  => $origin,
            'arrival_id'    => $destination,
            'outbound_date' => $date,
            'type'          => '2',
            'adults'        => $passengers,
            'currency'      => 'EUR',
            'gl'            => 'fr',
            'hl'            => 'fr',
            'sort_by'       => '2',
        ];
        if ($max_conn <= 0) {
            $params['stops'] = '1';
        }

        $body = self::_serpapi_request($params);
        if (is_wp_error($body)) {
            return $body;
        }

        $items = self::_parse_serpapi_results($body, $passengers, false, $max_lay, $max_conn);
        return ['flights' => $items];
    }

    /**
     * GET SerpApi JSON ou WP_Error.
     *
     * @param array $params Sans engine (ajouté ici).
     * @return array|WP_Error
     */
    private static function _serpapi_request(array $params) {
        $params['engine'] = 'google_flights';
        $url  = self::API_BASE . '?' . http_build_query($params);
        $log_params = $params;
        unset($log_params['api_key']);
        if (function_exists('vs08v_flog')) { vs08v_flog('[SerpApi REQ] ' . wp_json_encode($log_params)); }
        $response = wp_remote_get($url, ['timeout' => 45]);
        if (is_wp_error($response)) {
            if (function_exists('vs08v_flog')) { vs08v_flog('[SerpApi ERR] wp_remote_get: ' . $response->get_error_message()); }
            return $response;
        }
        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if ($code !== 200) {
            if (function_exists('vs08v_flog')) { vs08v_flog('[SerpApi ERR] HTTP ' . $code . ' — ' . substr(wp_remote_retrieve_body($response), 0, 300)); }
            return new WP_Error('serpapi_http', 'SerpApi HTTP ' . $code);
        }
        if (!empty($body['error'])) {
            if (function_exists('vs08v_flog')) { vs08v_flog('[SerpApi ERR] ' . (string) $body['error']); }
            return new WP_Error('serpapi_error', (string) $body['error']);
        }
        $nb = count($body['best_flights'] ?? []) + count($body['other_flights'] ?? []);
        if (function_exists('vs08v_flog')) { vs08v_flog('[SerpApi OK] ' . $nb . ' option(s) — ' . ($params['departure_id'] ?? '?') . '→' . ($params['arrival_id'] ?? '?') . ' stops=' . ($params['stops'] ?? 'auto')); }
        return is_array($body) ? $body : new WP_Error('serpapi_bad', 'Réponse SerpApi invalide.');
    }

    /**
     * Clé d’appariement aller/retour : TUI / Tuifly / BY / X3 / TB / OR → même groupe.
     */
    private static function _airline_match_key(array $item) {
        $iata = strtoupper((string) ($item['airline_iata'] ?? ''));
        $name = strtoupper((string) ($item['airline_name'] ?? ''));
        $tui_codes = ['BY', 'X3', 'TB', '6B', 'OR'];
        if (in_array($iata, $tui_codes, true) || strpos($name, 'TUI') !== false) {
            return '__TUI_GROUP__';
        }
        if ($iata !== '') {
            return $iata;
        }
        $fn = trim((string) ($item['flight_number'] ?? ''));
        if (preg_match('/^([A-Z0-9]{2})\b/i', $fn, $m)) {
            return strtoupper($m[1]);
        }
        return '__UNK__';
    }

    /**
     * Résume un tronçon (aller ou retour) à partir du tableau flights SerpApi.
     *
     * @return array|null
     */
    private static function _summarize_leg_from_flights(array $flights, $max_layover_minutes) {
        $n = count($flights);
        if ($n < 1) {
            return null;
        }
        if ($max_layover_minutes <= 0 && $n !== 1) {
            return null;
        }
        for ($i = 1; $i < $n; $i++) {
            $a0  = $flights[ $i - 1 ]['arrival_airport']['time'] ?? '';
            $d1  = $flights[ $i ]['departure_airport']['time'] ?? '';
            $t_arr = $a0 ? strtotime($a0) : 0;
            $t_dep = $d1 ? strtotime($d1) : 0;
            if (!$t_arr || !$t_dep || $t_dep <= $t_arr) {
                return null;
            }
            $lay = (int) round(($t_dep - $t_arr) / 60);
            if ($max_layover_minutes > 0 && $lay > $max_layover_minutes) {
                return null;
            }
        }

        $seg  = $flights[0];
        $last = $flights[ $n - 1 ];
        $dep  = $seg['departure_airport'] ?? [];
        $arr  = $last['arrival_airport'] ?? [];
        $dep_time_str = $dep['time'] ?? '';
        $arr_time_str = $arr['time'] ?? '';
        $dep_ts       = $dep_time_str ? strtotime($dep_time_str) : 0;
        $arr_ts       = $arr_time_str ? strtotime($arr_time_str) : 0;

        $duration_min = 0;
        foreach ($flights as $f) {
            $duration_min += (int) ($f['duration'] ?? 0);
        }

        $fn_parts        = [];
        $segments_detail = [];
        $prev_arr_ts_seg = null;
        foreach ($flights as $fl) {
            $fn_parts[] = $fl['flight_number'] ?? '';
            $s_dep_str  = $fl['departure_airport']['time'] ?? '';
            $s_arr_str  = $fl['arrival_airport']['time'] ?? '';
            $s_dep_ts   = $s_dep_str ? strtotime($s_dep_str) : 0;
            $s_arr_ts   = $s_arr_str ? strtotime($s_arr_str) : 0;
            $layover    = 0;
            if ($prev_arr_ts_seg && $s_dep_ts > $prev_arr_ts_seg) {
                $layover = (int) round(($s_dep_ts - $prev_arr_ts_seg) / 60);
            }
            $segments_detail[] = [
                'flight'               => $fl['flight_number'] ?? '',
                'origin'               => strtoupper($fl['departure_airport']['id'] ?? ''),
                'destination'          => strtoupper($fl['arrival_airport']['id'] ?? ''),
                'depart_time'          => $s_dep_ts ? date('H:i', $s_dep_ts) : '--:--',
                'arrive_time'          => $s_arr_ts ? date('H:i', $s_arr_ts) : '--:--',
                'layover_before_min'   => $layover,
            ];
            $prev_arr_ts_seg = $s_arr_ts;
        }

        $airline_iata = self::_extract_iata_from_flight_number($seg['flight_number'] ?? '');
        $airline_name = $seg['airline'] ?? $airline_iata;

        return [
            'flight_number'   => $seg['flight_number'] ?? '',
            'flight_detail'   => implode(' + ', array_filter($fn_parts)),
            'segments_detail' => $segments_detail,
            'depart_at'       => $dep_time_str,
            'arrive_at'       => $arr_time_str,
            'depart_time'     => $dep_ts ? date('H:i', $dep_ts) : '--:--',
            'arrive_time'     => $arr_ts ? date('H:i', $arr_ts) : '--:--',
            'duration_min'    => $duration_min,
            'airline_iata'    => $airline_iata,
            'airline_name'    => $airline_name,
            'has_connections' => $n > 1,
        ];
    }

    /**
     * @param array $out _summarize_leg_from_flights
     * @param array $ret _summarize_leg_from_flights
     */
    private static function _build_rt_offer_from_legs(array $out, array $ret, $price_total, $passengers, $currency) {
        $price_total = (float) $price_total;
        return [
            'offer_id'               => 'serpapi_' . md5(($out['flight_number'] ?? '') . ($out['depart_time'] ?? '') . ($ret['flight_number'] ?? '') . ($ret['depart_time'] ?? '') . $price_total),
            'airline_name'           => $out['airline_name'],
            'airline_iata'           => $out['airline_iata'],
            'flight_number'          => $out['flight_number'],
            'flight_detail'          => $out['flight_detail'],
            'segments_detail'        => $out['segments_detail'],
            'depart_at'              => $out['depart_at'],
            'arrive_at'              => $out['arrive_at'],
            'depart_time'            => $out['depart_time'],
            'arrive_time'            => $out['arrive_time'],
            'duration_min'           => $out['duration_min'],
            'has_connections'        => !empty($out['has_connections']) || !empty($ret['has_connections']),
            'retour_flight'          => $ret['flight_number'],
            'retour_flights_detail'  => $ret['flight_detail'],
            'retour_segments_detail' => $ret['segments_detail'],
            'retour_depart'          => $ret['depart_time'],
            'retour_arrive'          => $ret['arrive_time'],
            'retour_duration'        => $ret['duration_min'],
            'price_total'            => $price_total,
            'price_per_pax'          => $passengers > 0 ? round($price_total / $passengers, 2) : $price_total,
            'currency'               => $currency ?: 'EUR',
            'bags_included'          => false,
            'is_roundtrip'           => true,
            'source'                 => 'serpapi',
        ];
    }

    /**
     * A/R classique (sans open jaw) : type=1 + departure_token (prix A/R Google).
     */
    private static function _search_roundtrip_type1($origin, $destination, $date_out, $date_back, $passengers, $api_key, $opts = []) {
        $max_conn = !empty($opts['max_connections']) ? (int) $opts['max_connections'] : 0;
        $max_lay  = !empty($opts['max_layover_minutes']) ? (int) $opts['max_layover_minutes'] : 0;

        $rt_params = [
            'api_key'        => $api_key,
            'departure_id'   => $origin,
            'arrival_id'     => $destination,
            'outbound_date'  => $date_out,
            'return_date'    => $date_back,
            'type'           => '1',
            'adults'         => $passengers,
            'currency'       => 'EUR',
            'gl'             => 'fr',
            'hl'             => 'fr',
            'sort_by'        => '2',
        ];
        if ($max_conn <= 0) {
            $rt_params['stops'] = '1';
        }
        $body = self::_serpapi_request($rt_params);
        if (is_wp_error($body)) {
            return $body;
        }

        $currency = $body['search_parameters']['currency'] ?? 'EUR';
        $all       = array_merge($body['best_flights'] ?? [], $body['other_flights'] ?? []);
        $combined  = [];
        $max_follow = 10;

        foreach ($all as $opt) {
            if (count($combined) >= $max_follow) {
                break;
            }
            $token       = $opt['departure_token'] ?? '';
            $price_total = (float) ($opt['price'] ?? 0);
            $out_flights = $opt['flights'] ?? [];
            if ($token === '' || $price_total <= 0 || empty($out_flights)) {
                continue;
            }

            $ret_body = self::_serpapi_request([
                'api_key'          => $api_key,
                'departure_token'  => $token,
                'currency'         => 'EUR',
                'hl'               => 'fr',
                'gl'               => 'fr',
            ]);
            if (is_wp_error($ret_body)) {
                continue;
            }
            $ret_opts = array_merge($ret_body['best_flights'] ?? [], $ret_body['other_flights'] ?? []);
            $ret_opt  = $ret_opts[0] ?? null;
            if (!$ret_opt || empty($ret_opt['flights'])) {
                continue;
            }
            $ret_flights = $ret_opt['flights'];

            $out_s = self::_summarize_leg_from_flights($out_flights, $max_lay);
            $ret_s = self::_summarize_leg_from_flights($ret_flights, $max_lay);
            if (!$out_s || !$ret_s) {
                continue;
            }

            $combined[] = self::_build_rt_offer_from_legs($out_s, $ret_s, $price_total, $passengers, $currency);
        }

        return ['flights' => $combined];
    }

    /**
     * Deux one-way + appariement par clé compagnie (TUI group, IATA…).
     * Utilisé pour open jaw ou si type=1 + token ne renvoie rien.
     */
    private static function _search_roundtrip_oneway_pair($origin, $destination, $date_out, $date_back, $passengers, $api_key, $opts = []) {
        $out = self::_search_oneway($origin, $destination, $date_out, $passengers, $api_key, $opts);
        if (is_wp_error($out)) {
            return $out;
        }

        $ret_from = !empty($opts['return_origin']) ? strtoupper(sanitize_text_field((string) $opts['return_origin'])) : $destination;
        $back     = self::_search_oneway($ret_from, $origin, $date_back, $passengers, $api_key, $opts);
        if (is_wp_error($back)) {
            return $back;
        }

        $out_items  = $out['flights'] ?? [];
        $back_items = $back['flights'] ?? [];
        if (empty($out_items) || empty($back_items)) {
            return ['flights' => []];
        }

        $back_by_key = [];
        foreach ($back_items as $b) {
            $key = self::_airline_match_key($b);
            if ($key === '__UNK__') {
                continue;
            }
            if (!isset($back_by_key[ $key ]) || $b['price_total'] < $back_by_key[ $key ]['price_total']) {
                $back_by_key[ $key ] = $b;
            }
        }

        $combined = [];
        foreach ($out_items as $o) {
            $key = self::_airline_match_key($o);
            if ($key === '__UNK__' || !isset($back_by_key[ $key ])) {
                continue;
            }
            $b           = $back_by_key[ $key ];
            $price_total = $o['price_total'] + $b['price_total'];

            $combined[] = [
                'offer_id'               => 'serpapi_' . md5($o['flight_number'] . $o['depart_time'] . $b['flight_number'] . $b['depart_time']),
                'airline_name'           => $o['airline_name'],
                'airline_iata'           => $o['airline_iata'],
                'flight_number'          => $o['flight_number'],
                'flight_detail'          => $o['flight_detail'] ?? $o['flight_number'],
                'segments_detail'        => $o['segments_detail'] ?? [],
                'depart_at'              => $o['depart_at'],
                'arrive_at'              => $o['arrive_at'],
                'depart_time'            => $o['depart_time'],
                'arrive_time'            => $o['arrive_time'],
                'duration_min'           => $o['duration_min'],
                'has_connections'        => !empty($o['has_connections']) || !empty($b['has_connections']),
                'retour_flight'          => $b['flight_number'],
                'retour_flights_detail'  => $b['flight_detail'] ?? $b['flight_number'],
                'retour_segments_detail' => $b['segments_detail'] ?? [],
                'retour_depart'          => $b['depart_time'],
                'retour_arrive'          => $b['arrive_time'],
                'retour_duration'        => $b['duration_min'],
                'price_total'            => $price_total,
                'price_per_pax'          => $passengers > 0 ? round($price_total / $passengers, 2) : $price_total,
                'currency'               => $o['currency'] ?? 'EUR',
                'bags_included'          => false,
                'is_roundtrip'           => true,
                'source'                 => 'serpapi',
            ];
        }

        usort($combined, fn ($a, $b) => $a['price_total'] <=> $b['price_total']);
        return ['flights' => $combined];
    }

    /**
     * Open jaw → uniquement 2 one-way. Sinon type=1+token, puis repli sur 2 one-way.
     */
    private static function _search_roundtrip($origin, $destination, $date_out, $date_back, $passengers, $api_key, $opts = []) {
        if (!empty($opts['return_origin'])) {
            return self::_search_roundtrip_oneway_pair($origin, $destination, $date_out, $date_back, $passengers, $api_key, $opts);
        }

        $bundled = self::_search_roundtrip_type1($origin, $destination, $date_out, $date_back, $passengers, $api_key, $opts);
        if (is_wp_error($bundled)) {
            return self::_search_roundtrip_oneway_pair($origin, $destination, $date_out, $date_back, $passengers, $api_key, $opts);
        }
        if (!empty($bundled['flights'])) {
            return $bundled;
        }

        return self::_search_roundtrip_oneway_pair($origin, $destination, $date_out, $date_back, $passengers, $api_key, $opts);
    }

    /**
     * Parse best_flights + other_flights de la réponse SerpApi.
     *
     * @param array $body               Réponse JSON SerpApi
     * @param int   $passengers
     * @param bool  $roundtrip
     * @param int   $max_layover_minutes 0 = vol direct uniquement ; >0 = escales acceptées si chaque correspondance ≤ max
     * @param int   $max_connections     0 = direct ; 1 = 1 escale max ; 2 = 2 escales max
     * @return array
     */
    private static function _parse_serpapi_results($body, $passengers, $roundtrip, $max_layover_minutes = 0, $max_connections = 0) {
        $all = array_merge(
            $body['best_flights'] ?? [],
            $body['other_flights'] ?? []
        );

        $max_segments = $max_connections + 1;

        $items = [];
        foreach ($all as $option) {
            $flights = $option['flights'] ?? [];
            $n       = count($flights);
            if ($n < 1) {
                continue;
            }
            if ($max_connections <= 0 && $n !== 1) {
                continue;
            }
            if ($n > $max_segments) {
                continue;
            }

            $layover_ok = true;
            for ($i = 1; $i < $n; $i++) {
                $a0    = $flights[$i - 1]['arrival_airport']['time'] ?? '';
                $d1    = $flights[$i]['departure_airport']['time'] ?? '';
                $t_arr = $a0 ? strtotime($a0) : 0;
                $t_dep = $d1 ? strtotime($d1) : 0;
                if (!$t_arr || !$t_dep || $t_dep <= $t_arr) {
                    $layover_ok = false;
                    break;
                }
                if ($max_layover_minutes > 0) {
                    $lay = (int) round(($t_dep - $t_arr) / 60);
                    if ($lay > $max_layover_minutes) {
                        $layover_ok = false;
                        break;
                    }
                }
            }
            if (!$layover_ok) {
                continue;
            }

            $seg  = $flights[0];
            $last = $flights[$n - 1];
            $dep  = $seg['departure_airport'] ?? [];
            $arr  = $last['arrival_airport'] ?? [];
            $dep_time_str = $dep['time'] ?? '';
            $arr_time_str = $arr['time'] ?? '';

            $airline_iata = self::_extract_iata_from_flight_number($seg['flight_number'] ?? '');
            $airline_name = $seg['airline'] ?? $airline_iata;

            $duration_min = 0;
            foreach ($flights as $f) {
                $duration_min += (int) ($f['duration'] ?? 0);
            }
            $price_total = (float) ($option['price'] ?? 0);
            if ($price_total <= 0) {
                continue;
            }

            $dep_ts = $dep_time_str ? strtotime($dep_time_str) : 0;
            $arr_ts = $arr_time_str ? strtotime($arr_time_str) : 0;

            $fn_parts        = [];
            $segments_detail = [];
            $prev_arr_ts_seg = null;
            foreach ($flights as $fl) {
                $fn_parts[] = $fl['flight_number'] ?? '';
                $s_dep_str  = $fl['departure_airport']['time'] ?? '';
                $s_arr_str  = $fl['arrival_airport']['time'] ?? '';
                $s_dep_ts   = $s_dep_str ? strtotime($s_dep_str) : 0;
                $s_arr_ts   = $s_arr_str ? strtotime($s_arr_str) : 0;
                $layover    = 0;
                if ($prev_arr_ts_seg && $s_dep_ts > $prev_arr_ts_seg) {
                    $layover = (int) round(($s_dep_ts - $prev_arr_ts_seg) / 60);
                }
                $segments_detail[] = [
                    'flight'             => $fl['flight_number'] ?? '',
                    'origin'             => strtoupper($fl['departure_airport']['id'] ?? ''),
                    'destination'        => strtoupper($fl['arrival_airport']['id'] ?? ''),
                    'depart_time'        => $s_dep_ts ? date('H:i', $s_dep_ts) : '--:--',
                    'arrive_time'        => $s_arr_ts ? date('H:i', $s_arr_ts) : '--:--',
                    'layover_before_min' => $layover,
                ];
                $prev_arr_ts_seg = $s_arr_ts;
            }
            $flight_detail = implode(' + ', array_filter($fn_parts));

            $items[] = [
                'offer_id'        => 'serpapi_' . md5(($seg['flight_number'] ?? '') . $dep_time_str . $price_total),
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
