<?php
if (!defined('ABSPATH')) exit;

class VS08V_Homepage_Editor {
    const OPTION_SLOTS = 'vs08v_homepage_slots_v1';
    const SLOTS_COUNT  = 4;
    const OPTION_DEPARTS_SLOTS = 'vs08v_homepage_departs_slots_v1';
    const DEPARTS_SLOTS_COUNT  = 4;

    public static function register() {
        add_action('wp_ajax_vs08v_home_editor_search', [__CLASS__, 'ajax_search_products']);
        add_action('wp_ajax_vs08v_home_editor_destinations', [__CLASS__, 'ajax_destinations']);
        add_action('wp_ajax_vs08v_home_editor_products_by_destination', [__CLASS__, 'ajax_products_by_destination']);
        add_action('wp_ajax_vs08v_home_editor_save_slot', [__CLASS__, 'ajax_save_slot']);
        add_action('wp_ajax_vs08v_home_editor_save_departs_slot', [__CLASS__, 'ajax_save_departs_slot']);
        add_action('wp_ajax_vs08v_home_editor_save_generic', [__CLASS__, 'ajax_save_generic']);
        add_action('wp_footer', [__CLASS__, 'render_front_editor']);
    }

    public static function render_home_cards() {
        $cards = self::get_home_cards_data();
        if (empty($cards)) {
            return '<div class="scard anim"><div class="scard-body"><h3>Aucun séjour à afficher</h3><p class="scard-desc">Ajoutez des séjours publiés pour alimenter cette section.</p></div></div>';
        }

        $out = '';
        foreach ($cards as $idx => $c) {
            $is_featured = ($idx === 0);
            $classes = $is_featured ? 'scard scard-featured anim' : 'scard anim';
            if (self::can_edit_front()) $classes .= ' vs08-home-editable';
            $btn_txt = $is_featured ? 'Voir ce séjour →' : 'Voir →';
            $price_txt = !empty($c['prix']) ? number_format((int) $c['prix'], 0, ',', ' ') . '€' : 'Sur devis';
            $country = trim(($c['flag'] ? $c['flag'] . ' ' : '') . ($c['destination'] ?: $c['pays']));

            $out .= '<div class="' . esc_attr($classes) . '" data-vs08-home-slot="' . esc_attr((string) ($idx + 1)) . '" data-vs08-post-id="' . esc_attr((string) $c['id']) . '">';
            if (self::can_edit_front()) {
                $out .= '<button type="button" class="vs08-home-edit-btn" data-vs08-home-edit-slot="' . esc_attr((string) ($idx + 1)) . '" aria-label="Modifier ce produit">✏️</button>';
            }
            $out .= '<div class="scard-img">';
            if (!empty($c['badge_html'])) {
                $out .= '<div class="scard-badges">' . $c['badge_html'] . '</div>';
            }
            $out .= '<img src="' . esc_url($c['img']) . '" alt="' . esc_attr($c['title']) . '">';
            $out .= '</div>';
            $out .= '<div class="scard-body">';
            $out .= '<p class="scard-country">' . esc_html($country) . '</p>';
            $out .= '<h3>' . esc_html($c['title']) . '</h3>';
            $out .= '<p class="scard-desc">' . esc_html($c['desc']) . '</p>';
            $out .= '<div class="scard-highlights">';
            foreach ($c['highlights'] as $hl) {
                $out .= '<span class="scard-chip">' . esc_html($hl) . '</span>';
            }
            $out .= '</div>';
            $out .= '<div class="scard-golfs">';
            foreach ($c['golfs'] as $g) {
                $out .= '<div class="scard-golf-chip"><span class="gchip-icon">⛳</span><div><span class="gchip-name">' . esc_html($g['name']) . '</span>';
                if (!empty($g['meta'])) {
                    $out .= '<br><span class="gchip-holes">' . esc_html($g['meta']) . '</span>';
                }
                $out .= '</div></div>';
            }
            $out .= '</div>';
            $out .= '<div class="scard-divider"></div>';
            $out .= '<div class="scard-footer">';
            $out .= '<div class="scard-price"><span class="price-label">Dès</span><span class="price-amount">' . esc_html($price_txt) . '</span><span class="price-per">/personne · tout compris</span></div>';
            $out .= '<a href="' . esc_url($c['url']) . '" class="scard-btn">' . esc_html($btn_txt) . '</a>';
            $out .= '</div></div></div>';
        }

        return $out;
    }

