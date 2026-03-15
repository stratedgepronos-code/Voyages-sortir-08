<?php
/**
 * Template Name: Page Séjour
 */
get_header();

// Récupérer les données du séjour depuis le contenu de la page
$content = get_the_content();
$sejour  = null;
if (preg_match('/<!-- vs08_sejour:([A-Za-z0-9+\/=]+) -->/', $content, $m)) {
    $sejour = json_decode(base64_decode($m[1]), true);
}

// Fallback si pas de données
if (!$sejour) {
    echo '<div style="padding:120px 80px;text-align:center;"><h2>Données du séjour non trouvées.</h2><p><a href="' . home_url('/golf') . '">← Voir tous les séjours</a></p></div>';
    get_footer(); exit;
}
?>
<style>
.sj-hero{position:relative;height:70vh;min-height:500px;display:flex;align-items:flex-end;background-size:cover;background-position:center;overflow:hidden}
.sj-hero-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(15,36,36,.95) 0%,rgba(15,36,36,.4) 50%,transparent 100%)}
.sj-hero-content{position:relative;z-index:2;padding:0 80px 60px;width:100%}
.sj-hero-breadcrumb{font-size:12px;color:rgba(255,255,255,.5);font-family:'Outfit',sans-serif;margin-bottom:16px}
.sj-hero-breadcrumb a{color:rgba(255,255,255,.5);text-decoration:none}.sj-hero-breadcrumb a:hover{color:#7ecece}
.sj-hero-breadcrumb span{margin:0 8px}
.sj-hero-badge-dest{display:inline-flex;align-items:center;gap:8px;background:rgba(89,183,183,.2);border:1px solid rgba(89,183,183,.4);color:#7ecece;padding:6px 14px;border-radius:100px;font-size:12px;font-weight:700;font-family:'Outfit',sans-serif;margin-bottom:16px}
.sj-hero h1{font-size:clamp(30px,4.5vw,54px);color:#fff;font-family:'Playfair Display',serif;line-height:1.1;margin-bottom:20px;max-width:800px}
.sj-hero-meta{display:flex;gap:12px;flex-wrap:wrap}
.sj-hero-meta-item{display:flex;align-items:center;gap:6px;background:rgba(255,255,255,.1);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.15);padding:7px 14px;border-radius:100px;font-size:13px;font-weight:600;color:rgba(255,255,255,.9);font-family:'Outfit',sans-serif}
.sj-page{background:#f9f6f0;padding:60px 0 80px}
.sj-page-inner{max-width:1400px;margin:0 auto;padding:0 80px;display:grid;grid-template-columns:1fr 360px;gap:40px;align-items:start}
.sj-gallery{display:grid;grid-template-columns:2fr 1fr;grid-template-rows:240px 240px;gap:10px;border-radius:20px;overflow:hidden;margin-bottom:32px}
.sj-gallery-item{overflow:hidden;cursor:pointer;position:relative}
.sj-gallery-item img{width:100%;height:100%;object-fit:cover;transition:transform .5s}
.sj-gallery-item:hover img{transform:scale(1.06)}
.sj-gallery-item:first-child{grid-row:span 2}
.sj-gallery-more{position:absolute;inset:0;background:rgba(15,36,36,.6);display:flex;align-items:center;justify-content:center;color:#fff;font-family:'Outfit',sans-serif;font-size:14px;font-weight:700}
.sj-section{background:#fff;border-radius:20px;padding:30px;margin-bottom:20px;box-shadow:0 2px 12px rgba(0,0,0,.05)}
.sj-section-title{font-size:21px;font-weight:700;color:#0f2424;font-family:'Playfair Display',serif;margin-bottom:18px;display:flex;align-items:center;gap:10px}
.sj-desc p{font-size:15px;color:#4a5568;line-height:1.8;font-family:'Outfit',sans-serif;margin-bottom:12px}
.sj-highlights{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:20px}
.sj-highlight{background:#edf8f8;border-radius:14px;padding:16px;text-align:center}
.sj-highlight-icon{font-size:22px;margin-bottom:6px}
.sj-highlight-val{font-family:'Playfair Display',serif;font-size:22px;font-weight:700;color:#3d9a9a}
.sj-highlight-lbl{font-size:10px;color:#6b7280;text-transform:uppercase;letter-spacing:1px;font-family:'Outfit',sans-serif;margin-top:2px}
.sj-day{border-left:2px solid #edf8f8;padding-left:20px;margin-bottom:22px;position:relative}
.sj-day:last-child{margin-bottom:0}
.sj-day::before{content:'';position:absolute;left:-6px;top:4px;width:10px;height:10px;border-radius:50%;background:#59b7b7}
.sj-day-num{font-size:10px;font-weight:700;color:#59b7b7;text-transform:uppercase;letter-spacing:1.5px;font-family:'Outfit',sans-serif;margin-bottom:3px}
.sj-day-title{font-size:16px;font-weight:700;color:#0f2424;font-family:'Playfair Display',serif;margin-bottom:5px}
.sj-day-desc{font-size:13px;color:#6b7280;line-height:1.65;font-family:'Outfit',sans-serif;margin-bottom:8px}
.sj-day-tags{display:flex;gap:6px;flex-wrap:wrap}
.sj-day-tag{background:#f9f6f0;color:#1a3a3a;font-size:11px;padding:3px 9px;border-radius:100px;font-family:'Outfit',sans-serif}
.sj-parcours-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:14px}
.sj-parcours-card{border:1.5px solid #f0f2f4;border-radius:14px;padding:16px;transition:border-color .2s}
.sj-parcours-card:hover{border-color:#59b7b7}
.sj-parcours-name{font-size:15px;font-weight:700;color:#0f2424;font-family:'Playfair Display',serif;margin-bottom:4px}
.sj-parcours-stars{color:#c9a84c;font-size:12px;margin-bottom:6px}
.sj-parcours-info{font-size:12px;color:#6b7280;font-family:'Outfit',sans-serif;line-height:1.6}
.sj-parcours-badges{display:flex;gap:5px;margin-top:8px;flex-wrap:wrap}
.sj-parcours-badge{background:#edf8f8;color:#3d9a9a;font-size:10px;font-weight:600;padding:3px 8px;border-radius:100px;font-family:'Outfit',sans-serif}
.sj-hotel{display:flex;gap:18px}
.sj-hotel-img{width:170px;flex-shrink:0;border-radius:12px;overflow:hidden;height:125px}
.sj-hotel-img img{width:100%;height:100%;object-fit:cover}
.sj-hotel-name{font-size:18px;font-weight:700;color:#0f2424;font-family:'Playfair Display',serif;margin-bottom:4px}
.sj-hotel-stars{color:#c9a84c;font-size:13px;margin-bottom:7px}
.sj-hotel-desc{font-size:13px;color:#6b7280;font-family:'Outfit',sans-serif;line-height:1.6}
.sj-hotel-tags{display:flex;gap:7px;flex-wrap:wrap;margin-top:10px}
.sj-hotel-tag{background:#f9f6f0;color:#1a3a3a;font-size:11px;padding:4px 10px;border-radius:100px;font-family:'Outfit',sans-serif}
.sj-inclus-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px}
.sj-inclus-subtitle{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:12px;font-family:'Outfit',sans-serif}
.sj-inclus-subtitle.green{color:#2d8a5a}.sj-inclus-subtitle.red{color:#e8724a}
.sj-inclus-list,.sj-non-inclus-list{list-style:none;display:flex;flex-direction:column;gap:9px}
.sj-inclus-list li,.sj-non-inclus-list li{font-size:14px;font-family:'Outfit',sans-serif;display:flex;align-items:flex-start;gap:8px;line-height:1.4}
.sj-inclus-list li{color:#1a3a3a}.sj-inclus-list li::before{content:'✓';color:#2d8a5a;font-weight:700;flex-shrink:0}
.sj-non-inclus-list li{color:#6b7280}.sj-non-inclus-list li::before{content:'✕';color:#e8724a;font-weight:700;flex-shrink:0}
.sj-avis-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:14px}
.sj-avis-card{border:1.5px solid #f0f2f4;border-radius:14px;padding:18px}
.sj-avis-stars{color:#c9a84c;font-size:12px;margin-bottom:7px}
.sj-avis-text{font-size:13px;color:#4a5568;font-style:italic;line-height:1.65;font-family:'Outfit',sans-serif;margin-bottom:12px}
.sj-avis-author{display:flex;align-items:center;gap:10px}
.sj-avis-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#59b7b7,#3d9a9a);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:12px;flex-shrink:0;font-family:'Outfit',sans-serif}
.sj-avis-name{font-size:13px;font-weight:700;color:#0f2424;font-family:'Outfit',sans-serif}
.sj-avis-date{font-size:11px;color:#6b7280;font-family:'Outfit',sans-serif}
/* BOOKING */
.sj-booking-col{position:sticky;top:90px;display:flex;flex-direction:column;gap:18px}
.sj-booking-card{background:#fff;border-radius:22px;padding:28px;box-shadow:0 8px 40px rgba(0,0,0,.1)}
.sj-price-block{text-align:center;padding-bottom:20px;border-bottom:1px solid #f0f2f4;margin-bottom:20px}
.sj-price-from{font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:1px;font-family:'Outfit',sans-serif}
.sj-price-main{font-family:'Playfair Display',serif;font-size:46px;font-weight:700;color:#3d9a9a;line-height:1}
.sj-price-per{font-size:12px;color:#6b7280;font-family:'Outfit',sans-serif}
.sj-price-note{font-size:11px;color:#2d8a5a;background:#e8f8f0;padding:5px 12px;border-radius:100px;display:inline-block;margin-top:8px;font-family:'Outfit',sans-serif;font-weight:600}
.sj-form-field{margin-bottom:13px}
.sj-form-field label{display:block;font-size:11px;font-weight:700;color:#1a3a3a;text-transform:uppercase;letter-spacing:1px;margin-bottom:5px;font-family:'Outfit',sans-serif}
.sj-form-field select,.sj-form-field input{width:100%;border:1.5px solid #f0f2f4;border-radius:10px;padding:10px 12px;font-size:13px;font-family:'Outfit',sans-serif;color:#0f2424;background:#fafafa;outline:none;transition:border-color .2s;-webkit-appearance:none}
.sj-form-field select:focus,.sj-form-field input:focus{border-color:#59b7b7}
.sj-btn-reserver{width:100%;background:#e8724a;color:#fff;border:none;padding:17px;border-radius:12px;font-size:15px;font-weight:700;font-family:'Outfit',sans-serif;cursor:pointer;transition:all .3s;margin-top:4px}
.sj-btn-reserver:hover{background:#d4603c;transform:translateY(-2px)}
.sj-btn-devis{width:100%;background:transparent;color:#0f2424;border:1.5px solid #f0f2f4;padding:13px;border-radius:12px;font-size:13px;font-weight:600;font-family:'Outfit',sans-serif;cursor:pointer;transition:all .3s;margin-top:7px;text-decoration:none;display:block;text-align:center}
.sj-btn-devis:hover{border-color:#59b7b7;color:#3d9a9a}
.sj-booking-reassurance{display:flex;flex-direction:column;gap:7px;margin-top:14px;padding-top:14px;border-top:1px solid #f0f2f4}
.sj-reassurance-item{font-size:12px;color:#6b7280;font-family:'Outfit',sans-serif}
.sj-share-card{background:#fff;border-radius:16px;padding:20px;box-shadow:0 4px 20px rgba(0,0,0,.06)}
.sj-share-card h4{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#59b7b7;margin-bottom:12px;font-family:'Outfit',sans-serif}
.sj-share-btns{display:flex;gap:8px}
.sj-share-btn{flex:1;padding:9px 6px;border-radius:10px;font-size:12px;font-weight:600;font-family:'Outfit',sans-serif;text-align:center;text-decoration:none;transition:all .2s;border:none;cursor:pointer}
.sj-share-email{background:#edf8f8;color:#3d9a9a}.sj-share-pdf{background:#f9f6f0;color:#1a3a3a}.sj-share-wa{background:#e8f8f0;color:#2d8a5a}
.sj-cta{background:#0f2424;border-radius:24px;padding:50px 80px;display:flex;justify-content:space-between;align-items:center;gap:30px;margin:40px 80px 60px}
.sj-cta h2{font-size:28px;color:#fff;font-family:'Playfair Display',serif;line-height:1.2}
.sj-cta h2 em{color:#7ecece;font-style:italic}
.sj-cta p{color:rgba(255,255,255,.5);font-size:14px;font-family:'Outfit',sans-serif;margin-top:7px}
.sj-cta-btns{display:flex;gap:12px;flex-shrink:0}
.sj-btn-coral{display:inline-block;background:#e8724a;color:#fff;padding:13px 26px;border-radius:100px;font-size:14px;font-weight:700;font-family:'Outfit',sans-serif;text-decoration:none;transition:all .3s}
.sj-btn-coral:hover{background:#d4603c;color:#fff}
.sj-btn-ghost{display:inline-block;color:rgba(255,255,255,.7);border:1.5px solid rgba(255,255,255,.2);padding:12px 22px;border-radius:100px;font-size:14px;font-weight:500;font-family:'Outfit',sans-serif;text-decoration:none;transition:all .3s}
.sj-btn-ghost:hover{border-color:#7ecece;color:#7ecece}
@media(max-width:1100px){.sj-page-inner{grid-template-columns:1fr;padding:0 40px}.sj-booking-col{position:static}.sj-hero-content{padding:0 40px 50px}.sj-cta{margin:30px 40px 50px;padding:40px}}
@media(max-width:768px){.sj-hero-content{padding:0 20px 36px}.sj-page-inner{padding:0 16px}.sj-gallery{grid-template-columns:1fr;grid-template-rows:200px 110px 110px}.sj-gallery-item:first-child{grid-row:span 1}.sj-parcours-grid{grid-template-columns:1fr}.sj-inclus-grid{grid-template-columns:1fr}.sj-avis-grid{grid-template-columns:1fr}.sj-hotel{flex-direction:column}.sj-hotel-img{width:100%;height:160px}.sj-cta{flex-direction:column;margin:16px;padding:26px}.sj-hero-meta{gap:8px}}
</style>

<!-- HERO -->
<section class="sj-hero" style="background-image:url('<?php echo esc_url($sejour['hero_img']); ?>');">
    <div class="sj-hero-overlay"></div>
    <div class="sj-hero-content">
        <p class="sj-hero-breadcrumb">
            <a href="<?php echo home_url(); ?>">Accueil</a><span>›</span>
            <a href="<?php echo home_url('/golf'); ?>">Séjours Golf</a><span>›</span>
            <?php echo esc_html($sejour['titre']); ?>
        </p>
        <div class="sj-hero-badge-dest"><?php echo esc_html($sejour['flag']); ?> <?php echo esc_html($sejour['destination']); ?></div>
        <h1><?php echo esc_html($sejour['titre']); ?></h1>
        <div class="sj-hero-meta">
            <div class="sj-hero-meta-item">🌙 <?php echo esc_html($sejour['duree']); ?></div>
            <div class="sj-hero-meta-item">⛳ <?php echo esc_html($sejour['parcours_nb']); ?></div>
            <div class="sj-hero-meta-item">✈️ Vol inclus</div>
            <div class="sj-hero-meta-item">🎯 <?php echo esc_html($sejour['niveau']); ?></div>
            <div class="sj-hero-meta-item">⭐ 4.9/5</div>
        </div>
    </div>
</section>

<section class="sj-page">
    <div class="sj-page-inner">
        <div class="sj-main">

            <!-- GALERIE -->
            <div class="sj-gallery">
                <?php foreach ($sejour['imgs'] as $i => $img) : ?>
                <div class="sj-gallery-item">
                    <img src="<?php echo esc_url($img); ?>" alt="Photo <?php echo $i+1; ?>" loading="<?php echo $i===0?'eager':'lazy'; ?>">
                    <?php if ($i===3): ?><div class="sj-gallery-more">📷 Voir toutes les photos</div><?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- DESCRIPTION -->
            <div class="sj-section">
                <h2 class="sj-section-title"><span>✦</span> Le séjour en quelques mots</h2>
                <div class="sj-desc">
                    <?php foreach (explode("\n\n", $sejour['desc']) as $p) : ?>
                        <p><?php echo esc_html($p); ?></p>
                    <?php endforeach; ?>
                </div>
                <div class="sj-highlights">
                    <div class="sj-highlight"><div class="sj-highlight-icon">⛳</div><div class="sj-highlight-val"><?php echo esc_html(count($sejour['parcours'])); ?></div><div class="sj-highlight-lbl">Parcours</div></div>
                    <div class="sj-highlight"><div class="sj-highlight-icon">🌙</div><div class="sj-highlight-val"><?php echo esc_html(intval($sejour['duree'])); ?></div><div class="sj-highlight-lbl">Nuits</div></div>
                    <div class="sj-highlight"><div class="sj-highlight-icon">⭐</div><div class="sj-highlight-val">4.9</div><div class="sj-highlight-lbl">Note clients</div></div>
                </div>
            </div>

            <!-- PROGRAMME -->
            <div class="sj-section">
                <h2 class="sj-section-title"><span>🗓️</span> Programme jour par jour</h2>
                <?php foreach ($sejour['programme'] as $day) : ?>
                <div class="sj-day">
                    <p class="sj-day-num"><?php echo esc_html($day['num']); ?></p>
                    <p class="sj-day-title"><?php echo esc_html($day['titre']); ?></p>
                    <p class="sj-day-desc"><?php echo esc_html($day['desc']); ?></p>
                    <div class="sj-day-tags"><?php foreach ($day['tags'] as $t): ?><span class="sj-day-tag"><?php echo esc_html($t); ?></span><?php endforeach; ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- PARCOURS -->
            <div class="sj-section">
                <h2 class="sj-section-title"><span>⛳</span> Les parcours au programme</h2>
                <div class="sj-parcours-grid">
                    <?php foreach ($sejour['parcours'] as $p) : ?>
                    <div class="sj-parcours-card">
                        <p class="sj-parcours-name"><?php echo esc_html($p['nom']); ?></p>
                        <p class="sj-parcours-stars"><?php echo esc_html($p['etoiles']); ?></p>
                        <p class="sj-parcours-info"><?php echo esc_html($p['info']); ?></p>
                        <div class="sj-parcours-badges"><?php foreach ($p['badges'] as $b): ?><span class="sj-parcours-badge"><?php echo esc_html($b); ?></span><?php endforeach; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- HÔTEL -->
            <div class="sj-section">
                <h2 class="sj-section-title"><span>🏨</span> Votre hôtel</h2>
                <div class="sj-hotel">
                    <div class="sj-hotel-img"><img src="<?php echo esc_url($sejour['hotel']['img']); ?>" alt="<?php echo esc_attr($sejour['hotel']['nom']); ?>" loading="lazy"></div>
                    <div>
                        <p class="sj-hotel-name"><?php echo esc_html($sejour['hotel']['nom']); ?></p>
                        <p class="sj-hotel-stars"><?php echo esc_html($sejour['hotel']['etoiles']); ?></p>
                        <p class="sj-hotel-desc"><?php echo esc_html($sejour['hotel']['desc']); ?></p>
                        <div class="sj-hotel-tags"><?php foreach ($sejour['hotel']['tags'] as $t): ?><span class="sj-hotel-tag"><?php echo esc_html($t); ?></span><?php endforeach; ?></div>
                    </div>
                </div>
            </div>

            <!-- INCLUS -->
            <div class="sj-section">
                <h2 class="sj-section-title"><span>✅</span> Inclus & non inclus</h2>
                <div class="sj-inclus-grid">
                    <div>
                        <p class="sj-inclus-subtitle green">Ce qui est inclus</p>
                        <ul class="sj-inclus-list"><?php foreach ($sejour['inclus'] as $item): ?><li><?php echo esc_html($item); ?></li><?php endforeach; ?></ul>
                    </div>
                    <div>
                        <p class="sj-inclus-subtitle red">Non inclus</p>
                        <ul class="sj-non-inclus-list"><?php foreach ($sejour['non_inclus'] as $item): ?><li><?php echo esc_html($item); ?></li><?php endforeach; ?></ul>
                    </div>
                </div>
            </div>

            <!-- AVIS -->
            <?php if (!empty($sejour['avis'])) : ?>
            <div class="sj-section">
                <h2 class="sj-section-title"><span>⭐</span> Avis des voyageurs</h2>
                <div class="sj-avis-grid">
                    <?php foreach ($sejour['avis'] as $avis) : ?>
                    <div class="sj-avis-card">
                        <div class="sj-avis-stars"><?php echo esc_html($avis['note']); ?></div>
                        <p class="sj-avis-text"><?php echo esc_html($avis['texte']); ?></p>
                        <div class="sj-avis-author">
                            <div class="sj-avis-avatar"><?php echo esc_html($avis['initiales']); ?></div>
                            <div><p class="sj-avis-name"><?php echo esc_html($avis['nom']); ?></p><p class="sj-avis-date"><?php echo esc_html($avis['date']); ?></p></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /sj-main -->

        <!-- BOOKING -->
        <div class="sj-booking-col">
            <div class="sj-booking-card">
                <div class="sj-price-block">
                    <p class="sj-price-from">À partir de</p>
                    <p class="sj-price-main"><?php echo esc_html($sejour['prix']); ?></p>
                    <p class="sj-price-per">/ personne · tout compris</p>
                    <span class="sj-price-note">🔒 Paiement sécurisé · Annulation flexible</span>
                </div>
                <div class="sj-form-field"><label>Date de départ</label>
                    <div id="sj-date-wrap" style="position:relative">
                        <div id="sj-date-trigger" onclick="window.sjCalDate && window.sjCalDate.toggle()" style="padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;cursor:pointer;font-size:13px;font-family:'Outfit',sans-serif;color:#9ca3af;background:#fff;transition:border-color .2s">
                            📅 Départ entre… et…
                        </div>
                    </div>
                    <input type="hidden" id="sj-date-input" name="date_depart_min">
                    <input type="hidden" id="sj-date-end" name="date_depart_max">
                </div>
                <div class="sj-form-field"><label>Ville de départ</label><select><option>Paris (CDG/Orly)</option><option>Lyon (LYS)</option><option>Lille (LIL)</option><option>Autre ville</option></select></div>
                <div class="sj-form-field"><label>Nombre de joueurs</label><select><option>1 joueur</option><option selected>2 joueurs</option><option>3 joueurs</option><option>4 joueurs</option><option>5+</option></select></div>
                <div class="sj-form-field"><label>Chambre</label><select><option>Chambre double</option><option>Chambre simple</option><option>Suite</option></select></div>
                <button class="sj-btn-reserver" onclick="window.location='<?php echo home_url('/reserver'); ?>'">Réserver ce séjour →</button>
                <a href="<?php echo home_url('/contact'); ?>" class="sj-btn-devis">✉️ Demander un devis gratuit</a>
                <div class="sj-booking-reassurance">
                    <div class="sj-reassurance-item">✅ Acompte de 30% seulement</div>
                    <div class="sj-reassurance-item">🔒 Paiement 3D Secure</div>
                    <div class="sj-reassurance-item">📞 Conseiller au 03 26 65 28 63</div>
                    <div class="sj-reassurance-item">🔄 Annulation flexible jusqu'à 30j</div>
                </div>
            </div>
            <div class="sj-share-card">
                <h4>Partager ce séjour</h4>
                <div class="sj-share-btns">
                    <button class="sj-share-btn sj-share-email" onclick="window.location='mailto:?subject=<?php echo urlencode($sejour['titre']); ?>&body=<?php echo urlencode(get_permalink()); ?>'">✉️ Email</button>
                    <button class="sj-share-btn sj-share-pdf" onclick="window.print()">📄 PDF</button>
                    <button class="sj-share-btn sj-share-wa" onclick="window.open('https://wa.me/?text=<?php echo urlencode($sejour['titre'] . ' ' . get_permalink()); ?>')">💬 WhatsApp</button>
                </div>
            </div>
        </div>

    </div>
</section>

<div class="sj-cta">
    <div>
        <h2>Ce séjour vous tente ?<br><em>Parlons-en ensemble.</em></h2>
        <p>Un conseiller vous rappelle sous 2h pour répondre à toutes vos questions.</p>
    </div>
    <div class="sj-cta-btns">
        <a href="<?php echo home_url('/contact'); ?>" class="sj-btn-coral">Demander à être rappelé</a>
        <a href="tel:0326652863" class="sj-btn-ghost">📞 03 26 65 28 63</a>
    </div>
</div>

<!-- ── Calendrier VS08 — Sidebar booking ── -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    window.sjCalDate = new VS08Calendar({
        el:       '#sj-date-wrap',
        mode:     'range',
        inline:   false,
        input:    '#sj-date-input',
        inputEnd: '#sj-date-end',
        title:    '\uD83D\uDCC5 P\u00e9riode de d\u00e9part',
        subtitle: 'D\u00e9part au plus t\u00f4t \u2192 d\u00e9part au plus tard',
        minDate:  new Date(),
        onConfirm: function(dep, ret) {
            var opts = { day: 'numeric', month: 'short' };
            var txt = '\uD83D\uDCC5 Entre ' + dep.toLocaleDateString('fr-FR', opts);
            if (ret) txt += ' et ' + ret.toLocaleDateString('fr-FR', opts);
            document.getElementById('sj-date-trigger').textContent = txt;
            document.getElementById('sj-date-trigger').style.color = '#0f2424';
            document.getElementById('sj-date-trigger').style.fontWeight = '600';
        }
    });
});
</script>

<?php get_footer(); ?>
