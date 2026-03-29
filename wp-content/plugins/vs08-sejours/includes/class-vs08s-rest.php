<?php
if (!defined('ABSPATH')) exit;

class VS08S_Rest {

    const NS = 'vs08s/v1';

    /**
     * Extraction robuste des paramètres : JSON body → POST → GET → raw input.
     * Hostinger/LiteSpeed peut bloquer get_json_params() sur certaines routes.
     */
    private static function all_params(\WP_REST_Request $req) {
        $json = $req->get_json_params();
        if (is_array($json) && !empty($json)) return $json;
        $all = $req->get_params();
        if (is_array($all) && !empty($all)) return $all;
        $raw = file_get_contents('php://input');
        if ($raw) { $d = json_decode($raw, true); if (is_array($d)) return $d; }
        return $_POST;
    }

    private static function p($params, $key, $default = '') {
        return $params[$key] ?? $default;
    }

    public static function register() {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }

    public static function register_routes() {
        register_rest_route(self::NS, '/flights', [
            'methods' => 'POST', 'callback' => [__CLASS__, 'search_flights'], 'permission_callback' => '__return_true',
        ]);
        register_rest_route(self::NS, '/hotel-availability', [
            'methods' => 'POST', 'callback' => [__CLASS__, 'hotel_availability'], 'permission_callback' => '__return_true',
        ]);
        register_rest_route(self::NS, '/calculate', [
            'methods' => 'POST', 'callback' => [__CLASS__, 'calculate'], 'permission_callback' => '__return_true',
        ]);
        register_rest_route(self::NS, '/booking', [
            'methods' => 'POST', 'callback' => [__CLASS__, 'booking'], 'permission_callback' => '__return_true',
        ]);
    }

    public static function search_flights(\WP_REST_Request $req) {
        $p = self::all_params($req);
        $sejour_id = intval(self::p($p, 'sejour_id', 0));
        $aeroport  = strtoupper(trim(self::p($p, 'aeroport', '')));
        $date      = self::p($p, 'date', self::p($p, 'date_depart', ''));
        $adults    = max(1, intval(self::p($p, 'adults', self::p($p, 'nb_adultes', 2))));

        if (!$sejour_id) {
            error_log('[VS08S flights] sejour_id=0 — keys: ' . json_encode(array_keys($p)));
            return new \WP_Error('no_sejour', 'ID séjour manquant.', ['status' => 400]);
        }

        $m = VS08S_Meta::get($sejour_id);
        $iata_dest = strtoupper(self::p($p, 'iata_dest', $m['iata_dest'] ?? ''));
        $duree = intval(self::p($p, 'duree', $m['duree'] ?? 7));

        if (!$aeroport)  return new \WP_Error('no_origin', 'Aéroport manquant.', ['status' => 400]);
        if (!$date)      return new \WP_Error('no_date', 'Date manquante.', ['status' => 400]);
        if (!$iata_dest) return new \WP_Error('no_dest', 'Code IATA destination non configuré.', ['status' => 400]);

        $date_retour = date('Y-m-d', strtotime($date . ' +' . $duree . ' days'));

        if (!class_exists('VS08_Duffel_API')) {
            return new \WP_Error('no_duffel', 'Plugin vs08-voyages requis.', ['status' => 500]);
        }

        $opts = [];
        if (!empty($m['vol_escales_autorisees'])) {
            $opts['max_connections'] = 1;
            $opts['max_layover_minutes'] = max(60, intval(($m['vol_escale_max_heures'] ?? 5) * 60));
        }

        $result = VS08_Duffel_API::search_flights($aeroport, $iata_dest, $date, $adults, $date_retour, $opts);
        if (is_wp_error($result)) return $result;

        return rest_ensure_response([
            'data' => $result['flights'] ?? [],
            'combos' => $result['flights'] ?? [],
            'ref_price_per_pax' => $result['ref_price_per_pax'] ?? 0,
            'passengers' => $result['passengers'] ?? $adults,
        ]);
    }

