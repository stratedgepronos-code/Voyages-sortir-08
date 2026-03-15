<?php
if (!defined('ABSPATH')) exit;
get_header();
while (have_posts()) : the_post();
    $id = get_the_ID();
    $m = VS08C_MetaBoxes::get($id);
    $duree_jours = (int) ($m['duree_jours'] ?? 8);
    $periodes = $m['periodes'] ?? [];
    $itineraire = $m['itineraire'] ?? [];
    $inclus = $m['inclus'] ?? [];
    $non_inclus = $m['non_inclus'] ?? [];
    $sous_titre = $m['sous_titre'] ?? '';
    $galerie = $m['galerie'] ?? [];
    $prix_min = 0;
    foreach ($periodes as $p) {
        $pr = isset($p['prix']) ? floatval($p['prix']) : 0;
        if ($pr > 0 && ($prix_min === 0 || $pr < $prix_min)) $prix_min = $pr;
    }
    $hero_img = !empty($galerie[0]) ? wp_get_attachment_image_url((int)$galerie[0], 'full') : get_the_post_thumbnail_url($id, 'full');
    $destinations = get_the_terms($id, 'circuit_destination');
    $themes = get_the_terms($id, 'circuit_theme');
    $dest_name = $destinations && !is_wp_error($destinations) ? $destinations[0]->name : '';
