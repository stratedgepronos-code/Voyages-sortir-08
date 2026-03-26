<?php
if (!defined('ABSPATH')) exit;


/* ============================================================
   SETUP DU THÈME
============================================================ */
add_action('after_setup_theme', function() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    add_theme_support('html5', ['search-form','comment-form','comment-list','gallery','caption']);
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    register_nav_menus(['primary' => 'Menu Principal']);
});

/* ============================================================
   STYLES & SCRIPTS
============================================================ */
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('vs08-fonts', 'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Outfit:wght@300;400;500;600;700&display=swap', [], null);
    wp_enqueue_style('vs08-main', get_template_directory_uri() . '/assets/css/main.css', ['vs08-fonts'], '1.3');
    wp_enqueue_style('vs08-header-mega', get_template_directory_uri() . '/assets/css/header-mega.css', ['vs08-main'], '1.3');
    wp_enqueue_style('vs08-footer-terminal', get_template_directory_uri() . '/assets/css/footer-terminal.css', ['vs08-main'], '1.0');
    if (is_front_page()) {
        /* Déploiement #373 — feat(homepage) : homepage-v3 + front-page-v2 (cartes dynamiques BDD) */
        wp_enqueue_style('vs08-homepage-v3', get_template_directory_uri() . '/assets/css/homepage-v3.css', ['vs08-main', 'vs08-header-mega'], '1.0');
        wp_enqueue_style('vs08-front-page-v2', get_template_directory_uri() . '/assets/css/front-page-v2.css', ['vs08-homepage-v3'], '2.0');
    }
    if (function_exists('is_checkout') && is_checkout() && !is_wc_endpoint_url('order-received')) {
        wp_enqueue_style('vs08-checkout', get_template_directory_uri() . '/assets/css/checkout.css', ['vs08-main'], '4.7');
        wp_enqueue_script('vs08-checkout-js', get_template_directory_uri() . '/assets/js/checkout.js', ['jquery'], '4.7', true);
    }
    wp_enqueue_script('vs08-main', get_template_directory_uri() . '/assets/js/main.js', [], '1.3', true);
    wp_enqueue_script('vs08-footer-terminal', get_template_directory_uri() . '/assets/js/footer-terminal.js', [], '1.2', true);
});

/* Ne pas charger stats.wp.com (Jetpack) — évite ERR_BLOCKED_BY_CLIENT en admin si extension bloque la requête */
add_filter('script_loader_tag', function($tag, $handle, $src) {
    if (strpos($src, 'stats.wp.com') !== false) {
        return '';
    }
    return $tag;
}, 10, 3);

/* Checkout : masquer le bloc code promo */
add_filter('woocommerce_coupon_enabled', function() {
    return false;
});

/* Redirection après « Procéder au paiement » : par défaut = page Checkout WooCommerce (celle qu’on a customisée avec form-checkout.php, recap, Paybox). Option vs08_checkout_page_id permet de forcer une autre page. */
add_filter('vs08v_booking_checkout_redirect_url', function($url) {
    $page_id = trim((string) get_option('vs08_checkout_page_id', ''));
    if ($page_id !== '' && is_numeric($page_id)) {
        $permalink = get_permalink((int) $page_id);
        if ($permalink) {
            return $permalink;
        }
    }
    return $url; // $url = wc_get_checkout_url() = page définie dans WooCommerce → Réglages → Avancé → Pages (Paiement)
});

/* ============================================================
   OPTIONS DU THÈME (ADMIN)
============================================================ */
add_action('admin_menu', function() {
    add_menu_page('VS08 Réglages', '⚙️ VS08 Réglages', 'manage_options', 'vs08-settings', 'vs08_settings_page', 'dashicons-admin-settings', 3);
});

function vs08_settings_page() {
    if (isset($_POST['vs08_save']) && check_admin_referer('vs08_save_action')) {
        $fields = ['vs08_annonce_text','vs08_annonce_link','vs08_annonce_link_text','vs08_tel','vs08_email','vs08_show_annonce','vs08_hero_title','vs08_hero_tagline','vs08_hero_desc','vs08_hero_img','vs08_checkout_page_id'];
        foreach ($fields as $f) {
            if ($f === 'vs08_show_annonce') {
                update_option($f, isset($_POST[$f]) ? '1' : '0');
            } else {
                update_option($f, sanitize_text_field($_POST[$f] ?? ''));
            }
        }
        echo '<div class="notice notice-success"><p>✅ Réglages sauvegardés !</p></div>';
    }
    $opts = [
        'vs08_show_annonce'     => get_option('vs08_show_annonce', '1'),
        'vs08_annonce_text'     => get_option('vs08_annonce_text', '⛳ Nouveau : Séjours Golf au Maroc dès 890€/pers. tout compris'),
        'vs08_annonce_link'     => get_option('vs08_annonce_link', '#'),
        'vs08_annonce_link_text'=> get_option('vs08_annonce_link_text', 'Voir l\'offre →'),
        'vs08_tel'              => get_option('vs08_tel', '03 24 XX XX XX'),
        'vs08_email'            => get_option('vs08_email', 'contact@voyagessortir08.fr'),
        'vs08_hero_title'       => get_option('vs08_hero_title', 'Jouez sur les plus beaux <em>parcours</em> du monde'),
        'vs08_hero_tagline'     => get_option('vs08_hero_tagline', '— Libre à vous de payer plus cher !'),
        'vs08_hero_desc'        => get_option('vs08_hero_desc', 'Des séjours golf tout compris pensés par des passionnés. Parcours d\'exception, hôtels de charme, vols inclus — vous n\'avez qu\'à jouer.'),
        'vs08_hero_img'         => get_option('vs08_hero_img', 'https://images.unsplash.com/photo-1535131749006-b7f58c99034b?w=1920&q=80'),
        'vs08_checkout_page_id' => get_option('vs08_checkout_page_id', ''),
    ];
    $checkout_page_id = trim($opts['vs08_checkout_page_id'] ?? '');
    if ($checkout_page_id === '' && function_exists('wc_get_page_id')) {
        $checkout_page_id = (string) wc_get_page_id('checkout');
    }
    ?>
    <div class="wrap">
        <h1>⚙️ Réglages Voyages Sortir 08</h1>
        <form method="post" style="max-width:800px;">
            <?php wp_nonce_field('vs08_save_action'); ?>
            <div style="background:#fff;padding:28px;border-radius:10px;margin-top:20px;box-shadow:0 2px 10px rgba(0,0,0,.08);">
                <h2 style="color:#1a3a3a;margin-top:0;">📢 Barre d'annonce</h2>
                <table class="form-table">
                    <tr><th>Afficher</th><td><input type="checkbox" name="vs08_show_annonce" value="1" <?php checked($opts['vs08_show_annonce'],'1'); ?>></td></tr>
                    <tr><th>Texte</th><td><input type="text" name="vs08_annonce_text" value="<?php echo esc_attr($opts['vs08_annonce_text']); ?>" class="large-text"></td></tr>
                    <tr><th>Texte du lien</th><td><input type="text" name="vs08_annonce_link_text" value="<?php echo esc_attr($opts['vs08_annonce_link_text']); ?>" class="regular-text"></td></tr>
                    <tr><th>URL du lien</th><td><input type="text" name="vs08_annonce_link" value="<?php echo esc_attr($opts['vs08_annonce_link']); ?>" class="regular-text"></td></tr>
                </table>
            </div>
            <div style="background:#fff;padding:28px;border-radius:10px;margin-top:16px;box-shadow:0 2px 10px rgba(0,0,0,.08);">
                <h2 style="color:#1a3a3a;margin-top:0;">💳 Page de paiement (après « Procéder au paiement »)</h2>
                <p style="color:#6b7280;margin-bottom:12px;">Tout a été créé dans le thème : la page de paiement = <strong>la page « Paiement » définie dans WooCommerce → Réglages → Avancé → Pages</strong>. Elle affiche automatiquement le formulaire custom (stepper, récap séjour, Paybox). Laissez le champ vide pour rediriger vers cette page.</p>
                <table class="form-table">
                    <tr><th>ID page (optionnel)</th><td>
                        <input type="text" name="vs08_checkout_page_id" value="<?php echo esc_attr($opts['vs08_checkout_page_id']); ?>" placeholder="<?php echo esc_attr($checkout_page_id); ?>" class="small-text">
                        <br><small>Vide = page Checkout WooCommerce (ID actuel : <?php echo esc_html($checkout_page_id ?: '—'); ?>). Renseigner un ID uniquement pour forcer une autre URL.</small>
                    </td></tr>
                </table>
            </div>
            <div style="background:#fff;padding:28px;border-radius:10px;margin-top:16px;box-shadow:0 2px 10px rgba(0,0,0,.08);">
                <h2 style="color:#1a3a3a;margin-top:0;">📞 Coordonnées</h2>
                <table class="form-table">
                    <tr><th>Téléphone</th><td><input type="text" name="vs08_tel" value="<?php echo esc_attr($opts['vs08_tel']); ?>" class="regular-text"></td></tr>
                    <tr><th>Email</th><td><input type="text" name="vs08_email" value="<?php echo esc_attr($opts['vs08_email']); ?>" class="regular-text"></td></tr>
                </table>
            </div>
            <div style="background:#fff;padding:28px;border-radius:10px;margin-top:16px;box-shadow:0 2px 10px rgba(0,0,0,.08);">
                <h2 style="color:#1a3a3a;margin-top:0;">🏌️ Section Hero</h2>
                <table class="form-table">
                    <tr><th>Titre (HTML ok)</th><td><input type="text" name="vs08_hero_title" value="<?php echo esc_attr($opts['vs08_hero_title']); ?>" class="large-text"></td></tr>
                    <tr><th>Tagline</th><td><input type="text" name="vs08_hero_tagline" value="<?php echo esc_attr($opts['vs08_hero_tagline']); ?>" class="regular-text"></td></tr>
                    <tr><th>Description</th><td><textarea name="vs08_hero_desc" class="large-text" rows="3"><?php echo esc_textarea($opts['vs08_hero_desc']); ?></textarea></td></tr>
                    <tr><th>URL Image Hero</th><td><input type="text" name="vs08_hero_img" value="<?php echo esc_attr($opts['vs08_hero_img']); ?>" class="large-text"><br><small>URL d'une image (Unsplash ou uploadée dans WordPress)</small></td></tr>
                </table>
            </div>
            <p style="margin-top:20px;">
                <button type="submit" name="vs08_save" class="button button-primary" style="background:#59b7b7;border-color:#3d9a9a;padding:10px 28px;height:auto;font-size:15px;border-radius:8px;">
                    💾 Enregistrer les réglages
                </button>
            </p>
        </form>
    </div>
    <?php
}

