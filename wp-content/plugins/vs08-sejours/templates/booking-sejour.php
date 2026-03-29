<?php
/**
 * Tunnel de réservation Séjour All Inclusive
 * URL: /reservation-sejour/{sejour_id}/?date_depart=...&aeroport=...&nb_adultes=...
 * Étape 1: Sélection du vol (recherche Duffel) + Assurance
 * Étape 2: Voyageurs + Facturation
 * Étape 3: Confirmation + Paiement
 */
if (!defined('ABSPATH')) exit;

$sejour_id = intval(get_query_var('vs08s_sejour_id'));
if (!$sejour_id || get_post_type($sejour_id) !== 'vs08_sejour') { wp_redirect(home_url()); exit; }

$m       = VS08S_Meta::get($sejour_id);
$titre   = get_the_title($sejour_id);
$flag    = $m['flag'] ?? '';
if (!$flag && class_exists('VS08V_MetaBoxes')) $flag = VS08V_MetaBoxes::get_flag_emoji($m['pays'] ?? $m['destination'] ?? '');
$duree   = intval($m['duree'] ?? 7);
$duree_j = intval($m['duree_jours'] ?? ($duree + 1));
$pension_map = ['ai'=>'All Inclusive','pc'=>'Pension complète','dp'=>'Demi-pension','bb'=>'Petit-déjeuner','lo'=>'Logement seul'];
$pension = $pension_map[$m['pension'] ?? 'ai'] ?? 'All Inclusive';
$hotel_nom = $m['hotel_nom'] ?? '';
$hotel_etoiles = intval($m['hotel_etoiles'] ?? 5);
$iata_dest = strtoupper($m['iata_dest'] ?? '');

$params = [
    'date_depart'   => sanitize_text_field($_GET['date_depart'] ?? ''),
    'aeroport'      => strtoupper(sanitize_text_field($_GET['aeroport'] ?? '')),
    'nb_adultes'    => max(1, intval($_GET['nb_adultes'] ?? 2)),
    'nb_chambres'   => max(1, intval($_GET['nb_chambres'] ?? 1)),
    'hotel_net'     => floatval($_GET['hotel_net'] ?? 0),
    'hotel_rate_key' => sanitize_text_field($_GET['hotel_rate_key'] ?? ''),
    'hotel_board'   => sanitize_text_field($_GET['hotel_board'] ?? 'AI'),
];
$nb_total    = $params['nb_adultes'];
$nb_chambres = $params['nb_chambres'];
$date_fmt    = $params['date_depart'] ? date('d/m/Y', strtotime($params['date_depart'])) : '';
$date_retour = $params['date_depart'] ? date('Y-m-d', strtotime($params['date_depart'].' +'.$duree.' days')) : '';
$date_retour_fmt = $date_retour ? date('d/m/Y', strtotime($date_retour)) : '';

$insurance_price = 0;
if (class_exists('VS08V_Insurance')) {
    try { $insurance_price = VS08V_Insurance::get_price(500); } catch (\Throwable $e) {}
}

$prix_bag_soute  = floatval($m['prix_bagage_soute'] ?? 0);
$prix_bag_cabine = floatval($m['prix_bagage_cabine'] ?? 0);
$has_bagages     = ($prix_bag_soute > 0 || $prix_bag_cabine > 0);

$bk_saved_fact = [];
$bk_saved_voy  = [];
if (is_user_logged_in() && class_exists('VS08V_Traveler_Space')) {
    try { $bk_saved_fact = VS08V_Traveler_Space::get_saved_facturation(); $bk_saved_voy = VS08V_Traveler_Space::get_saved_voyageurs(); } catch (\Throwable $e) {}
}

$acompte_pct = floatval($m['acompte_pct'] ?? 30);
$delai_solde = intval($m['delai_solde'] ?? 30);
$payer_tout  = $params['date_depart'] && ((strtotime($params['date_depart']) - time()) / 86400 <= $delai_solde);

$voy_par_chambre = [];
for ($c = 1; $c <= $nb_chambres; $c++) $voy_par_chambre[$c] = [];
for ($i = 0; $i < $nb_total; $i++) { $ch = ($i % $nb_chambres) + 1; $voy_par_chambre[$ch][] = $i; }

get_header();
?>

<script>var BK_SEJOUR=<?php echo json_encode([
    'sejour_id'      => $sejour_id,
    'titre'          => $titre,
    'nb_total'       => $nb_total,
    'nb_chambres'    => $nb_chambres,
    'date_depart'    => $params['date_depart'],
    'date_retour'    => $date_retour,
    'aeroport'       => $params['aeroport'],
    'iata_dest'      => $iata_dest,
    'duree'          => $duree,
    'hotel_net'      => $params['hotel_net'],
    'hotel_rate_key' => $params['hotel_rate_key'],
    'hotel_board'    => $params['hotel_board'],
    'hotel_nom'      => $hotel_nom,
    'pension'        => $pension,
    'acompte_pct'    => $acompte_pct,
    'payer_tout'     => $payer_tout,
    'insurance_pp'   => $insurance_price,
    'insurance_total'=> $insurance_price * $nb_total,
    'prix_bag_soute' => $prix_bag_soute,
    'prix_bag_cabine'=> $prix_bag_cabine,
    'rest_url'       => rest_url('vs08s/v1/'),
    'nonce'          => wp_create_nonce('wp_rest'),
]); ?>;</script>

