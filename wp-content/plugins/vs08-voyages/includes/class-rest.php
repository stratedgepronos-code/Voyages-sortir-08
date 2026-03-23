<?php
/**
 * VS08 Voyages — API REST (recherche vols + calcul)
 * Contourne les 500 sur admin-ajax.php en proposant une route REST alternative.
 */
if (!defined('ABSPATH')) exit;

class VS08V_REST {

    const NAMESPACE = 'vs08v/v1';

    public static function register() {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }

    public static function register_routes() {
        register_rest_route(self::NAMESPACE, '/flight', [
            'methods'             => 'POST',
            'permission_callback' => '__return_true',
            'callback'            => [__CLASS__, 'flight'],
            'args'                => [
                'voyage_id'   => ['required' => true, 'type' => 'integer'],
                'date'        => ['required' => true, 'type' => 'string'],
                'aeroport'    => ['required' => true, 'type' => 'string'],
                'date_retour' => ['type' => 'string', 'default' => ''],
                'passengers'  => ['type' => 'integer', 'default' => 1],
                'destination' => ['type' => 'string', 'default' => ''],
            ],
        ]);
        register_rest_route(self::NAMESPACE, '/calculate', [
            'methods'             => 'POST',
            'permission_callback' => '__return_true',
            'callback'            => [__CLASS__, 'calculate'],
            'args'                => [
                'voyage_id'      => ['required' => true, 'type' => 'integer'],
                'date_depart'    => ['required' => true, 'type' => 'string'],
                'aeroport'       => ['required' => true, 'type' => 'string'],
                'nb_golfeurs'    => ['type' => 'integer', 'default' => 1],
                'nb_nongolfeurs' => ['type' => 'integer', 'default' => 0],
                'type_chambre'   => ['type' => 'string', 'default' => 'double'],
                'nb_chambres'    => ['type' => 'integer', 'default' => 1],
                'prix_vol'       => ['type' => 'number', 'default' => 0],
                'rooms'          => ['type' => 'string', 'default' => '[]'],
                'airline_iata'   => ['type' => 'string', 'default' => ''],
                'options'       => ['type' => 'string', 'default' => '[]'],
            ],
        ]);
        register_rest_route(self::NAMESPACE, '/booking', [
            'methods'             => 'POST',
            'permission_callback' => '__return_true',
            'callback'            => [__CLASS__, 'booking'],
            'args'                => [],
        ]);
        // Nonces frais pour éviter « Session expirée » si la page est restée ouverte longtemps
        register_rest_route(self::NAMESPACE, '/nonce', [
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'callback'            => [__CLASS__, 'nonce'],
            'args'                => [],
        ]);
    }

    /**
     * Retourne des nonces frais (pour rafraîchir avant soumission réservation).
     */
    public static function nonce(WP_REST_Request $request) {
        return new WP_REST_Response([
            'nonce'         => wp_create_nonce('vs08v_nonce'),
            'booking_nonce' => wp_create_nonce('vs08v_booking'),
        ], 200, ['Content-Type' => 'application/json']);
    }

    public static function flight(WP_REST_Request $request) {
        $params = [
            'voyage_id'   => $request->get_param('voyage_id'),
            'date'        => $request->get_param('date'),
            'date_retour' => $request->get_param('date_retour'),
            'aeroport'    => $request->get_param('aeroport'),
            'passengers'  => $request->get_param('passengers'),
            'destination' => $request->get_param('destination'),
        ];
        $params = self::merge_missing_from_body($request, $params, ['voyage_id', 'date', 'aeroport', 'date_retour', 'passengers', 'destination']);
        $r = vs08v_get_flight_result($params);
        return new WP_REST_Response(
            ['success' => $r['success'], 'data' => $r['data']],
            200,
            ['Content-Type' => 'application/json']
        );
    }