/* ============================================================
   CRÉATION DES PAGES SITE (footer, menu) si elles n'existent pas
============================================================ */
add_action('init', function() {
    if (get_option('vs08v_site_pages_created') === 'yes') return;
    $pages = [
        'mentions-legales'       => ['title' => 'Mentions légales', 'template' => 'page-mentions-legales.php'],
        'qui-sommes-nous'        => ['title' => 'Qui sommes-nous', 'template' => 'page-qui-sommes-nous.php'],
        'avis-clients'           => ['title' => 'Avis clients', 'template' => 'page-avis-clients.php'],
        'comment-reserver'       => ['title' => 'Comment réserver', 'template' => 'page-comment-reserver.php'],
        'blog'                   => ['title' => 'Blog voyage & golf', 'template' => 'page-blog.php'],
        'assurances'             => ['title' => 'Assurances voyage', 'template' => 'page-assurances.php'],
        'faq'                    => ['title' => 'FAQ', 'template' => 'page-faq.php'],
        'contact'                => ['title' => 'Contact', 'template' => 'page-contact.php'],
        'destinations'           => ['title' => 'Destinations', 'template' => 'page-destinations.php'],
        'devis-golf'             => ['title' => 'Devis golf', 'template' => 'page-devis-golf.php'],
        'conditions'             => ['title' => 'Conditions générales de vente', 'template' => 'page-conditions.php'],
        'rgpd'                   => ['title' => 'Politique de confidentialité', 'template' => 'page-rgpd.php'],
        'golf'                   => ['title' => 'Séjours Golf', 'template' => 'page-golf.php'],
    ];
    foreach ($pages as $slug => $data) {
        if (get_page_by_path($slug, OBJECT, 'page')) continue;
        wp_insert_post([
            'post_title'   => $data['title'],
            'post_name'    => $slug,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_author'  => 1,
            'post_content' => '',
        ]);
    }
    update_option('vs08v_site_pages_created', 'yes');
}, 20);

/* ============================================================
   HELPER — OPTIONS
============================================================ */
function vs08_opt($key, $default = '') {
    return get_option($key, $default);
}

/* ============================================================
   MÉGA-MENU — données depuis les voyages publiés (vs08_voyage)
============================================================ */
function vs08_mega_resultats_url() {
    return home_url('/resultats-recherche');
}

