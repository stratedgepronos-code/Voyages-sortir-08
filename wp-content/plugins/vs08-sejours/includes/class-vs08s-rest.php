<?php
if (!defined('ABSPATH')) exit;

class VS08S_Rest {

    const NS = 'vs08s/v1';

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
        $sejour_id = intval($req->get_param('sejour_id'));
        $aeroport  = strtoupper(sanitize_text_field($req->get_param('aeroport') ?? ''));
        $date      = sanitize_text_field($req->get_param('date') ?? '');
        $adults    = max(1, intval($req->get_param('adults') ?? 2));

        $m = VS08S_Meta::get($sejour_id);
        if (empty($m['iata_dest'])) {
            return new \WP_Error('no_dest', 'Code IATA destination manquant.', ['status' => 400]);
        }

        $duree = intval($m['duree'] ?? 7);
        $date_retour = date('Y-m-d', strtotime($date . ' +' . $duree . ' days'));

        // Utiliser la classe Duffel du plugin vs08-voyages
        if (!class_exists('VS08V_DuffelApi')) {
            return new \WP_Error('no_duffel', 'Plugin vs08-voyages requis pour la recherche de vols.', ['status' => 500]);
        }

        $result = VS08V_DuffelApi::search_offers([
            'origin'      => $aeroport,
            'destination' => $m['iata_dest'],
            'departure'   => $date,
            'return'      => $date_retour,
            'adults'      => $adults,
            'cabin_class' => 'economy',
            'max_connections' => 1,
        ]);

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response($result);
    }

    /**
     * Recherche de disponibilité hôtel via Bedsonline.
     */
    public static function hotel_availability(\WP_REST_Request $req) {
        $sejour_id = intval($req->get_param('sejour_id'));
        $date      = sanitize_text_field($req->get_param('date') ?? '');
        $adults    = max(1, intval($req->get_param('adults') ?? 2));
        $rooms     = max(1, intval($req->get_param('rooms') ?? 1));

        $m = VS08S_Meta::get($sejour_id);

        // Collecter tous les codes hôtel
        $codes = [];
        if (!empty($m['hotel_code'])) $codes[] = $m['hotel_code'];
        if (!empty($m['hotel_codes']) && is_array($m['hotel_codes'])) {
            $codes = array_merge($codes, $m['hotel_codes']);
        }
        $codes = array_unique(array_filter($codes));

        if (empty($codes)) {
            return new \WP_Error('no_codes', 'Aucun code hôtel Bedsonline configuré.', ['status' => 400]);
        }

        $duree = intval($m['duree'] ?? 7);
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
