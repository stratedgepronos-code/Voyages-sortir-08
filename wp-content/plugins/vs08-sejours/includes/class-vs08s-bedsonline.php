<?php
/**
 * Intégration API Bedsonline / Hotelbeds
 *
 * Documentation: https://developer.hotelbeds.com/documentation/hotels/
 * 
 * Endpoints:
 *   Sandbox:    https://api.test.hotelbeds.com/hotel-api/1.0/
 *   Production: https://api.hotelbeds.com/hotel-api/1.0/
 * 
 * Authentication:
 *   Header Api-key: votre clé API
 *   Header X-Signature: SHA256(ApiKey + SharedSecret + UTCTimestamp_in_seconds)
 *   Header Accept: application/json
 *   Header Content-Type: application/json
 */
if (!defined('ABSPATH')) exit;

class VS08S_Bedsonline {

    const SANDBOX_URL = 'https://api.test.hotelbeds.com/hotel-api/1.0/';
    const PROD_URL    = 'https://api.hotelbeds.com/hotel-api/1.0/';

    /**
     * Retourne l'URL de base selon le mode (sandbox ou production).
     */
    private static function base_url() {
        return VS08S_BEDS_SANDBOX ? self::SANDBOX_URL : self::PROD_URL;
    }

    /**
     * Génère les headers d'authentification.
     */
    private static function auth_headers() {
        $api_key = VS08S_BEDS_API_KEY;
        $secret  = VS08S_BEDS_API_SECRET;
        $ts      = time();
        $sig     = hash('sha256', $api_key . $secret . $ts);

        return [
            'Api-key'      => $api_key,
            'X-Signature'  => $sig,
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Vérifie que les credentials API sont configurées.
     */
    public static function is_configured() {
        return !empty(VS08S_BEDS_API_KEY) && !empty(VS08S_BEDS_API_SECRET);
    }

    /**
     * Recherche de disponibilité hôtelière.
     *
     * @param array $hotel_codes  Codes hôtel Bedsonline (ex: [134589, 134590])
     * @param string $check_in    Date d'arrivée (YYYY-MM-DD)
     * @param string $check_out   Date de départ (YYYY-MM-DD)
     * @param int $adults         Nombre d'adultes par chambre
     * @param int $rooms          Nombre de chambres
     * @param string $nationality Code pays client (FR par défaut)
     * @return array|WP_Error     Résultats ou erreur
     */
    public static function search_availability($hotel_codes, $check_in, $check_out, $adults = 2, $rooms = 1, $nationality = 'FR') {
        if (!self::is_configured()) {
            return new \WP_Error('not_configured', 'API Bedsonline non configurée. Ajoutez les clés dans config.cfg.');
        }

        if (empty($hotel_codes)) {
            return new \WP_Error('no_codes', 'Aucun code hôtel fourni.');
        }

        // Construire les occupations
        // IMPORTANT: adults = nombre TOTAL de voyageurs, pas par chambre
        // Il faut répartir les adultes entre les chambres
        $adults_per_room = max(1, intval(ceil($adults / $rooms)));
        $remaining = $adults;
        $occupancies = [];
        for ($i = 0; $i < $rooms; $i++) {
            $in_this_room = min($adults_per_room, $remaining);
            $remaining -= $in_this_room;
            $occupancies[] = [
                'rooms'    => 1,
                'adults'   => max(1, $in_this_room),
                'children' => 0,
            ];
        }

        $body = [
            'stay' => [
                'checkIn'  => $check_in,
                'checkOut' => $check_out,
            ],
            'occupancies' => $occupancies,
            'hotels' => [
                'hotel' => array_map('intval', $hotel_codes),
            ],
        ];

        // Cache : éviter de rappeler l'API pour la même requête
        $cache_key = 'vs08s_beds_' . md5(json_encode($body));
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $url = self::base_url() . 'hotels';
        $headers = self::auth_headers();

        $response = wp_remote_post($url, [
            'timeout' => 30,
            'headers' => $headers,
            'body'    => json_encode($body),
        ]);

        if (is_wp_error($response)) {
            error_log('[VS08S Bedsonline] HTTP error: ' . $response->get_error_message());
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $raw  = wp_remote_retrieve_body($response);
        $data = json_decode($raw, true);

        if ($code !== 200) {
            $err_msg = $data['error']['message'] ?? 'Erreur HTTP ' . $code;
            error_log('[VS08S Bedsonline] API error (' . $code . '): ' . $err_msg);
            return new \WP_Error('api_error', $err_msg);
        }

        // Extraire les résultats pertinents
        $results = self::parse_availability($data);

        // Cacher 2 minutes (phase test — repasser à 600 en production)
        set_transient($cache_key, $results, 120);

        return $results;
    }

    /**
     * Parse la réponse de l'API et extrait les tarifs structurés.
     */
    private static function parse_availability($data) {
        $hotels = $data['hotels']['hotels'] ?? [];
        $results = [];

        foreach ($hotels as $hotel) {
            $hotel_result = [
                'code'     => $hotel['code'] ?? '',
                'name'     => $hotel['name'] ?? '',
                'category' => $hotel['categoryName'] ?? '',
                'rooms'    => [],
            ];

            foreach ($hotel['rooms'] ?? [] as $room) {
                foreach ($room['rates'] ?? [] as $rate) {
                    $board_code = $rate['boardCode'] ?? '';
                    $board_name = $rate['boardName'] ?? self::board_label($board_code);
                    $net_price  = floatval($rate['net'] ?? 0);
                    $cancellation = $rate['cancellationPolicies'] ?? [];
                    $rate_type  = $rate['rateType'] ?? '';
                    $packaging  = !empty($rate['packaging']);

                    $hotel_result['rooms'][] = [
                        'room_code'    => $room['code'] ?? '',
                        'room_name'    => $room['name'] ?? '',
                        'board_code'   => $board_code,
                        'board_name'   => $board_name,
                        'net_price'    => $net_price,
                        // Tarifs uniquement: on ne stocke pas de clé de réservation Hotelbeds.
                        'rate_type'    => $rate_type,
                        'packaging'    => $packaging,
                        'cancellation' => $cancellation,
                        'rooms_count'  => $rate['rooms'] ?? 1,
                        'adults'       => $rate['adults'] ?? 2,
                    ];
                }
            }

            // Trier par prix
            usort($hotel_result['rooms'], function($a, $b) {
                return $a['net_price'] <=> $b['net_price'];
            });

            $results[] = $hotel_result;
        }

        return $results;
    }

    /**
     * Trouve le meilleur tarif pour un board code donné (ex: 'AI' pour All Inclusive).
     *
     * @param array  $results    Résultats de search_availability()
     * @param string $board_code Code board souhaité ('AI', 'HB', 'BB', 'RO', etc.)
     * @return array|null        Meilleur tarif trouvé ou null
     */
    public static function best_rate($results, $board_code = 'AI') {
        $best = null;

        foreach ($results as $hotel) {
            foreach ($hotel['rooms'] as $room) {
                if (strtoupper($room['board_code']) !== strtoupper($board_code)) continue;
                if ($best === null || $room['net_price'] < $best['net_price']) {
                    $best = array_merge($room, [
                        'hotel_code' => $hotel['code'],
                        'hotel_name' => $hotel['name'],
                    ]);
                }
            }
        }

        return $best;
    }

    /**
     * Convertit un code board Bedsonline en label français.
     */
    public static function board_label($code) {
        $map = [
            'RO' => 'Logement seul',
            'BB' => 'Petit-déjeuner',
            'HB' => 'Demi-pension',
            'FB' => 'Pension complète',
            'AI' => 'All Inclusive',
            'TI' => 'Ultra All Inclusive',
        ];
        return $map[strtoupper($code)] ?? $code;
    }

    /**
     * Convertit le code pension interne (vs08) en code board Bedsonline.
     */
    public static function pension_to_board($pension_code) {
        $map = [
            'lo' => 'RO',
            'bb' => 'BB',
            'dp' => 'HB',
            'pc' => 'FB',
            'ai' => 'AI',
        ];
        return $map[$pension_code] ?? 'AI';
    }

    /**
     * Récupère les détails d'un hôtel (photos, description, etc.).
     * Utile pour enrichir la fiche produit.
     */
    public static function hotel_details($hotel_code) {
        if (!self::is_configured()) return new \WP_Error('not_configured', 'API non configurée.');

        $cache_key = 'vs08s_hotel_detail_' . $hotel_code;
        $cached = get_transient($cache_key);
        if ($cached !== false) return $cached;

        $url = self::base_url() . 'hotels/' . intval($hotel_code) . '/details';
        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => self::auth_headers(),
        ]);

        if (is_wp_error($response)) return $response;

        $data = json_decode(wp_remote_retrieve_body($response), true);
        $hotel = $data['hotel'] ?? null;

        if ($hotel) {
            // Cacher 24h
            set_transient($cache_key, $hotel, 86400);
        }

        return $hotel;
    }
}
