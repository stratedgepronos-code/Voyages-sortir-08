<?php
/**
 * Template for single vs08_voyage
 * Place in your theme: single-vs08_voyage.php
 */
get_header();
while (have_posts()) : the_post();
    $id = get_the_ID();
    $m  = VS08V_MetaBoxes::get($id);
    $galerie      = $m['galerie'] ?? [];
    $aeroports    = $m['aeroports'] ?? [];
    $dates_depart = $m['dates_depart'] ?? [];
    $programme    = $m['programme'] ?? [];
    $options      = $m['options'] ?? [];
    $duree        = intval($m['duree'] ?? 7);
    $hero_img     = !empty($galerie[0]) ? $galerie[0] : get_the_post_thumbnail_url($id,'full');
?>

<!-- Injecter les données du voyage pour JS -->
<script>
var VS08V_VOYAGE = <?php echo json_encode([
    'id'                 => $id,
    'titre'              => get_the_title(),
    'duree'              => $duree,
    'prix_double'        => floatval($m['prix_double']??0),
    'prix_simple_supp'   => floatval($m['prix_simple_supp']??0),
    'prix_triple'        => floatval($m['prix_triple']??0),
    'prix_greenfees'     => floatval($m['prix_greenfees']??0),
    'reduction_nongolf'  => floatval($m['reduction_nongolfeur']??30),
    'prix_vol_base'      => floatval($m['prix_vol_base']??0),
    'prix_taxe'          => floatval($m['prix_taxe']??0),
    'prix_transfert'     => floatval($m['prix_transfert']??0),
    'acompte_pct'        => floatval($m['acompte_pct']??30),
    'delai_solde'        => intval($m['delai_solde']??30),
    'iata_dest'          => strtoupper((string)($m['iata_dest']??'')),
    'statut'             => $m['statut']??'actif',
    'options'            => $options,
    'aeroports'          => $aeroports,
    'booking_url'        => home_url('/reservation/'.$id),
]); ?>;
</script>

