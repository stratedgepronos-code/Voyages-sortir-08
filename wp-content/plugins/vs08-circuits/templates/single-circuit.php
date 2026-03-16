<?php
/**
 * Template: Single Circuit
 * Design: Luxury editorial matching VS08 theme
 */
get_header();
while (have_posts()) : the_post();
    $id = get_the_ID();
    $m  = VS08C_Meta::get($id);
    $flag = VS08C_Meta::resolve_flag($m);
    $galerie   = $m['galerie'] ?? [];
    $jours     = $m['jours'] ?? [];
    $aeroports = $m['aeroports'] ?? [];
    $options   = $m['options'] ?? [];
    $hotels    = $m['hotels'] ?? [];
    $duree_j   = intval($m['duree_jours'] ?? 8);
    $duree_n   = intval($m['duree'] ?? 7);
    $hero_img  = !empty($galerie[0]) ? $galerie[0] : get_the_post_thumbnail_url($id, 'full');
    $pension_labels = ['bb'=>'Petit-déjeuner','dp'=>'Demi-pension','pc'=>'Pension complète (hors boissons)','ai'=>'Tout inclus','mixed'=>'Selon programme'];
    $transport_labels = ['bus'=>'Bus climatisé','4x4'=>'4x4','voiture'=>'Voiture de location','train'=>'Train','mixed'=>'Transport mixte'];
    $badge_labels = ['new'=>'Nouveauté','best'=>'Coup de cœur','promo'=>'Promo','derniere'=>'Dernières places'];
    $etoiles_map = ['2'=>'★★','3'=>'★★★','4'=>'★★★★','5'=>'★★★★★','riad'=>'Riad','camp'=>'Bivouac'];
?>

<!-- JS Data -->
<script>
var VS08C_CIRCUIT = <?php echo json_encode([
    'id'            => $id,
    'titre'         => get_the_title(),
    'duree'         => $duree_n,
    'prix_double'   => floatval($m['prix_double'] ?? 0),
    'prix_vol_base' => floatval($m['prix_vol_base'] ?? 0),
    'prix_triple'   => floatval($m['prix_triple'] ?? 0),
    'iata_dest'     => strtoupper($m['iata_dest'] ?? ''),
    'aeroports'     => $aeroports,
    'options'       => $options,
    'booking_url'   => home_url('/reservation-circuit/' . $id),
]); ?>;
</script>

<!-- BADGE -->
<?php if (!empty($m['badge']) && isset($badge_labels[$m['badge']])): ?>
<div class="vc-badge"><?php echo esc_html($badge_labels[$m['badge']]); ?></div>
<?php endif; ?>

<!-- ═══ HERO ═══ -->
<section class="vc-hero" style="background-image:url('<?php echo esc_url($hero_img); ?>')">
    <div class="vc-hero-overlay"></div>
    <div class="vc-hero-content">
        <div class="vc-breadcrumb">
            <a href="<?php echo home_url(); ?>">Accueil</a> <span>›</span>
            <a href="<?php echo home_url('/resultats-recherche?type=circuit'); ?>">Circuits</a> <span>›</span>
            <?php the_title(); ?>
        </div>
        <div class="vc-hero-dest"><?php echo esc_html($flag . ' ' . ($m['destination'] ?? '')); ?></div>
        <h1><?php the_title(); ?></h1>
        <div class="vc-hero-meta">
            <span class="vc-meta-chip">📅 <?php echo $duree_j; ?>j / <?php echo $duree_n; ?>n</span>
            <span class="vc-meta-chip">🍽️ <?php echo esc_html($pension_labels[$m['pension'] ?? ''] ?? ''); ?></span>
            <span class="vc-meta-chip">🚌 <?php echo esc_html($transport_labels[$m['transport'] ?? ''] ?? ''); ?></span>
            <span class="vc-meta-chip">✈️ Vol inclus</span>
            <?php if (!empty($m['guide_lang'])): ?><span class="vc-meta-chip">🗣️ Guide <?php echo esc_html($m['guide_lang']); ?></span><?php endif; ?>
            <?php if ($m['prix_double'] ?? 0): ?><span class="vc-meta-chip" style="background:rgba(89,183,183,.3);border-color:rgba(89,183,183,.5)">Dès <?php echo number_format($m['prix_double'], 0, ',', ' '); ?> €/pers.</span><?php endif; ?>
        </div>
    </div>
</section>