?>
<div class="sv-page" style="background:#f9f6f0;padding:0 0 60px">
    <!-- HERO — même style que voyages golf -->
    <div class="sv-hero" style="background-image:url('<?php echo $hero_img ? esc_url($hero_img) : ''; ?>')">
        <div class="sv-hero-overlay"></div>
        <div class="sv-hero-content">
            <?php if ($dest_name): ?>
            <div class="sv-hero-dest"><?php echo esc_html($dest_name); ?></div>
            <?php endif; ?>
            <h1><?php the_title(); ?></h1>
            <?php if ($sous_titre): ?><p class="sv-hero-meta" style="margin:0"><span class="sv-meta-chip"><?php echo esc_html($sous_titre); ?></span></p><?php endif; ?>
            <div class="sv-hero-meta">
                <span class="sv-meta-chip"><?php echo $duree_jours; ?> jours</span>
                <?php if ($themes && !is_wp_error($themes)): foreach ($themes as $t): ?>
                    <span class="sv-meta-chip"><?php echo esc_html($t->name); ?></span>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <div class="sv-page-inner">
        <div class="sv-left-col">
            <!-- Présentation -->
            <div class="sv-card" id="sec-presentation">
                <h2 class="sv-section-title">✦ Ce circuit en quelques mots</h2>
                <div class="sv-desc"><?php the_content(); ?></div>
                <div class="sv-highlights">
                    <div class="sv-hl"><div class="sv-hl-icon">🗓️</div><div class="sv-hl-val" style="font-size:18px;line-height:1.2"><?php echo $duree_jours; ?> jours</div><div class="sv-hl-lbl">Durée</div></div>
                    <?php if ($dest_name): ?>
                    <div class="sv-hl"><div class="sv-hl-icon">📍</div><div class="sv-hl-val" style="font-size:14px;line-height:1.2"><?php echo esc_html($dest_name); ?></div><div class="sv-hl-lbl">Destination</div></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Itinéraire -->
            <?php if (!empty($itineraire)): ?>
            <div class="sv-card" id="sec-programme">
                <h2 class="sv-section-title">🗓️ Programme jour par jour</h2>
                <?php foreach ($itineraire as $i => $j): ?>
                <div class="sv-day">
                    <p class="sv-day-num">Jour <?php echo $i + 1; ?></p>
                    <p class="sv-day-title"><?php echo esc_html($j['titre'] ?? ''); ?></p>
                    <?php if (!empty($j['desc'])): ?><p class="sv-day-desc"><?php echo nl2br(esc_html($j['desc'])); ?></p><?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Inclus / Non inclus -->
            <?php if (!empty($inclus) || !empty($non_inclus)): ?>
            <div class="sv-card" id="sec-compris">
                <h2 class="sv-section-title">✅ Ce qui est compris / non compris</h2>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
                    <?php if (!empty($inclus)): ?>
                    <div>
                        <p style="font-size:11px;font-weight:700;color:#59b7b7;text-transform:uppercase;letter-spacing:1px;margin:0 0 10px">Inclus</p>
                        <ul style="margin:0;padding:0;list-style:none">
                            <?php foreach ($inclus as $item): ?><li style="padding:5px 0;font-size:14px;color:#4a5568;font-family:'Outfit',sans-serif;padding-left:18px;position:relative"><span style="position:absolute;left:0;color:#59b7b7">✓</span><?php echo esc_html($item); ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($non_inclus)): ?>
                    <div>
                        <p style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:1px;margin:0 0 10px">Non inclus</p>
                        <ul style="margin:0;padding:0;list-style:none">
                            <?php foreach ($non_inclus as $item): ?><li style="padding:5px 0;font-size:14px;color:#4a5568;font-family:'Outfit',sans-serif;padding-left:18px;position:relative"><span style="position:absolute;left:0;color:#9ca3af">–</span><?php echo esc_html($item); ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Galerie -->
            <?php if (count($galerie) > 1): $galerie_urls = array_values(array_filter(array_map(function($aid) { return wp_get_attachment_image_url((int)$aid, 'large'); }, array_filter($galerie)))); $nb_gal = count($galerie_urls); ?>
            <div class="sv-carousel-wrap" id="sec-photos">
                <h2 class="sv-section-title">📷 Galerie photos</h2>
                <div class="sv-carousel" id="sv-carousel-circuit">
                    <div class="sv-carousel-track" id="sv-carousel-track-circuit">
                        <?php foreach ($galerie_urls as $url): ?><img src="<?php echo esc_url($url); ?>" alt="" loading="lazy"><?php endforeach; ?>
                    </div>
                    <button type="button" class="sv-carousel-btn" id="sv-prev-circuit">&#8249;</button>
                    <button type="button" class="sv-carousel-btn" id="sv-next-circuit">&#8250;</button>
                    <div class="sv-carousel-counter" id="sv-counter-circuit">1 / <?php echo $nb_gal; ?></div>
                </div>
                <div class="sv-dots" id="sv-dots-circuit">
                    <?php for ($i = 0; $i < $nb_gal; $i++): ?><div class="sv-dot<?php echo $i === 0 ? ' active' : ''; ?>" data-index="<?php echo $i; ?>"></div><?php endfor; ?>
                </div>
            </div>
            <script>
            (function(){
                var track = document.getElementById('sv-carousel-track-circuit');
                var counter = document.getElementById('sv-counter-circuit');
                var dots = document.querySelectorAll('#sv-dots-circuit .sv-dot');
                var total = track ? track.children.length : 0;
                var idx = 0;
                function update(){
                    if (!track || total === 0) return;
                    idx = (idx % total + total) % total;
                    track.style.transform = 'translateX(-' + (idx * 100) + '%)';
                    if (counter) counter.textContent = (idx + 1) + ' / ' + total;
                    dots.forEach(function(d, i){ d.classList.toggle('active', i === idx); });
                }
                var prev = document.getElementById('sv-prev-circuit');
                var next = document.getElementById('sv-next-circuit');
                if (prev) prev.addEventListener('click', function(){ idx--; update(); });
                if (next) next.addEventListener('click', function(){ idx++; update(); });
                dots.forEach(function(d){ d.addEventListener('click', function(){ idx = parseInt(d.getAttribute('data-index'), 10); update(); }); });
            })();
            </script>
            <?php endif; ?>
        </div>

        <!-- Colonne droite sticky — même style que voyages -->
        <div class="sv-right-col">
            <div class="sv-calc-card">
                <p class="sv-calc-title">Réserver ce circuit</p>
                <p class="sv-calc-sub">Prix par personne selon la période. Recherche de vol à l’étape suivante.</p>
                <div class="sv-price-box">
                    <p class="sv-price-from">À partir de</p>
                    <?php if ($prix_min > 0): ?>
                    <p class="sv-price-main"><?php echo number_format($prix_min, 0, ',', ' '); ?> €</p>
                    <p class="sv-price-per">par personne</p>
                    <?php else: ?>
                    <p class="sv-price-main">Sur devis</p>
                    <?php endif; ?>
                </div>
                <?php if (!empty($periodes)): ?>
                <div style="font-size:11px;color:#9ca3af;margin-bottom:6px;font-family:'Outfit',sans-serif">Prix par période</div>
                <?php foreach ($periodes as $p):
                    $prix = isset($p['prix']) ? floatval($p['prix']) : 0;
                    if ($prix <= 0) continue;
                    $label = $p['label'] ?? '';
                    $deb = !empty($p['date_debut']) ? date_i18n('j M', strtotime($p['date_debut'])) : '';
                    $fin = !empty($p['date_fin']) ? date_i18n('j M', strtotime($p['date_fin'])) : '';
                ?>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f0f2f4;font-size:13px;font-family:'Outfit',sans-serif">
                    <span style="color:#4a5568"><?php echo esc_html($label ?: $deb . ' – ' . $fin); ?></span>
                    <strong style="color:#3d9a9a"><?php echo number_format($prix, 0, ',', ' '); ?> €</strong>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                <a href="<?php echo esc_url(home_url('/reservation/')); ?>?circuit=<?php echo $id; ?>" class="sv-btn-reserver" style="margin-top:16px">Réserver ce circuit →</a>
                <div class="sv-reassurance">
                    <div class="sv-reass"><span class="sv-reass-icon">🛡️</span><span>Assurance en option</span></div>
                    <div class="sv-reass"><span class="sv-reass-icon">✈️</span><span>Recherche de vol à l’étape suivante</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
endwhile;
get_footer();
