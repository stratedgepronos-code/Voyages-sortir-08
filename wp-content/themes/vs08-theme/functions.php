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
        wp_enqueue_style('vs08-front-page-v2', get_template_directory_uri() . '/assets/css/front-page-v2.css', ['vs08-main'], '2.0');
        wp_enqueue_style('vs08-section-univers', get_template_directory_uri() . '/assets/css/section-univers.css', [], '1.0.0');
        wp_enqueue_script('vs08-section-univers-js', get_template_directory_uri() . '/assets/js/section-univers.js', [], '1.0.0', true);
    }
    if (function_exists('is_checkout') && is_checkout() && !is_wc_endpoint_url('order-received')) {
        wp_enqueue_style('vs08-checkout', get_template_directory_uri() . '/assets/css/checkout.css', ['vs08-main'], '4.7');
        wp_enqueue_script('vs08-checkout-js', get_template_directory_uri() . '/assets/js/checkout.js', ['jquery'], '4.7', true);
    }
    wp_enqueue_script('vs08-main', get_template_directory_uri() . '/assets/js/main.js', [], '1.3', true);
    wp_enqueue_script('vs08-footer-terminal', get_template_directory_uri() . '/assets/js/footer-terminal.js', [], '1.0', true);
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