/** Pays / zones golf : uniquement les fiches catalogue golf réelles (type ou nb parcours). */
function vs08_mega_golf_countries($limit = 5) {
    $res = vs08_mega_resultats_url();

    // Descriptions par pays — les régions/villes golf emblématiques
    $desc_map = [
        'Portugal'  => 'Algarve, Lisbonne, Madère',
        'Espagne'   => 'Costa del Sol, Majorque, Tenerife',
        'Maroc'     => 'Marrakech, Agadir, El Jadida',
        'Turquie'   => 'Belek, Antalya',
        'Tunisie'   => 'Hammamet, Djerba, Sousse',
        'Irlande'   => 'Dublin, Kerry, Cork',
        'Grèce'     => 'Crète, Rhodes, Costa Navarino',
        'Écosse'    => 'St Andrews, Highlands',
        'Italie'    => 'Sardaigne, Sicile, Toscane',
        'France'    => 'Côte d\'Azur, Pays Basque',
        'Thaïlande' => 'Phuket, Hua Hin, Bangkok',
        'Maurice'   => 'Île Maurice',
        'Égypte'    => 'Soma Bay, El Gouna',
        'Chypre'    => 'Paphos, Limassol',
    ];

    // Essayer de lire depuis la BDD
    if (class_exists('VS08V_MetaBoxes')) {
        $ids = get_posts([
            'post_type'      => 'vs08_voyage',
            'post_status'    => 'publish',
            'posts_per_page' => 200,
            'fields'         => 'ids',
        ]);
        $countries = []; // pays => ['count' => N, 'flag' => '🇵🇹', 'dest' => 'Portugal']
        foreach ($ids as $pid) {
            $m = VS08V_MetaBoxes::get($pid);
            if (($m['statut'] ?? '') === 'archive') continue;
            // Filtre golf : type = sejour_golf OU nb_parcours renseigné
            $is_golf = (($m['type_voyage'] ?? '') === 'sejour_golf') || (intval($m['nb_parcours'] ?? 0) > 0);
            if (!$is_golf) continue;

            $pays = trim($m['pays'] ?? '');
            if ($pays === '') continue;
            $flag = VS08V_MetaBoxes::resolve_flag($m);

            if (!isset($countries[$pays])) {
                $countries[$pays] = ['count' => 0, 'flag' => $flag, 'dest' => $pays];
            }
            $countries[$pays]['count']++;
        }
        // Trier par nombre de séjours (le plus populaire en premier)
        uasort($countries, function($a, $b) { return $b['count'] - $a['count']; });

        $out = [];
        foreach (array_slice($countries, 0, $limit, true) as $pays => $info) {
            $out[] = [
                'label' => $pays,
                'flag'  => $info['flag'],
                'desc'  => $desc_map[$pays] ?? 'Séjours golf tout compris',
                'url'   => add_query_arg(['type' => 'sejour_golf', 'dest' => $info['dest']], $res),
            ];
        }
        if (!empty($out)) return $out;
    }

    // Fallback si BDD vide
    $fallback = [
        ['label' => 'Portugal',  'flag' => '🇵🇹', 'dest' => 'Portugal'],
        ['label' => 'Espagne',   'flag' => '🇪🇸', 'dest' => 'Espagne'],
        ['label' => 'Maroc',     'flag' => '🇲🇦', 'dest' => 'Maroc'],
        ['label' => 'Turquie',   'flag' => '🇹🇷', 'dest' => 'Turquie'],
        ['label' => 'Tunisie',   'flag' => '🇹🇳', 'dest' => 'Tunisie'],
    ];
    $out = [];
    foreach (array_slice($fallback, 0, $limit) as $fb) {
        $out[] = [
            'label' => $fb['label'],
            'flag'  => $fb['flag'],
            'desc'  => $desc_map[$fb['label']] ?? 'Séjours golf tout compris',
            'url'   => add_query_arg(['type' => 'sejour_golf', 'dest' => $fb['dest']], $res),
        ];
    }
    return $out;
}

/** Aéroports : les 5 plus utilisés dans les séjours golf publiés. */
function vs08_mega_departure_airports($limit = 5) {
    $res = vs08_mega_resultats_url();

    // Essayer de lire depuis la BDD
    if (class_exists('VS08V_MetaBoxes')) {
        $ids = get_posts([
            'post_type'      => 'vs08_voyage',
            'post_status'    => 'publish',
            'posts_per_page' => 200,
            'fields'         => 'ids',
        ]);
        $airports = []; // code => ['ville' => '...', 'count' => N]
        foreach ($ids as $pid) {
            $m = VS08V_MetaBoxes::get($pid);
            if (($m['statut'] ?? '') === 'archive') continue;
            // Tous les types de voyages (pas que golf) pour les aéroports
            if (!empty($m['aeroports']) && is_array($m['aeroports'])) {
                foreach ($m['aeroports'] as $a) {
                    $code  = strtoupper(trim($a['code'] ?? ''));
                    $ville = trim($a['ville'] ?? '');
                    if (!$code) continue;
                    if (!isset($airports[$code])) {
                        $airports[$code] = ['ville' => $ville, 'count' => 0];
                    }
                    $airports[$code]['count']++;
                }
            }
        }
        // Trier par nombre de séjours (le plus utilisé en premier)
        uasort($airports, function($a, $b) { return $b['count'] - $a['count']; });

        $out = [];
        foreach (array_slice($airports, 0, $limit, true) as $code => $info) {
            $out[] = [
                'code'  => $code,
                'label' => $info['ville'] ?: $code,
                'url'   => add_query_arg(['type' => 'sejour_golf', 'airport' => $code], $res),
            ];
        }
        if (!empty($out)) return $out;
    }

    // Fallback si BDD vide
    $fallback = [
        ['code' => 'CDG', 'label' => 'Paris Charles de Gaulle'],
        ['code' => 'ORY', 'label' => 'Paris Orly'],
        ['code' => 'LYS', 'label' => 'Lyon Saint-Exupéry'],
        ['code' => 'MRS', 'label' => 'Marseille Provence'],
        ['code' => 'NTE', 'label' => 'Nantes Atlantique'],
    ];
    $out = [];
    foreach (array_slice($fallback, 0, $limit) as $fb) {
        $fb['url'] = add_query_arg(['type' => 'sejour_golf', 'airport' => $fb['code']], $res);
        $out[] = $fb;
    }
    return $out;
}

/** Destinations catalogue : deux colonnes pour le méga-menu. */
function vs08_mega_destinations_split() {
    if (class_exists('VS08V_Search')) {
        $list = array_values(VS08V_Search::get_aggregated_options()['destinations'] ?? []);
        if (count($list) > 0) {
            $mid = (int) ceil(count($list) / 2);
            return [array_slice($list, 0, $mid), array_slice($list, $mid)];
        }
    }
    // Fallback — destinations populaires (toutes catégories confondues)
    $res = vs08_mega_resultats_url();
    $fb = [
        ['value' => 'Portugal',              'pays' => 'Portugal',              'flag' => '🇵🇹', 'label' => 'Portugal'],
        ['value' => 'Espagne',               'pays' => 'Espagne',               'flag' => '🇪🇸', 'label' => 'Espagne'],
        ['value' => 'Maroc',                 'pays' => 'Maroc',                 'flag' => '🇲🇦', 'label' => 'Maroc'],
        ['value' => 'Grèce',                 'pays' => 'Grèce',                 'flag' => '🇬🇷', 'label' => 'Grèce'],
        ['value' => 'Tunisie',               'pays' => 'Tunisie',               'flag' => '🇹🇳', 'label' => 'Tunisie'],
        ['value' => 'Italie',                'pays' => 'Italie',                'flag' => '🇮🇹', 'label' => 'Italie'],
        ['value' => 'Turquie',               'pays' => 'Turquie',               'flag' => '🇹🇷', 'label' => 'Turquie'],
        ['value' => 'République Dominicaine','pays' => 'République Dominicaine','flag' => '🇩🇴', 'label' => 'Rép. Dominicaine'],
        ['value' => 'Croatie',               'pays' => 'Croatie',               'flag' => '🇭🇷', 'label' => 'Croatie'],
        ['value' => 'Irlande',               'pays' => 'Irlande',               'flag' => '🇮🇪', 'label' => 'Irlande'],
    ];
    // Ajouter count = 0 (pas de produits encore) et image vide
    foreach ($fb as &$d) {
        $d['count'] = 0;
        $d['image'] = '';
    }
    unset($d);
    $mid = (int) ceil(count($fb) / 2);
    return [array_slice($fb, 0, $mid), array_slice($fb, $mid)];
}

