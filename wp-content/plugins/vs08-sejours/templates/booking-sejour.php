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
.bks-combo-card{border:2px solid #e5e7eb;border-radius:14px;padding:16px;margin-bottom:10px;cursor:pointer;transition:all .2s;font-family:'Outfit',sans-serif}
.bks-combo-card:hover{border-color:#59b7b7;background:#fafffe}
.bks-combo-card.selected{border-color:#59b7b7;background:#edf8f8;box-shadow:0 0 0 3px rgba(89,183,183,.15)}
.bks-combo-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
.bks-combo-airline{font-weight:700;font-size:14px;color:#0f2424}
.bks-combo-delta{font-weight:700;font-size:13px;color:#b85c1a}
.bks-combo-delta.ref{color:#2d8a5a;background:#e8f8f0;padding:3px 10px;border-radius:100px;font-size:11px}
.bks-combo-legs{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.bks-combo-leg{font-size:12px;color:#4a5568}
.bks-combo-leg strong{color:#0f2424;font-size:13px}
.bks-combo-badge{display:inline-block;padding:2px 8px;border-radius:100px;font-size:10px;font-weight:700;margin-left:6px}
.bks-combo-badge.direct{background:#e8f8f0;color:#2d8a5a}
.bks-combo-badge.escale{background:#fef3c7;color:#92400e}
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
.bks-ins-wrap{background:#f0f9ff;border:2px solid #bae6fd;border-radius:14px;padding:20px;margin-top:20px}
.bks-ins-footer{display:flex;justify-content:space-between;align-items:center;margin-top:12px;padding-top:12px;border-top:1px solid #bae6fd}
@media(max-width:900px){.bks-container{grid-template-columns:1fr}.bks-recap{position:static}}
@media(max-width:600px){.bks-container{padding:16px}.bks-field-row{grid-template-columns:1fr}.bks-header{padding:16px}.bks-section{padding:20px}.bks-combo-legs{grid-template-columns:1fr}}
</style>

<div class="bks-page">
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

            <div style="display:flex;justify-content:space-around;align-items:center;background:#f9f6f0;border-radius:12px;padding:14px;margin-bottom:16px;font-family:'Outfit',sans-serif">
                <div style="text-align:center"><div style="font-size:18px;font-weight:800;color:#0f2424"><?php echo esc_html($params['aeroport']); ?></div><div style="font-size:11px;color:#6b7280">Départ</div></div>
                <div style="font-size:20px;color:#59b7b7">✈️ ⟷</div>
                <div style="text-align:center"><div style="font-size:18px;font-weight:800;color:#0f2424"><?php echo esc_html($iata_dest); ?></div><div style="font-size:11px;color:#6b7280">Arrivée</div></div>
            </div>
            <div style="text-align:center;font-size:12px;color:#6b7280;font-family:'Outfit',sans-serif;margin-bottom:16px"><?php echo $date_fmt; ?> → <?php echo $date_retour_fmt; ?> · <?php echo $nb_total; ?> passager<?php echo $nb_total>1?'s':''; ?></div>

            <div id="bks-combo-wrap">
                <div class="bks-combo-loading" id="bks-combo-loading"><div class="bks-combo-spinner"></div>Recherche des vols aller-retour…</div>
                <div id="bks-combo-list"></div>
                <div id="bks-no-flights" class="bks-no-flights" style="display:none">❌ Aucun vol trouvé pour ces dates. Essayez une autre date.</div>
            </div>
            <input type="hidden" id="bks-selected-vol-price" value="0">
        </div>

        <!-- Assurance -->
        <?php if ($insurance_price > 0): ?>
        <div class="bks-section">
            <div class="bks-ins-wrap">
                <div style="font-weight:700;color:#0369a1;font-size:14px;font-family:'Outfit',sans-serif;margin-bottom:8px">🛡️ Assurance Multirisque GALAXY · Assurever</div>
                <p style="font-size:13px;color:#374151;font-family:'Outfit',sans-serif;line-height:1.6;margin:0">Annulation, rapatriement 24h/24, frais médicaux, bagages.</p>
                <div class="bks-ins-footer">
                    <label style="display:flex;gap:8px;cursor:pointer;align-items:center">
                        <input type="checkbox" id="bks-assurance" onchange="bksUpdateTotal()">
                        <span style="font-size:13px;font-family:'Outfit',sans-serif;color:#0369a1;font-weight:600">Oui, je souhaite être protégé(e)</span>
                    </label>
                    <div style="text-align:right;font-family:'Outfit',sans-serif">
                        <div style="font-size:16px;font-weight:800;color:#0369a1"><?php echo number_format($insurance_price * $nb_total, 2, ',', ' '); ?> €</div>
                        <div style="font-size:11px;color:#6b7280"><?php echo number_format($insurance_price, 2, ',', ' '); ?> €/pers</div>
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
        <div class="bks-recap-line" id="bks-recap-vol" style="display:none"><span>✈️ Vol sélectionné</span><span id="bks-recap-vol-val">—</span></div>
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
        }).catch(function(err){
            document.getElementById('bks-combo-loading').style.display='none';
            document.getElementById('bks-no-flights').style.display='block';
            document.getElementById('bks-no-flights').textContent='❌ Erreur: '+err.message;
        });
    }

    function renderCombos(flights){
        var list=document.getElementById('bks-combo-list');
        var shown=flights.slice(0,6);
        var html='';
        shown.forEach(function(f,idx){
            var airline = f.airline_name || '';
            var isRef = f.is_reference || idx===0;
            var delta = parseFloat(f.delta_per_pax || 0);

            // Aller : depart_time, arrive_time, flight_number
            var allerDep = f.depart_time || '';
            var allerArr = f.arrive_time || '';
            var allerFlight = f.flight_number || '';

            // Retour : retour_depart, retour_arrive, retour_flight
            var retourDep = f.retour_depart || '';
            var retourArr = f.retour_arrive || '';
            var retourFlight = f.retour_flight || '';

            var connBadge = f.has_connections ? '<span class="bks-combo-badge escale">1 escale</span>' : '<span class="bks-combo-badge direct">Direct</span>';
            var priceHtml = isRef ? '<span class="bks-combo-delta ref">Meilleur prix</span>' : '<span class="bks-combo-delta">+' + fmt(delta) + ' €/pers</span>';

            html += '<div class="bks-combo-card' + (idx===0?' selected':'') + '" id="bks-combo-' + idx + '" onclick="bksSelectCombo(' + idx + ')">'
                + '<div class="bks-combo-top"><span class="bks-combo-airline">' + (airline||'Vol') + (allerFlight ? ' · '+allerFlight : '') + ' ' + connBadge + '</span>' + priceHtml + '</div>'
                + '<div class="bks-combo-legs">'
                + '<div class="bks-combo-leg">Aller: <strong>' + (allerDep||'—') + ' → ' + (allerArr||'—') + '</strong></div>'
                + '<div class="bks-combo-leg">Retour: <strong>' + (retourDep||'—') + ' → ' + (retourArr||'—') + '</strong>' + (retourFlight ? ' · '+retourFlight : '') + '</div>'
                + '</div></div>';
        });
        if(flights.length > 6) html += '<div style="text-align:center;font-size:12px;color:#6b7280;font-family:Outfit,sans-serif;padding:8px">+' + (flights.length-6) + ' autres vols disponibles</div>';
        list.innerHTML = html;
    }

    window.bksSelectCombo=function(idx){
        var f=comboData[idx]; if(!f) return;
        selectedCombo=f;
        volPricePax=parseFloat(f.price_per_pax || 0);
        document.getElementById('bks-selected-vol-price').value=volPricePax;
        document.querySelectorAll('.bks-combo-card').forEach(function(c){c.classList.remove('selected')});
        var card=document.getElementById('bks-combo-'+idx);
        if(card) card.classList.add('selected');
        document.getElementById('bks-btn-step1').disabled=false;
        // Update recap
        var volRow=document.getElementById('bks-recap-vol');
        if(volRow){volRow.style.display='flex';document.getElementById('bks-recap-vol-val').textContent=(f.airline_name||'Vol')+' · '+fmt(volPricePax)+'€/p'}
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
            body:JSON.stringify({sejour_id:BK.sejour_id,date_depart:BK.date_depart,aeroport:BK.aeroport,nb_adultes:BK.nb_total,nb_chambres:BK.nb_chambres,vol_price:volPricePax,hotel_net:BK.hotel_net,hotel_rate_key:BK.hotel_rate_key,hotel_board:BK.hotel_board,assurance:chk&&chk.checked?1:0})
        }).then(function(r){return r.json()}).then(function(d){
            document.getElementById('bks-recap-total').textContent=fmt(d.total)+' €';
            document.getElementById('bks-recap-pp').textContent=fmt(d.prix_par_personne)+' €/pers.';
            if(!d.payer_tout&&d.acompte<d.total){document.getElementById('bks-recap-acompte').style.display='flex';document.getElementById('bks-recap-acompte-val').textContent=fmt(d.acompte)+' €'}
            BK._devis=d;
        });
    }
    window.bksUpdateTotal=bksUpdateTotal;

    // ── Navigation ──
    function showStep(n){document.querySelectorAll('.bks-step-page').forEach(function(p){p.classList.remove('bks-step-active')});var el=document.getElementById('bks-step-'+n);if(el)el.classList.add('bks-step-active');window.scrollTo({top:0,behavior:'smooth'})}
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
