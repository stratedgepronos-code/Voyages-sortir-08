<?php
/**
 * Tunnel de réservation Séjour All Inclusive
 * URL: /reservation-sejour/{sejour_id}/
 * Calqué sur booking-circuit.php
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

$params = [
    'date_depart'   => sanitize_text_field($_GET['date_depart'] ?? ''),
    'aeroport'      => strtoupper(sanitize_text_field($_GET['aeroport'] ?? '')),
    'nb_adultes'    => max(1, intval($_GET['nb_adultes'] ?? 2)),
    'nb_chambres'   => max(1, intval($_GET['nb_chambres'] ?? 1)),
    'vol_price'     => floatval($_GET['vol_price'] ?? 0),
    'vol_offer_id'  => sanitize_text_field($_GET['vol_offer_id'] ?? ''),
    'hotel_net'     => floatval($_GET['hotel_net'] ?? 0),
    'hotel_rate_key' => sanitize_text_field($_GET['hotel_rate_key'] ?? ''),
    'hotel_board'   => sanitize_text_field($_GET['hotel_board'] ?? 'AI'),
];
$nb_total    = $params['nb_adultes'];
$nb_chambres = $params['nb_chambres'];
$date_fmt    = $params['date_depart'] ? date('d/m/Y', strtotime($params['date_depart'])) : '';
$date_retour_fmt = $params['date_depart'] ? date('d/m/Y', strtotime($params['date_depart'].' +'.$duree.' days')) : '';

// Calculer le devis côté serveur
$devis = VS08S_Calculator::compute($sejour_id, $params);

$insurance_price = 0;
if (class_exists('VS08V_Insurance')) {
    try { $insurance_price = VS08V_Insurance::get_price($devis['prix_par_personne'] ?? 0); } catch (\Throwable $e) {}
}

$bk_saved_fact = [];
$bk_saved_voy  = [];
if (is_user_logged_in() && class_exists('VS08V_Traveler_Space')) {
    try {
        $bk_saved_fact = VS08V_Traveler_Space::get_saved_facturation();
        $bk_saved_voy  = VS08V_Traveler_Space::get_saved_voyageurs();
    } catch (\Throwable $e) {}
}

$acompte_pct = floatval($m['acompte_pct'] ?? 30);
$delai_solde = intval($m['delai_solde'] ?? 30);
$payer_tout  = false;
if ($params['date_depart']) {
    if ((strtotime($params['date_depart']) - time()) / 86400 <= $delai_solde) $payer_tout = true;
}

// Répartition voyageurs par chambre
$voy_par_chambre = [];
for ($c = 1; $c <= $nb_chambres; $c++) $voy_par_chambre[$c] = [];
for ($i = 0; $i < $nb_total; $i++) { $ch = ($i % $nb_chambres) + 1; $voy_par_chambre[$ch][] = $i; }

$iata_dest = strtoupper($m['iata_dest'] ?? '');

get_header();
?>

<script>var BK_SEJOUR=<?php echo json_encode([
    'sejour_id'     => $sejour_id,
    'titre'         => $titre,
    'nb_total'      => $nb_total,
    'nb_chambres'   => $nb_chambres,
    'date_depart'   => $params['date_depart'],
    'aeroport'      => $params['aeroport'],
    'iata_dest'     => $iata_dest,
    'duree'         => $duree,
    'vol_price'     => $params['vol_price'],
    'vol_offer_id'  => $params['vol_offer_id'],
    'hotel_net'     => $params['hotel_net'],
    'hotel_rate_key'=> $params['hotel_rate_key'],
    'hotel_board'   => $params['hotel_board'],
    'total'         => $devis['total'],
    'acompte'       => $devis['acompte'],
    'payer_tout'    => $payer_tout,
    'insurance_total' => $insurance_price * $nb_total,
    'insurance_pax' => $insurance_price,
    'rest_url'      => rest_url('vs08s/v1/'),
    'ajax_url'      => admin_url('admin-ajax.php'),
    'nonce'         => wp_create_nonce('wp_rest'),
]); ?>;</script>

<style>
.bks-page{background:#f9f6f0;min-height:100vh;padding:0 0 60px}
.bks-header{background:linear-gradient(135deg,#0f2424 0%,#1a4a3a 100%);padding:24px 32px;color:#fff}
.bks-header-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;gap:20px;flex-wrap:wrap}
.bks-header-info h2{margin:0;font-size:20px;font-family:'Playfair Display',serif;font-weight:700}
.bks-header-info p{margin:4px 0 0;font-size:13px;color:rgba(255,255,255,.7);font-family:'Outfit',sans-serif}
.bks-header-chips{display:flex;gap:6px;flex-wrap:wrap;margin-top:8px}
.bks-chip{background:rgba(89,183,183,.25);border:1px solid rgba(89,183,183,.4);padding:3px 10px;border-radius:100px;font-size:11px;font-weight:600;font-family:'Outfit',sans-serif;color:#b0e0e0}
.bks-container{max-width:1200px;margin:0 auto;padding:24px 32px;display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start}
.bks-section{background:#fff;border-radius:18px;padding:28px;margin-bottom:18px;box-shadow:0 2px 10px rgba(0,0,0,.05)}
.bks-section-title{font-family:'Playfair Display',serif;font-size:18px;color:#0f2424;margin:0 0 6px;display:flex;align-items:center;gap:10px}
.bks-section-sub{font-size:13px;color:#6b7280;font-family:'Outfit',sans-serif;margin:0 0 18px}
.bks-step-num{background:#59b7b7;color:#fff;width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;font-family:'Outfit',sans-serif;flex-shrink:0}
.bks-chambre{background:#f9f6f0;border:1px solid #ede9e0;border-radius:14px;padding:20px;margin-bottom:16px}
.bks-chambre-title{font-weight:700;font-size:14px;color:#0f2424;margin-bottom:12px;font-family:'Outfit',sans-serif}
.bks-voyageur{margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #e5e7eb}
.bks-voyageur:last-child{margin-bottom:0;padding-bottom:0;border-bottom:none}
.bks-voyageur-label{font-size:12px;font-weight:700;color:#59b7b7;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;font-family:'Outfit',sans-serif}
.bks-field-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.bks-field{margin-bottom:10px}
.bks-field label{display:block;font-size:11px;font-weight:600;color:#374151;margin-bottom:4px;font-family:'Outfit',sans-serif}
.bks-field input,.bks-field select{width:100%;border:1.5px solid #e5e7eb;border-radius:10px;padding:10px 12px;font-size:13px;font-family:'Outfit',sans-serif;box-sizing:border-box}
.bks-field input:focus,.bks-field select:focus{border-color:#59b7b7;outline:none}
.bks-field input.bks-err{border-color:#dc2626}
.bks-nav{display:flex;gap:12px;justify-content:flex-end;margin-top:20px}
.bks-btn-prev{background:#f3f4f6;color:#374151;border:1px solid #d1d5db;border-radius:12px;padding:12px 24px;font-size:14px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif}
.bks-btn-next{background:#e8724a;color:#fff;border:none;border-radius:12px;padding:12px 28px;font-size:14px;font-weight:700;cursor:pointer;font-family:'Outfit',sans-serif;transition:all .2s}
.bks-btn-next:hover{background:#d4603c;transform:translateY(-1px)}
.bks-btn-next:disabled{opacity:.5;cursor:not-allowed;transform:none}
/* Recap */
.bks-recap{position:sticky;top:100px;background:#fff;border-radius:18px;padding:24px;box-shadow:0 4px 20px rgba(0,0,0,.08)}
.bks-recap-title{font-family:'Playfair Display',serif;font-size:16px;color:#0f2424;margin:0 0 14px}
.bks-recap-line{display:flex;justify-content:space-between;padding:6px 0;font-size:13px;font-family:'Outfit',sans-serif;color:#374151;border-bottom:1px solid #f0ece4}
.bks-recap-sep{height:1px;background:#59b7b7;margin:10px 0}
.bks-recap-total{display:flex;justify-content:space-between;align-items:center;padding:10px 0}
.bks-recap-total-lbl{font-size:14px;font-weight:700;color:#0f2424;font-family:'Outfit',sans-serif}
.bks-recap-total-val{font-size:24px;font-weight:800;color:#59b7b7;font-family:'Outfit',sans-serif}
.bks-recap-acompte{display:flex;justify-content:space-between;background:#edf8f8;border-radius:8px;padding:8px 12px;font-size:13px;font-weight:600;color:#59b7b7;font-family:'Outfit',sans-serif;margin-top:8px}
.bks-error{display:none;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px;border-radius:10px;font-size:13px;font-family:'Outfit',sans-serif;margin-bottom:14px}
.bks-loading{display:none;text-align:center;padding:20px}
.bks-spinner{width:28px;height:28px;border:3px solid #e5e7eb;border-top-color:#59b7b7;border-radius:50%;animation:bks-spin .7s linear infinite;margin:0 auto 8px}
@keyframes bks-spin{to{transform:rotate(360deg)}}
.bks-step-page{display:none!important}.bks-step-active{display:block!important}
/* Insurance */
.bks-ins-wrap{background:#f0f9ff;border:2px solid #bae6fd;border-radius:14px;padding:20px;margin-top:20px}
.bks-ins-title{font-weight:700;color:#0369a1;font-size:14px;font-family:'Outfit',sans-serif;margin-bottom:8px}
.bks-ins-footer{display:flex;justify-content:space-between;align-items:center;margin-top:12px;padding-top:12px;border-top:1px solid #bae6fd}
@media(max-width:900px){.bks-container{grid-template-columns:1fr}.bks-recap{position:static}}
@media(max-width:600px){.bks-container{padding:16px}.bks-field-row{grid-template-columns:1fr}.bks-header{padding:16px}.bks-section{padding:20px}}
</style>

<div class="bks-page">

    <div class="bks-header">
        <div class="bks-header-inner">
            <a href="<?php echo get_permalink($sejour_id); ?>" style="color:rgba(255,255,255,.5);text-decoration:none;font-size:13px;font-family:'Outfit',sans-serif">← Retour à la fiche</a>
            <div class="bks-header-info">
                <h2><?php echo esc_html($flag . ' ' . $titre); ?></h2>
                <p><?php echo esc_html($duree_j . ' jours / ' . $duree . ' nuits · ' . $hotel_nom . ' ' . str_repeat('★', $hotel_etoiles) . ' · ' . $pension); ?></p>
                <div class="bks-header-chips">
                    <?php if ($date_fmt): ?><span class="bks-chip">📅 <?php echo esc_html($date_fmt); ?></span><?php endif; ?>
                    <?php if ($params['aeroport']): ?><span class="bks-chip">✈️ <?php echo esc_html($params['aeroport']); ?></span><?php endif; ?>
                    <span class="bks-chip">👥 <?php echo $nb_total; ?> voyageur<?php echo $nb_total > 1 ? 's' : ''; ?></span>
                    <span class="bks-chip">🛏️ <?php echo $nb_chambres; ?> ch.</span>
                </div>
            </div>
        </div>
    </div>

    <div class="bks-container">
    <div>
        <div class="bks-error" id="bks-error"></div>

        <!-- ═══ ÉTAPE 1 : Récap vol + hôtel + assurance ═══ -->
        <div id="bks-step-1" class="bks-step-page bks-step-active">
        <div class="bks-section">
            <h3 class="bks-section-title"><span class="bks-step-num">1</span> Votre séjour</h3>
            <p class="bks-section-sub">Vérifiez les détails de votre forfait.</p>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                <div style="background:#edf8f8;border-radius:12px;padding:16px">
                    <div style="font-size:12px;font-weight:700;color:#59b7b7;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;font-family:'Outfit',sans-serif">✈️ Vols</div>
                    <div style="font-size:14px;font-weight:700;color:#0f2424;font-family:'Outfit',sans-serif"><?php echo esc_html($params['aeroport']); ?> ↔ <?php echo esc_html($iata_dest); ?></div>
                    <div style="font-size:12px;color:#6b7280;font-family:'Outfit',sans-serif"><?php echo esc_html($date_fmt); ?> → <?php echo esc_html($date_retour_fmt); ?></div>
                    <div style="font-size:16px;font-weight:800;color:#59b7b7;font-family:'Outfit',sans-serif;margin-top:6px"><?php echo number_format($params['vol_price'], 0, ',', ' '); ?> €/pers</div>
                </div>
                <div style="background:#f9f6f0;border-radius:12px;padding:16px">
                    <div style="font-size:12px;font-weight:700;color:#c8a45e;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;font-family:'Outfit',sans-serif">🏨 Hébergement</div>
                    <div style="font-size:14px;font-weight:700;color:#0f2424;font-family:'Outfit',sans-serif"><?php echo esc_html($hotel_nom); ?> <?php echo str_repeat('★', $hotel_etoiles); ?></div>
                    <div style="font-size:12px;color:#6b7280;font-family:'Outfit',sans-serif"><?php echo esc_html($pension); ?> · <?php echo $duree; ?> nuits</div>
                    <div style="font-size:16px;font-weight:800;color:#c8a45e;font-family:'Outfit',sans-serif;margin-top:6px"><?php echo number_format($params['hotel_net'], 0, ',', ' '); ?> € net</div>
                </div>
            </div>

            <!-- Assurance -->
            <?php if ($insurance_price > 0): ?>
            <div class="bks-ins-wrap">
                <div class="bks-ins-title">🛡️ Assurance Multirisque GALAXY · Assurever</div>
                <p style="font-size:13px;color:#374151;font-family:'Outfit',sans-serif;line-height:1.6;margin:0">Annulation, rapatriement 24h/24, frais médicaux, bagages — voyagez l'esprit libre.</p>
                <div class="bks-ins-footer">
                    <label style="display:flex;gap:8px;cursor:pointer;align-items:center">
                        <input type="checkbox" id="bks-assurance" onchange="bksUpdateInsurance()">
                        <span style="font-size:13px;font-family:'Outfit',sans-serif;color:#0369a1;font-weight:600">Oui, je souhaite être protégé(e)</span>
                    </label>
                    <div style="text-align:right">
                        <div style="font-size:16px;font-weight:800;color:#0369a1;font-family:'Outfit',sans-serif"><?php echo number_format($insurance_price * $nb_total, 2, ',', ' '); ?> €</div>
                        <div style="font-size:11px;color:#6b7280;font-family:'Outfit',sans-serif"><?php echo number_format($insurance_price, 2, ',', ' '); ?> € /pers</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="bks-nav"><button type="button" class="bks-btn-next" onclick="bksGoToStep2()">Continuer →</button></div>
        </div>
        </div>

        <!-- ═══ ÉTAPE 2 : Voyageurs + Facturation ═══ -->
        <div id="bks-step-2" class="bks-step-page">
        <div class="bks-section">
            <h3 class="bks-section-title"><span class="bks-step-num">2</span> Informations voyageurs & facturation</h3>
            <p class="bks-section-sub"><?php echo $nb_total; ?> voyageur(s) — <?php echo $nb_chambres; ?> chambre(s)</p>

            <?php
            for ($chambre = 1; $chambre <= $nb_chambres; $chambre++):
                $voy_in_ch = $voy_par_chambre[$chambre] ?? [];
            ?>
            <div class="bks-chambre">
                <div class="bks-chambre-title">🏨 Chambre <?php echo $chambre; ?></div>
                <?php foreach ($voy_in_ch as $vi):
                    $saved = $bk_saved_voy[$vi] ?? [];
                ?>
                <div class="bks-voyageur" data-voy-index="<?php echo $vi; ?>">
                    <div class="bks-voyageur-label">Voyageur <?php echo $vi + 1; ?></div>
                    <div class="bks-field-row">
                        <div class="bks-field"><label>Prénom *</label>
                            <input type="text" name="voyageurs[<?php echo $vi; ?>][prenom]" class="bks-required" placeholder="Jean" value="<?php echo esc_attr($saved['prenom'] ?? ''); ?>">
                        </div>
                        <div class="bks-field"><label>Nom *</label>
                            <input type="text" name="voyageurs[<?php echo $vi; ?>][nom]" class="bks-required" placeholder="Dupont" value="<?php echo esc_attr($saved['nom'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="bks-field-row">
                        <div class="bks-field"><label>Date de naissance *</label>
                            <input type="date" name="voyageurs[<?php echo $vi; ?>][ddn]" class="bks-required" value="<?php echo esc_attr($saved['ddn'] ?? ''); ?>">
                        </div>
                        <div class="bks-field"><label>N° Passeport</label>
                            <input type="text" name="voyageurs[<?php echo $vi; ?>][passeport]" placeholder="Optionnel" value="<?php echo esc_attr($saved['passeport'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endfor; ?>

            <!-- Facturation -->
            <h3 class="bks-section-title" style="margin-top:24px"><span class="bks-step-num">📄</span> Coordonnées de facturation</h3>
            <div class="bks-field-row">
                <div class="bks-field"><label>Prénom *</label><input type="text" id="bks-fact-prenom" class="bks-required" value="<?php echo esc_attr($bk_saved_fact['prenom'] ?? ''); ?>"></div>
                <div class="bks-field"><label>Nom *</label><input type="text" id="bks-fact-nom" class="bks-required" value="<?php echo esc_attr($bk_saved_fact['nom'] ?? ''); ?>"></div>
            </div>
            <div class="bks-field-row">
                <div class="bks-field"><label>Email *</label><input type="email" id="bks-fact-email" class="bks-required" value="<?php echo esc_attr($bk_saved_fact['email'] ?? ''); ?>"></div>
                <div class="bks-field"><label>Téléphone *</label><input type="tel" id="bks-fact-tel" class="bks-required" value="<?php echo esc_attr($bk_saved_fact['tel'] ?? ''); ?>"></div>
            </div>
            <div class="bks-field"><label>Adresse *</label><input type="text" id="bks-fact-adresse" class="bks-required" value="<?php echo esc_attr($bk_saved_fact['adresse'] ?? ''); ?>"></div>
            <div class="bks-field-row">
                <div class="bks-field"><label>Code postal *</label><input type="text" id="bks-fact-cp" class="bks-required" value="<?php echo esc_attr($bk_saved_fact['cp'] ?? ''); ?>"></div>
                <div class="bks-field"><label>Ville *</label><input type="text" id="bks-fact-ville" class="bks-required" value="<?php echo esc_attr($bk_saved_fact['ville'] ?? ''); ?>"></div>
            </div>

            <div class="bks-nav">
                <button type="button" class="bks-btn-prev" onclick="bksGoBack(1)">← Retour</button>
                <button type="button" class="bks-btn-next" onclick="bksGoToConfirm()">Vérifier et confirmer →</button>
            </div>
        </div>
        </div>

        <!-- ═══ ÉTAPE 3 : Confirmation ═══ -->
        <div id="bks-step-3" class="bks-step-page">
        <div class="bks-section">
            <h3 class="bks-section-title"><span class="bks-step-num">3</span> Confirmation</h3>
            <p class="bks-section-sub">Vérifiez toutes les informations avant le paiement.</p>

            <div id="bks-recap-final" style="margin-bottom:20px"></div>

            <div style="background:#fff8f0;border:1.5px solid #f0dcc0;border-radius:12px;padding:16px;margin-bottom:16px">
                <label style="display:flex;gap:10px;cursor:pointer;align-items:flex-start">
                    <input type="checkbox" id="bks-confirm-info" style="margin-top:3px">
                    <span style="font-size:12px;color:#6b5630;font-family:'Outfit',sans-serif;line-height:1.5">
                        Je certifie que les <strong>noms, prénoms, dates de naissance et passeports</strong> sont exacts et correspondent aux pièces d'identité officielles.
                    </span>
                </label>
            </div>

            <label style="display:flex;gap:10px;align-items:flex-start;font-size:12px;font-family:'Outfit',sans-serif;color:#1a3a3a;cursor:pointer;line-height:1.5">
                <input type="checkbox" id="bks-cgu" style="margin-top:3px">
                <span>J'accepte les <a href="<?php echo home_url('/conditions/'); ?>" target="_blank" style="color:#59b7b7">conditions générales de vente</a> et la <a href="<?php echo home_url('/rgpd'); ?>" target="_blank" style="color:#59b7b7">politique de confidentialité</a>. Directive UE 2015/2302 relative aux voyages à forfait.</span>
            </label>

            <div class="bks-nav" style="margin-top:24px">
                <button type="button" class="bks-btn-prev" onclick="bksGoBack(2)">← Retour</button>
                <button type="button" class="bks-btn-next" id="bks-btn-submit" onclick="bksSubmit()">🔒 Procéder au paiement →</button>
            </div>
        </div>
        </div>

    </div>

    <!-- SIDEBAR RÉCAP -->
    <div class="bks-recap">
        <h3 class="bks-recap-title">📋 Récapitulatif</h3>
        <div class="bks-recap-line" style="font-weight:600;color:#0f2424"><span>🏖️ <?php echo esc_html($titre); ?></span></div>
        <div class="bks-recap-line"><span>📅 Départ</span><span><?php echo esc_html($date_fmt ?: '—'); ?></span></div>
        <div class="bks-recap-line"><span>📅 Retour</span><span><?php echo esc_html($date_retour_fmt ?: '—'); ?></span></div>
        <div class="bks-recap-line"><span>✈️ Aéroport</span><span><?php echo esc_html($params['aeroport'] ?: '—'); ?></span></div>
        <div class="bks-recap-line"><span>🏨 Hôtel</span><span><?php echo esc_html($hotel_nom); ?></span></div>
        <div class="bks-recap-line"><span>🍽️ Pension</span><span><?php echo esc_html($pension); ?></span></div>
        <div class="bks-recap-line"><span>📅 Durée</span><span><?php echo $duree_j; ?>j / <?php echo $duree; ?>n</span></div>
        <div class="bks-recap-line"><span>👥 Voyageurs</span><span><?php echo $nb_total; ?> pers.</span></div>
        <div class="bks-recap-line" id="bks-recap-row-insurance" style="display:none"><span>🛡️ Assurance</span><span id="bks-recap-insurance-val">—</span></div>
        <div style="height:12px"></div>
        <?php foreach ($devis['lines'] as $line): ?>
        <div class="bks-recap-line"><span><?php echo esc_html($line['label']); ?></span><span><?php echo number_format($line['montant'], 0, ',', ' '); ?> €</span></div>
        <?php endforeach; ?>
        <div class="bks-recap-sep"></div>
        <div class="bks-recap-total">
            <span class="bks-recap-total-lbl">Total séjour</span>
            <span class="bks-recap-total-val" id="bks-recap-total-val"><?php echo number_format($devis['total'], 0, ',', ' '); ?> €</span>
        </div>
        <div style="font-size:11px;color:#6b7280;text-align:right;font-family:'Outfit',sans-serif;margin-top:2px">soit <?php echo number_format($devis['prix_par_personne'], 0, ',', ' '); ?> €/pers.</div>
        <?php if (!$payer_tout && $devis['acompte'] < $devis['total']): ?>
        <div class="bks-recap-acompte"><span>🔒 Acompte</span><span><?php echo number_format($devis['acompte'], 0, ',', ' '); ?> €</span></div>
        <div style="font-size:11px;color:#6b7280;font-family:'Outfit',sans-serif;margin-top:4px">Solde à régler <?php echo $delai_solde; ?> jours avant le départ</div>
        <?php endif; ?>
        <div class="bks-loading" id="bks-loading"><div class="bks-spinner"></div><div style="font-size:13px;color:#59b7b7;font-family:'Outfit',sans-serif">Création de votre réservation…</div></div>
        <div style="margin-top:12px;font-size:11px;color:#9ca3af;text-align:center;font-family:'Outfit',sans-serif">Paiement sécurisé 3D Secure · APST · Atout France</div>
    </div>

    </div>
</div>

<script>
(function(){
    var BK = BK_SEJOUR;
    var submitting = false;
    var bks_insurance = false;

    window.bksUpdateInsurance = function() {
        var chk = document.getElementById('bks-assurance');
        bks_insurance = chk && chk.checked;
        var row = document.getElementById('bks-recap-row-insurance');
        var val = document.getElementById('bks-recap-insurance-val');
        var totalEl = document.getElementById('bks-recap-total-val');
        if (bks_insurance && BK.insurance_total > 0) {
            if (row) row.style.display = 'flex';
            if (val) val.textContent = Number(BK.insurance_total).toLocaleString('fr-FR',{minimumFractionDigits:0,maximumFractionDigits:0}) + ' €';
            if (totalEl) totalEl.textContent = Number(BK.total + BK.insurance_total).toLocaleString('fr-FR',{minimumFractionDigits:0,maximumFractionDigits:0}) + ' €';
        } else {
            if (row) row.style.display = 'none';
            if (totalEl) totalEl.textContent = Number(BK.total).toLocaleString('fr-FR',{minimumFractionDigits:0,maximumFractionDigits:0}) + ' €';
        }
    };

    function showStep(n) {
        document.querySelectorAll('.bks-step-page').forEach(function(p){ p.classList.remove('bks-step-active'); });
        var el = document.getElementById('bks-step-' + n);
        if (el) el.classList.add('bks-step-active');
        window.scrollTo({top:0,behavior:'smooth'});
    }

    window.bksGoToStep2 = function() { showStep(2); };
    window.bksGoBack = function(from) { showStep(from); };

    window.bksGoToConfirm = function() {
        // Validate required fields
        var ok = true;
        document.querySelectorAll('#bks-step-2 .bks-required').forEach(function(input) {
            input.classList.remove('bks-err');
            if (!input.value.trim()) { input.classList.add('bks-err'); ok = false; }
        });
        if (!ok) { showError('Veuillez remplir tous les champs obligatoires.'); return; }

        // Build recap
        var recap = '<div style="font-family:Outfit,sans-serif;font-size:13px">';
        for (var vi = 0; vi < BK.nb_total; vi++) {
            var prenom = val('voyageurs['+vi+'][prenom]');
            var nom = val('voyageurs['+vi+'][nom]');
            var ddn = val('voyageurs['+vi+'][ddn]');
            recap += '<div style="padding:4px 0;border-bottom:1px solid #f0ece4">Voyageur '+(vi+1)+': <strong>'+esc(prenom)+' '+esc(nom)+'</strong> — '+esc(ddn)+'</div>';
        }
        recap += '<div style="margin-top:10px;padding:8px 0;font-weight:600;color:#0f2424">Facturation : '+esc(document.getElementById('bks-fact-prenom').value)+' '+esc(document.getElementById('bks-fact-nom').value)+'</div>';
        recap += '<div>'+esc(document.getElementById('bks-fact-email').value)+' · '+esc(document.getElementById('bks-fact-tel').value)+'</div>';
        recap += '<div>'+esc(document.getElementById('bks-fact-adresse').value)+', '+esc(document.getElementById('bks-fact-cp').value)+' '+esc(document.getElementById('bks-fact-ville').value)+'</div>';
        recap += '</div>';
        document.getElementById('bks-recap-final').innerHTML = recap;

        showStep(3);
    };

    window.bksSubmit = function() {
        if (submitting) return;
        var c1 = document.getElementById('bks-confirm-info');
        var c2 = document.getElementById('bks-cgu');
        if (!c1 || !c1.checked) { alert('Veuillez certifier l\'exactitude des informations.'); return; }
        if (!c2 || !c2.checked) { alert('Veuillez accepter les conditions générales de vente.'); return; }

        submitting = true;
        var btn = document.getElementById('bks-btn-submit');
        btn.disabled = true; btn.textContent = '⏳ Réservation en cours...';
        document.getElementById('bks-loading').style.display = 'block';

        var voyageurs = [];
        for (var vi = 0; vi < BK.nb_total; vi++) {
            voyageurs.push({
                prenom: val('voyageurs['+vi+'][prenom]'),
                nom: val('voyageurs['+vi+'][nom]'),
                ddn: val('voyageurs['+vi+'][ddn]'),
                passeport: val('voyageurs['+vi+'][passeport]')
            });
        }

        var body = {
            sejour_id: BK.sejour_id,
            date_depart: BK.date_depart,
            aeroport: BK.aeroport,
            nb_adultes: BK.nb_total,
            nb_chambres: BK.nb_chambres,
            vol_price: BK.vol_price,
            vol_offer_id: BK.vol_offer_id,
            hotel_net: BK.hotel_net,
            hotel_rate_key: BK.hotel_rate_key,
            hotel_board: BK.hotel_board,
            assurance: bks_insurance ? 1 : 0,
            voyageurs: voyageurs,
            facturation: {
                prenom: document.getElementById('bks-fact-prenom').value,
                nom: document.getElementById('bks-fact-nom').value,
                email: document.getElementById('bks-fact-email').value,
                tel: document.getElementById('bks-fact-tel').value,
                adresse: document.getElementById('bks-fact-adresse').value,
                cp: document.getElementById('bks-fact-cp').value,
                ville: document.getElementById('bks-fact-ville').value
            }
        };

        fetch(BK.rest_url + 'booking', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-WP-Nonce': BK.nonce},
            body: JSON.stringify(body)
        })
        .then(function(r){ return r.json(); })
        .then(function(res) {
            if (res && res.checkout_url) {
                window.location.href = res.checkout_url;
            } else if (res && res.data && res.data.checkout_url) {
                window.location.href = res.data.checkout_url;
            } else {
                showError((res && res.message) || 'Erreur. Contactez-nous au 03 26 65 28 63.');
                submitting = false; btn.disabled = false; btn.textContent = '🔒 Procéder au paiement →';
                document.getElementById('bks-loading').style.display = 'none';
            }
        })
        .catch(function(err) {
            showError('Erreur réseau : ' + err.message);
            submitting = false; btn.disabled = false; btn.textContent = '🔒 Procéder au paiement →';
            document.getElementById('bks-loading').style.display = 'none';
        });
    };

    function val(name) { var el = document.querySelector('[name="'+name+'"]'); return el ? el.value.trim() : ''; }
    function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function showError(msg) { var el = document.getElementById('bks-error'); el.textContent = msg; el.style.display = 'block'; window.scrollTo({top:0,behavior:'smooth'}); }
})();
</script>

<?php get_footer(); ?>
