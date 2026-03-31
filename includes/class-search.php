<?php
if (!defined('ABSPATH')) exit;

class VS08V_Search {

    const TRANSIENT_KEY = 'vs08v_search_agg_v5';

    const TYPE_LABELS = [
        'sejour_golf'   => 'Séjours Golf',
        'sejour'        => 'Séjours All Inclusive',
        'road_trip'     => 'Road Trip',
        'circuit'       => 'Circuits',
        'city_trip'     => 'City Trip',
        'parc'          => 'Billets Parcs',
    ];

    /**
     * Normalisation aéroports : corrige les typos et doublons.
     * Clé = code saisi dans le produit → valeur = code IATA correct.
     */
    const AIRPORT_NORMALIZE = [
        'NTA'  => 'NTE',
        'ORLY' => 'ORY',
        'BAL'  => 'BSL',
        'BLS'  => 'BSL',
        'LIM'  => 'LIG',
        'DOL'  => 'DOL',
        'CRL'  => 'CRL',
    ];

    /**
     * Noms propres des aéroports (code IATA → ville).
     * Utilisé pour uniformiser les labels dans les dropdowns.
     */
    const AIRPORT_NAMES = [
        'BSL' => 'Bâle-Mulhouse',
        'BES' => 'Brest',
        'BIO' => 'Bilbao',
        'BIQ' => 'Biarritz',
        'BOD' => 'Bordeaux',
        'BRU' => 'Bruxelles',
        'BVA' => 'Paris Beauvais',
        'CDG' => 'Paris Charles de Gaulle',
        'CRL' => 'Charleroi',
        'DLE' => 'Dole',
        'DOL' => 'Deauville',
        'GVA' => 'Genève',
        'LIG' => 'Limoges',
        'LIL' => 'Lille',
        'LRH' => 'La Rochelle',
        'LUX' => 'Luxembourg',
        'LYS' => 'Lyon',
        'MPL' => 'Montpellier',
        'MRS' => 'Marseille',
        'NCE' => 'Nice',
        'NTE' => 'Nantes',
        'ORY' => 'Paris Orly',
        'PGF' => 'Perpignan',
        'RNS' => 'Rennes',
        'SXB' => 'Strasbourg',
        'TLS' => 'Toulouse',
        'TUF' => 'Tours',
        'XCR' => 'Vatry',
    ];

    /**
     * Normalisation destinations : ville/région → pays.
     * Quand un produit a "Djerba" comme destination, on le regroupe sous "Tunisie".
     */
    const DEST_NORMALIZE = [
        'Djerba'            => 'Tunisie',
        'Hammamet'          => 'Tunisie',
        'Sousse'            => 'Tunisie',
        'Faro'              => 'Portugal',
        'Algarve'           => 'Portugal',
        'Lisbonne'          => 'Portugal',
        'Madère'            => 'Portugal',
        'Fes'               => 'Maroc',
        'Fès'               => 'Maroc',
        'Marrakech'         => 'Maroc',
        'Tanger'            => 'Maroc',
        'Agadir'            => 'Maroc',
        'El Jadida'         => 'Maroc',
        'Grande Canarie'    => 'Canaries',
        'Fuerteventura'     => 'Canaries',
        'Tenerife'          => 'Canaries',
        'Lanzarote'         => 'Canaries',
        'Costa del Sol'     => 'Espagne',
        'Marbella'          => 'Espagne',
        'Majorque'          => 'Espagne',
        'Andalousie'        => 'Espagne',
        'Antalya'           => 'Turquie',
        'Belek'             => 'Turquie',
        'Phuket'            => 'Thaïlande',
        'Hua Hin'           => 'Thaïlande',
        'Hurghada'          => 'Égypte',
        'Soma Bay'          => 'Égypte',
        'El Gouna'          => 'Égypte',
        'Sharm el Sheikh'   => 'Égypte',
        'Punta Cana'        => 'République Dominicaine',
        'Rép. Dominicaine'  => 'République Dominicaine',
        'Île Maurice'       => 'Maurice',
        'Crète'             => 'Grèce',
        'Rhodes'            => 'Grèce',
        'Sicile'            => 'Italie',
        'Sardaigne'         => 'Italie',
        'Split'             => 'Croatie',
        'Dubrovnik'         => 'Croatie',
        'Da Nang'           => 'Vietnam',
        'Hanoï'             => 'Vietnam',
        'St Andrews'        => 'Écosse',
        'Paphos'            => 'Chypre',
        'San José'          => 'Costa Rica',
    ];

