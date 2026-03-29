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
// Convert 2-letter ISO code to flag emoji if needed
if ($flag && strlen($flag) === 2 && preg_match('/^[a-zA-Z]{2}$/', $flag)) {
    $code = strtoupper($flag);
    $flag = mb_chr(0x1F1E6 + ord($code[0]) - 65, 'UTF-8') . mb_chr(0x1F1E6 + ord($code[1]) - 65, 'UTF-8');
}
if (!$flag && class_exists('VS08V_MetaBoxes')) {
    $flag = VS08V_MetaBoxes::get_flag_emoji($m['pays'] ?? $m['destination'] ?? '');
}
$duree   = intval($m['duree'] ?? 7);
$duree_j = intval($m['duree_jours'] ?? ($duree + 1));
$pension_map = ['ai'=>'All Inclusive','pc'=>'Pension complète','dp'=>'Demi-pension','bb'=>'Petit-déjeuner','lo'=>'Logement seul'];
$pension = $pension_map[$m['pension'] ?? 'ai'] ?? 'All Inclusive';
$hotel_nom = $m['hotel_nom'] ?? '';
$hotel_etoiles = intval($m['hotel_etoiles'] ?? 5);
$iata_dest = strtoupper($m['iata_dest'] ?? '');
$transfert_type = $m['transfert_type'] ?? '';
if (empty($transfert_type)) $transfert_type = 'groupes';
$transfert_labels = ['groupes'=>'🚌 Transferts groupés','prives'=>'🚐 Transferts privés','inclus'=>'✅ Inclus dans l\'hôtel','aucun'=>'❌ Non inclus'];
$transfert_label = $transfert_labels[$transfert_type] ?? '🚌 Transferts groupés';

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
if (is_user_logged_in()) {
    $uid = get_current_user_id();
    // Try VS08 saved facturation first
    if (class_exists('VS08V_Traveler_Space')) {
        try { $bk_saved_fact = VS08V_Traveler_Space::get_saved_facturation(); $bk_saved_voy = VS08V_Traveler_Space::get_saved_voyageurs(); } catch (\Throwable $e) {}
    }
    // Fallback: WooCommerce billing data
    if (empty($bk_saved_fact['prenom'])) {
        $user = wp_get_current_user();
        $bk_saved_fact = array_merge([
            'prenom'  => get_user_meta($uid, 'billing_first_name', true) ?: $user->first_name,
            'nom'     => get_user_meta($uid, 'billing_last_name', true) ?: $user->last_name,
            'email'   => get_user_meta($uid, 'billing_email', true) ?: $user->user_email,
            'tel'     => get_user_meta($uid, 'billing_phone', true),
            'adresse' => get_user_meta($uid, 'billing_address_1', true),
            'cp'      => get_user_meta($uid, 'billing_postcode', true),
            'ville'   => get_user_meta($uid, 'billing_city', true),
        ], array_filter($bk_saved_fact));
    }
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
    'transfert_type' => $m['transfert_type'] ?? 'groupes',
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
.bks-page{background:#f9f6f0;min-height:100vh;padding:140px 0 60px}
.bks-header{background:linear-gradient(135deg,#0f2424,#1a4a4a);border-radius:18px;padding:24px 28px;margin-bottom:28px;display:flex;align-items:center;gap:20px;color:#fff;grid-column:span 2}
.bks-header-info h2{font-family:'Playfair Display',serif;font-size:22px;margin:0 0 4px;color:#fff}
.bks-header-info p{font-size:13px;color:rgba(255,255,255,.55);font-family:'Outfit',sans-serif;margin:0}
.bks-chips{display:flex;gap:8px;margin-top:8px;flex-wrap:wrap}
.bks-chip{background:rgba(89,183,183,.25);border:1px solid rgba(89,183,183,.4);padding:3px 10px;border-radius:100px;font-size:11px;font-weight:600;font-family:'Outfit',sans-serif;color:#b0e0e0}
.bks-container{max-width:1200px;margin:0 auto;padding:0 32px;display:grid;grid-template-columns:1fr 400px;gap:24px;align-items:start}
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
.bks-field-row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
.bks-field{margin-bottom:10px}
.bks-field label{display:block;font-size:11px;font-weight:600;color:#374151;margin-bottom:4px;font-family:'Outfit',sans-serif}
.bks-field input,.bks-field select{width:100%;border:1.5px solid #e5e7eb;border-radius:10px;padding:10px 12px;font-size:13px;font-family:'Outfit',sans-serif;box-sizing:border-box;transition:border-color .2s}
.bks-field input:focus{border-color:#59b7b7;outline:none}
.bks-field input.bks-err{border-color:#dc2626}
.bks-ddn-trigger{padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;cursor:pointer;font-size:13px;font-family:'Outfit',sans-serif;color:#9ca3af;background:#fafafa;transition:border-color .2s}
.bks-ddn-trigger:hover{border-color:#b7dfdf}
.bks-nav{display:flex;gap:12px;justify-content:flex-end;margin-top:20px}
.bks-btn-prev{background:#f3f4f6;color:#374151;border:1px solid #d1d5db;border-radius:12px;padding:12px 24px;font-size:14px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif}
.bks-btn-next{background:#e8724a;color:#fff;border:none;border-radius:12px;padding:12px 28px;font-size:14px;font-weight:700;cursor:pointer;font-family:'Outfit',sans-serif;transition:all .2s}
.bks-btn-next:hover{background:#d4603c}
.bks-btn-next:disabled{opacity:.5;cursor:not-allowed}
/* Recap sidebar */
.bks-recap{position:sticky;top:100px;background:#fff;border-radius:18px;padding:28px;box-shadow:0 4px 20px rgba(0,0,0,.08)}
.bks-recap-title{font-family:'Playfair Display',serif;font-size:18px;color:#0f2424;margin:0 0 16px}
.bks-recap-line{display:flex;justify-content:space-between;padding:6px 0;font-size:13px;font-family:'Outfit',sans-serif;color:#374151;border-bottom:1px solid #f0ece4}
.bks-recap-sep{height:1px;background:#59b7b7;margin:10px 0}
.bks-recap-total{display:flex;justify-content:space-between;align-items:center;padding:10px 0}
.bks-recap-total-lbl{font-size:14px;font-weight:700;color:#0f2424;font-family:'Outfit',sans-serif}
.bks-recap-total-val{font-size:28px;font-weight:800;color:#59b7b7;font-family:'Outfit',sans-serif}
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
@media(max-width:900px){.bks-container{grid-template-columns:1fr}.bks-recap{position:static}.bks-header{grid-column:span 1}}
@media(max-width:600px){.bks-container{padding:0 16px}.bks-field-row{grid-template-columns:1fr}.bks-header{padding:16px;flex-direction:column;text-align:center}.bks-chips{justify-content:center}.bks-section{padding:20px}.bks-route-header{padding:10px 14px;gap:10px}.bks-route-iata{font-size:20px}.bks-ins-footer{flex-direction:column;gap:12px;align-items:stretch}.bks-ins-price{text-align:left}}
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
            <div class="bksf-range-row"><span class="bksf-range-val" id="bksf-dep-min-lbl">00:00</span><span class="bksf-range-val" id="bksf-dep-max-lbl">23:59</span></div>
            <input type="range" class="bksf-range" id="bksf-dep-min" min="0" max="1439" value="0" step="30">
            <input type="range" class="bksf-range" id="bksf-dep-max" min="0" max="1439" value="1439" step="30">
        </div>
        <div class="bksf-section">
            <div class="bksf-label">Départ retour</div>
            <div class="bksf-range-row"><span class="bksf-range-val" id="bksf-ret-min-lbl">00:00</span><span class="bksf-range-val" id="bksf-ret-max-lbl">23:59</span></div>
            <input type="range" class="bksf-range" id="bksf-ret-min" min="0" max="1439" value="0" step="30">
            <input type="range" class="bksf-range" id="bksf-ret-max" min="0" max="1439" value="1439" step="30">
        </div>
        <button type="button" class="bksf-reset" id="bksf-reset">Réinitialiser</button>
    </aside>

    <div class="bks-container">

    <!-- HEADER (inside grid, span 2 columns) -->
    <div class="bks-header">
        <a href="<?php echo get_permalink($sejour_id); ?>" style="color:rgba(255,255,255,.5);text-decoration:none;font-size:13px;font-family:'Outfit',sans-serif">← Retour</a>
        <div class="bks-header-info">
            <h2><?php echo esc_html($flag . ' ' . $titre); ?></h2>
            <p><?php echo esc_html($duree_j.'j/'.$duree.'n · '.$hotel_nom.' '.str_repeat('★',$hotel_etoiles).' · '.$pension); ?></p>
            <div class="bks-chips">
                <?php if($date_fmt):?><span class="bks-chip">📅 <?php echo $date_fmt; ?></span><?php endif; ?>
                <?php if($params['aeroport']):?><span class="bks-chip">✈️ <?php echo $params['aeroport']; ?></span><?php endif; ?>
                <span class="bks-chip">👥 <?php echo $nb_total; ?> pax</span>
                <span class="bks-chip">🛏️ <?php echo $nb_chambres; ?> ch.</span>
            </div>
        </div>
    </div>

    <!-- MAIN -->
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
            <h3 class="bks-section-title"><span class="bks-step-num">2</span> Bulletin d'inscription — Informations voyageurs et coordonnées de facturation</h3>
            <p class="bks-section-sub"><?php echo $nb_total; ?> voyageur(s) — <?php echo $nb_chambres; ?> chambre(s) — Départ le <?php echo esc_html($date_fmt); ?></p>
            <?php for ($chambre = 1; $chambre <= $nb_chambres; $chambre++): ?>
            <div class="bks-chambre"><div class="bks-chambre-title">🏨 Chambre <?php echo $chambre; ?></div>
                <?php foreach ($voy_par_chambre[$chambre] ?? [] as $vi): $saved = $bk_saved_voy[$vi] ?? []; ?>
                <div class="bks-voyageur">
                    <div class="bks-voyageur-label">Voyageur <?php echo $vi+1; ?></div>
                    <div class="bks-field-row">
                        <div class="bks-field"><label>Prénom *</label><input type="text" name="voyageurs[<?php echo $vi; ?>][prenom]" class="bks-required" placeholder="Jean" value="<?php echo esc_attr($saved['prenom']??''); ?>"></div>
                        <div class="bks-field"><label>Nom *</label><input type="text" name="voyageurs[<?php echo $vi; ?>][nom]" class="bks-required" placeholder="Dupont" value="<?php echo esc_attr($saved['nom']??''); ?>"></div>
                        <div class="bks-field"><label>Date de naissance *</label>
                            <div id="bks-ddn-wrap-<?php echo $vi; ?>" style="position:relative">
                                <div class="bks-ddn-trigger" id="bks-ddn-trigger-<?php echo $vi; ?>"
                                     onclick="window.bksCalDDN_<?php echo $vi; ?> && window.bksCalDDN_<?php echo $vi; ?>.toggle()">
                                    🎂 Date de naissance
                                </div>
                            </div>
                            <input type="hidden" name="voyageurs[<?php echo $vi; ?>][ddn]" id="bks-ddn-<?php echo $vi; ?>" class="bks-required">
                        </div>
                    </div>
                    <div class="bks-field-row" style="grid-template-columns:1fr 1fr">
                        <div class="bks-field"><label>N° Passeport <span style="color:#9ca3af;font-style:italic">(facultatif)</span></label><input type="text" name="voyageurs[<?php echo $vi; ?>][passeport]" placeholder="XX000000" value="<?php echo esc_attr($saved['passeport']??''); ?>"></div>
                        <div class="bks-field"><label>Nationalité</label><input type="text" name="voyageurs[<?php echo $vi; ?>][nationalite]" placeholder="Française" value="Française"></div>
                    </div>
                    <input type="hidden" name="voyageurs[<?php echo $vi; ?>][chambre]" value="<?php echo $chambre; ?>">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endfor; ?>
        </div>

        <div class="bks-section">
            <h4 class="bks-section-title" style="font-size:16px"><span class="bks-step-num" style="width:24px;height:24px;font-size:12px">📄</span> Coordonnées de facturation</h4>
            <p class="bks-section-sub">Ces informations figureront sur votre facture et permettront à votre conseiller de vous contacter.</p>
            <div class="bks-field-row" style="grid-template-columns:1fr 1fr">
                <div class="bks-field"><label>Prénom *</label><input type="text" id="bks-f-prenom" class="bks-required" placeholder="Jean" value="<?php echo esc_attr($bk_saved_fact['prenom']??''); ?>"></div>
                <div class="bks-field"><label>Nom *</label><input type="text" id="bks-f-nom" class="bks-required" placeholder="Dupont" value="<?php echo esc_attr($bk_saved_fact['nom']??''); ?>"></div>
            </div>
            <div class="bks-field-row" style="grid-template-columns:1fr 1fr">
                <div class="bks-field"><label>Email *</label><input type="email" id="bks-f-email" class="bks-required" placeholder="jean@email.com" value="<?php echo esc_attr($bk_saved_fact['email']??''); ?>"></div>
                <div class="bks-field"><label>Téléphone *</label><input type="tel" id="bks-f-tel" class="bks-required" placeholder="06 XX XX XX XX" value="<?php echo esc_attr($bk_saved_fact['tel']??''); ?>"></div>
            </div>
            <div class="bks-field"><label>Adresse *</label><input type="text" id="bks-f-adresse" class="bks-required" placeholder="12 rue des Fleurs" value="<?php echo esc_attr($bk_saved_fact['adresse']??''); ?>"></div>
            <div class="bks-field-row" style="grid-template-columns:1fr 1fr">
                <div class="bks-field"><label>Code postal *</label><input type="text" id="bks-f-cp" class="bks-required" placeholder="51000" value="<?php echo esc_attr($bk_saved_fact['cp']??''); ?>"></div>
                <div class="bks-field"><label>Ville *</label><input type="text" id="bks-f-ville" class="bks-required" placeholder="Châlons-en-Champagne" value="<?php echo esc_attr($bk_saved_fact['ville']??''); ?>"></div>
            </div>
        </div>

        <div class="bks-nav">
            <button type="button" class="bks-btn-prev" onclick="bksShow(1)">← Retour</button>
            <button type="button" class="bks-btn-next" onclick="bksGoToConfirm()">Vérifier et confirmer →</button>
        </div>
        </div>

        <!-- ═══ ÉTAPE 3 : Confirmation ═══ -->
        <div id="bks-step-3" class="bks-step-page">
        <div class="bks-section">
            <h3 class="bks-section-title"><span class="bks-step-num">3</span> Confirmation de votre réservation</h3>
            <p class="bks-section-sub">Vérifiez scrupuleusement toutes les informations avant de procéder au paiement.</p>

            <div id="bks-recap-final" style="border-radius:14px;margin-bottom:20px"></div>

            <div style="background:#f9fafb;border:1.5px solid #e5e7eb;border-radius:12px;padding:16px;margin-bottom:16px;font-family:'Outfit',sans-serif">
                <div style="font-size:13px;font-weight:700;color:#0f2424;margin-bottom:10px">💳 Mode de règlement</div>
                <label style="display:flex;gap:10px;cursor:pointer;align-items:flex-start;margin-bottom:12px">
                    <input type="radio" name="bks-payment-mode" value="card" checked style="margin-top:4px;flex-shrink:0">
                    <span style="font-size:13px;color:#374151;line-height:1.5"><strong>Payer par carte bancaire</strong> (Paybox sécurisé) — encaissement de l'acompte ou du montant dû en ligne.</span>
                </label>
                <label style="display:flex;gap:10px;cursor:pointer;align-items:flex-start">
                    <input type="radio" name="bks-payment-mode" value="agency" id="bks-payment-agency" style="margin-top:4px;flex-shrink:0">
                    <span style="font-size:13px;color:#374151;line-height:1.5"><strong>Paiement en agence</strong> (pré-réservation)</span>
                </label>
                <div id="bks-agence-confirm-wrap" style="display:none;margin:12px 0 0 28px">
                    <label style="display:flex;gap:8px;cursor:pointer;align-items:flex-start">
                        <input type="checkbox" id="bks-agence-confirm" style="margin-top:2px;flex-shrink:0">
                        <span style="font-size:11px;color:#6b7280;line-height:1.45">Je comprends qu'il s'agit d'une <strong>pré-réservation</strong> : le <strong>prix n'est pas définitivement bloqué</strong> tant que le règlement n'a pas été effectué en agence.</span>
                    </label>
                </div>
            </div>

            <div style="background:#fff8f0;border:1.5px solid #f0dcc0;border-radius:12px;padding:16px;margin-bottom:16px">
                <label style="display:flex;gap:10px;cursor:pointer;align-items:flex-start">
                    <input type="checkbox" id="bks-confirm-info" style="margin-top:3px;flex-shrink:0">
                    <span style="font-size:12px;color:#6b5630;font-family:'Outfit',sans-serif;line-height:1.5">
                        Je certifie que <strong>les noms, prénoms, dates de naissance et informations de passeport</strong> renseignés sont exacts et
                        correspondent aux pièces d'identité officielles de chaque voyageur.
                        Je suis informé(e) que toute erreur pourra entraîner un refus d'embarquement
                        et que les frais de modification de billet seront à ma charge.
                    </span>
                </label>
            </div>

            <label style="display:flex;gap:10px;align-items:flex-start;font-size:12px;font-family:'Outfit',sans-serif;color:#1a3a3a;cursor:pointer;line-height:1.5">
                <input type="checkbox" id="bks-cgu" style="margin-top:3px;flex-shrink:0">
                <span>
                    J'accepte les <a href="<?php echo home_url('/conditions/'); ?>" target="_blank" style="color:#3d9a9a">conditions générales de vente</a>
                    et la <a href="<?php echo home_url('/rgpd'); ?>" target="_blank" style="color:#3d9a9a">politique de confidentialité</a>.
                    Je reconnais avoir pris connaissance du <strong>formulaire d'information standard</strong> prévu par la
                    <a href="https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32015L2302" target="_blank" rel="noopener" style="color:#3d9a9a">Directive (UE) 2015/2302</a>
                    relative aux voyages à forfait, ainsi que des conditions d'annulation du séjour.
                </span>
            </label>

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

        <!-- Aller : date + vol -->
        <div style="border-bottom:1px solid #f0ece4;padding:8px 0">
            <div class="bks-recap-line" style="border:none;padding-bottom:2px"><span>✈️ Aller</span><span><?php echo $date_fmt; ?></span></div>
            <div id="bks-recap-aller-vol" style="display:none;font-size:11px;color:#6b7280;font-family:'Outfit',sans-serif;padding-left:24px">
                <span id="bks-recap-aller-detail">—</span>
            </div>
        </div>

        <!-- Retour : date + vol -->
        <div style="border-bottom:1px solid #f0ece4;padding:8px 0">
            <div class="bks-recap-line" style="border:none;padding-bottom:2px"><span>✈️ Retour</span><span><?php echo $date_retour_fmt; ?></span></div>
            <div id="bks-recap-retour-vol" style="display:none;font-size:11px;color:#6b7280;font-family:'Outfit',sans-serif;padding-left:24px">
                <span id="bks-recap-retour-detail">—</span>
            </div>
        </div>

        <div class="bks-recap-line"><span>🏨 Hôtel</span><span><?php echo esc_html($hotel_nom); ?></span></div>
        <div class="bks-recap-line"><span>🍽️ Pension</span><span><?php echo esc_html($pension); ?></span></div>
        <div class="bks-recap-line"><span>🚐 Transferts</span><span><?php echo esc_html($transfert_label); ?></span></div>
        <div class="bks-recap-line"><span>👥 Voyageurs</span><span><?php echo $nb_total; ?> pers.</span></div>

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
                return;
            }
            // Stop above footer
            var footer = document.querySelector('footer') || document.querySelector('.site-footer') || document.getElementById('footer');
            if (footer) {
                var fRect = footer.getBoundingClientRect();
                var sH = sidebar.offsetHeight;
                if (fRect.top < (160 + sH + 20)) {
                    sidebar.style.opacity = '0';
                    sidebar.style.pointerEvents = 'none';
                } else {
                    sidebar.style.opacity = '1';
                    sidebar.style.pointerEvents = '';
                }
            }
        }
        posSidebar();
        window.addEventListener('resize', posSidebar);
        window.addEventListener('scroll', posSidebar);

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
        // Update recap — aller details
        var allerVol=document.getElementById('bks-recap-aller-vol');
        if(allerVol&&f.depart_time){
            allerVol.style.display='block';
            document.getElementById('bks-recap-aller-detail').textContent=(f.airline_name||'')+' · '+(f.flight_number||'')+' · '+(f.depart_time||'—')+' → '+(f.arrive_time||'—');
        }
        // Update recap — retour details
        var retourVol=document.getElementById('bks-recap-retour-vol');
        if(retourVol&&f.retour_depart){
            retourVol.style.display='block';
            document.getElementById('bks-recap-retour-detail').textContent=(f.airline_name||'')+' · '+(f.retour_flight||'')+' · '+(f.retour_depart||'—')+' → '+(f.retour_arrive||'—');
        }
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

        var S='font-family:Outfit,sans-serif;';
        var section='font-weight:700;font-size:13px;text-transform:uppercase;letter-spacing:1px;color:#59b7b7;margin:18px 0 8px;padding-top:14px;border-top:1px solid #e8e4dc;'+S;
        var rowS='display:flex;justify-content:space-between;padding:5px 0;font-size:13px;color:#4a5568;'+S;
        var html='<div style="'+S+'font-size:13px">';

        // Voyage
        html+='<div style="background:#f0fafa;border-radius:12px;padding:14px;margin-bottom:4px">';
        html+='<div style="font-family:Playfair Display,serif;font-weight:700;font-size:17px;color:#0f2424;margin-bottom:4px">🏖️ '+esc(BK.titre)+'</div>';
        html+='<div style="font-size:12px;color:#4a5568">'+BK.nb_total+' voyageur(s) · '+BK.nb_chambres+' chambre(s)</div>';
        html+='</div>';

        // Vol
        if(selectedCombo){
            html+='<div style="'+section+'">✈️ Vols sélectionnés</div>';
            html+='<div style="'+rowS+'"><span>Aller · '+esc(BK.date_depart?new Date(BK.date_depart).toLocaleDateString('fr-FR',{day:'numeric',month:'short'}):'')+'</span><span>'+(selectedCombo.airline_name||'')+' · '+(selectedCombo.flight_number||'')+' · '+(selectedCombo.depart_time||'—')+' → '+(selectedCombo.arrive_time||'—')+'</span></div>';
            if(selectedCombo.retour_depart) html+='<div style="'+rowS+'"><span>Retour · '+esc(BK.date_retour?new Date(BK.date_retour).toLocaleDateString('fr-FR',{day:'numeric',month:'short'}):'')+'</span><span>'+(selectedCombo.airline_name||'')+' · '+(selectedCombo.retour_flight||'')+' · '+(selectedCombo.retour_depart||'—')+' → '+(selectedCombo.retour_arrive||'—')+'</span></div>';
        }

        // Voyageurs
        html+='<div style="'+section+'">👥 Voyageurs</div>';
        for(var i=0;i<BK.nb_total;i++){
            var p=val('voyageurs['+i+'][prenom]'),n=val('voyageurs['+i+'][nom]'),d=val('voyageurs['+i+'][ddn]'),pp=val('voyageurs['+i+'][passeport]');
            html+='<div style="'+rowS+'"><span>'+esc(p)+' <strong>'+esc(n).toUpperCase()+'</strong></span><span>'+esc(d)+(pp?' · 🛂 '+esc(pp):'')+'</span></div>';
        }

        // Facturation
        html+='<div style="'+section+'">📄 Facturation</div>';
        html+='<div style="'+rowS+'"><span>'+esc(document.getElementById('bks-f-prenom').value)+' <strong>'+esc(document.getElementById('bks-f-nom').value).toUpperCase()+'</strong></span></div>';
        html+='<div style="'+rowS+'"><span>'+esc(document.getElementById('bks-f-email').value)+' · '+esc(document.getElementById('bks-f-tel').value)+'</span></div>';
        html+='<div style="'+rowS+'"><span>'+esc(document.getElementById('bks-f-adresse').value)+', '+esc(document.getElementById('bks-f-cp').value)+' '+esc(document.getElementById('bks-f-ville').value)+'</span></div>';

        html+='</div>';
        document.getElementById('bks-recap-final').innerHTML=html;
        showStep(3);
    };

    window.bksSubmit=function(){
        var payMode = (document.querySelector('input[name="bks-payment-mode"]:checked')||{}).value || 'card';
        if (payMode === 'agency') {
            var ac = document.getElementById('bks-agence-confirm');
            if (!ac || !ac.checked) { alert('Cochez la case relative au paiement en agence (prix non bloqué).'); return; }
        }
        if(!document.getElementById('bks-confirm-info').checked){alert('Certifiez les informations voyageurs.');return}
        if(!document.getElementById('bks-cgu').checked){alert('Acceptez les conditions générales de vente.');return}
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
            vs08_payment_mode:payMode,
            vs08_agence_confirm:(payMode==='agency'&&document.getElementById('bks-agence-confirm')&&document.getElementById('bks-agence-confirm').checked)?'1':'',
            assurance:(document.getElementById('bks-assurance')&&document.getElementById('bks-assurance').checked)?1:0,
            voyageurs:voy,facturation:{prenom:document.getElementById('bks-f-prenom').value,nom:document.getElementById('bks-f-nom').value,email:document.getElementById('bks-f-email').value,tel:document.getElementById('bks-f-tel').value,adresse:document.getElementById('bks-f-adresse').value,cp:document.getElementById('bks-f-cp').value,ville:document.getElementById('bks-f-ville').value}};
        fetch(BK.rest_url+'booking',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':BK.nonce},body:JSON.stringify(body)})
        .then(function(r){return r.json()}).then(function(res){
            var url=res.checkout_url||(res.data&&res.data.checkout_url)||'';
            if(url){window.location.href=url}
            else{
                var msg=(res&&res.message)||(res&&res.data&&typeof res.data==='string'&&res.data)||(res&&res.code)||'Erreur inconnue. Contactez le 03 26 65 28 63.';
                showError(msg);submitting=false;btn.disabled=false;btn.textContent='🔒 Procéder au paiement →';document.getElementById('bks-loading').style.display='none';
            }
        }).catch(function(e){showError('Erreur réseau: '+e.message);submitting=false;btn.disabled=false;btn.textContent='🔒 Procéder au paiement →';document.getElementById('bks-loading').style.display='none'});
    };

    function val(n){var e=document.querySelector('[name="'+n+'"]');return e?e.value.trim():''}
    function esc(s){var d=document.createElement('div');d.textContent=s;return d.innerHTML}
    function fmt(n){return Number(n||0).toLocaleString('fr-FR',{minimumFractionDigits:0,maximumFractionDigits:0})}
    function showError(m){var e=document.getElementById('bks-error');e.textContent=m;e.style.display='block';window.scrollTo({top:0,behavior:'smooth'})}

    // ── Payment mode toggle (agence checkbox) ──
    (function(){
        var agency = document.getElementById('bks-payment-agency');
        var w = document.getElementById('bks-agence-confirm-wrap');
        if (w && agency) {
            function toggle(){ w.style.display = agency.checked ? 'block' : 'none'; }
            document.querySelectorAll('input[name="bks-payment-mode"]').forEach(function(r) {
                r.addEventListener('change', toggle);
            });
        }
    })();

    // ── DDN Calendars (VS08Calendar) ──
    function initDDNCalendars() {
        if (typeof VS08Calendar === 'undefined') return;
        for (var i = 0; i < BK.nb_total; i++) {
            (function(idx) {
                var wrapId    = '#bks-ddn-wrap-' + idx;
                var inputId   = '#bks-ddn-' + idx;
                var triggerId = '#bks-ddn-trigger-' + idx;
                if (!document.querySelector(wrapId)) return;
                var cal = new VS08Calendar({
                    el:        wrapId,
                    mode:      'date',
                    inline:    false,
                    input:     inputId,
                    title:     '🎂 Date de naissance',
                    subtitle:  'Voyageur ' + (idx + 1),
                    yearRange: [1920, new Date().getFullYear()],
                    maxDate:   new Date(),
                    onConfirm: function(dt) {
                        var trigger = document.querySelector(triggerId);
                        if (trigger && dt) {
                            var d = new Date(dt);
                            trigger.textContent = '🎂 ' + d.toLocaleDateString('fr-FR', {day:'numeric',month:'long',year:'numeric'});
                            trigger.style.color = '#0f2424';
                            trigger.style.fontWeight = '600';
                            trigger.style.borderColor = '#3d9a9a';
                        }
                    }
                });
                window['bksCalDDN_' + idx] = cal;
            })(i);
        }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initDDNCalendars);
    else initDDNCalendars();

    // ── Init ──
    searchFlights();
})();
</script>

<?php get_footer(); ?>