    /** Section "Partez bientôt" : cartes éditables comme coups de cœur */
    public static function render_departs_cards() {
        $cards = self::get_departs_cards_data();
        if (empty($cards)) {
            return '<div class="dcard anim"><div class="dcard-body"><p class="dcard-dest">Aucun départ à afficher</p><h4>Ajoutez des séjours dans l’éditeur</h4></div></div>';
        }
        $out = '';
        foreach ($cards as $idx => $c) {
            $price_txt = !empty($c['prix']) ? number_format((int) $c['prix'], 0, ',', ' ') . '€' : 'Sur devis';
            $country = trim(($c['flag'] ? $c['flag'] . ' ' : '') . ($c['destination'] ?: $c['pays']));
            $urgence = !empty($c['urgence']) ? '<span class="dcard-urgence">' . esc_html($c['urgence']) . '</span>' : '';
            $classes = 'dcard anim';
            if (self::can_edit_front()) $classes .= ' vs08-home-editable';
            $out .= '<div class="' . esc_attr($classes) . '" data-vs08-home-section="departs" data-vs08-home-slot="' . esc_attr((string) ($idx + 1)) . '" data-vs08-post-id="' . esc_attr((string) $c['id']) . '">';
            if (self::can_edit_front()) {
                $out .= '<button type="button" class="vs08-home-edit-btn vs08-home-edit-btn-departs" data-vs08-home-section="departs" data-vs08-home-edit-slot="' . esc_attr((string) ($idx + 1)) . '" aria-label="Modifier ce départ">✏️</button>';
            }
            $out .= '<div class="dcard-img"><img src="' . esc_url($c['img']) . '" alt="' . esc_attr($c['title']) . '">' . $urgence . '</div>';
            $out .= '<div class="dcard-body">';
            $out .= '<p class="dcard-dest">' . esc_html($country) . '</p>';
            $out .= '<h4>' . esc_html($c['title']) . '</h4>';
            $infos = [];
            if (!empty($c['dates_txt'])) $infos[] = '📅 ' . $c['dates_txt'];
            if (!empty($c['vol_txt'])) $infos[] = '✈️ ' . $c['vol_txt'];
            $infos[] = '🌙 ' . max(1, (int) ($c['duree'] ?? 7)) . ' nuits';
            if (!empty($c['nb_parcours'])) $infos[] = '⛳ ' . $c['nb_parcours'] . ' parcours';
            $out .= '<div class="dcard-infos">' . implode('', array_map(function ($i) { return '<span>' . esc_html($i) . '</span>'; }, $infos)) . '</div>';
            $out .= '<div class="dcard-bottom"><div class="dcard-price">' . esc_html($price_txt) . ' <small>/pers</small></div><a href="' . esc_url($c['url']) . '" class="dcard-btn">Voir →</a></div>';
            $out .= '</div></div>';
        }
        return $out;
    }

    private static function get_departs_cards_data() {
        $slots = self::get_departs_slots();
        $ids = array_values(array_filter(array_map('intval', $slots)));
        $cards = [];
        $used = [];
        foreach ($ids as $id) {
            if (isset($used[$id])) continue;
            $c = self::build_departs_card_data($id);
            if ($c) {
                $cards[] = $c;
                $used[$id] = true;
            }
        }
        if (count($cards) < self::DEPARTS_SLOTS_COUNT) {
            $fallback = get_posts([
                'post_type'      => 'vs08_voyage',
                'post_status'    => 'publish',
                'posts_per_page' => 12,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'fields'         => 'ids',
            ]);
            foreach ($fallback as $id) {
                if (isset($used[$id])) continue;
                $c = self::build_departs_card_data($id);
                if (!$c) continue;
                $cards[] = $c;
                $used[$id] = true;
                if (count($cards) >= self::DEPARTS_SLOTS_COUNT) break;
            }
        }
        return array_slice($cards, 0, self::DEPARTS_SLOTS_COUNT);
    }

    private static function build_departs_card_data($post_id) {
        $c = self::build_card_data($post_id);
        if (!$c) return null;
        $m = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($post_id) : [];
        $dates_depart = $m['dates_depart'] ?? [];
        $first_date = null;
        if (is_array($dates_depart)) {
            foreach ($dates_depart as $dd) {
                $d = $dd['date'] ?? '';
                if ($d && ($dd['statut'] ?? '') !== 'complet') {
                    $first_date = $d;
                    break;
                }
            }
        }
        $dates_txt = $first_date ? date_i18n('j M — j M Y', strtotime($first_date)) : '';
        $aeroports = $m['aeroports'] ?? [];
        $vol_txt = 'Vol depuis Paris';
        if (!empty($aeroports) && is_array($aeroports)) {
            $first = reset($aeroports);
            $code = $first['code'] ?? '';
            $ville = $first['ville'] ?? '';
            if ($code) $vol_txt = 'Vol direct ' . $ville . ' ' . $code;
        }
        $c['dates_txt'] = $dates_txt;
        $c['vol_txt'] = $vol_txt;
        $c['duree'] = $m['duree'] ?? 7;
        $c['nb_parcours'] = $m['nb_parcours'] ?? '';
        $c['urgence'] = '';
        return $c;
    }

    private static function get_departs_slots() {
        $slots = get_option(self::OPTION_DEPARTS_SLOTS, []);
        if (!is_array($slots)) $slots = [];
        $out = [];
        for ($i = 1; $i <= self::DEPARTS_SLOTS_COUNT; $i++) {
            $out[$i] = intval($slots[$i] ?? 0);
        }
        return $out;
    }

    private static function get_home_cards_data() {
        $slots = self::get_slots();
        $ids = array_values(array_filter(array_map('intval', $slots)));
        $cards = [];
        $used = [];

        foreach ($ids as $id) {
            if (isset($used[$id])) continue;
            $c = self::build_card_data($id);
            if ($c) {
                $cards[] = $c;
                $used[$id] = true;
            }
        }

        if (count($cards) < self::SLOTS_COUNT) {
            $fallback = get_posts([
                'post_type'      => 'vs08_voyage',
                'post_status'    => 'publish',
                'posts_per_page' => 12,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'fields'         => 'ids',
            ]);
            foreach ($fallback as $id) {
                if (isset($used[$id])) continue;
                $c = self::build_card_data($id);
                if (!$c) continue;
                $cards[] = $c;
                $used[$id] = true;
                if (count($cards) >= self::SLOTS_COUNT) break;
            }
        }

        return array_slice($cards, 0, self::SLOTS_COUNT);
    }