/** Destinations pour lesquelles au moins un voyage « circuit » existe. */
function vs08_mega_circuit_destinations($limit = 8) {
    $res = vs08_mega_resultats_url();
    if (class_exists('VS08V_MetaBoxes')) {
        $ids = get_posts([
            'post_type'      => 'vs08_voyage',
            'post_status'    => 'publish',
            'posts_per_page' => 200,
            'fields'         => 'ids',
        ]);
        $seen = [];
        $out = [];
        foreach ($ids as $pid) {
            if (count($out) >= $limit) {
                break;
            }
            $m = VS08V_MetaBoxes::get($pid);
            if (($m['statut'] ?? '') === 'archive') {
                continue;
            }
            if (($m['type_voyage'] ?? '') !== 'circuit') {
                continue;
            }
            $dest = trim($m['destination'] ?? '');
            $pays = trim($m['pays'] ?? '');
            $key = $dest !== '' ? $dest : $pays;
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $flag = VS08V_MetaBoxes::resolve_flag($m);
            $out[] = [
                'label' => $key,
                'flag'  => $flag,
                'url'   => add_query_arg(['type' => 'circuit', 'dest' => $key], $res),
            ];
        }
        if (!empty($out)) {
            return $out;
        }
    }
    // Fallback — circuits populaires
    $fb = [
        ['label' => 'Italie',         'flag' => '🇮🇹', 'dest' => 'Italie'],
        ['label' => 'Grèce',          'flag' => '🇬🇷', 'dest' => 'Grèce'],
        ['label' => 'Croatie',         'flag' => '🇭🇷', 'dest' => 'Croatie'],
        ['label' => 'Vietnam',         'flag' => '🇻🇳', 'dest' => 'Vietnam'],
        ['label' => 'Costa Rica',      'flag' => '🇨🇷', 'dest' => 'Costa Rica'],
    ];
    $out = [];
    foreach ($fb as $f) {
        $out[] = [
            'label' => $f['label'],
            'flag'  => $f['flag'],
            'url'   => add_query_arg(['type' => 'circuit', 'dest' => $f['dest']], $res),
        ];
    }
    return array_slice($out, 0, $limit);
}

/**
 * Devis hors golf : envoi aux deux adresses agence.
 */
function vs08_mail_devis_agence($subject, $body_plain, $reply_to_email) {
    $headers = ['Content-Type: text/plain; charset=UTF-8'];
    if (is_email($reply_to_email)) {
        $headers[] = 'Reply-To: ' . $reply_to_email;
    }
    $ok1 = wp_mail('sortir08@wanadoo.fr', $subject, $body_plain, $headers);
    $ok2 = wp_mail('sortir08.ag@wanadoo.fr', $subject, $body_plain, $headers);
    return $ok1 && $ok2;
}

/* Pages devis (hub + formulaires) — création / template une fois */
add_action('init', function() {
    if (get_option('vs08_pages_devis_hub_v1') === 'yes') {
        return;
    }
    $new = [
        'devis-gratuit'         => ['title' => 'Devis gratuit', 'template' => 'page-devis-gratuit.php'],
        'devis-sejour-vacances' => ['title' => 'Devis séjour vacances', 'template' => 'page-devis-sejour-vacances.php'],
        'devis-city-trip'       => ['title' => 'Devis City Trip', 'template' => 'page-devis-city-trip.php'],
        'devis-road-trip'       => ['title' => 'Devis Road Trip', 'template' => 'page-devis-road-trip.php'],
        'devis-circuit'         => ['title' => 'Devis circuit', 'template' => 'page-devis-circuit.php'],
    ];
    foreach ($new as $slug => $data) {
        $existing = get_page_by_path($slug, OBJECT, 'page');
        if ($existing) {
            update_post_meta($existing->ID, '_wp_page_template', $data['template']);
            continue;
        }
        $post_id = wp_insert_post([
            'post_title'   => $data['title'],
            'post_name'    => $slug,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_author'  => 1,
            'post_content' => '',
        ]);
        if ($post_id && !is_wp_error($post_id)) {
            update_post_meta($post_id, '_wp_page_template', $data['template']);
        }
    }
    update_option('vs08_pages_devis_hub_v1', 'yes');
}, 26);

/* ============================================================
   PAGES SITE — création auto des pages nécessaires au menu
   Fonctionne comme les pages devis : crée une seule fois, puis ne touche plus.
   Si la page existe déjà, on assigne juste le bon template.
============================================================ */
add_action('init', function() {
    if (get_option('vs08_pages_site_v4') === 'yes') {
        return;
    }
    // slug => ['title' => ..., 'template' => ... (optionnel si slug matching suffit)]
    $pages = [
        'resultats-recherche' => [
            'title'    => 'Résultats de recherche',
            'template' => 'page-resultats.php',
        ],
        'destinations' => [
            'title'    => 'Nos destinations',
            'template' => 'page-destinations.php',
        ],
        'sejours-golf' => [
            'title'    => 'Séjours Golf',
            'template' => 'page-golf.php',
        ],
        'bientot-disponible' => [
            'title'    => 'Bientôt disponible',
            'template' => 'page-bientot-disponible.php',
        ],
        'contact' => [
            'title'    => 'Contact',
            'template' => 'page-contact.php',
        ],
        'avis-clients' => [
            'title'    => 'Avis clients',
            'template' => 'page-avis-clients.php',
        ],
        'qui-sommes-nous' => [
            'title'    => 'Qui sommes-nous',
            'template' => 'page-qui-sommes-nous.php',
        ],
        'faq' => [
            'title'    => 'FAQ',
            'template' => 'page-faq.php',
        ],
        'mentions-legales' => [
            'title'    => 'Mentions légales',
            'template' => 'page-mentions-legales.php',
        ],
        'conditions-generales' => [
            'title'    => 'Conditions générales de vente',
            'template' => 'page-conditions.php',
        ],
        'rgpd' => [
            'title'    => 'Politique de confidentialité',
            'template' => 'page-rgpd.php',
        ],
        'comment-reserver' => [
            'title'    => 'Comment réserver',
            'template' => 'page-comment-reserver.php',
        ],
        'assurances' => [
            'title'    => 'Assurances voyage',
            'template' => 'page-assurances.php',
        ],
    ];

    foreach ($pages as $slug => $data) {
        $existing = get_page_by_path($slug, OBJECT, 'page');
        if ($existing) {
            // La page existe déjà — on assigne juste le template si précisé
            if (!empty($data['template'])) {
                update_post_meta($existing->ID, '_wp_page_template', $data['template']);
            }
            continue;
        }
        // Créer la page
        $post_id = wp_insert_post([
            'post_title'   => $data['title'],
            'post_name'    => $slug,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_author'  => 1,
            'post_content' => '',
        ]);
        if ($post_id && !is_wp_error($post_id) && !empty($data['template'])) {
            update_post_meta($post_id, '_wp_page_template', $data['template']);
        }
    }
    update_option('vs08_pages_site_v4', 'yes');
}, 27);

/* ============================================================
   DÉSACTIVER LE CACHE SUR LES PAGES DYNAMIQUES (prix temps réel)
============================================================ */
add_action('template_redirect', function() {
    if (is_singular('vs08_voyage') || get_query_var('vs08_voyage_id')) {
        if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate(get_template_directory() . '/single-vs08_voyage.php', true);
        }
    }
});