<!-- ═══ MAIN CONTENT ═══ -->
<div class="vc-page">
    <div class="vc-inner">
        <!-- LEFT COLUMN -->
        <div class="vc-main">

            <!-- GALLERY -->
            <?php if (count($galerie) > 1): ?>
            <div class="vc-gallery">
                <?php foreach (array_slice($galerie, 0, 4) as $gi => $img): ?>
                <div class="vc-gal-item">
                    <img src="<?php echo esc_url($img); ?>" alt="<?php the_title(); ?> - Photo <?php echo $gi + 1; ?>" loading="lazy">
                    <?php if ($gi === 3 && count($galerie) > 4): ?>
                    <div class="vc-gal-more">+<?php echo count($galerie) - 4; ?> photos</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- DESCRIPTION + HIGHLIGHTS -->
            <section class="vc-section">
                <h2 class="vc-section-title">🗺️ Le circuit</h2>
                <div class="vc-desc">
                    <?php echo wp_kses_post(wpautop($m['description'] ?? get_the_excerpt())); ?>
                </div>
                <?php
                $points = array_filter(array_map('trim', explode("\n", $m['points_forts'] ?? '')));
                if (!empty($points)):
                ?>
                <div class="vc-highlights">
                    <?php foreach (array_slice($points, 0, 4) as $pt): ?>
                    <div class="vc-hl">
                        <div class="vc-hl-icon">✨</div>
                        <div style="font-size:13px;color:#1a3a3a;font-family:'Outfit',sans-serif;font-weight:600"><?php echo esc_html($pt); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Highlights row -->
                <div class="vc-highlights" style="margin-top:14px">
                    <div class="vc-hl">
                        <div class="vc-hl-icon">📅</div>
                        <div class="vc-hl-val"><?php echo $duree_j; ?>J/<?php echo $duree_n; ?>N</div>
                        <div class="vc-hl-lbl">Durée</div>
                    </div>
                    <div class="vc-hl">
                        <div class="vc-hl-icon">👥</div>
                        <div class="vc-hl-val"><?php echo intval($m['group_min'] ?? 2); ?>-<?php echo intval($m['group_max'] ?? 20); ?></div>
                        <div class="vc-hl-lbl">Personnes</div>
                    </div>
                    <div class="vc-hl">
                        <div class="vc-hl-icon">🗣️</div>
                        <div class="vc-hl-val"><?php echo esc_html($m['guide_lang'] ?? 'FR'); ?></div>
                        <div class="vc-hl-lbl">Guide</div>
                    </div>
                </div>
            </section>

            <!-- ITINERARY -->
            <?php if (!empty($jours)): ?>
            <section class="vc-section">
                <h2 class="vc-section-title">🗓️ Programme jour par jour</h2>
                <div class="vc-itinerary">
                    <?php foreach ($jours as $ji => $jour):
                        if (empty($jour['titre'])) continue;
                    ?>
                    <div class="vc-day">
                        <div class="vc-day-header">
                            <span class="vc-day-num">Jour <?php echo $ji + 1; ?></span>
                            <span class="vc-day-title"><?php echo esc_html($jour['titre']); ?></span>
                            <span class="vc-day-toggle">▾</span>
                        </div>
                        <div class="vc-day-body">
                            <?php if (!empty($jour['description'])): ?>
                            <div class="vc-day-desc"><?php echo nl2br(esc_html($jour['description'])); ?></div>
                            <?php endif; ?>

                            <div class="vc-day-info">
                                <?php if (!empty($jour['repas'])): ?>
                                <span class="vc-day-info-item">🍽️ <?php echo esc_html($jour['repas']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($jour['nuit'])): ?>
                                <span class="vc-day-info-item">🏨 <?php echo esc_html($jour['nuit']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($jour['transport'])): ?>
                                <span class="vc-day-info-item">🚌 <?php echo esc_html($jour['transport']); ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($jour['tags'])):
                                $tags = array_filter(array_map('trim', explode(',', $jour['tags'])));
                            ?>
                            <div class="vc-day-tags">
                                <?php foreach ($tags as $tag): ?>
                                <span class="vc-day-tag"><?php echo esc_html($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($jour['image'])): ?>
                            <div class="vc-day-image">
                                <img src="<?php echo esc_url($jour['image']); ?>" alt="Jour <?php echo $ji + 1; ?>" loading="lazy">
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- OPTIONS / SUPPLÉMENTS (juste sous l'itinéraire) -->
            <?php if (!empty($options)): ?>
            <section class="vc-section vc-options-section">
                <h2 class="vc-section-title">🎁 Options & suppléments</h2>
                <p style="font-size:13px;color:#6b7280;font-family:'Outfit',sans-serif;margin:-8px 0 18px">Personnalisez votre circuit : les montants s'ajoutent au total dans le récapitulatif de réservation.</p>
                <div class="vc-options-grid">
                    <?php foreach ($options as $opt):
                        $opt_type_txt = ($opt['type'] ?? 'par_pers') === 'par_pers' ? '/pers.' : (($opt['type'] ?? '') === 'fixe' ? ' forfait' : '/unité');
                        $prix_fmt = number_format(floatval($opt['prix'] ?? 0), 0, ',', ' ');
                    ?>
                    <div class="vc-option-card">
                        <div class="vc-option-icon">✨</div>
                        <div class="vc-option-info">
                            <div class="vc-option-name"><?php echo esc_html($opt['label'] ?? ''); ?></div>
                            <div class="vc-option-price"><?php echo $prix_fmt; ?> €<?php echo $opt_type_txt; ?></div>
                        </div>
                        <label class="vc-option-toggle">
                            <input type="checkbox" class="vc-option-check" data-opt-id="<?php echo esc_attr($opt['id'] ?? ''); ?>" data-prix="<?php echo floatval($opt['prix'] ?? 0); ?>" data-type="<?php echo esc_attr($opt['type'] ?? 'par_pers'); ?>">
                            <span class="vc-option-slider"></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- HOTELS -->
            <?php if (!empty($hotels)): ?>
            <section class="vc-section">
                <h2 class="vc-section-title">🏨 Hébergements</h2>
                <div class="vc-hotels-list">
                    <?php foreach ($hotels as $hotel):
                        if (empty($hotel['nom'])) continue;
                        $stars = $etoiles_map[$hotel['etoiles'] ?? '4'] ?? '';
                    ?>
                    <div class="vc-hotel-card">
                        <div class="vc-hotel-icon">🏨</div>
                        <div class="vc-hotel-info">
                            <div class="vc-hotel-name"><?php echo esc_html($hotel['nom']); ?> <span class="vc-hotel-stars"><?php echo $stars; ?></span></div>
                            <div class="vc-hotel-detail"><?php echo esc_html($hotel['ville'] ?? ''); ?></div>
                        </div>
                        <div class="vc-hotel-nights"><?php echo intval($hotel['nuits'] ?? 1); ?> nuit<?php echo intval($hotel['nuits'] ?? 1) > 1 ? 's' : ''; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- INCLUS / NON INCLUS -->
            <section class="vc-section">
                <h2 class="vc-section-title">✅ Ce qui est inclus</h2>
                <div class="vc-inclus-grid">
                    <div class="vc-inclus-col">
                        <h4>✅ Inclus</h4>
                        <ul class="vc-inclus-list">
                            <?php foreach (array_filter(array_map('trim', explode("\n", $m['inclus'] ?? ''))) as $item): ?>
                            <li><span class="vc-check">✓</span> <?php echo esc_html($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="vc-inclus-col">
                        <h4>❌ Non inclus</h4>
                        <ul class="vc-inclus-list">
                            <?php foreach (array_filter(array_map('trim', explode("\n", $m['non_inclus'] ?? ''))) as $item): ?>
                            <li><span class="vc-cross">✕</span> <?php echo esc_html($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- PRACTICAL INFO -->
            <?php
            $practical = [];
            if (!empty($m['formalites'])) $practical[] = ['icon' => '🛂', 'title' => 'Formalités', 'text' => $m['formalites']];
            if (!empty($m['sante']))      $practical[] = ['icon' => '💉', 'title' => 'Santé / Vaccins', 'text' => $m['sante']];
            if (!empty($m['climat']))     $practical[] = ['icon' => '🌤️', 'title' => 'Climat', 'text' => $m['climat']];
            if (!empty($m['monnaie']))    $practical[] = ['icon' => '💱', 'title' => 'Monnaie & Pourboires', 'text' => $m['monnaie']];
            if (!empty($practical)):
            ?>
            <section class="vc-section">
                <h2 class="vc-section-title">ℹ️ Informations pratiques</h2>
                <div class="vc-practical-grid">
                    <?php foreach ($practical as $p): $pid = 'vc-practical-' . sanitize_title($p['title']); ?>
                    <div class="vc-practical-card">
                        <button type="button" class="vc-practical-card-header" aria-expanded="false" aria-controls="<?php echo esc_attr($pid); ?>">
                            <span class="vc-practical-card-title"><?php echo $p['icon'] . ' ' . esc_html($p['title']); ?></span>
                            <span class="vc-practical-card-toggle" aria-hidden="true">▾</span>
                        </button>
                        <div class="vc-practical-card-body" id="<?php echo esc_attr($pid); ?>" hidden>
                            <p><?php echo esc_html($p['text']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- CONDITIONS -->
            <?php if (!empty($m['annulation'])): ?>
            <section class="vc-section">
                <h2 class="vc-section-title">⚠️ Conditions d'annulation</h2>
                <div class="vc-desc">
                    <p><?php echo nl2br(esc_html($m['annulation'])); ?></p>
                </div>
            </section>
            <?php endif; ?>

        </div>

        <!-- ═══ STICKY SIDEBAR — CALCULATOR ═══ -->
        <div class="vc-calc-col">
            <div class="vc-calc-card">
                <div class="vc-calc-title">Réserver ce circuit</div>
                <div class="vc-calc-sub"><?php echo esc_html($flag . ' ' . get_the_title()); ?> — <?php echo $duree_j; ?>j/<?php echo $duree_n; ?>n</div>
                <div class="vc-calc-hint">Choisissez d'abord votre aéroport de départ → le calendrier des jours ouverts s'affichera ensuite.</div>

                <!-- 1. AÉROPORT -->
                <div class="vc-field">
                    <label>✈️ Aéroport de départ <span class="vc-required-dot">*</span></label>
                    <select id="vc-aeroport">
                        <option value="">— Choisir un aéroport —</option>
                        <?php foreach ($aeroports as $a):
                            $supp = floatval($a['supp'] ?? 0);
                            $lbl = strtoupper($a['code']) . ' — ' . ($a['label'] ?? '');
                            if ($supp > 0) $lbl .= ' (+' . intval($supp) . '€)';
                        ?>
                        <option value="<?php echo esc_attr(strtoupper($a['code'])); ?>"><?php echo esc_html($lbl); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 2. DATE DE DÉPART — VS08 Calendar (apparaît après choix aéroport) -->
                <div class="vc-field" id="vc-field-date-block" style="display:none">
                    <label>📅 Date de départ</label>
                    <div id="vc-date-wrap" style="position:relative"></div>
                    <input type="hidden" id="vc-date-depart">
                </div>
                <p class="vc-date-hint" id="vc-date-hint">Choisissez d'abord un aéroport ci-dessus.</p>

                <!-- VOL STATUS -->
                <div class="vc-vol-status" id="vc-vol-status"></div>

                <!-- STEP 2 — caché tant que aéroport + date + vol pas ok -->
                <div id="vc-step2" style="display:none">

                    <!-- VOYAGEURS -->
                    <div class="vc-field">
                        <label>👥 Voyageurs</label>
                        <select id="vc-nb-adultes">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php selected($i, 2); ?>><?php echo $i; ?> voyageur<?php echo $i > 1 ? 's' : ''; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <!-- CHAMBRES -->
                    <div class="vc-field">
                        <label>🛏️ Nombre de chambres</label>
                        <select id="vc-nb-chambres">
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?> chambre<?php echo $i > 1 ? 's' : ''; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <!-- Chambres auto-générées -->
                    <div class="vc-rooms-section" id="vc-rooms-section"></div>

                    <!-- RÉSULTAT PRIX -->
                    <div class="vc-price-loading" id="vc-price-loading" style="display:none">⏳ Calcul en cours...</div>
                    <div class="vc-price-result" id="vc-price-result"></div>

                    <!-- CTA -->
                    <button class="vc-cta-btn" disabled>
                        Réserver ce circuit →
                    </button>
                </div>

                <div class="vc-reassurance">
                    <div class="vc-reass-item">🛡️ Assurances multirisques en option</div>
                    <div class="vc-reass-item">🔒 Paiement 100% sécurisé</div>
                    <div class="vc-reass-item" style="font-weight:700;color:#0f2424">☎ Conseiller disponible<br><span style="font-weight:400;color:#6b7280">03 26 65 28 63<br>Lun — Ven 09h00 – 12h00 / 14h00 – 18h30<br>Samedi 09h00 – 12h00 / 14h00 – 18h00</span></div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php endwhile; get_footer(); ?>