    public static function hotel_availability(\WP_REST_Request $req) {
        $p = self::all_params($req);
        $sejour_id = intval(self::p($p, 'sejour_id', 0));
        $date      = self::p($p, 'date', self::p($p, 'date_depart', ''));
        $adults    = max(1, intval(self::p($p, 'adults', self::p($p, 'nb_adultes', 2))));
        $rooms     = max(1, intval(self::p($p, 'rooms', self::p($p, 'nb_chambres', 1))));

        if (!$sejour_id) {
            error_log('[VS08S hotel] sejour_id=0 — keys: ' . json_encode(array_keys($p)));
            return new \WP_Error('no_sejour', 'ID séjour manquant.', ['status' => 400]);
        }

        $m = VS08S_Meta::get($sejour_id);

        $codes = [];
        if (!empty(self::p($p, 'hotel_code', ''))) $codes[] = self::p($p, 'hotel_code');
        $rc = self::p($p, 'hotel_codes', []);
        if (is_array($rc)) $codes = array_merge($codes, $rc);
        if (!empty($m['hotel_code'])) $codes[] = $m['hotel_code'];
        if (!empty($m['hotel_codes']) && is_array($m['hotel_codes'])) $codes = array_merge($codes, $m['hotel_codes']);
        $codes = array_unique(array_filter($codes));

        if (empty($codes)) return new \WP_Error('no_codes', 'Aucun code Bedsonline configuré.', ['status' => 400]);
        if (!VS08S_Bedsonline::is_configured()) return new \WP_Error('not_configured', 'API Bedsonline non configurée (config.cfg manquant).', ['status' => 500]);

        $duree = intval(self::p($p, 'duree', $m['duree'] ?? 7));
        $check_out = date('Y-m-d', strtotime($date . ' +' . $duree . ' days'));

        $results = VS08S_Bedsonline::search_availability($codes, $date, $check_out, $adults, $rooms);
        if (is_wp_error($results)) return $results;

        $pension_map = ['ai'=>'AI','pc'=>'FB','dp'=>'HB','bb'=>'BB','lo'=>'RO'];
        $preferred = $pension_map[$m['pension'] ?? 'ai'] ?? 'AI';
        $best = VS08S_Bedsonline::best_rate($results, $preferred);

        $alternatives = [];
        foreach ($pension_map as $k => $b) {
            $alt = VS08S_Bedsonline::best_rate($results, $b);
            if ($alt) $alternatives[$k] = ['board_code'=>$b, 'board_name'=>VS08S_Bedsonline::board_label($b), 'net_price'=>$alt['net_price'], 'room_name'=>$alt['room_name']];
        }

        return rest_ensure_response([
            'best' => $best, 'alternatives' => $alternatives,
            'hotel_name' => $m['hotel_nom'] ?? '', 'check_in' => $date, 'check_out' => $check_out, 'duration' => $duree,
        ]);
    }

    public static function calculate(\WP_REST_Request $req) {
        $p = self::all_params($req);
        $sejour_id = intval(self::p($p, 'sejour_id', 0));
        if (!$sejour_id) return new \WP_Error('no_sejour', 'ID séjour manquant.', ['status' => 400]);
        return rest_ensure_response(VS08S_Calculator::compute($sejour_id, $p));
    }

    public static function booking(\WP_REST_Request $req) {
        $p = self::all_params($req);
        $sejour_id = intval(self::p($p, 'sejour_id', 0));
        if (!$sejour_id) return new \WP_Error('no_sejour', 'Séjour manquant.', ['status' => 400]);

        try {
            $result = VS08S_Booking::create_order($sejour_id, $p);
            if (is_wp_error($result)) return $result;
            return rest_ensure_response($result);
        } catch (\Throwable $e) {
            error_log('[VS08S Booking CRASH] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            return new \WP_Error('booking_error', 'Erreur de réservation: ' . $e->getMessage(), ['status' => 500]);
        }
    }
}
