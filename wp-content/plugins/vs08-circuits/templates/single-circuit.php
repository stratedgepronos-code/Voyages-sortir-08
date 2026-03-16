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
    $dates     = $m['dates_depart'] ?? [];
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
    'id'             => $id,
    'titre'          => get_the_title(),
    'duree'          => $duree_n,
    'prix_double'    => floatval($m['prix_double'] ?? 0),
    'prix_vol_base'  => floatval($m['prix_vol_base'] ?? 0),
    'iata_dest'      => strtoupper((string)($m['iata_dest'] ?? '')),
    'aeroports'      => $aeroports,
    'dates'          => $dates,
    'options'        => $options,
    'booking_url'    => home_url('/reservation-circuit/' . $id),
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
                    <?php foreach ($practical as $p): ?>
                    <div class="vc-practical-card">
                        <h4><?php echo $p['icon'] . ' ' . esc_html($p['title']); ?></h4>
                        <p><?php echo esc_html($p['text']); ?></p>
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

                <!-- Date -->
                <div class="vc-field">
                    <label>📅 Date de départ <span class="vc-required-dot">*</span></label>
                    <select id="vc-date-depart">
                        <option value="">— Choisir une date —</option>
                        <?php
                        // Générer les dates depuis les périodes
                        $periodes = $dates; // $dates = $m['dates_depart']
                        $generated_dates = [];
                        foreach ($periodes as $per) {
                            if (($per['statut'] ?? '') === 'complet') continue;
                            $debut = $per['date_debut'] ?? '';
                            $fin   = $per['date_fin'] ?? '';
                            if (!$debut || !$fin) continue;
                            $ts_debut = strtotime($debut);
                            $ts_fin   = strtotime($fin);
                            if (!$ts_debut || !$ts_fin) continue;
                            $jours_ok = $per['jours_depart'] ?? [1,2,3,4,5,6,7];
                            if (empty($jours_ok)) $jours_ok = [1,2,3,4,5,6,7];
                            $supp   = floatval($per['supp'] ?? 0);
                            $statut = $per['statut'] ?? 'ouvert';

                            // Parcourir chaque jour de la période
                            $current = $ts_debut;
                            while ($current <= $ts_fin) {
                                // Vérifier que la date est dans le futur
                                if ($current > time()) {
                                    // Jour de la semaine PHP: 1=Lun ... 7=Dim
                                    $dow = (int) date('N', $current);
                                    if (in_array($dow, $jours_ok)) {
                                        $date_str = date('Y-m-d', $current);
                                        if (!isset($generated_dates[$date_str])) {
                                            $generated_dates[$date_str] = [
                                                'date'   => $date_str,
                                                'supp'   => $supp,
                                                'statut' => $statut,
                                            ];
                                        }
                                    }
                                }
                                $current = strtotime('+1 day', $current);
                            }
                        }
                        // Trier par date
                        ksort($generated_dates);
                        $jours_noms = [1=>'Lun',2=>'Mar',3=>'Mer',4=>'Jeu',5=>'Ven',6=>'Sam',7=>'Dim'];
                        foreach ($generated_dates as $gd):
                            $ts = strtotime($gd['date']);
                            $dow = (int) date('N', $ts);
                            $supp_txt = $gd['supp'] > 0 ? ' (+' . intval($gd['supp']) . '€/pers)' : '';
                            $garanti = $gd['statut'] === 'garanti' ? ' ✅ Garanti' : '';
                            $label = ($jours_noms[$dow] ?? '') . '. ' . date('d/m/Y', $ts) . $supp_txt . $garanti;
                        ?>
                        <option value="<?php echo esc_attr($gd['date']); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Aéroport -->
                <div class="vc-field">
                    <label>✈️ Aéroport de départ <span class="vc-required-dot">*</span></label>
                    <select id="vc-aeroport">
                        <option value="">— Choisir —</option>
                        <?php foreach ($aeroports as $a):
                            $supp = floatval($a['supp'] ?? 0);
                            $lbl = strtoupper($a['code']) . ' — ' . ($a['label'] ?? '');
                            if ($supp > 0) $lbl .= ' (+' . intval($supp) . '€)';
                        ?>
                        <option value="<?php echo esc_attr(strtoupper($a['code'])); ?>"><?php echo esc_html($lbl); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Voyageurs (adultes uniquement) -->
                <div class="vc-field">
                    <label>👥 Nombre de voyageurs</label>
                    <select id="vc-nb-adultes">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php selected($i, 2); ?>><?php echo $i; ?> voyageur<?php echo $i > 1 ? 's' : ''; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <!-- Nombre de chambres -->
                <div class="vc-field">
                    <label>🛏️ Nombre de chambres</label>
                    <select id="vc-nb-chambres">
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?> chambre<?php echo $i > 1 ? 's' : ''; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <!-- Chambres auto-générées -->
                <div class="vc-rooms-section" id="vc-rooms-section">
                    <!-- Les chambres seront générées automatiquement par le JS -->
                </div>

                <!-- Statut vol (recherche Duffel) -->
                <div class="vc-vol-status" id="vc-vol-status" style="display:none; font-size:12px; padding:8px 12px; border-radius:8px; margin-bottom:12px; font-family:'Outfit',sans-serif"></div>

                <?php if (!empty($options)): ?>
                <!-- Options / Suppléments -->
                <div class="vc-options-block">
                    <div class="vc-field" style="margin-bottom:8px"><label>🎁 Options & suppléments</label></div>
                    <?php foreach ($options as $oi => $opt):
                        $oid = $opt['id'] ?? 'opt_' . $oi;
                        $type = $opt['type'] ?? 'par_pers';
                        $prix_opt = floatval($opt['prix'] ?? 0);
                        $label_opt = esc_html($opt['label'] ?? 'Option');
                        if ($type === 'quantite'): ?>
                    <div class="vc-option-row">
                        <span class="vc-option-label"><?php echo $label_opt; ?></span>
                        <span class="vc-option-price"><?php echo number_format($prix_opt, 0); ?> €<?php echo $type === 'par_pers' ? '/pers.' : ''; ?></span>
                        <select name="vc_opt_<?php echo esc_attr($oid); ?>" class="vc-option-qty" data-id="<?php echo esc_attr($oid); ?>" data-type="<?php echo esc_attr($type); ?>" data-prix="<?php echo esc_attr($prix_opt); ?>">
                            <?php for ($q = 0; $q <= 5; $q++): ?><option value="<?php echo $q; ?>"><?php echo $q; ?></option><?php endfor; ?>
                        </select>
                    </div>
                        <?php else: ?>
                    <div class="vc-option-row">
                        <label class="vc-option-label" style="cursor:pointer;display:flex;align-items:center;gap:8px">
                            <input type="checkbox" class="vc-option-cb" name="vc_opt_<?php echo esc_attr($oid); ?>" value="1" data-id="<?php echo esc_attr($oid); ?>" data-type="<?php echo esc_attr($type); ?>" data-prix="<?php echo esc_attr($prix_opt); ?>">
                            <span><?php echo $label_opt; ?></span>
                        </label>
                        <span class="vc-option-price"><?php echo number_format($prix_opt, 0); ?> €<?php echo $type === 'par_pers' ? '/pers.' : ''; ?></span>
                    </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Price Result -->
                <div class="vc-price-result">
                    <div class="vc-price-placeholder" style="text-align:center;color:#9ca3af;font-size:13px;font-family:'Outfit',sans-serif;padding:12px 0">
                        Choisissez date et aéroport → le prix vol sera cherché puis ajouté au total
                    </div>
                </div>

                <!-- CTA -->
                <button class="vc-cta-btn" disabled>
                    Réserver ce circuit →
                </button>
            </div>

            <!-- Contact Card -->
            <div class="vc-contact-card">
                <h3>Un conseiller à votre écoute</h3>
                <p>Personnalisez ce circuit selon vos envies</p>
                <a href="tel:0326652863" class="vc-contact-phone">📞 03 26 65 28 63</a>
            </div>
        </div>

    </div>
</div>

<?php endwhile; get_footer(); ?>
