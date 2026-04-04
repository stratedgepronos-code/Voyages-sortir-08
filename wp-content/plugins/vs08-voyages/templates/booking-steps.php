<?php
/**
 * Tunnel de réservation multi-étapes
 * Accessible via /reservation/{voyage_id}/
 */
if (!defined('ABSPATH')) exit;

if (!function_exists('vs08_iata_city')) {
    function vs08_iata_city($code) {
        static $map = [
            'CDG'=>'Paris CDG','ORY'=>'Paris Orly','BVA'=>'Paris Beauvais',
            'LYS'=>'Lyon','MRS'=>'Marseille','NCE'=>'Nice','TLS'=>'Toulouse',
            'BOD'=>'Bordeaux','NTE'=>'Nantes','LIL'=>'Lille','SXB'=>'Strasbourg',
            'RNS'=>'Rennes','BES'=>'Brest','MPL'=>'Montpellier','RHE'=>'Reims',
            'LHR'=>'Londres Heathrow','LGW'=>'Londres Gatwick','STN'=>'Londres Stansted',
            'AMS'=>'Amsterdam','BRU'=>'Bruxelles','GVA'=>'Genève','ZRH'=>'Zurich',
            'FCO'=>'Rome','MXP'=>'Milan','BCN'=>'Barcelone','MAD'=>'Madrid',
            'LIS'=>'Lisbonne','ATH'=>'Athènes','IST'=>'Istanbul','DXB'=>'Dubaï',
            'CMN'=>'Casablanca','RAK'=>'Marrakech','AGA'=>'Agadir','FEZ'=>'Fès',
            'TNG'=>'Tanger','OUD'=>'Oujda',
            'TUN'=>'Tunis','MIR'=>'Monastir','DJE'=>'Djerba',
            'ALG'=>'Alger','ORN'=>'Oran','CZL'=>'Constantine',
            'CAI'=>'Le Caire','HRG'=>'Hurghada','SSH'=>'Sharm el-Sheikh','LXR'=>'Louxor',
            'RBA'=>'Rabat','NKC'=>'Nouakchott',
            'JFK'=>'New York JFK','MIA'=>'Miami','LAX'=>'Los Angeles','YUL'=>'Montréal',
            'CUN'=>'Cancún','MEX'=>'Mexico City','BOG'=>'Bogota','GRU'=>'São Paulo',
            'NBO'=>'Nairobi','ADD'=>'Addis-Abeba','DAR'=>'Dar es Salaam','JNB'=>'Johannesburg',
            'CMB'=>'Colombo','BKK'=>'Bangkok','SIN'=>'Singapour','KUL'=>'Kuala Lumpur',
            'HKG'=>'Hong Kong','PEK'=>'Pékin','PVG'=>'Shanghai','NRT'=>'Tokyo',
            'SYD'=>'Sydney','AKL'=>'Auckland',
            'LCA'=>'Larnaca','PFO'=>'Paphos','HER'=>'Héraklion (Crète)','SKG'=>'Thessalonique',
            'OPO'=>'Porto','AGP'=>'Málaga','PMI'=>'Palma de Majorque',
            'VCE'=>'Venise','NAP'=>'Naples','BUD'=>'Budapest','PRG'=>'Prague',
            'WAW'=>'Varsovie','VIE'=>'Vienne','ZAG'=>'Zagreb','BEG'=>'Belgrade',
            'SVO'=>'Moscou','LED'=>'Saint-Pétersbourg',
            'BEY'=>'Beyrouth','AMM'=>'Amman','TLV'=>'Tel Aviv','DOH'=>'Doha','AUH'=>'Abu Dhabi',
            'MLE'=>'Malé (Maldives)','SEZ'=>'Mahé (Seychelles)',
            'RUN'=>'La Réunion','TNR'=>'Antananarivo','MRU'=>'Île Maurice',
        ];
        $code = strtoupper(trim($code));
        return $map[$code] ?? $code;
    }
}

$voyage_id = intval(get_query_var('vs08_voyage_id'));
if (!$voyage_id) { wp_redirect(home_url()); exit; }

$m       = VS08V_MetaBoxes::get($voyage_id);
$titre   = get_the_title($voyage_id);
$duree   = intval($m['duree'] ?? 7);
$flag_display = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::resolve_flag($m) : trim((string) ($m['flag'] ?? ''));

// Récupérer les paramètres depuis l'URL
$params = [
    'date_depart'    => sanitize_text_field($_GET['date'] ?? ''),
    'aeroport'       => strtoupper(sanitize_text_field($_GET['aeroport'] ?? '')),
    'nb_golfeurs'    => intval($_GET['ngolf'] ?? 1),
    'nb_nongolfeurs' => intval($_GET['nngolf'] ?? 0),
    'type_chambre'   => sanitize_text_field($_GET['chambre'] ?? 'double'),
    'nb_chambres'    => intval($_GET['nchamb'] ?? 1),
    'prix_vol'       => floatval($_GET['vol'] ?? 0),
    'airline_iata'   => strtoupper(sanitize_text_field($_GET['airline'] ?? '')),
];
$nb_total = $params['nb_golfeurs'] + $params['nb_nongolfeurs'];
$nb_chambres = max(1, $params['nb_chambres']);

// Calcul du devis
$devis = VS08V_Calculator::calculate($voyage_id, $params);
$insurance_price = VS08V_Insurance::get_price($devis['par_pers']);
$bk_saved_fact = (is_user_logged_in() && class_exists('VS08V_Traveler_Space')) ? VS08V_Traveler_Space::get_saved_facturation() : [];
$bk_saved_voyageurs = (is_user_logged_in() && class_exists('VS08V_Traveler_Space')) ? VS08V_Traveler_Space::get_saved_voyageurs() : [];

// URL de retour après connexion/inscription (conserve tous les paramètres + step=2)
$bk_redirect_back = home_url('/reservation/' . $voyage_id . '/?'
    . 'date='    . urlencode($params['date_depart'])
    . '&aeroport=' . urlencode($params['aeroport'])
    . '&ngolf='  . intval($params['nb_golfeurs'])
    . '&nngolf=' . intval($params['nb_nongolfeurs'])
    . '&chambre=' . urlencode($params['type_chambre'])
    . '&nchamb=' . intval($params['nb_chambres'])
    . '&vol='    . floatval($params['prix_vol'])
    . '&airline=' . urlencode($params['airline_iata'])
    . '&step=2');

