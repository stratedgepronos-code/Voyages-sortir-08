<?php
/**
 * Template: Single Séjour All Inclusive
 * Plugin: vs08-sejours
 */
if (!defined('ABSPATH')) exit;
get_header();

$sejour_id = get_the_ID();
$m = VS08S_Meta::get($sejour_id);
$titre = get_the_title();
$destination = $m['destination'] ?? '';
$pays = $m['pays'] ?? '';
$flag = $m['flag'] ?? '';
$duree = intval($m['duree'] ?? 7);
$duree_jours = intval($m['duree_jours'] ?? ($duree + 1));
$hotel_nom = $m['hotel_nom'] ?? '';
$hotel_etoiles = intval($m['hotel_etoiles'] ?? 5);
$pension_labels = ['ai'=>'All Inclusive','pc'=>'Pension complète','dp'=>'Demi-pension','bb'=>'Petit-déjeuner','lo'=>'Logement seul'];
$pension = $pension_labels[$m['pension'] ?? 'ai'] ?? 'All Inclusive';
$transfert_labels = ['groupes'=>'🚌 Transferts groupés','prives'=>'🚐 Transferts privés','inclus'=>'✅ Transferts inclus','aucun'=>'Non inclus'];
$transfert_txt = $transfert_labels[$m['transfert_type'] ?? 'groupes'] ?? '';
$iata_dest = strtoupper($m['iata_dest'] ?? '');
$description_courte = $m['description_courte'] ?? '';
$galerie = $m['galerie'] ?? [];
$inclus_raw = $m['inclus'] ?? '';
$non_inclus_raw = $m['non_inclus'] ?? '';
$aeroports = $m['aeroports'] ?? [];
$badge = $m['badge'] ?? '';
$badge_map = ['new'=>'Nouveauté','promo'=>'Promo','best'=>'Best-seller','derniere'=>'Dernières places'];

// Image hero
$hero_img = get_the_post_thumbnail_url($sejour_id, 'full');
if (!$hero_img && !empty($galerie[0])) $hero_img = $galerie[0];
if (!$hero_img) $hero_img = 'https://images.unsplash.com/photo-1590523741831-ab7e8b8f9c7f?w=1600&q=80';

// Flag display
$flag_display = $flag;
if (!$flag_display && class_exists('VS08V_MetaBoxes')) {
    $flag_display = VS08V_MetaBoxes::get_flag_emoji($pays ?: $destination);
}

// Prix d'appel
$prix_appel = VS08S_Calculator::prix_appel($sejour_id);
?>

<!-- ═══════════════════════════════════ HERO ══ -->
<section class="sj-hero" style="background-image:url('<?php echo esc_url($hero_img); ?>')">
    <div class="sj-hero-overlay"></div>
    <div class="sj-hero-content">
        <div class="sj-hero-dest">
            <?php if ($flag_display): ?><span style="font-size:18px"><?php echo $flag_display; ?></span><?php endif; ?>
            <?php echo esc_html($destination); ?>
        </div>
        <h1><?php the_title(); ?></h1>
        <div class="sj-hero-meta">
            <div class="sj-meta-chip">🗓️ <?php echo $duree_jours; ?> jours / <?php echo $duree; ?> nuits</div>
            <div class="sj-meta-chip">🏨 <?php echo esc_html($hotel_nom); ?> <?php echo str_repeat('★', $hotel_etoiles); ?></div>
            <div class="sj-meta-chip">🍽️ <?php echo esc_html($pension); ?></div>
            <?php if ($transfert_txt && ($m['transfert_type'] ?? '') !== 'aucun'): ?>
            <div class="sj-meta-chip"><?php echo esc_html($transfert_txt); ?></div>
            <?php endif; ?>
            <?php if ($badge && isset($badge_map[$badge])): ?>
            <div class="sj-meta-chip" style="background:rgba(232,114,74,.8);border-color:rgba(232,114,74,.9)"><?php echo esc_html($badge_map[$badge]); ?></div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════ PAGE ══ -->