/* ============================================================
   CHECKOUT : forcer le shortcode classique sur la page Commander
   (sinon le bloc Gutenberg est utilisé et notre template/CSS ne s'appliquent pas)
============================================================ */
// 1) Intercepter le rendu du BLOC Checkout → remplacer par le shortcode
add_filter('render_block', function($block_content, $block) {
    if (!isset($block['blockName']) || $block['blockName'] !== 'woocommerce/checkout') {
        return $block_content;
    }
    if (!function_exists('is_checkout') || !is_checkout() || is_wc_endpoint_url('order-received')) {
        return $block_content;
    }
    return do_shortcode('[woocommerce_checkout]');
}, 10, 2);

// 2) Si la page utilise le shortcode ou un contenu classique, the_content peut contenir le bloc déjà rendu ; on force quand même le shortcode si pas de shortcode
add_filter('the_content', function($content) {
    if (!function_exists('is_checkout') || !is_checkout() || is_wc_endpoint_url('order-received')) {
        return $content;
    }
    if (has_shortcode($content, 'woocommerce_checkout')) {
        return $content;
    }
    // Page avec bloc Checkout (ou autre) : forcer le checkout classique
    return do_shortcode('[woocommerce_checkout]');
}, 1);

/* Flush OPcache une fois après déploiement */
add_action('init', function() {
    $flag = get_transient('vs08_opcache_flushed_v21');
    if (!$flag && function_exists('opcache_reset')) {
        opcache_reset();
        set_transient('vs08_opcache_flushed_v21', 1, 3600);
    }
}, 1);

/* ============================================================
   CHECKOUT PREMIUM — Hooks pour les éléments de confiance
   Note sécurité sous le bouton + logos CB dans Paybox
============================================================ */

/**
 * 1) Note sécurité SOUS le bouton "Commander"
 *    Hook : woocommerce_review_order_after_submit
 *    → S'affiche juste après le bouton #place_order
 */
add_action('woocommerce_review_order_after_submit', function() {
    ?>
    <div class="vs08-secure-note">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#3d9a9a" stroke-width="2">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            <polyline points="9 12 11 14 15 10" stroke="#3d9a9a" stroke-width="2.5" fill="none"/>
        </svg>
        Paiement 100% sécurisé — Cryptage SSL 256 bits
    </div>
    <?php
});

/**
 * 3) Texte du bouton Commander personnalisé avec icône cadenas + montant
 */
add_filter('woocommerce_order_button_text', function() {
    return '🔒 Confirmer & Payer';
});

/**
 * 4) Récap séjour — injecté via le hook vs08_checkout_recap
 *    (appelé dans form-checkout.php après woocommerce_checkout_before_order_review)
 */