    public static function calculate(WP_REST_Request $request) {
        $params = [
            'voyage_id'      => $request->get_param('voyage_id'),
            'date_depart'    => $request->get_param('date_depart'),
            'aeroport'       => $request->get_param('aeroport'),
            'nb_golfeurs'    => $request->get_param('nb_golfeurs'),
            'nb_nongolfeurs' => $request->get_param('nb_nongolfeurs'),
            'type_chambre'   => $request->get_param('type_chambre'),
            'nb_chambres'    => $request->get_param('nb_chambres'),
            'prix_vol'       => $request->get_param('prix_vol'),
            'rooms'          => $request->get_param('rooms'),
            'airline_iata'   => $request->get_param('airline_iata'),
        ];
        $params = self::merge_missing_from_body($request, $params, ['voyage_id', 'date_depart', 'aeroport', 'nb_golfeurs', 'nb_nongolfeurs', 'type_chambre', 'nb_chambres', 'prix_vol', 'rooms', 'airline_iata', 'options']);
        $r = vs08v_calculate_result($params);
        return new WP_REST_Response(
            ['success' => $r['success'], 'data' => $r['data']],
            200,
            ['Content-Type' => 'application/json']
        );
    }

    public static function booking(WP_REST_Request $request) {
        $params = $request->get_body_params();
        if (empty($params)) {
            $params = $request->get_json_params();
        }
        // Fallback : body form-urlencoded parfois pas parsé par WP REST
        if (empty($params) && !empty($request->get_body())) {
            $raw = $request->get_body();
            parse_str($raw, $params);
        }
        if (empty($params) || empty($params['voyage_id'])) {
            return new WP_REST_Response(
                ['success' => false, 'data' => 'Données manquantes. Rechargez la page et réessayez.'],
                200,
                ['Content-Type' => 'application/json']
            );
        }
        $_POST = array_merge($_POST, $params);
        $_REQUEST = array_merge($_REQUEST, $params);

        $nonce_val = isset($params['nonce']) ? $params['nonce'] : '';
        if (!$nonce_val || !wp_verify_nonce($nonce_val, 'vs08v_nonce')) {
            return new WP_REST_Response(
                ['success' => false, 'data' => 'Session expirée. Rechargez la page et réessayez.'],
                200,
                ['Content-Type' => 'application/json']
            );
        }

        try {
            if (function_exists('WC') && WC()) {
                if (is_null(WC()->session)) {
                    WC()->initialize_session();
                }
                if (is_null(WC()->cart)) {
                    if (function_exists('wc_load_cart')) {
                        wc_load_cart();
                    } elseif (method_exists(WC(), 'initialize_cart')) {
                        WC()->initialize_cart();
                    }
                }
            }

            $result = VS08V_Booking::process_submission();
            if (!empty($result['error'])) {
                return new WP_REST_Response(
                    ['success' => false, 'data' => $result['error']],
                    200,
                    ['Content-Type' => 'application/json']
                );
            }

            // Le panier sera reconstruit côté checkout via le token transient dans l'URL de redirection

            return new WP_REST_Response(
                ['success' => true, 'data' => $result],
                200,
                ['Content-Type' => 'application/json']
            );
        } catch (Exception $e) {
            if (function_exists('error_log')) {
                error_log('[VS08 REST booking] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            }
            return new WP_REST_Response(
                ['success' => false, 'data' => 'Erreur lors de l\'envoi. Réessayez ou contactez-nous.'],
                200,
                ['Content-Type' => 'application/json']
            );
        }
    }

    /**
     * Repli : jQuery.post (form-urlencoded) parfois non reflété dans get_param() selon l’hébergeur — fusion depuis le corps brut.
     *
     * @param string[] $keys Clés à compléter si vides.
     */
    private static function merge_missing_from_body(WP_REST_Request $request, array $params, array $keys) {
        $parsed = $request->get_body_params();
        if (empty($parsed) || !is_array($parsed)) {
            $jp = $request->get_json_params();
            $parsed = is_array($jp) ? $jp : [];
        }
        if (empty($parsed) && $request->get_body() !== '') {
            $parsed = [];
            parse_str((string) $request->get_body(), $parsed);
        }
        if (!is_array($parsed) || empty($parsed)) {
            return $params;
        }
        foreach ($keys as $k) {
            $cur = array_key_exists($k, $params) ? $params[$k] : null;
            if ($cur === '' || $cur === null) {
                if (array_key_exists($k, $parsed)) {
                    $params[$k] = $parsed[$k];
                }
            }
        }
        return $params;
    }
}
