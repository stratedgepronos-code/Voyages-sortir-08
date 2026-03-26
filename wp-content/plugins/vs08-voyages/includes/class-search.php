<?php
if (!defined('ABSPATH')) exit;

class VS08V_Search {

    const TRANSIENT_KEY = 'vs08v_search_agg_v3';

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
        add_action('trashed_post',                 [__CLASS__, 'invalidate_cache']);
        add_action('untrashed_post',               [__CLASS__, 'invalidate_cache']);

        // Reset one-shot du cache vol opportuniste (v3 : ajout saisons au calcul)
        if (get_option('_vs08v_vol_cache_reset') !== 'v3') {
            add_action('init', [__CLASS__, 'reset_vol_cache_once']);
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

    public static function get_aggregated_options() {
        $cached = get_transient(self::TRANSIENT_KEY);
        if ($cached !== false) return $cached;

        $posts = get_posts([
            'post_type'      => 'vs08_voyage',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);

        $types        = [];
        $destinations = [];
        $airports     = [];
        $durees       = [];
        $dates        = [];
        $today        = date('Y-m-d');

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
            if ($dest) {
                $key = $pays ?: $dest;
                if (!isset($destinations[$key])) {
                    $img = get_the_post_thumbnail_url($pid, 'large');
                    if (!$img) {
                        $gal = $m['galerie'] ?? [];
                        $img = !empty($gal[0]) ? $gal[0] : '';
                    }
                    $destinations[$key] = [
                        'value'    => $dest,
                        'pays'     => $pays,
                        'flag'     => $flag,
                        'label'    => mb_convert_case($dest, MB_CASE_TITLE, 'UTF-8'),
                        'image'    => $img ?: '',
                        'count'    => 1,
                    ];
                } else {
                    $destinations[$key]['count'] = ($destinations[$key]['count'] ?? 1) + 1;
                }
            }

            if (!empty($m['aeroports']) && is_array($m['aeroports'])) {
                foreach ($m['aeroports'] as $a) {
                    $code  = strtoupper(trim($a['code'] ?? ''));
                    $ville = trim($a['ville'] ?? '');
                    if ($code && !isset($airports[$code])) {
                        $airports[$code] = [
                            'code'  => $code,
                            'ville' => $ville,
                            'label' => $code . ' — ' . $ville,
                        ];
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
                    if ($dt && $dt >= $today && $st !== 'complet') {
                        $dates[] = $dt;
                    }
                }
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
            'types'        => $types,
            'destinations' => array_values($destinations),
            'aeroports'    => array_values($airports),
            'durees'       => array_values($durees),
            'dates'        => array_values($dates),
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