add_action('vs08_checkout_recap', function() {
    if (!WC()->cart) return;

    // Circuit : récap détaillé (même zone que le golf)
    foreach (WC()->cart->get_cart() as $item) {
        $id = $item['product_id'] ?? 0;
        if (!$id) continue;
        $data = get_post_meta($id, '_vs08c_booking_data', true);
        if (!empty($data) && is_array($data) && ($data['type'] ?? '') === 'circuit') {
            if (class_exists('VS08C_Checkout')) {
                VS08C_Checkout::output_recap_card();
            }
            return;
        }
    }

    $product_id = null;
    $booking_data = null;

    foreach (WC()->cart->get_cart() as $item) {
        $id = $item['product_id'] ?? 0;
        if (!$id) continue;
        $data = get_post_meta($id, '_vs08v_booking_data', true);
        if (!empty($data)) {
            $product_id = $id;
            $booking_data = $data;
            break;
        }
    }

    if (!$product_id || !is_array($booking_data)) return;

    $params = $booking_data['params'] ?? [];
    $devis  = $booking_data['devis'] ?? [];
    $titre  = $booking_data['voyage_titre'] ?? '';
    $vid    = $booking_data['voyage_id'] ?? 0;

    /* ── Date de depart ── */
    $date_dep = $params['date_depart'] ?? '';

    /* ── Nombre de nuits ── */
    $nights = 0;
    if ($vid) $nights = (int) get_post_meta($vid, '_vs08v_nights', true);
    if (!$nights && !empty($devis['lines'])) {
        foreach ($devis['lines'] as $l) {
            if (preg_match('/(\d+)\s*nuits/', $l['detail'] ?? '', $m)) { $nights = (int)$m[1]; break; }
        }
    }
    if (!$nights) $nights = 7;

    /* ── Date de retour ── */
    $date_ret = '';
    if ($date_dep) {
        $dt = new DateTime($date_dep);
        $dt->modify("+{$nights} days");
        $date_ret = $dt->format('Y-m-d');
    }

    /* ── Formater les dates en francais ── */
    $mois = ['01'=>'janvier','02'=>'f\xc3\xa9vrier','03'=>'mars','04'=>'avril','05'=>'mai','06'=>'juin','07'=>'juillet','08'=>'ao\xc3\xbbt','09'=>'septembre','10'=>'octobre','11'=>'novembre','12'=>'d\xc3\xa9cembre'];
    $fmt = function($d) use ($mois) {
        if (!$d) return '';
        $dt = new DateTime($d);
        return (int)$dt->format('d') . ' ' . ($mois[$dt->format('m')] ?? $dt->format('F')) . ' ' . $dt->format('Y');
    };

    /* ── Aeroports ── */
    $ap_names = [
        'CDG'=>'Paris CDG','ORY'=>'Paris Orly','LYS'=>'Lyon','MRS'=>'Marseille',
        'NCE'=>'Nice','TLS'=>'Toulouse','BOD'=>'Bordeaux','NTE'=>'Nantes',
        'SXB'=>'Strasbourg','LIL'=>'Lille',
    ];
    $ap_code = $params['aeroport'] ?? '';
    $ap_dep  = $ap_names[$ap_code] ?? $ap_code;

    /* ── Destination (depuis le titre du voyage) ── */
    $dest_map = [
        'Marrakech'=>['Marrakech RAK','MA'],
        'Agadir'=>['Agadir AGA','MA'],
        'Casablanca'=>['Casablanca CMN','MA'],
        'Fes'=>['Fes FEZ','MA'], 'Fez'=>['Fes FEZ','MA'],
        'Tanger'=>['Tanger TNG','MA'],
        'Essaouira'=>['Essaouira ESU','MA'],
        'Djerba'=>['Djerba DJE','TN'],
        'Hammamet'=>['Tunis TUN','TN'], 'Tunis'=>['Tunis TUN','TN'],
        'Antalya'=>['Antalya AYT','TR'],
        'Algarve'=>['Faro FAO','PT'], 'Faro'=>['Faro FAO','PT'],
        'Lisbonne'=>['Lisbonne LIS','PT'],
        'Malaga'=>['Malaga AGP','ES'], 'Costa del Sol'=>['Malaga AGP','ES'],
        'Palma'=>['Palma PMI','ES'], 'Majorque'=>['Palma PMI','ES'],
        'Tenerife'=>['Tenerife TFS','ES'],
        'Sicile'=>['Catane CTA','IT'], 'Sardaigne'=>['Cagliari CAG','IT'],
        'Maurice'=>['Maurice MRU','MU'],
        'Ile Maurice'=>['Maurice MRU','MU'],
    ];
    $flag_map = ['MA'=>"\xf0\x9f\x87\xb2\xf0\x9f\x87\xa6",'TN'=>"\xf0\x9f\x87\xb9\xf0\x9f\x87\xb3",'TR'=>"\xf0\x9f\x87\xb9\xf0\x9f\x87\xb7",'PT'=>"\xf0\x9f\x87\xb5\xf0\x9f\x87\xb9",'ES'=>"\xf0\x9f\x87\xaa\xf0\x9f\x87\xb8",'IT'=>"\xf0\x9f\x87\xae\xf0\x9f\x87\xb9",'MU'=>"\xf0\x9f\x87\xb2\xf0\x9f\x87\xba"];
    $dest_ap = 'Destination';
    $flag = '';
    foreach ($dest_map as $kw => $info) {
        if (stripos($titre, $kw) !== false) {
            $dest_ap = $info[0];
            $flag = $flag_map[$info[1]] ?? '';
            break;
        }
    }

    /* ── Green fees ── */
    $gf = 0;
    if ($vid) {
        $gf = (int) get_post_meta($vid, '_vs08v_green_fees', true);
        if (!$gf) $gf = (int) get_post_meta($vid, '_vs08v_nb_parcours', true);
        if (!$gf) $gf = (int) get_post_meta($vid, 'green_fees', true);
    }

    /* ── Pension / Formule ── */
    $pension = '';
    $pension_labels = [
        'ai' => 'All Inclusive',
        'all_inclusive' => 'All Inclusive',
        'dp' => 'Demi-pension',
        'demi_pension' => 'Demi-pension',
        'demi-pension' => 'Demi-pension',
        'pc' => 'Pension compl&egrave;te',
        'pension_complete' => 'Pension compl&egrave;te',
        'bb' => 'Petit-d&eacute;jeuner inclus',
        'pdj' => 'Petit-d&eacute;jeuner inclus',
        'petit_dejeuner' => 'Petit-d&eacute;jeuner inclus',
        'ro' => 'Room Only',
        'logement' => 'Logement seul',
    ];
    if ($vid) {
        $all_meta = get_post_meta($vid);
        if ($all_meta) {
            foreach ($all_meta as $mk => $mv) {
                $raw = is_array($mv) ? $mv[0] : $mv;
                if (!is_string($raw)) continue;
                // Essayer de deserialiser
                $unserialized = @unserialize($raw);
                if (is_array($unserialized) && isset($unserialized['pension'])) {
                    $code = strtolower(trim($unserialized['pension']));
                    $pension = isset($pension_labels[$code]) ? $pension_labels[$code] : ucfirst($code);
                    break;
                }
                // Si la meta key elle-meme est pension/formule
                if (in_array($mk, ['pension', 'formule', '_pension', '_formule', '_vs08v_pension', '_vs08v_formule'])) {
                    $code = strtolower(trim($raw));
                    $pension = isset($pension_labels[$code]) ? $pension_labels[$code] : ucfirst($raw);
                    break;
                }
            }
        }
    }

    /* ── Voyageurs ── */
    $nb_g  = (int)($params['nb_golfeurs'] ?? 0);
    $nb_ng = (int)($params['nb_nongolfeurs'] ?? 0);
    $nb    = $nb_g + $nb_ng;
    if (!$nb) $nb = (int)($devis['nb_total'] ?? 2);

    /* ── Hôtel (depuis les métadonnées du voyage) ── */
    $hotel_nom = '';
    $hotel_etoiles = '';
    if ($vid) {
        $vm = get_post_meta($vid, 'vs08v_data', true);
        if (is_array($vm)) {
            $hotel_nom     = $vm['hotel_nom'] ?? ($vm['hotel']['nom'] ?? '');
            $hotel_etoiles = $vm['hotel_etoiles'] ?? ($vm['hotel']['etoiles'] ?? '');
        }
    }

    /* ── Vols sélectionnés (horaires + n° vol) ── */
    $vol_aller_num     = $params['vol_aller_num'] ?? '';
    $vol_aller_dep     = $params['vol_aller_depart'] ?? '';
    $vol_aller_arr     = $params['vol_aller_arrivee'] ?? '';
    $vol_aller_cie     = $params['vol_aller_cie'] ?? '';
    $vol_retour_num    = $params['vol_retour_num'] ?? '';
    $vol_retour_dep    = $params['vol_retour_depart'] ?? '';
    $vol_retour_arr    = $params['vol_retour_arrivee'] ?? '';

    /* ── Type chambre ── */
    $chambre_labels = ['double'=>'Double','simple'=>'Single','triple'=>'Triple'];
    $type_chambre = $params['type_chambre'] ?? '';
    $chambre_label = $chambre_labels[$type_chambre] ?? ucfirst($type_chambre);

    /* ── HTML ── */
    echo '<div class="vs08v-checkout-recap-wrapper" data-booking="' . esc_attr(json_encode($booking_data, JSON_UNESCAPED_UNICODE)) . '">';
    echo '<div class="vs08v-checkout-recap-card">';
    echo '<div class="vs08v-woo-recap">';

    echo '<h3>' . $flag . ' ' . esc_html($titre) . '</h3>';
    echo '<h4>D&eacute;tails du s&eacute;jour</h4>';
    echo '<table>';
    echo '<tr><td>&#x1F4C5; Dates</td><td>' . esc_html($fmt($date_dep)) . ' &rarr; ' . esc_html($fmt($date_ret)) . '</td></tr>';
    echo '<tr><td>&#x1F319; Dur&eacute;e</td><td>' . ($nights+1) . ' jours / ' . $nights . ' nuits</td></tr>';
    echo '<tr><td>&#x2708;&#xFE0F; Vols</td><td>' . esc_html($ap_dep) . ' &rarr; ' . esc_html($dest_ap) . '</td></tr>';
    if ($vol_aller_num) {
        $vol_aller_txt = $vol_aller_num;
        if ($vol_aller_cie) $vol_aller_txt .= ' (' . $vol_aller_cie . ')';
        if ($vol_aller_dep && $vol_aller_arr) $vol_aller_txt .= ' · ' . $vol_aller_dep . ' → ' . $vol_aller_arr;
        echo '<tr><td style="padding-left:24px">&#x1F6EB; Aller</td><td>' . esc_html($vol_aller_txt) . '</td></tr>';
    }
    if ($vol_retour_num) {
        $vol_retour_txt = $vol_retour_num;
        if ($vol_retour_dep && $vol_retour_arr) $vol_retour_txt .= ' · ' . $vol_retour_dep . ' → ' . $vol_retour_arr;
        echo '<tr><td style="padding-left:24px">&#x1F6EC; Retour</td><td>' . esc_html($vol_retour_txt) . '</td></tr>';
    }
    if (!$vol_aller_num && !$vol_retour_num) {
        echo '<tr><td style="padding-left:24px;font-size:11px;color:var(--gray)">Horaires</td><td style="font-size:11px;color:var(--gray)">À confirmer avec votre conseiller</td></tr>';
    }
    if ($hotel_nom) {
        $hotel_txt = $hotel_nom;
        if ($hotel_etoiles) $hotel_txt .= ' ' . str_repeat('★', intval($hotel_etoiles));
        echo '<tr><td>&#x1F3E8; H&ocirc;tel</td><td>' . esc_html($hotel_txt) . '</td></tr>';
    }
    if ($pension) {
        echo '<tr><td>&#x1F37D;&#xFE0F; Formule</td><td>' . $pension . '</td></tr>';
    }
    if ($type_chambre) {
        echo '<tr><td>&#x1F6CF;&#xFE0F; Chambre</td><td>' . esc_html($chambre_label) . ' (' . intval($params['nb_chambres'] ?? 1) . ')</td></tr>';
    }
    if ($gf > 0) {
        echo '<tr><td>&#x26F3; Parcours</td><td>' . $gf . ' green fees inclus</td></tr>';
    } else {
        echo '<tr><td>&#x26F3; Parcours</td><td>Green fees inclus</td></tr>';
    }
    echo '<tr><td>&#x1F465; Voyageurs</td><td>' . $nb . ' adulte' . ($nb > 1 ? 's' : '');
    if ($nb_g > 0 && $nb_ng > 0) {
        echo ' (' . $nb_g . ' golfeur' . ($nb_g > 1 ? 's' : '') . ', ' . $nb_ng . ' accompagnant' . ($nb_ng > 1 ? 's' : '') . ')';
    }
    echo '</td></tr>';
    echo '</table>';

    echo '</div></div></div>';

    /* ── Bloc vérification voyageurs ── */
    $voyageurs = $booking_data['voyageurs'] ?? [];
    if (!empty($voyageurs)) {
        $fmt_ddn = function($ddn) {
            if (empty($ddn)) return '';
            $d = DateTime::createFromFormat('Y-m-d', $ddn);
            if ($d) return $d->format('d/m/Y');
            $d = DateTime::createFromFormat('d/m/Y', $ddn);
            return $d ? $d->format('d/m/Y') : esc_html($ddn);
        };
        echo '<div class="vs08v-pax-section">';
        echo '<h4 class="vs08v-pax-title">&#x1F6C2; V&eacute;rification des voyageurs</h4>';
        echo '<table class="vs08v-pax-table">';
        $pax_index = 0;
        foreach ($voyageurs as $v) {
            $pax_index++;
            $prenom    = trim($v['prenom'] ?? '');
            $nom       = trim($v['nom'] ?? '');
            $ddn       = $fmt_ddn($v['ddn'] ?? '');
            $passeport = trim($v['passeport'] ?? '');
            $is_golf   = ($v['type'] ?? 'golfeur') === 'golfeur';
            $type_label= $is_golf ? '&#x26F3; Golfeur' : '&#x1F464; Accompagnant';
            if ($prenom === '' && $nom === '') {
                $name_display = 'Voyageur ' . $pax_index . ' <span style="font-weight:400;color:var(--gray);font-size:11px">(non renseign&eacute;)</span>';
            } else {
                $name_display = esc_html($prenom) . ' <strong>' . esc_html(strtoupper($nom)) . '</strong>';
            }
            echo '<tr>';
            echo '<td class="vs08v-pax-type">' . $type_label . '</td>';
            echo '<td class="vs08v-pax-info">';
            echo $name_display;
            if ($ddn) echo '<br>N&eacute;(e) le ' . $ddn;
            if (!empty($passeport)) echo '<br>Passeport : <span class="vs08v-pax-pp">' . esc_html($passeport) . '</span>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '<label class="vs08v-pax-confirm">';
        echo '<input type="checkbox" name="vs08v_voyageurs_confirm" id="vs08v_voyageurs_confirm" value="1">';
        echo '<span>Je confirme que les informations voyageurs sont <strong>exactes</strong> et conformes aux pi&egrave;ces d\'identit&eacute;.</span>';
        echo '</label>';
        echo '</div>';
    }
}, 5);

/* Validation checkout : case voyageurs obligatoire pour les résa VS08 */
add_action('woocommerce_checkout_process', function() {
    if (!WC()->cart) return;
    foreach (WC()->cart->get_cart() as $item) {
        $data = get_post_meta($item['product_id'] ?? 0, '_vs08v_booking_data', true);
        if (!empty($data) && !empty($data['voyageurs'])) {
            if (empty($_POST['vs08v_voyageurs_confirm'])) {
                wc_add_notice(__('Veuillez certifier que les informations des voyageurs sont exactes (case à cocher).', 'woocommerce'), 'error');
            }
            break;
        }
    }
});

/* Enregistrer la confirmation voyageurs sur la commande (traçabilité) */
add_action('woocommerce_checkout_create_order', function($order) {
    if (!empty($_POST['vs08v_voyageurs_confirm'])) {
        $order->update_meta_data('_vs08v_voyageurs_confirmed', '1');
    }
}, 10, 1);

/* ============================================================
   NEWSLETTER — menu top-level indépendant
   Fonctionne même si le plugin ne charge pas la bonne version
============================================================ */
add_action('admin_menu', function() {
    add_menu_page(
        'Newsletter',
        '📧 Newsletter',
        'manage_options',
        'vs08-newsletter-hub',
        'vs08_newsletter_admin_page',
        'dashicons-email-alt',
        28
    );
}, 5);

function vs08_newsletter_admin_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'vs08_newsletter';

    // Vérifier que la table existe
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    if (!$table_exists) {
        // Créer la table
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            email VARCHAR(180) NOT NULL,
            prenom VARCHAR(100) DEFAULT '',
            nom VARCHAR(100) DEFAULT '',
            source VARCHAR(30) DEFAULT 'homepage',
            token VARCHAR(64) NOT NULL,
            active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email)
        ) $charset;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Migrer les anciennes données depuis wp_options
        $old = get_option('vs08v_newsletter_emails_v1', []);
        if (is_array($old) && !empty($old)) {
            foreach ($old as $row) {
                $e = strtolower(sanitize_email($row['email'] ?? ''));
                if (!is_email($e)) continue;
                $wpdb->replace($table, [
                    'email' => $e,
                    'source' => 'homepage_v1',
                    'token' => bin2hex(random_bytes(32)),
                    'active' => 1,
                    'created_at' => $row['date'] ?? current_time('mysql'),
                ], ['%s','%s','%s','%d','%s']);
            }
        }
        $table_exists = true;
        echo '<div class="notice notice-success"><p>✅ Table newsletter créée avec succès.</p></div>';
    }

    // Export CSV
    if (isset($_GET['action']) && $_GET['action'] === 'export' && current_user_can('manage_options')) {
        check_admin_referer('vs08_nl_export');
        $active_only = isset($_GET['active_only']);
        $where = $active_only ? "WHERE active = 1" : "";
        $rows = $wpdb->get_results("SELECT * FROM $table $where ORDER BY created_at DESC");
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=vs08-newsletter-brevo-' . date('Y-m-d') . '.csv');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, ['EMAIL','PRENOM','NOM','SOURCE','DATE_INSCRIPTION','STATUT'], ';');
        foreach ($rows as $r) {
            fputcsv($out, [$r->email, $r->prenom, $r->nom, $r->source, $r->created_at, $r->active ? 'actif' : 'desabonne'], ';');
        }
        fclose($out);
        exit;
    }

    // Supprimer
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && current_user_can('manage_options')) {
        check_admin_referer('vs08_nl_del_' . intval($_GET['id']));
        $wpdb->delete($table, ['id' => intval($_GET['id'])], ['%d']);
        echo '<div class="notice notice-success"><p>Contact supprimé.</p></div>';
    }

    // Stats
    $total  = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
    $active = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE active = 1");
    $sources = $wpdb->get_results("SELECT source, COUNT(*) as cnt FROM $table WHERE active = 1 GROUP BY source ORDER BY cnt DESC");

    // Recherche
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $where = '';
    if ($search) {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $where = $wpdb->prepare(" WHERE email LIKE %s OR prenom LIKE %s OR nom LIKE %s", $like, $like, $like);
    }
    $subs = $wpdb->get_results("SELECT * FROM $table $where ORDER BY created_at DESC LIMIT 200");
    $base = admin_url('admin.php?page=vs08-newsletter-hub');
    ?>
    <div class="wrap">
        <h1>📧 Newsletter — Abonnés</h1>
        <div style="display:flex;gap:16px;margin:20px 0">
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px 28px;flex:1;text-align:center">
                <div style="font-size:36px;font-weight:700;color:#0f2424"><?php echo $active; ?></div>
                <div style="color:#6b7280;font-size:13px;margin-top:4px">Abonnés actifs</div>
            </div>
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px 28px;flex:1;text-align:center">
                <div style="font-size:36px;font-weight:700;color:#b91c1c"><?php echo $total - $active; ?></div>
                <div style="color:#6b7280;font-size:13px;margin-top:4px">Désabonnés</div>
            </div>
            <?php foreach ($sources as $src) : ?>
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px 28px;flex:1;text-align:center">
                <div style="font-size:36px;font-weight:700;color:#59b7b7"><?php echo $src->cnt; ?></div>
                <div style="color:#6b7280;font-size:13px;margin-top:4px"><?php echo esc_html(ucfirst(str_replace('_', ' ', $src->source))); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:12px;align-items:center;margin-bottom:20px">
            <a href="<?php echo esc_url(wp_nonce_url($base . '&action=export&active_only=1', 'vs08_nl_export')); ?>" class="button button-primary">📥 Export CSV actifs (Brevo)</a>
            <a href="<?php echo esc_url(wp_nonce_url($base . '&action=export', 'vs08_nl_export')); ?>" class="button">📥 Export CSV tous</a>
            <form method="get" style="margin-left:auto;display:flex;gap:8px">
                <input type="hidden" name="page" value="vs08-newsletter-hub">
                <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Rechercher..." style="min-width:240px">
                <button type="submit" class="button">🔍</button>
            </form>
        </div>
        <table class="wp-list-table widefat striped">
            <thead><tr><th>Email</th><th>Prénom</th><th>Nom</th><th>Source</th><th>Statut</th><th>Date</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($subs)) : ?>
                <tr><td colspan="7" style="text-align:center;padding:24px;color:#9ca3af">Aucun abonné pour le moment.</td></tr>
            <?php else : foreach ($subs as $s) :
                $src_colors = ['homepage'=>'#dbeafe','commande'=>'#fef3c7','inscription'=>'#e0e7ff','homepage_v1'=>'#f3e8ff'];
                $bg = $src_colors[$s->source] ?? '#f3f4f6';
            ?>
                <tr>
                    <td><strong><?php echo esc_html($s->email); ?></strong></td>
                    <td><?php echo esc_html($s->prenom); ?></td>
                    <td><?php echo esc_html($s->nom); ?></td>
                    <td><span style="background:<?php echo $bg; ?>;padding:3px 10px;border-radius:100px;font-size:11px;font-weight:600"><?php echo esc_html(ucfirst($s->source)); ?></span></td>
                    <td><?php echo $s->active ? '<span style="color:#059669">✓ Actif</span>' : '<span style="color:#b91c1c">✗</span>'; ?></td>
                    <td style="font-size:12px;color:#6b7280"><?php echo esc_html(date_i18n('j M Y H:i', strtotime($s->created_at))); ?></td>
                    <td><a href="<?php echo esc_url(wp_nonce_url($base . '&action=delete&id=' . $s->id, 'vs08_nl_del_' . $s->id)); ?>" onclick="return confirm('Supprimer ?')" style="color:#b91c1c;font-size:11px">✕</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        <p class="description" style="margin-top:16px">CSV compatible <strong>Brevo</strong> : <code>EMAIL;PRENOM;NOM;SOURCE;DATE_INSCRIPTION;STATUT</code></p>
    </div>
    <?php
}