    /**
     * Drapeaux par pays (fallback quand le produit n'a pas de flag).
     */
    const COUNTRY_FLAGS = [
        'Portugal'              => '🇵🇹',
        'Espagne'               => '🇪🇸',
        'Maroc'                 => '🇲🇦',
        'Tunisie'               => '🇹🇳',
        'Turquie'               => '🇹🇷',
        'Canaries'              => '🇪🇸',
        'Grèce'                 => '🇬🇷',
        'Italie'                => '🇮🇹',
        'Croatie'               => '🇭🇷',
        'Irlande'               => '🇮🇪',
        'Écosse'                => '🏴󠁧󠁢󠁳󠁣󠁴󠁿',
        'France'                => '🇫🇷',
        'Égypte'                => '🇪🇬',
        'Thaïlande'             => '🇹🇭',
        'Maurice'               => '🇲🇺',
        'République Dominicaine'=> '🇩🇴',
        'Chypre'                => '🇨🇾',
        'Vietnam'               => '🇻🇳',
        'Costa Rica'            => '🇨🇷',
        'Malte'                 => '🇲🇹',
    ];

    /**
     * Normalise un code aéroport (corrige les typos).
     */
    public static function normalize_airport(string $code): string {
        $code = strtoupper(trim($code));
        return self::AIRPORT_NORMALIZE[$code] ?? $code;
    }

    /**
     * Normalise une destination (ville → pays).
     */
    public static function normalize_destination(string $dest): string {
        $dest = trim($dest);
        return self::DEST_NORMALIZE[$dest] ?? $dest;
    }

    /**
     * Retourne le nom propre d'un aéroport.
     */
    public static function airport_name(string $code): string {
        $code = self::normalize_airport($code);
        return self::AIRPORT_NAMES[$code] ?? $code;
    }

    public static function register() {
        add_action('wp_ajax_vs08v_search',        [__CLASS__, 'ajax_search']);
        add_action('wp_ajax_nopriv_vs08v_search',  [__CLASS__, 'ajax_search']);
        add_action('save_post_vs08_voyage',        [__CLASS__, 'invalidate_cache']);
        add_action('save_post_vs08_sejour',        [__CLASS__, 'invalidate_cache']);
        add_action('save_post_vs08_circuit',       [__CLASS__, 'invalidate_cache']);
        add_action('trashed_post',                 [__CLASS__, 'invalidate_cache']);
        add_action('untrashed_post',               [__CLASS__, 'invalidate_cache']);

        // Admin: rebuild manuel via ?vs08_rebuild_map=1
        if (is_admin() && isset($_GET['vs08_rebuild_map'])) {
            add_action('init', function() {
                self::rebuild_map_json();
                wp_die('✅ vs08-map-data.json régénéré à ' . date('H:i:s'));
            });
        }

        // Admin: rebuild complet (carte + recherche) via ?vs08_rebuild_search=1
        if (is_admin() && isset($_GET['vs08_rebuild_search'])) {
            add_action('init', function() {
                delete_transient(self::TRANSIENT_KEY);
                self::rebuild_map_json();
                $agg = self::get_aggregated_options();
                $nb_dest = count($agg['destinations'] ?? []);
                $nb_aero = count($agg['aeroports'] ?? []);
                $nb_dates = count($agg['dates'] ?? []);
                wp_die(sprintf(
                    '✅ Index de recherche reconstruit à %s<br>• %d destinations<br>• %d aéroports<br>• %d dates de départ<br>• Carte JSON régénérée',
                    date('H:i:s'), $nb_dest, $nb_aero, $nb_dates
                ));
            });
        }

        // Reset one-shot du cache vol opportuniste (v3 : ajout saisons au calcul)
        if (get_option('_vs08v_vol_cache_reset') !== 'v3') {
            add_action('init', [__CLASS__, 'reset_vol_cache_once']);
        }

        // Générer le fichier JSON carte si inexistant
        $upload_dir = wp_upload_dir();
        $json_path  = $upload_dir['basedir'] . '/vs08-map-data.json';
        if (!file_exists($json_path)) {
            add_action('init', [__CLASS__, 'rebuild_map_json'], 99);
        }
    }

