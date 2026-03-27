<?php
/**
 * Template: Single Séjour All Inclusive
 * Calqué sur single-vs08_voyage.php (golf) — même qualité, sans golf
 */
if (!defined('ABSPATH')) exit;
get_header();

$id           = function_exists('vs08s_get_context_sejour_id') ? vs08s_get_context_sejour_id() : (int) get_the_ID();
if (!$id) {
    $id = (int) get_the_ID();
}
$m            = VS08S_Meta::get($id);
$destination  = $m['destination'] ?? '';
$pays         = $m['pays'] ?? '';
$flag         = $m['flag'] ?? '';
if (!$flag && class_exists('VS08V_MetaBoxes')) $flag = VS08V_MetaBoxes::get_flag_emoji($pays ?: $destination);
$duree        = intval($m['duree'] ?? 7);
$duree_jours  = intval($m['duree_jours'] ?? ($duree + 1));
$hotel_nom    = $m['hotel_nom'] ?? '';
$hotel_etoiles = intval($m['hotel_etoiles'] ?? 5);
$hotel_desc   = $m['hotel_description'] ?? '';
$hotel_equip  = $m['hotel_equipements'] ?? '';
$hotel_adresse = $m['hotel_adresse'] ?? '';
$hotel_map    = $m['hotel_map_url'] ?? '';
$pension_map  = ['ai'=>'All Inclusive','pc'=>'Pension complète','dp'=>'Demi-pension','bb'=>'Petit-déjeuner','lo'=>'Logement seul'];
$pension      = $pension_map[$m['pension'] ?? 'ai'] ?? 'All Inclusive';
$transfert_type = $m['transfert_type'] ?? 'groupes';
$transfert_map = ['groupes'=>'🚌 Transferts groupés','prives'=>'🚐 Transferts privés','inclus'=>'✅ Transferts inclus','aucun'=>''];
$galerie      = $m['galerie'] ?? [];
$hero_img     = !empty($galerie[0]) ? $galerie[0] : get_the_post_thumbnail_url($id, 'full');
if (!$hero_img) $hero_img = 'https://images.unsplash.com/photo-1590523741831-ab7e8b8f9c7f?w=1600&q=80';
$inclus_raw   = $m['inclus'] ?? '';
$non_inclus_raw = $m['non_inclus'] ?? '';
$desc_courte  = $m['description_courte'] ?? '';
$badge        = $m['badge'] ?? '';
$badge_map    = ['new'=>'Nouveauté','promo'=>'Promo','best'=>'Best-seller','derniere'=>'Dernières places'];
$aeroports    = $m['aeroports'] ?? [];
$annulation_texte = $m['annulation_texte'] ?? '';
$annulation   = $m['annulation'] ?? [];
$prix_appel   = VS08S_Calculator::prix_appel($id);
$transport_type = $m['transport_type'] ?? 'vol';

// Données riches hôtel (JSON IA)
$hd_raw = $m['hotel_data_json'] ?? '';
$hd = !empty($hd_raw) ? json_decode($hd_raw, true) : [];
if (!is_array($hd)) $hd = [];
$tripadvisor_note = floatval($hd['tripadvisor_note'] ?? 0);
$tripadvisor_url  = $hd['tripadvisor_url'] ?? '';
$dist_aero   = $hd['dist_aero'] ?? '';
$dist_centre = $hd['dist_centre'] ?? '';
$dist_plage  = $hd['dist_plage'] ?? '';
$loc_desc    = $hd['loc_desc'] ?? '';
$ai_details  = $hd['all_inclusive_details'] ?? '';
$restaurants = $hd['restaurants'] ?? [];
$bars        = $hd['bars'] ?? [];
$piscines    = $hd['piscines'] ?? [];
$plage       = $hd['plage'] ?? [];
$animations  = $hd['animations'] ?? [];
$spa_data    = $hd['spa'] ?? [];
$enfants     = $hd['enfants'] ?? [];
$sports      = $hd['sports'] ?? [];
$chambres    = $hd['chambres'] ?? [];
$has_rich    = !empty($hd);

// Aéroports pour JS
$aeroports_js = [];
foreach ($aeroports as $a) {
    $aeroports_js[] = [
        'code'         => strtoupper($a['code'] ?? ''),
        'ville'        => $a['ville'] ?? '',
        'supplement'   => floatval($a['supplement'] ?? 0),
        'periodes_vol' => $a['periodes_vol'] ?? [],
        'jours_direct' => $a['jours_direct'] ?? [1,2,3,4,5,6,7],
    ];
}
$periodes_fermees_js = $m['periodes_fermees_vente'] ?? [];

$has_hotel    = !empty($hotel_nom);
$has_compris  = !empty($inclus_raw) || !empty($non_inclus_raw);
$has_map      = $has_hotel && (!empty($hotel_adresse) || !empty($hotel_map));
$has_equip    = !empty($hotel_equip);
?>

<?php
$vs08s_js_flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
if (defined('JSON_UNESCAPED_UNICODE')) {
    $vs08s_js_flags |= JSON_UNESCAPED_UNICODE;
}
$vs08s_payload = [
    'id'                     => (int) $id,
    'titre'                  => get_the_title($id) ?: get_the_title(),
    'duree'                  => $duree,
    'iata_dest'              => strtoupper($m['iata_dest'] ?? ''),
    'aeroports'              => $aeroports_js,
    'periodes_fermees_vente' => $periodes_fermees_js,
    'vol_escales_autorisees' => !empty($m['vol_escales_autorisees']),
    'vol_escale_max_heures'  => floatval($m['vol_escale_max_heures'] ?? 5),
    'hotel_code'             => $m['hotel_code'] ?? '',
    'hotel_codes'            => $m['hotel_codes'] ?? [],
    'pension'                => $m['pension'] ?? 'ai',
    'transfert_prix'         => floatval($m['transfert_prix'] ?? 0),
    'marge_type'             => $m['marge_type'] ?? 'pourcentage',
    'marge_valeur'           => floatval($m['marge_valeur'] ?? 15),
    'prix_bagage_soute'      => floatval($m['prix_bagage_soute'] ?? 0),
    'prix_bagage_cabine'     => floatval($m['prix_bagage_cabine'] ?? 0),
    'acompte_pct'            => floatval($m['acompte_pct'] ?? 30),
    'delai_solde'            => (int) ($m['delai_solde'] ?? 30),
    'booking_url'            => $id ? home_url('/reservation-sejour/' . (int) $id) : '',
    'rest_url'               => rest_url('vs08s/v1/'),
    'nonce'                  => wp_create_nonce('wp_rest'),
    'ajax_url'               => admin_url('admin-ajax.php'),
];
?>
<script>var VS08S_DATA=<?php echo wp_json_encode($vs08s_payload, $vs08s_js_flags); ?>;</script>