/* ── Newsletter : hooks de collecte (AJAX, inscription, commande) ── */

// Helper : ajouter un abonné
function vs08_nl_add($email, $prenom = '', $nom = '', $source = 'homepage') {
    global $wpdb;
    $table = $wpdb->prefix . 'vs08_newsletter';
    $email = strtolower(sanitize_email($email));
    if (!is_email($email)) return false;
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) return false;
    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE email = %s", $email));
    if ($exists) {
        $wpdb->query($wpdb->prepare(
            "UPDATE $table SET active = 1, prenom = IF(prenom = '' AND %s != '', %s, prenom), nom = IF(nom = '' AND %s != '', %s, nom) WHERE email = %s",
            $prenom, $prenom, $nom, $nom, $email
        ));
        return 'exists';
    }
    $wpdb->insert($table, [
        'email' => $email, 'prenom' => sanitize_text_field($prenom),
        'nom' => sanitize_text_field($nom), 'source' => sanitize_key($source),
        'token' => bin2hex(random_bytes(32)), 'active' => 1,
        'created_at' => current_time('mysql'),
    ]);
    return 'added';
}

// AJAX : formulaire homepage
add_action('wp_ajax_vs08v_newsletter_subscribe', 'vs08_nl_ajax');
add_action('wp_ajax_nopriv_vs08v_newsletter_subscribe', 'vs08_nl_ajax');
function vs08_nl_ajax() {
    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    if (!is_email($email)) wp_send_json_error(['message' => 'Adresse email invalide.']);
    $r = vs08_nl_add($email, sanitize_text_field($_POST['prenom'] ?? ''), '', 'homepage');
    if ($r === 'exists') wp_send_json_success(['message' => 'Vous êtes déjà inscrit !']);
    elseif ($r === 'added') wp_send_json_success(['message' => 'Merci ! Vous recevrez nos meilleures offres.']);
    else wp_send_json_error(['message' => 'Réessayez plus tard.']);
}