    /**
     * Carte « coups de cœur » pour un circuit (même grille HTML que le golf).
     */
    private static function build_circuit_card_data(\WP_Post $post) {
        if (!class_exists('VS08C_Meta')) {
            return null;
        }
        $post_id = (int) $post->ID;
        $m       = VS08C_Meta::get($post_id);
        $dest    = trim((string) ($m['destination'] ?? ''));
        $pays    = trim((string) ($m['pays'] ?? ''));
        $flag    = VS08C_Meta::resolve_flag($m);

        $img = get_the_post_thumbnail_url($post_id, 'large');
        if (!$img && !empty($m['galerie'][0])) {
            $img = $m['galerie'][0];
        }
        if (!$img) {
            $img = 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=1000&q=80';
        }

        $desc = trim(wp_strip_all_tags((string) get_the_excerpt($post_id)));
        if ($desc === '') {
            $desc = wp_trim_words(wp_strip_all_tags((string) $post->post_content), 22, '…');
        }
        if ($desc === '') {
            $desc = 'Circuit accompagné : découvrez les incontournables avec un guide francophone.';
        }

        $badge_html = self::build_badge_html($m['badge'] ?? '');

        $golfs = [];
        $hotels = $m['hotels'] ?? [];
        if (is_array($hotels)) {
            foreach ($hotels as $h) {
                if (!is_array($h)) {
                    continue;
                }
                $name = trim((string) ($h['nom'] ?? $h['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $golfs[] = ['name' => $name, 'meta' => ''];
                if (count($golfs) >= 3) {
                    break;
                }
            }
        }
        if (empty($golfs)) {
            $dj = (int) ($m['duree_jours'] ?? 0);
            $golfs[] = [
                'name' => $dj > 0 ? ('🗺️ ' . $dj . ' jours') : 'Circuit organisé',
                'meta' => '',
            ];
        }

        $prix = 0;
        if (class_exists('VS08C_Search')) {
            $prix = (int) round((float) VS08C_Search::get_prix_min_for_circuit($m));
        }
        if ($prix <= 0) {
            $prix = (int) round((float) get_post_meta($post_id, 'vs08c_prix_min', true));
        }
        if ($prix <= 0 && !empty($m['prix_double'])) {
            $prix = (int) round((float) $m['prix_double']);
        }

        $duree_j  = (int) ($m['duree_jours'] ?? 0);
        $dur_chip = $duree_j > 0 ? '🗓️ ' . $duree_j . ' jours' : '';

        $transp_map = [
            'bus'     => '🚌 Bus clim.',
            '4x4'     => '🚙 4×4',
            'voiture' => '🚗 Voiture',
            'train'   => '🚄 Train',
            'mixed'   => '🚐 Transport',
        ];
        $transp_key = (string) ($m['transport'] ?? 'bus');
        $trans_lbl  = $transp_map[$transp_key] ?? '';

        $pension_map = [
            'bb'    => '☕ Petit-déj.',
            'dp'    => '🍽️ Demi-pension',
            'pc'    => '🍽️ Pension complète',
            'ai'    => '🍽️ All inclusive',
            'mixed' => '🍽️ Selon programme',
        ];
        $pension = strtolower((string) ($m['pension'] ?? ''));
        $pen_lbl = ($pension !== '' && isset($pension_map[$pension])) ? $pension_map[$pension] : '';

        $highlights = array_values(array_filter([
            $dur_chip,
            $trans_lbl,
            $pen_lbl,
            !empty($m['guide_lang']) ? '🗣️ ' . trim((string) $m['guide_lang']) : '',
            '✈️ Vol',
            '🗺️ Circuit',
        ]));

        return [
            'id'          => $post_id,
            'title'       => get_the_title($post_id),
            'url'         => get_permalink($post_id),
            'img'         => $img,
            'flag'        => $flag,
            'destination' => $dest,
            'pays'        => $pays,
            'hotel_nom'   => '',
            'etoiles'     => '',
            'desc'        => $desc,
            'badge_html'  => $badge_html,
            'golfs'       => $golfs,
            'highlights'  => array_slice($highlights, 0, 9),
            'prix'        => $prix,
        ];
    }

    private static function build_card_data($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return null;
        }
        if ($post->post_type === 'vs08_circuit') {
            return self::build_circuit_card_data($post);
        }
        if ($post->post_type !== 'vs08_voyage') {
            return null;
        }

        $m = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($post_id) : [];
        if (($m['statut'] ?? 'actif') === 'archive') return null;

        $dest = trim((string) ($m['destination'] ?? ''));
        $pays = trim((string) ($m['pays'] ?? ''));
        $flag = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::resolve_flag($m) : trim((string) ($m['flag'] ?? ''));

        $img = get_the_post_thumbnail_url($post_id, 'large');
        if (!$img) {
            $gal = $m['galerie'] ?? [];
            $img = !empty($gal[0]) ? $gal[0] : 'https://images.unsplash.com/photo-1587174486073-ae5e5cff23aa?w=1000&q=80';
        }

        $hotel = $m['hotel'] ?? [];
        $hotel_nom = trim((string) ($hotel['nom'] ?? ($m['hotel_nom'] ?? 'Hôtel sélectionné')));
        $etoiles_n = intval($hotel['etoiles'] ?? ($m['hotel_etoiles'] ?? 5));
        if ($etoiles_n < 1) $etoiles_n = 5;
        $etoiles = str_repeat('★', min(5, $etoiles_n));

        $desc = trim((string) ($m['desc'] ?? ''));
        if ($desc === '') {
            $desc = wp_strip_all_tags((string) get_the_excerpt($post_id));
        }
        if ($desc === '') {
            $desc = wp_trim_words(wp_strip_all_tags((string) $post->post_content), 22, '...');
        }
        if ($desc === '') {
            $desc = 'Séjour golf tout compris, parcours d’exception et accompagnement premium.';
        }

        $badge_html = self::build_badge_html($m['badge'] ?? '');

        $golfs = [];
        $golfs_src = $m['golfs'] ?? [];
        if (!is_array($golfs_src) || empty($golfs_src)) {
            $golfs_src = $hotel['golfs'] ?? [];
        }
        if (is_array($golfs_src)) {
            foreach ($golfs_src as $g) {
                if (!is_array($g)) {
                    continue;
                }
                $name = trim((string) ($g['nom'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $holes = trim((string) ($g['trous'] ?? ''));
                $meta  = $holes !== '' ? ($holes . ' trous') : '';
                $golfs[] = ['name' => $name, 'meta' => $meta];
                if (count($golfs) >= 3) {
                    break;
                }
            }
        }
        if (empty($golfs)) {
            $nb = intval($m['nb_parcours'] ?? 0);
            if ($nb > 0) {
                $golfs[] = ['name' => 'Parcours sélectionnés', 'meta' => $nb . ' green fees'];
            } else {
                $golfs[] = ['name' => 'Parcours partenaires', 'meta' => ''];
            }
        }

        $prix = 0;
        if (class_exists('VS08V_Search')) {
            $prix_data = VS08V_Search::compute_prix_appel($m, $post_id);
            $prix = intval($prix_data['prix'] ?? 0);
        }

        $duree_n = intval($m['duree'] ?? 0);
        $duree_j = intval($m['duree_jours'] ?? 0);
        if ($duree_j < 1 && $duree_n > 0) {
            $duree_j = $duree_n + 1;
        }
        $dur_chip = '';
        if ($duree_j > 0 && $duree_n > 0) {
            $dur_chip = '🗓️ ' . $duree_j . 'J / ' . $duree_n . 'N';
        } elseif ($duree_n > 0) {
            $dur_chip = '🗓️ ' . $duree_n . 'N';
        } elseif ($duree_j > 0) {
            $dur_chip = '🗓️ ' . $duree_j . 'J';
        }

        $golfs_list = $m['golfs'] ?? [];
        $nb_parcours = is_array($golfs_list) ? count($golfs_list) : 0;
        if ($nb_parcours < 1) {
            $nb_parcours = intval($m['nb_parcours'] ?? 0);
        }

        $transf_map = [
            'groupes' => '🚌 Transferts groupés',
            'prives'  => '🚐 Transferts privés',
            'voiture' => '🚗 Location voiture',
        ];
        $transfert_type = (string) ($m['transfert_type'] ?? '');
        $transf_lbl     = $transf_map[$transfert_type] ?? '';

        $tt = (string) ($m['transport_type'] ?? 'vol');
        $vol_lbl = '';
        if ($tt === 'vol' || $tt === '') {
            $vol_lbl = '✈️ Vols inclus';
        } elseif ($tt === 'vol_option') {
            $vol_lbl = '✈️ Vol en option';
        } elseif ($tt === 'sans_vol') {
            $vol_lbl = '🏨 Sans vol (hôtel seul)';
        } elseif ($tt === 'voiture') {
            $vol_lbl = '🚗 Accès en voiture';
        }

        $highlights = [];
        if ($dur_chip !== '') {
            $highlights[] = $dur_chip;
        }
        if ($nb_parcours > 0) {
            $highlights[] = '⛳ ' . $nb_parcours . ' parcours';
        }
        if ($transf_lbl !== '') {
            $highlights[] = $transf_lbl;
        }
        $pension_map = ['bb' => '☕ Petit-déjeuner', 'dp' => '🍽️ Demi-pension', 'pc' => '🍽️ Pension complète', 'ai' => '🍽️ Tout inclus'];
        $pension = strtolower((string) ($m['pension'] ?? ''));
        if ($pension && isset($pension_map[$pension])) {
            $highlights[] = $pension_map[$pension];
        }
        if ($vol_lbl !== '') {
            $highlights[] = $vol_lbl;
        }
        $highlights[] = '🧳 Bagage soute & sac golf inclus';
        if (($m['buggy'] ?? '') === 'inclus') {
            $highlights[] = '🛞 Buggy inclus';
        }

        return [
            'id'         => $post_id,
            'title'      => get_the_title($post_id),
            'url'        => get_permalink($post_id),
            'img'        => $img,
            'flag'       => $flag,
            'destination'=> $dest,
            'pays'       => $pays,
            'hotel_nom'  => $hotel_nom,
            'etoiles'    => $etoiles,
            'desc'       => $desc,
            'badge_html' => $badge_html,
            'golfs'      => $golfs,
            'highlights' => array_slice($highlights, 0, 9),
            'prix'       => $prix,
        ];
    }

    private static function build_badge_html($badge) {
        $badge = trim((string) $badge);
        if ($badge === '') return '<span class="badge badge-new">Nouveauté</span>';
        $map = [
            'new'      => ['badge-new', 'Nouveauté'],
            'promo'    => ['badge-promo', 'Promo'],
            'best'     => ['badge-best', 'Best-seller'],
            'derniere' => ['badge-promo', 'Dernières places'],
        ];
        if (!isset($map[$badge])) return '';
        return '<span class="badge ' . esc_attr($map[$badge][0]) . '">' . esc_html($map[$badge][1]) . '</span>';
    }

    private static function can_edit_front() {
        return is_user_logged_in() && current_user_can('manage_options') && is_front_page();
    }

    private static function get_slots() {
        $slots = get_option(self::OPTION_SLOTS, []);
        if (!is_array($slots)) $slots = [];
        $out = [];
        for ($i = 1; $i <= self::SLOTS_COUNT; $i++) {
            $out[$i] = intval($slots[$i] ?? 0);
        }
        return $out;
    }

    public static function render_front_editor() {
        if (!self::can_edit_front()) return;
        $nonce = wp_create_nonce('vs08v_home_editor');
        ?>
        <style>
        .vs08-home-editable{position:relative}
        .vs08-home-edit-btn{position:absolute;top:10px;right:10px;z-index:5;border:none;background:#0f2424;color:#fff;width:34px;height:34px;border-radius:999px;cursor:pointer;font-size:15px;box-shadow:0 4px 12px rgba(0,0,0,.2)}
        .vs08-home-edit-btn:hover{background:#59b7b7}
        .vs08-home-editor-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);display:none;align-items:center;justify-content:center;z-index:99999}
        .vs08-home-editor{width:min(96vw,680px);background:#fff;border-radius:14px;padding:18px;box-shadow:0 12px 44px rgba(0,0,0,.25);font-family:Outfit,sans-serif}
        .vs08-home-editor h3{margin:0 0 12px;font-size:19px}
        .vs08-home-editor .row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
        .vs08-home-editor .field{margin-bottom:10px}
        .vs08-home-editor label{display:block;font-size:12px;font-weight:700;margin-bottom:4px;color:#374151}
        .vs08-home-editor input,.vs08-home-editor select{width:100%;padding:10px 11px;border:1px solid #d1d5db;border-radius:9px;font-size:14px}
        .vs08-home-results{max-height:180px;overflow:auto;border:1px solid #e5e7eb;border-radius:9px}
        .vs08-home-results button{width:100%;text-align:left;border:none;background:#fff;padding:10px 11px;cursor:pointer;border-bottom:1px solid #f1f5f9}
        .vs08-home-results button:hover{background:#f8fafc}
        .vs08-home-editor .actions{display:flex;justify-content:flex-end;gap:8px;margin-top:12px}
        .vs08-home-editor .btn{border:none;border-radius:8px;padding:9px 13px;cursor:pointer;font-weight:600}
        .vs08-home-editor .btn-cancel{background:#f3f4f6;color:#111827}
        .vs08-home-editor .btn-save{background:#0f2424;color:#fff}
        .vs08-home-editor .btn-save:hover{background:#59b7b7}
        .vs08-home-picked{font-size:13px;color:#0f766e;background:#ecfeff;border:1px solid #a5f3fc;padding:7px 9px;border-radius:8px;display:none}
        @media(max-width:640px){.vs08-home-editor .row{grid-template-columns:1fr}}
        </style>
        <div class="vs08-home-editor-overlay" id="vs08-home-editor-overlay" aria-hidden="true">
            <div class="vs08-home-editor">
                <h3>✏️ Remplacer ce produit</h3>
                <p style="margin:0 0 10px;color:#6b7280;font-size:13px">Méthode 1 : recherche par nom (tous types). Méthode 2 : type de produit, destination, puis produit.</p>
                <div class="field">
                    <label>1) Rechercher par nom</label>
                    <input type="text" id="vs08-home-search-input" placeholder="Ex: Kenzi, Algarve, Marbella...">
                </div>
                <div class="vs08-home-results" id="vs08-home-search-results"></div>
                <div class="field" style="margin-top:10px">
                    <label>2) Type de produit</label>
                    <select id="vs08-home-type-select">
                        <option value="">Tous (golf + circuits)</option>
                        <option value="vs08_voyage">⛳ Séjour golf</option>
                        <option value="vs08_circuit">🗺️ Circuit</option>
                    </select>
                </div>
                <div class="row" style="margin-top:10px">
                    <div class="field">
                        <label>3) Destination</label>
                        <select id="vs08-home-dest-select"><option value="">Choisir...</option></select>
                    </div>
                    <div class="field">
                        <label>4) Produit</label>
                        <select id="vs08-home-product-select"><option value="">Choisir...</option></select>
                    </div>
                </div>
                <div class="vs08-home-picked" id="vs08-home-picked"></div>
                <div class="actions">
                    <button type="button" class="btn btn-cancel" id="vs08-home-cancel">Annuler</button>
                    <button type="button" class="btn btn-save" id="vs08-home-save">Remplacer le produit</button>
                </div>
            </div>
        </div>
        <script>
        (function(){
            var overlay = document.getElementById('vs08-home-editor-overlay');
            if (!overlay) return;
            var searchInput = document.getElementById('vs08-home-search-input');
            var searchResults = document.getElementById('vs08-home-search-results');
            var typeSelect = document.getElementById('vs08-home-type-select');
            var destSelect = document.getElementById('vs08-home-dest-select');
            var productSelect = document.getElementById('vs08-home-product-select');
            var picked = document.getElementById('vs08-home-picked');
            var btnSave = document.getElementById('vs08-home-save');
            var currentSlot = 0;
            var currentSection = 'coups';
            var selectedProductId = 0;
            var selectedProductLabel = '';
            var debounceTimer = null;

            function post(action, payload) {
                var fd = new FormData();
                fd.append('action', action);
                fd.append('nonce', <?php echo wp_json_encode($nonce); ?>);
                Object.keys(payload || {}).forEach(function(k){ fd.append(k, payload[k]); });
                return fetch(<?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>, { method:'POST', credentials:'same-origin', body:fd }).then(function(r){ return r.json(); });
            }
            function open(slot, section) {
                currentSlot = slot;
                currentSection = section || 'coups';
                var sectionInput = document.getElementById('vs08-home-editor-section');
                var titleEl = document.getElementById('vs08-home-editor-title');
                if (sectionInput) sectionInput.value = currentSection;
                if (titleEl) titleEl.textContent = currentSection === 'departs' ? '✏️ Remplacer ce départ' : '✏️ Remplacer ce produit';
                selectedProductId = 0;
                selectedProductLabel = '';
                picked.style.display = 'none';
                searchInput.value = '';
                searchResults.innerHTML = '';
                if (typeSelect) typeSelect.value = '';
                destSelect.innerHTML = '<option value="">Choisir...</option>';
                productSelect.innerHTML = '<option value="">Choisir...</option>';
                overlay.style.display = 'flex';
                overlay.setAttribute('aria-hidden', 'false');
                loadDestinations();
                setTimeout(function(){ searchInput.focus(); }, 30);
            }
            function close() {
                overlay.style.display = 'none';
                overlay.setAttribute('aria-hidden', 'true');
            }
            function pick(id, label) {
                selectedProductId = parseInt(id || 0, 10);
                selectedProductLabel = label || '';
                if (selectedProductId > 0) {
                    picked.textContent = 'Produit choisi : ' + selectedProductLabel;
                    picked.style.display = 'block';
                } else {
                    picked.style.display = 'none';
                }
            }
            function renderSearchResults(items) {
                if (!items || !items.length) {
                    searchResults.innerHTML = '<div style="padding:10px;color:#6b7280;font-size:13px">Aucun résultat</div>';
                    return;
                }
                searchResults.innerHTML = items.map(function(it){
                    var txt = (it.flag ? it.flag + ' ' : '') + (it.destination || it.pays || '') + ' — ' + it.title;
                    return '<button type="button" data-id="'+it.id+'" data-label="'+txt.replace(/"/g,'&quot;')+'">'+txt+'</button>';
                }).join('');
            }
            function currentProductType() {
                return typeSelect ? (typeSelect.value || '') : '';
            }
            function loadDestinations() {
                post('vs08v_home_editor_destinations', { post_type: currentProductType() }).then(function(res){
                    if (!res || !res.success || !res.data) return;
                    var opts = '<option value="">Choisir...</option>';
                    res.data.forEach(function(d){ opts += '<option value="'+d.value+'">'+d.label+'</option>'; });
                    destSelect.innerHTML = opts;
                });
            }
            function loadProductsByDestination(destination) {
                if (!destination) {
                    productSelect.innerHTML = '<option value="">Choisir...</option>';
                    return;
                }
                post('vs08v_home_editor_products_by_destination', { destination: destination, post_type: currentProductType() }).then(function(res){
                    var opts = '<option value="">Choisir...</option>';
                    if (res && res.success && res.data) {
                        res.data.forEach(function(p){
                            var txt = (p.flag ? p.flag + ' ' : '') + p.title;
                            opts += '<option value="'+p.id+'" data-label="'+txt.replace(/"/g,'&quot;')+'">'+txt+'</option>';
                        });
                    }
                    productSelect.innerHTML = opts;
                });
            }

            document.querySelectorAll('[data-vs08-home-edit-slot]').forEach(function(btn){
                btn.addEventListener('click', function(e){
                    e.preventDefault();
                    e.stopPropagation();
                    var section = btn.getAttribute('data-vs08-home-section') || 'coups';
                    open(parseInt(btn.getAttribute('data-vs08-home-edit-slot') || '0', 10), section);
                });
            });

            // ══ AUTO-DISCOVER: ajouter ✏️ sur TOUTES les cards produit de la homepage ══
            var sectionSelectors = [
                { selector: '.sh-card', section: 'golf_showcase', parent: '.sh-grid' },
                { selector: '.dl-item', section: 'circuits', parent: '.dl-grid' },
                { selector: '.fp-ucard', section: 'univers', parent: '.fp-bento' },
            ];
            sectionSelectors.forEach(function(cfg) {
                var cards = document.querySelectorAll(cfg.selector);
                cards.forEach(function(card, idx) {
                    if (card.querySelector('.vs08-home-edit-btn')) return;
                    card.style.position = 'relative';
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'vs08-home-edit-btn';
                    btn.setAttribute('aria-label', 'Modifier ce produit');
                    btn.textContent = '✏️';
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        open(idx + 1, cfg.section);
                    });
                    card.appendChild(btn);
                });
            });
            document.getElementById('vs08-home-cancel').addEventListener('click', close);
            overlay.addEventListener('click', function(e){ if (e.target === overlay) close(); });

            searchInput.addEventListener('input', function(){
                var q = (searchInput.value || '').trim();
                clearTimeout(debounceTimer);
                if (q.length < 2) {
                    searchResults.innerHTML = '';
                    return;
                }
                debounceTimer = setTimeout(function(){
                    post('vs08v_home_editor_search', { q: q }).then(function(res){
                        if (!res || !res.success) return renderSearchResults([]);
                        renderSearchResults(res.data || []);
                    });
                }, 220);
            });
            searchResults.addEventListener('click', function(e){
                var btn = e.target.closest('button[data-id]');
                if (!btn) return;
                pick(btn.getAttribute('data-id'), btn.getAttribute('data-label'));
            });
            if (typeSelect) {
                typeSelect.addEventListener('change', function(){
                    destSelect.innerHTML = '<option value="">Choisir...</option>';
                    productSelect.innerHTML = '<option value="">Choisir...</option>';
                    loadDestinations();
                });
            }
            destSelect.addEventListener('change', function(){
                loadProductsByDestination(destSelect.value);
            });
            productSelect.addEventListener('change', function(){
                var opt = productSelect.options[productSelect.selectedIndex];
                pick(productSelect.value, opt ? (opt.getAttribute('data-label') || opt.textContent) : '');
            });
            btnSave.addEventListener('click', function(){
                if (!currentSlot || !selectedProductId) {
                    alert('Choisissez un produit avant de valider.');
                    return;
                }
                var action;
                if (currentSection === 'coups') action = 'vs08v_home_editor_save_slot';
                else if (currentSection === 'departs') action = 'vs08v_home_editor_save_departs_slot';
                else action = 'vs08v_home_editor_save_generic';

                var payload = { slot: currentSlot, product_id: selectedProductId };
                if (action === 'vs08v_home_editor_save_generic') payload.section = currentSection;

                btnSave.disabled = true;
                btnSave.textContent = 'Enregistrement...';
                post(action, payload).then(function(res){
                    if (res && res.success) {
                        window.location.reload();
                        return;
                    }
                    alert((res && res.data) ? res.data : 'Erreur de sauvegarde.');
                }).finally(function(){
                    btnSave.disabled = false;
                    btnSave.textContent = 'Remplacer le produit';
                });
            });
        })();
        </script>
        <?php
    }

    public static function ajax_search_products() {
        self::guard_ajax();
        $q = sanitize_text_field(wp_unslash($_POST['q'] ?? ''));
        if (mb_strlen($q) < 2) wp_send_json_success([]);

        $out = [];

        // Séjours golf
        $ids = get_posts(['post_type' => 'vs08_voyage', 'post_status' => 'publish', 'posts_per_page' => 8, 's' => $q, 'fields' => 'ids']);
        foreach ($ids as $id) {
            $m = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($id) : [];
            if (($m['statut'] ?? 'actif') === 'archive') continue;
            $out[] = [
                'id' => $id, 'title' => get_the_title($id), 'type' => 'golf',
                'destination' => $m['destination'] ?? '', 'pays' => $m['pays'] ?? '',
                'flag' => class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::resolve_flag($m) : ($m['flag'] ?? ''),
            ];
        }

        // Circuits
        $cids = get_posts(['post_type' => 'vs08_circuit', 'post_status' => 'publish', 'posts_per_page' => 8, 's' => $q, 'fields' => 'ids']);
        foreach ($cids as $id) {
            $m = class_exists('VS08C_Meta') ? VS08C_Meta::get($id) : [];
            $out[] = [
                'id' => $id, 'title' => '🗺️ ' . get_the_title($id), 'type' => 'circuit',
                'destination' => $m['destination'] ?? '', 'pays' => $m['pays'] ?? '',
                'flag' => $m['flag'] ?? '',
            ];
        }

        wp_send_json_success($out);
    }

    /**
     * Filtre optionnel : vs08_voyage | vs08_circuit | vide = les deux.
     */
    private static function parse_home_editor_post_type_filter() {
        $raw = sanitize_text_field(wp_unslash($_POST['post_type'] ?? ''));
        if ($raw === 'vs08_voyage' || $raw === 'vs08_circuit') {
            return $raw;
        }
        return '';
    }

    public static function ajax_destinations() {
        self::guard_ajax();
        $filter = self::parse_home_editor_post_type_filter();
        $map = [];

        if ($filter === '' || $filter === 'vs08_voyage') {
            $ids = get_posts(['post_type' => 'vs08_voyage', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids']);
            foreach ($ids as $id) {
                $m = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($id) : [];
                if (($m['statut'] ?? 'actif') === 'archive') continue;
                $dest = trim((string) ($m['destination'] ?? ''));
                if ($dest === '' || isset($map[$dest])) continue;
                $flag = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::resolve_flag($m) : ($m['flag'] ?? '');
                $map[$dest] = ['value' => $dest, 'label' => trim(($flag ? $flag . ' ' : '') . $dest)];
            }
        }

        if ($filter === '' || $filter === 'vs08_circuit') {
            $cids = get_posts(['post_type' => 'vs08_circuit', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids']);
            foreach ($cids as $id) {
                $m = class_exists('VS08C_Meta') ? VS08C_Meta::get($id) : [];
                $dest = trim((string) ($m['destination'] ?? ''));
                if ($dest === '' || isset($map[$dest])) continue;
                $map[$dest] = ['value' => $dest, 'label' => trim(($m['flag'] ?? '') . ' ' . $dest)];
            }
        }

        usort($map, function($a, $b){ return strcmp($a['label'], $b['label']); });
        wp_send_json_success(array_values($map));
    }

    public static function ajax_products_by_destination() {
        self::guard_ajax();
        $destination = trim(sanitize_text_field(wp_unslash($_POST['destination'] ?? '')));
        if ($destination === '') wp_send_json_success([]);
        $filter = self::parse_home_editor_post_type_filter();
        $out = [];

        if ($filter === '' || $filter === 'vs08_voyage') {
            $ids = get_posts(['post_type' => 'vs08_voyage', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids']);
            foreach ($ids as $id) {
                $m = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($id) : [];
                if (($m['statut'] ?? 'actif') === 'archive') continue;
                if (trim((string) ($m['destination'] ?? '')) !== $destination) continue;
                $out[] = ['id' => $id, 'title' => '⛳ ' . get_the_title($id), 'flag' => class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::resolve_flag($m) : ($m['flag'] ?? '')];
            }
        }

        if ($filter === '' || $filter === 'vs08_circuit') {
            $cids = get_posts(['post_type' => 'vs08_circuit', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids']);
            foreach ($cids as $id) {
                $m = class_exists('VS08C_Meta') ? VS08C_Meta::get($id) : [];
                if (trim((string) ($m['destination'] ?? '')) !== $destination) continue;
                $out[] = ['id' => $id, 'title' => '🗺️ ' . get_the_title($id), 'flag' => $m['flag'] ?? ''];
            }
        }

        wp_send_json_success($out);
    }

    public static function ajax_save_slot() {
        self::guard_ajax();
        $slot = intval($_POST['slot'] ?? 0);
        $product_id = intval($_POST['product_id'] ?? 0);
        if ($slot < 1 || $slot > self::SLOTS_COUNT) {
            wp_send_json_error('Slot invalide.');
        }
        if ($product_id <= 0) {
            wp_send_json_error('Produit invalide.');
        }
        $post = get_post($product_id);
        if (!$post || !in_array($post->post_type, ['vs08_voyage', 'vs08_circuit']) || $post->post_status !== 'publish') {
            wp_send_json_error('Produit non publié.');
        }
        $slots = self::get_slots();
        $slots[$slot] = $product_id;
        update_option(self::OPTION_SLOTS, $slots, false);
        wp_send_json_success(['slot' => $slot, 'product_id' => $product_id]);
    }

    public static function ajax_save_departs_slot() {
        self::guard_ajax();
        $slot = intval($_POST['slot'] ?? 0);
        $product_id = intval($_POST['product_id'] ?? 0);
        if ($slot < 1 || $slot > self::DEPARTS_SLOTS_COUNT) {
            wp_send_json_error('Slot invalide.');
        }
        if ($product_id <= 0) {
            wp_send_json_error('Produit invalide.');
        }
        $post = get_post($product_id);
        if (!$post || !in_array($post->post_type, ['vs08_voyage', 'vs08_circuit']) || $post->post_status !== 'publish') {
            wp_send_json_error('Produit non publié.');
        }
        $slots = self::get_departs_slots();
        $slots[$slot] = $product_id;
        update_option(self::OPTION_DEPARTS_SLOTS, $slots, false);
        wp_send_json_success(['slot' => $slot, 'product_id' => $product_id]);
    }

    private static function guard_ajax() {
        if (!check_ajax_referer('vs08v_home_editor', 'nonce', false)) {
            wp_send_json_error('Nonce expiré.');
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission refusée.');
        }
    }

    /**
     * Sauvegarde générique pour n'importe quelle section de la homepage.
     * Accepte golf ET circuits.
     */
    public static function ajax_save_generic() {
        self::guard_ajax();
        $section = sanitize_key($_POST['section'] ?? '');
        $slot = intval($_POST['slot'] ?? 0);
        $product_id = intval($_POST['product_id'] ?? 0);
        if (!$section || $slot < 1) wp_send_json_error('Données invalides.');
        $post = get_post($product_id);
        if (!$post || !in_array($post->post_type, ['vs08_voyage', 'vs08_circuit']) || $post->post_status !== 'publish') {
            wp_send_json_error('Produit non publié.');
        }
        $option = 'vs08v_homepage_' . $section . '_slots';
        $slots = get_option($option, []);
        if (!is_array($slots)) $slots = [];
        $slots[$slot] = $product_id;
        update_option($option, $slots, false);
        wp_send_json_success(['section' => $section, 'slot' => $slot, 'product_id' => $product_id]);
    }

    /**
     * Données d’une ligne « Circuits / Road-trip » (accueil) à partir d’un ID circuit.
     *
     * @return array<string,mixed>|null
     */
    public static function build_homepage_dl_circuit_entry($post_id) {
        $post_id = (int) $post_id;
        if ($post_id <= 0 || get_post_type($post_id) !== 'vs08_circuit' || get_post_status($post_id) !== 'publish') {
            return null;
        }
        if (!class_exists('VS08C_Meta')) {
            return null;
        }
        $dl_m       = VS08C_Meta::get($post_id);
        $dl_transport = $dl_m['transport'] ?? 'bus';
        $dl_prix_min  = get_post_meta($post_id, 'vs08c_prix_min', true);
        $dl_prix_val  = $dl_prix_min > 0 ? (float) $dl_prix_min : (float) ($dl_m['prix_double'] ?? 0);
        $flag_r       = VS08C_Meta::resolve_flag($dl_m);
        $dl_flag_show = trim((string) ($dl_m['flag'] ?? ''));
        if ($dl_flag_show === '') {
            $dl_flag_show = $flag_r;
        }
        return [
            'id'        => $post_id,
            'title'     => get_the_title($post_id),
            'link'      => get_permalink($post_id),
            'img'       => get_the_post_thumbnail_url($post_id, 'medium_large') ?: (!empty($dl_m['galerie'][0]) ? $dl_m['galerie'][0] : ''),
            'pays'      => trim($dl_flag_show . ' ' . ($dl_m['pays'] ?? '')),
            'prix'      => $dl_prix_val > 0 ? number_format($dl_prix_val, 0, ',', ' ') . '€' : '',
            'jours'     => !empty($dl_m['duree_jours']) ? (int) $dl_m['duree_jours'] . ' jours' : '',
            'desc'      => has_excerpt($post_id) ? get_the_excerpt($post_id) : wp_trim_words(strip_tags((string) get_post_field('post_content', $post_id)), 20),
            'pension'   => $dl_m['pension'] ?? '',
            'transport' => $dl_transport,
            'guide'     => $dl_m['guide_lang'] ?? '',
            'badge'     => $dl_m['badge'] ?? '',
            'hotels'    => $dl_m['hotels'] ?? [],
        ];
    }

    /**
     * Récupère les IDs sauvegardés pour une section donnée.
     */
    public static function get_section_slots($section, $count = 4) {
        $slots = get_option('vs08v_homepage_' . sanitize_key($section) . '_slots', []);
        if (!is_array($slots)) $slots = [];
        $out = [];
        for ($i = 1; $i <= $count; $i++) {
            $out[$i] = intval($slots[$i] ?? 0);
        }
        return $out;
    }
}
