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
.bkc-combo-leg{display:flex;align-items:center;gap:10px;padding:6px 0;font-family:'Outfit',sans-serif;font-size:12px;color:#4a5568}
.bkc-combo-leg-badge{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:1px;padding:3px 8px;border-radius:6px;flex-shrink:0}
.bkc-combo-leg-badge.aller{background:#edf8f8;color:#3d9a9a}
.bkc-combo-leg-badge.retour{background:#fff3e8;color:#b85c1a}
.bkc-combo-leg-times{display:flex;align-items:center;gap:6px;flex:1}
.bkc-combo-leg-line{flex:1;display:flex;align-items:center;gap:4px}
.bkc-combo-leg-dash{flex:1;height:1px;background:#ddd}
.bkc-combo-leg-plane{font-size:12px;color:#59b7b7}
.bkc-combo-leg-dur{font-size:10px;color:#9ca3af;min-width:40px;text-align:center}
.bkc-combo-leg-num{font-size:10px;color:#bbb;min-width:50px;text-align:right}
.bkc-combo-more{text-align:center;padding:11px;font-size:14px;font-weight:600;color:#59b7b7;cursor:pointer;border-top:1px solid #e5e7eb;transition:color .15s;background:#fafafa;border-radius:0 0 14px 14px}
.bkc-combo-more:hover{color:#0f2424}
.bkc-flights-hidden{display:none}
@media(max-width:960px){.bkc-inner{grid-template-columns:1fr;padding:0 20px}.bkc-header{grid-column:span 1}.bkc-recap{position:static}}
@media(max-width:640px){.bkc-inner{padding:0 14px}.bkc-field-row{grid-template-columns:1fr}.bkc-fact-grid{grid-template-columns:1fr}.bkc-fact-grid .full{grid-column:span 1}.bkc-header{flex-direction:column;text-align:center}.bkc-header-chips{justify-content:center}.bkc-route-iata{font-size:20px}.bkc-route-header{padding:10px 14px;gap:10px}}
</style>

<div class="bkc-wrap"><div class="bkc-inner">

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

        <!-- ÉTAPE 1 : Sélection du vol -->
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
                <div id="bkc-combo-error" class="bkc-flights-error" style="display:none"></div>
            </div>

            <input type="hidden" id="bkc-selected-offer-id" name="selected_offer_id" value="">
            <input type="hidden" id="bkc-selected-vol-delta" name="vol_delta_pax" value="0">
        </div>

        <!-- ÉTAPE 2 : Voyageurs groupés par chambre -->
        <div class="bkc-section">
            <h3 class="bkc-section-title"><span class="bkc-step-num">2</span> Informations voyageurs</h3>
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

        <!-- ÉTAPE 2 : Facturation -->
        <div class="bkc-section">
            <h3 class="bkc-section-title"><span class="bkc-step-num">3</span> Coordonnées de facturation</h3>
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

        <!-- ÉTAPE 4 : CONFIRMATION — Récap complet avant paiement (comme golf) -->
        <div class="bkc-section">
            <h3 class="bkc-section-title"><span class="bkc-step-num">4</span> Confirmation de votre réservation</h3>
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
        </div>
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
        <div style="height:12px"></div>
        <?php foreach ($devis['lines'] as $line): ?>
        <div class="bkc-recap-line"><span><?php echo esc_html($line['label']); ?></span><span><?php echo number_format($line['montant'], 0, ',', ' '); ?> €</span></div>
        <?php endforeach; ?>
        <div class="bkc-recap-sep"></div>
        <div class="bkc-recap-total">
            <span class="bkc-recap-total-lbl">Total circuit</span>
            <span class="bkc-recap-total-val"><?php echo number_format($devis['total'], 0, ',', ' '); ?> €</span>
        </div>
        <div style="font-size:11px;color:#6b7280;text-align:right;font-family:'Outfit',sans-serif;margin-top:2px">soit <?php echo number_format($devis['par_pers'], 0, ',', ' '); ?> €/pers.</div>
        <?php if (!$payer_tout && ($devis['acompte'] ?? 0) < ($devis['total'] ?? 0)): ?>
        <div class="bkc-recap-acompte"><span>🔒 Acompte <?php echo intval($acompte_pct); ?>%</span><span><?php echo number_format($devis['acompte'], 0, ',', ' '); ?> €</span></div>
        <div style="font-size:11px;color:#6b7280;font-family:'Outfit',sans-serif;margin-top:4px">Solde à régler <?php echo $delai_solde; ?> jours avant le départ</div>
        <?php endif; ?>
        <div class="bkc-loading" id="bkc-loading"><div class="bkc-spinner"></div><div style="font-size:13px;color:#59b7b7;font-family:'Outfit',sans-serif">Création de votre réservation…</div></div>
        <button type="button" class="bkc-btn-submit" id="bkc-btn-submit" onclick="bkcSubmit()">🔒 Procéder au paiement →</button>
        <div class="bkc-security">Paiement sécurisé 3D Secure · APST · Atout France</div>
    </div>

</div></div>

<script>
(function(){
    var BK = BK_CIRCUIT;
    var submitting = false;

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

        var dateRetour = BK.date_retour || bkcAddDays(date, BK.duree || 7);

        function bkcPostFlight(payload, done) {
            var ajaxData = { action: 'vs08c_get_flight', nonce: BK.nonce };
            for (var k in payload) ajaxData[k] = payload[k];
            jQuery.post(BK.ajax_url, ajaxData).done(done).fail(function(){ done({ success: false }); });
        }

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
            bkcTryBuildCombos();
        });

        bkcPostFlight({
            circuit_id: BK.circuit_id,
            date: dateRetour,
            aeroport: BK.iata_dest,
            destination: aero,
            passengers: bkc_vol_nb_pax
        }, function(res){
            if (res && res.success && res.data && res.data.flights && res.data.flights.length) {
                bkc_retour_data = res.data.flights;
            }
            bkc_retour_loaded = true;
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
            if (errDiv) { errDiv.style.display = 'block'; errDiv.textContent = 'Aucun vol direct disponible pour cette combinaison. Contactez-nous au 03 26 65 28 63.'; }
            return;
        }

        var combos = [];
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

        combos.sort(function(a,b){ return a.total_delta - b.total_delta; });
        if (combos.length) combos[0].is_reference = true;
        bkc_combos_data = combos;
        bkcRenderCombos(combos);
        bkcSelectCombo(0);
    }

    function bkcRenderCombos(combos) {
        var list = document.getElementById('bkc-combo-list');
        if (!list) return;
        list.innerHTML = '';

        combos.forEach(function(c, idx) {
            var a = c.aller, r = c.retour;
            var hidden = idx >= 3 ? ' bkc-flights-hidden' : '';
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
                +'<div class="bkc-combo-leg-badge aller">Aller</div>'
                +'<div class="bkc-combo-leg-times">'
                +'<div>'+bkcEsc(a.depart_time)+'</div>'
                +'<div class="bkc-combo-leg-line"><div class="bkc-combo-leg-dash"></div><div class="bkc-combo-leg-plane">✈</div><div class="bkc-combo-leg-dash"></div></div>'
                +'<div>'+bkcEsc(a.arrive_time)+'</div>'
                +'</div>'
                +'<div class="bkc-combo-leg-dur">'+bkcFmtDuration(a.duration_min)+'</div>'
                +'<div class="bkc-combo-leg-num">'+bkcEsc(a.flight_number)+'</div>'
                +'</div>';

            if (r) {
                html += '<div class="bkc-combo-leg">'
                    +'<div class="bkc-combo-leg-badge retour">Retour</div>'
                    +'<div class="bkc-combo-leg-times">'
                    +'<div>'+bkcEsc(r.depart_time)+'</div>'
                    +'<div class="bkc-combo-leg-line"><div class="bkc-combo-leg-dash"></div><div class="bkc-combo-leg-plane">✈</div><div class="bkc-combo-leg-dash"></div></div>'
                    +'<div>'+bkcEsc(r.arrive_time)+'</div>'
                    +'</div>'
                    +'<div class="bkc-combo-leg-dur">'+bkcFmtDuration(r.duration_min)+'</div>'
                    +'<div class="bkc-combo-leg-num">'+bkcEsc(r.flight_number)+'</div>'
                    +'</div>';
            }

            var card = document.createElement('div');
            card.className = 'bkc-combo-card' + hidden;
            card.id = 'bkc-combo-' + idx;
            card.innerHTML = html;
            card.addEventListener('click', (function(i){ return function(){ bkcSelectCombo(i); }; })(idx));
            list.appendChild(card);
        });

        if (combos.length > 3) {
            var more = document.createElement('div');
            more.className = 'bkc-combo-more';
            var extra = combos.length - 3;
            more.textContent = 'Afficher ' + extra + ' autre(s) combinaison(s) ▾';
            more.setAttribute('data-expanded', 'false');
            more.addEventListener('click', function() {
                var exp = this.getAttribute('data-expanded') === 'true';
                if (exp) {
                    list.querySelectorAll('.bkc-combo-card').forEach(function(el,i){ if(i>=3) el.classList.add('bkc-flights-hidden'); });
                    this.textContent = 'Afficher ' + extra + ' autre(s) combinaison(s) ▾';
                    this.setAttribute('data-expanded','false');
                } else {
                    list.querySelectorAll('.bkc-flights-hidden').forEach(function(el){ el.classList.remove('bkc-flights-hidden'); });
                    this.textContent = 'Réduire ▴';
                    this.setAttribute('data-expanded','true');
                }
            });
            list.appendChild(more);
        }
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
                retourDetail.textContent = (BK.iata_dest||'') + ' → ' + (BK.params.aeroport||'') + rAirline + rTimes;
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
            html += '<div style="' + rowS + '"><span style="' + lblS + '">Vol retour (' + bkcEsc(BK.iata_dest) + ' → ' + bkcEsc(BK.params.aeroport) + ')</span><span style="' + valS + '">' + retourDetail.textContent.trim() + '</span></div>';
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

        var total = Math.ceil(parseFloat(BK.devis.total) || 0);
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

    bkcBuildRecap();

    /* ── Submit ── */
    window.bkcSubmit = function() {
        var errEl = document.getElementById('bkc-error');
        bkcBuildRecap();
        if (!document.getElementById('bkc-confirm-info').checked) { alert("Veuillez certifier l'exactitude des informations voyageurs."); return; }
        if (!document.getElementById('bkc-cgu').checked) { alert('Veuillez accepter les conditions générales de vente et la politique de confidentialité.'); return; }

        var missing = false;
        document.querySelectorAll('.bkc-required').forEach(function(el){ if(!el.value.trim()){el.style.borderColor='#dc2626';missing=true;}else{el.style.borderColor='';} });
        if (missing) { errEl.textContent='Veuillez remplir tous les champs obligatoires (*).'; errEl.style.display='block'; window.scrollTo({top:errEl.offsetTop-120,behavior:'smooth'}); return; }
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
            fact_ville:(document.getElementById('fact-ville')||{}).value||''
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