// Inscription utilisateur
add_action('user_register', function($uid) {
    $u = get_userdata($uid);
    if ($u && $u->user_email) vs08_nl_add($u->user_email, $u->first_name ?? '', $u->last_name ?? '', 'inscription');
});

// Commande WooCommerce
add_action('woocommerce_order_status_completed', 'vs08_nl_on_order');
add_action('woocommerce_order_status_processing', 'vs08_nl_on_order');
add_action('woocommerce_thankyou', 'vs08_nl_on_order');
function vs08_nl_on_order($oid) {
    if (!function_exists('wc_get_order')) return;
    $o = wc_get_order($oid);
    if (!$o) return;
    vs08_nl_add($o->get_billing_email(), $o->get_billing_first_name(), $o->get_billing_last_name(), 'commande');
}

// Désinscription
add_action('template_redirect', function() {
    if (!isset($_GET['vs08_unsub']) || !isset($_GET['token'])) return;
    global $wpdb;
    $wpdb->update($wpdb->prefix . 'vs08_newsletter', ['active' => 0], ['token' => sanitize_text_field($_GET['token'])]);
    wp_die('<div style="max-width:500px;margin:80px auto;text-align:center;font-family:sans-serif"><h1>Désinscription confirmée</h1><p style="color:#666;margin-top:16px">Vous ne recevrez plus nos emails.</p><a href="' . esc_url(home_url('/')) . '" style="display:inline-block;margin-top:24px;padding:12px 28px;background:#59b7b7;color:#fff;border-radius:100px;text-decoration:none;font-weight:700">Retour au site</a></div>', 'Désinscription');
});