    public static function reset_vol_cache_once() {
        global $wpdb;
        $wpdb->delete($wpdb->postmeta, ['meta_key' => '_vs08v_vol_min_cache']);
        update_option('_vs08v_vol_cache_reset', 'v3');
        delete_transient(self::TRANSIENT_KEY);
    }

    public static function invalidate_cache() {
        delete_transient(self::TRANSIENT_KEY);
        // Régénérer le fichier JSON carte en arrière-plan
        self::rebuild_map_json();
    }

    /**
     * Coordonnées GPS des destinations connues (pour la carte interactive).
     * Ajoutez de nouvelles destinations ici quand vous les créez.
     */
    const DEST_COORDS = [
        'Portugal'              => ['lat'=>37.02,'lon'=>-7.93,'city'=>'Algarve','iata'=>'FAO','region'=>'PORTUGAL'],
        'Espagne'               => ['lat'=>36.72,'lon'=>-4.42,'city'=>'Marbella','iata'=>'AGP','region'=>'ESPAGNE · ANDALOUSIE'],
        'France'                => ['lat'=>44.8,'lon'=>2.0,'city'=>'Biarritz','iata'=>'BOD','region'=>'FRANCE'],
        'Maroc'                 => ['lat'=>31.63,'lon'=>-8.0,'city'=>'Marrakech','iata'=>'RAK','region'=>'MAROC'],
        'Tunisie'               => ['lat'=>34.0,'lon'=>9.8,'city'=>'Djerba','iata'=>'DJE','region'=>'TUNISIE'],
        'Égypte'                => ['lat'=>27.18,'lon'=>33.8,'city'=>'Hurghada','iata'=>'HRG','region'=>'ÉGYPTE · MER ROUGE'],
        'Italie'                => ['lat'=>40.8,'lon'=>14.5,'city'=>'Sicile','iata'=>'CTA','region'=>'ITALIE'],
        'Grèce'                 => ['lat'=>35.5,'lon'=>24.5,'city'=>'Crète','iata'=>'HER','region'=>'GRÈCE'],
        'Turquie'               => ['lat'=>37.5,'lon'=>30.7,'city'=>'Antalya','iata'=>'AYT','region'=>'TURQUIE · BELEK'],
        'Irlande'               => ['lat'=>52.3,'lon'=>-8.5,'city'=>'Kerry','iata'=>'SNN','region'=>'IRLANDE'],
        'Canaries'              => ['lat'=>28.45,'lon'=>-13.86,'city'=>'Fuerteventura','iata'=>'FUE','region'=>'ÎLES CANARIES'],
        'Thaïlande'             => ['lat'=>8.5,'lon'=>98.4,'city'=>'Phuket','iata'=>'HKT','region'=>'THAÏLANDE'],
        'Croatie'               => ['lat'=>43.5,'lon'=>16.4,'city'=>'Split','iata'=>'SPU','region'=>'CROATIE'],
        'République Dominicaine'=> ['lat'=>18.5,'lon'=>-69.9,'city'=>'Punta Cana','iata'=>'PUJ','region'=>'RÉP. DOMINICAINE'],
        'Écosse'                => ['lat'=>56.5,'lon'=>-3.5,'city'=>'St Andrews','iata'=>'EDI','region'=>'ÉCOSSE'],
        'Maurice'               => ['lat'=>-20.3,'lon'=>57.5,'city'=>'Île Maurice','iata'=>'MRU','region'=>'ÎLE MAURICE'],
        'Chypre'                => ['lat'=>34.7,'lon'=>33.0,'city'=>'Paphos','iata'=>'PFO','region'=>'CHYPRE'],
        'Vietnam'               => ['lat'=>16.05,'lon'=>108.2,'city'=>'Da Nang','iata'=>'DAD','region'=>'VIETNAM'],
        'Costa Rica'            => ['lat'=>9.93,'lon'=>-84.08,'city'=>'San José','iata'=>'SJO','region'=>'COSTA RICA'],
        'Malte'                 => ['lat'=>35.9,'lon'=>14.5,'city'=>'La Valette','iata'=>'MLA','region'=>'MALTE'],
    ];