<style>
.bks-page{background:#f9f6f0;min-height:100vh;padding:0 0 60px}
.bks-header{background:linear-gradient(135deg,#0f2424 0%,#1a4a3a 100%);padding:24px 32px;color:#fff}
.bks-header-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;gap:20px;flex-wrap:wrap}
.bks-header-info h2{margin:0;font-size:20px;font-family:'Playfair Display',serif}
.bks-header-info p{margin:4px 0 0;font-size:13px;color:rgba(255,255,255,.7);font-family:'Outfit',sans-serif}
.bks-chips{display:flex;gap:6px;flex-wrap:wrap;margin-top:8px}
.bks-chip{background:rgba(89,183,183,.25);border:1px solid rgba(89,183,183,.4);padding:3px 10px;border-radius:100px;font-size:11px;font-weight:600;font-family:'Outfit',sans-serif;color:#b0e0e0}
.bks-container{max-width:1200px;margin:0 auto;padding:24px 32px;display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start}
.bks-section{background:#fff;border-radius:18px;padding:28px;margin-bottom:18px;box-shadow:0 2px 10px rgba(0,0,0,.05)}
.bks-section-title{font-family:'Playfair Display',serif;font-size:18px;color:#0f2424;margin:0 0 6px;display:flex;align-items:center;gap:10px}
.bks-section-sub{font-size:13px;color:#6b7280;font-family:'Outfit',sans-serif;margin:0 0 18px}
.bks-step-num{background:#59b7b7;color:#fff;width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0}
/* Flight combos */
.bks-combo-loading{text-align:center;padding:30px;font-family:'Outfit',sans-serif;font-size:14px;color:#6b7280}
.bks-combo-spinner{width:28px;height:28px;border:3px solid #e5e7eb;border-top-color:#59b7b7;border-radius:50%;animation:bks-spin .7s linear infinite;margin:0 auto 10px}
@keyframes bks-spin{to{transform:rotate(360deg)}}
.bks-combo-card{background:#fff;border:1.5px solid #e5e7eb;border-radius:14px;padding:16px 18px;margin-bottom:10px;cursor:pointer;transition:all .25s}
.bks-combo-card:hover{border-color:#b7dfdf;box-shadow:0 2px 12px rgba(89,183,183,.08)}
.bks-combo-card.selected{border-color:#59b7b7;background:#f0fafa;box-shadow:0 2px 16px rgba(89,183,183,.15)}
.bks-combo-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.bks-combo-airline{display:flex;align-items:center;gap:10px}
.bks-combo-airline img{width:28px;height:28px;border-radius:6px;object-fit:contain;background:#f5f5f5;padding:2px}
.bks-combo-airline-name{font-size:14px;font-weight:700;color:#0f2424;font-family:'Outfit',sans-serif}
.bks-combo-airline-sub{font-size:10px;color:#9ca3af;font-family:'Outfit',sans-serif}
.bks-combo-price-delta{font-size:13px;font-weight:700;color:#b85c1a;font-family:'Outfit',sans-serif}
.bks-combo-price-delta.ref{color:#2d8a5a;background:#e8f8f0;padding:3px 10px;border-radius:100px;font-size:11px}
.bks-combo-price-sub{font-size:9px;color:#9ca3af;font-family:'Outfit',sans-serif;text-align:right}
.bks-combo-check{width:22px;height:22px;border-radius:50%;background:#59b7b7;color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;opacity:0;transition:opacity .2s}
.bks-combo-card.selected .bks-combo-check{opacity:1}
.bks-combo-leg{display:flex;align-items:center;gap:10px;padding:6px 0;font-family:'Outfit',sans-serif;font-size:12px;color:#4a5568;flex-wrap:wrap}
.bks-combo-leg-badge{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:1px;padding:3px 8px;border-radius:6px;flex-shrink:0}
.bks-combo-leg-badge.aller{background:#edf8f8;color:#3d9a9a}
.bks-combo-leg-badge.retour{background:#fff3e8;color:#b85c1a}
.bks-combo-leg-times{display:flex;align-items:center;gap:6px;flex:1}
.bks-combo-leg-line{flex:1;display:flex;align-items:center;gap:4px}
.bks-combo-leg-dash{flex:1;height:1px;background:#ddd}
.bks-combo-leg-plane{font-size:12px;color:#59b7b7}
.bks-combo-leg-meta{display:flex;align-items:center;gap:8px;margin-left:auto;flex-shrink:0}
.bks-combo-leg-dur{font-size:10px;color:#9ca3af;white-space:nowrap}
.bks-combo-leg-num{font-size:10px;color:#bbb;white-space:nowrap}
.bks-combo-conn{display:inline-block;padding:2px 8px;border-radius:100px;font-size:9px;font-weight:700}
.bks-combo-conn.direct{background:#e8f8f0;color:#2d8a5a}
.bks-combo-conn.escale{background:#fef3c7;color:#92400e}
.bks-show-more{text-align:center;margin-top:8px}
.bks-show-more button{background:none;border:1.5px solid #59b7b7;color:#59b7b7;border-radius:10px;padding:8px 20px;font-size:12px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;transition:all .2s}
.bks-show-more button:hover{background:#59b7b7;color:#fff}
.bks-no-flights{padding:20px;text-align:center;color:#dc2626;font-family:'Outfit',sans-serif}
/* Voyageurs */
.bks-chambre{background:#f9f6f0;border:1px solid #ede9e0;border-radius:14px;padding:20px;margin-bottom:16px}
.bks-chambre-title{font-weight:700;font-size:14px;color:#0f2424;margin-bottom:12px;font-family:'Outfit',sans-serif}
.bks-voyageur{margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #e5e7eb}
.bks-voyageur:last-child{margin-bottom:0;padding-bottom:0;border-bottom:none}
.bks-voyageur-label{font-size:12px;font-weight:700;color:#59b7b7;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;font-family:'Outfit',sans-serif}
.bks-field-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.bks-field{margin-bottom:10px}
.bks-field label{display:block;font-size:11px;font-weight:600;color:#374151;margin-bottom:4px;font-family:'Outfit',sans-serif}
.bks-field input,.bks-field select{width:100%;border:1.5px solid #e5e7eb;border-radius:10px;padding:10px 12px;font-size:13px;font-family:'Outfit',sans-serif;box-sizing:border-box}
.bks-field input:focus{border-color:#59b7b7;outline:none}
.bks-field input.bks-err{border-color:#dc2626}
.bks-nav{display:flex;gap:12px;justify-content:flex-end;margin-top:20px}
.bks-btn-prev{background:#f3f4f6;color:#374151;border:1px solid #d1d5db;border-radius:12px;padding:12px 24px;font-size:14px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif}
.bks-btn-next{background:#e8724a;color:#fff;border:none;border-radius:12px;padding:12px 28px;font-size:14px;font-weight:700;cursor:pointer;font-family:'Outfit',sans-serif;transition:all .2s}
.bks-btn-next:hover{background:#d4603c}
.bks-btn-next:disabled{opacity:.5;cursor:not-allowed}
/* Recap sidebar */
.bks-recap{position:sticky;top:100px;background:#fff;border-radius:18px;padding:24px;box-shadow:0 4px 20px rgba(0,0,0,.08)}
.bks-recap-title{font-family:'Playfair Display',serif;font-size:16px;color:#0f2424;margin:0 0 14px}
.bks-recap-line{display:flex;justify-content:space-between;padding:5px 0;font-size:13px;font-family:'Outfit',sans-serif;color:#374151;border-bottom:1px solid #f0ece4}
.bks-recap-sep{height:1px;background:#59b7b7;margin:10px 0}
.bks-recap-total{display:flex;justify-content:space-between;align-items:center;padding:10px 0}
.bks-recap-total-lbl{font-size:14px;font-weight:700;color:#0f2424;font-family:'Outfit',sans-serif}
.bks-recap-total-val{font-size:24px;font-weight:800;color:#59b7b7;font-family:'Outfit',sans-serif}
.bks-recap-acompte{display:flex;justify-content:space-between;background:#edf8f8;border-radius:8px;padding:8px 12px;font-size:13px;font-weight:600;color:#59b7b7;font-family:'Outfit',sans-serif;margin-top:8px}
.bks-error{display:none;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px;border-radius:10px;font-size:13px;font-family:'Outfit',sans-serif;margin-bottom:14px}
.bks-loading{display:none;text-align:center;padding:20px}
.bks-step-page{display:none!important}.bks-step-active{display:block!important}
/* Route header */
.bks-route-header{display:flex;align-items:center;justify-content:center;gap:14px;background:#0f2424;border-radius:14px;padding:14px 20px;margin-bottom:20px}
.bks-route-iata{font-family:'Playfair Display',serif;font-size:28px;font-weight:700;color:#fff;letter-spacing:1px;text-transform:uppercase}
.bks-route-arrow{font-size:22px;color:#59b7b7}
.bks-route-city{font-size:10px;color:rgba(255,255,255,.5);font-family:'Outfit',sans-serif;text-transform:uppercase;letter-spacing:1px}
.bks-route-dates{font-size:11px;color:rgba(255,255,255,.45);font-family:'Outfit',sans-serif;margin-top:3px}
/* Insurance — same as circuit */
/* Baggage options */
.bks-opt-row{display:flex;align-items:center;gap:14px;padding:16px;border:1.5px solid #e5e7eb;border-radius:14px;margin-bottom:10px;transition:all .2s}
.bks-opt-row.checked{border-color:#59b7b7;background:#edf8f8}
.bks-opt-icon{font-size:26px;flex-shrink:0}
.bks-opt-body{flex:1}
.bks-opt-name{font-size:15px;font-weight:700;color:#0f2424;font-family:'Outfit',sans-serif}
.bks-opt-desc{font-size:13px;color:#6b7280;font-family:'Outfit',sans-serif;margin-top:2px}
.bks-opt-price{font-size:14px;font-weight:700;color:#3d9a9a;font-family:'Outfit',sans-serif;margin-top:4px}
.bks-opt-right{display:flex;align-items:center;gap:10px;flex-shrink:0}
.bks-opt-qty{display:flex;align-items:center;gap:8px}
.bks-opt-qty button{width:32px;height:32px;border:1.5px solid #e5e7eb;background:#fff;border-radius:8px;font-size:18px;font-weight:700;cursor:pointer;color:#0f2424;transition:all .15s}
.bks-opt-qty button:hover{border-color:#59b7b7;background:#edf8f8}
.bks-opt-qty span{font-size:16px;font-weight:700;font-family:'Outfit',sans-serif;min-width:24px;text-align:center}
/* Insurance */
.bks-ins-wrap{background:linear-gradient(135deg,#f0f9fa 0%,#fdf2f8 100%);border:2px solid #59b7b7;border-radius:18px;padding:0;overflow:hidden}
.bks-ins-header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px 12px;border-bottom:1px solid rgba(89,183,183,.18)}
.bks-ins-logo{height:32px;width:auto}
.bks-ins-badge{font-family:'Outfit',sans-serif;font-size:11px;font-weight:700;background:linear-gradient(135deg,#e3147a,#c30d66);color:#fff;padding:4px 12px;border-radius:20px;letter-spacing:.5px}
.bks-ins-body{padding:14px 20px 16px}
.bks-ins-hook{font-family:'Playfair Display',serif;font-size:16px;font-weight:700;color:#0f2424;line-height:1.35;margin:0 0 6px}
.bks-ins-sub{font-family:'Outfit',sans-serif;font-size:12.5px;color:#4b5563;line-height:1.5;margin:0 0 14px}
.bks-ins-docs{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px}
.bks-ins-doc{font-family:'Outfit',sans-serif;font-size:11px;color:#0083a3;text-decoration:none;display:flex;align-items:center;gap:4px;padding:5px 10px;background:rgba(255,255,255,.8);border:1px solid rgba(0,131,163,.2);border-radius:8px;transition:all .2s}
.bks-ins-doc:hover{background:#e3147a;color:#fff;border-color:#e3147a}
.bks-ins-footer{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;background:rgba(255,255,255,.6);border-top:1px solid rgba(89,183,183,.15)}
.bks-ins-check-label{display:flex;align-items:center;gap:12px;cursor:pointer;flex:1}
.bks-ins-check-label input[type=checkbox]{width:20px;height:20px;accent-color:#e3147a;flex-shrink:0}
.bks-ins-check-text{font-family:'Outfit',sans-serif;font-size:13.5px;font-weight:700;color:#0f2424}
.bks-ins-check-text small{font-weight:400;color:#6b7280;font-size:12px}
.bks-ins-price{text-align:right;flex-shrink:0}
.bks-ins-price-main{font-family:'Outfit',sans-serif;font-size:20px;font-weight:800;color:#e3147a}
.bks-ins-price-detail{font-family:'Outfit',sans-serif;font-size:11px;color:#6b7280}
@media(max-width:900px){.bks-container{grid-template-columns:1fr}.bks-recap{position:static}}
@media(max-width:600px){.bks-container{padding:16px}.bks-field-row{grid-template-columns:1fr}.bks-header{padding:16px}.bks-section{padding:20px}.bks-route-header{padding:10px 14px;gap:10px}.bks-route-iata{font-size:20px}.bks-ins-footer{flex-direction:column;gap:12px;align-items:stretch}.bks-ins-price{text-align:left}}
/* Filter sidebar */
.bks-filters-sidebar{position:fixed;top:160px;width:200px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;font-family:'Outfit',sans-serif;box-shadow:0 2px 12px rgba(0,0,0,.06);z-index:50;transition:opacity .3s}
.bksf-title{font-size:16px;font-weight:700;color:#0f2424;margin-bottom:14px}
.bksf-section{margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid #e5e7eb}
.bksf-section:last-of-type{border-bottom:none;margin-bottom:8px;padding-bottom:0}
.bksf-label{font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px}
.bksf-check{display:flex;align-items:center;gap:7px;font-size:13px;color:#4b5563;cursor:pointer;padding:4px 0;transition:color .15s}
.bksf-check:hover{color:#0f2424}
.bksf-check input[type=radio]{accent-color:#3d9a9a;margin:0}
.bksf-n{font-size:11px;background:#e5e7eb;color:#6b7280;border-radius:8px;padding:1px 6px;font-weight:700;margin-left:auto}
.bksf-range-row{display:flex;justify-content:space-between;margin-bottom:4px}
.bksf-range-val{font-size:12px;font-weight:600;color:#3d9a9a}
.bksf-range{width:100%;margin:3px 0;accent-color:#3d9a9a;cursor:pointer}
.bksf-reset{width:100%;padding:7px;border:1.5px solid #e5e7eb;border-radius:10px;background:#fff;color:#6b7280;font-size:13px;font-weight:600;cursor:pointer;transition:all .15s;font-family:'Outfit',sans-serif}
.bksf-reset:hover{border-color:#3d9a9a;color:#3d9a9a}
@media(max-width:960px){.bks-filters-sidebar{display:none!important}}
</style>

<div class="bks-page">

    <!-- SIDEBAR FILTRES -->
    <aside class="bks-filters-sidebar" id="bks-filters-sidebar" style="display:none">
        <div class="bksf-title">Filtres</div>
        <div class="bksf-section">
            <div class="bksf-label">Type de vol</div>
            <label class="bksf-check"><input type="radio" name="bksf_type" value="all" checked> Tous <span class="bksf-n" id="bksf-n-all"></span></label>
            <label class="bksf-check"><input type="radio" name="bksf_type" value="direct"> ✈ Vol direct <span class="bksf-n" id="bksf-n-direct"></span></label>
            <label class="bksf-check"><input type="radio" name="bksf_type" value="escale"> ⇄ Avec escale <span class="bksf-n" id="bksf-n-escale"></span></label>
        </div>
        <div class="bksf-section">
            <div class="bksf-label">Départ aller</div>
            <div class="bksf-range-row">
                <span class="bksf-range-val" id="bksf-dep-min-lbl">00:00</span>
                <span class="bksf-range-val" id="bksf-dep-max-lbl">23:59</span>
            </div>
            <input type="range" class="bksf-range" id="bksf-dep-min" min="0" max="1439" value="0" step="30">
            <input type="range" class="bksf-range" id="bksf-dep-max" min="0" max="1439" value="1439" step="30">
        </div>
        <div class="bksf-section">
            <div class="bksf-label">Départ retour</div>
            <div class="bksf-range-row">
                <span class="bksf-range-val" id="bksf-ret-min-lbl">00:00</span>
                <span class="bksf-range-val" id="bksf-ret-max-lbl">23:59</span>
            </div>
            <input type="range" class="bksf-range" id="bksf-ret-min" min="0" max="1439" value="0" step="30">
            <input type="range" class="bksf-range" id="bksf-ret-max" min="0" max="1439" value="1439" step="30">
        </div>
        <button type="button" class="bksf-reset" id="bksf-reset">Réinitialiser</button>
    </aside>

    <div class="bks-header"><div class="bks-header-inner">
        <a href="<?php echo get_permalink($sejour_id); ?>" style="color:rgba(255,255,255,.5);text-decoration:none;font-size:13px;font-family:'Outfit',sans-serif">← Retour</a>
        <div class="bks-header-info">
            <h2><?php echo esc_html($flag . ' ' . $titre); ?></h2>
            <p><?php echo esc_html($duree_j.'j/'.$duree.'n · '.$hotel_nom.' '.str_repeat('★',$hotel_etoiles).' · '.$pension); ?></p>
            <div class="bks-chips">
                <?php if($date_fmt):?><span class="bks-chip">📅 <?php echo $date_fmt; ?></span><?php endif; ?>
                <?php if($params['aeroport']):?><span class="bks-chip">✈️ <?php echo $params['aeroport']; ?></span><?php endif; ?>
                <span class="bks-chip">👥 <?php echo $nb_total; ?> pax</span>
            </div>
        </div>
    </div></div>

    <div class="bks-container">
    <div>
        <div class="bks-error" id="bks-error"></div>

        <!-- ═══ ÉTAPE 1 : Sélection du vol ═══ -->
        <div id="bks-step-1" class="bks-step-page bks-step-active">
        <div class="bks-section">
            <h3 class="bks-section-title"><span class="bks-step-num">1</span> Sélection de votre vol</h3>
            <p class="bks-section-sub">Choisissez votre combinaison aller-retour parmi les vols disponibles.</p>

            <div class="bks-route-header">
                <div style="text-align:center">
                    <div class="bks-route-iata"><?php echo esc_html($params['aeroport'] ?: '—'); ?></div>
                    <div class="bks-route-city">Départ</div>
                </div>
                <div style="text-align:center">
                    <div class="bks-route-arrow">✈️ ⟷</div>
                    <div class="bks-route-dates"><?php echo esc_html($date_fmt . ' → ' . $date_retour_fmt . ' · ' . $nb_total . ' pax'); ?></div>
                </div>
                <div style="text-align:center">
                    <div class="bks-route-iata"><?php echo esc_html($iata_dest ?: '—'); ?></div>
                    <div class="bks-route-city">Arrivée</div>
                </div>
            </div>

            <div id="bks-combo-wrap">
                <div class="bks-combo-loading" id="bks-combo-loading"><div class="bks-combo-spinner"></div>Recherche des vols aller et retour…</div>
                <div id="bks-combo-list"></div>
                <div id="bks-combo-no-match" class="bks-no-flights" style="display:none">Aucun vol ne correspond à vos filtres.</div>
                <div id="bks-no-flights" class="bks-no-flights" style="display:none">❌ Aucun vol trouvé pour ces dates. Essayez une autre date.</div>
            </div>
            <input type="hidden" id="bks-selected-offer-id" name="selected_offer_id" value="">
            <input type="hidden" id="bks-selected-vol-delta" name="vol_delta_pax" value="0">
            <input type="hidden" id="bks-selected-vol-price" value="0">
        </div>

        <!-- ═══ OPTIONS BAGAGES ═══ -->
        <?php if ($has_bagages): ?>
        <div class="bks-section">
            <h3 class="bks-section-title"><span class="bks-step-num">✈️</span> Options de vol</h3>
            <p class="bks-section-sub">Personnalisez vos bagages — les quantités sont ajustables.</p>

            <?php if ($prix_bag_soute > 0): ?>
            <div class="bks-opt-row" id="bks-opt-soute">
                <div class="bks-opt-icon">🧳</div>
                <div class="bks-opt-body">
                    <div class="bks-opt-name">Bagage en soute</div>
                    <div class="bks-opt-desc">Selon compagnie entre 20 & 23 kg</div>
                    <div class="bks-opt-price"><?php echo number_format($prix_bag_soute, 0, ',', ' '); ?> € / pers.</div>
                </div>
                <div class="bks-opt-right">
                    <div class="bks-opt-qty">
                        <button type="button" onclick="bksBagChange('soute',-1)">−</button>
                        <span id="bks-bag-soute-qty">0</span>
                        <button type="button" onclick="bksBagChange('soute',1)">+</button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($prix_bag_cabine > 0): ?>
            <div class="bks-opt-row" id="bks-opt-cabine">
                <div class="bks-opt-icon">🎒</div>
                <div class="bks-opt-body">
                    <div class="bks-opt-name">Bagage cabine supplémentaire</div>
                    <div class="bks-opt-desc">Selon compagnie entre 8 & 12 kg</div>
                    <div class="bks-opt-price"><?php echo number_format($prix_bag_cabine, 0, ',', ' '); ?> € / pers.</div>
                </div>
                <div class="bks-opt-right">
                    <div class="bks-opt-qty">
                        <button type="button" onclick="bksBagChange('cabine',-1)">−</button>
                        <span id="bks-bag-cabine-qty">0</span>
                        <button type="button" onclick="bksBagChange('cabine',1)">+</button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Assurance -->
        <?php if ($insurance_price > 0): ?>
        <div class="bks-section">
            <div class="bks-ins-wrap">
                <div class="bks-ins-header">
                    <img src="<?php echo defined('VS08V_URL') ? VS08V_URL : ''; ?>assets/img/assurever-logo.png" alt="Assurever" class="bks-ins-logo" onerror="this.style.display='none'">
                    <span class="bks-ins-badge">GALAXY MULTIRISQUE</span>
                </div>
                <div class="bks-ins-body">
                    <div class="bks-ins-hook">Voyagez l'esprit libre, on s'occupe du reste.</div>
                    <p class="bks-ins-sub">Annulation selon cause prévue dans le contrat, rapatriement 24h/24, frais médicaux à l'étranger, bagages… Une couverture complète pour partir sereinement.</p>
                    <?php if (defined('VS08V_URL')): ?>
                    <div class="bks-ins-docs">
                        <a href="<?php echo VS08V_URL; ?>assets/docs/assurever-ipid-galaxy.pdf" target="_blank" class="bks-ins-doc">📄 Fiche produit (IPID)</a>
                        <a href="<?php echo VS08V_URL; ?>assets/docs/assurever-conditions-galaxy.pdf" target="_blank" class="bks-ins-doc">📄 Conditions générales</a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="bks-ins-footer">
                    <label class="bks-ins-check-label">
                        <input type="checkbox" id="bks-assurance" onchange="bksUpdateTotal()">
                        <div class="bks-ins-check-text">Oui, je souhaite être protégé(e)<br><small>Assurance Multirisque GALAXY · Assurever / Mutuaide</small></div>
                    </label>
                    <div class="bks-ins-price">
                        <div class="bks-ins-price-main"><?php echo number_format($insurance_price * $nb_total, 2, ',', ' '); ?> €</div>
                        <div class="bks-ins-price-detail"><?php echo number_format($insurance_price, 2, ',', ' '); ?> € /pers. × <?php echo $nb_total; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="bks-nav"><button type="button" class="bks-btn-next" id="bks-btn-step1" onclick="bksGoToStep2()" disabled>Continuer →</button></div>
        </div>

        <!-- ═══ ÉTAPE 2 : Voyageurs + Facturation ═══ -->
        <div id="bks-step-2" class="bks-step-page">
        <div class="bks-section">
            <h3 class="bks-section-title"><span class="bks-step-num">2</span> Informations voyageurs & facturation</h3>
            <p class="bks-section-sub"><?php echo $nb_total; ?> voyageur(s) — <?php echo $nb_chambres; ?> chambre(s)</p>
            <?php for ($chambre = 1; $chambre <= $nb_chambres; $chambre++): ?>
            <div class="bks-chambre"><div class="bks-chambre-title">🏨 Chambre <?php echo $chambre; ?></div>
                <?php foreach ($voy_par_chambre[$chambre] ?? [] as $vi): $saved = $bk_saved_voy[$vi] ?? []; ?>
                <div class="bks-voyageur"><div class="bks-voyageur-label">Voyageur <?php echo $vi+1; ?></div>
                    <div class="bks-field-row">
                        <div class="bks-field"><label>Prénom *</label><input type="text" name="voyageurs[<?php echo $vi; ?>][prenom]" class="bks-required" value="<?php echo esc_attr($saved['prenom']??''); ?>"></div>
                        <div class="bks-field"><label>Nom *</label><input type="text" name="voyageurs[<?php echo $vi; ?>][nom]" class="bks-required" value="<?php echo esc_attr($saved['nom']??''); ?>"></div>
                    </div>
                    <div class="bks-field-row">
                        <div class="bks-field"><label>Date naissance *</label><input type="date" name="voyageurs[<?php echo $vi; ?>][ddn]" class="bks-required" value="<?php echo esc_attr($saved['ddn']??''); ?>"></div>
                        <div class="bks-field"><label>N° Passeport</label><input type="text" name="voyageurs[<?php echo $vi; ?>][passeport]" value="<?php echo esc_attr($saved['passeport']??''); ?>"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endfor; ?>
            <h3 class="bks-section-title" style="margin-top:24px"><span class="bks-step-num">📄</span> Facturation</h3>
            <div class="bks-field-row">
                <div class="bks-field"><label>Prénom *</label><input type="text" id="bks-f-prenom" class="bks-required" value="<?php echo esc_attr($bk_saved_fact['prenom']??''); ?>"></div>
                <div class="bks-field"><label>Nom *</label><input type="text" id="bks-f-nom" class="bks-required" value="<?php echo esc_attr($bk_saved_fact['nom']??''); ?>"></div>
            </div>
            <div class="bks-field-row">
                <div class="bks-field"><label>Email *</label><input type="email" id="bks-f-email" class="bks-required" value="<?php echo esc_attr($bk_saved_fact['email']??''); ?>"></div>
                <div class="bks-field"><label>Téléphone *</label><input type="tel" id="bks-f-tel" class="bks-required" value="<?php echo esc_attr($bk_saved_fact['tel']??''); ?>"></div>
            </div>
            <div class="bks-field"><label>Adresse *</label><input type="text" id="bks-f-adresse" class="bks-required" value="<?php echo esc_attr($bk_saved_fact['adresse']??''); ?>"></div>
            <div class="bks-field-row">
                <div class="bks-field"><label>Code postal *</label><input type="text" id="bks-f-cp" class="bks-required" value="<?php echo esc_attr($bk_saved_fact['cp']??''); ?>"></div>
                <div class="bks-field"><label>Ville *</label><input type="text" id="bks-f-ville" class="bks-required" value="<?php echo esc_attr($bk_saved_fact['ville']??''); ?>"></div>
            </div>
            <div class="bks-nav">
                <button type="button" class="bks-btn-prev" onclick="bksShow(1)">← Retour</button>
                <button type="button" class="bks-btn-next" onclick="bksGoToConfirm()">Confirmer →</button>
            </div>
        </div></div>

        <!-- ═══ ÉTAPE 3 : Confirmation ═══ -->
        <div id="bks-step-3" class="bks-step-page">
        <div class="bks-section">
            <h3 class="bks-section-title"><span class="bks-step-num">3</span> Confirmation</h3>
            <div id="bks-recap-final" style="margin-bottom:20px"></div>
            <div style="background:#fff8f0;border:1.5px solid #f0dcc0;border-radius:12px;padding:16px;margin-bottom:16px">
                <label style="display:flex;gap:10px;cursor:pointer;align-items:flex-start"><input type="checkbox" id="bks-confirm-info" style="margin-top:3px"><span style="font-size:12px;color:#6b5630;font-family:'Outfit',sans-serif;line-height:1.5">Je certifie que les <strong>noms, prénoms, dates de naissance et passeports</strong> sont exacts.</span></label>
            </div>
            <label style="display:flex;gap:10px;align-items:flex-start;font-size:12px;font-family:'Outfit',sans-serif;color:#1a3a3a;cursor:pointer;line-height:1.5"><input type="checkbox" id="bks-cgu" style="margin-top:3px"><span>J'accepte les <a href="<?php echo home_url('/conditions/'); ?>" target="_blank" style="color:#59b7b7">CGV</a> et la <a href="<?php echo home_url('/rgpd'); ?>" target="_blank" style="color:#59b7b7">politique de confidentialité</a>.</span></label>
            <div class="bks-nav" style="margin-top:24px">
                <button type="button" class="bks-btn-prev" onclick="bksShow(2)">← Retour</button>
                <button type="button" class="bks-btn-next" id="bks-btn-submit" onclick="bksSubmit()">🔒 Procéder au paiement →</button>
            </div>
        </div></div>
    </div>

    <!-- SIDEBAR RÉCAP -->
    <div class="bks-recap">
        <h3 class="bks-recap-title">📋 Récapitulatif</h3>
        <div class="bks-recap-line" style="font-weight:600;color:#0f2424"><span>🏖️ <?php echo esc_html($titre); ?></span></div>
        <div class="bks-recap-line"><span>📅 Aller</span><span><?php echo $date_fmt; ?></span></div>
        <div class="bks-recap-line"><span>📅 Retour</span><span><?php echo $date_retour_fmt; ?></span></div>
        <div class="bks-recap-line"><span>✈️ Route</span><span><?php echo $params['aeroport'].' ↔ '.$iata_dest; ?></span></div>
        <div class="bks-recap-line"><span>🏨 Hôtel</span><span><?php echo esc_html($hotel_nom); ?></span></div>
        <div class="bks-recap-line"><span>🍽️ Pension</span><span><?php echo esc_html($pension); ?></span></div>
        <div class="bks-recap-line"><span>👥 Voyageurs</span><span><?php echo $nb_total; ?> pers.</span></div>
        <div class="bks-recap-line" id="bks-recap-vol" style="display:none"><span>✈️ Vol</span><span id="bks-recap-vol-val">—</span></div>
        <div class="bks-recap-line" id="bks-recap-vol-aller" style="display:none"><span style="padding-left:12px;font-size:11px">↗ Aller</span><span id="bks-recap-vol-aller-val" style="font-size:11px">—</span></div>
        <div class="bks-recap-line" id="bks-recap-vol-retour" style="display:none"><span style="padding-left:12px;font-size:11px">↙ Retour</span><span id="bks-recap-vol-retour-val" style="font-size:11px">—</span></div>
        <div class="bks-recap-line" id="bks-recap-bag-soute" style="display:none"><span>🧳 Bagage soute</span><span id="bks-recap-bag-soute-val">—</span></div>
        <div class="bks-recap-line" id="bks-recap-bag-cabine" style="display:none"><span>🎒 Bagage cabine</span><span id="bks-recap-bag-cabine-val">—</span></div>
        <div class="bks-recap-line" id="bks-recap-ins" style="display:none"><span>🛡️ Assurance</span><span id="bks-recap-ins-val">—</span></div>
        <div class="bks-recap-sep"></div>
        <div class="bks-recap-total"><span class="bks-recap-total-lbl">Total</span><span class="bks-recap-total-val" id="bks-recap-total">—</span></div>
        <div style="font-size:11px;color:#6b7280;text-align:right;font-family:'Outfit',sans-serif" id="bks-recap-pp"></div>
        <div class="bks-recap-acompte" id="bks-recap-acompte" style="display:none"><span>🔒 Acompte</span><span id="bks-recap-acompte-val">—</span></div>
        <div class="bks-loading" id="bks-loading"><div class="bks-combo-spinner"></div><div style="font-size:13px;color:#59b7b7;font-family:'Outfit',sans-serif">Réservation en cours…</div></div>
        <div style="margin-top:12px;font-size:11px;color:#9ca3af;text-align:center;font-family:'Outfit',sans-serif">🔒 Paiement sécurisé · APST</div>
    </div>
    </div>
</div>

<script>
(function(){
    var BK=BK_SEJOUR, submitting=false;
    var selectedCombo=null, volPricePax=0, comboData=[];

    // ── Recherche des vols au chargement ──
    function searchFlights(){
        fetch(BK.rest_url+'flights',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':BK.nonce},
            body:JSON.stringify({sejour_id:BK.sejour_id,aeroport:BK.aeroport,date:BK.date_depart,adults:BK.nb_total})
        }).then(function(r){return r.json()}).then(function(res){
            document.getElementById('bks-combo-loading').style.display='none';
            var flights=res.data||res.combos||[];
            if(!flights.length){document.getElementById('bks-no-flights').style.display='block';return}
            comboData=flights; // Déjà triés par prix par l'API Duffel
            renderCombos(flights);
            bksSelectCombo(0);
            initSidebarFilters();
        }).catch(function(err){
            document.getElementById('bks-combo-loading').style.display='none';
            document.getElementById('bks-no-flights').style.display='block';
            document.getElementById('bks-no-flights').textContent='❌ Erreur: '+err.message;
        });
    }

    var SHOW_INITIAL = 3;

    function fmtDuration(min) {
        if (!min) return '';
        var h = Math.floor(min/60), m = min%60;
        return h > 0 ? h+'h'+String(m).padStart(2,'0') : m+'min';
    }

    function renderCombos(flights) {
        var list = document.getElementById('bks-combo-list');
        list.innerHTML = '';

        flights.forEach(function(f, idx) {
            var airline = f.airline_name || '';
            var iata = f.airline_iata || '';
            var isRef = f.is_reference || idx === 0;
            var delta = parseFloat(f.delta_per_pax || 0);
            var conn = f.has_connections;

            // Price badge
            var priceHtml = isRef
                ? '<div class="bks-combo-price-delta ref">Meilleur prix</div>'
                : '<div class="bks-combo-price-delta">+' + fmt(delta) + ' €</div><div class="bks-combo-price-sub">/pers. aller-retour</div>';

            // Connection badge
            var connHtml = conn
                ? '<span class="bks-combo-conn escale">1 escale</span>'
                : '<span class="bks-combo-conn direct">Direct</span>';

            var html = '<div class="bks-combo-header">'
                + '<div class="bks-combo-airline">'
                + '<img src="https://images.kiwi.com/airlines/64/' + (iata||'XX') + '.png" alt="" onerror="this.style.display=\'none\'">'
                + '<div><div class="bks-combo-airline-name">' + (airline||'Vol') + '</div>'
                + '<div class="bks-combo-airline-sub">' + iata + '</div></div>'
                + '</div>'
                + '<div style="display:flex;align-items:center;gap:8px">'
                + '<div>' + priceHtml + '</div>'
                + '<div class="bks-combo-check">✓</div>'
                + '</div></div>';

            // ── LEG ALLER ──
            html += '<div class="bks-combo-leg">'
                + '<div class="bks-combo-leg-badge aller">ALLER</div>'
                + '<div class="bks-combo-leg-times">'
                + '<div>' + (f.depart_time||'—') + '</div>'
                + '<div class="bks-combo-leg-line"><div class="bks-combo-leg-dash"></div><div class="bks-combo-leg-plane">✈</div><div class="bks-combo-leg-dash"></div></div>'
                + '<div>' + (f.arrive_time||'—') + '</div>'
                + '</div>'
                + '<div class="bks-combo-leg-meta">'
                + connHtml
                + '<span class="bks-combo-leg-dur">' + fmtDuration(f.duration_min) + '</span>'
                + '<span class="bks-combo-leg-num">' + (f.flight_number||'') + '</span>'
                + '</div></div>';

            // ── LEG RETOUR ──
            if (f.retour_depart) {
                var retConn = f.has_connections
                    ? '<span class="bks-combo-conn escale">1 escale</span>'
                    : '<span class="bks-combo-conn direct">Direct</span>';

                html += '<div class="bks-combo-leg">'
                    + '<div class="bks-combo-leg-badge retour">RETOUR</div>'
                    + '<div class="bks-combo-leg-times">'
                    + '<div>' + (f.retour_depart||'—') + '</div>'
                    + '<div class="bks-combo-leg-line"><div class="bks-combo-leg-dash"></div><div class="bks-combo-leg-plane">✈</div><div class="bks-combo-leg-dash"></div></div>'
                    + '<div>' + (f.retour_arrive||'—') + '</div>'
                    + '</div>'
                    + '<div class="bks-combo-leg-meta">'
                    + retConn
                    + '<span class="bks-combo-leg-dur">' + fmtDuration(f.retour_duration) + '</span>'
                    + '<span class="bks-combo-leg-num">' + (f.retour_flight||'') + '</span>'
                    + '</div></div>';
            }

            var card = document.createElement('div');
            card.className = 'bks-combo-card' + (idx === 0 ? ' selected' : '');
            card.id = 'bks-combo-' + idx;
            card.setAttribute('data-conn', conn ? '1' : '0');
            card.setAttribute('data-dep', timeToMin(f.depart_time));
            card.setAttribute('data-ret', timeToMin(f.retour_depart));
            card.onclick = (function(i) { return function() { bksSelectCombo(i); }; })(idx);
            card.innerHTML = html;
            list.appendChild(card);
        });

        // Counts for filters
        var nbDirect = 0, nbEscale = 0;
        flights.forEach(function(f) { if (f.has_connections) nbEscale++; else nbDirect++; });
        var nAll = document.getElementById('bksf-n-all');
        var nDir = document.getElementById('bksf-n-direct');
        var nEsc = document.getElementById('bksf-n-escale');
        if (nAll) nAll.textContent = flights.length;
        if (nDir) nDir.textContent = nbDirect;
        if (nEsc) nEsc.textContent = nbEscale;

        // Apply visibility (show first 3 + show more)
        bksShowAll = false;
        bksApplyVisibility();
    }

    window.bksShowAllCombos = function() {
        bksShowAll = true;
        bksApplyVisibility();
    };

    var bksShowAll = false;

    // Central visibility: handles both filters + show more
    function bksApplyVisibility() {
        var typeVal = 'all';
        document.querySelectorAll('input[name="bksf_type"]').forEach(function(r) { if (r.checked) typeVal = r.value; });
        var depMinR = document.getElementById('bksf-dep-min');
        var depMaxR = document.getElementById('bksf-dep-max');
        var retMinR = document.getElementById('bksf-ret-min');
        var retMaxR = document.getElementById('bksf-ret-max');
        var dMin = depMinR ? parseInt(depMinR.value,10) : 0;
        var dMax = depMaxR ? parseInt(depMaxR.value,10) : 1439;
        var rMin = retMinR ? parseInt(retMinR.value,10) : 0;
        var rMax = retMaxR ? parseInt(retMaxR.value,10) : 1439;

        // Update labels
        var el;
        el = document.getElementById('bksf-dep-min-lbl'); if (el) el.textContent = minToTime(dMin);
        el = document.getElementById('bksf-dep-max-lbl'); if (el) el.textContent = minToTime(dMax);
        el = document.getElementById('bksf-ret-min-lbl'); if (el) el.textContent = minToTime(rMin);
        el = document.getElementById('bksf-ret-max-lbl'); if (el) el.textContent = minToTime(rMax);

        var cards = document.querySelectorAll('.bks-combo-card');
        var matchCount = 0;
        cards.forEach(function(card) {
            var match = true;
            var conn = card.getAttribute('data-conn');
            if (typeVal === 'direct' && conn === '1') match = false;
            if (typeVal === 'escale' && conn === '0') match = false;
            if (match) { var dep = parseInt(card.getAttribute('data-dep'),10); if (dep < dMin || dep > dMax) match = false; }
            if (match) { var ret = parseInt(card.getAttribute('data-ret'),10); if (ret < rMin || ret > rMax) match = false; }

            if (!match) {
                card.style.display = 'none';
            } else {
                matchCount++;
                card.style.display = (!bksShowAll && matchCount > SHOW_INITIAL) ? 'none' : '';
            }
        });

        // Show more button
        var oldBtn = document.getElementById('bks-show-more');
        if (oldBtn) oldBtn.remove();
        if (!bksShowAll && matchCount > SHOW_INITIAL) {
            var wrap = document.createElement('div');
            wrap.className = 'bks-show-more';
            wrap.id = 'bks-show-more';
            var hidden = matchCount - SHOW_INITIAL;
            wrap.innerHTML = '<button onclick="bksShowAllCombos()">Voir ' + hidden + ' autre' + (hidden > 1 ? 's' : '') + ' vol' + (hidden > 1 ? 's' : '') + ' ↓</button>';
            document.getElementById('bks-combo-list').appendChild(wrap);
        }
    }

    // ── Time helpers ──
    function timeToMin(t) {
        if (!t || typeof t !== 'string') return 0;
        var p = t.split(':');
        return parseInt(p[0],10) * 60 + parseInt(p[1]||'0',10);
    }
    function minToTime(m) {
        var h = Math.floor(m/60) % 24, mn = m % 60;
        return String(h).padStart(2,'0') + ':' + String(mn).padStart(2,'0');
    }

    // ── Filter sidebar ──
    function initSidebarFilters() {
        var sidebar = document.getElementById('bks-filters-sidebar');
        if (!sidebar) return;

        // Position sidebar to the left of .bks-container
        function posSidebar() {
            var container = document.querySelector('.bks-container');
            if (!container || !sidebar) return;
            var rect = container.getBoundingClientRect();
            var gap = 20, sidebarW = 200;
            var spaceLeft = rect.left - gap - sidebarW;
            if (spaceLeft >= 10) {
                sidebar.style.left = (rect.left - gap - sidebarW) + 'px';
                sidebar.style.display = '';
            } else {
                sidebar.style.display = 'none';
            }
        }
        posSidebar();
        window.addEventListener('resize', posSidebar);

        // Wire filter controls to bksApplyVisibility
        document.querySelectorAll('input[name="bksf_type"]').forEach(function(r) {
            r.addEventListener('change', function() { bksShowAll = false; bksApplyVisibility(); });
        });
        ['bksf-dep-min','bksf-dep-max','bksf-ret-min','bksf-ret-max'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('input', function() { bksShowAll = false; bksApplyVisibility(); });
        });
        var resetBtn = document.getElementById('bksf-reset');
        if (resetBtn) resetBtn.addEventListener('click', function() {
            document.querySelectorAll('input[name="bksf_type"]').forEach(function(r) { r.checked = r.value === 'all'; });
            var ids = ['bksf-dep-min','bksf-dep-max','bksf-ret-min','bksf-ret-max'];
            var vals = [0, 1439, 0, 1439];
            ids.forEach(function(id, i) { var el = document.getElementById(id); if (el) el.value = vals[i]; });
            bksShowAll = false;
            bksApplyVisibility();
        });
    }

    // ── Baggage ──
    var bagSouteQty = 0;
    var bagCabineQty = 0;

    window.bksBagChange = function(type, delta) {
        if (type === 'soute') {
            bagSouteQty = Math.max(0, Math.min(bagSouteQty + delta, BK.nb_total));
            document.getElementById('bks-bag-soute-qty').textContent = bagSouteQty;
            var row = document.getElementById('bks-opt-soute');
            if (row) row.classList.toggle('checked', bagSouteQty > 0);
            var recap = document.getElementById('bks-recap-bag-soute');
            if (recap) { recap.style.display = bagSouteQty > 0 ? 'flex' : 'none'; document.getElementById('bks-recap-bag-soute-val').textContent = bagSouteQty + ' × ' + fmt(BK.prix_bag_soute) + '€'; }
        } else {
            bagCabineQty = Math.max(0, Math.min(bagCabineQty + delta, BK.nb_total));
            document.getElementById('bks-bag-cabine-qty').textContent = bagCabineQty;
            var row2 = document.getElementById('bks-opt-cabine');
            if (row2) row2.classList.toggle('checked', bagCabineQty > 0);
            var recap2 = document.getElementById('bks-recap-bag-cabine');
            if (recap2) { recap2.style.display = bagCabineQty > 0 ? 'flex' : 'none'; document.getElementById('bks-recap-bag-cabine-val').textContent = bagCabineQty + ' × ' + fmt(BK.prix_bag_cabine) + '€'; }
        }
        bksUpdateTotal();
    };

    window.bksSelectCombo=function(idx){
        var f=comboData[idx]; if(!f) return;
        selectedCombo=f;
        volPricePax=parseFloat(f.price_per_pax || 0);
        document.getElementById('bks-selected-vol-price').value=volPricePax;
        var offerInput=document.getElementById('bks-selected-offer-id');
        if(offerInput) offerInput.value=f.offer_id||'';
        var deltaInput=document.getElementById('bks-selected-vol-delta');
        if(deltaInput) deltaInput.value=f.delta_per_pax||0;
        document.querySelectorAll('.bks-combo-card').forEach(function(c){c.classList.remove('selected')});
        var card=document.getElementById('bks-combo-'+idx);
        if(card) card.classList.add('selected');
        document.getElementById('bks-btn-step1').disabled=false;
        // Update recap
        var volRow=document.getElementById('bks-recap-vol');
        if(volRow){volRow.style.display='flex';document.getElementById('bks-recap-vol-val').textContent=(f.airline_name||'Vol')+' · '+(f.flight_number||'')}
        var allerRow=document.getElementById('bks-recap-vol-aller');
        if(allerRow){allerRow.style.display='flex';document.getElementById('bks-recap-vol-aller-val').textContent=(f.depart_time||'—')+' → '+(f.arrive_time||'—')}
        var retourRow=document.getElementById('bks-recap-vol-retour');
        if(retourRow&&f.retour_depart){retourRow.style.display='flex';document.getElementById('bks-recap-vol-retour-val').textContent=(f.retour_depart||'—')+' → '+(f.retour_arrive||'—')}
        bksUpdateTotal();
    };

    function bksUpdateTotal(){
        var hotel=parseFloat(BK.hotel_net)||0;
        var vol=volPricePax*BK.nb_total;
        var ins=0;
        var chk=document.getElementById('bks-assurance');
        if(chk&&chk.checked&&BK.insurance_total>0){ins=BK.insurance_total;document.getElementById('bks-recap-ins').style.display='flex';document.getElementById('bks-recap-ins-val').textContent=fmt(ins)+' €'}
        else{var ri=document.getElementById('bks-recap-ins');if(ri)ri.style.display='none'}
        // Appeler le backend pour le vrai total (marge, transferts, bagages)
        fetch(BK.rest_url+'calculate',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':BK.nonce},
            body:JSON.stringify({sejour_id:BK.sejour_id,date_depart:BK.date_depart,aeroport:BK.aeroport,nb_adultes:BK.nb_total,nb_chambres:BK.nb_chambres,vol_price:volPricePax,hotel_net:BK.hotel_net,hotel_rate_key:BK.hotel_rate_key,hotel_board:BK.hotel_board,assurance:chk&&chk.checked?1:0,bagage_soute:bagSouteQty,bagage_cabine:bagCabineQty})
        }).then(function(r){return r.json()}).then(function(d){
            document.getElementById('bks-recap-total').textContent=fmt(d.total)+' €';
            document.getElementById('bks-recap-pp').textContent=fmt(d.prix_par_personne)+' €/pers.';
            if(!d.payer_tout&&d.acompte<d.total){document.getElementById('bks-recap-acompte').style.display='flex';document.getElementById('bks-recap-acompte-val').textContent=fmt(d.acompte)+' €'}
            BK._devis=d;
        });
    }
    window.bksUpdateTotal=bksUpdateTotal;

    // ── Navigation ──
    function showStep(n){
        document.querySelectorAll('.bks-step-page').forEach(function(p){p.classList.remove('bks-step-active')});
        var el=document.getElementById('bks-step-'+n);if(el)el.classList.add('bks-step-active');
        // Hide/show filter sidebar
        var sidebar=document.getElementById('bks-filters-sidebar');
        if(sidebar) sidebar.style.display = n===1 ? '' : 'none';
        window.scrollTo({top:0,behavior:'smooth'});
    }
    window.bksShow=showStep;
    window.bksGoToStep2=function(){if(!selectedCombo){alert('Sélectionnez un vol.');return}showStep(2)};

    window.bksGoToConfirm=function(){
        var ok=true;
        document.querySelectorAll('#bks-step-2 .bks-required').forEach(function(i){i.classList.remove('bks-err');if(!i.value.trim()){i.classList.add('bks-err');ok=false}});
        if(!ok){showError('Remplissez tous les champs obligatoires.');return}
        var html='<div style="font-family:Outfit,sans-serif;font-size:13px">';
        for(var i=0;i<BK.nb_total;i++){html+='<div style="padding:4px 0;border-bottom:1px solid #f0ece4">Voyageur '+(i+1)+': <strong>'+esc(val('voyageurs['+i+'][prenom]'))+' '+esc(val('voyageurs['+i+'][nom]'))+'</strong> — '+esc(val('voyageurs['+i+'][ddn]'))+'</div>'}
        html+='<div style="margin-top:10px;font-weight:600;color:#0f2424">Facturation: '+esc(document.getElementById('bks-f-prenom').value)+' '+esc(document.getElementById('bks-f-nom').value)+'</div>';
        html+='<div>'+esc(document.getElementById('bks-f-email').value)+' · '+esc(document.getElementById('bks-f-tel').value)+'</div></div>';
        document.getElementById('bks-recap-final').innerHTML=html;
        showStep(3);
    };

    window.bksSubmit=function(){
        if(!document.getElementById('bks-confirm-info').checked){alert('Certifiez les informations.');return}
        if(!document.getElementById('bks-cgu').checked){alert('Acceptez les CGV.');return}
        if(submitting)return;submitting=true;
        var btn=document.getElementById('bks-btn-submit');btn.disabled=true;btn.textContent='⏳ En cours…';
        document.getElementById('bks-loading').style.display='block';
        var voy=[];for(var i=0;i<BK.nb_total;i++){voy.push({prenom:val('voyageurs['+i+'][prenom]'),nom:val('voyageurs['+i+'][nom]'),ddn:val('voyageurs['+i+'][ddn]'),passeport:val('voyageurs['+i+'][passeport]')})}
        var body={sejour_id:BK.sejour_id,date_depart:BK.date_depart,aeroport:BK.aeroport,nb_adultes:BK.nb_total,nb_chambres:BK.nb_chambres,
            vol_price:volPricePax,hotel_net:BK.hotel_net,hotel_rate_key:BK.hotel_rate_key,hotel_board:BK.hotel_board,
            vol_aller_num:selectedCombo.flight_number||'',
            vol_aller_cie:selectedCombo.airline_name||'',
            vol_aller_depart:selectedCombo.depart_time||'',
            vol_aller_arrivee:selectedCombo.arrive_time||'',
            vol_retour_num:selectedCombo.retour_flight||'',
            vol_retour_depart:selectedCombo.retour_depart||'',
            vol_retour_arrivee:selectedCombo.retour_arrive||'',
            bagage_soute:bagSouteQty,
            bagage_cabine:bagCabineQty,
            assurance:(document.getElementById('bks-assurance')&&document.getElementById('bks-assurance').checked)?1:0,
            voyageurs:voy,facturation:{prenom:document.getElementById('bks-f-prenom').value,nom:document.getElementById('bks-f-nom').value,email:document.getElementById('bks-f-email').value,tel:document.getElementById('bks-f-tel').value,adresse:document.getElementById('bks-f-adresse').value,cp:document.getElementById('bks-f-cp').value,ville:document.getElementById('bks-f-ville').value}};
        fetch(BK.rest_url+'booking',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':BK.nonce},body:JSON.stringify(body)})
        .then(function(r){return r.json()}).then(function(res){
            if(res&&(res.checkout_url||(res.data&&res.data.checkout_url))){window.location.href=res.checkout_url||res.data.checkout_url}
            else{showError((res&&res.message)||'Erreur. Contactez le 03 26 65 28 63.');submitting=false;btn.disabled=false;btn.textContent='🔒 Procéder au paiement →';document.getElementById('bks-loading').style.display='none'}
        }).catch(function(e){showError('Erreur réseau: '+e.message);submitting=false;btn.disabled=false;btn.textContent='🔒 Procéder au paiement →';document.getElementById('bks-loading').style.display='none'});
    };

    function val(n){var e=document.querySelector('[name="'+n+'"]');return e?e.value.trim():''}
    function esc(s){var d=document.createElement('div');d.textContent=s;return d.innerHTML}
    function fmt(n){return Number(n||0).toLocaleString('fr-FR',{minimumFractionDigits:0,maximumFractionDigits:0})}
    function showError(m){var e=document.getElementById('bks-error');e.textContent=m;e.style.display='block';window.scrollTo({top:0,behavior:'smooth'})}

    // ── Init ──
    searchFlights();
})();
</script>

<?php get_footer(); ?>