<div class="sj-page">
<div class="sj-page-inner">

    <!-- ── COLONNE GAUCHE ── -->
    <div class="sj-left-col">

        <?php if ($description_courte): ?>
        <div class="sj-card">
            <p style="font-size:16px;line-height:1.7;color:#374151;font-family:'Outfit',sans-serif;margin:0"><?php echo nl2br(esc_html($description_courte)); ?></p>
        </div>
        <?php endif; ?>

        <!-- Galerie -->
        <?php if (count($galerie) >= 2): ?>
        <div class="sj-gallery">
            <?php foreach (array_slice($galerie, 0, 4) as $gi => $gurl): ?>
            <div class="sj-gal-item">
                <img src="<?php echo esc_url($gurl); ?>" alt="<?php echo esc_attr($titre . ' - Photo ' . ($gi + 1)); ?>" loading="lazy">
                <?php if ($gi === 3 && count($galerie) > 4): ?>
                <div style="position:absolute;inset:0;background:rgba(15,36,36,.6);display:flex;align-items:center;justify-content:center;color:#fff;font-family:'Outfit',sans-serif;font-size:13px;font-weight:700">+<?php echo count($galerie) - 4; ?> photos</div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Points forts -->
        <div class="sj-card">
            <h2 class="sj-section-title">✨ Les points forts</h2>
            <div class="sj-highlights">
                <div class="sj-highlight"><span class="sj-highlight-icon">✈️</span><div class="sj-highlight-text">Vol inclus A/R</div></div>
                <div class="sj-highlight"><span class="sj-highlight-icon">🏨</span><div class="sj-highlight-text"><?php echo esc_html($hotel_nom); ?> <?php echo str_repeat('★', $hotel_etoiles); ?></div></div>
                <div class="sj-highlight"><span class="sj-highlight-icon">🍽️</span><div class="sj-highlight-text"><?php echo esc_html($pension); ?></div></div>
                <?php if ($transfert_txt && ($m['transfert_type'] ?? '') !== 'aucun'): ?>
                <div class="sj-highlight"><span class="sj-highlight-icon">🚌</span><div class="sj-highlight-text"><?php echo esc_html($transfert_txt); ?></div></div>
                <?php endif; ?>
                <div class="sj-highlight"><span class="sj-highlight-icon">🗓️</span><div class="sj-highlight-text"><?php echo $duree_jours; ?> jours / <?php echo $duree; ?> nuits</div></div>
                <div class="sj-highlight"><span class="sj-highlight-icon">📋</span><div class="sj-highlight-text">Assistance VS08 24/7</div></div>
            </div>
        </div>

        <!-- Description complète -->
        <?php $content = get_the_content(); if ($content): ?>
        <div class="sj-card">
            <h2 class="sj-section-title">📝 Descriptif du séjour</h2>
            <div style="font-size:15px;line-height:1.75;color:#374151;font-family:'Outfit',sans-serif">
                <?php echo apply_filters('the_content', $content); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Inclus / Non inclus -->
        <?php if ($inclus_raw || $non_inclus_raw): ?>
        <div class="sj-card">
            <h2 class="sj-section-title">📋 Ce qui est inclus</h2>
            <div class="sj-inclus-grid">
                <?php if ($inclus_raw): ?>
                <div class="sj-inclus-box yes">
                    <h3>✅ Inclus</h3>
                    <ul>
                        <?php foreach (explode("\n", $inclus_raw) as $line): $line = trim($line); if ($line): ?>
                        <li><?php echo esc_html($line); ?></li>
                        <?php endif; endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <?php if ($non_inclus_raw): ?>
                <div class="sj-inclus-box no">
                    <h3>❌ Non inclus</h3>
                    <ul>
                        <?php foreach (explode("\n", $non_inclus_raw) as $line): $line = trim($line); if ($line): ?>
                        <li><?php echo esc_html($line); ?></li>
                        <?php endif; endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Conditions d'annulation -->
        <?php $annulation_texte = $m['annulation_texte'] ?? ''; $annulation_paliers = $m['annulation'] ?? [];
        if ($annulation_texte || !empty($annulation_paliers)): ?>
        <div class="sj-card">
            <h2 class="sj-section-title">📋 Conditions d'annulation</h2>
            <?php if ($annulation_texte): ?>
            <p style="font-size:14px;line-height:1.7;color:#374151;font-family:'Outfit',sans-serif"><?php echo nl2br(esc_html($annulation_texte)); ?></p>
            <?php endif; ?>
            <?php if (!empty($annulation_paliers) && is_array($annulation_paliers)): ?>
            <table style="width:100%;border-collapse:collapse;margin-top:12px;font-family:'Outfit',sans-serif;font-size:13px">
                <tr style="background:#0f2424;color:#fff"><th style="padding:8px 12px;text-align:left">Délai avant départ</th><th style="padding:8px 12px;text-align:right">Frais retenus</th></tr>
                <?php foreach ($annulation_paliers as $pal): ?>
                <tr><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb">Plus de <?php echo intval($pal['jours']); ?> jours</td><td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;text-align:right;font-weight:600"><?php echo intval($pal['pct']); ?>%</td></tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div><!-- /sj-left-col -->

    <!-- ── COLONNE DROITE — CALCULATEUR ── -->
    <div class="sj-calc-col">
        <div class="sj-calc-card" id="sj-calc-card" data-sejour-id="<?php echo $sejour_id; ?>">
            <div class="sj-calc-title">Réserver ce séjour</div>
            <div class="sj-calc-sub"><?php echo esc_html($flag_display . ' ' . $titre); ?> — <?php echo $duree_jours; ?>j/<?php echo $duree; ?>n</div>

            <?php if ($prix_appel > 0): ?>
            <div style="background:#edf8f8;border-radius:10px;padding:12px;margin-bottom:16px;text-align:center">
                <div style="font-size:11px;color:#6b7280;font-family:'Outfit',sans-serif">À partir de</div>
                <div style="font-size:28px;font-weight:800;color:#59b7b7;font-family:'Outfit',sans-serif"><?php echo number_format($prix_appel, 0, ',', ' '); ?> €</div>
                <div style="font-size:11px;color:#6b7280;font-family:'Outfit',sans-serif">/personne · tout compris</div>
            </div>
            <?php endif; ?>

            <!-- Étape 1 : Aéroport -->
            <div class="sj-field">
                <label>✈️ Aéroport de départ</label>
                <select id="sj-aeroport">
                    <option value="">Choisissez votre aéroport</option>
                    <?php foreach ($aeroports as $a): ?>
                    <option value="<?php echo esc_attr(strtoupper($a['code'])); ?>">
                        <?php echo esc_html(strtoupper($a['code']) . ' — ' . ($a['ville'] ?? '')); ?>
                        <?php if (floatval($a['supplement'] ?? 0) > 0): ?>(+<?php echo number_format($a['supplement'], 0); ?>€/pers)<?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Étape 1b : Voyageurs -->
            <div class="sj-field-row">
                <div class="sj-field">
                    <label>👤 Voyageurs</label>
                    <select id="sj-adults">
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php selected($i, 2); ?>><?php echo $i; ?> adulte<?php echo $i > 1 ? 's' : ''; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="sj-field">
                    <label>🚪 Chambres</label>
                    <select id="sj-rooms">
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php selected($i, 1); ?>><?php echo $i; ?> chambre<?php echo $i > 1 ? 's' : ''; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <!-- Étape 2 : Calendrier (apparaît après sélection aéroport) -->
            <div id="sj-calendar-wrap" style="display:none;margin-bottom:14px">
                <label style="display:block;font-size:10px;font-weight:700;color:#1a3a3a;text-transform:uppercase;letter-spacing:1px;margin-bottom:5px;font-family:'Outfit',sans-serif">📅 Date de départ</label>
                <input type="date" id="sj-date" style="display:none;width:100%;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;font-family:'Outfit',sans-serif" min="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
            </div>

            <!-- Loading -->
            <div class="sj-hotel-loading" id="sj-loading">
                <div class="sj-hotel-spinner"></div>
                <span>Recherche en cours...</span>
            </div>

            <!-- Erreur -->
            <div id="sj-error" style="display:none;padding:12px;background:#fee2e2;color:#dc2626;border-radius:10px;font-size:13px;font-family:'Outfit',sans-serif;margin-bottom:14px"></div>

            <!-- Résultat hôtel -->
            <div class="sj-hotel-result" id="sj-hotel-result">
                <div class="sj-hotel-result-name"></div>
                <div class="sj-hotel-result-board"></div>
                <div class="sj-hotel-result-price"></div>
            </div>

            <!-- Prix détaillé -->
            <div class="sj-price-result" id="sj-price-result">
                <div class="sj-price-lines"></div>
                <div class="sj-price-total">
                    <span class="sj-price-total-lbl">Total</span>
                    <span class="sj-price-total-val" id="sj-total-val">—</span>
                </div>
                <div class="sj-price-per" id="sj-per-person"></div>
            </div>

            <!-- CTA -->
            <button class="sj-btn-book" id="sj-btn-book" disabled>
                🔒 Réserver ce séjour
            </button>

            <div style="margin-top:16px;text-align:center">
                <div style="font-size:11px;color:#6b7280;font-family:'Outfit',sans-serif">
                    🔒 Paiement sécurisé · ✅ Garantie APST<br>
                    📞 <a href="tel:0326652863" style="color:#59b7b7;font-weight:600">03 26 65 28 63</a>
                </div>
            </div>
        </div>

        <!-- Besoin d'aide -->
        <div style="background:#0f2424;border-radius:14px;padding:18px;margin-top:14px">
            <div style="font-size:11px;font-weight:700;color:#7ecece;text-transform:uppercase;letter-spacing:1.5px;font-family:'Outfit',sans-serif;margin-bottom:12px">Besoin d'aide ?</div>
            <div style="font-size:16px;font-weight:700;color:#fff;font-family:'Playfair Display',serif;margin-bottom:4px">03 26 65 28 63</div>
            <div style="font-size:11px;color:rgba(255,255,255,.5);font-family:'Outfit',sans-serif;line-height:1.7">Lun–Ven 09h–12h / 14h–18h30<br>Sam 09h–12h / 14h–18h00</div>
        </div>
    </div><!-- /sj-calc-col -->

</div><!-- /sj-page-inner -->
</div><!-- /sj-page -->

<?php get_footer(); ?>
