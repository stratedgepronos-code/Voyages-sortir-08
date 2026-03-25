<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
/**
 * Tunnel de réservation Circuit
 * URL: /reservation-circuit/{circuit_id}/
 * Calqué sur booking-steps.php (golf) : chambres groupées, VS08Calendar DDN
 */
if (!defined('ABSPATH')) exit;

$circuit_id = intval(get_query_var('vs08c_circuit_id'));
if (!$circuit_id || get_post_type($circuit_id) !== 'vs08_circuit') { wp_redirect(home_url()); exit; }

$m       = VS08C_Meta::get($circuit_id);
$titre   = get_the_title($circuit_id);
$flag    = VS08C_Meta::resolve_flag($m);
$duree   = intval($m['duree'] ?? 7);
$duree_j = intval($m['duree_jours'] ?? ($duree + 1));

$options_from_url = [];
if (!empty($_GET['options'])) {
    $dec = json_decode(stripslashes($_GET['options']), true);
    if (is_array($dec)) $options_from_url = $dec;
}
$params = [
    'date_depart' => sanitize_text_field($_GET['date'] ?? ''),
    'aeroport'    => strtoupper(sanitize_text_field($_GET['aeroport'] ?? '')),
    'nb_adultes'  => max(1, intval($_GET['nadultes'] ?? 2)),
    'nb_enfants'  => 0,
    'nb_chambres' => max(1, intval($_GET['nchamb'] ?? 1)),
    'prix_vol'    => floatval($_GET['vol'] ?? 0),
    'rooms'       => sanitize_text_field($_GET['rooms'] ?? ''),
    'options'     => !empty($options_from_url) ? json_encode($options_from_url) : '',
];
$nb_total    = $params['nb_adultes'];
$nb_chambres = $params['nb_chambres'];

$devis = VS08C_Calculator::calculate($circuit_id, $params);

$insurance_price = 0;
if (class_exists('VS08V_Insurance')) {
    try { $insurance_price = VS08V_Insurance::get_price($devis['par_pers'] ?? 0); } catch (\Throwable $e) {}
}

$bk_saved_fact = [];
$bk_saved_voy  = [];
if (is_user_logged_in() && class_exists('VS08V_Traveler_Space')) {
    try {
        $bk_saved_fact = VS08V_Traveler_Space::get_saved_facturation();
        $bk_saved_voy  = VS08V_Traveler_Space::get_saved_voyageurs();
    } catch (\Throwable $e) {}
}

$galerie  = $m['galerie'] ?? [];
$hero_img = !empty($galerie[0]) ? $galerie[0] : '';
$date_fmt = $params['date_depart'] ? date('d/m/Y', strtotime($params['date_depart'])) : '';

$acompte_pct = floatval($m['acompte_pct'] ?? 30);
$delai_solde = intval($m['delai_solde'] ?? 30);
$payer_tout  = false;
if ($params['date_depart']) {
    if ((strtotime($params['date_depart']) - time()) / 86400 <= $delai_solde) $payer_tout = true;
}

// Répartition voyageurs par chambre
$voy_par_chambre = [];
for ($c = 1; $c <= $nb_chambres; $c++) $voy_par_chambre[$c] = [];
$vi = 0;
for ($i = 0; $i < $nb_total; $i++) {
    $ch = ($i % $nb_chambres) + 1;
    $voy_par_chambre[$ch][] = $vi;
    $vi++;
}

get_header();
?>

<script>
var BK_CIRCUIT = <?php echo json_encode([
    'circuit_id'     => $circuit_id,
    'titre'          => $titre,
    'duree'          => $duree,
    'iata_dest'      => strtoupper($m['iata_dest'] ?? ''),
    'iata_retour_depart' => (!empty($m['vol_open_jaw']) && !empty($m['iata_retour_depart'])) ? strtoupper($m['iata_retour_depart']) : '',
    'nb_total'       => $nb_total,
    'nb_chambres'    => $nb_chambres,
    'params'         => $params,
    'devis'          => $devis,
    'acompte_pct'    => $acompte_pct,
    'delai_solde'    => $delai_solde,
    'payer_tout'     => $payer_tout,
    'saved_voyageurs'=> $bk_saved_voy,
    'ajax_url'       => admin_url('admin-ajax.php'),
    'nonce'          => wp_create_nonce('vs08c_nonce'),
    'date_retour'    => $params['date_depart'] ? date('Y-m-d', strtotime($params['date_depart'].' +'.$duree.' days')) : '',
    'insurance_pp'   => $insurance_price,
    'insurance_total'=> $insurance_price * $nb_total,
]); ?>;
</script>

