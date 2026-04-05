<?php
if (!defined('ABSPATH')) exit;

class VS08V_Search {

    const TRANSIENT_KEY = 'vs08v_search_agg_v9';

    const TYPE_LABELS = [
        'sejour_golf' => 'Séjours Golfique',
        'sejour'      => 'Séjours',
        'road_trip'   => 'Road Trip',
        'circuit'     => 'Circuits',
        'city_trip'   => 'City Trip',
    ];

    public static function register() {
        add_action('wp_ajax_vs08v_search',        [__CLASS__, 'ajax_search']);
        add_action('wp_ajax_nopriv_vs08v_search',  [__CLASS__, 'ajax_search']);
        add_action('save_post_vs08_voyage',        [__CLASS__, 'invalidate_cache']);
        add_action('save_post_vs08_sejour',         [__CLASS__, 'invalidate_cache']);
        add_action('save_post_vs08_circuit',       [__CLASS__, 'invalidate_cache']);
        add_action('trashed_post',                 [__CLASS__, 'invalidate_cache']);
        add_action('untrashed_post',               [__CLASS__, 'invalidate_cache']);

        // Reset one-shot du cache vol opportuniste (v3 : ajout saisons au calcul)
        if (get_option('_vs08v_vol_cache_reset') !== 'v3') {
            add_action('init', [__CLASS__, 'reset_vol_cache_once']);
        }
        // Force recalcul du cache search (agrégation par pays v4)
        if (get_option('_vs08v_search_cache_v') !== 'v4') {
            delete_transient(self::TRANSIENT_KEY);
            update_option('_vs08v_search_cache_v', 'v4');
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
    }

    /**
     * Clé d'agrégation : une entrée par PAYS — le dropdown affiche les pays, pas les villes.
     * Si pays vide, repli sur la destination (legacy).
     */
    private static function destination_aggregate_key(string $pays, string $dest): string {
        $p = trim($pays);
        $d = trim($dest);
        // Si les deux sont vides, rien à afficher
        if ($p === '' && $d === '') {
            return '';
        }
        $base = $p !== '' ? $p : $d; // pays en priorité, sinon destination
        return function_exists('mb_strtolower') ? mb_strtolower($base, 'UTF-8') : strtolower($base);
    }

    public static function get_aggregated_options() {
        $cached = get_transient(self::TRANSIENT_KEY);
        if ($cached !== false) {
            return $cached;
        }

        $types         = [];
        $destinations  = [];
        $airports      = [];
        $durees        = [];
        $dates         = [];
        $airport_dest  = [];
        $type_dest     = [];
        $cutoff_golf   = date('Y-m-d', strtotime('+7 days')); // J-7 fermeture ventes golf

        $push_type_dest = static function (array &$type_dest, string $type, string $dest_val) {
            if ($type === '' || $dest_val === '') {
                return;
            }
            if (!isset($type_dest[$type])) {
                $type_dest[$type] = [];
            }
            if (!in_array($dest_val, $type_dest[$type], true)) {
                $type_dest[$type][] = $dest_val;
            }
        };

        $push_air_dest = static function (array &$airport_dest, string $code, string $dest_val) {
            if ($code === '' || $dest_val === '') {
                return;
            }
            if (!isset($airport_dest[$code])) {
                $airport_dest[$code] = [];
            }
            if (!in_array($dest_val, $airport_dest[$code], true)) {
                $airport_dest[$code][] = $dest_val;
            }
        };

        $posts = get_posts([
            'post_type'      => 'vs08_voyage',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);

        foreach ($posts as $pid) {
            $m = VS08V_MetaBoxes::get($pid);
            $statut = $m['statut'] ?? 'actif';
            if ($statut === 'archive') {
                continue;
            }

            $tv = trim((string) ($m['type_voyage'] ?? ''));
            if ($tv !== '' && isset(self::TYPE_LABELS[$tv])) {
                $types[$tv] = self::TYPE_LABELS[$tv];
            }
            if ($tv === '' && self::is_catalog_golf($m)) {
                $types['sejour_golf'] = self::TYPE_LABELS['sejour_golf'];
            }

            $dest = trim($m['destination'] ?? '');
            $pays = trim($m['pays'] ?? '');
            $flag = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::resolve_flag($m) : trim($m['flag'] ?? '');
            // label = pays (priorité) ou destination si pays vide
            $display_val   = $pays !== '' ? $pays : $dest;
            $display_label = ($flag !== '' ? $flag . ' ' : '') . mb_convert_case($display_val, MB_CASE_TITLE, 'UTF-8');
            if ($dest !== '' || $pays !== '') {
                $key = self::destination_aggregate_key($pays, $dest);
                if ($key !== '' && !isset($destinations[$key])) {
                    $img = get_the_post_thumbnail_url($pid, 'large');
                    if (!$img) {
                        $gal = $m['galerie'] ?? [];
                        $img = !empty($gal[0]) ? $gal[0] : '';
                    }
                    $destinations[$key] = [
                        'value' => $display_val,
                        'pays'  => $pays,
                        'flag'  => $flag,
                        'label' => $display_label,
                        'image' => $img ?: '',
                        'count' => 1,
                    ];
                } elseif ($key !== '') {
                    $destinations[$key]['count'] = ($destinations[$key]['count'] ?? 1) + 1;
                }

                $map_type = $tv;
                if ($map_type === '' && self::is_catalog_golf($m)) {
                    $map_type = 'sejour_golf';
                }
                if ($map_type !== '' && isset(self::TYPE_LABELS[$map_type])) {
                    $push_type_dest($type_dest, $map_type, $display_val);
                }
            }

            if (!empty($m['aeroports']) && is_array($m['aeroports'])) {
                foreach ($m['aeroports'] as $a) {
                    $code  = strtoupper(trim($a['code'] ?? ''));
                    $ville = trim($a['ville'] ?? '');
                    if ($code !== '' && !isset($airports[$code])) {
                        $airports[$code] = [
                            'code'  => $code,
                            'ville' => $ville,
                            'label' => $code . ' — ' . $ville,
                        ];
                    }
                    if ($code !== '' && $display_val !== '') {
                        $push_air_dest($airport_dest, $code, $display_val);
                    }
                }
            }

            $d = intval($m['duree'] ?? 0);
            if ($d > 0) {
                $durees[$d] = $d;
            }

            if (!empty($m['dates_depart']) && is_array($m['dates_depart'])) {
                foreach ($m['dates_depart'] as $dd) {
                    $dt = $dd['date'] ?? '';
                    $st = $dd['statut'] ?? 'dispo';
                    if ($dt !== '' && $dt >= $cutoff_golf && $st !== 'complet') {
                        $dates[] = $dt;
                    }
                }
            }
        }

        // Séjours all inclusive
        if (class_exists('VS08S_Meta')) {
            $sejours = get_posts([
                'post_type'      => 'vs08_sejour',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ]);
            foreach ($sejours as $pid) {
                $m = VS08S_Meta::get($pid);
                if (($m['statut'] ?? 'actif') === 'archive') {
                    continue;
                }
                $types['sejour'] = self::TYPE_LABELS['sejour'] ?? 'Séjours';

                $dest = trim($m['destination'] ?? '');
                $pays = trim($m['pays'] ?? '');
                $flag = trim($m['flag'] ?? '');
                $display_val   = $pays !== '' ? $pays : $dest;
                $display_label = ($flag !== '' ? $flag . ' ' : '') . mb_convert_case($display_val, MB_CASE_TITLE, 'UTF-8');
                if ($dest !== '' || $pays !== '') {
                    $key = self::destination_aggregate_key($pays, $dest);
                    if ($key !== '' && !isset($destinations[$key])) {
                        $img = get_the_post_thumbnail_url($pid, 'large');
                        $destinations[$key] = [
                            'value' => $display_val,
                            'pays'  => $pays,
                            'flag'  => $flag,
                            'label' => $display_label,
                            'image' => $img ?: '',
                            'count' => 1,
                        ];
                    } elseif ($key !== '') {
                        $destinations[$key]['count'] = ($destinations[$key]['count'] ?? 1) + 1;
                    }
                    $push_type_dest($type_dest, 'sejour', $display_val);
                }

                if (!empty($m['aeroports']) && is_array($m['aeroports'])) {
                    foreach ($m['aeroports'] as $a) {
                        $code  = strtoupper(trim($a['code'] ?? ''));
                        $ville = trim($a['ville'] ?? '');
                        if ($code !== '' && !isset($airports[$code])) {
                            $airports[$code] = [
                                'code'  => $code,
                                'ville' => $ville,
                                'label' => $code . ' — ' . $ville,
                            ];
                        }
                        if ($code !== '' && $display_val !== '') {
                            $push_air_dest($airport_dest, $code, $display_val);
                        }
                    }
                }

                $d = intval($m['duree'] ?? 0);
                if ($d > 0) {
                    $durees[$d] = $d;
                }
            }
        }

        // Circuits
        if (class_exists('VS08C_Meta')) {
            $circuits = get_posts([
                'post_type'      => 'vs08_circuit',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ]);
            foreach ($circuits as $pid) {
                $m = VS08C_Meta::get($pid);
                if (($m['statut'] ?? '') === 'archive') {
                    continue;
                }
                $types['circuit'] = self::TYPE_LABELS['circuit'] ?? 'Circuits';

                $dest = trim($m['destination'] ?? '');
                $pays = trim($m['pays'] ?? '');
                $flag = class_exists('VS08C_Meta') ? VS08C_Meta::resolve_flag($m) : trim($m['flag'] ?? '');
                $display_val   = $pays !== '' ? $pays : $dest;
                $display_label = ($flag !== '' ? $flag . ' ' : '') . mb_convert_case($display_val, MB_CASE_TITLE, 'UTF-8');
                if ($dest !== '' || $pays !== '') {
                    $key = self::destination_aggregate_key($pays, $dest);
                    if ($key !== '' && !isset($destinations[$key])) {
                        $img = get_the_post_thumbnail_url($pid, 'large');
                        $destinations[$key] = [
                            'value' => $display_val,
                            'pays'  => $pays,
                            'flag'  => $flag,
                            'label' => $display_label,
                            'image' => $img ?: '',
                            'count' => 1,
                        ];
                    } elseif ($key !== '') {
                        $destinations[$key]['count'] = ($destinations[$key]['count'] ?? 1) + 1;
                    }
                    $push_type_dest($type_dest, 'circuit', $display_val);
                }

                if (!empty($m['aeroports']) && is_array($m['aeroports'])) {
                    foreach ($m['aeroports'] as $a) {
                        $code  = strtoupper(trim($a['code'] ?? ''));
                        $ville = trim($a['label'] ?? ($a['ville'] ?? ''));
                        if ($code !== '' && !isset($airports[$code])) {
                            $airports[$code] = [
                                'code'  => $code,
                                'ville' => $ville,
                                'label' => $code . ' — ' . $ville,
                            ];
                        }
                        if ($code !== '' && $display_val !== '') {
                            $push_air_dest($airport_dest, $code, $display_val);
                        }
                    }
                }

                $d = intval($m['duree'] ?? 0);
                if ($d > 0) {
                    $durees[$d] = $d;
                }
            }
        }

        ksort($types);
        uasort($destinations, function ($a, $b) {
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

    /* ============================================================
       Filtres catalogue / résultats-recherche (type, destination)
       ============================================================ */

    /**
     * Produit « golf » pour le catalogue : type explicite ou parcours renseignés
     * (les fiches anciennes ont souvent type_voyage vide tout en étant du golf).
     */
    public static function is_catalog_golf(array $m): bool {
        $tv = trim((string) ($m['type_voyage'] ?? ''));
        if ($tv === 'sejour_golf') {
            return true;
        }
        if (in_array($tv, ['circuit', 'road_trip', 'city_trip'], true)) {
            return false;
        }
        return (int) ($m['nb_parcours'] ?? 0) > 0;
    }

    /**
     * Correspondance du filtre ?type= sur une fiche vs08_voyage.
     */
    public static function voyage_matches_type_filter(array $m, string $requested): bool {
        $requested = trim($requested);
        if ($requested === '') {
            return true;
        }
        $tv = trim((string) ($m['type_voyage'] ?? ''));

        if ($requested === 'sejour_golf') {
            return self::is_catalog_golf($m);
        }

        if ($requested === 'sejour') {
            if (self::is_catalog_golf($m)) {
                return false;
            }
            return $tv === 'sejour' || $tv === '';
        }

        return $tv === $requested;
    }

    public static function normalize_search_token(string $s): string {
        $s = trim(wp_strip_all_tags($s));
        if ($s === '') {
            return '';
        }
        if (function_exists('mb_strtolower')) {
            $s = mb_strtolower($s, 'UTF-8');
        } else {
            $s = strtolower($s);
        }
        if (class_exists('Normalizer')) {
            $s = Normalizer::normalize($s, Normalizer::FORM_D);
            $s = preg_replace('/\pM/u', '', $s);
        }
        return trim($s);
    }

    /**
     * Filtre ?dest= : pays, destination, titre (ex. lien footer dest=marrakech).
     */
    public static function voyage_matches_dest(array $m, string $needle, int $post_id = 0): bool {
        $needle = trim($needle);
        if ($needle === '') {
            return true;
        }
        $candidates = [
            trim((string) ($m['destination'] ?? '')),
            trim((string) ($m['pays'] ?? '')),
        ];
        if ($post_id > 0) {
            $candidates[] = get_the_title($post_id);
        }
        $n_norm = self::normalize_search_token($needle);
        foreach ($candidates as $c) {
            if ($c === '') {
                continue;
            }
            if (function_exists('mb_stripos')) {
                if (mb_stripos($c, $needle, 0, 'UTF-8') !== false) {
                    return true;
                }
                $c_norm = self::normalize_search_token($c);
                if ($c_norm !== '' && $n_norm !== '' && mb_stripos($c_norm, $n_norm, 0, 'UTF-8') !== false) {
                    return true;
                }
            } else {
                if (stripos($c, $needle) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    /** Pays / zones affichés dans le méga-menu golf (données réelles uniquement). */
    public static function get_mega_menu_golf_countries(int $limit = 5): array {
        if (!class_exists('VS08V_MetaBoxes')) {
            return [];
        }
        $res    = home_url('/resultats-recherche');
        $ids    = get_posts([
            'post_type'      => 'vs08_voyage',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);
        $counts = [];
        foreach ($ids as $pid) {
            $m = VS08V_MetaBoxes::get($pid);
            if (($m['statut'] ?? '') === 'archive') {
                continue;
            }
            if (!self::is_catalog_golf($m)) {
                continue;
            }
            $pays = trim((string) ($m['pays'] ?? ''));
            $dest = trim((string) ($m['destination'] ?? ''));
            $key  = $pays !== '' ? $pays : $dest;
            if ($key === '') {
                continue;
            }
            if (!isset($counts[$key])) {
                $counts[$key] = ['n' => 0, 'flag' => VS08V_MetaBoxes::resolve_flag($m)];
            }
            $counts[$key]['n']++;
        }
        uasort($counts, static function ($a, $b) {
            return ($b['n'] <=> $a['n']);
        });
        $out = [];
        foreach ($counts as $label => $row) {
            if (count($out) >= $limit) {
                break;
            }
            $flag = $row['flag'] ?? '';
            if ($flag === '') {
                $flag = VS08V_MetaBoxes::get_flag_emoji($label);
            }
            $out[] = [
                'label' => $label,
                'flag'  => $flag,
                'url'   => add_query_arg(['type' => 'sejour_golf', 'dest' => $label], $res),
            ];
        }
        return $out;
    }

    /** Aéroports présents sur au moins une fiche golf (pas de liens vides). */
    public static function get_mega_menu_golf_airports(): array {
        if (!class_exists('VS08V_MetaBoxes')) {
            return [];
        }
        $known = [
            'CDG' => 'Paris Charles-de-Gaulle',
            'ORY' => 'Paris Orly',
            'LUX' => 'Luxembourg',
            'NTE' => 'Nantes Atlantique',
            'MRS' => 'Marseille Provence',
            'LYS' => 'Lyon Saint-Exupéry',
            'BOD' => 'Bordeaux Mérignac',
            'TLS' => 'Toulouse Blagnac',
            'NCE' => 'Nice Côte d\'Azur',
            'BRU' => 'Bruxelles',
        ];
        $res   = home_url('/resultats-recherche');
        $ids   = get_posts([
            'post_type'      => 'vs08_voyage',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);
        $found = [];
        foreach ($ids as $pid) {
            $m = VS08V_MetaBoxes::get($pid);
            if (($m['statut'] ?? '') === 'archive') {
                continue;
            }
            if (!self::is_catalog_golf($m)) {
                continue;
            }
            foreach (($m['aeroports'] ?? []) as $a) {
                $c = strtoupper(trim($a['code'] ?? ''));
                if ($c === '') {
                    continue;
                }
                $ville = trim($a['ville'] ?? '');
                if (!isset($found[$c])) {
                    $found[$c] = $known[$c] ?? ($ville !== '' ? $c . ' — ' . $ville : $c);
                }
            }
        }
        $out = [];
        foreach (array_keys($known) as $code) {
            if (!isset($found[$code])) {
                continue;
            }
            $out[] = [
                'code'  => $code,
                'label' => $found[$code],
                'url'   => add_query_arg(['type' => 'sejour_golf', 'airport' => $code], $res),
            ];
        }
        $extra = array_diff(array_keys($found), array_keys($known));
        sort($extra, SORT_STRING);
        foreach ($extra as $code) {
            $out[] = [
                'code'  => $code,
                'label' => $found[$code],
                'url'   => add_query_arg(['type' => 'sejour_golf', 'airport' => $code], $res),
            ];
        }
        return $out;
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
        // Séjours all inclusive
        if ($post->post_type === 'vs08_sejour' && class_exists('VS08S_Meta')) {
            $m = VS08S_Meta::get($post->ID);
            if (($m['statut'] ?? 'actif') === 'archive') return null;
            $thumb = get_the_post_thumbnail_url($post->ID, 'medium');
            if (!$thumb && !empty($m['galerie'][0])) $thumb = $m['galerie'][0];
            $sflag = $m['flag'] ?? '';
            if (!$sflag && class_exists('VS08V_MetaBoxes')) $sflag = VS08V_MetaBoxes::get_flag_emoji($m['pays'] ?? $m['destination'] ?? '');
            $prix = class_exists('VS08S_Calculator') ? VS08S_Calculator::prix_appel($post->ID) : 0;
            return [
                'id' => $post->ID, 'title' => get_the_title($post->ID), 'url' => get_permalink($post->ID),
                'thumbnail' => $thumb ?: '', 'destination' => $m['destination'] ?? '', 'pays' => $m['pays'] ?? '',
                'flag' => $sflag, 'prix' => $prix, 'has_vol' => true, 'vol_estimate' => false,
                'duree' => $m['duree'] ?? '', 'duree_jours' => $m['duree_jours'] ?? '',
                'nb_parcours' => '', 'niveau' => '', 'badge' => $m['badge'] ?? '',
                'hotel_nom' => $m['hotel_nom'] ?? '', 'post_type' => 'vs08_sejour', 'type_voyage' => 'sejour',
            ];
        }

        // Circuits
        if ($post->post_type === 'vs08_circuit' && class_exists('VS08C_Meta')) {
            $m = VS08C_Meta::get($post->ID);
            if (($m['statut'] ?? '') === 'archive') return null;
            $thumb = get_the_post_thumbnail_url($post->ID, 'medium');
            if (!$thumb) { $gal = $m['galerie'] ?? ($m['photos'] ?? []); $thumb = !empty($gal[0]) ? (is_array($gal[0]) ? ($gal[0]['url'] ?? '') : $gal[0]) : ''; }
            $cflag = class_exists('VS08C_Meta') ? VS08C_Meta::resolve_flag($m) : ($m['flag'] ?? '');
            return [
                'id' => $post->ID, 'title' => get_the_title($post->ID), 'url' => get_permalink($post->ID),
                'thumbnail' => $thumb ?: '', 'destination' => $m['destination'] ?? '', 'pays' => $m['pays'] ?? '',
                'flag' => $cflag, 'prix' => floatval($m['prix_double'] ?? 0), 'has_vol' => false, 'vol_estimate' => false,
                'duree' => $m['duree'] ?? '', 'duree_jours' => $m['duree_jours'] ?? '',
                'nb_parcours' => '', 'niveau' => '', 'badge' => $m['badge'] ?? '',
                'hotel_nom' => '', 'post_type' => 'vs08_circuit', 'type_voyage' => 'circuit',
            ];
        }

        // Golf (défaut)
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