get_header();
?>
<style>
.bk-wrap{background:#f9f6f0;min-height:100vh;padding:140px 0 80px;overflow-x:hidden}
.bk-inner{max-width:1200px;margin:0 auto;padding:0 60px;display:grid;grid-template-columns:1fr 360px;gap:32px;align-items:start}
.bk-main{}
/* STEPPER */
/* ── Stepper Map Pins ── */
.bk-stepper{position:relative;background:linear-gradient(170deg,#faf8f5 0%,#f0ece4 100%);border-radius:18px;padding:28px 20px 22px;border:1.5px solid #e2ddd3;overflow:hidden;margin-bottom:36px}
.bk-stepper::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23d4cfc5' fill-opacity='0.12'%3E%3Cpath d='M0 0h1v1H0zM20 20h1v1h-1z'/%3E%3C/g%3E%3C/svg%3E");pointer-events:none}
.bk-stepper-track{display:flex;align-items:flex-end;position:relative;z-index:2}
.bk-step{display:flex;flex-direction:column;align-items:center;gap:0;flex-shrink:0;cursor:pointer;transition:all .4s}
.bk-step-pin{position:relative;width:40px;height:52px;display:flex;align-items:flex-start;justify-content:center;transition:all .4s}
.bk-step-pin-body{width:36px;height:36px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);display:flex;align-items:center;justify-content:center;background:#d4cfc5;border:2px solid #c4bfb5;transition:all .4s;box-shadow:0 3px 8px rgba(0,0,0,.08)}
.bk-step-pin-icon{transform:rotate(45deg);font-size:14px;transition:all .3s}
.bk-step-pin::after{content:'';position:absolute;bottom:0;left:50%;transform:translateX(-50%);width:8px;height:4px;border-radius:50%;background:rgba(0,0,0,.12);transition:all .3s}
.bk-step-label{font-size:11px;font-weight:700;color:#a09a8e;margin-top:6px;text-transform:uppercase;letter-spacing:.6px;transition:all .3s;white-space:nowrap}
.bk-step-hint{font-size:9px;color:#c4bfb5;transition:all .3s;font-style:italic}
.bk-step-line{flex:1;height:0;border-top:2.5px dashed #d4cfc5;margin:0 4px;transform:translateY(-26px);transition:all .4s;position:relative}
/* Active */
.bk-step.active .bk-step-pin-body{background:#d4cfc5;border-color:#59b7b7;border-width:3px;box-shadow:0 6px 20px rgba(89,183,183,.25);transform:rotate(-45deg) scale(1.15)}
.bk-step.active .bk-step-pin::after{width:12px;height:5px;background:rgba(89,183,183,.2)}
.bk-step.active .bk-step-label{color:#0f2424}
.bk-step.active .bk-step-hint{color:#59b7b7}
/* Done */
.bk-step.done .bk-step-pin-body{background:#59b7b7;border-color:#59b7b7;box-shadow:0 3px 10px rgba(89,183,183,.2)}
.bk-step.done .bk-step-label{color:#59b7b7}
.bk-step-line.done{border-color:#59b7b7;border-style:solid}
@media(max-width:480px){.bk-step-pin-body{width:30px;height:30px}.bk-step-pin{width:34px;height:44px}.bk-step-label{font-size:9px}.bk-step-pin-icon{font-size:12px}.bk-step-line{transform:translateY(-22px)}}
@media(max-width:768px){
.bk-stepper{padding:20px 12px 16px;overflow-x:auto;-webkit-overflow-scrolling:touch}
.bk-stepper-track{min-width:0}
.bk-step{flex-shrink:1;min-width:0}
.bk-step-label{font-size:9px;letter-spacing:0}
.bk-step-hint{font-size:8px}
.bk-route-header{padding:10px 14px;gap:8px;flex-wrap:nowrap;overflow:hidden}
.bk-route-iata{font-size:20px}
.bk-route-city{font-size:9px}
.bk-route-arrow{font-size:16px}
.bk-route-dates{font-size:10px;padding:2px 8px}
}

/* ── Step 1 : Sélection vol ── */
.bk-flights-loading{display:flex;align-items:center;gap:12px;padding:20px;color:#6b7280;font-family:'Outfit',sans-serif;font-size:15px}
.bk-flights-spinner{width:20px;height:20px;border:3px solid #e5e7eb;border-top-color:#59b7b7;border-radius:50%;animation:bk-spin .7s linear infinite;flex-shrink:0}
@keyframes bk-spin{to{transform:rotate(360deg)}}
.bk-flights-error{padding:14px 16px;background:#fee2e2;color:#dc2626;border-radius:10px;font-size:15px;font-family:'Outfit',sans-serif}
/* ── COMBO VOL ALLER+RETOUR ── */
.bk-route-header{display:flex;align-items:center;justify-content:center;gap:14px;background:#0f2424;border-radius:14px;padding:14px 20px;margin-bottom:20px}
.bk-route-iata{font-family:'Playfair Display',serif;font-size:28px;font-weight:700;color:#fff;letter-spacing:1px;text-transform:uppercase}
.bk-route-city{font-size:11px;color:rgba(255,255,255,.85);letter-spacing:.5px;margin-top:2px;text-align:center;font-family:'Outfit',sans-serif;font-weight:500}
.bk-route-arrow{color:#59b7b7;font-size:22px}
.bk-route-dates{font-size:12px;color:rgba(255,255,255,.9);text-align:center;margin-top:4px;font-family:'Outfit',sans-serif;font-weight:600;background:rgba(89,183,183,.18);padding:3px 10px;border-radius:20px;white-space:nowrap}
.combo-card{border:2px solid #e5e7eb;border-radius:16px;overflow:hidden;cursor:pointer;transition:border-color .2s,box-shadow .2s,transform .15s;background:#fff;position:relative;margin-bottom:10px}
.combo-card:hover{border-color:#b7dfdf;box-shadow:0 4px 20px rgba(61,154,154,.1);transform:translateY(-1px)}
.combo-card.selected{border-color:#3d9a9a;box-shadow:0 0 0 4px rgba(61,154,154,.12)}
.combo-card.selected .combo-header{background:#edf8f8}
.combo-header{display:flex;align-items:center;justify-content:space-between;padding:12px 16px 10px;border-bottom:1px solid #e5e7eb;transition:background .2s}
.combo-airline{display:flex;align-items:center;gap:10px}
.combo-airline img{width:30px;height:30px;border-radius:7px;border:1px solid #e5e7eb;background:#fafafa;object-fit:contain}
.combo-airline-name{font-size:15px;font-weight:700;color:#0f2424}
.combo-airline-sub{font-size:13px;color:#9ca3af;margin-top:1px}
.combo-price-delta{font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:#3d9a9a;text-align:right}
.combo-price-delta.ref{color:#2d8a5a;font-size:14px;font-weight:700;font-family:'Outfit',sans-serif;background:#e8f8f0;padding:4px 10px;border-radius:20px}
.combo-price-sub{font-size:13px;color:#9ca3af;text-align:right;margin-top:1px}
.combo-leg{display:flex;align-items:center;padding:11px 16px;flex-wrap:wrap;gap:4px 0}
.combo-leg:first-child{border-bottom:1px dashed #e5e7eb}
.combo-leg-meta{display:flex;align-items:center;gap:8px;margin-left:auto;flex-shrink:0}
.combo-leg-badge{display:inline-flex;align-items:center;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:1px;border-radius:20px;padding:3px 7px;white-space:nowrap;width:62px;justify-content:center}
.combo-leg-badge.aller{color:#3d9a9a;border:1px solid #b7dfdf;background:#edf8f8}
.combo-leg-badge.retour{color:#c9972d;border:1px solid #f0d9a8;background:#fdf6e9}
.combo-leg-times{display:flex;align-items:center;gap:8px;flex:1;margin:0 14px}
.combo-time{font-size:20px;font-weight:800;color:#0f2424;font-family:'Playfair Display',serif;letter-spacing:-.5px;white-space:nowrap}
.combo-leg-line{flex:1;display:flex;align-items:center;gap:4px}
.combo-leg-dash{flex:1;height:1px;background:#e5e7eb}
.combo-leg-plane{font-size:14px;color:#59b7b7}
.combo-leg-dur{font-size:13px;color:#9ca3af;white-space:nowrap}
.combo-leg-num{font-size:13px;color:#9ca3af;white-space:nowrap}
.combo-check{width:22px;height:22px;background:#3d9a9a;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;opacity:0;transform:scale(.5);transition:opacity .2s,transform .2s;flex-shrink:0;margin-left:10px}
.combo-card.selected .combo-check{opacity:1;transform:scale(1)}
.combo-more{text-align:center;padding:11px;font-size:14px;font-weight:600;color:#59b7b7;cursor:pointer;border-top:1px solid #e5e7eb;transition:color .15s;background:#fafafa}
.combo-more:hover{color:#0f2424}
.bk-flights-hidden{display:none}
.bk-show-more{display:block;width:100%;padding:12px;background:#f9f6f0;border:1.5px solid #e5e7eb;border-radius:12px;font-size:14px;font-weight:600;color:#59b7b7;cursor:pointer;font-family:'Outfit',sans-serif;text-align:center;margin-top:8px;transition:all .2s}
.bk-show-more:hover{background:#edf8f8;border-color:#59b7b7}
.bk-filters-sidebar{position:fixed;top:160px;width:200px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;font-family:'Outfit',sans-serif;box-shadow:0 2px 12px rgba(0,0,0,.06);z-index:50;transition:opacity .3s}
.bkf-title{font-size:16px;font-weight:700;color:#0f2424;margin-bottom:14px}
.bkf-section{margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid #e5e7eb}
.bkf-section:last-of-type{border-bottom:none;margin-bottom:8px;padding-bottom:0}
.bkf-label{font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px}
.bkf-check{display:flex;align-items:center;gap:7px;font-size:13px;color:#4b5563;cursor:pointer;padding:4px 0;transition:color .15s}
.bkf-check:hover{color:#0f2424}
.bkf-check input[type=radio]{accent-color:#3d9a9a;margin:0}
.bkf-n{font-size:11px;background:#e5e7eb;color:#6b7280;border-radius:8px;padding:1px 6px;font-weight:700;margin-left:auto}
.bkf-reset{width:100%;padding:7px;border:1.5px solid #e5e7eb;border-radius:10px;background:#fff;color:#6b7280;font-size:13px;font-weight:600;cursor:pointer;transition:all .15s;font-family:'Outfit',sans-serif}
.bkf-reset:hover{border-color:#3d9a9a;color:#3d9a9a}
.bk-combo-loading{display:flex;align-items:center;gap:10px;font-size:15px;color:#9ca3af;font-family:'Outfit',sans-serif;padding:20px 0}
/* ── Badge escale / direct ── */
.combo-conn-badge{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:700;letter-spacing:.3px;border-radius:20px;padding:2px 9px;white-space:nowrap;flex-shrink:0}
.combo-conn-badge.direct{color:#2d8a5a;background:#e8f8f0;border:1px solid #b7e4cc}
.combo-conn-badge.escale{color:#b85c1a;background:#fff4e6;border:1px solid #f0d9a8}
/* ── Détail escale dépliable ── */
.combo-conn-detail{display:none;padding:6px 16px 10px 80px;font-size:12px;color:#6b7280;line-height:1.6;font-family:'Outfit',sans-serif;border-top:1px dashed #f0f0f0}
.combo-conn-detail.open{display:block}
.combo-conn-toggle{cursor:pointer;user-select:none;display:inline-flex;align-items:center;gap:4px;font-size:12px;color:#59b7b7;padding:2px 16px 2px 80px}
.combo-conn-toggle:hover{color:#0f2424}
.combo-conn-toggle .chevron{display:inline-block;transition:transform .2s;font-size:9px}
.combo-conn-toggle.open .chevron{transform:rotate(180deg)}
.combo-conn-step{display:flex;align-items:center;gap:6px;padding:3px 0;font-size:12.5px}
.combo-conn-step .dot{width:6px;height:6px;border-radius:50%;background:#59b7b7;flex-shrink:0}
.combo-conn-step .dot.layover{background:#f0a030}
.combo-conn-step.layover-row{color:#b85c1a;font-style:italic;font-size:11.5px}
.combo-conn-step .seg-flight{color:#9ca3af;font-size:11px;margin-left:4px}
/* CARD */
.bk-card{background:#fff;border-radius:20px;padding:36px;box-shadow:0 4px 24px rgba(0,0,0,.07);margin-bottom:20px}
.bk-card-title{font-size:24px;font-weight:700;color:#0f2424;font-family:'Playfair Display',serif;margin-bottom:6px}
.bk-card-sub{font-size:15px;color:#6b7280;font-family:'Outfit',sans-serif;margin-bottom:26px}
/* VOYAGEURS */
.bk-chambre-block{border:1.5px solid #e5e7eb;border-radius:16px;padding:20px;margin-bottom:16px}
.bk-chambre-title{font-size:15px;font-weight:700;color:#59b7b7;text-transform:uppercase;letter-spacing:1px;font-family:'Outfit',sans-serif;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.bk-voyageur-row{border-bottom:1px solid #f0f2f4;padding-bottom:16px;margin-bottom:16px}
.bk-voyageur-row:last-child{border-bottom:none;margin-bottom:0;padding-bottom:0}
.bk-voyageur-label{font-size:13px;font-weight:700;color:#1a3a3a;text-transform:uppercase;letter-spacing:1px;font-family:'Outfit',sans-serif;margin-bottom:10px;display:flex;align-items:center;gap:8px}
.bk-type-badge{padding:2px 8px;border-radius:100px;font-size:13px;font-weight:700}
.bk-type-golfeur{background:#edf8f8;color:#3d9a9a}
.bk-type-nongolfeur{background:#fff3e8;color:#b85c1a}
.bk-voyageur-fill-row{display:flex;align-items:center;gap:10px;margin-bottom:12px;flex-wrap:wrap}
.bk-voyageur-fill-row label{font-size:13px;font-weight:600;color:#4a5568}
.bk-voyageur-fill-row select{max-width:260px;border:1.5px solid #e5e7eb;border-radius:10px;padding:8px 12px;font-size:14px;font-family:'Outfit',sans-serif}
/* FIELDS */
.bk-field-row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:10px}
.bk-field-row.cols-4{grid-template-columns:1fr 1fr 1fr 1fr}
.bk-field-row.cols-2{grid-template-columns:1fr 1fr}
.bk-field label{display:block;font-size:13px;font-weight:700;color:#1a3a3a;text-transform:uppercase;letter-spacing:1px;margin-bottom:5px;font-family:'Outfit',sans-serif}
.bk-field input,.bk-field select{width:100%;border:1.5px solid #e5e7eb;border-radius:10px;padding:10px 12px;font-size:15px;font-family:'Outfit',sans-serif;color:#0f2424;background:#fafafa;outline:none;transition:border-color .2s;-webkit-appearance:none}
.bk-field input:focus,.bk-field select:focus{border-color:#59b7b7}
.bk-field input.required-error{border-color:#dc2626}
.bk-optional{font-size:13px;color:#9ca3af;font-style:italic;margin-left:4px}
/* FACTURATION */
.bk-fact-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.bk-fact-grid .bk-field.full{grid-column:span 2}
/* OPTIONS */
.bk-option-row{display:flex;align-items:flex-start;gap:14px;padding:16px;border:1.5px solid #e5e7eb;border-radius:14px;margin-bottom:10px;cursor:pointer;transition:all .2s}
.bk-option-row:hover,.bk-option-row.checked{border-color:#59b7b7;background:#edf8f8}
.bk-option-icon{font-size:26px;flex-shrink:0}
.bk-option-right{display:flex;align-items:center;gap:8px;flex-shrink:0;margin-left:auto}
.bk-opt-qty-badge{background:#0f2424;color:#fff;font-size:13px;font-weight:700;font-family:'Outfit',sans-serif;border-radius:20px;padding:3px 10px;min-width:24px;text-align:center}
.bk-opt-modifier-btn{background:none;border:1.5px solid #59b7b7;color:#3d9a9a;font-size:12px;font-weight:600;font-family:'Outfit',sans-serif;border-radius:20px;padding:3px 12px;cursor:pointer;transition:all .15s}
.bk-opt-modifier-btn:hover{background:#edf8f8}
.bk-opt-modifier-btn.active{background:#edf8f8;border-color:#3d9a9a}
.bk-opt-checkmark{color:#2d8a5a;font-size:18px;font-weight:700}
.bk-option-body{flex:1}
.bk-option-name{font-size:16px;font-weight:700;color:#0f2424;font-family:'Outfit',sans-serif}
.bk-option-desc{font-size:14px;color:#6b7280;font-family:'Outfit',sans-serif;margin-top:2px}
.bk-option-price{font-size:15px;font-weight:700;color:#3d9a9a;font-family:'Outfit',sans-serif;margin-top:4px}
.bk-option-qty{display:flex;align-items:center;gap:8px;margin-top:8px;display:none}
.bk-option-qty button{width:28px;height:28px;border:1.5px solid #e5e7eb;background:#fff;border-radius:6px;font-size:16px;font-weight:700;cursor:pointer;color:#0f2424}
.bk-option-qty span{font-size:16px;font-weight:700;font-family:'Outfit',sans-serif;min-width:24px;text-align:center}
/* ── Assurance Voyage (Assurever Galaxy) ── */
/* ── Insurance block ── */
.bk-ins-wrap{background:linear-gradient(135deg,#f0f9fa 0%,#fdf2f8 100%);border:2px solid #59b7b7;border-radius:18px;padding:0;overflow:hidden;margin-bottom:16px;position:relative}
.bk-ins-header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px 12px;border-bottom:1px solid rgba(89,183,183,.18)}
.bk-ins-logo{height:32px;width:auto}
.bk-ins-badge{font-family:'Outfit',sans-serif;font-size:11px;font-weight:700;background:linear-gradient(135deg,#e3147a,#c30d66);color:#fff;padding:4px 12px;border-radius:20px;letter-spacing:.5px}
.bk-ins-body{padding:14px 20px 16px}
.bk-ins-hook{font-family:'Playfair Display',serif;font-size:16px;font-weight:700;color:#0f2424;line-height:1.35;margin:0 0 6px}
.bk-ins-sub{font-family:'Outfit',sans-serif;font-size:12.5px;color:#4b5563;line-height:1.5;margin:0 0 14px}
.bk-ins-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px}
.bk-ins-card{display:flex;align-items:flex-start;gap:8px;background:rgba(255,255,255,.75);border:1px solid rgba(89,183,183,.2);border-radius:10px;padding:9px 11px}
.bk-ins-card-icon{font-size:18px;flex-shrink:0;line-height:1}
.bk-ins-card-label{font-family:'Outfit',sans-serif;font-size:11.5px;font-weight:600;color:#1f2937;line-height:1.3}
.bk-ins-card-val{font-family:'Outfit',sans-serif;font-size:10.5px;color:#6b7280;line-height:1.3;margin-top:1px}
.bk-ins-docs{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px}
.bk-ins-doc{font-family:'Outfit',sans-serif;font-size:11px;color:#0083a3;text-decoration:none;display:flex;align-items:center;gap:4px;padding:5px 10px;background:rgba(255,255,255,.8);border:1px solid rgba(0,131,163,.2);border-radius:8px;transition:all .2s}
.bk-ins-doc:hover{background:#e3147a;color:#fff;border-color:#e3147a}
.bk-ins-doc:hover svg{fill:#fff}
.bk-ins-footer{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;background:rgba(255,255,255,.6);border-top:1px solid rgba(89,183,183,.15)}
.bk-ins-check-label{display:flex;align-items:center;gap:12px;cursor:pointer;flex:1}
.bk-ins-check-label input[type=checkbox]{width:20px;height:20px;accent-color:#e3147a;flex-shrink:0}
.bk-ins-check-text{font-family:'Outfit',sans-serif;font-size:13.5px;font-weight:700;color:#0f2424}
.bk-ins-check-text small{font-weight:400;color:#6b7280;font-size:12px}
.bk-ins-price{font-family:'Outfit',sans-serif;text-align:right}
.bk-ins-price-main{font-size:18px;font-weight:800;color:#e3147a}
.bk-ins-price-detail{font-size:11px;color:#6b7280;margin-top:1px}
@media(max-width:480px){.bk-ins-grid{grid-template-columns:1fr}.bk-ins-footer{flex-direction:column;gap:12px;align-items:stretch}.bk-ins-price{text-align:left}}
/* CGV */
.bk-cgu{display:flex;align-items:flex-start;gap:10px;cursor:pointer;margin:16px 0}
.bk-cgu input{width:18px;height:18px;accent-color:#59b7b7;flex-shrink:0;margin-top:2px;cursor:pointer}
.bk-cgu-text{font-size:15px;color:#4a5568;font-family:'Outfit',sans-serif;line-height:1.6}
.bk-cgu-text a{color:#59b7b7}
/* NAV */
.bk-nav{display:flex;justify-content:space-between;align-items:center;margin-top:24px;padding-top:20px;border-top:1px solid #f0f2f4}
.bk-btn-prev{background:none;border:1.5px solid #e5e7eb;color:#6b7280;padding:12px 24px;border-radius:100px;font-size:16px;font-weight:600;font-family:'Outfit',sans-serif;cursor:pointer;transition:all .2s}
.bk-btn-prev:hover{border-color:#59b7b7;color:#3d9a9a}
.bk-btn-next{background:#59b7b7;color:#fff;border:none;padding:13px 32px;border-radius:100px;font-size:16px;font-weight:700;font-family:'Outfit',sans-serif;cursor:pointer;transition:all .3s}
.bk-btn-next:hover{background:#3d9a9a;transform:translateY(-2px)}
.bk-btn-submit{background:#e8724a;color:#fff;border:none;padding:15px 36px;border-radius:100px;font-size:17px;font-weight:700;font-family:'Outfit',sans-serif;cursor:pointer;transition:all .3s}
.bk-btn-submit:hover{background:#d4603c;transform:translateY(-2px)}
/* RÉCAP STICKY DROITE */
.bk-recap-col{position:sticky;top:120px}
.bk-recap-card{background:#fff;border-radius:18px;padding:24px;box-shadow:0 4px 24px rgba(0,0,0,.08)}
.bk-recap-title{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:#59b7b7;font-family:'Outfit',sans-serif;margin-bottom:16px}
.bk-recap-voyage{display:flex;align-items:center;gap:10px;padding-bottom:14px;border-bottom:1px solid #f0f2f4;margin-bottom:14px}
.bk-recap-voyage-img{width:54px;height:44px;border-radius:8px;object-fit:cover;flex-shrink:0}
.bk-recap-voyage-name{font-size:16px;font-weight:700;color:#0f2424;font-family:'Outfit',sans-serif;line-height:1.3}
.bk-recap-voyage-dest{font-size:13px;color:#6b7280;font-family:'Outfit',sans-serif}
.bk-recap-row{display:flex;justify-content:space-between;padding:6px 0;font-family:'Outfit',sans-serif;font-size:14px;border-bottom:1px solid #f9f6f0}
.bk-recap-row:last-child{border-bottom:none}
.bk-recap-lbl{color:#6b7280}
.bk-recap-val{font-weight:600;color:#0f2424;text-align:right;display:block}
.bk-recap-total{display:flex;justify-content:space-between;align-items:center;margin-top:14px;padding-top:14px;border-top:2px solid #3d9a9a}
.bk-recap-total-lbl{font-size:15px;font-weight:700;color:#0f2424;font-family:'Outfit',sans-serif}
.bk-recap-total-val{font-family:'Playfair Display',serif;font-size:30px;font-weight:700;color:#3d9a9a}
.bk-recap-acompte{background:#e8f8f0;border-radius:10px;padding:10px;margin-top:10px;text-align:center;font-size:14px;font-family:'Outfit',sans-serif}
.bk-recap-acompte strong{display:block;color:#2d8a5a;font-size:18px;margin-bottom:2px}
/* SUCCESS */
.bk-success{text-align:center;padding:50px 30px}
.bk-success-icon{font-size:72px;margin-bottom:24px}
.bk-success h2{font-size:32px;font-family:'Playfair Display',serif;color:#0f2424;margin-bottom:10px}
.bk-success p{font-size:17px;color:#6b7280;font-family:'Outfit',sans-serif;max-width:480px;margin:0 auto 20px}
.bk-success-ref{background:#edf8f8;border-radius:12px;padding:14px 24px;display:inline-block;font-family:'Outfit',sans-serif}
.bk-success-ref strong{color:#3d9a9a;font-size:19px}
@media(max-width:900px){.bk-inner{grid-template-columns:1fr;padding:0 20px}.bk-recap-col{position:static}.bk-field-row{grid-template-columns:1fr 1fr}.bk-field-row.cols-4{grid-template-columns:1fr 1fr}.bk-filters-sidebar{display:none!important}}
@media(max-width:480px){.bk-field-row{grid-template-columns:1fr}.bk-field-row.cols-4{grid-template-columns:1fr}.combo-header{flex-direction:column;gap:8px;align-items:flex-start}.combo-leg-times{gap:6px}.combo-time{font-size:16px}.combo-airline{gap:6px}.combo-airline img{width:28px;height:28px}.bk-nav{flex-direction:column;gap:10px}.bk-btn-next,.bk-btn-prev{width:100%;text-align:center}}
@media(max-width:768px){.combo-leg{padding:10px 12px}.combo-leg-times{margin:0 6px;gap:6px}.combo-leg-meta{gap:4px;font-size:12px}.combo-leg-badge{font-size:11px;width:52px;padding:2px 5px}.combo-time{font-size:18px}.bk-card{padding:16px}}
/* ── Encart création de compte dans le tunnel ── */
.bk-account-nudge{background:linear-gradient(135deg,#f0f9f9 0%,#e4f3f3 100%);border:1.5px solid #a8d8d8;border-radius:16px;padding:20px 24px;margin-bottom:26px;display:flex;align-items:center;gap:20px;position:relative;overflow:hidden}
.bk-account-nudge::before{content:'';position:absolute;top:-30px;right:-30px;width:120px;height:120px;background:radial-gradient(circle,rgba(61,154,154,.12),transparent 70%);pointer-events:none}
.bk-account-nudge-icon{width:50px;height:50px;background:linear-gradient(135deg,#3d9a9a,#2d7a7a);border-radius:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:24px;box-shadow:0 4px 12px rgba(61,154,154,.3)}
.bk-account-nudge-body{flex:1;min-width:0}
.bk-account-nudge-title{font-family:'Outfit',sans-serif;font-size:15px;font-weight:700;color:#1a3a3a;margin:0 0 5px;line-height:1.3}
.bk-account-nudge-desc{font-family:'Outfit',sans-serif;font-size:13px;color:#4a7070;margin:0 0 14px;line-height:1.55}
.bk-account-nudge-btns{display:flex;gap:10px;flex-wrap:wrap}
.bk-account-nudge-btn-primary{background:linear-gradient(135deg,#3d9a9a,#2a8080);color:#fff!important;border:none;border-radius:10px;padding:9px 18px;font-family:'Outfit',sans-serif;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:transform .15s,box-shadow .15s;box-shadow:0 3px 10px rgba(61,154,154,.35)}
.bk-account-nudge-btn-primary:hover{transform:translateY(-1px);box-shadow:0 5px 15px rgba(61,154,154,.4);color:#fff!important}
.bk-account-nudge-btn-secondary{background:transparent;color:#2d8080!important;border:1.5px solid #3d9a9a;border-radius:10px;padding:8px 16px;font-family:'Outfit',sans-serif;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all .2s}
.bk-account-nudge-btn-secondary:hover{background:#3d9a9a;color:#fff!important}
.bk-account-nudge-close{position:absolute;top:10px;right:12px;background:none;border:none;cursor:pointer;color:#9cb8b8;font-size:20px;line-height:1;padding:2px 6px;border-radius:6px;transition:color .2s;font-weight:400}
.bk-account-nudge-close:hover{color:#2d7a7a}
@media(max-width:600px){.bk-account-nudge{flex-direction:column;gap:14px;align-items:flex-start}.bk-account-nudge-btns{flex-direction:column}.bk-account-nudge-btn-primary,.bk-account-nudge-btn-secondary{justify-content:center}}
</style>

<div class="bk-wrap">

<!-- SIDEBAR FILTRES VOLS -->
<aside class="bk-filters-sidebar" id="bk-filters-sidebar" style="display:none">
    <div class="bkf-title">Filtres</div>
    <div class="bkf-section">
        <div class="bkf-label">Type de vol</div>
        <label class="bkf-check"><input type="radio" name="bkf_type" value="all" checked> Tous <span class="bkf-n" id="bkf-n-all"></span></label>
        <label class="bkf-check"><input type="radio" name="bkf_type" value="direct"> ✈ Vol direct <span class="bkf-n" id="bkf-n-direct"></span></label>
        <label class="bkf-check"><input type="radio" name="bkf_type" value="escale"> ⇄ Avec escale <span class="bkf-n" id="bkf-n-escale"></span></label>
    </div>
    <div class="bkf-section">
        <div class="bkf-label">Trier par</div>
        <label class="bkf-check"><input type="radio" name="bkf_sort" value="price" checked> Prix</label>
        <label class="bkf-check"><input type="radio" name="bkf_sort" value="duration"> Durée</label>
        <label class="bkf-check"><input type="radio" name="bkf_sort" value="depart"> Heure départ</label>
    </div>
    <button type="button" class="bkf-reset" id="bkf-reset">↺ Réinitialiser</button>
</aside>

<div class="bk-inner">

    <!-- COLONNE PRINCIPALE -->
    <div class="bk-main">

        <!-- STEPPER -->
        <div class="bk-stepper">
            <div class="bk-stepper-track">
                <div class="bk-step active" id="bk-ind-1">
                    <div class="bk-step-pin"><div class="bk-step-pin-body"><span class="bk-step-pin-icon">✈️</span></div></div>
                    <div class="bk-step-label">Vol</div>
                    <div class="bk-step-hint">Départ</div>
                </div>
                <div class="bk-step-line" id="bk-line-1"></div>
                <div class="bk-step" id="bk-ind-2">
                    <div class="bk-step-pin"><div class="bk-step-pin-body"><span class="bk-step-pin-icon">👤</span></div></div>
                    <div class="bk-step-label">Voyageurs</div>
                    <div class="bk-step-hint">Renseignements</div>
                </div>
                <div class="bk-step-line" id="bk-line-2"></div>
                <div class="bk-step" id="bk-ind-3">
                    <div class="bk-step-pin"><div class="bk-step-pin-body"><span class="bk-step-pin-icon">💳</span></div></div>
                    <div class="bk-step-label">Paiement</div>
                    <div class="bk-step-hint">Contrat</div>
                </div>
                <div class="bk-step-line" id="bk-line-3"></div>
                <div class="bk-step" id="bk-ind-4">
                    <div class="bk-step-pin"><div class="bk-step-pin-body"><span class="bk-step-pin-icon">🎉</span></div></div>
                    <div class="bk-step-label">Confirmation</div>
                    <div class="bk-step-hint">Arrivée !</div>
                </div>
            </div>
        </div>

        <!-- MOBILE : bouton retour au produit (visible uniquement ≤ 768px) -->
        <a href="<?php echo esc_url(get_permalink($voyage_id)); ?>" class="bk-mobile-back">← Retour à la fiche séjour</a>
        <style>
        .bk-mobile-back{display:none}
        @media(max-width:768px){
            .bk-mobile-back{display:inline-flex;align-items:center;gap:4px;font-family:'Outfit',sans-serif;font-size:12px;font-weight:600;color:#59b7b7;text-decoration:none;padding:8px 14px;background:rgba(89,183,183,.08);border:1px solid rgba(89,183,183,.15);border-radius:100px;margin-bottom:14px;transition:all .2s}
            .bk-mobile-back:hover,.bk-mobile-back:active{background:rgba(89,183,183,.15);color:#3d9a9a}
        }
        </style>

        <!-- STEP 1 — VOL -->
        <div id="bk-step-1" class="bk-step-content">
            <div class="bk-card">
                <h2 class="bk-card-title">✈️ Sélection de votre vol</h2>
                <p class="bk-card-sub">Choisissez votre combinaison aller-retour parmi les vols disponibles.</p>

                <!-- ROUTE HEADER -->
                <div class="bk-route-header">
                    <div>
                        <div class="bk-route-iata"><?php echo esc_html(strtoupper((string)($params['aeroport'] ?? '—'))); ?></div>
                        <div class="bk-route-city"><?php echo esc_html(vs08_iata_city($params['aeroport'] ?? '')); ?></div>
                    </div>
                    <div style="text-align:center">
                        <div class="bk-route-arrow">⇄</div>
                        <div class="bk-route-dates">
                            <?php
                                $d_aller  = $params['date_depart'] ? date('d/m/Y', strtotime($params['date_depart'])) : '—';
                                $d_retour = $params['date_depart'] ? date('d/m/Y', strtotime($params['date_depart'].' +'.$duree.' days')) : '—';
                                echo $d_aller . ' → ' . $d_retour . ' · ' . $nb_total . ' pax';
                            ?>
                        </div>
                    </div>
                    <div>
                        <div class="bk-route-iata" style="text-align:right"><?php echo esc_html(strtoupper((string)($m['iata_dest'] ?? '—'))); ?></div>
                        <div class="bk-route-city"><?php echo esc_html(!empty($m['destination']) ? $m['destination'] : vs08_iata_city($m['iata_dest'] ?? '')); ?></div>
                    </div>
                </div>

                <!-- COMBINAISONS VOL ALLER+RETOUR -->
                <div id="bk-combo-wrap">
                    <div class="bk-combo-loading" id="bk-combo-loading">
                        <div class="bk-flights-spinner"></div>
                        Recherche des vols aller et retour…
                    </div>
                    <div id="bk-combo-list"></div>
                    <div id="bk-combo-error" class="bk-flights-error" style="display:none"></div>
                </div>

                <!-- Vol sélectionné (hidden, transmis au submit) -->
                <input type="hidden" id="bk-selected-offer-id" name="selected_offer_id" value="">
                <input type="hidden" id="bk-selected-vol-delta" name="vol_delta_pax" value="0">

                <!-- ══ OPTIONS BAGAGES ══ -->
                <?php
                $prix_bag_soute = floatval($m['prix_bagage_soute'] ?? 120);
                $prix_bag_golf  = floatval($m['prix_bagage_golf'] ?? 120);
                if (isset($m['prix_bagage_soute']) && $m['prix_bagage_soute'] === '' ) $prix_bag_soute = 120;
                if (isset($m['prix_bagage_golf'])  && $m['prix_bagage_golf']  === '' ) $prix_bag_golf  = 120;
                $nb_golfeurs_bk = $params['nb_golfeurs'];
                $has_bagages = ($prix_bag_soute > 0 || $prix_bag_golf > 0);
                ?>
                <?php if ($has_bagages): ?>
                <div style="margin-top:24px;border-top:1.5px dashed #e5e7eb;padding-top:20px">
                    <h3 style="font-family:'Playfair Display',serif;font-size:15px;font-weight:700;color:#0f2424;margin:0 0 4px">✈️ Options de vol</h3>
                    <p style="font-size:12px;color:#6b7280;font-family:'Outfit',sans-serif;margin:0 0 14px">Personnalisez votre voyage</p>

                    <?php if ($prix_bag_soute > 0): ?>
                    <div class="bk-option-row checked"
                         id="opt-row-bagage_soute"
                         data-bag-type="soute"
                         data-prix="<?php echo $prix_bag_soute; ?>"
                         data-qty-max="<?php echo $nb_total; ?>"
                         data-qty-default="<?php echo $nb_total; ?>"
                         onclick="bkToggleBag(this)">
                        <div class="bk-option-icon">🧳</div>
                        <div class="bk-option-body">
                            <div class="bk-option-name">Bagage en soute</div>
                            <div class="bk-option-desc">Selon compagnie entre 20 & 23 kg</div>
                        </div>
                        <div class="bk-option-right">
                            <span class="bk-opt-qty-badge" id="opt-badge-bagage_soute"><?php echo $nb_total; ?></span>
                            <button type="button" class="bk-opt-modifier-btn" id="opt-modifier-bagage_soute"
                                    onclick="event.stopPropagation();bkShowBagQty('bagage_soute')">Modifier</button>
                            <div class="bk-option-qty" id="opt-qty-bagage_soute" style="display:none">
                                <button type="button" onclick="event.stopPropagation();bkBagQtyChange('bagage_soute',-1)">−</button>
                                <span id="opt-qty-val-bagage_soute"><?php echo $nb_total; ?></span>
                                <button type="button" onclick="event.stopPropagation();bkBagQtyChange('bagage_soute',1)">+</button>
                            </div>
                        </div>
                        <input type="hidden" name="nb_bagage_soute" id="bk-nb-bagage-soute" value="<?php echo $nb_total; ?>">
                    </div>
                    <?php endif; ?>

                    <?php if ($prix_bag_golf > 0 && $nb_golfeurs_bk > 0): ?>
                    <div class="bk-option-row checked"
                         id="opt-row-bagage_golf"
                         data-bag-type="golf"
                         data-prix="<?php echo $prix_bag_golf; ?>"
                         data-qty-max="<?php echo $nb_total; ?>"
                         data-qty-default="<?php echo $nb_golfeurs_bk; ?>"
                         onclick="bkToggleBag(this)">
                        <div class="bk-option-icon">🏌️</div>
                        <div class="bk-option-body">
                            <div class="bk-option-name">Sac de golf</div>
                            <div class="bk-option-desc">Votre sac de golf en soute</div>
                        </div>
                        <div class="bk-option-right">
                            <span class="bk-opt-qty-badge" id="opt-badge-bagage_golf"><?php echo $nb_golfeurs_bk; ?></span>
                            <button type="button" class="bk-opt-modifier-btn" id="opt-modifier-bagage_golf"
                                    onclick="event.stopPropagation();bkShowBagQty('bagage_golf')">Modifier</button>
                            <div class="bk-option-qty" id="opt-qty-bagage_golf" style="display:none">
                                <button type="button" onclick="event.stopPropagation();bkBagQtyChange('bagage_golf',-1)">−</button>
                                <span id="opt-qty-val-bagage_golf"><?php echo $nb_golfeurs_bk; ?></span>
                                <button type="button" onclick="event.stopPropagation();bkBagQtyChange('bagage_golf',1)">+</button>
                            </div>
                        </div>
                        <input type="hidden" name="nb_bagage_golf" id="bk-nb-bagage-golf" value="<?php echo $nb_golfeurs_bk; ?>">
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- ══ ASSURANCE VOYAGE ══ -->
                <?php if ($insurance_price > 0): ?>
                <div style="margin-top:24px;border-top:1.5px dashed #e5e7eb;padding-top:22px">
                    <div class="bk-ins-wrap">
                        <!-- Header avec logo + badge -->
                        <div class="bk-ins-header">
                            <img src="<?php echo VS08V_URL; ?>assets/img/assurever-logo.png" alt="Assurever" class="bk-ins-logo" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <span style="display:none;font-family:'Outfit',sans-serif;font-size:15px;font-weight:800;color:#0083a3;letter-spacing:.5px">ASSUREVER</span>
                            <span class="bk-ins-badge">GALAXY MULTIRISQUE</span>
                        </div>

                        <!-- Corps -->
                        <div class="bk-ins-body">
                            <!-- Liens documents -->
                            <div class="bk-ins-docs">
                                <a href="<?php echo VS08V_URL; ?>assets/docs/assurever-ipid-galaxy.pdf" target="_blank" class="bk-ins-doc">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="#0083a3"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/></svg>
                                    Fiche produit (IPID)
                                </a>
                                <a href="<?php echo VS08V_URL; ?>assets/docs/assurever-conditions-galaxy.pdf" target="_blank" class="bk-ins-doc">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="#0083a3"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/></svg>
                                    Conditions générales
                                </a>
                                <a href="<?php echo VS08V_URL; ?>assets/docs/assurever-resume-galaxy.pdf" target="_blank" class="bk-ins-doc">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="#0083a3"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/></svg>
                                    Synthèse des garanties
                                </a>
                            </div>
                        </div><!-- /bk-ins-body -->

                        <!-- Footer : checkbox + prix -->
                        <div class="bk-ins-footer">
                            <label class="bk-ins-check-label">
                                <input type="checkbox" id="bk-assurance" onchange="bkUpdateTotal()">
                                <div class="bk-ins-check-text">
                                    Oui, je souhaite être protégé(e)
                                    <br><small>Assurance Multirisque GALAXY · Assurever / Mutuaide</small>
                                </div>
                            </label>
                            <div class="bk-ins-price">
                                <div class="bk-ins-price-main"><?php echo number_format($insurance_price * $nb_total,2,',',' '); ?> €</div>
                                <div class="bk-ins-price-detail"><?php echo number_format($insurance_price,2,',',' '); ?> € /pers. × <?php echo $nb_total; ?></div>
                            </div>
                        </div>
                    </div><!-- /bk-ins-wrap -->
                </div>
                <?php endif; ?>

                <div class="bk-nav bk-nav-step1" style="margin-top:24px">
                    <a href="<?php echo esc_url(get_permalink($voyage_id)); ?>" class="bk-btn-prev">← Retour au séjour</a>
                    <button class="bk-btn-next" onclick="bkGo(2)">Continuer →</button>
                </div>
                <script>if(window.innerWidth<=768){var ns1=document.querySelector('.bk-nav-step1');if(ns1)ns1.style.display='none';}</script>
            </div>
        </div>

        <!-- STEP 2 — VOYAGEURS -->
        <div id="bk-step-2" class="bk-step-content" style="display:none">
            <div class="bk-card">
                <h2 class="bk-card-title">Informations voyageurs</h2>
                <p class="bk-card-sub"><?php echo $nb_total; ?> voyageur(s) — <?php echo $nb_chambres; ?> chambre(s) <?php echo ucfirst($params['type_chambre']); ?> — Départ le <?php echo date('d/m/Y', strtotime($params['date_depart'])); ?></p>

                <?php if (!is_user_logged_in()): ?>
                <div class="bk-account-nudge" id="bk-account-nudge">
                    <div class="bk-account-nudge-icon">🏌️</div>
                    <div class="bk-account-nudge-body">
                        <p class="bk-account-nudge-title">Créez votre espace voyageur — gratuit</p>
                        <p class="bk-account-nudge-desc">Retrouvez vos dossiers, contrats et voyageurs enregistrés. En créant votre compte maintenant, vous serez automatiquement redirigé ici au même stade.</p>
                        <div class="bk-account-nudge-btns">
                            <a href="<?php echo esc_url(home_url('/connexion/') . '?tab=register&redirect_to=' . urlencode($bk_redirect_back)); ?>" class="bk-account-nudge-btn-primary">✨ Créer mon compte</a>
                            <a href="<?php echo esc_url(home_url('/connexion/') . '?redirect_to=' . urlencode($bk_redirect_back)); ?>" class="bk-account-nudge-btn-secondary">J'ai déjà un compte →</a>
                        </div>
                    </div>
                    <button class="bk-account-nudge-close" onclick="document.getElementById('bk-account-nudge').style.display='none'" title="Fermer">×</button>
                </div>
                <?php endif; ?>

                <?php
                $has_loc_voiture = in_array('location_vehicule', $m['compris']['oui'] ?? []);
                if ($has_loc_voiture): ?>
                <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:10px;padding:10px 16px;margin-bottom:14px;font-size:12px;color:#166534;font-family:'Outfit',sans-serif">
                    🚗 <strong>Location de véhicule incluse</strong> — Sélectionnez le conducteur principal en cochant la case sous son nom.
                </div>
                <?php endif; ?>

                <?php
                $voy_index = 0;
                for ($chambre = 1; $chambre <= $nb_chambres; $chambre++):
                    // Répartir les voyageurs par chambre
                    $voy_par_chambre = ceil($nb_total / $nb_chambres);
                ?>
                <div class="bk-chambre-block">
                    <div class="bk-chambre-title">🏨 Chambre <?php echo $chambre; ?></div>

                    <?php for ($v = 0; $v < $voy_par_chambre && $voy_index < $nb_total; $v++, $voy_index++):
                        $is_golfeur = $voy_index < $params['nb_golfeurs'];
                        $type_label = $is_golfeur ? 'golfeur' : 'non-golfeur';
                    ?>
                    <div class="bk-voyageur-row" data-voy-index="<?php echo $voy_index; ?>">
                        <div class="bk-voyageur-label">
                            Voyageur <?php echo $voy_index + 1; ?>
                            <span class="bk-type-badge bk-type-<?php echo $is_golfeur ? 'golfeur' : 'nongolfeur'; ?>">
                                <?php echo $is_golfeur ? '⛳ Golfeur' : '👤 Non-golfeur'; ?>
                            </span>
                        </div>
                        <?php if (!empty($bk_saved_voyageurs)): ?>
                        <div class="bk-voyageur-fill-row">
                            <label for="bk-fill-voy-<?php echo $voy_index; ?>">Remplir avec un voyageur enregistré :</label>
                            <select id="bk-fill-voy-<?php echo $voy_index; ?>" class="bk-fill-voyageur-select" data-voy-index="<?php echo $voy_index; ?>">
                                <option value="">— Choisir —</option>
                                <?php foreach ($bk_saved_voyageurs as $sv_i => $sv): ?>
                                <option value="<?php echo (int) $sv_i; ?>"><?php echo esc_html(($sv['prenom'] ?? '') . ' ' . strtoupper($sv['nom'] ?? '')); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="bk-field-row">
                            <div class="bk-field">
                                <label>Prénom *</label>
                                <input type="text" name="voyageurs[<?php echo $voy_index;?>][prenom]" class="bk-required" placeholder="Jean">
                            </div>
                            <div class="bk-field">
                                <label>Nom *</label>
                                <input type="text" name="voyageurs[<?php echo $voy_index;?>][nom]" class="bk-required" placeholder="Dupont">
                            </div>
                            <div class="bk-field">
                                <label>Date de naissance *</label>
                                <div id="bk-ddn-wrap-<?php echo $voy_index;?>" style="position:relative">
                                    <div class="bk-ddn-trigger" id="bk-ddn-trigger-<?php echo $voy_index;?>"
                                         onclick="window.bkCalDDN_<?php echo $voy_index;?> && window.bkCalDDN_<?php echo $voy_index;?>.toggle()"
                                         style="padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;cursor:pointer;font-size:13px;font-family:'Outfit',sans-serif;color:#9ca3af;background:#fafafa;transition:border-color .2s">
                                        🎂 Date de naissance
                                    </div>
                                </div>
                                <input type="hidden" name="voyageurs[<?php echo $voy_index;?>][ddn]" id="bk-ddn-<?php echo $voy_index;?>" class="bk-required">
                            </div>
                        </div>
                        <div class="bk-field-row cols-2">
                            <div class="bk-field">
                                <label>N° Passeport <span class="bk-optional">(facultatif)</span></label>
                                <input type="text" name="voyageurs[<?php echo $voy_index;?>][passeport]" placeholder="XX000000">
                            </div>
                            <div class="bk-field">
                                <label>Nationalité</label>
                                <input type="text" name="voyageurs[<?php echo $voy_index;?>][nationalite]" placeholder="Française" value="Française">
                            </div>
                        </div>
                        <input type="hidden" name="voyageurs[<?php echo $voy_index;?>][type]" value="<?php echo $type_label;?>">
                        <input type="hidden" name="voyageurs[<?php echo $voy_index;?>][chambre]" value="<?php echo $chambre;?>">
                        <?php if ($has_loc_voiture): ?>
                        <label class="bk-conducteur-radio-lbl" id="bk-conducteur-lbl-<?php echo $voy_index; ?>">
                            <input type="radio" name="conducteur_principal" value="<?php echo $voy_index; ?>"
                                   id="bk-conducteur-<?php echo $voy_index; ?>"
                                   onchange="bkUpdateConducteur(<?php echo $voy_index; ?>)">
                            <span class="bk-conducteur-radio-txt">🚗 Ce passager sera le conducteur principal du véhicule</span>
                        </label>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>
                <?php endfor; ?>

                <?php if ($has_loc_voiture): ?>
                <input type="hidden" id="bk-conducteur-val" class="bk-required" value="">
                <style>
                .bk-conducteur-radio-lbl{display:flex;align-items:center;gap:10px;cursor:pointer;margin-top:10px;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;background:#f9fafb;transition:all .2s;user-select:none}
                .bk-conducteur-radio-lbl:hover{border-color:#59b7b7;background:#f0fafa}
                .bk-conducteur-radio-lbl input[type=radio]{width:18px;height:18px;accent-color:#59b7b7;flex-shrink:0;cursor:pointer}
                .bk-conducteur-radio-txt{font-size:12px;color:#374151;font-family:'Outfit',sans-serif;font-weight:500}
                .bk-conducteur-radio-lbl.selected{border-color:#59b7b7;background:#e0f7f7}
                .bk-conducteur-radio-lbl.selected .bk-conducteur-radio-txt{color:#0f2424;font-weight:700}
                @keyframes bkShake{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-6px)}40%,80%{transform:translateX(6px)}}
                </style>
                <?php endif; ?>

                <div class="bk-nav">
                    <button class="bk-btn-prev" onclick="bkGo(1)">← Retour</button>
                    <button class="bk-btn-next" onclick="bkGo(3)">Continuer →</button>
                </div>
            </div>
        </div>

        <!-- STEP 3 — FACTURATION -->
        <div id="bk-step-3" class="bk-step-content" style="display:none">
            <div class="bk-card">
                <h2 class="bk-card-title">Coordonnées de facturation</h2>
                <p class="bk-card-sub">Ces informations figureront sur votre facture et permettront à votre conseiller de vous contacter.</p>
                <div class="bk-fact-grid">
                    <div class="bk-field"><label>Prénom *</label><input type="text" id="fact-prenom" class="bk-required" placeholder="Jean" value="<?php echo esc_attr($bk_saved_fact['prenom'] ?? ''); ?>"></div>
                    <div class="bk-field"><label>Nom *</label><input type="text" id="fact-nom" class="bk-required" placeholder="Dupont" value="<?php echo esc_attr($bk_saved_fact['nom'] ?? ''); ?>"></div>
                    <div class="bk-field"><label>Email *</label><input type="email" id="fact-email" class="bk-required" placeholder="jean@email.com" value="<?php echo esc_attr($bk_saved_fact['email'] ?? ''); ?>"></div>
                    <div class="bk-field"><label>Téléphone *</label><input type="tel" id="fact-tel" class="bk-required" placeholder="06 XX XX XX XX" value="<?php echo esc_attr($bk_saved_fact['tel'] ?? ''); ?>"></div>
                    <div class="bk-field full"><label>Adresse *</label><input type="text" id="fact-adresse" class="bk-required" placeholder="12 rue des Fleurs" value="<?php echo esc_attr($bk_saved_fact['adresse'] ?? ''); ?>"></div>
                    <div class="bk-field"><label>Code postal *</label><input type="text" id="fact-cp" class="bk-required" placeholder="08000" value="<?php echo esc_attr($bk_saved_fact['cp'] ?? ''); ?>"></div>
                    <div class="bk-field"><label>Ville *</label><input type="text" id="fact-ville" class="bk-required" placeholder="Charleville-Mézières" value="<?php echo esc_attr($bk_saved_fact['ville'] ?? ''); ?>"></div>
                    <div class="bk-field"><label>Pays</label><input type="text" id="fact-pays" value="France" placeholder="France"></div>
                </div>
                <div class="bk-nav">
                    <button class="bk-btn-prev" onclick="bkGo(2)">← Retour</button>
                    <button class="bk-btn-next" onclick="bkGo(4)">Continuer →</button>
                </div>
            </div>
        </div>
        <!-- STEP 4 — CONFIRMATION -->
        <div id="bk-step-4" class="bk-step-content" style="display:none">
            <div class="bk-card">
                <h2 class="bk-card-title">Confirmation de votre réservation</h2>
                <p class="bk-card-sub">Vérifiez scrupuleusement toutes les informations avant de procéder au paiement.</p>

                <div id="bk-recap-final" style="border-radius:14px;margin-bottom:20px"></div>

                <div style="background:#f9fafb;border:1.5px solid #e5e7eb;border-radius:12px;padding:16px;margin-bottom:16px;font-family:'Outfit',sans-serif">
                    <div style="font-size:13px;font-weight:700;color:#0f2424;margin-bottom:10px">💳 Mode de règlement</div>
                    <label style="display:flex;gap:10px;cursor:pointer;align-items:flex-start">
                        <input type="radio" name="bk-payment-mode" value="card" checked style="margin-top:4px;flex-shrink:0">
                        <span style="font-size:13px;color:#374151;line-height:1.5"><strong>Payer par carte bancaire</strong> (Paybox sécurisé) — encaissement de l’acompte ou du montant dû en ligne.</span>
                    </label>
                    <div style="height:1px;background:#e5e7eb;margin:12px 0"></div>
                    <label style="display:flex;gap:10px;cursor:pointer;align-items:flex-start">
                        <input type="radio" name="bk-payment-mode" value="agency" id="bk-payment-agency" style="margin-top:4px;flex-shrink:0">
                        <span style="font-size:13px;color:#374151;line-height:1.5"><strong>Paiement en agence</strong> (pré-réservation)</span>
                    </label>
                    <div id="bk-agence-confirm-wrap" style="display:none;margin:12px 0 0 28px">
                        <label style="display:flex;gap:8px;cursor:pointer;align-items:flex-start">
                            <input type="checkbox" id="bk-agence-confirm" style="margin-top:2px;flex-shrink:0">
                            <span style="font-size:11px;color:#6b7280;line-height:1.45">Je comprends qu’il s’agit d’une <strong>pré-réservation</strong> : le <strong>prix n’est pas définitivement bloqué</strong> tant que le règlement n’a pas été effectué en agence.</span>
                        </label>
                    </div>
                    <div style="height:1px;background:#e5e7eb;margin:12px 0"></div>
                    <label style="display:flex;gap:10px;cursor:pointer;align-items:flex-start" id="bk-mode-split-label">
                        <input type="radio" name="bk-payment-mode" value="splitpay" id="bk-payment-splitpay" style="margin-top:4px;flex-shrink:0;accent-color:#59b7b7">
                        <span style="font-size:13px;color:#374151;line-height:1.5">
                            <strong>👥 Payer à plusieurs</strong> — Créez un dossier groupe. Chaque participant recevra son lien de paiement sécurisé.<br>
                            <span style="font-size:11px;color:#59b7b7">Idéal pour les groupes de golfeurs · Vous configurerez les parts dans votre espace voyageur</span>
                        </span>
                    </label>
                </div>

                <!-- Clause légale conforme au Code du Tourisme (art. L211-8 et suivants) et Directive UE 2015/2302 -->
                <div style="background:#fff8f0;border:1.5px solid #f0dcc0;border-radius:12px;padding:16px;margin-bottom:16px">
                    <label style="display:flex;gap:10px;cursor:pointer;align-items:flex-start">
                        <input type="checkbox" id="bk-confirm-info" style="margin-top:3px;flex-shrink:0">
                        <span style="font-size:12px;color:#6b5630;font-family:'Outfit',sans-serif;line-height:1.5">
                            Je certifie que <strong>les noms, prénoms, dates de naissance et informations de passeport</strong> renseignés sont exacts et
                            correspondent aux pièces d'identité officielles de chaque voyageur.
                            Je suis informé(e) que toute erreur pourra entraîner un refus d'embarquement
                            et que les frais de modification de billet seront à ma charge.
                        </span>
                    </label>
                </div>

                <!-- Conditions d'entrée — obligation légale de vérification (art. L211-16 Code du Tourisme) -->
                <div style="background:#f0f7ff;border:1.5px solid #bfdbfe;border-radius:12px;padding:16px;margin-bottom:16px">
                    <label style="display:flex;gap:10px;cursor:pointer;align-items:flex-start">
                        <input type="checkbox" id="bk-confirm-entree" style="margin-top:3px;flex-shrink:0">
                        <span style="font-size:12px;color:#1e3a5f;font-family:'Outfit',sans-serif;line-height:1.6">
                            Je déclare m'être renseigné(e) sur les <strong>conditions d'entrée dans le(s) pays de destination</strong>
                            (validité du passeport, visa, vaccinations obligatoires ou recommandées, restrictions sanitaires, etc.)
                            auprès des autorités compétentes (<a href="https://www.diplomatie.gouv.fr/fr/conseils-aux-voyageurs/" target="_blank" rel="noopener" style="color:#2563eb">France Diplomatie</a>)
                            et je reconnais être <strong>seul(e) responsable</strong> du respect de ces formalités.
                            Voyages Sortir 08 ne saurait être tenu responsable d'un refus d'entrée sur le territoire pour non-respect des conditions requises.
                        </span>
                    </label>
                </div>

                <label class="bk-cgu">
                    <input type="checkbox" id="bk-cgu">
                    <span class="bk-cgu-text">
                        J'accepte les <a href="<?php echo home_url('/conditions'); ?>" target="_blank">conditions générales de vente</a>
                        et la <a href="<?php echo home_url('/rgpd'); ?>" target="_blank">politique de confidentialité</a>.
                        Je reconnais avoir pris connaissance du <strong>formulaire d'information standard</strong> prévu par la
                        <a href="https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32015L2302" target="_blank" rel="noopener">Directive (UE) 2015/2302</a>
                        relative aux voyages à forfait, ainsi que des conditions d'annulation du séjour.
                        Conformément à l'article L211-8 du Code du Tourisme, je dispose d'un droit de rétractation
                        dans les conditions prévues par le contrat.
                    </span>
                </label>

                <div class="bk-nav">
                    <button class="bk-btn-prev" onclick="bkGo(3)">← Retour</button>
                    <button class="bk-btn-submit" onclick="bkSubmit()">🔒 Procéder au paiement →</button>
                </div>
            </div>
        </div>

        <!-- SUCCESS -->
        <div id="bk-success" style="display:none">
            <div class="bk-card bk-success">
                <div class="bk-success-icon">🎉</div>
                <h2>Réservation confirmée !</h2>
                <p>Votre conseiller vous contacte sous <strong>2 heures</strong> pour finaliser les détails et vous envoyer votre facture.</p>
                <div class="bk-success-ref">Référence : <strong id="bk-ref"></strong></div>
                <p style="margin-top:18px;font-size:12px;color:#9ca3af;font-family:'Outfit',sans-serif">Confirmation envoyée à <strong id="bk-success-email"></strong></p>
            </div>
        </div>

    </div><!-- /bk-main -->

    <!-- RÉCAP STICKY DROITE -->
    <div class="bk-recap-col">
        <div class="bk-recap-card">
            <p class="bk-recap-title">Récapitulatif</p>
            <?php
            $galerie = $m['galerie'] ?? [];
            $img_recap = !empty($galerie[0]) ? $galerie[0] : '';
            ?>
            <div class="bk-recap-voyage">
                <?php if ($img_recap): ?><img src="<?php echo esc_url($img_recap); ?>" class="bk-recap-voyage-img" alt=""><?php endif; ?>
                <div>
                    <div class="bk-recap-voyage-name"><?php echo esc_html($titre); ?></div>
                    <div class="bk-recap-voyage-dest"><?php echo esc_html(($flag_display ? $flag_display . ' ' : '').($m['destination']??'')); ?></div>
                </div>
            </div>

            <?php
            $date_aller_recap  = $params['date_depart'] ? date('d/m/Y', strtotime($params['date_depart'])) : '—';
            $date_retour_recap = $params['date_depart'] ? date('d/m/Y', strtotime($params['date_depart'] . ' +' . (intval($m['duree_jours'] ?? $duree + 1) - 1) . ' days')) : '—';
            $hotel_nom_recap   = $m['hotel_nom'] ?? '';
            $etoiles_recap     = intval($m['hotel_etoiles'] ?? 0);
            $pension_labels    = ['bb'=>'Petit-déjeuner','dp'=>'Demi-pension','pc'=>'Pension complète','ai'=>'Tout inclus'];
            $pension_recap     = $pension_labels[$m['pension'] ?? 'bb'] ?? '';
            $transfert_type_recap    = $m['transfert_type'] ?? '';
            $transfert_labels_recap  = ['groupes'=>'Transferts groupés','prives'=>'Transferts privés','voiture'=>'Location de voiture'];
            $transfert_icons_recap   = ['groupes'=>'🚌','prives'=>'🚐','voiture'=>'🚗'];

            // Lignes de devis → totaux par catégorie (récap admin : montants / visiteur : ✓)
            $recap_montants = [];
            foreach ($devis['lines'] as $dl) {
                $recap_montants[] = $dl;
            }
            // Helper pour chercher un montant par mot-clé dans le label
            function vs08v_find_line($lines, $keyword) {
                $total = 0;
                foreach ($lines as $l) {
                    if (stripos($l['label'], $keyword) !== false) {
                        $total += $l['montant'];
                    }
                }
                return $total;
            }
            $recap_vol      = vs08v_find_line($recap_montants, 'Vol');
            $recap_transfert = vs08v_find_line($recap_montants, 'Transfert') + vs08v_find_line($recap_montants, 'Location');
            $recap_hebergement = 0;
            foreach ($recap_montants as $rl) {
                if (stripos($rl['label'], 'bergement') !== false || stripos($rl['label'], 'Chambre') !== false || stripos($rl['label'], 'Suppl.') !== false) {
                    $recap_hebergement += $rl['montant'];
                }
            }
            $recap_saison   = vs08v_find_line($recap_montants, 'Supplément');
            $recap_greenfees = vs08v_find_line($recap_montants, 'Forfait green fees');
            $recap_accomp    = vs08v_find_line($recap_montants, 'Accompagnant');
            $recap_bag_soute = vs08v_find_line($recap_montants, 'Bagage soute');
            $recap_bag_golf  = vs08v_find_line($recap_montants, 'Bagage golf');
            $recap_taxe      = vs08v_find_line($recap_montants, 'Taxe');
            $recap_marge     = vs08v_find_line($recap_montants, 'Marge');
            ?>

            <?php $is_admin_user = current_user_can('manage_options'); ?>

            <!-- 1. VOL ALLER -->
            <div class="bk-recap-row" id="bk-recap-row-vol">
                <span class="bk-recap-lbl">✈️ Vol aller <span id="bk-recap-vol-date" style="font-weight:400;color:#9ca3af;font-size:12px">· le <?php echo $date_aller_recap; ?></span>
                    <div id="bk-recap-vol-detail" style="font-size:11px;color:#9ca3af;margin-top:2px"><?php echo esc_html(strtoupper((string)($params['aeroport'] ?: '—'))); ?> → <?php echo esc_html(strtoupper((string)($m['iata_dest'] ?? '—'))); ?></div>
                </span>
                <?php if ($is_admin_user): ?>
                <span class="bk-recap-val" id="bk-recap-vol-val"><?php echo $recap_vol > 0 ? number_format($recap_vol, 0, ',', ' ').' €' : '—'; ?></span>
                <?php else: ?>
                <span class="bk-recap-val" id="bk-recap-vol-val" style="color:#2d8a5a;font-size:12px">✓</span>
                <?php endif; ?>
            </div>

            <!-- 2. VOL RETOUR -->
            <div class="bk-recap-row" id="bk-recap-row-retour">
                <span class="bk-recap-lbl">✈️ Vol retour <span id="bk-recap-retour-date" style="font-weight:400;color:#9ca3af;font-size:12px">· le <?php echo $date_retour_recap; ?></span>
                    <div id="bk-recap-retour-detail" style="font-size:11px;color:#9ca3af;margin-top:2px"><?php echo esc_html(strtoupper((string)($m['iata_dest'] ?? '—'))); ?> → <?php echo esc_html(strtoupper((string)($params['aeroport'] ?: '—'))); ?></div>
                </span>
                <?php if ($is_admin_user): ?>
                <span class="bk-recap-val" id="bk-recap-retour-val">—</span>
                <?php else: ?>
                <span class="bk-recap-val" id="bk-recap-retour-val" style="color:#2d8a5a;font-size:12px">✓</span>
                <?php endif; ?>
            </div>

            <!-- SUPPLÉMENT VOL (admin only) -->
            <?php if ($is_admin_user): ?>
            <div class="bk-recap-row" id="bk-recap-row-vol-delta" style="display:none">
                <span class="bk-recap-lbl">⬆️ Supplément vol</span>
                <span class="bk-recap-val" id="bk-recap-vol-delta-val" style="color:#b85c1a"></span>
            </div>
            <?php else: ?>
            <div style="display:none"><span id="bk-recap-vol-delta-val"></span></div>
            <?php endif; ?>

            <!-- 3. TRANSFERT / LOCATION -->
            <?php if ($transfert_type_recap && isset($transfert_labels_recap[$transfert_type_recap])): ?>
            <div class="bk-recap-row">
                <span class="bk-recap-lbl"><?php echo $transfert_icons_recap[$transfert_type_recap]; ?> <?php echo esc_html($transfert_labels_recap[$transfert_type_recap]); ?>
                    <?php if ($transfert_type_recap === 'voiture' && !empty($m['voiture_details']['modele'])): ?>
                    <div style="font-size:11px;color:#9ca3af;margin-top:2px"><?php echo esc_html($m['voiture_details']['modele']); ?></div>
                    <?php endif; ?>
                </span>
                <span class="bk-recap-val"><?php echo $is_admin_user ? ($recap_transfert > 0 ? number_format($recap_transfert, 0, ',', ' ').' €' : '0 €') : '<span style="color:#2d8a5a;font-size:12px">✓</span>'; ?></span>
            </div>
            <?php endif; ?>

            <!-- 4. HÔTEL & PENSION -->
            <?php if ($hotel_nom_recap): ?>
            <div class="bk-recap-row">
                <span class="bk-recap-lbl">🏨 <?php echo esc_html($hotel_nom_recap); ?> <?php echo $etoiles_recap > 0 ? str_repeat('★', $etoiles_recap) : ''; ?>
                    <div style="font-size:11px;color:#9ca3af;margin-top:2px"><?php echo esc_html($pension_recap); ?> · <?php echo $duree; ?> nuits</div>
                </span>
                <span class="bk-recap-val"><?php echo $is_admin_user ? number_format($recap_hebergement + $recap_saison, 0, ',', ' ').' €' : '<span style="color:#2d8a5a;font-size:12px">✓</span>'; ?></span>
            </div>
            <?php endif; ?>

            <!-- ACCOMPAGNANTS -->
            <?php if ($recap_accomp > 0): ?>
            <div class="bk-recap-row">
                <span class="bk-recap-lbl">👤 Accompagnants non-golfeurs</span>
                <span class="bk-recap-val"><?php echo $is_admin_user ? number_format($recap_accomp, 0, ',', ' ').' €' : '<span style="color:#2d8a5a;font-size:12px">✓</span>'; ?></span>
            </div>
            <?php endif; ?>

            <!-- 5. BAGAGE SOUTE -->
            <?php if ($prix_bag_soute > 0): ?>
            <div class="bk-recap-row" id="bk-recap-row-bag-soute">
                <span class="bk-recap-lbl">🧳 Bagage en soute
                    <div style="font-size:11px;color:#9ca3af;margin-top:2px" id="bk-recap-bag-soute-detail"><?php echo $nb_total; ?> bagage<?php echo $nb_total > 1 ? 's' : ''; ?></div>
                </span>
                <?php if ($is_admin_user): ?>
                <span class="bk-recap-val" id="bk-recap-bag-soute-val"><?php echo number_format($prix_bag_soute * $nb_total, 0, ',', ' '); ?> €</span>
                <?php else: ?>
                <span class="bk-recap-val" id="bk-recap-bag-soute-val" style="color:#2d8a5a;font-size:12px">✓</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- 6. BAGAGE GOLF -->
            <?php if ($prix_bag_golf > 0 && $nb_golfeurs_bk > 0): ?>
            <div class="bk-recap-row" id="bk-recap-row-bag-golf">
                <span class="bk-recap-lbl">🏌️ Bagage golf
                    <div style="font-size:11px;color:#9ca3af;margin-top:2px" id="bk-recap-bag-golf-detail"><?php echo $nb_golfeurs_bk; ?> bagage<?php echo $nb_golfeurs_bk > 1 ? 's' : ''; ?></div>
                </span>
                <?php if ($is_admin_user): ?>
                <span class="bk-recap-val" id="bk-recap-bag-golf-val"><?php echo number_format($prix_bag_golf * $nb_golfeurs_bk, 0, ',', ' '); ?> €</span>
                <?php else: ?>
                <span class="bk-recap-val" id="bk-recap-bag-golf-val" style="color:#2d8a5a;font-size:12px">✓</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- 7. FORFAIT GREEN FEES -->
            <?php if (floatval($m['prix_greenfees'] ?? 0) > 0 && $nb_golfeurs_bk > 0): ?>
            <div class="bk-recap-row" id="bk-recap-row-greenfees">
                <span class="bk-recap-lbl">⛳ Forfait green fees
                    <div style="font-size:11px;color:#9ca3af;margin-top:2px"><?php echo $nb_golfeurs_bk; ?> golfeur<?php echo $nb_golfeurs_bk > 1 ? 's' : ''; ?></div>
                </span>
                <span class="bk-recap-val"><?php echo $is_admin_user ? number_format($recap_greenfees, 0, ',', ' ').' €' : '<span style="color:#2d8a5a;font-size:12px">✓</span>'; ?></span>
            </div>
            <?php endif; ?>

            <!-- TAXES (admin only) -->
            <?php if ($is_admin_user && $recap_taxe > 0): ?>
            <div class="bk-recap-row">
                <span class="bk-recap-lbl">🏛️ Taxes aéroport</span>
                <span class="bk-recap-val"><?php echo number_format($recap_taxe, 0, ',', ' '); ?> €</span>
            </div>
            <?php endif; ?>

            <!-- MARGE (admin only) -->
            <?php if ($is_admin_user && $recap_marge > 0): ?>
            <div class="bk-recap-row">
                <span class="bk-recap-lbl">📊 Marge agence</span>
                <span class="bk-recap-val"><?php echo number_format($recap_marge, 0, ',', ' '); ?> €</span>
            </div>
            <?php endif; ?>

            <div id="bk-recap-insurance-line"></div>

            <div class="bk-recap-total">
                <div class="bk-recap-total-lbl">Total</div>
                <div class="bk-recap-total-val" id="bk-recap-total"><?php echo number_format($devis['total'],0,',',' '); ?> €</div>
            </div>

            <?php
            $acompte_pct = floatval($m['acompte_pct'] ?? 30);
            $delai_solde = intval($m['delai_solde'] ?? 30);
            $payer_tout = false;
            if ($params['date_depart']) {
                $jours = (strtotime($params['date_depart']) - time()) / 86400;
                $payer_tout = $jours <= $delai_solde;
            }
            ?>
            <div class="bk-recap-acompte">
                <?php if ($payer_tout): ?>
                    <strong>Paiement intégral requis</strong>
                    <div style="font-size:11px;color:#6b7280">Départ dans moins de <?php echo $delai_solde; ?> jours</div>
                <?php else: ?>
                    <strong id="bk-recap-acompte-val"><?php echo number_format($devis['acompte'],0,',',' '); ?> €</strong>
                    <div style="font-size:11px;color:#6b7280">Acompte <?php echo intval($devis['acompte_pct_final'] ?? $acompte_pct); ?>% à payer maintenant</div>
                    <div style="font-size:11px;color:#9ca3af">Solde <?php echo $delai_solde; ?> j. avant départ</div>
                <?php endif; ?>
            </div>
        </div>

        <div style="background:#0f2424;border-radius:14px;padding:18px;margin-top:14px">
            <div style="font-size:11px;font-weight:700;color:#7ecece;text-transform:uppercase;letter-spacing:1.5px;font-family:'Outfit',sans-serif;margin-bottom:12px">Besoin d'aide ?</div>
            <div style="font-size:16px;font-weight:700;color:#fff;font-family:'Playfair Display',serif;margin-bottom:4px">03 26 65 28 63</div>
            <div style="font-size:11px;color:rgba(255,255,255,.5);font-family:'Outfit',sans-serif;line-height:1.7">Lun–Ven 09h–12h / 14h–18h30<br>Sam 09h–12h / 14h–18h00</div>
        </div>

        <!-- Bouton Continuer sous recap (visible sur mobile step 1) -->
        <div id="bk-recap-btn-wrap" style="margin-top:14px">
            <button class="bk-btn-next" style="width:100%" id="bk-recap-btn" onclick="bkGo(2)">Continuer →</button>
        </div>
        <style>
        @media(min-width:769px){#bk-recap-btn-wrap{display:none!important}}
        </style>
    </div>

</div>
</div>

<!-- DONNÉES PHP → JS -->
<script>
<?php
$h_data           = $m['hotel'] ?? [];
$pension_labels_js = ['bb'=>'Petit-déjeuner','hb'=>'Demi-pension','fb'=>'Pension complète','ai'=>'Tout inclus','sc'=>'Sans repas','dp'=>'Demi-pension','pc'=>'Pension complète'];
$pension_js       = $pension_labels_js[$h_data['pension'] ?? $m['pension'] ?? 'bb'] ?? 'Petit-déjeuner';
$hotel_nom_js     = !empty($h_data['nom']) ? $h_data['nom'] : ($m['hotel_nom'] ?? '');
$etoiles_js       = intval($h_data['etoiles'] ?? $m['hotel_etoiles'] ?? 5);
$date_retour_js   = $params['date_depart'] ? date('Y-m-d', strtotime($params['date_depart']) + $duree * 86400) : '';
$duree_jours_js   = $duree + 1;
?>
var BK_DATA = <?php echo json_encode([
    'voyage_id'       => $voyage_id,
    'titre'           => $titre,
    'params'          => $params,
    'devis'           => $devis,
    'nb_total'        => $nb_total,
    'nb_chambres'     => $nb_chambres,
    'insurance_price' => $insurance_price,
    'insurance_total' => $insurance_price * $nb_total,
    'acompte_pct'     => $acompte_pct,
    'acompte_mode'    => $m['acompte_mode'] ?? 'pct',
    'acompte_eur'     => floatval($m['acompte_eur'] ?? 0),
    'payer_tout'      => $payer_tout,
    'delai_solde'     => $delai_solde,
    'ajax_url'        => admin_url('admin-ajax.php'),
    'rest_flight'     => rest_url('vs08v/v1/flight'),
    'rest_booking'    => rest_url('vs08v/v1/booking'),
    'rest_nonce'      => rest_url('vs08v/v1/nonce'),
    'duree'           => $duree,
    'duree_jours'     => $duree_jours_js,
    'iata_dest'       => strtoupper((string)($m['iata_dest'] ?? '')),
    'nonce'           => wp_create_nonce('vs08v_nonce'),
    'booking_nonce'   => wp_create_nonce('vs08v_booking'),
    'checkout_url'    => wc_get_checkout_url(),
    'hotel_nom'       => $hotel_nom_js,
    'pension'         => $pension_js,
    'etoiles'         => $etoiles_js,
    'date_retour'     => $date_retour_js,
    'destination'     => $m['destination'] ?? '',
    'flag'            => $flag_display,
    'saved_voyageurs' => $bk_saved_voyageurs,
    'transfert_type'  => $m['transfert_type'] ?? '',
    'transfert_label' => $bk_tl[$m['transfert_type']??''] ?? '',
    'voiture_details' => ($m['transfert_type']??'') === 'voiture' ? ($m['voiture_details'] ?? []) : [],
    'nb_golfeurs'      => $params['nb_golfeurs'],
    'prix_greenfees'   => floatval($m['prix_greenfees'] ?? 0),
    'prix_bagage_soute' => $prix_bag_soute,
    'prix_bagage_golf'  => $prix_bag_golf,
    'is_admin'          => current_user_can('manage_options'),
]); ?>;
var bk_options_total     = 0;
var bk_options_data      = {};
var bk_insurance_check   = false;
var bk_vol_delta_total   = 0; // supplément vol total (delta_per_pax × nb_passagers)
var bk_flights_data      = [];
var bk_retour_data       = [];
var bk_combos_data       = [];
var bk_vol_nb_pax        = BK_DATA.params.nb_golfeurs + BK_DATA.params.nb_nongolfeurs;

// ── Initialisation des calendriers de naissance VS08 (différée) ──
function bkInitDDNCalendars() {
    if (typeof VS08Calendar === 'undefined') return;
    var nb = BK_DATA.nb_total;
    for (var i = 0; i < nb; i++) {
        (function(idx) {
            var wrapId    = '#bk-ddn-wrap-' + idx;
            var inputId   = '#bk-ddn-' + idx;
            var triggerId = '#bk-ddn-trigger-' + idx;
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
                        var opts = { day: 'numeric', month: 'long', year: 'numeric' };
                        trigger.textContent = '🎂 ' + d.toLocaleDateString('fr-FR', opts);
                        trigger.style.color = '#0f2424';
                        trigger.style.fontWeight = '600';
                        trigger.style.borderColor = '#3d9a9a';
                    }
                }
            });
            window['bkCalDDN_' + idx] = cal;
        })(i);
    }
}
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bkInitDDNCalendars);
} else {
    bkInitDDNCalendars();
}

// Remplissage voyageur depuis la liste des voyageurs enregistrés
(function bkFillVoyageurFromSaved() {
    var saved = (typeof BK_DATA !== 'undefined' && BK_DATA.saved_voyageurs) ? BK_DATA.saved_voyageurs : [];
    if (!saved.length) return;
    function parseDDN(str) {
        if (!str || !str.trim()) return null;
        str = str.trim();
        var m = str.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
        if (m) return m[3] + '-' + m[2].padStart(2,'0') + '-' + m[1].padStart(2,'0');
        m = str.match(/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/);
        if (m) return m[1] + '-' + m[2].padStart(2,'0') + '-' + m[3].padStart(2,'0');
        return null;
    }
    function formatDDNDisplay(iso) {
        if (!iso) return '';
        var d = new Date(iso + 'T12:00:00');
        if (isNaN(d.getTime())) return iso;
        return '🎂 ' + d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
    }
    document.querySelectorAll('.bk-fill-voyageur-select').forEach(function(sel) {
        sel.addEventListener('change', function() {
            var idx = parseInt(this.dataset.voyIndex, 10);
            var optVal = this.value;
            if (optVal === '') return;
            var v = saved[parseInt(optVal, 10)];
            if (!v) return;
            var row = document.querySelector('.bk-voyageur-row[data-voy-index="' + idx + '"]');
            if (!row) return;
            var prenomInp = row.querySelector('input[name="voyageurs[' + idx + '][prenom]"]');
            var nomInp = row.querySelector('input[name="voyageurs[' + idx + '][nom]"]');
            var ddnInp = document.getElementById('bk-ddn-' + idx);
            var ddnTrigger = document.getElementById('bk-ddn-trigger-' + idx);
            var passeportInp = row.querySelector('input[name="voyageurs[' + idx + '][passeport]"]');
            if (prenomInp) prenomInp.value = v.prenom || '';
            if (nomInp) nomInp.value = v.nom || '';
            if (passeportInp) passeportInp.value = v.passeport || '';
            var iso = parseDDN(v.ddn);
            if (ddnInp) ddnInp.value = iso || '';
            if (ddnTrigger) {
                ddnTrigger.textContent = iso ? formatDDNDisplay(iso) : '🎂 Date de naissance';
                ddnTrigger.style.color = iso ? '#0f2424' : '';
                ddnTrigger.style.fontWeight = iso ? '600' : '';
                ddnTrigger.style.borderColor = iso ? '#3d9a9a' : '';
            }
        });
    });
})();

// ████████████████████████████████████████████████████████████████████████████
// ██  NE PAS MODIFIER — BLOC RECHERCHE VOLS (bkLoadFlights → bkSelectCombo)  ██
// ██  Toute modification brise la recherche. Bloc testé et validé.            ██
// ████████████████████████████████████████████████████████████████████████████
var bk_aller_loaded  = false;
var bk_retour_loaded = false;

(function bkLoadFlights(){
    var aero    = BK_DATA.params.aeroport;
    var date    = BK_DATA.params.date_depart;
    var errDiv  = document.getElementById('bk-combo-error');
    var loading = document.getElementById('bk-combo-loading');

    if (!aero || !date) {
        if (loading) loading.style.display = 'none';
        if (errDiv)  { errDiv.style.display = 'block'; errDiv.textContent = 'Aucun aéroport ou date sélectionné. Retournez sur la fiche voyage.'; }
        return;
    }

    var dateRetour = bkAddDays(date, BK_DATA.duree || 7);

    // Envoi recherche vol : REST en priorité (évite 500 admin-ajax), puis fallback AJAX
    function bkPostFlight(payload, done) {
        var url = BK_DATA.rest_flight || '';
        var ajaxPayload = { action: 'vs08v_get_flight', nonce: BK_DATA.nonce };
        for (var k in payload) ajaxPayload[k] = payload[k];
        function doAjax(u, d) { jQuery.post(u, d).done(done).fail(function() { if (BK_DATA.ajax_url && u === BK_DATA.rest_flight) doAjax(BK_DATA.ajax_url, ajaxPayload); else done({ success: false }); }); }
        if (url) doAjax(url, payload); else doAjax(BK_DATA.ajax_url, ajaxPayload);
    }

    // ── Appel ALLER ──
    bkPostFlight({
        voyage_id : BK_DATA.voyage_id,
        date      : date,
        aeroport  : aero,
        passengers: bk_vol_nb_pax
    }, function(res){
        if (res && res.success && res.data && res.data.flights && res.data.flights.length) {
            bk_flights_data = res.data.flights;
        }
        bk_aller_loaded = true;
        bkTryBuildCombos();
    });

    // ── Appel RETOUR ──
    bkPostFlight({
        voyage_id   : BK_DATA.voyage_id,
        date        : dateRetour,
        aeroport    : BK_DATA.iata_dest,
        destination : aero,
        passengers  : bk_vol_nb_pax
    }, function(res){
        if (res && res.success && res.data && res.data.flights && res.data.flights.length) {
            bk_retour_data = res.data.flights;
        }
        bk_retour_loaded = true;
        bkTryBuildCombos();
    });
})();

// ── Quand les 2 sont chargés → construire les combinaisons ──────────────────
function bkTryBuildCombos() {
    if (!bk_aller_loaded || !bk_retour_loaded) return;

    var loading = document.getElementById('bk-combo-loading');
    var errDiv  = document.getElementById('bk-combo-error');
    if (loading) loading.style.display = 'none';

    if (!bk_flights_data.length && !bk_retour_data.length) {
        if (errDiv) { errDiv.style.display = 'block'; errDiv.textContent = 'Aucun vol disponible pour cette combinaison. Contactez-nous au 03 26 65 28 63.'; }
        return;
    }

    // Construire les combinaisons aller+retour (même compagnie)
    var combos = [];
    bk_flights_data.forEach(function(a) {
        bk_retour_data.filter(function(r){ return r.airline_iata === a.airline_iata; }).forEach(function(r) {
            combos.push({
                aller       : a,
                retour      : r,
                airline_name: a.airline_name,
                airline_iata: a.airline_iata,
                total_delta : (a.delta_per_pax || 0) + (r.delta_per_pax || 0),
            });
        });
    });

    // Si aucune combo (compagnies différentes) → combos aller seul
    if (!combos.length) {
        bk_flights_data.forEach(function(a) {
            combos.push({ aller: a, retour: null, airline_name: a.airline_name, airline_iata: a.airline_iata, total_delta: a.delta_per_pax || 0 });
        });
    }

    // Trier par supplément total croissant
    combos.sort(function(a, b){ return a.total_delta - b.total_delta; });

    // Marquer la moins chère
    if (combos.length) combos[0].is_reference = true;

    bk_combos_data = combos;
    bkRenderCombos(combos);
    bkSelectCombo(0);
}

// ████████████████████████████████████████████████████████████████████████████
// ██  FIN BLOC RECHERCHE VOLS — NE PAS MODIFIER AU-DESSUS DE CETTE LIGNE     ██
// ████████████████████████████████████████████████████████████████████████████

// ══════════════════════════════════════════════════════════════════════════════
// HELPERS RECAP CARD
// ══════════════════════════════════════════════════════════════════════════════
function bkUpdateRecapVol(f) {
    var volDate   = document.getElementById('bk-recap-vol-date');
    var volDetail = document.getElementById('bk-recap-vol-detail');
    var aero = BK_DATA.params.aeroport || '—';
    var dest = BK_DATA.iata_dest || '—';
    var dateStr = BK_DATA.params.date_depart ? bkFmtDate(BK_DATA.params.date_depart) : '—';
    if (volDate) {
        volDate.textContent = '· le ' + dateStr;
    }
    if (volDetail) {
        var airline = f.airline_name ? ' · ' + f.airline_name : '';
        var times = (f.depart_time && f.arrive_time) ? ' · ' + f.depart_time + ' → ' + f.arrive_time : '';
        volDetail.textContent = aero + ' → ' + dest + airline + times;
    }
}

function bkUpdateRecapRetour(f) {
    var retourRow    = document.getElementById('bk-recap-row-retour');
    var retourDate   = document.getElementById('bk-recap-retour-date');
    var retourDetail = document.getElementById('bk-recap-retour-detail');
    if (retourRow) retourRow.style.display = 'flex';
    var aero = BK_DATA.params.aeroport || '—';
    var dest = BK_DATA.iata_dest || '—';
    var dateRetStr = BK_DATA.date_retour ? bkFmtDate(BK_DATA.date_retour) : '—';
    if (retourDate) {
        retourDate.textContent = '· le ' + dateRetStr;
    }
    if (retourDetail) {
        var airline = f.airline_name ? ' · ' + f.airline_name : '';
        var times = (f.depart_time && f.arrive_time) ? ' · ' + f.depart_time + ' → ' + f.arrive_time : '';
        retourDetail.textContent = dest + ' → ' + aero + airline + times;
    }
}

// Ligne supplément vol (visible seulement si delta > 0)
function bkUpdateRecapVolDelta() {
    var deltaLine = document.getElementById('bk-recap-row-vol-delta');
    if (!deltaLine) return;
    if (bk_vol_delta_total > 0) {
        deltaLine.style.display = 'flex';
        var deltaVal = document.getElementById('bk-recap-vol-delta-val');
        if (deltaVal) { deltaVal.textContent = '+' + bkFmt(bk_vol_delta_total); deltaVal.style.color = '#b85c1a'; }
    } else {
        deltaLine.style.display = 'none';
    }
}


// ══════════════════════════════════════════════════════════════════════════════
// HELPERS
// ══════════════════════════════════════════════════════════════════════════════

function bkEsc(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str || ''));
    return div.innerHTML;
}

function bkFmt(n) {
    return Math.ceil(parseFloat(n || 0)).toLocaleString('fr-FR', {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' €';
}

function bkAddDays(dateStr, days) {
    var d = new Date(dateStr);
    d.setDate(d.getDate() + parseInt(days));
    var yyyy = d.getFullYear();
    var mm   = String(d.getMonth() + 1).padStart(2, '0');
    var dd   = String(d.getDate()).padStart(2, '0');
    return yyyy + '-' + mm + '-' + dd;
}

function bkFmtDuration(min) {
    if (!min) return '';
    var h = Math.floor(min / 60);
    var m = min % 60;
    return h + 'h' + String(m).padStart(2, '0');
}


// ══════════════════════════════════════════════════════════════════════════════
// RENDU DES COMBINAISONS ALLER+RETOUR
// ══════════════════════════════════════════════════════════════════════════════

function bkHasConn(f) {
    if (!f) return false;
    if (f.has_connections === true || f.has_connections === 1 || f.has_connections === '1') return true;
    if (f.flight_detail && f.flight_detail.indexOf('+') !== -1) return true;
    return false;
}

function bkConnBadge(f) {
    if (!f) return '';
    var isConn = bkHasConn(f);
    var cls = isConn ? 'escale' : 'direct';
    var label = isConn ? '1 escale' : 'Vol direct';
    return '<span class="combo-conn-badge ' + cls + '">' + label + '</span>';
}

function bkFmtLayover(min) {
    if (!min) return '';
    var h = Math.floor(min / 60), m = min % 60;
    if (h > 0 && m > 0) return h + 'h' + String(m).padStart(2,'0');
    if (h > 0) return h + 'h';
    return m + 'min';
}
function bkConnDetail(f, idx, legType) {
    if (!f || !bkHasConn(f)) return '';
    var segs = f.segments_detail || [];
    var id = 'conn-detail-' + idx + '-' + legType;
    var togId = 'conn-tog-' + idx + '-' + legType;
    var stepsHtml = '';

    if (segs.length > 0) {
        segs.forEach(function(s, i) {
            if (i > 0 && s.layover_before_min > 0) {
                stepsHtml += '<div class="combo-conn-step layover-row"><span class="dot layover"></span> Escale · ' + bkFmtLayover(s.layover_before_min) + ' d\'attente</div>';
            }
            stepsHtml += '<div class="combo-conn-step"><span class="dot"></span>'
                + ' <strong>' + bkEsc(s.origin) + '</strong> ' + bkEsc(s.depart_time)
                + ' → <strong>' + bkEsc(s.destination) + '</strong> ' + bkEsc(s.arrive_time)
                + ' <span class="seg-flight">' + bkEsc(s.flight) + '</span>'
                + '</div>';
        });
    } else {
        var detail = f.flight_detail || f.flight_number || '';
        var parts = detail.split(/\s*\+\s*/);
        parts.forEach(function(p, i) {
            if (i > 0) stepsHtml += '<div class="combo-conn-step"><span class="dot layover"></span> <em>Correspondance</em></div>';
            stepsHtml += '<div class="combo-conn-step"><span class="dot"></span> Vol ' + bkEsc(p.trim()) + '</div>';
        });
    }

    return '<div class="combo-conn-toggle" id="' + togId + '" onclick="event.stopPropagation();var d=document.getElementById(\'' + id + '\');var t=document.getElementById(\'' + togId + '\');if(d.classList.contains(\'open\')){d.classList.remove(\'open\');t.classList.remove(\'open\')}else{d.classList.add(\'open\');t.classList.add(\'open\')}">'
        + '<span class="chevron">▼</span> Voir les détails'
        + '</div>'
        + '<div class="combo-conn-detail" id="' + id + '">' + stepsHtml + '</div>';
}

function bkRenderCombos(combos) {
    var list = document.getElementById('bk-combo-list');
    if (!list) return;
    list.innerHTML = '';

    combos.forEach(function(c, idx) {
        var a = c.aller;
        var r = c.retour;

        var priceHtml = '';
        if (c.is_reference) {
            priceHtml = '<div class="combo-price-delta ref">Meilleur prix</div>';
        } else {
            priceHtml = '<div class="combo-price-delta">+' + bkFmt(c.total_delta) + '</div>'
                      + '<div class="combo-price-sub">/pers. aller-retour</div>';
        }

        var html = '<div class="combo-header">'
            +   '<div class="combo-airline">'
            +     '<img src="https://images.kiwi.com/airlines/64/' + bkEsc(a.airline_iata) + '.png" alt="" onerror="this.style.display=\'none\'">'
            +     '<div><div class="combo-airline-name">' + bkEsc(c.airline_name) + '</div>'
            +     '<div class="combo-airline-sub">' + bkEsc(a.airline_iata) + '</div></div>'
            +   '</div>'
            +   '<div style="display:flex;align-items:center;gap:8px">'
            +     priceHtml
            +     '<div class="combo-check">✓</div>'
            +   '</div>'
            + '</div>';

        html += '<div class="combo-leg">'
            + '<div class="combo-leg-badge aller">ALLER</div>'
            + '<div class="combo-leg-times">'
            +   '<div class="combo-time">' + bkEsc(a.depart_time) + '</div>'
            +   '<div class="combo-leg-line"><div class="combo-leg-dash"></div><div class="combo-leg-plane">✈</div><div class="combo-leg-dash"></div></div>'
            +   '<div class="combo-time">' + bkEsc(a.arrive_time) + '</div>'
            + '</div>'
            + '<div class="combo-leg-meta">'
            +   bkConnBadge(a)
            +   '<span class="combo-leg-dur">' + bkFmtDuration(a.duration_min) + '</span>'
            +   '<span class="combo-leg-num">' + bkEsc(a.flight_number) + '</span>'
            + '</div>'
            + '</div>'
            + bkConnDetail(a, idx, 'aller');

        if (r) {
            html += '<div class="combo-leg">'
                + '<div class="combo-leg-badge retour">RETOUR</div>'
                + '<div class="combo-leg-times">'
                +   '<div class="combo-time">' + bkEsc(r.depart_time) + '</div>'
                +   '<div class="combo-leg-line"><div class="combo-leg-dash"></div><div class="combo-leg-plane">✈</div><div class="combo-leg-dash"></div></div>'
                +   '<div class="combo-time">' + bkEsc(r.arrive_time) + '</div>'
                + '</div>'
                + '<div class="combo-leg-meta">'
                +   bkConnBadge(r)
                +   '<span class="combo-leg-dur">' + bkFmtDuration(r.duration_min) + '</span>'
                +   '<span class="combo-leg-num">' + bkEsc(r.flight_number) + '</span>'
                + '</div>'
                + '</div>'
                + bkConnDetail(r, idx, 'retour');
        }

        var card = document.createElement('div');
        card.className = 'combo-card';
        card.id = 'combo-card-' + idx;
        card.innerHTML = html;
        card.addEventListener('click', (function(i) {
            return function() { bkSelectCombo(i); };
        })(idx));
        list.appendChild(card);
    });
}



// ══════════════════════════════════════════════════════════════════════════════
// SÉLECTION D'UNE COMBINAISON
// ══════════════════════════════════════════════════════════════════════════════

function bkSelectCombo(idx) {
    var c = bk_combos_data[idx];
    if (!c) return;

    // Déselectionner toutes les cartes
    document.querySelectorAll('.combo-card').forEach(function(el) {
        el.classList.remove('selected');
    });

    // Sélectionner celle-ci
    var card = document.getElementById('combo-card-' + idx);
    if (card) card.classList.add('selected');

    // Supplément vol
    bk_vol_delta_total = (c.total_delta || 0) * bk_vol_nb_pax;

    // Compagnie → bagage golf offert TU/AT (aligné calculateur / Paybox)
    if (c.aller && c.aller.airline_iata) {
        BK_DATA.params.airline_iata = c.aller.airline_iata;
    }

    // Stocker les détails du vol sélectionné pour le submit
    window.bk_selected_combo = c;

    // Hidden fields
    var offerInput = document.getElementById('bk-selected-offer-id');
    var deltaInput = document.getElementById('bk-selected-vol-delta');
    if (offerInput) offerInput.value = c.aller.offer_id;
    if (deltaInput) deltaInput.value = c.total_delta;

    // Recap
    bkUpdateRecapVol(c.aller);
    if (c.retour) bkUpdateRecapRetour(c.retour);

    bkUpdateTotal();
}


// ══════════════════════════════════════════════════════════════════════════════
// NAVIGATION ENTRE ÉTAPES
// ══════════════════════════════════════════════════════════════════════════════

function bkUpdateConducteur(idx) {
    // Mettre à jour le champ hidden requis
    var val = document.getElementById('bk-conducteur-val');
    if (val) val.value = String(idx);
    // Mettre en surbrillance la case sélectionnée, retirer les autres
    document.querySelectorAll('.bk-conducteur-radio-lbl').forEach(function(lbl) {
        lbl.classList.remove('selected');
    });
    var sel = document.getElementById('bk-conducteur-lbl-' + idx);
    if (sel) sel.classList.add('selected');
}

function bkGo(step) {
    var currentStep = 0;
    for (var s = 1; s <= 4; s++) {
        var el = document.getElementById('bk-step-' + s);
        if (el && el.style.display !== 'none') { currentStep = s; break; }
    }

    // Valider les champs requis si on avance
    if (step > currentStep) {
        var currentDiv = document.getElementById('bk-step-' + currentStep);
        if (currentDiv) {
            var invalid = false;
            currentDiv.querySelectorAll('.bk-required').forEach(function(input) {
                if (!input.value.trim()) {
                    input.classList.add('required-error');
                    invalid = true;
                    if (input.type === 'hidden' && input.id && input.id.indexOf('bk-ddn-') === 0) {
                        var trigger = document.getElementById(input.id.replace('bk-ddn-', 'bk-ddn-trigger-'));
                        if (trigger) { trigger.style.borderColor = '#dc2626'; trigger.style.boxShadow = '0 0 0 2px rgba(220,38,38,.15)'; }
                    }
                    // Conducteur non sélectionné → alerte visuelle
                    if (input.id === 'bk-conducteur-val') {
                        document.querySelectorAll('.bk-conducteur-radio-lbl').forEach(function(lbl) {
                            lbl.style.borderColor = '#dc2626';
                            lbl.style.boxShadow = '0 0 0 2px rgba(220,38,38,.15)';
                            lbl.style.animation = 'bkShake .5s ease-in-out';
                        });
                        // Afficher un message d'erreur visible
                        var errBox = document.getElementById('bk-conducteur-error');
                        if (!errBox) {
                            errBox = document.createElement('div');
                            errBox.id = 'bk-conducteur-error';
                            errBox.style.cssText = 'background:#fee2e2;color:#991b1b;border:1.5px solid #fca5a5;border-radius:10px;padding:12px 16px;margin:10px 0;font-size:13px;font-weight:600;font-family:Outfit,sans-serif;display:flex;align-items:center;gap:8px';
                            errBox.innerHTML = '⚠️ Veuillez sélectionner le conducteur principal du véhicule';
                            input.parentNode.insertBefore(errBox, input);
                        }
                        errBox.style.display = 'flex';
                    }
                } else {
                    input.classList.remove('required-error');
                    if (input.type === 'hidden' && input.id && input.id.indexOf('bk-ddn-') === 0) {
                        var trigger = document.getElementById(input.id.replace('bk-ddn-', 'bk-ddn-trigger-'));
                        if (trigger) { trigger.style.borderColor = '#3d9a9a'; trigger.style.boxShadow = 'none'; }
                    }
                    if (input.id === 'bk-conducteur-val') {
                        document.querySelectorAll('.bk-conducteur-radio-lbl').forEach(function(lbl) {
                            lbl.style.borderColor = ''; lbl.style.boxShadow = ''; lbl.style.animation = '';
                        });
                        var errBox = document.getElementById('bk-conducteur-error');
                        if (errBox) errBox.style.display = 'none';
                    }
                }
            });
            if (invalid) {
                var firstErr = currentDiv.querySelector('.required-error');
                if (firstErr) {
                    if (firstErr.id === 'bk-conducteur-val') {
                        // Scroll vers la première radio conducteur
                        var firstRadio = document.querySelector('.bk-conducteur-radio-lbl');
                        if (firstRadio) firstRadio.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } else if (firstErr.type === 'hidden') {
                        var triggerErr = firstErr.closest('.bk-field');
                        if (triggerErr) triggerErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } else {
                        firstErr.focus();
                    }
                }
                return;
            }
        }
    }

    // Construire le récap final à l'étape 4
    if (step === 4) bkBuildRecap();

    // Afficher/masquer les étapes
    for (var i = 1; i <= 4; i++) {
        var el = document.getElementById('bk-step-' + i);
        if (el) el.style.display = (i === step) ? 'block' : 'none';
    }

    // Mettre à jour le stepper
    for (var i = 1; i <= 4; i++) {
        var ind  = document.getElementById('bk-ind-' + i);
        var line = document.getElementById('bk-line-' + (i - 1));
        if (ind) {
            ind.className = 'bk-step';
            if (i < step)  ind.classList.add('done');
            if (i === step) ind.classList.add('active');
        }
        if (line) {
            line.className = 'bk-step-line';
            if (i <= step) line.classList.add('done');
        }
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });

    // ── Mobile : masquer la recap card sur les étapes 2, 3, 4 ──
    var recapCol = document.querySelector('.bk-recap-col');
    if (recapCol && window.innerWidth <= 900) {
        recapCol.style.display = (step === 1) ? '' : 'none';
    }
}


// ══════════════════════════════════════════════════════════════════════════════
// MISE À JOUR DU TOTAL (récap sticky droite)
// ══════════════════════════════════════════════════════════════════════════════

/** Plancher vols + bagages (même règle que VS08V_Calculator::compute_acompte_for_total) */
function bkPlancherVolBagages() {
    var prixVolPax = parseFloat(BK_DATA.params.prix_vol) || 0;
    var coutVol = prixVolPax * bk_vol_nb_pax + (bk_vol_delta_total || 0);
    var nbSouteEl = document.getElementById('bk-nb-bagage-soute');
    var nbGolfEl = document.getElementById('bk-nb-bagage-golf');
    var nbSoute = nbSouteEl ? parseInt(nbSouteEl.value, 10) : bk_vol_nb_pax;
    var nbGolf = nbGolfEl ? parseInt(nbGolfEl.value, 10) : (parseInt(BK_DATA.nb_golfeurs, 10) || 0);
    if (isNaN(nbSoute) || nbSoute < 0) nbSoute = bk_vol_nb_pax;
    if (isNaN(nbGolf) || nbGolf < 0) nbGolf = parseInt(BK_DATA.nb_golfeurs, 10) || 0;
    var ps = parseFloat(BK_DATA.prix_bagage_soute) || 0;
    var pg = parseFloat(BK_DATA.prix_bagage_golf) || 0;
    var iata = (BK_DATA.params.airline_iata || '').toUpperCase();
    var golfFree = (iata === 'TU' || iata === 'AT');
    return coutVol + ps * nbSoute + (golfFree ? 0 : pg * nbGolf);
}

/** Acompte affiché = logique serveur (%, € fixe, plancher) */
function bkComputeAcompteDisplay(total) {
    if (BK_DATA.payer_tout) {
        return {acompte: total, acomptePct: 100};
    }
    var mode = BK_DATA.acompte_mode || 'pct';
    var acomptePct = parseFloat(BK_DATA.acompte_pct) || 30;
    var plancher = bkPlancherVolBagages();
    var acompte;
    if (mode === 'eur' && parseFloat(BK_DATA.acompte_eur) > 0) {
        acompte = Math.max(Math.ceil(parseFloat(BK_DATA.acompte_eur)), Math.ceil(plancher));
        acompte = Math.min(total, acompte);
        acomptePct = total > 0 ? Math.min(100, Math.ceil(100 * acompte / total)) : acomptePct;
    } else {
        acompte = total * acomptePct / 100;
        if (plancher > 0 && acompte < plancher && total > 0) {
            acomptePct = Math.ceil((plancher / total) * 100 / 5) * 5;
            acompte = total * acomptePct / 100;
        }
        acompte = Math.ceil(acompte);
    }
    return {acompte: acompte, acomptePct: acomptePct};
}

function bkUpdateTotal() {
    var base      = parseFloat(BK_DATA.devis.total) || 0;
    var volDelta  = bk_vol_delta_total || 0;
    var options   = bk_options_total   || 0;
    var insurance = 0;

    var assurChk = document.getElementById('bk-assurance');
    if (assurChk && assurChk.checked) {
        insurance = parseFloat(BK_DATA.insurance_total) || 0;
        bk_insurance_check = true;
    } else {
        bk_insurance_check = false;
    }

    var total = Math.ceil(base + volDelta + options + insurance);

    // Total affiché — arrondi à l'euro supérieur
    var totalEl = document.getElementById('bk-recap-total');
    if (totalEl) totalEl.textContent = bkFmt(total);

    var ac = bkComputeAcompteDisplay(total);
    var acompte = ac.acompte;
    var acomptePct = ac.acomptePct;

    var acompteEl = document.getElementById('bk-recap-acompte-val');
    if (acompteEl) acompteEl.textContent = bkFmt(acompte);

    // Mettre à jour le libellé % si modifié
    var acompteLblEl = document.querySelector('.bk-recap-acompte div');
    if (acompteLblEl && !BK_DATA.payer_tout) {
        acompteLblEl.textContent = 'Acompte ' + acomptePct + '% à payer maintenant';
    }

    // Vol recap val + supplément
    var volVal = document.getElementById('bk-recap-vol-val');
    if (volVal) volVal.style.display = 'block';
    bkUpdateRecapVolDelta();

    var retourVal = document.getElementById('bk-recap-retour-val');
    if (retourVal) retourVal.style.display = 'block';

    // Bagages recap update
    bkUpdateBagRecap();

    // Assurance recap
    var insLine = document.getElementById('bk-recap-insurance-line');
    if (insLine) {
        if (bk_insurance_check && insurance > 0) {
            var insVal = BK_DATA.is_admin ? '+' + bkFmt(insurance) : '✓';
            var insColor = BK_DATA.is_admin ? 'color:#e3147a' : 'color:#2d8a5a;font-size:12px';
            insLine.innerHTML = '<div class="bk-recap-row"><span class="bk-recap-lbl">🛡️ Assurance Multirisques</span><span class="bk-recap-val" style="display:block;' + insColor + '">' + insVal + '</span></div>';
        } else {
            insLine.innerHTML = '';
        }
    }

    // Afficher toutes les valeurs recap qui ont du contenu
    document.querySelectorAll('.bk-recap-val').forEach(function(el) {
        if (el.textContent.trim()) el.style.display = 'block';
    });
}


// ══════════════════════════════════════════════════════════════════════════════
// BAGAGES (soute + golf)
// ══════════════════════════════════════════════════════════════════════════════

function bkShowBagQty(bagId) {
    var qtyWrap = document.getElementById('opt-qty-' + bagId);
    var modBtn  = document.getElementById('opt-modifier-' + bagId);
    var badge   = document.getElementById('opt-badge-' + bagId);
    if (!qtyWrap) return;
    var isVisible = qtyWrap.style.display !== 'none';
    qtyWrap.style.display = isVisible ? 'none' : 'flex';
    if (badge) badge.style.display = isVisible ? 'inline-block' : 'none';
    if (modBtn) modBtn.classList.toggle('active', !isVisible);
}

function bkToggleBag(el) {
    bkShowBagQty(el.getAttribute('data-bag-type') === 'golf' ? 'bagage_golf' : 'bagage_soute');
}

function bkBagQtyChange(bagId, delta) {
    var row = document.getElementById('opt-row-' + bagId);
    if (!row) return;
    var maxQ       = parseInt(row.getAttribute('data-qty-max'))     || 99;
    var defaultQty = parseInt(row.getAttribute('data-qty-default')) || 0;
    var prix       = parseFloat(row.getAttribute('data-prix'))      || 0;
    var valEl      = document.getElementById('opt-qty-val-' + bagId);
    var hiddenEl   = document.getElementById(bagId === 'bagage_soute' ? 'bk-nb-bagage-soute' : 'bk-nb-bagage-golf');
    var badgeEl    = document.getElementById('opt-badge-' + bagId);

    var current = parseInt(valEl.textContent) || 0;
    var newVal  = Math.max(0, Math.min(current + delta, defaultQty));
    valEl.textContent = newVal;
    if (hiddenEl) hiddenEl.value = newVal;
    if (badgeEl) badgeEl.textContent = newVal;

    var priceDelta = (newVal - defaultQty) * prix;
    bk_options_data[bagId] = { total: priceDelta };
    if (priceDelta === 0) delete bk_options_data[bagId];

    if (newVal > 0) {
        row.classList.add('checked');
    } else {
        row.classList.remove('checked');
    }

    bk_options_total = 0;
    Object.values(bk_options_data).forEach(function(o) { bk_options_total += o.total || 0; });
    bkUpdateTotal();
}

function bkUpdateBagRecap() {
    var types = [
        { id: 'bagage_soute', rowId: 'bk-recap-row-bag-soute', detailId: 'bk-recap-bag-soute-detail', valId: 'bk-recap-bag-soute-val', icon: '🧳 Bagage en soute', sub: '1 par voyageur · aller-retour' },
        { id: 'bagage_golf',  rowId: 'bk-recap-row-bag-golf',  detailId: 'bk-recap-bag-golf-detail',  valId: 'bk-recap-bag-golf-val',  icon: '🏌️ Bagage golf',     sub: '1 par golfeur · aller-retour' }
    ];
    types.forEach(function(t) {
        var optRow  = document.getElementById('opt-row-' + t.id);
        var recapRow = document.getElementById(t.rowId);
        if (!optRow || !recapRow) return;
        var defaultQty = parseInt(optRow.getAttribute('data-qty-default')) || 0;
        var prix       = parseFloat(optRow.getAttribute('data-prix'))      || 0;
        var valEl      = document.getElementById('opt-qty-val-' + t.id);
        var qty        = valEl ? parseInt(valEl.textContent) : defaultQty;
        var detailEl   = document.getElementById(t.detailId);
        var valRecapEl = document.getElementById(t.valId);
        if (detailEl) detailEl.textContent = BK_DATA.is_admin ? (qty + ' × ' + Math.round(prix) + '€') : (qty + ' bagage' + (qty > 1 ? 's' : ''));
        if (valRecapEl) {
            if (BK_DATA.is_admin) {
                var montant = qty * prix;
                valRecapEl.textContent = montant > 0 ? (Math.round(montant).toLocaleString('fr-FR') + ' €') : '—';
                valRecapEl.style.color = qty < defaultQty ? '#dc2626' : '#0f2424';
            }
        }
        recapRow.style.display = qty > 0 ? 'flex' : 'none';
    });
}


// ══════════════════════════════════════════════════════════════════════════════
// RÉCAPITULATIF FINAL (étape 5)
// ══════════════════════════════════════════════════════════════════════════════

function bkBuildRecap() {
    var recap = document.getElementById('bk-recap-final');
    if (!recap) return;
    var S = 'font-family:Outfit,sans-serif;';
    var section = 'font-weight:700;font-size:13px;text-transform:uppercase;letter-spacing:1px;color:#59b7b7;margin:18px 0 8px;padding-top:14px;border-top:1px solid #e8e4dc;' + S;
    var rowS = 'display:flex;justify-content:space-between;padding:5px 0;font-size:13px;color:#4a5568;' + S;
    var lblS = 'color:#6b7280';
    var valS = 'font-weight:600;color:#0f2424;text-align:right';

    var html = '<div style="' + S + 'font-size:13px">';

    // ── SÉJOUR ──
    html += '<div style="background:#f0fafa;border-radius:12px;padding:14px;margin-bottom:4px">';
    html += '<div style="font-family:Playfair Display,serif;font-weight:700;font-size:17px;color:#0f2424;margin-bottom:4px">' + bkEsc(BK_DATA.titre) + '</div>';
    html += '<div style="font-size:12px;color:#6b7280">' + bkEsc(BK_DATA.flag) + ' ' + bkEsc(BK_DATA.destination) + '</div>';
    if (BK_DATA.hotel_nom) {
        html += '<div style="font-size:12px;color:#4a5568;margin-top:4px">🏨 ' + bkEsc(BK_DATA.hotel_nom) + ' ' + BK_DATA.etoiles + '★ · ' + bkEsc(BK_DATA.pension) + '</div>';
    }
    html += '<div style="font-size:12px;color:#4a5568;margin-top:2px">🗓️ ' + BK_DATA.duree_jours + ' jours / ' + BK_DATA.duree + ' nuits · ' + BK_DATA.nb_total + ' voyageur(s) · ' + BK_DATA.nb_chambres + ' chambre(s)</div>';
    html += '</div>';

    // ── DATES & VOLS ──
    html += '<div style="' + section + '">✈️ Vols & Dates</div>';

    var dateDep = BK_DATA.params.date_depart ? bkFmtDate(BK_DATA.params.date_depart) : '—';
    var dateRet = BK_DATA.date_retour ? bkFmtDate(BK_DATA.date_retour) : '—';
    html += '<div style="' + rowS + '"><span style="' + lblS + '">Date de départ</span><span style="' + valS + '">' + dateDep + '</span></div>';
    html += '<div style="' + rowS + '"><span style="' + lblS + '">Date de retour</span><span style="' + valS + '">' + dateRet + '</span></div>';

    // Vol aller sélectionné
    var allerEl = document.getElementById('bk-recap-vol-detail');
    if (allerEl) {
        html += '<div style="' + rowS + '"><span style="' + lblS + '">Vol aller (' + bkEsc(BK_DATA.params.aeroport) + ' → ' + bkEsc(BK_DATA.iata_dest) + ')</span><span style="' + valS + '">' + allerEl.textContent.trim() + '</span></div>';
    }
    // Vol retour sélectionné
    var retourEl = document.getElementById('bk-recap-retour-detail');
    if (retourEl && retourEl.textContent.trim() !== '—') {
        html += '<div style="' + rowS + '"><span style="' + lblS + '">Vol retour (' + bkEsc(BK_DATA.iata_dest) + ' → ' + bkEsc(BK_DATA.params.aeroport) + ')</span><span style="' + valS + '">' + retourEl.textContent.trim() + '</span></div>';
    }

    // ── TRANSFERT / LOCATION VOITURE ──
    if (BK_DATA.transfert_type && BK_DATA.transfert_label) {
        var tIcon = {'groupes':'🚌','prives':'🚐','voiture':'🚗'}[BK_DATA.transfert_type] || '🚐';
        html += '<div style="' + section + '">' + tIcon + ' Transport sur place</div>';
        html += '<div style="' + rowS + '"><span style="' + lblS + '">' + tIcon + ' ' + bkEsc(BK_DATA.transfert_label) + '</span><span style="' + valS + ';color:#2d8a5a">Inclus</span></div>';
        if (BK_DATA.transfert_type === 'voiture' && BK_DATA.voiture_details) {
            var vd = BK_DATA.voiture_details;
            if (vd.modele) html += '<div style="' + rowS + '"><span style="' + lblS + '">Véhicule</span><span style="' + valS + '">' + bkEsc(vd.modele) + '</span></div>';
            var specs = [];
            if (vd.boite) specs.push((vd.boite === 'automatique' ? 'Auto' : 'Manuelle'));
            if (vd.clim === 'oui') specs.push('Clim');
            if (vd.portes) specs.push(vd.portes + ' portes');
            if (vd.places) specs.push(vd.places + ' places');
            if (specs.length) html += '<div style="' + rowS + '"><span style="' + lblS + '">Caractéristiques</span><span style="' + valS + '">' + bkEsc(specs.join(' · ')) + '</span></div>';
            if (vd.kilometrage === 'illimite') html += '<div style="' + rowS + '"><span style="' + lblS + '">Kilométrage</span><span style="' + valS + ';color:#2d8a5a">Illimité</span></div>';
            if (vd.assurance === 'incluse') html += '<div style="' + rowS + '"><span style="' + lblS + '">Assurance tous risques</span><span style="' + valS + ';color:#2d8a5a">✅ Incluse</span></div>';
        }
    }

    // ── BAGAGES ──
    var bagageRows = [];
    var bagSoute = document.getElementById('opt-row-bagage_soute');
    var bagGolf  = document.getElementById('opt-row-bagage_golf');
    if (bagSoute) {
        var bQty = document.getElementById('opt-qty-val-bagage_soute');
        var bVal = bQty ? parseInt(bQty.textContent) : parseInt(bagSoute.getAttribute('data-qty-default')) || 0;
        var bPrix = parseFloat(bagSoute.getAttribute('data-prix')) || 0;
        if (bVal > 0) bagageRows.push({ lbl: '🧳 Bagage soute × ' + bVal, montant: bVal * bPrix });
    }
    if (bagGolf) {
        var gQty = document.getElementById('opt-qty-val-bagage_golf');
        var gVal = gQty ? parseInt(gQty.textContent) : parseInt(bagGolf.getAttribute('data-qty-default')) || 0;
        var gPrix = parseFloat(bagGolf.getAttribute('data-prix')) || 0;
        if (gVal > 0) bagageRows.push({ lbl: '🏌️ Bagage golf × ' + gVal, montant: gVal * gPrix });
    }
    if (bagageRows.length) {
        html += '<div style="' + section + '">🧳 Bagages</div>';
        bagageRows.forEach(function(b) {
            html += '<div style="' + rowS + '"><span style="' + lblS + '">' + b.lbl + '</span><span style="' + valS + '">' + bkFmt(b.montant) + '</span></div>';
        });
    }

    // ── FORFAIT GREEN FEES ──
    if (BK_DATA.nb_golfeurs > 0 && parseFloat(BK_DATA.prix_greenfees || 0) > 0) {
        html += '<div style="' + section + '">⛳ Forfait green fees</div>';
        html += '<div style="' + rowS + '"><span style="' + lblS + '">⛳ ' + BK_DATA.nb_golfeurs + ' golfeur' + (BK_DATA.nb_golfeurs > 1 ? 's' : '') + ' · green fees inclus</span><span style="' + valS + ';color:#2d8a5a">Inclus</span></div>';
    }

    // ── VOYAGEURS ──
    html += '<div style="' + section + '">👥 Voyageurs</div>';
    document.querySelectorAll('.bk-voyageur-row').forEach(function(row, i) {
        var prenom  = row.querySelector('input[name*="[prenom]"]');
        var nom     = row.querySelector('input[name*="[nom]"]');
        var ddn     = row.querySelector('input[name*="[ddn]"]');
        var passeport = row.querySelector('input[name*="[passeport]"]');
        var nationalite = row.querySelector('input[name*="[nationalite]"]');
        var typeH   = row.querySelector('input[name*="[type]"]');
        if (!prenom || !prenom.value) return;

        var badge = (typeH && typeH.value === 'golfeur')
            ? '<span style="background:#e0f5e0;color:#2d8a5a;padding:1px 6px;border-radius:6px;font-size:10px;margin-left:6px">⛳ Golfeur</span>'
            : '<span style="background:#eef2ff;color:#5b6abf;padding:1px 6px;border-radius:6px;font-size:10px;margin-left:6px">👤 Accomp.</span>';

        html += '<div style="background:#fff;border:1px solid #e8e4dc;border-radius:10px;padding:10px 12px;margin-bottom:6px">';
        html += '<div style="font-weight:700;color:#0f2424;font-size:14px;margin-bottom:4px">' + bkEsc(prenom.value) + ' ' + bkEsc(nom.value.toUpperCase()) + badge + '</div>';

        var details = [];
        if (ddn && ddn.value) details.push('Né(e) le ' + bkFmtDate(ddn.value));
        if (nationalite && nationalite.value) details.push(bkEsc(nationalite.value));
        if (passeport && passeport.value) details.push('Passeport : ' + bkEsc(passeport.value));
        if (details.length) html += '<div style="font-size:11px;color:#6b7280">' + details.join(' · ') + '</div>';
        html += '</div>';
    });

    // ── FACTURATION ──
    html += '<div style="' + section + '">📋 Facturation</div>';
    var fP = document.getElementById('fact-prenom'), fN = document.getElementById('fact-nom');
    var fE = document.getElementById('fact-email'),  fT = document.getElementById('fact-tel');
    var fA = document.getElementById('fact-adresse'), fCP = document.getElementById('fact-cp');
    var fV = document.getElementById('fact-ville'), fPays = document.getElementById('fact-pays');
    if (fP && fN) html += '<div style="color:#0f2424;font-weight:600">' + bkEsc(fP.value) + ' ' + bkEsc(fN.value) + '</div>';
    if (fE) html += '<div style="color:#4a5568">' + bkEsc(fE.value) + '</div>';
    if (fT) html += '<div style="color:#4a5568">' + bkEsc(fT.value) + '</div>';
    if (fA && fA.value) html += '<div style="color:#4a5568">' + bkEsc(fA.value) + (fCP && fCP.value ? ', ' + bkEsc(fCP.value) : '') + (fV && fV.value ? ' ' + bkEsc(fV.value) : '') + (fPays && fPays.value ? ' — ' + bkEsc(fPays.value) : '') + '</div>';

    // ── TOTAL & ACOMPTE ──
    var total = (parseFloat(BK_DATA.devis.total) || 0) + (bk_vol_delta_total || 0) + (bk_options_total || 0);
    if (bk_insurance_check) total += parseFloat(BK_DATA.insurance_total) || 0;
    total = Math.ceil(total);

    html += '<div style="margin-top:18px;padding-top:14px;border-top:2.5px solid #3d9a9a;display:flex;justify-content:space-between;align-items:center">';
    html += '<span style="font-size:15px;font-weight:700;color:#0f2424">Total séjour</span>';
    html += '<span style="font-family:Playfair Display,serif;font-size:28px;font-weight:700;color:#3d9a9a">' + bkFmt(total) + '</span>';
    html += '</div>';

    // Acompte (même moteur que récap latéral / serveur)
    if (!BK_DATA.payer_tout) {
        var acFin = bkComputeAcompteDisplay(total);
        html += '<div style="background:#e8f8f0;border-radius:10px;padding:10px;margin-top:8px;text-align:center">';
        html += '<div style="font-weight:700;font-size:17px;color:#2d8a5a">' + bkFmt(acFin.acompte) + '</div>';
        html += '<div style="font-size:11px;color:#6b7280">Acompte ' + acFin.acomptePct + '% à payer maintenant · Solde ' + BK_DATA.delai_solde + ' j. avant départ</div>';
        html += '</div>';
    } else {
        html += '<div style="background:#fff3e0;border-radius:10px;padding:10px;margin-top:8px;text-align:center">';
        html += '<div style="font-weight:700;font-size:14px;color:#b85c1a">Paiement intégral requis</div>';
        html += '<div style="font-size:11px;color:#6b7280">Départ dans moins de ' + BK_DATA.delai_solde + ' jours</div>';
        html += '</div>';
    }

    html += '</div>';
    recap.innerHTML = html;
}

function bkFmtDate(dateStr) {
    if (!dateStr) return '—';
    var parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    var mois = ['janv.','févr.','mars','avr.','mai','juin','juil.','août','sept.','oct.','nov.','déc.'];
    return parseInt(parts[2]) + ' ' + mois[parseInt(parts[1]) - 1] + ' ' + parts[0];
}


// ══════════════════════════════════════════════════════════════════════════════
// SOUMISSION DE RÉSERVATION
// ══════════════════════════════════════════════════════════════════════════════

function bkSubmit() {
    var confirmInfo = document.getElementById('bk-confirm-info');
    if (!confirmInfo || !confirmInfo.checked) {
        alert('Veuillez certifier l\'exactitude des informations voyageurs (noms, dates de naissance, passeports).');
        confirmInfo && confirmInfo.closest('label') && (confirmInfo.closest('label').style.outline = '2px solid #dc2626');
        return;
    }
    var confirmEntree = document.getElementById('bk-confirm-entree');
    if (!confirmEntree || !confirmEntree.checked) {
        alert('Veuillez confirmer que vous avez vérifié les conditions d\'entrée dans le pays de destination.');
        confirmEntree && confirmEntree.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }
    // ── Vérifier le mode de règlement ──
    var isSplitpay = false;
    var splitRadio = document.getElementById('bk-payment-splitpay');
    if (splitRadio && splitRadio.checked) isSplitpay = true;

    // ═══ MODE SPLITPAY → créer un dossier groupe via REST API ═══
    if (isSplitpay) {
        var spPayload = {
            voyage_id    : BK_DATA.voyage_id,
            voyage_titre : BK_DATA.titre || document.title,
            total        : (parseFloat(BK_DATA.devis.total) || 0) + (window.bk_vol_delta_total || 0) + (window.bk_options_total || 0) + (bk_insurance_check ? (parseFloat(BK_DATA.insurance_total) || 0) : 0),
            acompte_pct  : parseFloat(BK_DATA.acompte_pct) || 30,
            payer_tout   : !!BK_DATA.payer_tout,
            params       : BK_DATA.params,
            devis        : BK_DATA.devis,
            facturation  : {
                prenom  : (document.getElementById('fact-prenom')  || {}).value || '',
                nom     : (document.getElementById('fact-nom')     || {}).value || '',
                email   : (document.getElementById('fact-email')   || {}).value || '',
                tel     : (document.getElementById('fact-tel')     || {}).value || '',
                adresse : (document.getElementById('fact-adresse') || {}).value || '',
                cp      : (document.getElementById('fact-cp')      || {}).value || '',
                ville   : (document.getElementById('fact-ville')   || {}).value || ''
            },
            voyageurs : [],
            nonce     : BK_DATA.nonce
        };
        document.querySelectorAll('.bk-voyageur-row').forEach(function(row) {
            var v = {};
            row.querySelectorAll('input').forEach(function(input) {
                var key = input.name ? input.name.replace(/voyageurs\[\d+\]\[/, '').replace(']','') : '';
                if (key) v[key] = input.value;
            });
            spPayload.voyageurs.push(v);
        });
        spPayload.total = Math.ceil(spPayload.total);
        var btn2 = document.querySelector('.bk-btn-submit');
        if (btn2) { btn2.disabled = true; btn2.textContent = '⏳ Création du dossier…'; }
        function doSplitpaySubmit() {
            jQuery.ajax({
                url: (typeof vs08sp !== 'undefined' && vs08sp.rest_url) ? vs08sp.rest_url + 'create-booking' : BK_DATA.ajax_url.replace('admin-ajax.php','') + '?rest_route=/vs08sp/v1/create-booking',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(spPayload),
                success: function(r) {
                    if (r.success && r.redirect) {
                        window.location.href = r.redirect;
                    } else {
                        alert(r.message || 'Erreur lors de la création du dossier groupe.');
                        if (btn2) { btn2.disabled = false; btn2.textContent = '👥 Créer le dossier groupe →'; }
                    }
                },
                error: function() {
                    alert('Erreur de connexion. Vérifiez votre réseau puis réessayez.');
                    if (btn2) { btn2.disabled = false; btn2.textContent = '👥 Créer le dossier groupe →'; }
                }
            });
        }
        if (BK_DATA.rest_nonce) {
            jQuery.get(BK_DATA.rest_nonce).done(function(res) {
                if (res && res.nonce) spPayload.nonce = res.nonce;
                doSplitpaySubmit();
            }).fail(doSplitpaySubmit);
        } else {
            doSplitpaySubmit();
        }
        return;
    }

    var payModeEl = document.querySelector('input[name="bk-payment-mode"]:checked');
    var payMode = payModeEl ? payModeEl.value : 'card';
    if (payMode === 'agency') {
        var ag = document.getElementById('bk-agence-confirm');
        if (!ag || !ag.checked) {
            alert('Pour un règlement en agence, cochez la case confirmant que le prix n’est pas bloqué tant que le paiement n’est pas effectué.');
            return;
        }
    }
    var cgu = document.getElementById('bk-cgu');
    if (!cgu || !cgu.checked) {
        alert('Veuillez accepter les conditions générales de vente.');
        return;
    }
    var offerInput = document.getElementById('bk-selected-offer-id');
    var hasOffer = offerInput && offerInput.value && offerInput.value.trim() !== '';
    var hasCombos = bk_combos_data && bk_combos_data.length > 0;
    if (hasCombos && !hasOffer) {
        alert('Veuillez s\u00e9lectionner un vol aller-retour \u00e0 l\'\u00e9tape 1 (S\u00e9lection du vol) pour que les horaires figurent sur votre r\u00e9cap.');
        return;
    }

    var data = {
        action               : 'vs08v_booking_submit',
        nonce                : BK_DATA.nonce,
        vs08v_booking_nonce  : BK_DATA.booking_nonce,
        voyage_id            : BK_DATA.voyage_id,
        date_depart     : BK_DATA.params.date_depart,
        aeroport        : BK_DATA.params.aeroport,
        nb_golfeurs     : BK_DATA.params.nb_golfeurs,
        nb_nongolfeurs  : BK_DATA.params.nb_nongolfeurs,
        type_chambre    : BK_DATA.params.type_chambre,
        nb_chambres     : BK_DATA.params.nb_chambres,
        prix_vol        : BK_DATA.params.prix_vol,
        airline_iata    : (window.bk_selected_combo && window.bk_selected_combo.aller && window.bk_selected_combo.aller.airline_iata) ? window.bk_selected_combo.aller.airline_iata : (BK_DATA.params.airline_iata || ''),
        selected_offer_id: (document.getElementById('bk-selected-offer-id') || {}).value || '',
        vol_delta_pax   : (document.getElementById('bk-selected-vol-delta') || {}).value || '0',
        vol_aller_depart : (window.bk_selected_combo && window.bk_selected_combo.aller) ? window.bk_selected_combo.aller.depart_time  : '',
        vol_aller_arrivee: (window.bk_selected_combo && window.bk_selected_combo.aller) ? window.bk_selected_combo.aller.arrive_time  : '',
        vol_aller_num    : (window.bk_selected_combo && window.bk_selected_combo.aller) ? window.bk_selected_combo.aller.flight_number : '',
        vol_aller_cie    : (window.bk_selected_combo && window.bk_selected_combo.aller) ? window.bk_selected_combo.aller.airline_name  : '',
        vol_retour_depart : (window.bk_selected_combo && window.bk_selected_combo.retour) ? window.bk_selected_combo.retour.depart_time  : '',
        vol_retour_arrivee: (window.bk_selected_combo && window.bk_selected_combo.retour) ? window.bk_selected_combo.retour.arrive_time  : '',
        vol_retour_num    : (window.bk_selected_combo && window.bk_selected_combo.retour) ? window.bk_selected_combo.retour.flight_number : '',
        assurance       : bk_insurance_check ? '1' : '0',
        fact_prenom     : (document.getElementById('fact-prenom')  || {}).value || '',
        fact_nom        : (document.getElementById('fact-nom')     || {}).value || '',
        fact_email      : (document.getElementById('fact-email')   || {}).value || '',
        fact_tel        : (document.getElementById('fact-tel')     || {}).value || '',
        fact_adresse    : (document.getElementById('fact-adresse') || {}).value || '',
        fact_cp         : (document.getElementById('fact-cp')      || {}).value || '',
        fact_ville      : (document.getElementById('fact-ville')   || {}).value || '',
        vs08_payment_mode : payMode,
        vs08_agence_confirm : (payMode === 'agency' && document.getElementById('bk-agence-confirm') && document.getElementById('bk-agence-confirm').checked) ? '1' : '',
    };

    // Voyageurs
    document.querySelectorAll('.bk-voyageur-row').forEach(function(row) {
        row.querySelectorAll('input').forEach(function(input) {
            if (input.name) data[input.name] = input.value;
        });
    });

    // Bagages
    var nbBagSoute = document.getElementById('bk-nb-bagage-soute');
    var nbBagGolf  = document.getElementById('bk-nb-bagage-golf');
    if (nbBagSoute) data['nb_bagage_soute'] = nbBagSoute.value;
    if (nbBagGolf)  data['nb_bagage_golf']  = nbBagGolf.value;

    var btn = document.querySelector('.bk-btn-submit');
    if (btn) { btn.disabled = true; btn.textContent = '⏳ Traitement en cours…'; }

    function onBookingDone(res) {
        if (res && res.success && res.data && res.data.redirect) {
            window.location.href = res.data.redirect;
        } else {
            alert((res && res.data) ? res.data : 'Erreur lors de la soumission. Veuillez réessayer.');
            if (btn) { btn.disabled = false; btn.textContent = '🔒 Procéder au paiement →'; }
        }
    }
    function onBookingFail() {
        alert('Erreur de connexion. Vérifiez votre réseau puis réessayez. Si le problème persiste, contactez-nous au 03 26 65 28 63.');
        if (btn) { btn.disabled = false; btn.textContent = '🔒 Procéder au paiement →'; }
    }

    var submitUrl = BK_DATA.rest_booking || BK_DATA.ajax_url;
    function doSubmit() {
        jQuery.post(submitUrl, data).done(onBookingDone).fail(function() {
            if (BK_DATA.ajax_url && submitUrl === BK_DATA.rest_booking) {
                jQuery.post(BK_DATA.ajax_url, data).done(onBookingDone).fail(onBookingFail);
            } else {
                onBookingFail();
            }
        });
    }

    // Rafraîchir le nonce avant envoi pour éviter « Session expirée » si la page est restée ouverte longtemps
    if (BK_DATA.rest_nonce) {
        jQuery.get(BK_DATA.rest_nonce).done(function(res) {
            if (res && res.nonce) {
                data.nonce = res.nonce;
                data.vs08v_booking_nonce = res.booking_nonce || data.vs08v_booking_nonce;
            }
            doSubmit();
        }).fail(doSubmit);
    } else {
        doSubmit();
    }
}

// ═══ SPLITPAY : listeners radio → changer texte bouton ═══
(function() {
    var radios = document.querySelectorAll('input[name="bk-payment-mode"]');
    if (!radios.length) return;
    var btn = document.querySelector('.bk-btn-submit');
    var splitLabel = document.getElementById('bk-mode-split-label');
    radios.forEach(function(r) {
        r.addEventListener('change', function() {
            if (this.value === 'splitpay') {
                if (btn) btn.textContent = '👥 Créer le dossier groupe →';
                if (splitLabel) { splitLabel.style.borderColor = '#59b7b7'; splitLabel.style.background = '#f0fafa'; }
            } else {
                if (btn) btn.textContent = '🔒 Procéder au paiement →';
                if (splitLabel) { splitLabel.style.borderColor = '#ddd'; splitLabel.style.background = '#fff'; }
            }
        });
    });
})();

// Init bagages : au démarrage, les bagages sont inclus au prix par défaut → delta = 0
bk_options_total = 0;

// Init : afficher les valeurs du recap
bkUpdateTotal();

(function() {
    function syncBkAgenceWrap() {
        var agency = document.getElementById('bk-payment-agency');
        var w = document.getElementById('bk-agence-confirm-wrap');
        if (w && agency) {
            w.style.display = agency.checked ? 'block' : 'none';
        }
    }
    document.querySelectorAll('input[name="bk-payment-mode"]').forEach(function(r) {
        r.addEventListener('change', syncBkAgenceWrap);
    });
    syncBkAgenceWrap();
})();

// ══════════════════════════════════════════════════════════════════════════════
// SHOW MORE + FILTRES — Wrapper autour de bkRenderCombos
// ══════════════════════════════════════════════════════════════════════════════
var bk_all_combos = [];
var bkBaseRenderCombos = bkRenderCombos;

bkRenderCombos = function(combos) {
    bk_all_combos = combos || [];
    bkBaseRenderCombos(combos);

    // Show-more: masquer au-delà de 3
    var list = document.getElementById('bk-combo-list');
    if (!list) return;
    var cards = list.querySelectorAll('.combo-card');
    var VISIBLE = 3;
    var old = list.querySelector('.bk-show-more');
    if (old) old.remove();
    if (cards.length > VISIBLE) {
        for (var i = VISIBLE; i < cards.length; i++) {
            cards[i].classList.add('bk-flights-hidden');
        }
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'bk-show-more';
        var hidden = cards.length - VISIBLE;
        btn.textContent = 'Voir ' + hidden + ' autre' + (hidden > 1 ? 's' : '') + ' combinaison' + (hidden > 1 ? 's' : '') + ' ▾';
        btn.addEventListener('click', function() {
            list.querySelectorAll('.combo-card.bk-flights-hidden').forEach(function(c) { c.classList.remove('bk-flights-hidden'); });
            btn.remove();
        });
        list.appendChild(btn);
    }

    // Compteurs filtres
    bkUpdateFilterCounts(combos);
    bkPositionSidebar();
};

function bkUpdateFilterCounts(combos) {
    if (!combos) combos = bk_all_combos;
    var nAll = combos.length, nDirect = 0, nEscale = 0;
    combos.forEach(function(c) {
        var isDirect = (!c.aller || !c.aller.stops || c.aller.stops === 0) && (!c.retour || !c.retour.stops || c.retour.stops === 0);
        if (isDirect) nDirect++; else nEscale++;
    });
    var eAll = document.getElementById('bkf-n-all');
    var eDirect = document.getElementById('bkf-n-direct');
    var eEscale = document.getElementById('bkf-n-escale');
    if (eAll) eAll.textContent = nAll;
    if (eDirect) eDirect.textContent = nDirect;
    if (eEscale) eEscale.textContent = nEscale;
}

function bkApplyFilters() {
    if (!bk_all_combos.length) return;
    var typeVal = document.querySelector('input[name="bkf_type"]:checked');
    var sortVal = document.querySelector('input[name="bkf_sort"]:checked');
    var type = typeVal ? typeVal.value : 'all';
    var sort = sortVal ? sortVal.value : 'price';

    var filtered = bk_all_combos.filter(function(c) {
        if (type === 'all') return true;
        var isDirect = (!c.aller || !c.aller.stops || c.aller.stops === 0) && (!c.retour || !c.retour.stops || c.retour.stops === 0);
        return type === 'direct' ? isDirect : !isDirect;
    });

    filtered.sort(function(a, b) {
        if (sort === 'price') return (a.total_delta || 0) - (b.total_delta || 0);
        if (sort === 'duration') {
            var da = (a.aller ? a.aller.duration_min || 0 : 0) + (a.retour ? a.retour.duration_min || 0 : 0);
            var db = (b.aller ? b.aller.duration_min || 0 : 0) + (b.retour ? b.retour.duration_min || 0 : 0);
            return da - db;
        }
        if (sort === 'depart') {
            return (a.aller ? a.aller.depart_time || '' : '').localeCompare(b.aller ? b.aller.depart_time || '' : '');
        }
        return 0;
    });

    bkBaseRenderCombos(filtered);

    // Re-apply show-more
    var list = document.getElementById('bk-combo-list');
    if (list) {
        var cards = list.querySelectorAll('.combo-card');
        var VISIBLE = 3;
        var old = list.querySelector('.bk-show-more');
        if (old) old.remove();
        if (cards.length > VISIBLE) {
            for (var j = VISIBLE; j < cards.length; j++) cards[j].classList.add('bk-flights-hidden');
            var btn2 = document.createElement('button');
            btn2.type = 'button'; btn2.className = 'bk-show-more';
            var h = cards.length - VISIBLE;
            btn2.textContent = 'Voir ' + h + ' autre' + (h > 1 ? 's' : '') + ' ▾';
            btn2.addEventListener('click', function() {
                list.querySelectorAll('.combo-card.bk-flights-hidden').forEach(function(c) { c.classList.remove('bk-flights-hidden'); });
                btn2.remove();
            });
            list.appendChild(btn2);
        }
    }
}

document.querySelectorAll('input[name="bkf_type"],input[name="bkf_sort"]').forEach(function(el) {
    el.addEventListener('change', bkApplyFilters);
});
var bkfReset = document.getElementById('bkf-reset');
if (bkfReset) {
    bkfReset.addEventListener('click', function() {
        document.querySelectorAll('input[name="bkf_type"][value="all"],input[name="bkf_sort"][value="price"]').forEach(function(r) { r.checked = true; });
        bkApplyFilters();
    });
}

// ══════════════════════════════════════════════════════════════════════════════
// SIDEBAR POSITION — marge gauche, stop avant footer
// ══════════════════════════════════════════════════════════════════════════════
function bkPositionSidebar() {
    var sidebar = document.getElementById('bk-filters-sidebar');
    var inner = document.querySelector('.bk-inner');
    if (!sidebar || !inner) return;
    var step1 = document.getElementById('bk-step-1');
    // Afficher uniquement sur l'étape 1
    if (!step1 || step1.style.display === 'none') { sidebar.style.display = 'none'; return; }

    var rect = inner.getBoundingClientRect();
    var sidebarW = 200, gap = 18;
    var spaceLeft = rect.left - gap - sidebarW;
    if (spaceLeft >= 10) {
        sidebar.style.left = (rect.left - gap - sidebarW) + 'px';
        sidebar.style.display = '';
    } else {
        sidebar.style.display = 'none';
    }

    // Stop before footer
    var footer = document.querySelector('.ft-wrap') || document.querySelector('footer');
    if (footer) {
        var fTop = footer.getBoundingClientRect().top;
        var sH = sidebar.offsetHeight;
        if (fTop < sH + 200) {
            sidebar.style.opacity = '0'; sidebar.style.pointerEvents = 'none';
        } else {
            sidebar.style.opacity = '1'; sidebar.style.pointerEvents = '';
        }
    }
}
window.addEventListener('scroll', bkPositionSidebar, {passive: true});
window.addEventListener('resize', bkPositionSidebar);

// ══════════════════════════════════════════════════════════════════════════════
// AUTO-NAVIGATION — retour après connexion/inscription (?step=X dans l'URL)
// ══════════════════════════════════════════════════════════════════════════════
(function() {
    var urlParams = new URLSearchParams(window.location.search);
    var targetStep = parseInt(urlParams.get('step'), 10);
    if (targetStep >= 2 && targetStep <= 4) {
        // Navigation directe sans validation (l'utilisateur revient juste de l'inscription)
        for (var i = 1; i <= 4; i++) {
            var el = document.getElementById('bk-step-' + i);
            if (el) el.style.display = (i === targetStep) ? 'block' : 'none';
        }
        for (var i = 1; i <= 4; i++) {
            var ind  = document.getElementById('bk-ind-' + i);
            var line = document.getElementById('bk-line-' + (i - 1));
            if (ind) {
                ind.className = 'bk-step';
                if (i < targetStep) ind.classList.add('done');
                if (i === targetStep) ind.classList.add('active');
            }
            if (line) {
                line.className = 'bk-step-line';
                if (i <= targetStep) line.classList.add('done');
            }
        }
        window.scrollTo({ top: 0, behavior: 'instant' });
    }
})();

// ══════════════════════════════════════════════════════════════════════════════
// BOUTON RECAP — s'adapte à l'étape en cours
// ══════════════════════════════════════════════════════════════════════════════
(function() {
    var recapBtn = document.getElementById('bk-recap-btn');
    var recapWrap = document.getElementById('bk-recap-btn-wrap');
    if (!recapBtn || !recapWrap) return;

    function updateRecapBtn() {
        var s1 = document.getElementById('bk-step-1');
        var s2 = document.getElementById('bk-step-2');
        var s3 = document.getElementById('bk-step-3');
        var s4 = document.getElementById('bk-step-4');

        if (s1 && s1.style.display !== 'none') {
            recapBtn.textContent = 'Continuer →';
            recapBtn.onclick = function() { bkGo(2); };
            recapWrap.style.display = 'block';
        } else if (s2 && s2.style.display !== 'none') {
            recapBtn.textContent = 'Continuer →';
            recapBtn.onclick = function() { bkGo(3); };
            recapWrap.style.display = 'block';
        } else if (s3 && s3.style.display !== 'none') {
            recapBtn.textContent = 'Vérifier et confirmer →';
            recapBtn.onclick = function() { bkGo(4); };
            recapWrap.style.display = 'block';
        } else {
            recapWrap.style.display = 'none';
        }
        bkPositionSidebar();
    }

    // Observer les changements de step
    var origBkGo = window.bkGo;
    window.bkGo = function(step) {
        origBkGo(step);
        setTimeout(updateRecapBtn, 100);
    };
    updateRecapBtn();
})();

</script>

<?php get_footer(); ?>