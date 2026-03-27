<?php
if (!defined('ABSPATH')) exit;

class VS08S_Rest {

    const NS = 'vs08s/v1';

    private static function json_params(\WP_REST_Request $req) {
        $params = $req->get_json_params();
        return is_array($params) ? $params : [];
    }

    public static function register() {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }

    public static function register_routes() {
        // Recherche vols (réutilise Duffel via vs08-voyages)
        register_rest_route(self::NS, '/flights', [
            'methods'  => 'POST',
            'callback' => [__CLASS__, 'search_flights'],
            'permission_callback' => '__return_true',
        ]);

        // Recherche disponibilité hôtel Bedsonline
        register_rest_route(self::NS, '/hotel-availability', [
            'methods'  => 'POST',
            'callback' => [__CLASS__, 'hotel_availability'],
            'permission_callback' => '__return_true',
        ]);

        // Calcul prix total
        register_rest_route(self::NS, '/calculate', [
            'methods'  => 'POST',
            'callback' => [__CLASS__, 'calculate'],
            'permission_callback' => '__return_true',
        ]);

        // Booking (création commande WooCommerce)
        register_rest_route(self::NS, '/booking', [
            'methods'  => 'POST',
            'callback' => [__CLASS__, 'booking'],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);
    }

    /**
     * Recherche de vols via Duffel (réutilise la classe existante).
     */
    public static function search_flights(\WP_REST_Request $req) {
        $params    = self::json_params($req);
        $sejour_id = intval($params['sejour_id'] ?? $req->get_param('sejour_id'));
        $aeroport  = strtoupper(sanitize_text_field($params['aeroport'] ?? $req->get_param('aeroport') ?? ''));
        $date      = sanitize_text_field($params['date'] ?? $params['date_depart'] ?? $req->get_param('date') ?? '');
        $adults    = max(1, intval($params['adults'] ?? $params['nb_adultes'] ?? $req->get_param('adults') ?? 2));

        $m = VS08S_Meta::get($sejour_id);
        $iata_dest = strtoupper(sanitize_text_field($params['iata_dest'] ?? ($m['iata_dest'] ?? '')));
        $duree = intval($params['duree'] ?? ($m['duree'] ?? 7));

        if (!$sejour_id) {
            return new \WP_Error('no_sejour', 'ID séjour manquant.', ['status' => 400]);
        }
        if (empty($aeroport)) {
            return new \WP_Error('no_origin', 'Aéroport de départ manquant.', ['status' => 400]);
        }
        if (empty($date)) {
            return new \WP_Error('no_date', 'Date de départ manquante.', ['status' => 400]);
        }
        if (empty($iata_dest)) {
            return new \WP_Error('no_dest', 'Code IATA destination manquant dans la fiche séjour.', ['status' => 400]);
        }

        $date_retour = date('Y-m-d', strtotime($date . ' +' . $duree . ' days'));

        // Utiliser la classe Duffel du plugin vs08-voyages
        if (!class_exists('VS08_Duffel_API')) {
            return new \WP_Error('no_duffel', 'Plugin vs08-voyages requis pour la recherche de vols.', ['status' => 500]);
        }

        $opts = [
            'max_connections' => !empty($m['vol_escales_autorisees']) ? 1 : 0,
            'max_layover_minutes' => max(60, intval(($m['vol_escale_max_heures'] ?? 5) * 60)),
        ];
        $result = VS08_Duffel_API::search_flights($aeroport, $iata_dest, $date, $adults, $date_retour, $opts);

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response([
            'data' => $result['flights'] ?? [],
            'combos' => $result['flights'] ?? [],
            'ref_price_per_pax' => $result['ref_price_per_pax'] ?? 0,
            'passengers' => $result['passengers'] ?? $adults,
            'origin' => $result['origin'] ?? $aeroport,
            'destination' => $result['destination'] ?? $iata_dest,
            'date' => $result['date'] ?? $date,
        ]);
    }

    /**
     * Recherche de disponibilité hôtel via Bedsonline.
     */
    public static function hotel_availability(\WP_REST_Request $req) {
        $params    = self::json_params($req);
        $sejour_id = intval($params['sejour_id'] ?? $req->get_param('sejour_id'));
        $date      = sanitize_text_field($params['date'] ?? $params['date_depart'] ?? $req->get_param('date') ?? '');
        $adults    = max(1, intval($params['adults'] ?? $params['nb_adultes'] ?? $req->get_param('adults') ?? 2));
        $rooms     = max(1, intval($params['rooms'] ?? $params['nb_chambres'] ?? $req->get_param('rooms') ?? 1));

        $m = VS08S_Meta::get($sejour_id);
        if (!$sejour_id) {
            return new \WP_Error('no_sejour', 'ID séjour manquant.', ['status' => 400]);
        }
        if (empty($date)) {
            return new \WP_Error('no_date', 'Date de départ manquante.', ['status' => 400]);
        }

        // Collecter tous les codes hôtel
        $codes = [];
        if (!empty($m['hotel_code'])) $codes[] = $m['hotel_code'];
        if (!empty($m['hotel_codes']) && is_array($m['hotel_codes'])) {
            $codes = array_merge($codes, $m['hotel_codes']);
        }
        if (!empty($params['hotel_code'])) {
            $codes[] = sanitize_text_field($params['hotel_code']);
        }
        if (!empty($params['hotel_codes']) && is_array($params['hotel_codes'])) {
            $codes = array_merge($codes, array_map('sanitize_text_field', $params['hotel_codes']));
        }
        $codes = array_unique(array_filter($codes));

        if (empty($codes)) {
            return new \WP_Error('no_codes', 'Aucun code hôtel Bedsonline configuré dans la fiche séjour.', ['status' => 400]);
        }

        $duree = intval($params['duree'] ?? ($m['duree'] ?? 7));
        $check_in  = $date;
        $check_out = date('Y-m-d', strtotime($date . ' +' . $duree . ' days'));

        $results = VS08S_Bedsonline::search_availability($codes, $check_in, $check_out, $adults, $rooms);

        if (is_wp_error($results)) {
            return $results;
        }

        // Trouver le meilleur tarif pour chaque board type
        $pension_map = [
            'ai' => 'AI', 'pc' => 'FB', 'dp' => 'HB', 'bb' => 'BB', 'lo' => 'RO',
        ];
        $preferred_board = $pension_map[$m['pension'] ?? 'ai'] ?? 'AI';

        $best = VS08S_Bedsonline::best_rate($results, $preferred_board);

        // Aussi chercher les alternatives
        $alternatives = [];
        foreach ($pension_map as $code => $board) {
            $alt = VS08S_Bedsonline::best_rate($results, $board);
            if ($alt) {
                $alternatives[$code] = [
                    'board_code' => $board,
                    'board_name' => VS08S_Bedsonline::board_label($board),
                    'net_price'  => $alt['net_price'],
                    'room_name'  => $alt['room_name'],
                    'rate_key'   => $alt['rate_key'],
                ];
            }
        }

        return rest_ensure_response([
            'best'         => $best,
            'alternatives' => $alternatives,
            'hotel_name'   => $m['hotel_nom'] ?? '',
            'check_in'     => $check_in,
            'check_out'    => $check_out,
            'duration'     => $duree,
        ]);
    }

    /**
     * Calcul de prix total.
     */
    public static function calculate(\WP_REST_Request $req) {
        $sejour_id = intval($req->get_param('sejour_id'));
        $params = $req->get_json_params();

        $devis = VS08S_Calculator::compute($sejour_id, $params);

        return rest_ensure_response($devis);
    }

    /**
     * Booking — crée la commande WooCommerce.
     */
    public static function booking(\WP_REST_Request $req) {
        $params = $req->get_json_params();
        $sejour_id = intval($params['sejour_id'] ?? 0);

        if (!$sejour_id) {
            return new \WP_Error('no_sejour', 'Séjour manquant.', ['status' => 400]);
        }

        $result = VS08S_Booking::create_order($sejour_id, $params);

        if (is_wp_error($result)) return $result;

        return rest_ensure_response($result);
    }
}