<style>
/* ═══ HERO ═══ */
.sv-hero{position:relative;height:78vh;min-height:560px;display:flex;align-items:flex-end;background-size:cover;background-position:center;background-color:#1a1a1a}
.sv-hero-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(10,28,28,.96) 0%,rgba(10,28,28,.2) 60%,transparent 100%)}
.sv-hero-content{position:relative;z-index:2;width:100%;padding:0 80px 56px;display:flex;flex-direction:column;align-items:flex-start}
.sv-hero-dest{display:inline-flex;align-items:center;gap:8px;background:rgba(89,183,183,.18);border:1px solid rgba(89,183,183,.4);color:#7ecece;padding:5px 14px;border-radius:100px;font-size:12px;font-weight:700;font-family:'Outfit',sans-serif;margin-bottom:14px}
.sv-hero h1{font-size:clamp(28px,4.5vw,58px);color:#fff;font-family:'Playfair Display',serif;line-height:1.1;margin:0 0 16px;max-width:700px}
.sv-hero-meta{display:flex;gap:8px;flex-wrap:wrap}
.sv-meta-chip{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);padding:5px 12px;border-radius:100px;font-size:12px;font-weight:600;color:#fff;font-family:'Outfit',sans-serif}
/* ═══ NAVBAR ═══ */
.sv-navbar{position:sticky;top:0;z-index:500;background:#fff;border-bottom:2px solid #e9ecef;box-shadow:0 2px 12px rgba(0,0,0,.08)}
.sv-navbar-inner{max-width:1460px;margin:0 auto;padding:0 80px;display:flex;align-items:stretch;overflow-x:auto;scrollbar-width:none}
.sv-navbar-inner::-webkit-scrollbar{display:none}
.sv-nav-btn{background:none;border:none;padding:14px 18px;font-size:13px;font-weight:600;color:#6b7280;cursor:pointer;white-space:nowrap;font-family:'Outfit',sans-serif;border-bottom:3px solid transparent;transition:all .2s}
.sv-nav-btn:hover{color:#0f2424}
.sv-nav-btn.active{color:#59b7b7;border-bottom-color:#59b7b7}
/* ═══ PAGE ═══ */
.sv-page{background:#f9f6f0;padding:40px 0 60px}
.sv-page-inner{max-width:1460px;margin:0 auto;padding:0 80px;display:grid;grid-template-columns:1fr 390px;gap:36px;align-items:start}
.sv-left-col{display:flex;flex-direction:column;gap:20px}
.sv-card{background:#fff;border-radius:16px;padding:32px;box-shadow:0 1px 8px rgba(0,0,0,.06)}
.sv-section-title{font-size:20px;font-weight:700;color:#0f2424;font-family:'Playfair Display',serif;margin:0 0 20px;display:flex;align-items:center;gap:10px}
.sv-desc{font-size:15px;line-height:1.75;color:#374151;font-family:'Outfit',sans-serif}
.sv-desc p{margin:0 0 1em}
/* Highlights */
.sv-highlights{display:grid;grid-template-columns:repeat(auto-fit,minmax(100px,1fr));gap:12px;margin-top:20px}
.sv-hl{text-align:center;padding:14px 8px;background:#f9f6f0;border-radius:12px;border:1px solid #ede9e0}
.sv-hl-icon{font-size:24px;margin-bottom:4px}
.sv-hl-val{font-size:16px;font-weight:800;color:#0f2424;font-family:'Outfit',sans-serif}
.sv-hl-lbl{font-size:11px;color:#6b7280;font-family:'Outfit',sans-serif;margin-top:2px}
/* Carousel */
.sv-carousel-wrap{background:#fff;border-radius:16px;padding:32px;box-shadow:0 1px 8px rgba(0,0,0,.06)}
.sv-carousel{position:relative;overflow:hidden;border-radius:12px;aspect-ratio:16/9;background:#f9f6f0}
.sv-carousel-track{display:flex;height:100%;transition:transform .65s cubic-bezier(.4,0,.2,1)}
.sv-carousel-track img{min-width:100%;height:100%;object-fit:cover;flex-shrink:0}
.sv-carousel-btn{position:absolute;top:50%;transform:translateY(-50%);background:rgba(15,36,36,.45);backdrop-filter:blur(6px);border:1px solid rgba(255,255,255,.2);color:#fff;width:44px;height:44px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:22px;z-index:5}
.sv-carousel-btn:hover{background:rgba(15,36,36,.7)}
.sv-carousel-counter{position:absolute;bottom:12px;right:12px;background:rgba(0,0,0,.5);color:#fff;padding:4px 12px;border-radius:100px;font-size:12px;font-family:'Outfit',sans-serif}
.sv-dots{display:flex;gap:6px;justify-content:center;margin-top:10px}
.sv-dot{width:8px;height:8px;background:#d1d5db;border-radius:50%;cursor:pointer;transition:all .2s}
.sv-dot.active{background:#59b7b7;width:24px;border-radius:4px}
/* Equip */
.sv-equip-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px;margin-top:14px}
.sv-equip-item{display:flex;align-items:center;gap:8px;padding:8px 12px;background:#f0f9f9;border-radius:8px;font-size:13px;font-family:'Outfit',sans-serif;color:#0f2424}
/* Inclus */
.sv-inclus-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px}
.sv-inclus-box{padding:24px;border-radius:14px}
.sv-inclus-box.yes{background:#ecfdf5;border:1px solid rgba(89,183,183,.2)}
.sv-inclus-box.no{background:#fef2f2;border:1px solid rgba(220,38,38,.1)}
.sv-inclus-box h3{font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin:0 0 12px;font-family:'Outfit',sans-serif}
.sv-inclus-box.yes h3{color:#59b7b7}
.sv-inclus-box.no h3{color:#dc2626}
.sv-inclus-box ul{margin:0;padding:0;list-style:none}
.sv-inclus-box li{padding:6px 0;font-size:14px;color:#374151;padding-left:20px;position:relative;font-family:'Outfit',sans-serif}
.sv-inclus-box.yes li::before{content:'✓';position:absolute;left:0;color:#59b7b7;font-weight:bold}
.sv-inclus-box.no li::before{content:'✗';position:absolute;left:0;color:#dc2626;font-weight:bold}
/* Right col */
.sv-right-col{position:sticky;top:70px;display:flex;flex-direction:column;gap:14px;align-self:start}
.sv-calc-card{background:#fff;border-radius:18px;padding:24px;box-shadow:0 4px 28px rgba(0,0,0,.1);overflow-y:auto;max-height:min(85vh,720px);scrollbar-width:none}
.sv-calc-card::-webkit-scrollbar{display:none}
.sv-calc-title{font-size:18px;font-weight:700;color:#0f2424;font-family:'Playfair Display',serif;margin-bottom:4px}
.sv-calc-sub{font-size:12px;color:#6b7280;font-family:'Outfit',sans-serif;margin-bottom:16px}
.sv-field{margin-bottom:14px}
.sv-field label{display:block;font-size:10px;font-weight:700;color:#1a3a3a;text-transform:uppercase;letter-spacing:1px;margin-bottom:5px;font-family:'Outfit',sans-serif}
.sv-field select,.sv-field input{width:100%;border:1.5px solid #e5e7eb;border-radius:10px;padding:10px 12px;font-size:13px;font-family:'Outfit',sans-serif;color:#0f2424;background:#fafafa}
.sv-field select:focus,.sv-field input:focus{border-color:#59b7b7;outline:none}
.sv-field-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.sv-vol-st{margin-top:6px;font-size:12px;font-family:'Outfit',sans-serif}
.sv-price-loading{display:none;text-align:center;padding:16px;font-size:13px;color:#6b7280;font-family:'Outfit',sans-serif}
.sv-price-box{background:#f9f6f0;border-radius:14px;padding:18px;text-align:center}
.sv-price-from{font-size:11px;color:#6b7280;margin:0 0 4px;font-family:'Outfit',sans-serif}
.sv-price-main{font-size:32px;font-weight:800;color:#0f2424;font-family:'Outfit',sans-serif}
.sv-price-per{font-size:12px;color:#6b7280;font-family:'Outfit',sans-serif;margin:2px 0 0}
.sv-price-acompte{font-size:11px;color:#59b7b7;font-weight:600;margin-top:6px;font-family:'Outfit',sans-serif}
.sv-btn-reserver{display:block;width:100%;padding:16px;background:#e8724a;color:#fff;border:none;border-radius:14px;font-size:17px;font-weight:700;font-family:'Outfit',sans-serif;cursor:pointer;margin-top:16px;transition:all .3s}
.sv-btn-reserver:hover{background:#d4603c;transform:translateY(-2px)}
.sv-btn-reserver:disabled{opacity:.5;cursor:not-allowed;transform:none}
.sv-reassurance{margin-top:14px;font-size:11px;color:#6b7280;font-family:'Outfit',sans-serif;line-height:1.6}
.sv-reass{display:flex;gap:8px;padding:6px 0}
.sv-reass-icon{font-size:16px;flex-shrink:0}
.sv-actions-card{display:flex;flex-direction:column;gap:7px}
.sv-action-btn{display:flex;align-items:center;gap:8px;padding:10px 14px;border-radius:12px;font-size:13px;font-weight:600;font-family:'Outfit',sans-serif;cursor:pointer;text-decoration:none;border:1.5px solid #e5e7eb;background:#fff;color:#374151;transition:all .2s}
.sv-action-btn:hover{border-color:#59b7b7;background:#edf8f8}
.sv-devis-card{background:linear-gradient(145deg,#0f2424,#1a4a3a);border-radius:18px;padding:22px;text-align:center}
.sv-devis-card h3{font-family:'Playfair Display',serif;font-size:16px;color:#fff;margin:8px 0 8px}
.sv-devis-card p{font-size:12px;color:rgba(255,255,255,.65);margin:0 0 16px;line-height:1.55;font-family:'Outfit',sans-serif}
.sv-devis-btn{display:inline-block;padding:10px 24px;background:#59b7b7;color:#fff;border-radius:100px;font-weight:700;font-size:13px;text-decoration:none;font-family:'Outfit',sans-serif;transition:all .25s}
.sv-devis-btn:hover{background:#3d9a9a;transform:translateY(-2px);color:#fff}
/* Lightbox */
.sv-lightbox{position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.92);display:none;align-items:center;justify-content:center;flex-direction:column}
.sv-lightbox.active{display:flex}
.sv-lb-close{position:absolute;top:20px;right:24px;background:none;border:none;color:#fff;font-size:32px;cursor:pointer;z-index:2}
.sv-lb-prev,.sv-lb-next{position:absolute;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.15);border:none;color:#fff;width:52px;height:52px;border-radius:50%;cursor:pointer;font-size:26px;z-index:2}
.sv-lb-prev{left:20px}.sv-lb-next{right:20px}
.sv-lb-img-wrap{max-width:90vw;max-height:80vh;display:flex;align-items:center;justify-content:center}
.sv-lb-img{max-width:100%;max-height:80vh;border-radius:8px;object-fit:contain}
.sv-lb-counter{color:#fff;font-size:13px;margin-top:12px;font-family:'Outfit',sans-serif}
/* Sticky bar */
.sv-sticky-bar{position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:2px solid #e9ecef;padding:10px 24px;z-index:600;transform:translateY(100%);transition:transform .3s;box-shadow:0 -4px 20px rgba(0,0,0,.1)}
.sv-sticky-bar.visible{transform:translateY(0)}
.sv-sticky-bar-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between}
.sv-sticky-bar-total{font-size:22px;font-weight:800;color:#0f2424;font-family:'Outfit',sans-serif}
.sv-sticky-bar-sub{font-size:11px;color:#6b7280;font-family:'Outfit',sans-serif}
.sv-sticky-btn{background:#e8724a;color:#fff;border:none;padding:12px 28px;border-radius:100px;font-size:15px;font-weight:700;font-family:'Outfit',sans-serif;cursor:pointer}
.sv-sticky-btn:disabled{opacity:.5;cursor:not-allowed}
/* Print */
.sv-print-logo{display:none}
@media print{.sv-navbar,.sv-right-col,.sv-carousel-wrap,.sv-sticky-bar,.sv-actions-card,.sv-devis-card,.sv-lightbox{display:none!important}.sv-print-logo{display:block!important}.sv-page-inner{grid-template-columns:1fr!important}.sv-card{box-shadow:none!important;border:1px solid #e5e7eb}}
/* Responsive */
@media(max-width:1100px){.sv-page-inner{grid-template-columns:1fr;padding:0 24px}.sv-right-col{position:static}.sv-navbar-inner{padding:0 24px}.sv-hero-content{padding:0 24px 40px}}
@media(max-width:768px){.sv-page-inner{padding:0 16px}.sv-navbar-inner{padding:0 16px}.sv-hero-content{padding:0 20px 36px}.sv-highlights{grid-template-columns:1fr 1fr}.sv-inclus-grid{grid-template-columns:1fr}.sv-equip-grid{grid-template-columns:1fr 1fr}}
@media(max-width:480px){.sv-hero{height:50vh;min-height:360px}.sv-hero h1{font-size:24px}.sv-highlights{grid-template-columns:1fr}.sv-card{padding:20px;border-radius:14px}.sv-navbar{display:none}.sv-field-row{grid-template-columns:1fr}}
</style>

<!-- Logo imprimé uniquement -->
<div class="sv-print-logo" style="text-align:center;padding:18px 30px 12px;border-bottom:2px solid #e9ecef;background:#fff">
    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/logo.png'); ?>" alt="<?php bloginfo('name'); ?>" style="height:40px">
    <p style="margin:6px 0 0;font-size:11px;color:#6b7280">www.voyagessortir08.fr — 03 26 65 28 63</p>
</div>

<!-- ═══ HERO ═══ -->
<section class="sv-hero" style="background-image:url('<?php echo esc_url($hero_img); ?>')">
    <div class="sv-hero-overlay"></div>
    <div class="sv-hero-content">
        <div class="sv-hero-dest">
            <?php if ($flag): ?><span style="font-size:18px"><?php echo $flag; ?></span><?php endif; ?>
            <?php echo esc_html($destination); ?>
        </div>
        <h1><?php the_title(); ?></h1>
        <div class="sv-hero-meta">
            <div class="sv-meta-chip">🗓️ <?php echo $duree_jours; ?> jours / <?php echo $duree; ?> nuits</div>
            <div class="sv-meta-chip">🏨 <?php echo esc_html($hotel_nom); ?> <?php echo str_repeat('★', $hotel_etoiles); ?></div>
            <div class="sv-meta-chip">🍽️ <?php echo esc_html($pension); ?></div>
            <?php if ($transfert_type !== 'aucun' && !empty($transfert_map[$transfert_type])): ?>
            <div class="sv-meta-chip"><?php echo esc_html($transfert_map[$transfert_type]); ?></div>
            <?php endif; ?>
            <?php if ($badge && isset($badge_map[$badge])): ?>
            <div class="sv-meta-chip" style="background:rgba(232,114,74,.8)"><?php echo esc_html($badge_map[$badge]); ?></div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ═══ NAVBAR ═══ -->
<nav class="sv-navbar" id="sv-navbar">
    <div class="sv-navbar-inner">
        <button class="sv-nav-btn active" onclick="svScrollTo('sec-presentation')">📄 Présentation</button>
        <?php if ($has_hotel): ?><button class="sv-nav-btn" onclick="svScrollTo('sec-hebergement')">🏨 Hébergement</button><?php endif; ?>
        <?php if (!empty($restaurants) || !empty($bars)): ?><button class="sv-nav-btn" onclick="svScrollTo('sec-resto')">🍽️ Restaurants</button><?php endif; ?>
        <?php if ($has_equip): ?><button class="sv-nav-btn" onclick="svScrollTo('sec-equipements')">🏊 Équipements</button><?php endif; ?>
        <?php if ($has_compris): ?><button class="sv-nav-btn" onclick="svScrollTo('sec-compris')">✅ Inclus</button><?php endif; ?>
        <?php if ($has_map): ?><button class="sv-nav-btn" onclick="svScrollTo('sec-map')">🗺️ Carte</button><?php endif; ?>
        <?php if (count($galerie) > 1): ?><button class="sv-nav-btn" onclick="svScrollTo('sec-photos')">📷 Photos</button><?php endif; ?>
    </div>
</nav>

<!-- ═══ PAGE ═══ -->
<div class="sv-page" id="sv-page" data-vs08-sejour-id="<?php echo esc_attr((string) (int) $id); ?>">
<div class="sv-page-inner">

    <!-- ══ COLONNE GAUCHE ══ -->
    <div class="sv-left-col">

        <!-- PRÉSENTATION -->
        <div class="sv-card" id="sec-presentation">
            <h2 class="sv-section-title">✦ Ce séjour en quelques mots</h2>
            <?php if ($desc_courte): ?>
            <p style="font-size:16px;line-height:1.7;color:#374151;font-family:'Outfit',sans-serif;margin:0 0 16px"><?php echo nl2br(esc_html($desc_courte)); ?></p>
            <?php endif; ?>
            <div class="sv-desc"><?php the_content(); ?></div>
            <div class="sv-highlights">
                <div class="sv-hl"><div class="sv-hl-icon">✈️</div><div class="sv-hl-val" style="font-size:13px">Vol A/R</div><div class="sv-hl-lbl">Inclus</div></div>
                <div class="sv-hl"><div class="sv-hl-icon">🌙</div><div class="sv-hl-val" style="font-size:18px"><?php echo $duree_jours; ?>j / <?php echo $duree; ?>n</div><div class="sv-hl-lbl">Durée</div></div>
                <div class="sv-hl"><div class="sv-hl-icon">🏨</div><div class="sv-hl-val"><?php echo $hotel_etoiles; ?>★</div><div class="sv-hl-lbl">Hôtel</div></div>
                <div class="sv-hl"><div class="sv-hl-icon">🍽️</div><div class="sv-hl-val" style="font-size:12px"><?php echo esc_html($pension); ?></div><div class="sv-hl-lbl">Pension</div></div>
                <?php if ($transfert_type !== 'aucun'): ?>
                <div class="sv-hl"><div class="sv-hl-icon"><?php echo ['groupes'=>'🚌','prives'=>'🚐','inclus'=>'✅'][$transfert_type] ?? '🚌'; ?></div><div class="sv-hl-val" style="font-size:12px">Transferts</div><div class="sv-hl-lbl">Inclus</div></div>
                <?php endif; ?>
                <div class="sv-hl"><div class="sv-hl-icon">🛡️</div><div class="sv-hl-val" style="font-size:12px">APST</div><div class="sv-hl-lbl">Garantie</div></div>
            </div>
        </div>

        <!-- HÉBERGEMENT -->
        <?php if ($has_hotel): ?>
        <div class="sv-card" id="sec-hebergement">
            <h2 class="sv-section-title">🏨 Votre hébergement</h2>

            <!-- En-tête : Nom + étoiles + TripAdvisor -->
            <div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:16px">
                <div style="flex:1;min-width:280px">
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                        <div style="font-family:'Playfair Display',serif;font-size:22px;font-weight:700;color:#0f2424"><?php echo esc_html($hotel_nom); ?></div>
                        <div style="font-size:16px;color:#f59e0b"><?php echo str_repeat('★', $hotel_etoiles); ?></div>
                    </div>

                    <?php if ($tripadvisor_note > 0):
                        $ta_full = floor($tripadvisor_note); $ta_half = ($tripadvisor_note - $ta_full) >= 0.3 ? 1 : 0; $ta_empty = 5 - $ta_full - $ta_half;
                        $ta_tag = $tripadvisor_url ? 'a' : 'span'; $ta_href = $tripadvisor_url ? ' href="'.esc_url($tripadvisor_url).'" target="_blank" rel="noopener"' : '';
                    ?>
                    <div style="margin-top:8px">
                        <<?php echo $ta_tag; ?><?php echo $ta_href; ?> style="display:inline-flex;align-items:center;gap:0;text-decoration:none;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);border:1px solid #e5e7eb">
                            <span style="display:flex;align-items:center;gap:6px;background:#34e0a1;padding:6px 12px">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><circle cx="7" cy="14" r="3.2" stroke="#fff" stroke-width="1.4"/><circle cx="7" cy="14" r="1.1" fill="#fff"/><circle cx="17" cy="14" r="3.2" stroke="#fff" stroke-width="1.4"/><circle cx="17" cy="14" r="1.1" fill="#fff"/><path d="M12 6.5C8.8 6.5 5.5 8.3 4 11h1.8c1.2-1.6 3.5-2.8 6.2-2.8s5 1.2 6.2 2.8H20c-1.5-2.7-4.8-4.5-8-4.5z" fill="#fff"/><polygon points="12,3.5 10.8,6 13.2,6" fill="#fff"/></svg>
                                <span style="font-size:16px;font-weight:800;color:#fff;font-family:'Outfit',sans-serif"><?php echo number_format($tripadvisor_note, 1, ',', ''); ?></span>
                            </span>
                            <span style="display:flex;align-items:center;gap:5px;background:#fff;padding:6px 14px 6px 10px">
                                <span style="display:inline-flex;gap:3px">
                                    <?php for ($b = 0; $b < $ta_full; $b++): ?><svg width="13" height="13" viewBox="0 0 16 16"><circle cx="8" cy="8" r="7.5" fill="#00aa6c"/></svg><?php endfor; ?>
                                    <?php if ($ta_half): ?><svg width="13" height="13" viewBox="0 0 16 16"><defs><clipPath id="ta-h"><rect x="0" y="0" width="8" height="16"/></clipPath></defs><circle cx="8" cy="8" r="7.5" fill="#dce8e3"/><circle cx="8" cy="8" r="7.5" fill="#00aa6c" clip-path="url(#ta-h)"/></svg><?php endif; ?>
                                    <?php for ($b = 0; $b < $ta_empty; $b++): ?><svg width="13" height="13" viewBox="0 0 16 16"><circle cx="8" cy="8" r="7.5" fill="#dce8e3"/></svg><?php endfor; ?>
                                </span>
                                <span style="font-size:11px;font-weight:600;color:#00aa6c;font-family:'Outfit',sans-serif;letter-spacing:.3px;text-transform:uppercase">Tripadvisor</span>
                            </span>
                        </<?php echo $ta_tag; ?>>
                    </div>
                    <?php endif; ?>

                    <?php if ($hotel_adresse): ?>
                    <div style="font-size:13px;color:#6b7280;font-family:'Outfit',sans-serif;margin-top:10px">📍 <?php echo esc_html($hotel_adresse); ?></div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($galerie[0])): ?>
                <div style="flex:0 0 280px">
                    <img src="<?php echo esc_url($galerie[0]); ?>" alt="<?php echo esc_attr($hotel_nom); ?>" style="width:100%;height:200px;object-fit:cover;border-radius:14px;border:1px solid #e5e7eb">
                </div>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <?php if ($hotel_desc): ?>
            <div style="font-size:14px;line-height:1.75;color:#374151;font-family:'Outfit',sans-serif;margin-bottom:16px"><?php echo nl2br(esc_html($hotel_desc)); ?></div>
            <?php endif; ?>

            <!-- Distance cards -->
            <?php if ($dist_aero || $dist_centre || $dist_plage): ?>
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
                <?php if ($dist_aero): ?>
                <div style="flex:1;min-width:120px;background:#f9f6f0;border-radius:12px;padding:14px;text-align:center">
                    <div style="font-size:22px">✈️</div>
                    <div style="font-size:12px;font-weight:700;color:#0f2424;font-family:'Outfit',sans-serif;margin-top:4px">Aéroport</div>
                    <div style="font-size:14px;font-weight:800;color:#59b7b7;font-family:'Outfit',sans-serif"><?php echo esc_html($dist_aero); ?> km</div>
                </div>
                <?php endif; ?>
                <?php if ($dist_centre): ?>
                <div style="flex:1;min-width:120px;background:#f9f6f0;border-radius:12px;padding:14px;text-align:center">
                    <div style="font-size:22px">🏙️</div>
                    <div style="font-size:12px;font-weight:700;color:#0f2424;font-family:'Outfit',sans-serif;margin-top:4px">Centre-ville</div>
                    <div style="font-size:14px;font-weight:800;color:#59b7b7;font-family:'Outfit',sans-serif"><?php echo esc_html($dist_centre); ?> km</div>
                </div>
                <?php endif; ?>
                <?php if ($dist_plage): ?>
                <div style="flex:1;min-width:120px;background:#f9f6f0;border-radius:12px;padding:14px;text-align:center">
                    <div style="font-size:22px">🏖️</div>
                    <div style="font-size:12px;font-weight:700;color:#0f2424;font-family:'Outfit',sans-serif;margin-top:4px">Plage</div>
                    <div style="font-size:14px;font-weight:800;color:#59b7b7;font-family:'Outfit',sans-serif"><?php echo $dist_plage == '0' ? 'Sur place' : esc_html($dist_plage) . ' m'; ?></div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- All Inclusive details -->
            <?php if ($ai_details): ?>
            <div style="background:#edf8f8;border:1px solid #b7dfdf;border-radius:12px;padding:16px;margin-bottom:16px">
                <div style="font-size:13px;font-weight:700;color:#0f2424;font-family:'Outfit',sans-serif;margin-bottom:6px">🍽️ Votre formule <?php echo esc_html($pension); ?></div>
                <div style="font-size:13px;line-height:1.7;color:#374151;font-family:'Outfit',sans-serif"><?php echo nl2br(esc_html($ai_details)); ?></div>
            </div>
            <?php endif; ?>

            <!-- Badges -->
            <div style="display:flex;flex-wrap:wrap;gap:8px">
                <span style="background:#edf8f8;border:1px solid #b7dfdf;border-radius:8px;padding:5px 10px;font-size:12px;font-family:'Outfit',sans-serif;color:#1a3a3a">🍽️ <?php echo esc_html($pension); ?></span>
                <?php if ($transfert_type !== 'aucun'): ?>
                <span style="background:#edf8f8;border:1px solid #b7dfdf;border-radius:8px;padding:5px 10px;font-size:12px;font-family:'Outfit',sans-serif;color:#1a3a3a"><?php echo esc_html($transfert_map[$transfert_type] ?? ''); ?></span>
                <?php endif; ?>
                <span style="background:#edf8f8;border:1px solid #b7dfdf;border-radius:8px;padding:5px 10px;font-size:12px;font-family:'Outfit',sans-serif;color:#1a3a3a">🌙 <?php echo $duree; ?> nuits</span>
                <?php if (!empty($hd['nb_chambres_total'])): ?>
                <span style="background:#edf8f8;border:1px solid #b7dfdf;border-radius:8px;padding:5px 10px;font-size:12px;font-family:'Outfit',sans-serif;color:#1a3a3a">🏨 <?php echo esc_html($hd['nb_chambres_total']); ?> chambres</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- RESTAURANTS & BARS -->
        <?php if (!empty($restaurants) || !empty($bars)): ?>
        <div class="sv-card">
            <h2 class="sv-section-title">🍽️ Restaurants & Bars</h2>
            <?php if (!empty($restaurants)): ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;margin-bottom:16px">
                <?php foreach ($restaurants as $resto): if (empty($resto['nom']) && empty($resto['desc'])) continue; ?>
                <div style="background:#f9f6f0;border-radius:12px;padding:16px">
                    <div style="font-size:15px;font-weight:700;color:#0f2424;font-family:'Outfit',sans-serif;margin-bottom:4px">🍽️ <?php echo esc_html($resto['nom'] ?? 'Restaurant'); ?></div>
                    <?php if (!empty($resto['cuisine'])): ?><div style="font-size:11px;color:#59b7b7;font-weight:600;font-family:'Outfit',sans-serif;margin-bottom:6px"><?php echo esc_html(ucfirst($resto['type'] ?? '') . ' · ' . $resto['cuisine']); ?></div><?php endif; ?>
                    <?php if (!empty($resto['desc'])): ?><div style="font-size:13px;color:#374151;font-family:'Outfit',sans-serif;line-height:1.6"><?php echo esc_html($resto['desc']); ?></div><?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($bars)): ?>
            <div style="display:flex;flex-wrap:wrap;gap:8px">
                <?php foreach ($bars as $bar): if (empty($bar['nom'])) continue; ?>
                <span style="background:#0f2424;color:#fff;border-radius:8px;padding:6px 12px;font-size:12px;font-family:'Outfit',sans-serif;font-weight:600">🍸 <?php echo esc_html($bar['nom']); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- CHAMBRES -->
        <?php if (!empty($chambres)): $chambre_labels = ['standard'=>'Standard','superieure'=>'Supérieure','suite'=>'Suite','familiale'=>'Familiale'];
        $has_any_chambre = false; foreach ($chambres as $ch) { if (($ch['dispo'] ?? '0') == '1') { $has_any_chambre = true; break; } }
        if ($has_any_chambre): ?>
        <div class="sv-card">
            <h2 class="sv-section-title">🛏️ Types de chambres</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px">
                <?php foreach ($chambres as $type => $ch): if (($ch['dispo'] ?? '0') != '1') continue; ?>
                <div style="background:#f9f6f0;border-radius:12px;padding:16px">
                    <div style="font-size:15px;font-weight:700;color:#0f2424;font-family:'Outfit',sans-serif;margin-bottom:4px"><?php echo esc_html($chambre_labels[$type] ?? ucfirst($type)); ?></div>
                    <?php if (!empty($ch['superficie'])): ?><div style="font-size:12px;color:#59b7b7;font-weight:700;font-family:'Outfit',sans-serif;margin-bottom:6px">📐 <?php echo esc_html($ch['superficie']); ?> m²</div><?php endif; ?>
                    <?php if (!empty($ch['desc'])): ?><div style="font-size:13px;color:#374151;font-family:'Outfit',sans-serif;line-height:1.6"><?php echo esc_html($ch['desc']); ?></div><?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; endif; ?>

        <!-- SPA -->
        <?php if (!empty($spa_data['desc'])): ?>
        <div class="sv-card">
            <h2 class="sv-section-title">🧖 Spa & Bien-être</h2>
            <?php if (!empty($spa_data['nom'])): ?><div style="font-size:16px;font-weight:700;color:#0f2424;font-family:'Playfair Display',serif;margin-bottom:8px"><?php echo esc_html($spa_data['nom']); ?><?php if (!empty($spa_data['superficie'])): ?> <span style="font-size:12px;color:#6b7280;font-weight:400">(<?php echo esc_html($spa_data['superficie']); ?> m²)</span><?php endif; ?></div><?php endif; ?>
            <div style="font-size:14px;line-height:1.75;color:#374151;font-family:'Outfit',sans-serif;margin-bottom:10px"><?php echo nl2br(esc_html($spa_data['desc'])); ?></div>
            <?php if (!empty($spa_data['soins'])): ?><div style="font-size:12px;color:#6b7280;font-family:'Outfit',sans-serif">💆 Soins : <?php echo esc_html($spa_data['soins']); ?></div><?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ANIMATIONS & ENFANTS -->
        <?php if (!empty($animations['desc']) || !empty($enfants['desc'])): ?>
        <div class="sv-card">
            <h2 class="sv-section-title">🎭 Animations & Loisirs</h2>
            <?php if (!empty($animations['jour'])): ?>
            <div style="margin-bottom:12px"><span style="font-weight:700;color:#0f2424;font-family:'Outfit',sans-serif">☀️ Journée :</span> <span style="font-size:14px;color:#374151;font-family:'Outfit',sans-serif"><?php echo esc_html($animations['jour']); ?></span></div>
            <?php endif; ?>
            <?php if (!empty($animations['soir'])): ?>
            <div style="margin-bottom:12px"><span style="font-weight:700;color:#0f2424;font-family:'Outfit',sans-serif">🌙 Soirée :</span> <span style="font-size:14px;color:#374151;font-family:'Outfit',sans-serif"><?php echo esc_html($animations['soir']); ?></span></div>
            <?php endif; ?>
            <?php if (!empty($enfants['desc'])): ?>
            <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:14px;margin-top:12px">
                <div style="font-size:13px;font-weight:700;color:#0f2424;font-family:'Outfit',sans-serif;margin-bottom:4px">👶 Club Enfants<?php if (!empty($enfants['ages'])): ?> (<?php echo esc_html($enfants['ages']); ?>)<?php endif; ?></div>
                <div style="font-size:13px;color:#374151;font-family:'Outfit',sans-serif;line-height:1.6"><?php echo esc_html($enfants['desc']); ?></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ÉQUIPEMENTS (simple grille) -->
        <?php if ($has_equip): ?>
        <div class="sv-card" id="sec-equipements">
            <h2 class="sv-section-title">🏊 Équipements & Services</h2>
            <div class="sv-equip-grid">
                <?php foreach (explode("\n", $hotel_equip) as $eq):
                    $eq = trim($eq); if (!$eq) continue;
                    $icon = '✅';
                    if (stripos($eq, 'piscine') !== false || stripos($eq, '🏊') !== false) $icon = '🏊';
                    elseif (stripos($eq, 'spa') !== false || stripos($eq, 'hammam') !== false || stripos($eq, '🧖') !== false) $icon = '🧖';
                    elseif (stripos($eq, 'restaurant') !== false || stripos($eq, 'buffet') !== false || stripos($eq, '🍽') !== false) $icon = '🍽️';
                    elseif (stripos($eq, 'wifi') !== false || stripos($eq, '📶') !== false) $icon = '📶';
                    elseif (stripos($eq, 'sport') !== false || stripos($eq, 'fitness') !== false || stripos($eq, '🏋') !== false) $icon = '🏋️';
                    elseif (stripos($eq, 'plage') !== false || stripos($eq, 'beach') !== false || stripos($eq, '🏖') !== false) $icon = '🏖️';
                    elseif (stripos($eq, 'enfant') !== false || stripos($eq, 'kids') !== false || stripos($eq, '👶') !== false) $icon = '👶';
                    elseif (stripos($eq, 'animation') !== false || stripos($eq, '🎭') !== false) $icon = '🎭';
                    elseif (stripos($eq, 'bar') !== false || stripos($eq, '🍸') !== false) $icon = '🍸';
                    elseif (stripos($eq, 'parking') !== false) $icon = '🅿️';
                    elseif (stripos($eq, 'tennis') !== false) $icon = '🎾';
                    elseif (stripos($eq, 'chambre') !== false || stripos($eq, '🛏') !== false) $icon = '🛏️';
                    elseif (stripos($eq, 'sport') !== false || stripos($eq, '🏃') !== false) $icon = '🏃';
                    // Si l'item commence déjà par un emoji, ne pas doubler
                    if (preg_match('/^[\x{1F300}-\x{1FAD6}]/u', $eq)) $icon = '';
                ?>
                <div class="sv-equip-item"><?php if ($icon): ?><span><?php echo $icon; ?></span><?php endif; ?> <?php echo esc_html($eq); ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- COMPRIS / NON COMPRIS -->
        <?php if ($has_compris): ?>
        <div class="sv-card" id="sec-compris">
            <h2 class="sv-section-title">✅ Ce qui est compris / non compris</h2>
            <div class="sv-inclus-grid">
                <?php if ($inclus_raw): ?>
                <div class="sv-inclus-box yes">
                    <h3>✅ Inclus</h3>
                    <ul><?php foreach (explode("\n", $inclus_raw) as $l): $l = trim($l); if ($l): ?><li><?php echo esc_html($l); ?></li><?php endif; endforeach; ?></ul>
                </div>
                <?php endif; ?>
                <?php if ($non_inclus_raw): ?>
                <div class="sv-inclus-box no">
                    <h3>❌ Non inclus</h3>
                    <ul><?php foreach (explode("\n", $non_inclus_raw) as $l): $l = trim($l); if ($l): ?><li><?php echo esc_html($l); ?></li><?php endif; endforeach; ?></ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- CARTE -->
        <?php if ($has_map): ?>
        <div class="sv-card" id="sec-map">
            <h2 class="sv-section-title">🗺️ Localisation</h2>
            <?php if ($hotel_map): ?>
            <div style="border-radius:12px;overflow:hidden;height:300px;border:1px solid #e5e7eb">
                <iframe width="100%" height="300" frameborder="0" style="border:0;display:block" src="<?php echo esc_url($hotel_map); ?>" allowfullscreen loading="lazy"></iframe>
            </div>
            <?php elseif ($hotel_adresse): ?>
            <div id="sv-map" style="height:300px;border-radius:12px;border:1px solid #e5e7eb"></div>
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
            <script>
            (function(){
                var q=<?php echo json_encode($hotel_adresse . ' ' . $hotel_nom); ?>;
                fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q='+encodeURIComponent(q),{headers:{'Accept-Language':'fr'}})
                .then(function(r){return r.json()})
                .then(function(res){
                    if(!res||!res[0]){document.getElementById('sv-map').style.display='none';return}
                    var lat=parseFloat(res[0].lat),lon=parseFloat(res[0].lon);
                    var map=L.map('sv-map',{scrollWheelZoom:false}).setView([lat,lon],14);
                    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',{maxZoom:19}).addTo(map);
                    var pin=L.divIcon({html:'<div style="background:#59b7b7;color:#fff;border-radius:50%;width:40px;height:40px;display:flex;align-items:center;justify-content:center;font-size:20px;box-shadow:0 3px 10px rgba(0,0,0,.3);border:3px solid #fff">🏨</div>',className:'',iconSize:[40,40],iconAnchor:[20,20]});
                    L.marker([lat,lon],{icon:pin}).addTo(map).bindPopup('<strong><?php echo esc_js($hotel_nom); ?></strong><br><small><?php echo esc_js($hotel_adresse); ?></small>').openPopup();
                }).catch(function(){});
            })();
            </script>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- CONDITIONS D'ANNULATION -->
        <?php if ($annulation_texte || !empty($annulation)): ?>
        <div class="sv-card">
            <h2 class="sv-section-title">📋 Conditions d'annulation</h2>
            <?php if ($annulation_texte): ?>
            <p style="font-size:14px;line-height:1.7;color:#374151;font-family:'Outfit',sans-serif"><?php echo nl2br(esc_html($annulation_texte)); ?></p>
            <?php endif; ?>
            <?php if (!empty($annulation) && is_array($annulation)): ?>
            <table style="width:100%;border-collapse:collapse;margin-top:12px;font-family:'Outfit',sans-serif;font-size:13px">
                <tr style="background:#0f2424;color:#fff"><th style="padding:8px 12px;text-align:left">Délai avant départ</th><th style="padding:8px 12px;text-align:right">Frais retenus</th></tr>
                <?php foreach ($annulation as $pal): ?>
                <tr><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb">Plus de <?php echo intval($pal['jours']); ?> jours</td><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;text-align:right;font-weight:600"><?php echo intval($pal['pct']); ?>%</td></tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- PHOTOS CARROUSEL -->
        <?php if (count($galerie) > 1): ?>
        <div class="sv-carousel-wrap" id="sec-photos">
            <h2 class="sv-section-title">📷 Galerie photos</h2>
            <div class="sv-carousel" id="sv-carousel" style="cursor:zoom-in">
                <div class="sv-carousel-track" id="sv-carousel-track">
                    <?php foreach ($galerie as $img): ?><img src="<?php echo esc_url($img); ?>" alt="" loading="lazy" onclick="svLightboxOpen()"><?php endforeach; ?>
                </div>
                <button class="sv-carousel-btn" style="left:12px" id="sv-prev">&#8249;</button>
                <button class="sv-carousel-btn" style="right:12px" id="sv-next">&#8250;</button>
                <div class="sv-carousel-counter" id="sv-counter">1 / <?php echo count($galerie); ?></div>
            </div>
            <div class="sv-dots" id="sv-dots">
                <?php foreach ($galerie as $i => $img): ?><div class="sv-dot<?php echo $i === 0 ? ' active' : ''; ?>" onclick="svGoto(<?php echo $i; ?>)"></div><?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- LIGHTBOX -->
        <div class="sv-lightbox" id="sv-lightbox" onclick="svLightboxClose()">
            <button class="sv-lb-close" onclick="svLightboxClose()">✕</button>
            <button class="sv-lb-prev" onclick="event.stopPropagation();svLightboxNav(-1)">&#8249;</button>
            <button class="sv-lb-next" onclick="event.stopPropagation();svLightboxNav(1)">&#8250;</button>
            <div class="sv-lb-img-wrap" onclick="event.stopPropagation()"><img class="sv-lb-img" id="sv-lb-img" src="" alt=""></div>
            <div class="sv-lb-counter" id="sv-lb-counter"></div>
        </div>

    </div><!-- /sv-left-col -->

    <!-- ══ COLONNE DROITE ══ -->
    <div class="sv-right-col">

        <div class="sv-calc-card">
            <p class="sv-calc-title">Calculez votre prix</p>
            <?php if (!empty($aeroports)): ?>
            <p class="sv-calc-sub">Choisissez d'abord votre aéroport → le calendrier s'affichera ensuite.</p>
            <?php else: ?>
            <p class="sv-calc-sub"><?php echo esc_html($flag . ' ' . get_the_title()); ?> — <?php echo $duree_jours; ?>j/<?php echo $duree; ?>n</p>
            <?php endif; ?>

            <?php if (!empty($aeroports)): ?>
            <!-- 1. AÉROPORT -->
            <div class="sv-field">
                <label>✈️ Aéroport de départ <span style="color:#dc2626">*</span></label>
                <select id="sv-aeroport" onchange="sjOnAeroportChange()">
                    <option value="">— Choisir un aéroport —</option>
                    <?php foreach ($aeroports as $a): $code = strtoupper(trim($a['code'] ?? '')); ?>
                    <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($code . ' — ' . ($a['ville'] ?? '')); ?><?php if (floatval($a['supplement'] ?? 0) > 0): ?> (+<?php echo number_format($a['supplement'], 0); ?>€/pers)<?php endif; ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="sv-vol-st" id="sv-vol-st"></div>
            </div>

            <!-- 2. DATE (après aéroport) -->
            <div class="sv-field" id="sv-field-date-block" style="display:none">
                <label>📅 Date de départ</label>
                <div id="sv-date-wrap" style="position:relative">
                    <div id="sv-date-trigger" onclick="window.sjCalDate && window.sjCalDate.toggle()" style="padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;cursor:pointer;font-size:13px;font-family:'Outfit',sans-serif;color:#9ca3af;background:#fafafa">
                        📅 Choisir une date de départ
                    </div>
                </div>
                <input type="hidden" id="sv-date-depart" onchange="sjOnDateChange()">
            </div>

            <!-- 3. VOYAGEURS + CHAMBRES (visibles dès la sélection aéroport, pas après date) -->
            <div class="sv-field-row" id="sv-field-pax" style="display:none">
                <div class="sv-field">
                    <label>👤 Voyageurs</label>
                    <select id="sv-nb-adultes" onchange="sjRecalculate()">
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php selected($i, 2); ?>><?php echo $i; ?> ad.</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="sv-field">
                    <label>🚪 Chambres</label>
                    <select id="sv-nb-chambres" onchange="sjRecalculate()">
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?> ch.</option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <?php endif; ?>

            <!-- Résultat recherche (après sélection date) -->
            <div id="sv-step2" style="display:none">
                <!-- Loading -->
                <div class="sv-price-loading" id="sv-price-loading">⏳ Recherche vols + hôtel...</div>
                <!-- Prix -->
                <div class="sv-price-box" id="sv-price-box" style="display:none">
                    <p class="sv-price-from">Total estimé tout compris</p>
                    <div class="sv-price-main" id="sv-price-val">—</div>
                    <p class="sv-price-per"><span id="sv-price-pp"></span> / pers.</p>
                    <div class="sv-price-acompte" id="sv-price-acompte"></div>
                </div>
                <button class="sv-btn-reserver" id="sv-btn-reserver" onclick="sjGoReserver()" disabled>Réserver ce séjour →</button>
            </div>

            <div class="sv-reassurance">
                <div class="sv-reass"><span class="sv-reass-icon">🛡️</span><span>Assurances multirisques en option</span></div>
                <div class="sv-reass"><span class="sv-reass-icon">🔒</span><span>Paiement 100% sécurisé</span></div>
                <div class="sv-reass"><span class="sv-reass-icon">📞</span><div>
                    <strong style="color:#374151">Conseiller disponible</strong><br>
                    03 26 65 28 63
                </div></div>
            </div>
        </div>

        <!-- ACTIONS -->
        <div class="sv-actions-card">
            <button class="sv-action-btn" onclick="window.print()">🖨️ Imprimer la fiche</button>
            <a class="sv-action-btn" href="mailto:?subject=<?php echo rawurlencode('Séjour : ' . get_the_title()); ?>&body=<?php echo rawurlencode("Bonjour,\n\nVoici un séjour qui pourrait t'intéresser :\n\n" . get_the_title() . "\n" . get_permalink()); ?>">✉️ Envoyer par mail</a>
            <button class="sv-action-btn" onclick="window.print()">📄 Sauvegarder en PDF</button>
        </div>

        <!-- DEVIS -->
        <div class="sv-devis-card">
            <div style="font-size:28px">📅</div>
            <h3>Vous ne trouvez pas votre date ?</h3>
            <p>Notre équipe organise ce séjour sur mesure. Réponse garantie sous 24h ouvrées.</p>
            <a class="sv-devis-btn" href="mailto:resa@voyagessortir08.com?subject=<?php echo rawurlencode('Demande de devis — ' . get_the_title()); ?>&body=<?php echo rawurlencode("Bonjour,\n\nJe souhaite un devis pour le séjour : " . get_the_title() . "\n\nMes disponibilités :\nNombre de voyageurs :\n\nMerci !"); ?>">✉️ Demander un devis personnalisé</a>
        </div>

    </div><!-- /sv-right-col -->

</div><!-- /sv-page-inner -->
</div><!-- /sv-page -->

<!-- Sticky bar -->
<div class="sv-sticky-bar" id="sv-sticky-bar">
    <div class="sv-sticky-bar-inner">
        <div><div class="sv-sticky-bar-total" id="sv-sticky-price">—</div><div class="sv-sticky-bar-sub">Total tout compris</div></div>
        <button class="sv-sticky-btn" id="sv-sticky-btn" onclick="sjGoReserver()" disabled>Réserver →</button>
    </div>
</div>

<script>
// ── NAVBAR ──
(function(){
    var nav=document.getElementById('sv-navbar'),rightCol=document.querySelector('.sv-right-col');
    function getHeaderH(){var h=document.getElementById('vs08-header')||document.getElementById('header');return h?Math.max(0,h.getBoundingClientRect().bottom):0}
    function apply(){var hh=getHeaderH(),nh=nav?nav.offsetHeight:52;if(nav)nav.style.top=hh+'px';if(rightCol)rightCol.style.top=(hh+nh+14)+'px'}
    window.addEventListener('load',apply);window.addEventListener('resize',apply);window.addEventListener('scroll',apply);
    var btns=document.querySelectorAll('.sv-nav-btn');
    var ids=['sec-presentation','sec-hebergement','sec-equipements','sec-compris','sec-map','sec-photos'];
    window.addEventListener('scroll',function(){var hh=getHeaderH(),nh=nav?nav.offsetHeight:52,y=window.scrollY+hh+nh+30;
        for(var i=ids.length-1;i>=0;i--){var el=document.getElementById(ids[i]);if(!el)continue;if(el.offsetTop<=y){btns.forEach(function(b){b.classList.remove('active')});btns.forEach(function(b){if(b.getAttribute('onclick')&&b.getAttribute('onclick').indexOf(ids[i])>-1)b.classList.add('active')});break}}
    });
})();
function svScrollTo(id){var el=document.getElementById(id);if(!el)return;var nav=document.getElementById('sv-navbar');var off=nav?(parseInt(nav.style.top)||0)+nav.offsetHeight+20:80;window.scrollTo({top:el.getBoundingClientRect().top+window.scrollY-off,behavior:'smooth'})}

// ── CARROUSEL + LIGHTBOX ──
(function(){
    var track=document.getElementById('sv-carousel-track');if(!track)return;
    var dots=document.querySelectorAll('.sv-dot'),counter=document.getElementById('sv-counter');
    var imgs=[].slice.call(track.querySelectorAll('img')),total=imgs.length,current=0,timer=null;
    function goTo(n){current=(n+total)%total;track.style.transform='translateX(-'+(current*100)+'%)';dots.forEach(function(d,i){d.classList.toggle('active',i===current)});if(counter)counter.textContent=(current+1)+' / '+total}
    window.svGoto=function(n){stop();goTo(n)};
    function start(){timer=setInterval(function(){goTo(current+1)},4000)}
    function stop(){clearInterval(timer)}
    var p=document.getElementById('sv-prev'),n=document.getElementById('sv-next');
    if(p)p.addEventListener('click',function(){stop();goTo(current-1)});
    if(n)n.addEventListener('click',function(){stop();goTo(current+1)});
    start();
    // Lightbox
    var lb=document.getElementById('sv-lightbox'),lbImg=document.getElementById('sv-lb-img'),lbCounter=document.getElementById('sv-lb-counter'),lbIdx=0;
    window.svLightboxOpen=function(){lb.classList.add('active');lbIdx=current;showLb();document.body.style.overflow='hidden'};
    window.svLightboxClose=function(){lb.classList.remove('active');document.body.style.overflow=''};
    window.svLightboxNav=function(dir){lbIdx=(lbIdx+dir+total)%total;showLb()};
    function showLb(){lbImg.src=imgs[lbIdx].src;lbCounter.textContent=(lbIdx+1)+' / '+total}
    document.addEventListener('keydown',function(e){if(!lb.classList.contains('active'))return;if(e.key==='Escape')svLightboxClose();if(e.key==='ArrowLeft')svLightboxNav(-1);if(e.key==='ArrowRight')svLightboxNav(1)});
})();

// ── CALCULATEUR ──
var sjState={aeroport:'',date:'',adults:2,rooms:1,vol_price:0,vol_offer_id:'',hotel_net:0,hotel_rate_key:'',hotel_board:'AI',hotel_room_name:''};

/** Fusionne VS08S_DATA avec l’ID fiable (data-vs08-sejour-id) si le JSON inline était tronqué / id à 0 */
function sjVs08Data(){
    var D=(typeof VS08S_DATA==='object'&&VS08S_DATA!==null)?VS08S_DATA:{};
    var rid=parseInt(D.id,10);
    if(!rid||isNaN(rid)){
        var el=document.getElementById('sv-page');
        if(el) rid=parseInt(el.getAttribute('data-vs08-sejour-id')||'0',10)||0;
    }
    D.id=rid;
    return D;
}

function sjOnAeroportChange(){
    var sel=document.getElementById('sv-aeroport');
    sjState.aeroport=sel?sel.value:'';
    sjState.date='';sjState.vol_price=0;sjState.hotel_net=0;
    document.getElementById('sv-step2').style.display='none';
    document.getElementById('sv-price-box').style.display='none';
    var dateBlock=document.getElementById('sv-field-date-block');
    var paxBlock=document.getElementById('sv-field-pax');
    if(sjState.aeroport){
        if(dateBlock) dateBlock.style.display='block';
        if(paxBlock) paxBlock.style.display='grid';
        // Init calendar
        if(typeof VS08Calendar!=='undefined'&&!window.sjCalDate){
            var D=sjVs08Data();
            var aero=null;
            for(var i=0;i<D.aeroports.length;i++){if(D.aeroports[i].code===sjState.aeroport){aero=D.aeroports[i];break}}
            window.sjCalDate=new VS08Calendar({
                el:'#sv-date-wrap',mode:'date',inline:false,input:'#sv-date-depart',
                title:'📅 Date de départ',subtitle:'Sélectionnez votre date',
                yearRange:[new Date().getFullYear(),new Date().getFullYear()+2],minDate:new Date(Date.now()+7*86400000),
                disabledRanges:(D.periodes_fermees_vente||[]).map(function(p){return{from:p.date_debut,to:p.date_fin}}),
                allowedDays:aero?aero.jours_direct:[1,2,3,4,5,6,7],
                onConfirm:function(dt){
                    var opts={day:'numeric',month:'long',year:'numeric'};
                    document.getElementById('sv-date-trigger').textContent='📅 '+dt.toLocaleDateString('fr-FR',opts);
                    document.getElementById('sv-date-trigger').style.color='#0f2424';
                    document.getElementById('sv-date-trigger').style.fontWeight='600';
                    document.getElementById('sv-date-depart').dispatchEvent(new Event('change',{bubbles:true}));
                }
            });
        }
    }
}

function sjOnDateChange(){
    var dateInput=document.getElementById('sv-date-depart');
    sjState.date=dateInput?dateInput.value:'';
    if(!sjState.date||!sjState.aeroport)return;
    document.getElementById('sv-step2').style.display='block';
    sjSearchAll();
}

function sjRecalculate(){
    sjState.adults=parseInt(document.getElementById('sv-nb-adultes').value)||2;
    sjState.rooms=parseInt(document.getElementById('sv-nb-chambres').value)||1;
    if(sjState.date&&sjState.aeroport)sjSearchAll();
}

function sjSearchAll(){
    var loading=document.getElementById('sv-price-loading');
    var priceBox=document.getElementById('sv-price-box');
    loading.style.display='block';priceBox.style.display='none';
    document.getElementById('sv-btn-reserver').disabled=true;
    document.getElementById('sv-sticky-btn').disabled=true;

    var D=sjVs08Data();
    Promise.all([sjSearchFlights(),sjSearchHotel()]).then(function(){
        loading.style.display='none';
        if(sjState.vol_price>0&&sjState.hotel_net>0){sjCalculateTotal()}
        else{loading.style.display='none';loading.textContent='❌ Aucune disponibilité trouvée';loading.style.display='block';loading.style.color='#dc2626'}
    }).catch(function(err){loading.style.display='none'});
}

function sjParseRest(r){
    return r.text().then(function(txt){
        var data={};
        try{data=txt?JSON.parse(txt):{}}catch(e){}
        if(!r.ok){throw new Error((data&&data.message)?data.message:('Erreur HTTP '+r.status));}
        return data;
    });
}

function sjSearchFlights(){
    var D=sjVs08Data();
    return fetch(D.rest_url+'flights',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':D.nonce},
        body:JSON.stringify({sejour_id:D.id,aeroport:sjState.aeroport,date:sjState.date,adults:sjState.adults,iata_dest:D.iata_dest||'',duree:D.duree||7})
    }).then(sjParseRest).then(function(data){
        var offers=(data&&data.combos&&data.combos.length>0)?data.combos:((data&&data.data&&Array.isArray(data.data))?data.data:((data&&data.flights&&Array.isArray(data.flights))?data.flights:[]));
        if(offers.length>0){
            offers.sort(function(a,b){return((a.price_per_pax||a.total_amount||999999)-(b.price_per_pax||b.total_amount||999999))});
            sjState.vol_price=parseFloat(offers[0].price_per_pax||((offers[0].total_amount||0)/sjState.adults)||0);
            sjState.vol_offer_id=offers[0].offer_id||offers[0].id||'';
        }
        var st=document.getElementById('sv-vol-st');
        if(st){st.textContent=sjState.vol_price>0?'✅ Vols trouvés — '+sjFmt(sjState.vol_price)+'€/pers':'❌ Aucun vol disponible';st.style.color=sjState.vol_price>0?'#059669':'#dc2626'}
    }).catch(function(err){
        var st=document.getElementById('sv-vol-st');
        if(st){st.textContent='❌ '+(err&&err.message?err.message:'Erreur vols');st.style.color='#dc2626'}
    });
}

function sjSearchHotel(){
    var D=sjVs08Data();
    return fetch(D.rest_url+'hotel-availability',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':D.nonce},
        body:JSON.stringify({sejour_id:D.id,date:sjState.date,adults:sjState.adults,rooms:sjState.rooms,hotel_code:D.hotel_code||'',hotel_codes:D.hotel_codes||[],duree:D.duree||7})
    }).then(sjParseRest).then(function(data){
        if(data&&data.best){
            sjState.hotel_net=parseFloat(data.best.net_price||0);
            sjState.hotel_rate_key=data.best.rate_key||'';
            sjState.hotel_board=data.best.board_code||'AI';
            sjState.hotel_room_name=data.best.room_name||'';
            // Pas d'affichage du prix net hôtel — seul le total final est montré
        }
    }).catch(function(err){
        var loading=document.getElementById('sv-price-loading');
        if(loading){loading.textContent='❌ '+(err&&err.message?err.message:'Erreur hôtel');loading.style.display='block';loading.style.color='#dc2626'}
    });
}

function sjCalculateTotal(){
    var D=sjVs08Data();
    fetch(D.rest_url+'calculate',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':D.nonce},
        body:JSON.stringify({sejour_id:D.id,date_depart:sjState.date,aeroport:sjState.aeroport,nb_adultes:sjState.adults,nb_chambres:sjState.rooms,vol_price:sjState.vol_price,hotel_net:sjState.hotel_net,hotel_rate_key:sjState.hotel_rate_key,hotel_board:sjState.hotel_board,hotel_room_name:sjState.hotel_room_name})
    }).then(function(r){return r.json()}).then(function(devis){
        var box=document.getElementById('sv-price-box');box.style.display='block';
        document.getElementById('sv-price-val').textContent=sjFmt(devis.total)+' €';
        document.getElementById('sv-price-pp').textContent=sjFmt(devis.prix_par_personne)+' €';
        var ac=document.getElementById('sv-price-acompte');
        if(devis.payer_tout){ac.textContent='Paiement intégral requis'}
        else{ac.textContent='Acompte : '+sjFmt(devis.acompte)+' € ('+Math.round(devis.acompte_pct_final||devis.acompte_pct)+'%)'}
        document.getElementById('sv-btn-reserver').disabled=false;
        document.getElementById('sv-sticky-btn').disabled=false;
        document.getElementById('sv-sticky-price').textContent=sjFmt(devis.total)+' €';
        // Show sticky bar
        var bar=document.getElementById('sv-sticky-bar');if(bar)bar.classList.add('visible');
    });
}

function sjGoReserver(){
    var D=sjVs08Data();
    var params=new URLSearchParams({sejour_id:D.id,aeroport:sjState.aeroport,date_depart:sjState.date,nb_adultes:sjState.adults,nb_chambres:sjState.rooms,vol_price:sjState.vol_price,vol_offer_id:sjState.vol_offer_id,hotel_net:sjState.hotel_net,hotel_rate_key:sjState.hotel_rate_key,hotel_board:sjState.hotel_board});
    window.location.href=D.booking_url+'?'+params.toString();
}

function sjFmt(n){return Number(n||0).toLocaleString('fr-FR',{minimumFractionDigits:0,maximumFractionDigits:0})}

// Sticky bar visibility
window.addEventListener('scroll',function(){
    var bar=document.getElementById('sv-sticky-bar');
    var calc=document.querySelector('.sv-calc-card');
    if(!bar||!calc)return;
    var rect=calc.getBoundingClientRect();
    bar.classList.toggle('visible',rect.bottom<0&&!document.getElementById('sv-btn-reserver').disabled);
});
</script>

<?php get_footer(); ?>