<style>
.bkc-wrap{background:#f9f6f0;min-height:100vh;padding:140px 0 80px}
.bkc-inner{max-width:1200px;margin:0 auto;padding:0 60px;display:grid;grid-template-columns:1fr 380px;gap:32px;align-items:start}
.bkc-header{background:linear-gradient(135deg,#0f2424,#1a4a4a);border-radius:18px;padding:24px 28px;margin-bottom:28px;display:flex;align-items:center;gap:20px;color:#fff;grid-column:span 2}
.bkc-header-thumb{width:110px;height:75px;border-radius:10px;object-fit:cover;flex-shrink:0}
.bkc-header-info h2{font-family:'Playfair Display',serif;font-size:22px;margin:0 0 4px;color:#fff}
.bkc-header-info p{font-size:13px;color:rgba(255,255,255,.55);font-family:'Outfit',sans-serif;margin:0}
.bkc-header-chips{display:flex;gap:8px;margin-top:8px;flex-wrap:wrap}
.bkc-chip{font-size:11px;background:rgba(89,183,183,.25);color:#7ecece;padding:4px 12px;border-radius:100px;font-family:'Outfit',sans-serif}
.bkc-section{background:#fff;border-radius:18px;padding:28px;margin-bottom:18px;box-shadow:0 2px 10px rgba(0,0,0,.05)}
.bkc-section-title{font-family:'Playfair Display',serif;font-size:18px;color:#0f2424;margin:0 0 6px;display:flex;align-items:center;gap:10px}
.bkc-section-sub{font-size:13px;color:#6b7280;font-family:'Outfit',sans-serif;margin:0 0 18px}
.bkc-step-num{background:#59b7b7;color:#fff;width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;font-family:'Outfit',sans-serif;flex-shrink:0}
/* Chambre block */
.bkc-chambre{border:1.5px solid #e5e7eb;border-radius:16px;padding:20px;margin-bottom:16px}
.bkc-chambre-title{font-size:15px;font-weight:700;color:#59b7b7;text-transform:uppercase;letter-spacing:1px;font-family:'Outfit',sans-serif;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.bkc-voyageur{border-bottom:1px solid #f0f2f4;padding-bottom:16px;margin-bottom:16px}
.bkc-voyageur:last-child{border-bottom:none;margin-bottom:0;padding-bottom:0}
.bkc-voyageur-label{font-size:13px;font-weight:700;color:#1a3a3a;text-transform:uppercase;letter-spacing:1px;font-family:'Outfit',sans-serif;margin-bottom:10px}
.bkc-field-row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:10px}
.bkc-field-row.cols-2{grid-template-columns:1fr 1fr}
.bkc-field label{display:block;font-size:10px;font-weight:700;color:#1a3a3a;text-transform:uppercase;letter-spacing:.8px;margin-bottom:5px;font-family:'Outfit',sans-serif}
.bkc-field input,.bkc-field select{width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:13px;font-family:'Outfit',sans-serif;color:#0f2424;background:#fafafa;box-sizing:border-box;transition:border-color .2s}
.bkc-field input:focus,.bkc-field select:focus{border-color:#59b7b7;outline:none;box-shadow:0 0 0 3px rgba(89,183,183,.12);background:#fff}
.bkc-optional{font-weight:400;color:#9ca3af;text-transform:lowercase;font-style:italic}
/* DOB trigger VS08 Calendar */
.bkc-ddn-trigger{padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;cursor:pointer;font-size:13px;font-family:'Outfit',sans-serif;color:#9ca3af;background:#fafafa;transition:border-color .2s}
.bkc-ddn-trigger:hover{border-color:#b7dfdf}
/* Fact */
.bkc-fact-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.bkc-fact-grid .full{grid-column:span 2}
/* Recap */
.bkc-recap{background:#fff;border-radius:22px;padding:28px;box-shadow:0 8px 40px rgba(0,0,0,.1);position:sticky;top:90px}
.bkc-recap-title{font-family:'Playfair Display',serif;font-size:17px;color:#0f2424;margin:0 0 16px}
.bkc-recap-line{display:flex;justify-content:space-between;padding:6px 0;font-size:12px;font-family:'Outfit',sans-serif;color:#4a5568;border-bottom:1px solid #f0ece4}
.bkc-recap-sep{border-top:2px solid #0f2424;margin:10px 0 8px}
.bkc-recap-total{display:flex;justify-content:space-between;align-items:baseline}
.bkc-recap-total-lbl{font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:1px;font-family:'Outfit',sans-serif}
.bkc-recap-total-val{font-family:'Playfair Display',serif;font-size:26px;font-weight:700;color:#0f2424}
.bkc-recap-acompte{display:flex;justify-content:space-between;padding:8px 0 0;font-size:12px;font-family:'Outfit',sans-serif;color:#e8724a;font-weight:600}
.bkc-option-row{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f0ece4}
.bkc-option-label{font-size:13px;font-family:'Outfit',sans-serif;color:#1a3a3a;flex:1}
.bkc-option-price{font-size:12px;color:#888;margin-right:12px;font-family:'Outfit',sans-serif}
.bkc-btn-submit{display:block;width:100%;padding:17px;background:linear-gradient(135deg,#59b7b7,#3d9a9a);color:#fff;border:none;border-radius:14px;font-family:'Outfit',sans-serif;font-size:15px;font-weight:700;cursor:pointer;text-align:center;margin-top:18px;transition:all .3s}
.bkc-btn-submit:hover{background:linear-gradient(135deg,#4aa8a8,#2d8a8a);transform:translateY(-1px);box-shadow:0 6px 20px rgba(89,183,183,.3)}
.bkc-btn-submit:disabled{opacity:.5;cursor:not-allowed;transform:none}
/* Navigation étapes */
.bkc-nav{display:flex;gap:12px;margin-top:18px;align-items:center}
.bkc-btn-next{flex:1;padding:15px;background:linear-gradient(135deg,#59b7b7,#3d9a9a);color:#fff;border:none;border-radius:14px;font-family:'Outfit',sans-serif;font-size:15px;font-weight:700;cursor:pointer;text-align:center;transition:all .3s}
.bkc-btn-next:hover{background:linear-gradient(135deg,#4aa8a8,#2d8a8a);transform:translateY(-1px);box-shadow:0 6px 20px rgba(89,183,183,.3)}
.bkc-btn-prev{padding:15px 24px;background:#fff;color:#0f2424;border:1.5px solid #e5e7eb;border-radius:14px;font-family:'Outfit',sans-serif;font-size:14px;font-weight:600;cursor:pointer;transition:all .2s}
.bkc-btn-prev:hover{border-color:#59b7b7;color:#59b7b7}
.bkc-step-content{}
/* Une seule page d'étape visible à la fois (step 1 = Vols, step 2 = Voyageurs+Facturation) */
.bkc-step-page{display:none!important}
.bkc-step-page.bkc-step-active{display:block!important}
/* ── Assurance (mêmes styles que golf) ── */
.bk-ins-wrap{background:linear-gradient(135deg,#f0f9fa 0%,#fdf2f8 100%);border:2px solid #59b7b7;border-radius:18px;padding:0;overflow:hidden}
.bk-ins-header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px 12px;border-bottom:1px solid rgba(89,183,183,.18)}
.bk-ins-logo{height:32px;width:auto}
.bk-ins-badge{font-family:'Outfit',sans-serif;font-size:11px;font-weight:700;background:linear-gradient(135deg,#e3147a,#c30d66);color:#fff;padding:4px 12px;border-radius:20px;letter-spacing:.5px}
.bk-ins-body{padding:14px 20px 16px}
.bk-ins-hook{font-family:'Playfair Display',serif;font-size:16px;font-weight:700;color:#0f2424;line-height:1.35;margin:0 0 6px}
.bk-ins-sub{font-family:'Outfit',sans-serif;font-size:12.5px;color:#4b5563;line-height:1.5;margin:0 0 14px}
.bk-ins-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px}
.bk-ins-card{display:flex;align-items:flex-start;gap:8px;background:rgba(255,255,255,.75);border:1px solid rgba(89,183,183,.2);border-radius:10px;padding:9px 11px}
.bk-ins-card-icon{font-size:18px;flex-shrink:0}
.bk-ins-card-label{font-family:'Outfit',sans-serif;font-size:11.5px;font-weight:600;color:#1f2937;line-height:1.3}
.bk-ins-card-val{font-family:'Outfit',sans-serif;font-size:10.5px;color:#6b7280;line-height:1.3;margin-top:1px}
.bk-ins-docs{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px}
.bk-ins-doc{font-family:'Outfit',sans-serif;font-size:11px;color:#0083a3;text-decoration:none;display:flex;align-items:center;gap:4px;padding:5px 10px;background:rgba(255,255,255,.8);border:1px solid rgba(0,131,163,.2);border-radius:8px;transition:all .2s}
.bk-ins-doc:hover{background:#e3147a;color:#fff;border-color:#e3147a}
.bk-ins-footer{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;background:rgba(255,255,255,.6);border-top:1px solid rgba(89,183,183,.15)}
.bk-ins-check-label{display:flex;align-items:center;gap:12px;cursor:pointer;flex:1}
.bk-ins-check-label input[type=checkbox]{width:20px;height:20px;accent-color:#e3147a;flex-shrink:0}
.bk-ins-check-text{font-family:'Outfit',sans-serif;font-size:13.5px;font-weight:700;color:#0f2424}
.bk-ins-check-text small{font-weight:400;color:#6b7280;font-size:12px}
.bk-ins-price{font-family:'Outfit',sans-serif;text-align:right}
.bk-ins-price-main{font-size:18px;font-weight:800;color:#e3147a}
.bk-ins-price-detail{font-size:11px;color:#6b7280;margin-top:1px}
@media(max-width:480px){.bk-ins-grid{grid-template-columns:1fr}.bk-ins-footer{flex-direction:column;gap:12px;align-items:stretch}.bk-ins-price{text-align:left}}
.bkc-security{text-align:center;margin-top:10px;font-size:11px;color:#999;font-family:'Outfit',sans-serif}
.bkc-error{background:#fee2e2;color:#dc2626;padding:14px 16px;border-radius:12px;font-size:13px;font-family:'Outfit',sans-serif;margin-bottom:16px;display:none}
.bkc-loading{display:none;text-align:center;padding:16px}
.bkc-spinner{width:24px;height:24px;border:3px solid #e5e7eb;border-top-color:#59b7b7;border-radius:50%;animation:bkc-spin .7s linear infinite;margin:0 auto 8px}
@keyframes bkc-spin{to{transform:rotate(360deg)}}
/* ── Sélection vol : route header ── */
.bkc-route-header{display:flex;align-items:center;justify-content:center;gap:14px;background:#0f2424;border-radius:14px;padding:14px 20px;margin-bottom:20px}
.bkc-route-iata{font-family:'Playfair Display',serif;font-size:28px;font-weight:700;color:#fff;letter-spacing:1px;text-transform:uppercase}
.bkc-route-arrow{font-size:22px;color:#59b7b7}
.bkc-route-city{font-size:10px;color:rgba(255,255,255,.5);font-family:'Outfit',sans-serif;text-transform:uppercase;letter-spacing:1px}
.bkc-route-dates{font-size:11px;color:rgba(255,255,255,.45);font-family:'Outfit',sans-serif;margin-top:3px}
/* ── Combo cards ── */
.bkc-combo-loading{display:flex;align-items:center;gap:10px;font-size:15px;color:#9ca3af;font-family:'Outfit',sans-serif;padding:20px 0}
.bkc-flights-spinner{width:20px;height:20px;border:3px solid #e5e7eb;border-top-color:#59b7b7;border-radius:50%;animation:bkc-spin .7s linear infinite;flex-shrink:0}
.bkc-flights-error{padding:14px 16px;background:#fee2e2;color:#dc2626;border-radius:10px;font-size:15px;font-family:'Outfit',sans-serif}
.bkc-combo-card{background:#fff;border:1.5px solid #e5e7eb;border-radius:14px;padding:16px 18px;margin-bottom:10px;cursor:pointer;transition:all .25s}
.bkc-combo-card:hover{border-color:#b7dfdf;box-shadow:0 2px 12px rgba(89,183,183,.08)}
.bkc-combo-card.selected{border-color:#59b7b7;background:#f0fafa;box-shadow:0 2px 16px rgba(89,183,183,.15)}
.bkc-combo-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
.bkc-combo-airline{display:flex;align-items:center;gap:10px}
.bkc-combo-airline img{width:28px;height:28px;border-radius:6px;object-fit:contain;background:#f5f5f5;padding:2px}
.bkc-combo-airline-name{font-size:14px;font-weight:700;color:#0f2424;font-family:'Outfit',sans-serif}
.bkc-combo-airline-sub{font-size:10px;color:#9ca3af;font-family:'Outfit',sans-serif}
.bkc-combo-price{text-align:right}
.bkc-combo-price-delta{font-size:13px;font-weight:700;color:#b85c1a;font-family:'Outfit',sans-serif}
.bkc-combo-price-delta.ref{color:#2d8a5a;background:#e8f8f0;padding:3px 10px;border-radius:100px;font-size:11px}
.bkc-combo-price-sub{font-size:9px;color:#9ca3af;font-family:'Outfit',sans-serif}
.bkc-combo-check{width:24px;height:24px;border-radius:50%;background:#59b7b7;color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;opacity:0;transition:all .2s}
.bkc-combo-card.selected .bkc-combo-check{opacity:1}
.bkc-combo-leg{display:flex;align-items:center;gap:10px;padding:6px 0;font-family:'Outfit',sans-serif;font-size:12px;color:#4a5568;flex-wrap:wrap}
.bkc-combo-leg-badge{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:1px;padding:3px 8px;border-radius:6px;flex-shrink:0}
.bkc-combo-leg-badge.aller{background:#edf8f8;color:#3d9a9a}
.bkc-combo-leg-badge.retour{background:#fff3e8;color:#b85c1a}
.bkc-combo-leg-times{display:flex;align-items:center;gap:6px;flex:1}
.bkc-combo-leg-line{flex:1;display:flex;align-items:center;gap:4px}
.bkc-combo-leg-dash{flex:1;height:1px;background:#ddd}
.bkc-combo-leg-plane{font-size:12px;color:#59b7b7}
.bkc-combo-leg-meta{display:flex;align-items:center;gap:8px;margin-left:auto;flex-shrink:0}
.bkc-combo-leg-dur{font-size:10px;color:#9ca3af;white-space:nowrap}
.bkc-combo-leg-num{font-size:10px;color:#bbb;white-space:nowrap}
.bkc-combo-more{text-align:center;padding:11px;font-size:14px;font-weight:600;color:#59b7b7;cursor:pointer;border-top:1px solid #e5e7eb;transition:color .15s;background:#fafafa;border-radius:0 0 14px 14px}
.bkc-combo-more:hover{color:#0f2424}
.bkc-flights-hidden{display:none}
/* ── Badge escale / direct ── */
.bkc-conn-badge{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:700;letter-spacing:.3px;border-radius:20px;padding:2px 9px;white-space:nowrap;flex-shrink:0}
.bkc-conn-badge.direct{color:#2d8a5a;background:#e8f8f0;border:1px solid #b7e4cc}
.bkc-conn-badge.escale{color:#b85c1a;background:#fff4e6;border:1px solid #f0d9a8}
/* ── Détail escale dépliable ── */
.bkc-conn-detail{display:none;padding:6px 16px 10px 60px;font-size:12px;color:#6b7280;line-height:1.6;font-family:'Outfit',sans-serif;border-top:1px dashed #f0f0f0}
.bkc-conn-detail.open{display:block}
.bkc-conn-toggle{cursor:pointer;user-select:none;display:inline-flex;align-items:center;gap:4px;font-size:12px;color:#59b7b7;padding:2px 16px 2px 60px}
.bkc-conn-toggle:hover{color:#0f2424}
.bkc-conn-toggle .chevron{display:inline-block;transition:transform .2s;font-size:9px}
.bkc-conn-toggle.open .chevron{transform:rotate(180deg)}
.bkc-conn-step{display:flex;align-items:center;gap:6px;padding:3px 0;font-size:12.5px}
.bkc-conn-step .dot{width:6px;height:6px;border-radius:50%;background:#59b7b7;flex-shrink:0}
.bkc-conn-step .dot.layover{background:#f0a030}
.bkc-conn-step.layover-row{color:#b85c1a;font-style:italic;font-size:11.5px}
.bkc-conn-step .seg-flight{color:#9ca3af;font-size:11px;margin-left:4px}
/* ── Sidebar filtres — marge gauche ── */
.bkc-filters-sidebar{position:fixed;top:160px;width:200px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;font-family:'Outfit',sans-serif;box-shadow:0 2px 12px rgba(0,0,0,.06);z-index:50;transition:opacity .3s}
.bkf-title{font-size:16px;font-weight:700;color:#0f2424;margin-bottom:14px}
.bkf-section{margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid #e5e7eb}
.bkf-section:last-of-type{border-bottom:none;margin-bottom:8px;padding-bottom:0}
.bkf-label{font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px}
.bkf-check{display:flex;align-items:center;gap:7px;font-size:13px;color:#4b5563;cursor:pointer;padding:4px 0;transition:color .15s}
.bkf-check:hover{color:#0f2424}
.bkf-check input[type=radio]{accent-color:#3d9a9a;margin:0}
.bkf-n{font-size:11px;background:#e5e7eb;color:#6b7280;border-radius:8px;padding:1px 6px;font-weight:700;margin-left:auto}
.bkf-range-row{display:flex;justify-content:space-between;margin-bottom:4px}
.bkf-range-val{font-size:12px;font-weight:600;color:#3d9a9a}
.bkf-range{width:100%;margin:3px 0;accent-color:#3d9a9a;cursor:pointer}
.bkf-reset{width:100%;padding:7px;border:1.5px solid #e5e7eb;border-radius:10px;background:#fff;color:#6b7280;font-size:13px;font-weight:600;cursor:pointer;transition:all .15s;font-family:'Outfit',sans-serif}
.bkf-reset:hover{border-color:#3d9a9a;color:#3d9a9a}
@media(max-width:960px){.bkc-inner{grid-template-columns:1fr;padding:0 20px}.bkc-header{grid-column:span 1}.bkc-recap{position:static}}
@media(max-width:640px){.bkc-inner{padding:0 14px}.bkc-field-row{grid-template-columns:1fr}.bkc-fact-grid{grid-template-columns:1fr}.bkc-fact-grid .full{grid-column:span 1}.bkc-header{flex-direction:column;text-align:center}.bkc-header-chips{justify-content:center}.bkc-route-iata{font-size:20px}.bkc-route-header{padding:10px 14px;gap:10px}}
</style>

<div class="bkc-wrap">

    <!-- SIDEBAR FILTRES — positionné dans la marge gauche -->
    <aside class="bkc-filters-sidebar" id="bkc-filters-sidebar" style="display:none">
        <div class="bkf-title">Filtres</div>
        <div class="bkf-section">
            <div class="bkf-label">Type de vol</div>
            <label class="bkf-check"><input type="radio" name="bkcf_type" value="all" checked> Tous <span class="bkf-n" id="bkcf-n-all"></span></label>
            <label class="bkf-check"><input type="radio" name="bkcf_type" value="direct"> ✈ Vol direct <span class="bkf-n" id="bkcf-n-direct"></span></label>
            <label class="bkf-check"><input type="radio" name="bkcf_type" value="escale"> ⇄ Avec escale <span class="bkf-n" id="bkcf-n-escale"></span></label>
        </div>
        <div class="bkf-section">
            <div class="bkf-label">Départ aller</div>
            <div class="bkf-range-row">
                <span class="bkf-range-val" id="bkcf-dep-min-lbl">00:00</span>
                <span class="bkf-range-val" id="bkcf-dep-max-lbl">23:59</span>
            </div>
            <input type="range" class="bkf-range" id="bkcf-dep-min" min="0" max="1439" value="0" step="30">
            <input type="range" class="bkf-range" id="bkcf-dep-max" min="0" max="1439" value="1439" step="30">
        </div>
        <div class="bkf-section">
            <div class="bkf-label">Départ retour</div>
            <div class="bkf-range-row">
                <span class="bkf-range-val" id="bkcf-ret-min-lbl">00:00</span>
                <span class="bkf-range-val" id="bkcf-ret-max-lbl">23:59</span>
            </div>
            <input type="range" class="bkf-range" id="bkcf-ret-min" min="0" max="1439" value="0" step="30">
            <input type="range" class="bkf-range" id="bkcf-ret-max" min="0" max="1439" value="1439" step="30">
        </div>
        <button type="button" class="bkf-reset" id="bkcf-reset">Réinitialiser</button>
    </aside>

<div class="bkc-inner">

    <!-- HEADER -->
    <div class="bkc-header">
        <?php if ($hero_img): ?><img src="<?php echo esc_url($hero_img); ?>" alt="" class="bkc-header-thumb"><?php endif; ?>
        <div class="bkc-header-info">
            <h2><?php echo esc_html($flag . ' ' . $titre); ?></h2>
            <p><?php echo esc_html($duree_j . ' jours / ' . $duree . ' nuits · ' . ($m['destination'] ?? '')); ?></p>
            <div class="bkc-header-chips">
                <?php if ($date_fmt): ?><span class="bkc-chip">📅 <?php echo esc_html($date_fmt); ?></span><?php endif; ?>
                <?php if ($params['aeroport']): ?><span class="bkc-chip">✈️ <?php echo esc_html($params['aeroport']); ?></span><?php endif; ?>
                <span class="bkc-chip">👥 <?php echo $nb_total; ?> voyageur<?php echo $nb_total > 1 ? 's' : ''; ?></span>
                <span class="bkc-chip">🛏️ <?php echo $nb_chambres; ?> chambre<?php echo $nb_chambres > 1 ? 's' : ''; ?></span>
            </div>
        </div>
    </div>

    <!-- MAIN -->
    <div class="bkc-main">
        <div class="bkc-error" id="bkc-error"></div>

        <!-- ═══ ÉTAPES 1-3 (formulaire) ═══ -->
        <div id="bkc-steps-form">

        <!-- PAGE 1 : ÉTAPE 1 — Vol + Assurance uniquement (recherche des vols) -->
        <div id="bkc-step-1" class="bkc-step-page bkc-step-active">
        <div class="bkc-section">
            <h3 class="bkc-section-title"><span class="bkc-step-num">1</span> Sélection de votre vol</h3>
            <p class="bkc-section-sub">Choisissez votre combinaison aller-retour parmi les vols disponibles.</p>

            <?php
            $d_aller  = $params['date_depart'] ? date('d/m/Y', strtotime($params['date_depart'])) : '—';
            $d_retour_fmt = $params['date_depart'] ? date('d/m/Y', strtotime($params['date_depart'].' +'.$duree.' days')) : '—';
            $iata_dest = strtoupper($m['iata_dest'] ?? '');
            ?>
            <div class="bkc-route-header">
                <div style="text-align:center">
                    <div class="bkc-route-iata"><?php echo esc_html($params['aeroport'] ?: '—'); ?></div>
                    <div class="bkc-route-city"><?php echo esc_html($params['aeroport']); ?></div>
                </div>
                <div style="text-align:center">
                    <div class="bkc-route-arrow">✈️ ⟷</div>
                    <div class="bkc-route-dates"><?php echo esc_html($d_aller . ' → ' . $d_retour_fmt . ' · ' . $nb_total . ' pax'); ?></div>
                </div>
                <div style="text-align:center">
                    <div class="bkc-route-iata"><?php echo esc_html($iata_dest ?: '—'); ?></div>
                    <div class="bkc-route-city"><?php echo esc_html($iata_dest); ?></div>
                </div>
            </div>

            <div id="bkc-combo-wrap">
                <div class="bkc-combo-loading" id="bkc-combo-loading">
                    <div class="bkc-flights-spinner"></div>
                    Recherche des vols aller et retour…
                </div>
                <div id="bkc-combo-list"></div>
                <div id="bkc-combo-no-match" class="bkc-flights-error" style="display:none">Aucun vol ne correspond à vos filtres.</div>
                <div id="bkc-combo-error" class="bkc-flights-error" style="display:none"></div>
            </div>

            <input type="hidden" id="bkc-selected-offer-id" name="selected_offer_id" value="">
            <input type="hidden" id="bkc-selected-vol-delta" name="vol_delta_pax" value="0">
        </div>

        <!-- ══ ASSURANCE VOYAGE ══ -->
        <?php if ($insurance_price > 0): ?>
        <div class="bkc-section">
            <div class="bk-ins-wrap">
                <div class="bk-ins-header">
                    <img src="<?php echo defined('VS08V_URL') ? VS08V_URL : ''; ?>assets/img/assurever-logo.png" alt="Assurever" class="bk-ins-logo" onerror="this.style.display='none'">
                    <span class="bk-ins-badge">GALAXY MULTIRISQUE</span>
                </div>
                <div class="bk-ins-body">
                    <div class="bk-ins-hook">Voyagez l'esprit libre, on s'occupe du reste.</div>
                    <p class="bk-ins-sub">Annulation selon cause prévue dans le contrat, rapatriement 24h/24, frais médicaux à l'étranger, bagages… Une couverture complète pour partir sereinement.</p>
                    <?php if (defined('VS08V_URL')): ?>
                    <div class="bk-ins-docs">
                        <a href="<?php echo VS08V_URL; ?>assets/docs/assurever-ipid-galaxy.pdf" target="_blank" class="bk-ins-doc">📄 Fiche produit (IPID)</a>
                        <a href="<?php echo VS08V_URL; ?>assets/docs/assurever-conditions-galaxy.pdf" target="_blank" class="bk-ins-doc">📄 Conditions générales</a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="bk-ins-footer">
                    <label class="bk-ins-check-label">
                        <input type="checkbox" id="bkc-assurance" onchange="bkcUpdateInsurance()">
                        <div class="bk-ins-check-text">Oui, je souhaite être protégé(e)<br><small>Assurance Multirisque GALAXY · Assurever / Mutuaide</small></div>
                    </label>
                    <div class="bk-ins-price">
                        <div class="bk-ins-price-main"><?php echo number_format($insurance_price * $nb_total, 2, ',', ' '); ?> €</div>
                        <div class="bk-ins-price-detail"><?php echo number_format($insurance_price, 2, ',', ' '); ?> € /pers. × <?php echo $nb_total; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="bkc-nav">
            <button type="button" class="bkc-btn-next" onclick="bkcGoToStep2()">Continuer →</button>
        </div>
        </div><!-- /bkc-step-1 -->

        <!-- PAGE 2 : ÉTAPE 2 — Bulletin d'inscription (voyageurs + facturation), step juste après la page Vols -->
        <div id="bkc-step-2" class="bkc-step-page">
        <div class="bkc-section">
            <h3 class="bkc-section-title"><span class="bkc-step-num">2</span> Bulletin d'inscription — Informations voyageurs et coordonnées de facturation</h3>
            <p class="bkc-section-sub"><?php echo $nb_total; ?> voyageur(s) — <?php echo $nb_chambres; ?> chambre(s) — Départ le <?php echo esc_html($date_fmt); ?></p>

            <?php
            $voy_index = 0;
            for ($chambre = 1; $chambre <= $nb_chambres; $chambre++):
                $voy_in_ch = $voy_par_chambre[$chambre] ?? [];
            ?>
            <div class="bkc-chambre">
                <div class="bkc-chambre-title">🏨 Chambre <?php echo $chambre; ?></div>
                <?php foreach ($voy_in_ch as $vi):
                    $saved = $bk_saved_voy[$vi] ?? [];
                ?>
                <div class="bkc-voyageur" data-voy-index="<?php echo $vi; ?>">
                    <div class="bkc-voyageur-label">Voyageur <?php echo $vi + 1; ?></div>
                    <div class="bkc-field-row">
                        <div class="bkc-field"><label>Prénom *</label>
                            <input type="text" name="voyageurs[<?php echo $vi; ?>][prenom]" class="bkc-required" placeholder="Jean" value="<?php echo esc_attr($saved['prenom'] ?? ''); ?>">
                        </div>
                        <div class="bkc-field"><label>Nom *</label>
                            <input type="text" name="voyageurs[<?php echo $vi; ?>][nom]" class="bkc-required" placeholder="Dupont" value="<?php echo esc_attr($saved['nom'] ?? ''); ?>">
                        </div>
                        <div class="bkc-field"><label>Date de naissance *</label>
                            <div id="bkc-ddn-wrap-<?php echo $vi; ?>" style="position:relative">
                                <div class="bkc-ddn-trigger" id="bkc-ddn-trigger-<?php echo $vi; ?>"
                                     onclick="window.bkcCalDDN_<?php echo $vi; ?> && window.bkcCalDDN_<?php echo $vi; ?>.toggle()">
                                    🎂 Date de naissance
                                </div>
                            </div>
                            <input type="hidden" name="voyageurs[<?php echo $vi; ?>][ddn]" id="bkc-ddn-<?php echo $vi; ?>" class="bkc-required">
                        </div>
                    </div>
                    <div class="bkc-field-row cols-2">
                        <div class="bkc-field"><label>N° Passeport <span class="bkc-optional">(facultatif)</span></label>
                            <input type="text" name="voyageurs[<?php echo $vi; ?>][passeport]" placeholder="XX000000" value="<?php echo esc_attr($saved['passeport'] ?? ''); ?>">
                        </div>
                        <div class="bkc-field"><label>Nationalité</label>
                            <input type="text" name="voyageurs[<?php echo $vi; ?>][nationalite]" placeholder="Française" value="Française">
                        </div>
                    </div>
                    <input type="hidden" name="voyageurs[<?php echo $vi; ?>][type]" value="adulte">
                    <input type="hidden" name="voyageurs[<?php echo $vi; ?>][chambre]" value="<?php echo $chambre; ?>">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endfor; ?>
        </div>

        <div class="bkc-section" style="margin-top:24px;padding-top:24px;border-top:1px solid #e5e7eb">
            <h4 class="bkc-section-title" style="font-size:16px"><span class="bkc-step-num" style="width:24px;height:24px;font-size:12px">2</span> Coordonnées de facturation</h4>
            <p class="bkc-section-sub">Ces informations figureront sur votre facture et permettront à votre conseiller de vous contacter.</p>
            <div class="bkc-fact-grid">
                <div class="bkc-field"><label>Prénom *</label><input type="text" id="fact-prenom" class="bkc-required" placeholder="Jean" value="<?php echo esc_attr($bk_saved_fact['prenom'] ?? ''); ?>"></div>
                <div class="bkc-field"><label>Nom *</label><input type="text" id="fact-nom" class="bkc-required" placeholder="Dupont" value="<?php echo esc_attr($bk_saved_fact['nom'] ?? ''); ?>"></div>
                <div class="bkc-field"><label>Email *</label><input type="email" id="fact-email" class="bkc-required" placeholder="jean@email.com" value="<?php echo esc_attr($bk_saved_fact['email'] ?? ''); ?>"></div>
                <div class="bkc-field"><label>Téléphone *</label><input type="tel" id="fact-tel" class="bkc-required" placeholder="06 XX XX XX XX" value="<?php echo esc_attr($bk_saved_fact['tel'] ?? ''); ?>"></div>
                <div class="bkc-field full"><label>Adresse *</label><input type="text" id="fact-adresse" class="bkc-required" placeholder="12 rue des Fleurs" value="<?php echo esc_attr($bk_saved_fact['adresse'] ?? ''); ?>"></div>
                <div class="bkc-field"><label>Code postal *</label><input type="text" id="fact-cp" class="bkc-required" placeholder="51000" value="<?php echo esc_attr($bk_saved_fact['cp'] ?? ''); ?>"></div>
                <div class="bkc-field"><label>Ville *</label><input type="text" id="fact-ville" class="bkc-required" placeholder="Châlons-en-Champagne" value="<?php echo esc_attr($bk_saved_fact['ville'] ?? ''); ?>"></div>
            </div>
        </div>

        <!-- Options : récap des options choisies sur la page produit (champs cachés pour le POST) -->
        <?php
        $circuit_options = $m['options'] ?? [];
        $options_recap = [];
        if (!empty($options_from_url) && !empty($circuit_options)) {
            foreach ($circuit_options as $opt) {
                $oid = $opt['id'] ?? '';
                $qty = isset($options_from_url[$oid]) ? max(0, intval($options_from_url[$oid])) : 0;
                if ($qty > 0) {
                    $options_recap[] = ['label' => $opt['label'], 'prix' => $opt['prix'], 'type' => $opt['type'] ?? 'par_pers', 'qty' => $qty];
                }
            }
        }
        foreach ($options_from_url as $oid => $qty):
            if (intval($qty) <= 0) continue;
        ?><input type="hidden" name="options[<?php echo esc_attr($oid); ?>]" value="<?php echo esc_attr(intval($qty)); ?>"><?php endforeach; ?>

        <div class="bkc-nav">
            <button type="button" class="bkc-btn-prev" onclick="bkcGoBackToStep1()">← Retour</button>
            <button type="button" class="bkc-btn-next" onclick="bkcGoToConfirm()">Vérifier et confirmer →</button>
        </div>
        </div><!-- /bkc-step-2 -->

        </div><!-- /bkc-steps-form -->

        <!-- ═══ ÉTAPE 4 : CONFIRMATION (page séparée) ═══ -->
        <div id="bkc-step-confirm" style="display:none">

        <div class="bkc-section">
            <h3 class="bkc-section-title"><span class="bkc-step-num">3</span> Confirmation de votre réservation</h3>
            <p class="bkc-section-sub">Vérifiez scrupuleusement toutes les informations avant de procéder au paiement.</p>

            <div id="bkc-recap-final" style="border-radius:14px;margin-bottom:20px"></div>

            <div style="background:#fff8f0;border:1.5px solid #f0dcc0;border-radius:12px;padding:16px;margin-bottom:16px">
                <label style="display:flex;gap:10px;cursor:pointer;align-items:flex-start">
                    <input type="checkbox" id="bkc-confirm-info" style="margin-top:3px;flex-shrink:0">
                    <span style="font-size:12px;color:#6b5630;font-family:'Outfit',sans-serif;line-height:1.5">
                        Je certifie que <strong>les noms, prénoms, dates de naissance et informations de passeport</strong> renseignés sont exacts et
                        correspondent aux pièces d'identité officielles de chaque voyageur.
                        Je suis informé(e) que toute erreur pourra entraîner un refus d'embarquement
                        et que les frais de modification de billet seront à ma charge.
                    </span>
                </label>
            </div>

            <label style="display:flex;gap:10px;align-items:flex-start;font-size:12px;font-family:'Outfit',sans-serif;color:#1a3a3a;cursor:pointer;line-height:1.5">
                <input type="checkbox" id="bkc-cgu" style="margin-top:3px;flex-shrink:0">
                <span>
                    J'accepte les <a href="<?php echo home_url('/conditions/'); ?>" target="_blank" style="color:#3d9a9a">conditions générales de vente</a>
                    et la <a href="<?php echo home_url('/rgpd'); ?>" target="_blank" style="color:#3d9a9a">politique de confidentialité</a>.
                    Je reconnais avoir pris connaissance du <strong>formulaire d'information standard</strong> prévu par la
                    <a href="https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32015L2302" target="_blank" rel="noopener" style="color:#3d9a9a">Directive (UE) 2015/2302</a>
                    relative aux voyages à forfait, ainsi que des conditions d'annulation du séjour.
                    Conformément à l'article L211-8 du Code du Tourisme, je dispose d'un droit de rétractation
                    dans les conditions prévues par le contrat.
                </span>
            </label>

            <div class="bkc-nav" style="margin-top:24px">
                <button type="button" class="bkc-btn-prev" onclick="bkcGoBack()">← Retour</button>
                <button type="button" class="bkc-btn-next" id="bkc-btn-submit" onclick="bkcSubmit()">🔒 Procéder au paiement →</button>
            </div>
        </div>

        </div><!-- /bkc-step-confirm -->
    </div>

    <!-- SIDEBAR RÉCAP -->
    <div class="bkc-recap">
        <h3 class="bkc-recap-title">📋 Récapitulatif</h3>
        <div class="bkc-recap-line" style="font-weight:600;color:#0f2424"><span>🗺️ <?php echo esc_html($titre); ?></span></div>
        <div class="bkc-recap-line"><span>📅 Départ</span><span><?php echo esc_html($date_fmt ?: '—'); ?></span></div>
        <div class="bkc-recap-line"><span>✈️ Aéroport</span><span><?php echo esc_html($params['aeroport'] ?: '—'); ?></span></div>
        <div class="bkc-recap-line" id="bkc-recap-row-vol" style="display:none"><span>✈️ Vol aller</span><span id="bkc-recap-vol-detail">—</span></div>
        <div class="bkc-recap-line" id="bkc-recap-row-retour" style="display:none"><span>✈️ Vol retour</span><span id="bkc-recap-retour-detail">—</span></div>
        <div class="bkc-recap-line" id="bkc-recap-row-vol-delta" style="display:none"><span>Supplément vol</span><span id="bkc-recap-vol-delta-val">—</span></div>
        <div class="bkc-recap-line"><span>📅 Durée</span><span><?php echo $duree_j; ?>j / <?php echo $duree; ?>n</span></div>
        <div class="bkc-recap-line"><span>👥 Voyageurs</span><span><?php echo $nb_total; ?> pers.</span></div>
        <?php if (!empty($options_recap)): ?>
        <div class="bkc-recap-line" style="font-size:12px;color:#6b7280"><span>🎁 Options</span><span><?php echo esc_html(implode(', ', array_map(function($o){ return $o['label']; }, $options_recap))); ?></span></div>
        <?php endif; ?>
        <div class="bkc-recap-line" id="bkc-recap-row-insurance" style="display:none"><span>🛡️ Assurance Multirisques</span><span id="bkc-recap-insurance-val">—</span></div>
        <div style="height:12px"></div>
        <?php foreach ($devis['lines'] as $line): ?>
        <div class="bkc-recap-line"><span><?php echo esc_html($line['label']); ?></span><span><?php echo number_format($line['montant'], 0, ',', ' '); ?> €</span></div>
        <?php endforeach; ?>
        <div class="bkc-recap-sep"></div>
        <div class="bkc-recap-total">
            <span class="bkc-recap-total-lbl">Total circuit</span>
            <span class="bkc-recap-total-val" id="bkc-recap-total-val"><?php echo number_format($devis['total'], 0, ',', ' '); ?> €</span>
        </div>
        <div style="font-size:11px;color:#6b7280;text-align:right;font-family:'Outfit',sans-serif;margin-top:2px">soit <?php echo number_format($devis['par_pers'], 0, ',', ' '); ?> €/pers.</div>
        <?php if (!$payer_tout && ($devis['acompte'] ?? 0) < ($devis['total'] ?? 0)): ?>
        <div class="bkc-recap-acompte"><span>🔒 Acompte <?php echo intval($acompte_pct); ?>%</span><span><?php echo number_format($devis['acompte'], 0, ',', ' '); ?> €</span></div>
        <div style="font-size:11px;color:#6b7280;font-family:'Outfit',sans-serif;margin-top:4px">Solde à régler <?php echo $delai_solde; ?> jours avant le départ</div>
        <?php endif; ?>
        <div class="bkc-loading" id="bkc-loading"><div class="bkc-spinner"></div><div style="font-size:13px;color:#59b7b7;font-family:'Outfit',sans-serif">Création de votre réservation…</div></div>
        <div class="bkc-security" style="margin-top:12px">Paiement sécurisé 3D Secure · APST · Atout France</div>
    </div>

</div></div>

<script>
(function(){
    var BK = BK_CIRCUIT;
    var submitting = false;
    var bkc_insurance_check = false;

    window.bkcUpdateInsurance = function() {
        var chk = document.getElementById('bkc-assurance');
        bkc_insurance_check = chk && chk.checked;
        var row = document.getElementById('bkc-recap-row-insurance');
        var val = document.getElementById('bkc-recap-insurance-val');
        var totalValEl = document.getElementById('bkc-recap-total-val');
        if (bkc_insurance_check && BK.insurance_total > 0) {
            if (row) row.style.display = 'flex';
            if (val) { val.textContent = '+' + bkcFmt(BK.insurance_total); val.style.color = '#e3147a'; }
        } else {
            if (row) row.style.display = 'none';
        }
        var base = parseFloat(BK.devis.total) || 0;
        var add = (bkc_insurance_check && (parseFloat(BK.insurance_total) || 0)) ? (parseFloat(BK.insurance_total) || 0) : 0;
        if (totalValEl) totalValEl.textContent = bkcFmt(base + add);
    };

    /* ── VS08 Calendar pour dates de naissance ── */
    function initDDNCalendars() {
        if (typeof VS08Calendar === 'undefined') return;
        for (var i = 0; i < BK.nb_total; i++) {
            (function(idx) {
                var wrapId    = '#bkc-ddn-wrap-' + idx;
                var inputId   = '#bkc-ddn-' + idx;
                var triggerId = '#bkc-ddn-trigger-' + idx;
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
                window['bkcCalDDN_' + idx] = cal;
            })(i);
        }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initDDNCalendars);
    else initDDNCalendars();

    /* ═══════════════════════════════════════════════════════
       RECHERCHE VOLS — aller + retour → combos (comme golf)
       ═══════════════════════════════════════════════════════ */
    var bkc_flights_data = [];
    var bkc_retour_data  = [];
    var bkc_combos_data  = [];
    var bkc_aller_loaded = false;
    var bkc_retour_loaded = false;
    var bkc_vol_nb_pax   = BK.nb_total;
    var bkc_vol_delta_total = 0;

    (function bkcLoadFlights(){
        var aero    = BK.params.aeroport;
        var date    = BK.params.date_depart;
        var errDiv  = document.getElementById('bkc-combo-error');
        var loading = document.getElementById('bkc-combo-loading');

        if (!aero || !date) {
            if (loading) loading.style.display = 'none';
            if (errDiv) { errDiv.style.display = 'block'; errDiv.textContent = 'Aucun aéroport ou date sélectionné. Retournez sur la fiche circuit.'; }
            return;
        }

        function bkcPostFlight(payload, done) {
            var ajaxData = { action: 'vs08c_get_flight', nonce: BK.nonce };
            for (var k in payload) ajaxData[k] = payload[k];
            jQuery.post(BK.ajax_url, ajaxData).done(done).fail(function(){ done({ success: false }); });
        }

        /* Une seule requête : l’API renvoie des offres A/R complètes (open jaw & escales gérés côté serveur). */
        bkcPostFlight({
            circuit_id: BK.circuit_id,
            date: date,
            aeroport: aero,
            passengers: bkc_vol_nb_pax
        }, function(res){
            if (res && res.success && res.data && res.data.flights && res.data.flights.length) {
                bkc_flights_data = res.data.flights;
            }
            bkc_aller_loaded = true;
            bkc_retour_loaded = true;
            bkc_retour_data = [];
            bkcTryBuildCombos();
        });
    })();

    function bkcAddDays(dateStr, days) {
        var d = new Date(dateStr); d.setDate(d.getDate() + parseInt(days));
        return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
    }
    function bkcFmtDuration(min) { if(!min) return ''; var h=Math.floor(min/60), m=min%60; return h+'h'+String(m).padStart(2,'0'); }
    function bkcEsc(str) { var d=document.createElement('div'); d.appendChild(document.createTextNode(str||'')); return d.innerHTML; }
    function bkcFmt(n) { return Math.ceil(parseFloat(n||0)).toLocaleString('fr-FR',{minimumFractionDigits:0,maximumFractionDigits:0})+' €'; }

    function bkcTryBuildCombos() {
        if (!bkc_aller_loaded || !bkc_retour_loaded) return;
        var loading = document.getElementById('bkc-combo-loading');
        var errDiv  = document.getElementById('bkc-combo-error');
        if (loading) loading.style.display = 'none';

        if (!bkc_flights_data.length && !bkc_retour_data.length) {
            if (errDiv) { errDiv.style.display = 'block'; errDiv.textContent = 'Aucun vol disponible pour cette combinaison. Contactez-nous au 03 26 65 28 63.'; }
            return;
        }

        var combos = [];

        /* Offres aller-retour complètes (Duffel / SerpApi) */
        if (bkc_flights_data.length && bkc_flights_data[0].is_roundtrip) {
            bkc_flights_data.forEach(function(a) {
                combos.push({
                    aller: a,
                    retour: {
                        flight_number: a.retour_flight,
                        flight_detail: a.retour_flights_detail || a.retour_flight || '',
                        segments_detail: a.retour_segments_detail || [],
                        depart_time: a.retour_depart,
                        arrive_time: a.retour_arrive,
                        duration_min: a.retour_duration,
                        has_connections: a.has_connections,
                        airline_iata: a.airline_iata,
                        airline_name: a.airline_name
                    },
                    airline_name: a.airline_name,
                    airline_iata: a.airline_iata,
                    total_delta: a.delta_per_pax || 0
                });
            });
        } else {
            bkc_flights_data.forEach(function(a) {
                bkc_retour_data.filter(function(r){ return r.airline_iata === a.airline_iata; }).forEach(function(r) {
                    combos.push({ aller:a, retour:r, airline_name:a.airline_name, airline_iata:a.airline_iata, total_delta:(a.delta_per_pax||0)+(r.delta_per_pax||0) });
                });
            });
            if (!combos.length) {
                bkc_flights_data.forEach(function(a) {
                    combos.push({ aller:a, retour:null, airline_name:a.airline_name, airline_iata:a.airline_iata, total_delta:a.delta_per_pax||0 });
                });
            }
        }

        combos.sort(function(a,b){ return a.total_delta - b.total_delta; });
        if (combos.length) combos[0].is_reference = true;
        bkc_combos_data = combos;

        bkcRenderCombos(combos);
        bkcInitSidebarFilters();
        bkcPositionSidebar();
        bkcSelectCombo(0);
    }

    function bkcTimeToMin(t) {
        if (!t) return 0;
        var p = (t+'').split(':');
        return (parseInt(p[0],10)||0)*60 + (parseInt(p[1],10)||0);
    }
    function bkcMinToTime(m) {
        var h = Math.floor(m/60) % 24;
        var mn = m % 60;
        return String(h).padStart(2,'0') + ':' + String(mn).padStart(2,'0');
    }
    function bkcHasConn(f) {
        if (!f) return false;
        if (f.has_connections === true || f.has_connections === 1 || f.has_connections === '1') return true;
        if (f.flight_detail && f.flight_detail.indexOf('+') !== -1) return true;
        return false;
    }
    function bkcConnBadge(f) {
        if (!f) return '';
        var isConn = bkcHasConn(f);
        var cls = isConn ? 'escale' : 'direct';
        var label = isConn ? '1 escale' : 'Vol direct';
        return '<span class="bkc-conn-badge ' + cls + '">' + label + '</span>';
    }
    function bkcFmtLayover(min) {
        if (!min) return '';
        var h = Math.floor(min / 60), m = min % 60;
        if (h > 0 && m > 0) return h + 'h' + String(m).padStart(2,'0');
        if (h > 0) return h + 'h';
        return m + 'min';
    }
    function bkcConnDetail(f, idx, legType) {
        if (!f || !bkcHasConn(f)) return '';
        var segs = f.segments_detail || [];
        var id = 'bkc-conn-detail-' + idx + '-' + legType;
        var togId = 'bkc-conn-tog-' + idx + '-' + legType;
        var stepsHtml = '';

        if (segs.length > 0) {
            segs.forEach(function(s, i) {
                if (i > 0 && s.layover_before_min > 0) {
                    stepsHtml += '<div class="bkc-conn-step layover-row"><span class="dot layover"></span> Escale · ' + bkcFmtLayover(s.layover_before_min) + ' d\'attente</div>';
                }
                stepsHtml += '<div class="bkc-conn-step"><span class="dot"></span>'
                    + ' <strong>' + bkcEsc(s.origin) + '</strong> ' + bkcEsc(s.depart_time)
                    + ' → <strong>' + bkcEsc(s.destination) + '</strong> ' + bkcEsc(s.arrive_time)
                    + ' <span class="seg-flight">' + bkcEsc(s.flight) + '</span>'
                    + '</div>';
            });
        } else {
            var detail = f.flight_detail || f.flight_number || '';
            var parts = detail.split(/\s*\+\s*/);
            parts.forEach(function(p, i) {
                if (i > 0) stepsHtml += '<div class="bkc-conn-step"><span class="dot layover"></span> <em>Correspondance</em></div>';
                stepsHtml += '<div class="bkc-conn-step"><span class="dot"></span> Vol ' + bkcEsc(p.trim()) + '</div>';
            });
        }

        return '<div class="bkc-conn-toggle" id="' + togId + '" onclick="event.stopPropagation();var d=document.getElementById(\'' + id + '\');var t=document.getElementById(\'' + togId + '\');if(d.classList.contains(\'open\')){d.classList.remove(\'open\');t.classList.remove(\'open\')}else{d.classList.add(\'open\');t.classList.add(\'open\')}">'
            + '<span class="chevron">▼</span> Voir les détails'
            + '</div>'
            + '<div class="bkc-conn-detail" id="' + id + '">' + stepsHtml + '</div>';
    }

    function bkcRenderCombos(combos) {
        var list = document.getElementById('bkc-combo-list');
        if (!list) return;
        list.innerHTML = '';

        var nbDirect = 0, nbEscale = 0;
        combos.forEach(function(c) {
            var conn = bkcHasConn(c.aller) || (c.retour && bkcHasConn(c.retour));
            if (conn) nbEscale++; else nbDirect++;
        });

        var nAll = document.getElementById('bkcf-n-all');
        var nDir = document.getElementById('bkcf-n-direct');
        var nEsc = document.getElementById('bkcf-n-escale');
        if (nAll) nAll.textContent = combos.length;
        if (nDir) nDir.textContent = nbDirect;
        if (nEsc) nEsc.textContent = nbEscale;

        combos.forEach(function(c, idx) {
            var a = c.aller, r = c.retour;
            var comboConn = bkcHasConn(a) || (r && bkcHasConn(r));
            var depMin = bkcTimeToMin(a.depart_time);
            var retMin = r ? bkcTimeToMin(r.depart_time) : 0;

            var priceHtml = '';
            if (c.is_reference) {
                priceHtml = '<div class="bkc-combo-price-delta ref">Meilleur prix</div>';
            } else {
                priceHtml = '<div class="bkc-combo-price-delta">+'+bkcFmt(c.total_delta)+'</div><div class="bkc-combo-price-sub">/pers. aller-retour</div>';
            }

            var html = '<div class="bkc-combo-header">'
                +'<div class="bkc-combo-airline">'
                +'<img src="https://images.kiwi.com/airlines/64/'+bkcEsc(a.airline_iata)+'.png" alt="" onerror="this.style.display=\'none\'">'
                +'<div><div class="bkc-combo-airline-name">'+bkcEsc(c.airline_name)+'</div>'
                +'<div class="bkc-combo-airline-sub">'+bkcEsc(a.airline_iata)+'</div></div>'
                +'</div>'
                +'<div style="display:flex;align-items:center;gap:8px">'
                +'<div class="bkc-combo-price">'+priceHtml+'</div>'
                +'<div class="bkc-combo-check">✓</div>'
                +'</div></div>';

            html += '<div class="bkc-combo-leg">'
                +'<div class="bkc-combo-leg-badge aller">ALLER</div>'
                +'<div class="bkc-combo-leg-times">'
                +'<div>'+bkcEsc(a.depart_time)+'</div>'
                +'<div class="bkc-combo-leg-line"><div class="bkc-combo-leg-dash"></div><div class="bkc-combo-leg-plane">✈</div><div class="bkc-combo-leg-dash"></div></div>'
                +'<div>'+bkcEsc(a.arrive_time)+'</div>'
                +'</div>'
                +'<div class="bkc-combo-leg-meta">'
                +bkcConnBadge(a)
                +'<span class="bkc-combo-leg-dur">'+bkcFmtDuration(a.duration_min)+'</span>'
                +'<span class="bkc-combo-leg-num">'+bkcEsc(a.flight_number)+'</span>'
                +'</div>'
                +'</div>'
                +bkcConnDetail(a, idx, 'aller');

            if (r) {
                html += '<div class="bkc-combo-leg">'
                    +'<div class="bkc-combo-leg-badge retour">RETOUR</div>'
                    +'<div class="bkc-combo-leg-times">'
                    +'<div>'+bkcEsc(r.depart_time)+'</div>'
                    +'<div class="bkc-combo-leg-line"><div class="bkc-combo-leg-dash"></div><div class="bkc-combo-leg-plane">✈</div><div class="bkc-combo-leg-dash"></div></div>'
                    +'<div>'+bkcEsc(r.arrive_time)+'</div>'
                    +'</div>'
                    +'<div class="bkc-combo-leg-meta">'
                    +bkcConnBadge(r)
                    +'<span class="bkc-combo-leg-dur">'+bkcFmtDuration(r.duration_min)+'</span>'
                    +'<span class="bkc-combo-leg-num">'+bkcEsc(r.flight_number)+'</span>'
                    +'</div>'
                    +'</div>'
                    +bkcConnDetail(r, idx, 'retour');
            }

            var card = document.createElement('div');
            card.className = 'bkc-combo-card';
            card.id = 'bkc-combo-' + idx;
            card.setAttribute('data-conn', comboConn ? '1' : '0');
            card.setAttribute('data-dep', depMin);
            card.setAttribute('data-ret', retMin);
            card.innerHTML = html;
            card.addEventListener('click', (function(i){ return function(){ bkcSelectCombo(i); }; })(idx));
            list.appendChild(card);
        });
    }

    function bkcInitSidebarFilters() {
        var radios = document.querySelectorAll('input[name="bkcf_type"]');
        var depMinR = document.getElementById('bkcf-dep-min');
        var depMaxR = document.getElementById('bkcf-dep-max');
        var retMinR = document.getElementById('bkcf-ret-min');
        var retMaxR = document.getElementById('bkcf-ret-max');
        var resetBtn = document.getElementById('bkcf-reset');

        function applyFilters() {
            var typeVal = 'all';
            radios.forEach(function(r){ if(r.checked) typeVal = r.value; });
            var dMin = parseInt(depMinR.value,10);
            var dMax = parseInt(depMaxR.value,10);
            var rMin = parseInt(retMinR.value,10);
            var rMax = parseInt(retMaxR.value,10);
            document.getElementById('bkcf-dep-min-lbl').textContent = bkcMinToTime(dMin);
            document.getElementById('bkcf-dep-max-lbl').textContent = bkcMinToTime(dMax);
            document.getElementById('bkcf-ret-min-lbl').textContent = bkcMinToTime(rMin);
            document.getElementById('bkcf-ret-max-lbl').textContent = bkcMinToTime(rMax);

            var cards = document.querySelectorAll('.bkc-combo-card');
            var visible = 0;
            cards.forEach(function(card) {
                var show = true;
                var conn = card.getAttribute('data-conn');
                if (typeVal === 'direct' && conn === '1') show = false;
                if (typeVal === 'escale' && conn === '0') show = false;
                if (show) {
                    var dep = parseInt(card.getAttribute('data-dep'),10);
                    if (dep < dMin || dep > dMax) show = false;
                }
                if (show) {
                    var ret = parseInt(card.getAttribute('data-ret'),10);
                    if (ret < rMin || ret > rMax) show = false;
                }
                card.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            var noMatch = document.getElementById('bkc-combo-no-match');
            if (noMatch) noMatch.style.display = visible === 0 ? 'block' : 'none';
        }

        radios.forEach(function(r){ r.addEventListener('change', applyFilters); });
        [depMinR, depMaxR, retMinR, retMaxR].forEach(function(el){
            if (el) el.addEventListener('input', applyFilters);
        });
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                radios.forEach(function(r){ r.checked = r.value === 'all'; });
                depMinR.value = 0; depMaxR.value = 1439;
                retMinR.value = 0; retMaxR.value = 1439;
                applyFilters();
            });
        }
    }

    function bkcPositionSidebar() {
        var sidebar = document.getElementById('bkc-filters-sidebar');
        var inner = document.querySelector('.bkc-inner');
        if (!sidebar || !inner) return;

        function pos() {
            var rect = inner.getBoundingClientRect();
            var gap = 18;
            var sidebarW = 200;
            var spaceLeft = rect.left - gap - sidebarW;
            if (spaceLeft >= 10) {
                sidebar.style.left = (rect.left - gap - sidebarW) + 'px';
                sidebar.style.display = '';
            } else {
                sidebar.style.display = 'none';
            }
        }
        pos();
        window.addEventListener('resize', pos);
    }

    function bkcSelectCombo(idx) {
        var c = bkc_combos_data[idx];
        if (!c) return;
        document.querySelectorAll('.bkc-combo-card').forEach(function(el){ el.classList.remove('selected'); });
        var card = document.getElementById('bkc-combo-' + idx);
        if (card) card.classList.add('selected');

        bkc_vol_delta_total = (c.total_delta || 0) * bkc_vol_nb_pax;
        window.bkc_selected_combo = c;

        var offerInput = document.getElementById('bkc-selected-offer-id');
        var deltaInput = document.getElementById('bkc-selected-vol-delta');
        if (offerInput) offerInput.value = c.aller.offer_id || '';
        if (deltaInput) deltaInput.value = c.total_delta || 0;

        var volRow = document.getElementById('bkc-recap-row-vol');
        var volDetail = document.getElementById('bkc-recap-vol-detail');
        if (volRow) volRow.style.display = 'flex';
        if (volDetail) {
            var airline = c.airline_name ? ' · ' + c.airline_name : '';
            var times = (c.aller.depart_time && c.aller.arrive_time) ? ' · ' + c.aller.depart_time + ' → ' + c.aller.arrive_time : '';
            volDetail.textContent = (BK.params.aeroport||'') + ' → ' + (BK.iata_dest||'') + airline + times;
        }

        var retourRow = document.getElementById('bkc-recap-row-retour');
        var retourDetail = document.getElementById('bkc-recap-retour-detail');
        if (c.retour) {
            if (retourRow) retourRow.style.display = 'flex';
            if (retourDetail) {
                var rAirline = c.retour.airline_name ? ' · ' + c.retour.airline_name : '';
                var rTimes = (c.retour.depart_time && c.retour.arrive_time) ? ' · ' + c.retour.depart_time + ' → ' + c.retour.arrive_time : '';
                var retFrom = (BK.iata_retour_depart && String(BK.iata_retour_depart).trim()) ? String(BK.iata_retour_depart).trim() : (BK.iata_dest||'');
                retourDetail.textContent = (retFrom||'') + ' → ' + (BK.params.aeroport||'') + rAirline + rTimes;
            }
        }

        var deltaLine = document.getElementById('bkc-recap-row-vol-delta');
        if (deltaLine) {
            if (bkc_vol_delta_total > 0) {
                deltaLine.style.display = 'flex';
                var deltaVal = document.getElementById('bkc-recap-vol-delta-val');
                if (deltaVal) { deltaVal.textContent = '+' + bkcFmt(bkc_vol_delta_total); deltaVal.style.color = '#b85c1a'; }
            } else {
                deltaLine.style.display = 'none';
            }
        }
    }
    window.bkcSelectCombo = bkcSelectCombo;

    /* ═══ Récap final — confirmation détaillée (comme golf) ═══ */
    function bkcBuildRecap() {
        var recap = document.getElementById('bkc-recap-final');
        if (!recap) return;
        var S = 'font-family:Outfit,sans-serif;';
        var section = 'font-weight:700;font-size:13px;text-transform:uppercase;letter-spacing:1px;color:#59b7b7;margin:18px 0 8px;padding-top:14px;border-top:1px solid #e8e4dc;' + S;
        var rowS = 'display:flex;justify-content:space-between;padding:5px 0;font-size:13px;color:#4a5568;' + S;
        var lblS = 'color:#6b7280';
        var valS = 'font-weight:600;color:#0f2424;text-align:right';

        var html = '<div style="' + S + 'font-size:13px">';

        html += '<div style="background:#f0fafa;border-radius:12px;padding:14px;margin-bottom:4px">';
        html += '<div style="font-family:Playfair Display,serif;font-weight:700;font-size:17px;color:#0f2424;margin-bottom:4px">🗺️ ' + bkcEsc(BK.titre) + '</div>';
        html += '<div style="font-size:12px;color:#4a5568;margin-top:2px">🗓️ ' + BK.devis.nb_total + ' voyageur(s) · ' + BK.nb_chambres + ' chambre(s)</div>';
        html += '</div>';

        html += '<div style="' + section + '">✈️ Vols & Dates</div>';
        var dateDep = BK.params.date_depart ? bkcFmtDate(BK.params.date_depart) : '—';
        var dateRet = BK.date_retour ? bkcFmtDate(BK.date_retour) : '—';
        html += '<div style="' + rowS + '"><span style="' + lblS + '">Date de départ</span><span style="' + valS + '">' + dateDep + '</span></div>';
        html += '<div style="' + rowS + '"><span style="' + lblS + '">Date de retour</span><span style="' + valS + '">' + dateRet + '</span></div>';

        var volDetail = document.getElementById('bkc-recap-vol-detail');
        if (volDetail && volDetail.textContent.trim() !== '—') {
            html += '<div style="' + rowS + '"><span style="' + lblS + '">Vol aller (' + bkcEsc(BK.params.aeroport) + ' → ' + bkcEsc(BK.iata_dest) + ')</span><span style="' + valS + '">' + volDetail.textContent.trim() + '</span></div>';
        }
        var retourDetail = document.getElementById('bkc-recap-retour-detail');
        if (retourDetail && retourDetail.textContent.trim() !== '—') {
            var retLab = (BK.iata_retour_depart && String(BK.iata_retour_depart).trim()) ? String(BK.iata_retour_depart).trim() : (BK.iata_dest||'');
            html += '<div style="' + rowS + '"><span style="' + lblS + '">Vol retour (' + bkcEsc(retLab) + ' → ' + bkcEsc(BK.params.aeroport) + ')</span><span style="' + valS + '">' + retourDetail.textContent.trim() + '</span></div>';
        }

        html += '<div style="' + section + '">👥 Voyageurs</div>';
        document.querySelectorAll('.bkc-voyageur').forEach(function(row) {
            var prenom = row.querySelector('input[name*="[prenom]"]');
            var nom = row.querySelector('input[name*="[nom]"]');
            var ddn = row.querySelector('input[name*="[ddn]"]');
            var passeport = row.querySelector('input[name*="[passeport]"]');
            var nationalite = row.querySelector('input[name*="[nationalite]"]');
            var chambre = row.querySelector('input[name*="[chambre]"]');
            if (!prenom || !prenom.value) return;
            html += '<div style="background:#fff;border:1px solid #e8e4dc;border-radius:10px;padding:10px 12px;margin-bottom:6px">';
            html += '<div style="font-weight:700;color:#0f2424;font-size:14px;margin-bottom:4px">' + bkcEsc(prenom.value) + ' ' + bkcEsc(nom.value.toUpperCase());
            if (chambre) html += ' <span style="background:#edf8f8;color:#3d9a9a;padding:1px 6px;border-radius:6px;font-size:10px;margin-left:6px">Ch.' + chambre.value + '</span>';
            html += '</div>';
            var details = [];
            if (ddn && ddn.value) details.push('Né(e) le ' + bkcFmtDate(ddn.value));
            if (nationalite && nationalite.value) details.push(bkcEsc(nationalite.value));
            if (passeport && passeport.value) details.push('Passeport : ' + bkcEsc(passeport.value));
            if (details.length) html += '<div style="font-size:11px;color:#6b7280">' + details.join(' · ') + '</div>';
            html += '</div>';
        });

        html += '<div style="' + section + '">📋 Facturation</div>';
        var fP = document.getElementById('fact-prenom'), fN = document.getElementById('fact-nom');
        var fE = document.getElementById('fact-email'), fT = document.getElementById('fact-tel');
        var fA = document.getElementById('fact-adresse'), fCP = document.getElementById('fact-cp'), fV = document.getElementById('fact-ville');
        if (fP && fN) html += '<div style="color:#0f2424;font-weight:600">' + bkcEsc(fP.value) + ' ' + bkcEsc(fN.value) + '</div>';
        if (fE) html += '<div style="color:#4a5568">' + bkcEsc(fE.value) + '</div>';
        if (fT) html += '<div style="color:#4a5568">' + bkcEsc(fT.value) + '</div>';
        if (fA && fA.value) html += '<div style="color:#4a5568">' + bkcEsc(fA.value) + (fCP && fCP.value ? ', ' + bkcEsc(fCP.value) : '') + (fV && fV.value ? ' ' + bkcEsc(fV.value) : '') + '</div>';

        if (bkc_insurance_check && BK.insurance_total > 0) {
            html += '<div style="' + rowS + '"><span style="' + lblS + '">🛡️ Assurance Multirisques</span><span style="' + valS + ';color:#e3147a">+' + bkcFmt(BK.insurance_total) + '</span></div>';
        }

        var total = Math.ceil((parseFloat(BK.devis.total) || 0) + (bkc_insurance_check ? (parseFloat(BK.insurance_total) || 0) : 0));
        html += '<div style="margin-top:18px;padding-top:14px;border-top:2.5px solid #3d9a9a;display:flex;justify-content:space-between;align-items:center">';
        html += '<span style="font-size:15px;font-weight:700;color:#0f2424">Total circuit</span>';
        html += '<span style="font-family:Playfair Display,serif;font-size:28px;font-weight:700;color:#3d9a9a">' + bkcFmt(total) + '</span>';
        html += '</div>';

        if (!BK.payer_tout) {
            var acomptePct = parseFloat(BK.acompte_pct) || 30;
            var acompte = Math.ceil(total * acomptePct / 100);
            html += '<div style="background:#e8f8f0;border-radius:10px;padding:10px;margin-top:8px;text-align:center">';
            html += '<div style="font-weight:700;font-size:17px;color:#2d8a5a">' + bkcFmt(acompte) + '</div>';
            html += '<div style="font-size:11px;color:#6b7280">Acompte ' + acomptePct + '% à payer maintenant · Solde ' + BK.delai_solde + ' j. avant départ</div>';
            html += '</div>';
        } else {
            html += '<div style="background:#fff3e0;border-radius:10px;padding:10px;margin-top:8px;text-align:center">';
            html += '<div style="font-weight:700;font-size:14px;color:#b85c1a">Paiement intégral requis</div>';
            html += '<div style="font-size:11px;color:#6b7280">Départ dans moins de ' + BK.delai_solde + ' jours</div>';
            html += '</div>';
        }

        html += '</div>';
        recap.innerHTML = html;
    }

    function bkcFmtDate(dateStr) {
        if (!dateStr) return '—';
        var parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        var mois = ['janv.','févr.','mars','avr.','mai','juin','juil.','août','sept.','oct.','nov.','déc.'];
        return parseInt(parts[2]) + ' ' + mois[parseInt(parts[1]) - 1] + ' ' + parts[0];
    }

    /* ═══ Navigation étapes : step 1 (Vols) → step 2 (Bulletin voyageurs+facturation) → confirmation ═══ */
    window.bkcGoToStep2 = function() {
        document.getElementById('bkc-step-1').classList.remove('bkc-step-active');
        document.getElementById('bkc-step-2').classList.add('bkc-step-active');
        window.scrollTo({top:0,behavior:'smooth'});
    };

    window.bkcGoBackToStep1 = function() {
        document.getElementById('bkc-step-2').classList.remove('bkc-step-active');
        document.getElementById('bkc-step-1').classList.add('bkc-step-active');
        window.scrollTo({top:0,behavior:'smooth'});
    };

    window.bkcGoToConfirm = function() {
        var errEl = document.getElementById('bkc-error');
        var missing = false;
        document.querySelectorAll('#bkc-step-2 .bkc-required').forEach(function(el){ if(!el.value.trim()){el.style.borderColor='#dc2626';missing=true;}else{el.style.borderColor='';} });
        if (missing) { errEl.textContent='Veuillez remplir tous les champs obligatoires (*).'; errEl.style.display='block'; window.scrollTo({top:errEl.offsetTop-120,behavior:'smooth'}); return; }
        errEl.style.display='none';
        bkcBuildRecap();
        document.getElementById('bkc-steps-form').style.display = 'none';
        document.getElementById('bkc-step-confirm').style.display = 'block';
        window.scrollTo({top:0,behavior:'smooth'});
    };

    window.bkcGoBack = function() {
        document.getElementById('bkc-step-confirm').style.display = 'none';
        document.getElementById('bkc-steps-form').style.display = 'block';
        document.getElementById('bkc-step-1').classList.remove('bkc-step-active');
        document.getElementById('bkc-step-2').classList.add('bkc-step-active');
        window.scrollTo({top:0,behavior:'smooth'});
    };

    /* ── Submit ── */
    window.bkcSubmit = function() {
        var errEl = document.getElementById('bkc-error');
        if (!document.getElementById('bkc-confirm-info').checked) { alert("Veuillez certifier l'exactitude des informations voyageurs."); return; }
        if (!document.getElementById('bkc-cgu').checked) { alert('Veuillez accepter les conditions générales de vente et la politique de confidentialité.'); return; }
        if (submitting) return;
        submitting = true;

        var btn = document.getElementById('bkc-btn-submit');
        var load = document.getElementById('bkc-loading');
        btn.disabled=true; btn.textContent='⏳ Traitement en cours…'; load.style.display='block'; errEl.style.display='none';

        var data = {
            action:'vs08c_booking_submit', nonce:BK.nonce, circuit_id:BK.circuit_id,
            date_depart:BK.params.date_depart, aeroport:BK.params.aeroport,
            nb_adultes:BK.params.nb_adultes, nb_enfants:0,
            nb_chambres:BK.params.nb_chambres, prix_vol:BK.params.prix_vol, rooms:BK.params.rooms||'',
            selected_offer_id: (document.getElementById('bkc-selected-offer-id')||{}).value||'',
            vol_delta_pax: (document.getElementById('bkc-selected-vol-delta')||{}).value||'0',
            vol_aller_depart: (window.bkc_selected_combo&&window.bkc_selected_combo.aller) ? window.bkc_selected_combo.aller.depart_time : '',
            vol_aller_arrivee: (window.bkc_selected_combo&&window.bkc_selected_combo.aller) ? window.bkc_selected_combo.aller.arrive_time : '',
            vol_aller_num: (window.bkc_selected_combo&&window.bkc_selected_combo.aller) ? window.bkc_selected_combo.aller.flight_number : '',
            vol_aller_cie: (window.bkc_selected_combo&&window.bkc_selected_combo.aller) ? window.bkc_selected_combo.aller.airline_name : '',
            vol_retour_depart: (window.bkc_selected_combo&&window.bkc_selected_combo.retour) ? window.bkc_selected_combo.retour.depart_time : '',
            vol_retour_arrivee: (window.bkc_selected_combo&&window.bkc_selected_combo.retour) ? window.bkc_selected_combo.retour.arrive_time : '',
            vol_retour_num: (window.bkc_selected_combo&&window.bkc_selected_combo.retour) ? window.bkc_selected_combo.retour.flight_number : '',
            fact_prenom:(document.getElementById('fact-prenom')||{}).value||'',
            fact_nom:(document.getElementById('fact-nom')||{}).value||'',
            fact_email:(document.getElementById('fact-email')||{}).value||'',
            fact_tel:(document.getElementById('fact-tel')||{}).value||'',
            fact_adresse:(document.getElementById('fact-adresse')||{}).value||'',
            fact_cp:(document.getElementById('fact-cp')||{}).value||'',
            fact_ville:(document.getElementById('fact-ville')||{}).value||'',
            assurance: bkc_insurance_check ? '1' : '0'
        };
        document.querySelectorAll('.bkc-voyageur').forEach(function(row){ row.querySelectorAll('input').forEach(function(input){ if(input.name) data[input.name]=input.value; }); });
        document.querySelectorAll('input[name^="options["]').forEach(function(inp){ var n=inp.getAttribute('name'); var m=n&&n.match(/options\[([^\]]+)\]/); if(m) data['options['+m[1]+']']=inp.value; });

        function done(res){if(res&&res.success&&res.data&&res.data.redirect){window.location.href=res.data.redirect;}else{errEl.textContent=(res&&res.data&&typeof res.data==='string')?res.data:'Erreur. Contactez-nous au 03 26 65 28 63.';errEl.style.display='block';submitting=false;btn.disabled=false;btn.textContent='🔒 Procéder au paiement →';load.style.display='none';}}
        function fail(){errEl.textContent='Erreur réseau. Vérifiez votre connexion.';errEl.style.display='block';submitting=false;btn.disabled=false;btn.textContent='🔒 Procéder au paiement →';load.style.display='none';}
        jQuery.post(BK.ajax_url, data).done(done).fail(fail);
    };
})();
</script>
<?php get_footer(); ?>
