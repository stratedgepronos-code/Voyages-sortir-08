<?php
if (!defined('ABSPATH')) exit;

/* ============================================================
  Checkout: ne pas manipuler $wp_filter ici.
  Les plugins lourds sont déjà gérés par les mu-plugins dédiés.
============================================================ */

/* ============================================================
   SMTP — Envoi d'emails via Hostinger (obligatoire)
   Sans ça, wp_mail() utilise mail() de PHP qui est bloqué.
============================================================ */
add_action('phpmailer_init', function($phpmailer) {
    $phpmailer->isSMTP();
    $phpmailer->Host       = 'smtp.hostinger.com';
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Port       = 465;
    $phpmailer->SMTPSecure = 'ssl';
    $phpmailer->Username   = 'noreply@sortirmonde.fr';
    $phpmailer->Password   = '51000Vs08-)';
    $phpmailer->From       = 'noreply@sortirmonde.fr';
    $phpmailer->FromName   = 'Voyages Sortir 08';
});


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
    // Google Fonts : Playfair Display + Outfit (design system VS08)
    wp_register_style('vs08-fonts', 'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Outfit:wght@300;400;500;600;700&display=swap', [], '1.1');
    wp_enqueue_style('vs08-fonts');
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
    // Calendrier VS08 sur les pages de devis
    if (is_page(['devis-golf', 'devis-gratuit', 'devis-circuit', 'devis-sejour-vacances', 'devis-city-trip', 'devis-road-trip'])) {
        $cal_base = defined('VS08V_URL') ? VS08V_URL : content_url('/plugins/vs08-voyages/');
        wp_enqueue_style('vs08-calendar', $cal_base . 'assets/css/vs08-calendar.css', [], '1.5.0');
        wp_enqueue_script('vs08-calendar', $cal_base . 'assets/js/vs08-calendar.js', [], '1.5.0', true);
    }
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
function vs08_mail_devis_agence($subject, $body_html, $reply_to_email) {
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: Voyages Sortir 08 <noreply@sortirmonde.fr>',
    ];
    if (is_email($reply_to_email)) {
        $headers[] = 'Reply-To: ' . $reply_to_email;
    }
    // Circuits, séjours vacances, city trip, road trip → sortir08.ag@wanadoo.fr
    $ok = wp_mail('sortir08.ag@wanadoo.fr', $subject, $body_html, $headers);
    return $ok;
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
        $sdata = get_post_meta($id, '_vs08s_booking_data', true);
        if (!empty($sdata) && is_array($sdata) && ($sdata['type'] ?? '') === 'sejour') {
            $product_id = $id;
            $booking_data = $sdata;
            break;
        }
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
    $booking_type = $booking_data['type'] ?? 'golf';

    // Séjour: récap léger (évite les scans meta coûteux qui peuvent saturer la RAM).
    if ($booking_type === 'sejour') {
        $sm = $vid ? get_post_meta($vid, '_vs08s_meta', true) : [];
        if (!is_array($sm)) $sm = [];

        $date_dep = (string)($params['date_depart'] ?? '');
        $duree    = max(1, intval($sm['duree'] ?? 7));
        $duree_j  = max(1, intval($sm['duree_jours'] ?? ($duree + 1)));
        $date_ret = $date_dep ? date('Y-m-d', strtotime($date_dep . ' +' . $duree . ' days')) : '';
        $fmt = function($d) {
            return $d ? date_i18n('j F Y', strtotime($d)) : '';
        };

        $ap_names = [
            'CDG'=>'Paris CDG','ORY'=>'Paris Orly','LYS'=>'Lyon','MRS'=>'Marseille',
            'NCE'=>'Nice','TLS'=>'Toulouse','BOD'=>'Bordeaux','NTE'=>'Nantes',
            'SXB'=>'Strasbourg','LIL'=>'Lille',
        ];
        $ap_code = strtoupper((string)($params['aeroport'] ?? ''));
        $ap_dep  = $ap_names[$ap_code] ?? $ap_code;
        $dest_ap = strtoupper((string)($sm['iata_dest'] ?? 'Destination'));

        $hotel_nom = (string)($sm['hotel_nom'] ?? '');
        $hotel_etoiles = intval($sm['hotel_etoiles'] ?? 0);
        $pension_map = ['ai'=>'All Inclusive','pc'=>'Pension complète','dp'=>'Demi-pension','bb'=>'Petit-déjeuner','lo'=>'Logement seul'];
        $pension = $pension_map[$sm['pension'] ?? 'ai'] ?? 'All Inclusive';
        $transfert_map = ['groupes'=>'Transferts groupés','prives'=>'Transferts privés','inclus'=>'Inclus dans l\'hôtel','aucun'=>'Non inclus'];
        $transfert = $transfert_map[$sm['transfert_type'] ?? 'groupes'] ?? 'Transferts groupés';
        $nb = max(1, intval($params['nb_adultes'] ?? 2));

        echo '<div class="vs08v-checkout-recap-wrapper">';
        echo '<div class="vs08v-checkout-recap-card">';
        echo '<div class="vs08v-woo-recap">';
        echo '<h3>' . esc_html($titre) . '</h3>';
        echo '<h4>D&eacute;tails du s&eacute;jour</h4>';
        echo '<table>';
        echo '<tr><td>&#x1F4C5; Dates</td><td>' . esc_html($fmt($date_dep)) . ' &rarr; ' . esc_html($fmt($date_ret)) . '</td></tr>';
        echo '<tr><td>&#x1F319; Dur&eacute;e</td><td>' . $duree_j . ' jours / ' . $duree . ' nuits</td></tr>';
        echo '<tr><td>&#x2708;&#xFE0F; Vols</td><td>' . esc_html($ap_dep) . ' &rarr; ' . esc_html($dest_ap) . '</td></tr>';
        if (!empty($params['vol_aller_num'])) {
            $vol_aller_txt = (string)$params['vol_aller_num'];
            if (!empty($params['vol_aller_cie'])) $vol_aller_txt .= ' (' . $params['vol_aller_cie'] . ')';
            if (!empty($params['vol_aller_depart']) && !empty($params['vol_aller_arrivee'])) $vol_aller_txt .= ' · ' . $params['vol_aller_depart'] . ' → ' . $params['vol_aller_arrivee'];
            echo '<tr><td style="padding-left:24px">&#x1F6EB; Aller</td><td>' . esc_html($vol_aller_txt) . '</td></tr>';
        }
        if (!empty($params['vol_retour_num'])) {
            $vol_retour_txt = (string)$params['vol_retour_num'];
            if (!empty($params['vol_retour_depart']) && !empty($params['vol_retour_arrivee'])) $vol_retour_txt .= ' · ' . $params['vol_retour_depart'] . ' → ' . $params['vol_retour_arrivee'];
            echo '<tr><td style="padding-left:24px">&#x1F6EC; Retour</td><td>' . esc_html($vol_retour_txt) . '</td></tr>';
        }
        if ($hotel_nom) {
            $hotel_txt = $hotel_nom . ($hotel_etoiles ? ' ' . str_repeat('★', $hotel_etoiles) : '');
            echo '<tr><td>&#x1F3E8; H&ocirc;tel</td><td>' . esc_html($hotel_txt) . '</td></tr>';
        }
        echo '<tr><td>&#x1F37D;&#xFE0F; Formule</td><td>' . esc_html($pension) . '</td></tr>';
        echo '<tr><td>&#x1F690; Transferts</td><td>' . esc_html($transfert) . '</td></tr>';
        echo '<tr><td>&#x1F465; Voyageurs</td><td>' . $nb . ' adulte' . ($nb > 1 ? 's' : '') . '</td></tr>';
        echo '</table>';
        echo '</div></div></div>';
        return;
    }

    /* ── Date de depart ── */
    $date_dep = $params['date_depart'] ?? '';

    /* ── Nombre de nuits ── */
    $nights = 0;
    if ($vid) {
        $nights = (int) get_post_meta($vid, '_vs08v_nights', true);
        if (!$nights) {
            $sm_nights = get_post_meta($vid, '_vs08s_meta', true);
            if (is_array($sm_nights)) $nights = intval($sm_nights['duree'] ?? 0);
        }
    }
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

    /* ── Type de réservation (golf/sejour/circuit) ── */
    $is_sejour = ($booking_type === 'sejour');

    /* ── Green fees (golf uniquement) ── */
    $gf = 0;
    if (!$is_sejour && $vid) {
        $gf = (int) get_post_meta($vid, '_vs08v_green_fees', true);
        if (!$gf) $gf = (int) get_post_meta($vid, '_vs08v_nb_parcours', true);
        if (!$gf) $gf = (int) get_post_meta($vid, 'green_fees', true);
    }

    /* ── Transferts (séjour uniquement) ── */
    $transfert_label = '';
    if ($is_sejour && $vid) {
        $sm = get_post_meta($vid, '_vs08s_meta', true);
        if (is_array($sm)) {
            $tt = $sm['transfert_type'] ?? '';
            if (empty($tt)) $tt = 'groupes';
            $transfert_map = ['groupes'=>'Transferts groupés','prives'=>'Transferts privés','inclus'=>'Inclus dans l\'hôtel','aucun'=>'Non inclus'];
            $transfert_label = $transfert_map[$tt] ?? 'Transferts groupés';
        } else {
            $transfert_label = 'Transferts groupés';
        }
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
    if (!$nb) $nb = (int)($params['nb_adultes'] ?? 0);
    if (!$nb) $nb = (int)($devis['nb_total'] ?? 2);

    /* ── Hôtel (depuis les métadonnées du voyage) ── */
    $hotel_nom = '';
    $hotel_etoiles = '';
    if ($vid) {
        // Essayer vs08v_data (golf) puis _vs08s_meta (séjour)
        $vm = get_post_meta($vid, 'vs08v_data', true);
        if (!is_array($vm) || empty($vm['hotel_nom'])) {
            $vm = get_post_meta($vid, '_vs08s_meta', true);
        }
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
    if (!$is_sejour) {
        if ($gf > 0) {
            echo '<tr><td>&#x26F3; Parcours</td><td>' . $gf . ' green fees inclus</td></tr>';
        } else {
            echo '<tr><td>&#x26F3; Parcours</td><td>Green fees inclus</td></tr>';
        }
    }
    if ($is_sejour && $transfert_label) {
        echo '<tr><td>&#x1F690; Transferts</td><td>' . esc_html($transfert_label) . '</td></tr>';
    }
    echo '<tr><td>&#x1F465; Voyageurs</td><td>' . $nb . ' adulte' . ($nb > 1 ? 's' : '');
    if (!$is_sejour && $nb_g > 0 && $nb_ng > 0) {
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
        $pid = (int) ($item['product_id'] ?? 0);
        if (!$pid) continue;
        if (metadata_exists('post', $pid, '_vs08s_booking_data') || metadata_exists('post', $pid, '_vs08s_booking_token')) {
            continue;
        }
        $data = get_post_meta($pid, '_vs08v_booking_data', true);
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

/* ============================================================
   CARNET DE VOYAGE — Meta box admin sur les commandes WooCommerce
   Permet d'uploader des fichiers (PDF, images) que le client
   verra dans son espace membre sous "Carnet de voyage"
============================================================ */
add_action('add_meta_boxes', function() {
    add_meta_box(
        'vs08_carnet_voyage',
        '📋 Carnet de voyage (documents client)',
        'vs08_carnet_metabox_render',
        'shop_order',
        'normal',
        'default'
    );
    // HPOS compat — seulement si la méthode existe
    if (class_exists('Automattic\WooCommerce\Utilities\OrderUtil') && method_exists('Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_enabled')) {
        try {
            if (Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_enabled()) {
                add_meta_box('vs08_carnet_voyage', '📋 Carnet de voyage (documents client)', 'vs08_carnet_metabox_render', 'woocommerce_page_wc-orders', 'normal', 'default');
            }
        } catch (\Throwable $e) {}
    }
});

function vs08_carnet_metabox_render($post_or_order) {
    $order_id = is_object($post_or_order) && method_exists($post_or_order, 'get_id') ? $post_or_order->get_id() : (is_object($post_or_order) ? $post_or_order->ID : 0);
    if (!$order_id) return;
    wp_nonce_field('vs08_carnet_save', 'vs08_carnet_nonce');
    $files = get_post_meta($order_id, '_vs08_carnet_files', true);
    if (!is_array($files)) $files = [];
    ?>
    <style>
    .vs08-carnet-list{margin:0 0 12px;padding:0;list-style:none}
    .vs08-carnet-item{display:flex;align-items:center;gap:10px;padding:8px 12px;background:#f9f9f9;border:1px solid #e5e5e5;border-radius:8px;margin-bottom:6px;font-size:13px}
    .vs08-carnet-item a{color:#2a7f7f;font-weight:600;text-decoration:none;flex:1}
    .vs08-carnet-item .vs08-carnet-date{color:#999;font-size:11px}
    .vs08-carnet-item .vs08-carnet-del{color:#dc3545;cursor:pointer;font-size:16px;border:none;background:none;padding:0}
    </style>
    <ul class="vs08-carnet-list" id="vs08-carnet-list">
    <?php foreach ($files as $i => $f): ?>
        <li class="vs08-carnet-item">
            <a href="<?php echo esc_url($f['url']); ?>" target="_blank"><?php echo esc_html($f['name']); ?></a>
            <span class="vs08-carnet-date"><?php echo esc_html($f['date'] ?? ''); ?></span>
            <button type="button" class="vs08-carnet-del" onclick="this.closest('li').remove()">✕</button>
            <input type="hidden" name="vs08_carnet[<?php echo $i; ?>][url]" value="<?php echo esc_attr($f['url']); ?>">
            <input type="hidden" name="vs08_carnet[<?php echo $i; ?>][name]" value="<?php echo esc_attr($f['name']); ?>">
            <input type="hidden" name="vs08_carnet[<?php echo $i; ?>][date]" value="<?php echo esc_attr($f['date'] ?? ''); ?>">
        </li>
    <?php endforeach; ?>
    </ul>
    <button type="button" class="button" id="vs08-carnet-add" onclick="vs08CarnetAdd()">📎 Ajouter un document</button>
    <p class="description" style="margin-top:8px">Uploadez les vouchers, billets d'avion, programme détaillé... Le client les verra dans "Carnet de voyage" de son espace membre.</p>
    <script>
    var vs08CarnetIdx = <?php echo count($files); ?>;
    function vs08CarnetAdd() {
        var frame = wp.media({title:'Choisir un document', button:{text:'Ajouter au carnet'}, multiple:true});
        frame.on('select', function(){
            frame.state().get('selection').each(function(att){
                var a = att.toJSON();
                var i = vs08CarnetIdx++;
                var li = document.createElement('li');
                li.className = 'vs08-carnet-item';
                li.innerHTML = '<a href="'+a.url+'" target="_blank">'+a.filename+'</a>'
                    + '<span class="vs08-carnet-date"><?php echo esc_js(current_time('Y-m-d')); ?></span>'
                    + '<button type="button" class="vs08-carnet-del" onclick="this.closest(\'li\').remove()">✕</button>'
                    + '<input type="hidden" name="vs08_carnet['+i+'][url]" value="'+a.url+'">'
                    + '<input type="hidden" name="vs08_carnet['+i+'][name]" value="'+a.filename+'">'
                    + '<input type="hidden" name="vs08_carnet['+i+'][date]" value="<?php echo esc_js(current_time('Y-m-d')); ?>">';
                document.getElementById('vs08-carnet-list').appendChild(li);
            });
        });
        frame.open();
    }
    </script>
    <?php
}

add_action('save_post_shop_order', 'vs08_carnet_save', 20);
add_action('woocommerce_process_shop_order_meta', 'vs08_carnet_save', 20);
function vs08_carnet_save($order_id) {
    if (!isset($_POST['vs08_carnet_nonce']) || !wp_verify_nonce($_POST['vs08_carnet_nonce'], 'vs08_carnet_save')) return;
    $files = [];
    if (!empty($_POST['vs08_carnet']) && is_array($_POST['vs08_carnet'])) {
        foreach ($_POST['vs08_carnet'] as $f) {
            $url = esc_url_raw($f['url'] ?? '');
            $name = sanitize_text_field($f['name'] ?? '');
            $date = sanitize_text_field($f['date'] ?? '');
            if ($url) $files[] = ['url' => $url, 'name' => $name, 'date' => $date];
        }
    }
    update_post_meta($order_id, '_vs08_carnet_files', $files);
}

/* ============================================================
   SOLDE REMINDERS — Étendre aux circuits
   Le cron existant (vs08v_solde_reminder) appelle 
   VS08V_Emails::run_solde_reminders() qui ne traite que les golf.
   On ajoute un hook pour les circuits.
============================================================ */
add_action('vs08v_solde_reminder', 'vs08_circuit_solde_reminders');
function vs08_circuit_solde_reminders() {
    if (!function_exists('wc_get_orders') || !class_exists('VS08C_Meta')) return;
    $orders = wc_get_orders(['limit' => -1, 'status' => array_keys(wc_get_order_statuses()), 'return' => 'ids']);
    $today = date('Y-m-d');
    foreach ($orders as $order_id) {
        $order = wc_get_order($order_id);
        if (!$order) continue;
        $data = $order->get_meta('_vs08c_booking_data');
        if (empty($data) || !is_array($data) || ($data['type'] ?? '') !== 'circuit') continue;
        if (!class_exists('VS08V_Traveler_Space')) continue;
        $solde_info = VS08V_Traveler_Space::get_solde_info($order_id);
        if (!$solde_info || !$solde_info['solde_due'] || $solde_info['solde'] <= 0) continue;
        $params = $data['params'] ?? [];
        $date_depart = $params['date_depart'] ?? '';
        if (!$date_depart) continue;
        $circuit_id = (int)($data['circuit_id'] ?? 0);
        $m = VS08C_Meta::get($circuit_id);
        $delai_solde = (int)($m['delai_solde'] ?? 30);
        $deadline_ts = strtotime($date_depart) - ($delai_solde * 86400);
        $deadline_ymd = date('Y-m-d', $deadline_ts);
        $days_left = (strtotime($deadline_ymd) - strtotime($today)) / 86400;
        if ($days_left < 0) continue;
        $customer_id = $order->get_customer_id();
        if (!$customer_id) continue;
        $user = get_userdata($customer_id);
        $email = $user ? $user->user_email : $order->get_billing_email();
        if (!$email) continue;
        $titre = $data['circuit_titre'] ?? 'Circuit';
        $solde_fmt = number_format($solde_info['solde'], 2, ',', ' ');
        $solde_date_fmt = $solde_info['solde_date'] ?? date('d/m/Y', $deadline_ts);
        $espace_url = VS08V_Traveler_Space::base_url();
        $body = '<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:20px">'
            . '<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,.08)">'
            . '<div style="background:#1a3a3a;padding:24px;text-align:center;color:#fff;font-family:Georgia,serif;font-size:20px">Voyages Sortir 08</div>'
            . '<div style="padding:28px 32px">'
            . '<h2 style="color:#1a3a3a;margin:0 0 16px">Rappel : solde à régler</h2>'
            . '<p style="font-size:15px;color:#333;line-height:1.6">Bonjour,</p>'
            . '<p style="font-size:15px;color:#333;line-height:1.6">Pour votre circuit <strong>' . esc_html($titre) . '</strong>, il reste un solde de <strong>' . $solde_fmt . ' €</strong> à régler avant le <strong>' . esc_html($solde_date_fmt) . '</strong>.</p>'
            . '<p style="margin-top:20px"><a href="' . esc_url($espace_url) . '" style="display:inline-block;padding:12px 28px;background:#2a7f7f;color:#fff;text-decoration:none;border-radius:8px;font-weight:bold">Accéder à mon espace voyageur</a></p>'
            . '<p style="margin-top:16px;font-size:12px;color:#999">Dossier VS08-' . $order_id . '</p>'
            . '</div></div></body></html>';
        $headers = ['Content-Type: text/html; charset=UTF-8', 'From: Voyages Sortir 08 <noreply@sortirmonde.fr>'];
        if ($days_left <= 14 && $days_left > 3 && !$order->get_meta('_vs08c_solde_reminder_14')) {
            wp_mail([$email], 'Rappel : solde à régler avant le ' . $solde_date_fmt . ' — ' . $titre, $body, $headers);
            $order->update_meta_data('_vs08c_solde_reminder_14', current_time('mysql'));
            $order->save();
        } elseif ($days_left <= 3 && $days_left >= 0 && !$order->get_meta('_vs08c_solde_reminder_3')) {
            wp_mail([$email], 'Dernier rappel : solde à régler — ' . $titre, $body, $headers);
            $order->update_meta_data('_vs08c_solde_reminder_3', current_time('mysql'));
            $order->save();
        }
    }
}

// S'assurer que le cron est planifié
add_action('init', function() {
    if (!wp_next_scheduled('vs08v_solde_reminder')) {
        wp_schedule_event(time(), 'daily', 'vs08v_solde_reminder');
    }
});

/* ============================================================
   MESSAGERIE ESPACE MEMBRE — envoi email aux admins + historique
   Les messages sont TOUJOURS sauvegardés en base (même si l'email échoue)
============================================================ */
add_action('wp_ajax_vs08v_member_contact', function() {
    check_ajax_referer('vs08v_member_contact', 'nonce');
    $user = wp_get_current_user();
    if (!$user || !$user->ID) wp_send_json_error('Non connecté.');

    $sujet   = sanitize_text_field($_POST['sujet'] ?? '');
    $message = sanitize_textarea_field($_POST['message'] ?? '');
    $order_id = intval($_POST['order_id'] ?? 0);

    if (!$sujet || !$message) wp_send_json_error('Sujet et message requis.');

    $client_name  = trim($user->first_name . ' ' . $user->last_name) ?: $user->display_name;
    $client_email = $user->user_email;
    $dossier = $order_id ? 'VS08-' . $order_id : 'Question générale';

    // ── 1. TOUJOURS sauvegarder en base (messages admin) ──
    $msg_entry = [
        'date'         => current_time('Y-m-d H:i:s'),
        'date_fmt'     => current_time('d/m/Y H:i'),
        'user_id'      => $user->ID,
        'client_name'  => $client_name,
        'client_email' => $client_email,
        'order_id'     => $order_id,
        'dossier'      => $dossier,
        'sujet'        => $sujet,
        'message'      => $message,
        'email_sent'   => false,
    ];

    $all_messages = get_option('vs08_member_messages', []);
    if (!is_array($all_messages)) $all_messages = [];
    $all_messages[] = $msg_entry;
    if (count($all_messages) > 500) $all_messages = array_slice($all_messages, -500);
    update_option('vs08_member_messages', $all_messages, false);

    // Historique user
    $history = get_user_meta($user->ID, '_vs08_messages_sent', true);
    if (!is_array($history)) $history = [];
    $history[] = ['date' => $msg_entry['date_fmt'], 'sujet' => $sujet, 'message' => $message, 'order_id' => $order_id];
    if (count($history) > 50) $history = array_slice($history, -50);
    update_user_meta($user->ID, '_vs08_messages_sent', $history);

    // ── 2. Tenter l'envoi email ──
    $subject_mail = sprintf('Message client — %s — %s', $client_name, $sujet);

    $body = '<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:20px">'
        . '<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,.08)">'
        . '<div style="background:#1a3a3a;padding:20px 28px;color:#fff;font-family:Georgia,serif;font-size:18px">Message depuis l\'espace voyageur</div>'
        . '<div style="padding:24px 28px">'
        . '<table style="width:100%;border-collapse:collapse;font-size:14px;margin-bottom:16px">'
        . '<tr><td style="padding:8px;border:1px solid #e5e5e5;background:#f8f8f8;font-weight:bold;width:120px">Client</td><td style="padding:8px;border:1px solid #e5e5e5">' . esc_html($client_name) . '</td></tr>'
        . '<tr><td style="padding:8px;border:1px solid #e5e5e5;background:#f8f8f8;font-weight:bold">Email</td><td style="padding:8px;border:1px solid #e5e5e5"><a href="mailto:' . esc_attr($client_email) . '">' . esc_html($client_email) . '</a></td></tr>'
        . '<tr><td style="padding:8px;border:1px solid #e5e5e5;background:#f8f8f8;font-weight:bold">Dossier</td><td style="padding:8px;border:1px solid #e5e5e5">' . esc_html($dossier) . '</td></tr>'
        . '<tr><td style="padding:8px;border:1px solid #e5e5e5;background:#f8f8f8;font-weight:bold">Sujet</td><td style="padding:8px;border:1px solid #e5e5e5;font-weight:bold">' . esc_html($sujet) . '</td></tr>'
        . '</table>'
        . '<div style="background:#f9f6f0;border-radius:10px;padding:16px 20px;font-size:14px;line-height:1.7;color:#333">'
        . nl2br(esc_html($message))
        . '</div>'
        . '<p style="margin-top:16px;font-size:12px;color:#999">Répondez directement à ce mail pour contacter le client.</p>'
        . '</div></div></body></html>';

    // Utiliser l'email admin WordPress comme From (plus fiable sur Hostinger)
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: Voyages Sortir 08 <noreply@sortirmonde.fr>',
        'Reply-To: ' . $client_name . ' <' . $client_email . '>',
    ];

    // Envoyer séparément à chaque admin (évite les problèmes de destinataires multiples)
    $admins = ['sortir08.ag@wanadoo.fr', 'sortir08@wanadoo.fr'];
    $email_ok = false;
    foreach ($admins as $admin) {
        $result = wp_mail($admin, $subject_mail, $body, $headers);
        if ($result) $email_ok = true;
        error_log('[VS08 Contact] wp_mail to ' . $admin . ' => ' . ($result ? 'OK' : 'FAIL'));
    }

    // Mettre à jour le statut email dans le message sauvegardé
    if ($email_ok) {
        $all_messages[count($all_messages) - 1]['email_sent'] = true;
        update_option('vs08_member_messages', $all_messages, false);
    }

    // Toujours renvoyer succès car le message est sauvé en base
    wp_send_json_success('Message envoyé ! Nous vous répondrons dans les meilleurs délais.');
});

/* ── Page admin pour voir les messages reçus ── */
add_action('admin_menu', function() {
    add_menu_page(
        'Messages clients',
        '💬 Messages',
        'manage_options',
        'vs08-messages',
        'vs08_messages_admin_page',
        'dashicons-format-chat',
        29
    );
}, 6);

function vs08_messages_admin_page() {
    $messages = get_option('vs08_member_messages', []);
    if (!is_array($messages)) $messages = [];

    // Supprimer un message
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['idx']) && current_user_can('manage_options')) {
        check_admin_referer('vs08_msg_del_' . intval($_GET['idx']));
        $idx = intval($_GET['idx']);
        if (isset($messages[$idx])) { unset($messages[$idx]); $messages = array_values($messages); update_option('vs08_member_messages', $messages, false); }
        echo '<div class="notice notice-success"><p>Message supprimé.</p></div>';
    }

    $messages = array_reverse($messages); // plus récent en premier
    $base = admin_url('admin.php?page=vs08-messages');
    ?>
    <div class="wrap">
        <h1>💬 Messages clients (espace membre)</h1>
        <p>Messages envoyés par les clients depuis leur espace voyageur. Sauvegardés même si l'email échoue.</p>
        <table class="wp-list-table widefat striped" style="margin-top:16px">
            <thead><tr><th style="width:140px">Date</th><th>Client</th><th>Email</th><th>Dossier</th><th>Sujet</th><th>Message</th><th style="width:50px">Email</th><th style="width:30px"></th></tr></thead>
            <tbody>
            <?php if (empty($messages)): ?>
                <tr><td colspan="8" style="text-align:center;padding:24px;color:#999">Aucun message pour le moment.</td></tr>
            <?php else: foreach ($messages as $i => $m):
                $real_idx = count(get_option('vs08_member_messages', [])) - 1 - $i;
            ?>
                <tr>
                    <td style="font-size:12px;color:#666"><?php echo esc_html($m['date_fmt'] ?? $m['date'] ?? ''); ?></td>
                    <td><strong><?php echo esc_html($m['client_name'] ?? ''); ?></strong></td>
                    <td><a href="mailto:<?php echo esc_attr($m['client_email'] ?? ''); ?>"><?php echo esc_html($m['client_email'] ?? ''); ?></a></td>
                    <td><?php echo esc_html($m['dossier'] ?? ''); ?></td>
                    <td><strong><?php echo esc_html($m['sujet'] ?? ''); ?></strong></td>
                    <td style="font-size:12px;color:#555;max-width:300px"><?php echo esc_html(mb_substr($m['message'] ?? '', 0, 100)); ?><?php echo mb_strlen($m['message'] ?? '') > 100 ? '…' : ''; ?></td>
                    <td><?php echo !empty($m['email_sent']) ? '<span style="color:#059669">✓</span>' : '<span style="color:#dc2626">✗</span>'; ?></td>
                    <td><a href="<?php echo esc_url(wp_nonce_url($base . '&action=delete&idx=' . $real_idx, 'vs08_msg_del_' . $real_idx)); ?>" onclick="return confirm('Supprimer ?')" style="color:#dc2626">✕</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