    /**
     * Couleur par type de voyage pour la carte.
     */
    const TYPE_COLORS = [
        'sejour_golf' => '#c9a84c',
        'circuit'     => '#e55d3a',
        'sejour'      => '#59b7b7',
    ];

    /**
     * Régénère le fichier JSON /wp-content/uploads/vs08-map-data.json
     * Appelé à chaque save_post_vs08_voyage / trash / untrash.
     */
    public static function rebuild_map_json() {
        $upload_dir = wp_upload_dir();
        $json_path  = $upload_dir['basedir'] . '/vs08-map-data.json';

        $posts = get_posts([
            'post_type'      => 'vs08_voyage',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);

        // Collecter : destination → { types[], airports[], count }
        $dest_data = [];
        $all_airports = [];

        foreach ($posts as $pid) {
            $m = VS08V_MetaBoxes::get($pid);
            if (($m['statut'] ?? '') === 'archive') continue;

            $dest = trim($m['destination'] ?? '');
            $pays = trim($m['pays'] ?? '');
            // Normaliser la destination
            $dest = self::normalize_destination($dest);
            $pays = $pays ? self::normalize_destination($pays) : $dest;
            $key  = $pays ?: $dest;
            $type = $m['type_voyage'] ?? '';
            if (!$key || !$type) continue;

            if (!isset($dest_data[$key])) {
                $dest_data[$key] = ['types' => [], 'airports' => [], 'count' => 0];
            }
            $dest_data[$key]['count']++;
            if (!in_array($type, $dest_data[$key]['types'])) {
                $dest_data[$key]['types'][] = $type;
            }

            if (!empty($m['aeroports']) && is_array($m['aeroports'])) {
                foreach ($m['aeroports'] as $a) {
                    $code = self::normalize_airport($a['code'] ?? '');
                    $ville = self::airport_name($code);
                    if (!$code) continue;
                    if (!in_array($code, $dest_data[$key]['airports'])) {
                        $dest_data[$key]['airports'][] = $code;
                    }
                    if (!isset($all_airports[$code])) {
                        $all_airports[$code] = ['code' => $code, 'name' => $ville];
                    }
                }
            }
        }

        // Construire le tableau de destinations pour la carte
        $destinations = [];
        foreach ($dest_data as $pays => $info) {
            $coords = self::DEST_COORDS[$pays] ?? null;
            if (!$coords) continue; // Pas de coordonnées connues → on skip

            $flag = '';
            if (class_exists('VS08V_MetaBoxes')) {
                $flag = VS08V_MetaBoxes::resolve_flag(['pays' => $pays]);
            }

            // Couleur = type principal
            $main_type = $info['types'][0] ?? 'sejour';
            $col = self::TYPE_COLORS[$main_type] ?? '#59b7b7';

            // URL de recherche
            $url = home_url('/resultats-recherche') . '?dest=' . rawurlencode($pays);
            if (count($info['types']) === 1) {
                $url .= '&type=' . rawurlencode($info['types'][0]);
            }

            $destinations[] = [
                'id'       => sanitize_title($pays),
                'pays'     => $pays,
                'flag'     => $flag,
                'city'     => $coords['city'],
                'region'   => $coords['region'],
                'iata'     => $coords['iata'],
                'lat'      => $coords['lat'],
                'lon'      => $coords['lon'],
                'col'      => $col,
                'url'      => $url,
                'types'    => $info['types'],
                'airports' => $info['airports'],
                'count'    => $info['count'],
            ];
        }

        $output = [
            'destinations' => $destinations,
            'airports'     => array_values($all_airports),
            'generated'    => date('c'),
        ];

        file_put_contents($json_path, wp_json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    public static function get_aggregated_options() {
        $cached = get_transient(self::TRANSIENT_KEY);
        if ($cached !== false) return $cached;

        $types        = [];
        $destinations = [];
        $airports     = [];
        $durees       = [];
        $dates        = [];
        $airport_dest = [];
        $type_dest    = [];
        $today        = date('Y-m-d');

        // ── 1. Voyages golf (vs08_voyage) ──
        $posts = get_posts([
            'post_type'      => 'vs08_voyage',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);

        foreach ($posts as $pid) {
            $m = VS08V_MetaBoxes::get($pid);
            $statut = $m['statut'] ?? 'actif';
            if ($statut === 'archive') continue;

            $tv = $m['type_voyage'] ?? '';
            if ($tv && isset(self::TYPE_LABELS[$tv])) {
                $types[$tv] = self::TYPE_LABELS[$tv];
            }

            $dest = trim($m['destination'] ?? '');
            $pays = trim($m['pays'] ?? '');
            $flag = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::resolve_flag($m) : trim($m['flag'] ?? '');
            $dest_normalized = self::normalize_destination($dest);
            $pays_normalized = $pays ? self::normalize_destination($pays) : $dest_normalized;
            if ($dest_normalized) {
                $key = $pays_normalized ?: $dest_normalized;
                if (!$flag || $flag === '') {
                    $flag = self::COUNTRY_FLAGS[$key] ?? '';
                }
                self::agg_add_destination($destinations, $key, $flag, $pid, $m['galerie'] ?? []);

                if ($tv) {
                    self::agg_add_type_dest($type_dest, $tv, $key);
                }
            }

            if (!empty($m['aeroports']) && is_array($m['aeroports'])) {
                foreach ($m['aeroports'] as $a) {
                    $code  = self::normalize_airport($a['code'] ?? '');
                    $ville = self::airport_name($code);
                    if ($code && !isset($airports[$code])) {
                        $airports[$code] = [
                            'code'  => $code,
                            'ville' => $ville,
                            'label' => $code . ' — ' . $ville,
                        ];
                    }
                    if ($code && $dest_normalized) {
                        if (!isset($airport_dest[$code])) $airport_dest[$code] = [];
                        if (!in_array($dest_normalized, $airport_dest[$code])) {
                            $airport_dest[$code][] = $dest_normalized;
                        }
                    }
                }
            }

            $d = intval($m['duree'] ?? 0);
            if ($d > 0) $durees[$d] = $d;

            if (!empty($m['dates_depart']) && is_array($m['dates_depart'])) {
                foreach ($m['dates_depart'] as $dd) {
                    $dt = $dd['date'] ?? '';
                    $st = $dd['statut'] ?? 'dispo';
                    if ($dt && $dt >= $today && $st !== 'complet') {
                        $dates[] = $dt;
                    }
                }
            }
        }

        // ── 2. Séjours all inclusive (vs08_sejour) ──
        if (class_exists('VS08S_Meta')) {
            $sejours = get_posts([
                'post_type'      => 'vs08_sejour',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ]);
            foreach ($sejours as $pid) {
                $m = VS08S_Meta::get($pid);
                if (($m['statut'] ?? 'actif') === 'archive') continue;

                if (!isset($types['sejour'])) {
                    $types['sejour'] = self::TYPE_LABELS['sejour'] ?? 'Séjours All Inclusive';
                }

                $dest = trim($m['destination'] ?? '');
                $pays = trim($m['pays'] ?? '');
                $flag = trim($m['flag'] ?? '');
                $dest_normalized = self::normalize_destination($dest);
                $pays_normalized = $pays ? self::normalize_destination($pays) : $dest_normalized;
                if ($dest_normalized) {
                    $key = $pays_normalized ?: $dest_normalized;
                    if (!$flag) $flag = self::COUNTRY_FLAGS[$key] ?? '';
                    self::agg_add_destination($destinations, $key, $flag, $pid, []);
                    self::agg_add_type_dest($type_dest, 'sejour', $key);
                }

                if (!empty($m['aeroports']) && is_array($m['aeroports'])) {
                    foreach ($m['aeroports'] as $a) {
                        $code  = self::normalize_airport($a['code'] ?? '');
                        $ville = self::airport_name($code);
                        if ($code && !isset($airports[$code])) {
                            $airports[$code] = ['code' => $code, 'ville' => $ville, 'label' => $code . ' — ' . $ville];
                        }
                        if ($code && $dest_normalized) {
                            if (!isset($airport_dest[$code])) $airport_dest[$code] = [];
                            if (!in_array($dest_normalized, $airport_dest[$code])) {
                                $airport_dest[$code][] = $dest_normalized;
                            }
                        }
                    }
                }

                $d = intval($m['duree'] ?? 0);
                if ($d > 0) $durees[$d] = $d;
            }
        }

        // ── 3. Circuits (vs08_circuit) ──
        if (class_exists('VS08C_Meta')) {
            $circuits = get_posts([
                'post_type'      => 'vs08_circuit',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ]);
            foreach ($circuits as $pid) {
                $m = VS08C_Meta::get($pid);
                if (($m['statut'] ?? 'actif') === 'archive') continue;

                if (!isset($types['circuit'])) {
                    $types['circuit'] = self::TYPE_LABELS['circuit'] ?? 'Circuits';
                }

                $dest = trim($m['destination'] ?? '');
                $pays = trim($m['pays'] ?? '');
                $flag = class_exists('VS08C_Meta') ? VS08C_Meta::resolve_flag($m) : trim($m['flag'] ?? '');
                $dest_normalized = self::normalize_destination($dest);
                $pays_normalized = $pays ? self::normalize_destination($pays) : $dest_normalized;
                if ($dest_normalized) {
                    $key = $pays_normalized ?: $dest_normalized;
                    if (!$flag) $flag = self::COUNTRY_FLAGS[$key] ?? '';
                    self::agg_add_destination($destinations, $key, $flag, $pid, []);
                    self::agg_add_type_dest($type_dest, 'circuit', $key);
                }

                if (!empty($m['aeroports']) && is_array($m['aeroports'])) {
                    foreach ($m['aeroports'] as $a) {
                        $code  = self::normalize_airport($a['code'] ?? '');
                        $ville = self::airport_name($code);
                        if ($code && !isset($airports[$code])) {
                            $airports[$code] = ['code' => $code, 'ville' => $ville, 'label' => $code . ' — ' . $ville];
                        }
                        if ($code && $dest_normalized) {
                            if (!isset($airport_dest[$code])) $airport_dest[$code] = [];
                            if (!in_array($dest_normalized, $airport_dest[$code])) {
                                $airport_dest[$code][] = $dest_normalized;
                            }
                        }
                    }
                }

                $d = intval($m['duree'] ?? 0);
                if ($d > 0) $durees[$d] = $d;
            }
        }

        ksort($types);
        uasort($destinations, function($a, $b) {
            return strcmp($a['label'], $b['label']);
        });
        ksort($airports);
        sort($durees);
        $dates = array_unique($dates);
        sort($dates);

        $result = [
            'types'            => $types,
            'destinations'     => array_values($destinations),
            'aeroports'        => array_values($airports),
            'durees'           => array_values($durees),
            'dates'            => array_values($dates),
            'airport_dest_map' => $airport_dest,
            'type_dest_map'    => $type_dest,
        ];

        set_transient(self::TRANSIENT_KEY, $result, HOUR_IN_SECONDS);
        return $result;
    }

    private static function agg_add_destination(array &$destinations, string $key, string $flag, int $pid, array $galerie): void {
        if (!isset($destinations[$key])) {
            $img = get_the_post_thumbnail_url($pid, 'large');
            if (!$img && !empty($galerie[0])) $img = $galerie[0];
            $destinations[$key] = [
                'value' => $key,
                'pays'  => $key,
                'flag'  => $flag,
                'label' => mb_convert_case($key, MB_CASE_TITLE, 'UTF-8'),
                'image' => $img ?: '',
                'count' => 1,
            ];
        } else {
            $destinations[$key]['count'] = ($destinations[$key]['count'] ?? 1) + 1;
        }
    }

    private static function agg_add_type_dest(array &$type_dest, string $type, string $dest): void {
        if (!isset($type_dest[$type])) $type_dest[$type] = [];
        if (!in_array($dest, $type_dest[$type])) {
            $type_dest[$type][] = $dest;
        }
    }

    /* ============================================================
       AJAX search (overlay header)
       ============================================================ */
    public static function ajax_search() {
        $q    = sanitize_text_field(wp_unslash($_POST['q'] ?? ''));
        $type = sanitize_text_field(wp_unslash($_POST['type'] ?? ''));

        $post_types = apply_filters('vs08v_search_post_types', ['vs08_voyage']);
        if ($type && in_array($type, $post_types, true)) {
            $post_types = [$type];
        }

        $args = [
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            'posts_per_page' => empty($q) ? 6 : 12,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        if (!empty($q)) {
            $args['s'] = $q;
        }

        $query   = new WP_Query($args);
        $results = [];
        $seen    = [];

        foreach ($query->posts as $p) {
            $seen[$p->ID] = true;
            $card = self::build_card($p);
            if ($card) $results[] = $card;
        }

        if (!empty($q) && count($results) < 12) {
            $meta_args = [
                'post_type'      => $post_types,
                'post_status'    => 'publish',
                'posts_per_page' => 30,
                'post__not_in'   => array_keys($seen) ?: [0],
                'meta_query'     => [
                    [
                        'key'     => 'vs08v_data',
                        'value'   => $q,
                        'compare' => 'LIKE',
                    ],
                ],
            ];
            $meta_query = new WP_Query($meta_args);
            foreach ($meta_query->posts as $p) {
                if (isset($seen[$p->ID])) continue;
                $seen[$p->ID] = true;
                $card = self::build_card($p);
                if ($card) $results[] = $card;
                if (count($results) >= 12) break;
            }
        }

        wp_send_json_success([
            'results' => $results,
            'total'   => count($results),
            'query'   => $q,
        ]);
    }

    private static function build_card($post) {
        $m = VS08V_MetaBoxes::get($post->ID);

        $statut = $m['statut'] ?? 'actif';
        if ($statut === 'archive') return null;

        $thumb = get_the_post_thumbnail_url($post->ID, 'medium');
        if (!$thumb) {
            $galerie = $m['galerie'] ?? [];
            $thumb   = !empty($galerie[0]) ? $galerie[0] : '';
        }

        $hotel    = $m['hotel'] ?? [];
        $appel    = self::compute_prix_appel($m, $post->ID);

        return [
            'id'          => $post->ID,
            'title'       => get_the_title($post->ID),
            'url'         => get_permalink($post->ID),
            'thumbnail'   => $thumb,
            'destination' => $m['destination'] ?? '',
            'pays'        => $m['pays'] ?? '',
            'flag'        => class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::resolve_flag($m) : ($m['flag'] ?? ''),
            'prix'         => $appel['prix'],
            'has_vol'      => $appel['has_vol'],
            'vol_estimate' => !empty($appel['vol_estimate']),
            'duree'       => $m['duree'] ?? '',
            'duree_jours' => $m['duree_jours'] ?? '',
            'nb_parcours' => $m['nb_parcours'] ?? '',
            'niveau'      => $m['niveau'] ?? '',
            'badge'       => $m['badge'] ?? '',
            'hotel_nom'   => $hotel['nom'] ?? ($m['hotel_nom'] ?? ''),
            'post_type'   => $post->post_type,
            'type_voyage' => $m['type_voyage'] ?? '',
        ];
    }

    /**
     * @param array    $m       Meta-données du produit (VS08V_MetaBoxes::get)
     * @param int|null $post_id ID du post — permet de lire le cache vol opportuniste
     * @return array   ['prix' => int, 'has_vol' => bool]
     */
    /**
     * Prix d'appel = minimum "tout compris" pour 1 golfeur en chambre double.
     * Reprend les mêmes composants que class-calculator.php.
     */
    public static function compute_prix_appel($m, $post_id = null) {
        $duree       = max(1, intval($m['duree'] ?? 7));
        $greenfees   = floatval($m['prix_greenfees'] ?? 0);
        $transfert   = floatval($m['prix_transfert'] ?? 0);
        $taxe        = floatval($m['prix_taxe'] ?? 0);

        // Hébergement = prix_double (base) + saison la moins chère
        // Le calculateur additionne les deux : base + supplément saisonnier
        $prix_nuit_base = floatval($m['prix_double'] ?? 0);
        $saison_min     = 0;

        if (!empty($m['saisons']) && is_array($m['saisons'])) {
            $supps = [];
            foreach ($m['saisons'] as $s) {
                $sv = floatval($s['supp'] ?? 0);
                if ($sv > 0) $supps[] = $sv;
            }
            if (!empty($supps)) {
                $saison_min = min($supps);
            }
        }

        $hebergement = ($prix_nuit_base + $saison_min) * $duree;

        // Bagages : mêmes défauts que le calculateur (120 € si non renseigné)
        $bagage_soute = floatval($m['prix_bagage_soute'] ?? 120);
        $bagage_golf  = floatval($m['prix_bagage_golf'] ?? 120);
        if (empty($m['prix_bagage_soute']) && !isset($m['prix_bagage_soute'])) $bagage_soute = 120;
        if (empty($m['prix_bagage_golf'])  && !isset($m['prix_bagage_golf']))  $bagage_golf  = 120;

        // Vol : 1) cache opportuniste (recherches réelles, le moins cher gagne) 2) sinon estimation admin (prix_vol_base)
        $vol           = 0;
        $has_vol       = false; // true = issu du cache visiteurs (API)
        $vol_estimate  = false; // true = composant vol = prix_vol_base fiche produit

        if ($post_id) {
            $cache = get_post_meta($post_id, '_vs08v_vol_min_cache', true);
            if (!empty($cache['prix']) && !empty($cache['ts'])) {
                $age = time() - intval($cache['ts']);
                if ($age < 14 * DAY_IN_SECONDS) {
                    $vol     = floatval($cache['prix']);
                    $has_vol = true;
                }
            }
        }

        if ($vol <= 0) {
            $pvb = floatval($m['prix_vol_base'] ?? 0);
            if ($pvb > 0) {
                $vol          = $pvb;
                $vol_estimate = true;
            }
        }

        $total = $hebergement + $greenfees + $vol + $transfert + $taxe + $bagage_soute + $bagage_golf;

        if (!empty($m['marge_activate'])) {
            $mv = floatval($m['marge_valeur'] ?? 0);
            if (($m['marge_type'] ?? 'pct') === 'pct') {
                $total *= (1 + $mv / 100);
            } else {
                $total += $mv;
            }
        }

        return [
            'prix'         => $total > 0 ? (int) ceil($total) : 0,
            'has_vol'      => $has_vol,
            'vol_estimate' => $vol_estimate,
            'debug'        => [
                'duree'         => $duree,
                'prix_nuit_base'=> $prix_nuit_base,
                'saison_min'    => $saison_min,
                'hebergement'   => $hebergement,
                'greenfees'     => $greenfees,
                'vol'           => $vol,
                'vol_estimate'  => $vol_estimate,
                'bagage_soute'  => $bagage_soute,
                'bagage_golf'   => $bagage_golf,
                'taxe'          => $taxe,
                'transfert'     => $transfert,
            ],
        ];
    }
}