<style>
/* === SINGLE VOYAGE === */
.sv-hero{position:relative;height:72vh;min-height:520px;display:flex;align-items:flex-end;background-size:cover;background-position:center}
.sv-hero-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(15,36,36,.95) 0%,rgba(15,36,36,.35) 55%,transparent 100%)}
.sv-hero-content{position:relative;z-index:2;padding:0 80px 60px;width:100%}
.sv-breadcrumb{font-size:12px;color:rgba(255,255,255,.45);font-family:'Outfit',sans-serif;margin-bottom:14px}
.sv-breadcrumb a{color:rgba(255,255,255,.45);text-decoration:none}.sv-breadcrumb a:hover{color:#7ecece}.sv-breadcrumb span{margin:0 8px}
.sv-hero-dest{display:inline-flex;align-items:center;gap:8px;background:rgba(89,183,183,.2);border:1px solid rgba(89,183,183,.35);color:#7ecece;padding:5px 14px;border-radius:100px;font-size:12px;font-weight:700;font-family:'Outfit',sans-serif;margin-bottom:14px}
.sv-hero h1{font-size:clamp(30px,4.5vw,54px);color:#fff;font-family:'Playfair Display',serif;line-height:1.1;margin-bottom:18px;max-width:760px}
.sv-hero-meta{display:flex;gap:10px;flex-wrap:wrap}
.sv-meta-chip{display:flex;align-items:center;gap:6px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);padding:6px 14px;border-radius:100px;font-size:12px;font-weight:600;color:#fff;font-family:'Outfit',sans-serif}
.sv-page{background:#f9f6f0;padding:50px 0 80px}
.sv-inner{max-width:1400px;margin:0 auto;padding:0 80px;display:grid;grid-template-columns:1fr 400px;gap:40px;align-items:start}

/* GALLERY */
.sv-gallery{display:grid;grid-template-columns:2fr 1fr;grid-template-rows:220px 220px;gap:8px;border-radius:18px;overflow:hidden;margin-bottom:28px}
.sv-gal-item{overflow:hidden;cursor:pointer;position:relative}
.sv-gal-item img{width:100%;height:100%;object-fit:cover;transition:transform .5s}
.sv-gal-item:hover img{transform:scale(1.06)}
.sv-gal-item:first-child{grid-row:span 2}
.sv-gal-more{position:absolute;inset:0;background:rgba(15,36,36,.6);display:flex;align-items:center;justify-content:center;color:#fff;font-family:'Outfit',sans-serif;font-size:13px;font-weight:700}

/* SECTIONS */
.sv-section{background:#fff;border-radius:18px;padding:28px;margin-bottom:18px;box-shadow:0 2px 10px rgba(0,0,0,.05)}
.sv-section-title{font-size:20px;font-weight:700;color:#0f2424;font-family:'Playfair Display',serif;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.sv-desc p{font-size:15px;color:#4a5568;line-height:1.8;font-family:'Outfit',sans-serif;margin-bottom:12px}
.sv-highlights{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:18px}
.sv-hl{background:#edf8f8;border-radius:12px;padding:14px;text-align:center}
.sv-hl-icon{font-size:20px;margin-bottom:6px}
.sv-hl-val{font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:#3d9a9a}
.sv-hl-lbl{font-size:10px;color:#6b7280;text-transform:uppercase;letter-spacing:1px;font-family:'Outfit',sans-serif}

/* PROGRAMME */
.sv-day{border-left:2px solid #edf8f8;padding-left:18px;margin-bottom:20px;position:relative}
.sv-day::before{content:'';position:absolute;left:-6px;top:4px;width:10px;height:10px;border-radius:50%;background:#59b7b7}
.sv-day:last-child{margin-bottom:0}
.sv-day-num{font-size:10px;font-weight:700;color:#59b7b7;text-transform:uppercase;letter-spacing:1.5px;font-family:'Outfit',sans-serif;margin-bottom:3px}
.sv-day-title{font-size:16px;font-weight:700;color:#0f2424;font-family:'Playfair Display',serif;margin-bottom:5px}
.sv-day-desc{font-size:13px;color:#6b7280;line-height:1.65;font-family:'Outfit',sans-serif;margin-bottom:8px}
.sv-day-tags{display:flex;gap:6px;flex-wrap:wrap}
.sv-day-tag{background:#f9f6f0;color:#1a3a3a;font-size:11px;padding:3px 9px;border-radius:100px;font-family:'Outfit',sans-serif}

/* INCLUS */
.sv-inclus-list{list-style:none;display:grid;grid-template-columns:1fr 1fr;gap:8px}
.sv-inclus-list li{font-size:13px;color:#1a3a3a;font-family:'Outfit',sans-serif;display:flex;align-items:flex-start;gap:7px;line-height:1.4}
.sv-inclus-list li::before{content:'✓';color:#2d8a5a;font-weight:700;flex-shrink:0}

/* ============================================================
   WIDGET CALCULATEUR — STICKY
============================================================ */
.sv-calc-col{position:sticky;top:90px;display:flex;flex-direction:column;gap:16px}
.sv-calc-card{background:#fff;border-radius:22px;padding:28px;box-shadow:0 8px 40px rgba(0,0,0,.1)}

.sv-calc-title{font-family:'Playfair Display',serif;font-size:19px;font-weight:700;color:#0f2424;margin-bottom:4px}
.sv-calc-sub{font-size:12px;color:#6b7280;font-family:'Outfit',sans-serif;margin-bottom:20px}
.sv-calc-order-hint{background:#edf8f8;border:1px solid rgba(89,183,183,.25);border-radius:10px;padding:10px 12px;font-size:12px;color:#1a3a3a;margin-bottom:18px;font-family:'Outfit',sans-serif}
.sv-required-dot{color:#e8724a;font-weight:700}
.sv-date-hint{font-size:12px;color:#6b7280;margin:6px 0 0;font-style:italic;font-family:'Outfit',sans-serif}
.sv-step-date:not(.sv-date-active) .sv-date-hint{display:block !important}
.sv-step-date.sv-date-active .sv-date-hint{display:none !important}

.sv-field{margin-bottom:14px}
.sv-field label{display:block;font-size:10px;font-weight:700;color:#1a3a3a;text-transform:uppercase;letter-spacing:1px;margin-bottom:5px;font-family:'Outfit',sans-serif}
.sv-field select,.sv-field input{width:100%;border:1.5px solid #e5e7eb;border-radius:10px;padding:10px 12px;font-size:13px;font-family:'Outfit',sans-serif;color:#0f2424;background:#fafafa;outline:none;transition:border-color .2s;-webkit-appearance:none}
.sv-field select:focus,.sv-field input:focus{border-color:#59b7b7}
.sv-field-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}

.sv-vol-status{font-size:11px;padding:6px 10px;border-radius:8px;font-family:'Outfit',sans-serif;margin-top:5px;display:none}
.sv-vol-status.loading{background:#fff3e8;color:#b85c1a;display:block}
.sv-vol-status.loaded{background:#e8f8f0;color:#2d8a5a;display:block}
.sv-vol-status.error{background:#fee2e2;color:#dc2626;display:block}

/* PRIX CALCULÉ */
.sv-price-result{background:#f9f6f0;border-radius:14px;padding:18px;margin:16px 0}
.sv-price-lines{margin-bottom:12px}
.sv-price-line{display:flex;justify-content:space-between;padding:4px 0;font-size:12px;font-family:'Outfit',sans-serif;color:#4a5568;border-bottom:1px solid #ede9e0}
.sv-price-line:last-child{border-bottom:none}
.sv-price-line.total{font-weight:700;color:#0f2424;font-size:14px;padding-top:8px}
.sv-price-total{text-align:center;margin-top:10px}
.sv-price-from{font-size:11px;color:#6b7280;text-transform:uppercase;font-family:'Outfit',sans-serif}
.sv-price-main{font-family:'Playfair Display',serif;font-size:40px;font-weight:700;color:#3d9a9a;line-height:1}
.sv-price-per{font-size:11px;color:#6b7280;font-family:'Outfit',sans-serif}
.sv-price-acompte{font-size:11px;color:#2d8a5a;background:#e8f8f0;padding:4px 12px;border-radius:100px;display:inline-block;margin-top:6px;font-family:'Outfit',sans-serif;font-weight:600}
.sv-price-loading{text-align:center;padding:20px;color:#9ca3af;font-family:'Outfit',sans-serif;font-size:13px;display:none}

.sv-btn-reserver{width:100%;background:#e8724a;color:#fff;border:none;padding:17px;border-radius:12px;font-size:15px;font-weight:700;font-family:'Outfit',sans-serif;cursor:pointer;transition:all .3s;margin-top:4px}
.sv-btn-reserver:hover{background:#d4603c;transform:translateY(-2px)}
.sv-btn-reserver:disabled{background:#9ca3af;cursor:not-allowed;transform:none}
.sv-reassurance{margin-top:14px;display:flex;flex-direction:column;gap:6px}
.sv-reass-item{font-size:11px;color:#6b7280;font-family:'Outfit',sans-serif}
.sv-btn-wishlist{display:block;width:100%;margin-top:10px;padding:10px 16px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;font-size:13px;font-weight:600;color:#2a7f7f;cursor:pointer;font-family:'Outfit',sans-serif;transition:background .2s,color .2s}
.sv-btn-wishlist:hover{background:#edf8f8;color:#1a3a3a}
.sv-btn-wishlist.in-wishlist{background:#edf8f8;border-color:#2a7f7f;color:#1a3a3a}
.sv-wishlist-login{font-size:12px;color:#6b7280;margin-top:10px}
.sv-wishlist-login a{color:#2a7f7f;text-decoration:none}
.sv-reviews-section{margin-top:18px}
.sv-reviews-avg{font-size:16px;color:#2a7f7f;margin-left:8px;font-weight:700}
.sv-reviews-empty{font-size:14px;color:#6b7280;margin:0;line-height:1.6}
.sv-reviews-list{display:flex;flex-direction:column;gap:16px;margin-top:12px}
.sv-review-item{background:#f9fafb;border-radius:10px;padding:14px;border:1px solid #e5e7eb}
.sv-review-meta{display:flex;align-items:center;gap:10px;margin-bottom:6px}
.sv-review-stars{color:#f59e0b;font-size:14px;letter-spacing:1px}
.sv-review-author{font-size:13px;font-weight:600;color:#1a3a3a}
.sv-review-content{font-size:14px;color:#4b5563;line-height:1.6;margin:0}

@media(max-width:1100px){.sv-inner{grid-template-columns:1fr;padding:0 40px}.sv-calc-col{position:static}.sv-hero-content{padding:0 40px 50px}}
@media(max-width:768px){.sv-inner{padding:0 16px}.sv-hero-content{padding:0 20px 36px}.sv-gallery{grid-template-columns:1fr;grid-template-rows:180px 100px 100px}.sv-gal-item:first-child{grid-row:span 1}.sv-inclus-list{grid-template-columns:1fr}.sv-field-row{grid-template-columns:1fr}}
</style>

<!-- HERO -->
<section class="sv-hero" style="background-image:url('<?php echo esc_url($hero_img); ?>');">
    <div class="sv-hero-overlay"></div>
    <div class="sv-hero-content">
        <p class="sv-breadcrumb"><a href="<?php echo home_url(); ?>">Accueil</a><span>›</span><a href="<?php echo home_url('/golf'); ?>">Séjours Golf</a><span>›</span><?php the_title(); ?></p>
        <div class="sv-hero-dest"><?php echo esc_html(($m['flag']??'').' '.($m['destination']??'')); ?></div>
        <h1><?php the_title(); ?></h1>
        <div class="sv-hero-meta">
            <div class="sv-meta-chip">🌙 <?php echo $duree; ?> nuits</div>
            <?php if (!empty($m['nb_parcours'])): ?><div class="sv-meta-chip">⛳ <?php echo $m['nb_parcours']; ?> parcours</div><?php endif; ?>
            <div class="sv-meta-chip">✈️ Vol inclus</div>
            <div class="sv-meta-chip">🎯 <?php $niveaux=['tous'=>'Tous niveaux','debutant'=>'Débutant','intermediaire'=>'Intermédiaire','confirme'=>'Confirmé']; echo $niveaux[$m['niveau']??'tous']??'Tous niveaux'; ?></div>
        </div>
    </div>
</section>

<section class="sv-page">
    <div class="sv-inner">

        <!-- COLONNE GAUCHE -->
        <div>
            <!-- GALERIE -->
            <?php if (!empty($galerie)): ?>
            <div class="sv-gallery">
                <?php foreach(array_slice($galerie,0,4) as $i=>$img): ?>
                <div class="sv-gal-item">
                    <img src="<?php echo esc_url($img); ?>" alt="" loading="<?php echo $i===0?'eager':'lazy'; ?>">
                    <?php if($i===3 && count($galerie)>4): ?><div class="sv-gal-more">+<?php echo count($galerie)-4; ?> photos</div><?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- DESCRIPTION -->
            <div class="sv-section">
                <h2 class="sv-section-title"><span>✦</span> Ce séjour en quelques mots</h2>
                <div class="sv-desc"><?php the_content(); ?></div>
                <div class="sv-highlights">
                    <div class="sv-hl"><div class="sv-hl-icon">⛳</div><div class="sv-hl-val"><?php echo esc_html($m['nb_parcours']??'—'); ?></div><div class="sv-hl-lbl">Parcours</div></div>
                    <div class="sv-hl"><div class="sv-hl-icon">🌙</div><div class="sv-hl-val"><?php echo $duree; ?></div><div class="sv-hl-lbl">Nuits</div></div>
                    <div class="sv-hl"><div class="sv-hl-icon">🏨</div><div class="sv-hl-val"><?php echo esc_html($m['hotel_etoiles']??'5'); ?>★</div><div class="sv-hl-lbl"><?php echo esc_html($m['hotel_nom']??'Hôtel'); ?></div></div>
                </div>
            </div>

            <!-- PROGRAMME -->
            <?php if (!empty($programme)): ?>
            <div class="sv-section">
                <h2 class="sv-section-title"><span>🗓️</span> Programme jour par jour</h2>
                <?php foreach($programme as $j): ?>
                <div class="sv-day">
                    <p class="sv-day-num"><?php echo esc_html($j['num']??''); ?></p>
                    <p class="sv-day-title"><?php echo esc_html($j['titre']??''); ?></p>
                    <p class="sv-day-desc"><?php echo esc_html($j['desc']??''); ?></p>
                    <div class="sv-day-tags"><?php foreach(array_filter(array_map('trim',explode(',',$j['tags']??''))) as $t): ?><span class="sv-day-tag"><?php echo esc_html($t); ?></span><?php endforeach; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- INCLUS -->
            <div class="sv-section">
                <h2 class="sv-section-title"><span>✅</span> Toujours inclus</h2>
                <ul class="sv-inclus-list">
                    <li>Vols aller-retour</li>
                    <li><?php echo $duree; ?> nuits hôtel <?php echo esc_html($m['hotel_etoiles']??5); ?>★</li>
                    <li><?php echo esc_html($m['nb_parcours']??''); ?> green fees avec buggy</li>
                    <li>Transferts aéroport</li>
                    <li>Petit-déjeuner inclus</li>
                    <li>Conseiller dédié 7j/7</li>
                </ul>
            </div>

            <!-- AVIS CLIENTS -->
            <?php if (class_exists('VS08V_Traveler_Space')) :
                $reviews = VS08V_Traveler_Space::get_reviews($id);
                $avg_rating = VS08V_Traveler_Space::get_average_rating($id);
            ?>
            <div class="sv-section sv-reviews-section">
                <h2 class="sv-section-title"><span>⭐</span> Avis des voyageurs <?php if ($avg_rating > 0): ?><span class="sv-reviews-avg"><?php echo number_format($avg_rating, 1, ',', ''); ?>/5</span><?php endif; ?></h2>
                <?php if (empty($reviews)): ?>
                <p class="sv-reviews-empty">Aucun avis pour le moment. Soyez le premier à partager votre expérience après votre séjour !</p>
                <?php else: ?>
                <div class="sv-reviews-list">
                    <?php foreach ($reviews as $rev):
                        $r = (int) get_comment_meta($rev->comment_ID, 'vs08_rating', true);
                    ?>
                    <div class="sv-review-item">
                        <div class="sv-review-meta">
                            <span class="sv-review-stars"><?php echo str_repeat('★', $r); ?><?php echo str_repeat('☆', 5 - $r); ?></span>
                            <span class="sv-review-author"><?php echo esc_html($rev->comment_author); ?></span>
                        </div>
                        <?php if ($rev->comment_content): ?>
                        <p class="sv-review-content"><?php echo nl2br(esc_html($rev->comment_content)); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- COLONNE DROITE — CALCULATEUR STICKY -->
        <div class="sv-calc-col">
            <div class="sv-calc-card">
                <p class="sv-calc-title">Calculez votre prix</p>
                <?php if (!empty($aeroports)): ?>
                <p class="sv-calc-sub sv-calc-order-hint">Étape 1 : choisissez votre aéroport de départ → les dates ouvertes se chargeront ensuite.</p>
                <?php else: ?>
                <p class="sv-calc-sub">Remplissez les champs — le prix se met à jour en temps réel.</p>
                <?php endif; ?>

                <!-- 1. Aéroport OBLIGATOIRE en premier (puis les dates s'adaptent) -->
                <?php if (!empty($aeroports)): ?>
                <div class="sv-field sv-step-aero">
                    <label>1. Aéroport de départ <span class="sv-required-dot">*</span></label>
                    <select id="sv-aeroport" onchange="sv_on_aeroport_change();">
                        <option value="">— Choisir un aéroport —</option>
                        <?php foreach($aeroports as $a): ?>
                        <option value="<?php echo esc_attr(strtoupper((string)($a['code']??''))); ?>"><?php echo esc_html(strtoupper((string)($a['code']??'')).' — '.($a['ville']??'')); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="sv-vol-status" id="sv-vol-status"></div>
                </div>

                <!-- 2. Date départ (affiché et activé UNIQUEMENT après choix aéroport) -->
                <div class="sv-field sv-step-date" id="sv-field-date-wrap">
                    <label>2. Date de départ</label>
                    <p class="sv-date-hint" id="sv-date-hint">Choisissez d’abord un aéroport ci-dessus pour afficher les dates disponibles.</p>
                    <?php if (!empty($dates_depart)): ?>
                    <select id="sv-date-depart" onchange="sv_fetch_vol(); sv_update();" disabled style="display:none;">
                        <option value="">Choisir une date</option>
                        <?php foreach($dates_depart as $d): if(($d['statut']??'dispo')==='complet') continue; ?>
                        <option value="<?php echo esc_attr($d['date']); ?>" <?php echo ($d['statut']??'')==='derniere_place'?'style="color:#e8724a"':''; ?>>
                            <?php echo date('d/m/Y', strtotime($d['date'])); ?> <?php echo ($d['statut']??'')==='derniere_place'?' — ⚠️ Dernières places':''; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php else: ?>
                    <input type="date" id="sv-date-depart" onchange="sv_on_date_change();" min="<?php echo date('Y-m-d'); ?>" disabled style="display:none;">
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <!-- Pas d'aéroports configurés : date en premier -->
                <div class="sv-field">
                    <label>Date de départ</label>
                    <?php if (!empty($dates_depart)): ?>
                    <select id="sv-date-depart" onchange="sv_update();">
                        <option value="">Choisir une date</option>
                        <?php foreach($dates_depart as $d): if(($d['statut']??'dispo')==='complet') continue; ?>
                        <option value="<?php echo esc_attr($d['date']); ?>"><?php echo date('d/m/Y', strtotime($d['date'])); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php else: ?>
                    <input type="date" id="sv-date-depart" onchange="sv_update()" min="<?php echo date('Y-m-d'); ?>">
                    <?php endif; ?>
                </div>
                <div id="sv-vol-status"></div>
                <?php endif; ?>

                <!-- Golfeurs / Non-golfeurs -->
                <div class="sv-field-row">
                    <div class="sv-field">
                        <label>Golfeurs</label>
                        <select id="sv-nb-golfeurs" onchange="sv_update()">
                            <?php for($i=1;$i<=8;$i++): ?><option value="<?php echo $i;?>" <?php selected($i,2);?>><?php echo $i;?></option><?php endfor; ?>
                        </select>
                    </div>
                    <div class="sv-field">
                        <label>Non-golfeurs</label>
                        <select id="sv-nb-nongolf" onchange="sv_update()">
                            <?php for($i=0;$i<=6;$i++): ?><option value="<?php echo $i;?>"><?php echo $i;?></option><?php endfor; ?>
                        </select>
                    </div>
                </div>

                <!-- Chambre + nb chambres -->
                <div class="sv-field-row">
                    <div class="sv-field">
                        <label>Type chambre</label>
                        <select id="sv-type-chambre" onchange="sv_update()">
                            <option value="double">Double (2 pers.)</option>
                            <option value="simple">Simple (+supplément)</option>
                            <option value="triple">Triple (3 pers.)</option>
                        </select>
                    </div>
                    <div class="sv-field">
                        <label>Nb chambres</label>
                        <select id="sv-nb-chambres" onchange="sv_update()">
                            <?php for($i=1;$i<=6;$i++): ?><option value="<?php echo $i;?>"><?php echo $i;?></option><?php endfor; ?>
                        </select>
                    </div>
                </div>

                <!-- RÉSULTAT PRIX -->
                <div class="sv-price-loading" id="sv-price-loading">⏳ Calcul en cours...</div>
                <div class="sv-price-result" id="sv-price-result" style="display:none">
                    <div class="sv-price-lines" id="sv-price-lines"></div>
                    <div class="sv-price-total">
                        <div class="sv-price-from">Total estimé</div>
                        <div class="sv-price-main" id="sv-price-total-val">—</div>
                        <div class="sv-price-per">tout compris · <span id="sv-price-perpers"></span> / pers.</div>
                        <div class="sv-price-acompte" id="sv-price-acompte"></div>
                    </div>
                </div>

                <button class="sv-btn-reserver" id="sv-btn-reserver" onclick="sv_go_reserver()" disabled>
                    Réserver ce séjour →
                </button>
                <?php if (is_user_logged_in() && class_exists('VS08V_Traveler_Space')) :
                    $in_wishlist = VS08V_Traveler_Space::is_in_wishlist($id);
                ?>
                <button type="button" class="sv-btn-wishlist <?php echo $in_wishlist ? 'in-wishlist' : ''; ?>" id="sv-btn-wishlist" data-voyage-id="<?php echo $id; ?>" data-in-wishlist="<?php echo $in_wishlist ? '1' : '0'; ?>" title="<?php echo $in_wishlist ? 'Retirer de ma liste' : 'Ajouter à ma liste d\'envies'; ?>">
                    <?php echo $in_wishlist ? '❤ Retiré des favoris' : '🤍 Ajouter à ma liste'; ?>
                </button>
                <script>window.vs08v_traveler_nonce = '<?php echo esc_js(wp_create_nonce('vs08v_traveler')); ?>';</script>
                <?php elseif (!is_user_logged_in()): ?>
                <p class="sv-wishlist-login"><a href="<?php echo esc_url(home_url('/connexion/?redirect_to=' . urlencode(get_permalink()))); ?>">Connectez-vous</a> pour sauvegarder vos séjours favoris.</p>
                <?php endif; ?>
                <div class="sv-reassurance">
                    <div class="sv-reass-item">✅ Acompte <?php echo esc_html($m['acompte_pct']??30); ?>% seulement</div>
                    <div class="sv-reass-item">🔒 Paiement sécurisé</div>
                    <div class="sv-reass-item">📞 03 26 65 28 63</div>
                </div>
            </div>
        </div>

    </div>
</section>

<script>
var sv_prix_vol = 0;
var sv_calc_timer = null;

function sv_on_aeroport_change() {
    var sel = document.getElementById('sv-aeroport');
    var dateEl = document.getElementById('sv-date-depart');
    var dateWrap = document.getElementById('sv-field-date-wrap');
    var hint = document.getElementById('sv-date-hint');
    if (!sel || !dateEl) return;
    var hasAero = sel.value && sel.value.length > 0;
    if (dateEl.disabled !== undefined) dateEl.disabled = !hasAero;
    if (dateEl.style) dateEl.style.display = hasAero ? '' : 'none';
    if (hint) hint.style.display = hasAero ? 'none' : 'block';
    if (dateWrap) { if (hasAero) dateWrap.classList.add('sv-date-active'); else dateWrap.classList.remove('sv-date-active'); }
    if (hasAero) {
        sv_apply_aeroport_date_rules();
        sv_fetch_vol();
    } else {
        dateEl.value = '';
        var st = document.getElementById('sv-vol-status');
        if (st) { st.style.display = 'none'; st.textContent = ''; }
    }
    sv_update();
}

function sv_on_date_change() {
    var sel = document.getElementById('sv-aeroport');
    var dateEl = document.getElementById('sv-date-depart');
    if (!sel || !sel.value || !dateEl || !dateEl.value) return;
    if (!sv_date_allowed_for_aeroport(dateEl.value, sel.value)) {
        dateEl.value = '';
        var st = document.getElementById('sv-vol-status');
        if (st) { st.className = 'sv-vol-status error'; st.style.display = 'block'; st.textContent = 'Aucun vol direct ce jour-là depuis cet aéroport.'; }
    } else {
        sv_fetch_vol();
    }
    sv_update();
}

// Retourne true si la date est autorisée pour cet aéroport.
// Chaque période peut avoir ses propres jours d'ouverture (jours_direct) ; sinon on utilise ceux de l'aéroport.
function sv_date_allowed_for_aeroport(dateStr, code) {
    if (!code || !VS08V_VOYAGE.aeroports || !dateStr) return true;
    var a = VS08V_VOYAGE.aeroports.find(function(x){ return (x.code||'').toUpperCase() === (code||'').toUpperCase(); });
    if (!a) return true;
    var periodes = a.periodes_vol || [];
    var joursDefaut = a.jours_direct || [1,2,3,4,5,6,7];
    var d = new Date(dateStr + 'T12:00:00');
    var jsDay = d.getDay();
    var phpDay = jsDay === 0 ? 7 : jsDay;
    if (periodes.length === 0) {
        return joursDefaut.length === 0 || joursDefaut.indexOf(phpDay) !== -1;
    }
    var t = d.getTime();
    for (var i = 0; i < periodes.length; i++) {
        var deb = periodes[i].date_debut;
        var fin = periodes[i].date_fin;
        if (!deb && !fin) continue;
        var tDeb = deb ? new Date(deb + 'T00:00:00').getTime() : 0;
        var tFin = fin ? new Date(fin + 'T23:59:59').getTime() : 9e12;
        if (t >= tDeb && t <= tFin) {
            // Date dans cette période : vérifier les jours de cette période (ou défaut aéroport)
            var joursPeriode = (periodes[i].jours_direct && periodes[i].jours_direct.length) ? periodes[i].jours_direct : joursDefaut;
            return joursPeriode.indexOf(phpDay) !== -1;
        }
    }
    return false;
}

function sv_apply_aeroport_date_rules() {
    var selAero = document.getElementById('sv-aeroport');
    var dateEl = document.getElementById('sv-date-depart');
    if (!dateEl || !selAero) return;
    var code = selAero.value;
    if (!code || !VS08V_VOYAGE.aeroports) return;
    var a = VS08V_VOYAGE.aeroports.find(function(x){ return (x.code||'').toUpperCase() === (code||'').toUpperCase(); });
    if (!a) return;

    if (dateEl.tagName === 'SELECT') {
        [].forEach.call(dateEl.options, function(opt) {
            if (opt.value === '') { opt.disabled = false; opt.hidden = false; return; }
            var ok = sv_date_allowed_for_aeroport(opt.value, code);
            opt.disabled = !ok;
            if (opt.selected && !ok) dateEl.value = '';
        });
    } else {
        var periodes = a.periodes_vol || []; // Plusieurs périodes : min/max = union de toutes les plages
        if (periodes.length) {
            var minD = '', maxD = '';
            periodes.forEach(function(p) {
                if (p.date_debut && (!minD || p.date_debut < minD)) minD = p.date_debut;
                if (p.date_fin && (!maxD || p.date_fin > maxD)) maxD = p.date_fin;
            });
            dateEl.min = minD || dateEl.min;
            dateEl.max = maxD || dateEl.max;
        }
        if (dateEl.value && !sv_date_allowed_for_aeroport(dateEl.value, code)) {
            dateEl.value = '';
            if (document.getElementById('sv-vol-status')) {
                var st = document.getElementById('sv-vol-status');
                st.className = 'sv-vol-status error';
                st.style.display = 'block';
                st.textContent = 'Aucun vol direct ce jour-là depuis cet aéroport. Choisissez une autre date.';
            }
        }
    }
}

function sv_fetch_vol() {
    var aeroport = document.getElementById('sv-aeroport') ? document.getElementById('sv-aeroport').value : '';
    var date     = document.getElementById('sv-date-depart').value;
    var status   = document.getElementById('sv-vol-status');
    if (!aeroport || !date) { sv_update(); return; }
    if (status) { status.className = 'sv-vol-status loading'; status.textContent = '⏳ Recherche du meilleur vol...'; }
    jQuery.post(VS08V.ajax_url, {
        action: 'vs08v_get_flight', nonce: VS08V.nonce,
        voyage_id: VS08V_VOYAGE.id, date: date, aeroport: aeroport
    }, function(res) {
        if (res.success) {
            sv_prix_vol = res.data.prix;
            if (status) {
                status.className = 'sv-vol-status loaded';
                status.textContent = res.data.note === 'estimate'
                    ? '~' + sv_prix_vol + '€/pers. (estimé)'
                    : '✅ ' + sv_prix_vol + '€/pers. (en temps réel)';
            }
        } else {
            sv_prix_vol = VS08V_VOYAGE.prix_vol_base;
            if (status) { status.className = 'sv-vol-status error'; status.textContent = 'Tarif vol indisponible'; }
        }
        sv_update();
    });
}

function sv_update() {
    clearTimeout(sv_calc_timer);
    sv_calc_timer = setTimeout(sv_do_calc, 400);
}

function sv_do_calc() {
    var date    = document.getElementById('sv-date-depart').value;
    var aeropt  = document.getElementById('sv-aeroport') ? document.getElementById('sv-aeroport').value : '';
    var ngolf   = document.getElementById('sv-nb-golfeurs').value;
    var nngolf  = document.getElementById('sv-nb-nongolf').value;
    var tchamb  = document.getElementById('sv-type-chambre').value;
    var nchamb  = document.getElementById('sv-nb-chambres').value;
    if (!date || parseInt(ngolf)+parseInt(nngolf) === 0) return;

    document.getElementById('sv-price-loading').style.display = 'block';
    document.getElementById('sv-price-result').style.display  = 'none';

    jQuery.post(VS08V.ajax_url, {
        action: 'vs08v_calculate', nonce: VS08V.nonce,
        voyage_id: VS08V_VOYAGE.id,
        date_depart: date, aeroport: aeropt,
        nb_golfeurs: ngolf, nb_nongolfeurs: nngolf,
        type_chambre: tchamb, nb_chambres: nchamb,
        prix_vol: sv_prix_vol
    }, function(res) {
        document.getElementById('sv-price-loading').style.display = 'none';
        if (!res.success) return;
        var d = res.data;
        // Lignes de détail
        var lines_html = '';
        d.lines.forEach(function(l) {
            lines_html += '<div class="sv-price-line"><span>' + l.label + '</span><span>' + sv_fmt(l.montant) + '</span></div>';
        });
        lines_html += '<div class="sv-price-line total"><span>Total</span><span>' + sv_fmt(d.total) + '</span></div>';
        document.getElementById('sv-price-lines').innerHTML = lines_html;
        document.getElementById('sv-price-total-val').textContent = sv_fmt(d.total);
        document.getElementById('sv-price-perpers').textContent   = sv_fmt(d.par_pers);
        document.getElementById('sv-price-acompte').textContent   = '🔒 Acompte ' + d.acompte_pct + '% = ' + sv_fmt(d.acompte);
        document.getElementById('sv-price-result').style.display  = 'block';
        document.getElementById('sv-btn-reserver').disabled = false;
        // Stocker pour la réservation
        window.sv_devis = d;
        window.sv_params = {date_depart:date,aeroport:aeropt,nb_golfeurs:ngolf,nb_nongolfeurs:nngolf,type_chambre:tchamb,nb_chambres:nchamb,prix_vol:sv_prix_vol};
    });
}

function sv_fmt(n) {
    return parseFloat(n).toLocaleString('fr-FR', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' €';
}

function sv_go_reserver() {
    if (!window.sv_params) return;
    var p = window.sv_params;
    var url = VS08V_VOYAGE.booking_url
        + '?date=' + encodeURIComponent(p.date_depart)
        + '&aeroport=' + encodeURIComponent(p.aeroport)
        + '&ngolf=' + p.nb_golfeurs
        + '&nngolf=' + p.nb_nongolfeurs
        + '&chambre=' + p.type_chambre
        + '&nchamb=' + p.nb_chambres
        + '&vol=' + p.prix_vol;
    window.location.href = url;
}

var sv_btn_wishlist = document.getElementById('sv-btn-wishlist');
if (sv_btn_wishlist && window.vs08v_traveler_nonce) {
    sv_btn_wishlist.addEventListener('click', function() {
        var voyageId = this.dataset.voyageId;
        var fd = new FormData();
        fd.append('action', 'vs08v_traveler_wishlist');
        fd.append('nonce', window.vs08v_traveler_nonce);
        fd.append('voyage_id', voyageId);
        jQuery.post(VS08V.ajax_url, { action: 'vs08v_traveler_wishlist', nonce: window.vs08v_traveler_nonce, voyage_id: voyageId }, function(res) {
            if (res.data && res.data.login_required) return;
            if (res.success) {
                var inWL = res.data.in_wishlist;
                sv_btn_wishlist.dataset.inWishlist = inWL ? '1' : '0';
                sv_btn_wishlist.classList.toggle('in-wishlist', inWL);
                sv_btn_wishlist.textContent = inWL ? '❤ Retiré des favoris' : '🤍 Ajouter à ma liste';
                sv_btn_wishlist.title = inWL ? 'Retirer de ma liste' : 'Ajouter à ma liste d\'envies';
            }
        }, 'json');
    });
}
</script>

<?php endwhile; get_footer(); ?>
