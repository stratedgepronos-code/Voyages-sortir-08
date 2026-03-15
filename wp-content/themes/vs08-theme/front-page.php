<?php get_header(); ?>
<style id="vs08-hero-critical">#hero-section{min-height:260px}.search-section{margin-top:-72px!important;padding-bottom:14px!important}</style>
<script>document.addEventListener('DOMContentLoaded',function(){var h=document.getElementById('hero-section');if(h){var H=window.innerHeight;var reserve=185;var target=Math.max(260,H-reserve);h.style.setProperty('height',target+'px','important');h.style.setProperty('max-height',target+'px','important');}});</script>
<section class="hero" id="hero-section" style="height: 55vh;">
    <div class="hero-bg" id="hero-bg" style="background-image:url('<?php echo esc_url(vs08_opt('vs08_hero_img', 'https://images.unsplash.com/photo-1535131749006-b7f58c99034b?w=1920&q=80')); ?>');"></div>
    <div class="hero-overlay"></div>
    <div class="hero-particles"><span></span><span></span><span></span><span></span><span></span></div>
    <div class="hero-content">
        <p class="hero-badge">● Agence Spécialiste Golf & Évasion</p>
        <h1><?php echo wp_kses(vs08_opt('vs08_hero_title', 'Jouez sur les plus beaux <em>parcours</em> du monde'), ['em'=>[],'strong'=>[],'br'=>[]]); ?></h1>
        <p class="hero-tagline"><?php echo esc_html(vs08_opt('vs08_hero_tagline', '— Libre à vous de payer plus cher !')); ?></p>
        <p class="hero-desc"><?php echo esc_html(vs08_opt('vs08_hero_desc', 'Des séjours golf tout compris pensés par des passionnés. Parcours d\'exception, hôtels de charme, vols inclus — vous n\'avez qu\'à jouer.')); ?></p>
        <div class="hero-btns">
            <a href="<?php echo esc_url(home_url('/golf')); ?>" class="btn-primary">Voir nos séjours golf</a>
            <a href="<?php echo esc_url(home_url('/contact')); ?>" class="btn-outline">Demander un devis gratuit</a>
        </div>
    </div>
    <div class="hero-stats">
        <div class="hero-stat"><span class="stat-num">250+</span><span class="stat-lbl">Parcours partenaires</span></div>
        <div class="hero-stat"><span class="stat-num">18</span><span class="stat-lbl">Pays couverts</span></div>
        <div class="hero-stat"><span class="stat-num">4.9★</span><span class="stat-lbl">Note clients</span></div>
    </div>
</section>

<?php $vs08_opts = class_exists('VS08V_Search') ? VS08V_Search::get_aggregated_options() : ['types'=>[],'destinations'=>[],'aeroports'=>[],'durees'=>[],'dates'=>[]]; ?>
<section class="search-section">
    <form class="search-card" action="<?php echo esc_url(home_url('/resultats-recherche')); ?>" method="get">
        <div class="search-field"><label>Type de voyage</label>
            <select name="type">
                <option value="">Tous les types</option>
                <?php foreach ($vs08_opts['types'] as $tv => $tl): ?>
                <option value="<?php echo esc_attr($tv); ?>"><?php echo esc_html($tl); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="search-field"><label>Destination</label>
            <select name="dest">
                <option value="">Toutes les destinations</option>
                <?php foreach ($vs08_opts['destinations'] as $d): ?>
                <option value="<?php echo esc_attr($d['value']); ?>"><?php echo esc_html($d['label']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="search-field"><label>Aéroport de départ</label>
            <select name="airport">
                <option value="">Tous les aéroports</option>
                <?php foreach ($vs08_opts['aeroports'] as $a): ?>
                <option value="<?php echo esc_attr($a['code']); ?>"><?php echo esc_html($a['label']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="search-field"><label>Date de départ</label>
            <div id="fp-date-wrap" style="position:relative">
                <div id="fp-date-trigger" class="search-field-date-trigger" onclick="window.fpCalDate && window.fpCalDate.toggle()">📅 Départ entre… et…</div>
            </div>
            <input type="hidden" id="fp-date-start" name="date_min">
            <input type="hidden" id="fp-date-end" name="date_max">
        </div>
        <div class="search-field"><label>Durée</label>
            <select name="duree">
                <option value="">Toutes les durées</option>
                <?php foreach ($vs08_opts['durees'] as $dn): ?>
                <option value="<?php echo esc_attr($dn); ?>"><?php echo esc_html($dn . ' nuits'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-search">🔍 Rechercher</button>
    </form>
</section>

<!-- Calendrier barre de recherche page d'accueil -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof VS08Calendar === 'undefined') return;
    var wrap = document.getElementById('fp-date-wrap');
    if (!wrap) return;
    var availDates = <?php echo wp_json_encode($vs08_opts['dates']); ?>;
    window.fpCalDate = new VS08Calendar({
        el:       '#fp-date-wrap',
        mode:     'range',
        inline:   false,
        input:    '#fp-date-start',
        inputEnd: '#fp-date-end',
        title:    '📅 Période de départ',
        subtitle: 'Départ au plus tôt → départ au plus tard',
        minDate:  new Date(),
        yearRange: [new Date().getFullYear(), new Date().getFullYear() + 2],
        highlightDates: availDates,
        onConfirm: function(dep, ret) {
            var opts = { day: 'numeric', month: 'short' };
            var txt = '📅 Entre ' + dep.toLocaleDateString('fr-FR', opts);
            if (ret) txt += ' et ' + ret.toLocaleDateString('fr-FR', opts);
            var trigger = document.getElementById('fp-date-trigger');
            if (trigger) {
                trigger.textContent = txt;
                trigger.style.color = '#0f2424';
                trigger.style.borderBottomColor = 'var(--teal)';
            }
        }
    });
});
</script>

<!-- SECTION NOS UNIVERS — Bento Grid -->
<section class="vs08-section vs08-section--univers" id="univers">
  <div class="vs08-container">
    <div class="vs08-section__header">
      <span class="vs08-section__label">✨ Nos univers</span>
      <h2 class="vs08-section__title">Séjours, golf, circuits &amp; <em>aventures</em></h2>
      <p class="vs08-section__subtitle">Chaque voyage est une histoire. Choisissez le premier chapitre de la vôtre parmi nos univers soigneusement conçus.</p>
    </div>
    <div class="vs08-bento">
      <a href="<?php echo esc_url(home_url('/resultats-recherche?type=sejour_golf')); ?>" class="vs08-univers-card vs08-univers-card--golf">
        <div class="vs08-univers-card__img">
          <img src="https://images.unsplash.com/photo-1587174486073-ae5e5cff23aa?w=900&q=80" alt="Séjour golfique" loading="lazy">
        </div>
        <div class="vs08-univers-card__overlay"></div>
        <div class="vs08-univers-card__arrow">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </div>
        <div class="vs08-univers-card__content">
          <span class="vs08-univers-card__badge">⛳ Spécialité maison</span>
          <h3 class="vs08-univers-card__title">Séjours Golfique</h3>
          <p class="vs08-univers-card__desc">Parcours d'exception, hôtels de charme, vols &amp; green fees inclus. Vous n'avez qu'à jouer.</p>
        </div>
        <span class="vs08-univers-card__count">32 séjours</span>
        <div class="vs08-univers-card__line"></div>
      </a>
      <a href="<?php echo esc_url(home_url('/resultats-recherche?type=sejour')); ?>" class="vs08-univers-card vs08-univers-card--sejour">
        <div class="vs08-univers-card__img">
          <img src="https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=700&q=80" alt="Séjour détente" loading="lazy">
        </div>
        <div class="vs08-univers-card__overlay"></div>
        <div class="vs08-univers-card__arrow">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </div>
        <div class="vs08-univers-card__content">
          <span class="vs08-univers-card__badge">☀️ Évasion</span>
          <h3 class="vs08-univers-card__title">Séjours</h3>
          <p class="vs08-univers-card__desc">Détente &amp; découverte dans les plus beaux hôtels-clubs.</p>
        </div>
        <span class="vs08-univers-card__count">18 séjours</span>
        <div class="vs08-univers-card__line"></div>
      </a>
      <a href="<?php echo esc_url(home_url('/resultats-recherche?type=circuit')); ?>" class="vs08-univers-card vs08-univers-card--circuit">
        <div class="vs08-univers-card__img">
          <img src="https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=700&q=80" alt="Circuit découverte" loading="lazy">
        </div>
        <div class="vs08-univers-card__overlay"></div>
        <div class="vs08-univers-card__arrow">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </div>
        <div class="vs08-univers-card__content">
          <span class="vs08-univers-card__badge">🗺️ Découverte</span>
          <h3 class="vs08-univers-card__title">Circuits</h3>
          <p class="vs08-univers-card__desc">Itinéraires conçus étape par étape pour ne rien manquer.</p>
        </div>
        <span class="vs08-univers-card__count">14 circuits</span>
        <div class="vs08-univers-card__line"></div>
      </a>
      <a href="<?php echo esc_url(home_url('/resultats-recherche?type=road_trip')); ?>" class="vs08-univers-card vs08-univers-card--road">
        <div class="vs08-univers-card__img">
          <img src="https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?w=700&q=80" alt="Road trip" loading="lazy">
        </div>
        <div class="vs08-univers-card__overlay"></div>
        <div class="vs08-univers-card__arrow">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </div>
        <div class="vs08-univers-card__content">
          <span class="vs08-univers-card__badge">🚗 Liberté</span>
          <h3 class="vs08-univers-card__title">Road-Trip</h3>
          <p class="vs08-univers-card__desc">Votre voiture, votre rythme, nos meilleures routes.</p>
        </div>
        <span class="vs08-univers-card__count">8 itinéraires</span>
        <div class="vs08-univers-card__line"></div>
      </a>
      <a href="<?php echo esc_url(home_url('/resultats-recherche')); ?>" class="vs08-univers-card vs08-univers-card--parcs">
        <div class="vs08-univers-card__img">
          <img src="https://images.unsplash.com/photo-1568667256549-094345857637?w=700&q=80" alt="Parcs d'attractions" loading="lazy">
        </div>
        <div class="vs08-univers-card__overlay"></div>
        <div class="vs08-univers-card__arrow">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </div>
        <div class="vs08-univers-card__content">
          <span class="vs08-univers-card__badge">🎢 Sensations</span>
          <h3 class="vs08-univers-card__title">Parcs d'attractions</h3>
          <p class="vs08-univers-card__desc">Billets à prix réduit pour Disneyland, Parc Astérix &amp; plus.</p>
        </div>
        <span class="vs08-univers-card__count">5 parcs</span>
        <div class="vs08-univers-card__line"></div>
      </a>
    </div>
  </div>
</section>

<div class="how-sejours-bridge">
    <div class="how-sejours-bridge-inner">
        <p>Nos séjours coups de cœur</p>
    </div>
</div>

<section class="sejours-section">
    <div class="container">
        <div class="section-header">
            <div><p class="section-label">⛳ Sélection Golf</p><h2 class="section-title">Nos séjours <em>coups de cœur</em></h2></div>
            <a href="<?php echo esc_url(home_url('/golf')); ?>" class="section-link">Tous les séjours golf →</a>
        </div>
        <div class="cards-grid">
            <?php echo class_exists('VS08V_Homepage_Editor') ? VS08V_Homepage_Editor::render_home_cards() : ''; ?>
            <?php /*
            <div class="scard scard-featured anim">
                <div class="scard-img">
                    <div class="scard-badges"><span class="badge badge-best">Best-seller</span></div>
                    <div class="scard-hotel-badge"><span>Kenzi Club Agdal Resort</span><span class="stars-sm">★★★★★</span></div>
                    <img src="https://images.unsplash.com/photo-1587174486073-ae5e5cff23aa?w=1000&q=80" alt="Golf Marrakech Kenzi Agdal">
                </div>
                <div class="scard-body">
                    <p class="scard-country">🇲🇦 Maroc — Marrakech</p>
                    <h3>Golf & Palmeraie — Kenzi Club Agdal</h3>
                    <p class="scard-desc">Niché au cœur des palmeraies de Marrakech, le Kenzi Club Agdal Resort 5★ offre 4 parcours mythiques dont le Royal Golf, piscines, spa, et pension complète. Un classique indétrônable.</p>
                    <div class="scard-highlights">
                        <span class="scard-chip">✈️ Vol inclus</span><span class="scard-chip">🌙 7 nuits</span><span class="scard-chip">⛳ 4 parcours</span><span class="scard-chip chip-gold">🍽️ Pension complète</span><span class="scard-chip">🚌 Transferts inclus</span>
                    </div>
                    <div class="scard-golfs">
                        <div class="scard-golf-chip"><span class="gchip-icon">⛳</span><div><span class="gchip-name">Royal Golf Marrakech</span><br><span class="gchip-holes">18 trous · Tous niveaux</span></div></div>
                        <div class="scard-golf-chip"><span class="gchip-icon">⛳</span><div><span class="gchip-name">Al Maaden Golf</span><br><span class="gchip-holes">18 trous · Intermédiaire</span></div></div>
                        <div class="scard-golf-chip"><span class="gchip-icon">⛳</span><div><span class="gchip-name">Samanah Golf</span><br><span class="gchip-holes">18 trous · Confirmé</span></div></div>
                        <div class="scard-golf-chip"><span class="gchip-icon">⛳</span><div><span class="gchip-name">Amelkis Golf</span><br><span class="gchip-holes">27 trous · Tous niveaux</span></div></div>
                    </div>
                    <div class="scard-divider"></div>
                    <div class="scard-footer">
                        <div class="scard-price"><span class="price-label">Dès</span><span class="price-amount">1 190€</span><span class="price-per">/personne · tout compris</span></div>
                        <a href="<?php echo esc_url(home_url('/golf')); ?>" class="scard-btn">Voir ce séjour →</a>
                    </div>
                </div>
            </div>
            <div class="scard anim">
                <div class="scard-img">
                    <div class="scard-badges"><span class="badge badge-new">Nouveauté</span></div>
                    <div class="scard-hotel-badge"><span>Pine Cliffs Resort</span><span class="stars-sm">★★★★★</span></div>
                    <img src="https://images.unsplash.com/photo-1593111774240-d529f12cf4bb?w=800&q=80" alt="Golf Portugal">
                </div>
                <div class="scard-body">
                    <p class="scard-country">🇵🇹 Portugal — Algarve</p>
                    <h3>Golf & Soleil Costa Vicentina</h3>
                    <p class="scard-desc">5 parcours d'exception, hôtel 5★ vue Atlantique, green fees & transferts inclus.</p>
                    <div class="scard-highlights">
                        <span class="scard-chip">✈️ Vol inclus</span><span class="scard-chip">🌙 7 nuits</span><span class="scard-chip">⛳ 5 parcours</span><span class="scard-chip chip-gold">☕ Petit-déjeuner</span>
                    </div>
                    <div class="scard-golfs">
                        <div class="scard-golf-chip"><span class="gchip-icon">⛳</span><div><span class="gchip-name">Vilamoura Old Course</span><br><span class="gchip-holes">18 trous · Confirmé</span></div></div>
                        <div class="scard-golf-chip"><span class="gchip-icon">⛳</span><div><span class="gchip-name">Quinta do Lago</span><br><span class="gchip-holes">18 trous · Tous niveaux</span></div></div>
                    </div>
                    <div class="scard-divider"></div>
                    <div class="scard-footer">
                        <div class="scard-price"><span class="price-label">Dès</span><span class="price-amount">1 290€</span><span class="price-per">/personne · tout compris</span></div>
                        <a href="<?php echo esc_url(home_url('/golf')); ?>" class="scard-btn">Voir →</a>
                    </div>
                </div>
            </div>
            <div class="scard anim">
                <div class="scard-img">
                    <div class="scard-badges"><span class="badge badge-promo">-15%</span></div>
                    <div class="scard-hotel-badge"><span>Marbella Club Hotel</span><span class="stars-sm">★★★★★</span></div>
                    <img src="https://images.unsplash.com/photo-1535131749006-b7f58c99034b?w=800&q=80" alt="Golf Espagne">
                </div>
                <div class="scard-body">
                    <p class="scard-country">🇪🇸 Espagne — Marbella</p>
                    <h3>Costa del Sol Luxury Golf</h3>
                    <p class="scard-desc">Parcours de championnat face à la mer, resort 5★ avec spa et piscine à débordement.</p>
                    <div class="scard-highlights">
                        <span class="scard-chip">✈️ Vol inclus</span><span class="scard-chip">🌙 10 nuits</span><span class="scard-chip">⛳ 6 parcours</span><span class="scard-chip chip-gold">🏌️ Spa inclus</span>
                    </div>
                    <div class="scard-golfs">
                        <div class="scard-golf-chip"><span class="gchip-icon">⛳</span><div><span class="gchip-name">Valderrama</span><br><span class="gchip-holes">18 trous · Confirmé</span></div></div>
                        <div class="scard-golf-chip"><span class="gchip-icon">⛳</span><div><span class="gchip-name">La Quinta</span><br><span class="gchip-holes">27 trous · Interm.</span></div></div>
                    </div>
                    <div class="scard-divider"></div>
                    <div class="scard-footer">
                        <div class="scard-price"><span class="price-label">Dès</span><span class="price-amount">1 590€</span><span class="price-per">/personne · tout compris</span></div>
                        <a href="<?php echo esc_url(home_url('/golf')); ?>" class="scard-btn">Voir →</a>
                    </div>
                </div>
            </div>
            <div class="scard anim">
                <div class="scard-img">
                    <div class="scard-badges"><span class="badge badge-new">Nouveauté</span></div>
                    <div class="scard-hotel-badge"><span>The Europe Hotel</span><span class="stars-sm">★★★★★</span></div>
                    <img src="https://images.unsplash.com/photo-1590523741831-ab7e8b8f9c7f?w=800&q=80" alt="Golf Irlande">
                </div>
                <div class="scard-body">
                    <p class="scard-country">🇮🇪 Irlande — Kerry</p>
                    <h3>Wild Atlantic Golf — Links</h3>
                    <p class="scard-desc">Links authentiques battus par le vent de l'Atlantique. Paysages à couper le souffle, pub culture et Guinness.</p>
                    <div class="scard-highlights">
                        <span class="scard-chip">✈️ Vol inclus</span><span class="scard-chip">🌙 7 nuits</span><span class="scard-chip">⛳ 4 links</span><span class="scard-chip chip-gold">☕ Petit-déjeuner</span>
                    </div>
                    <div class="scard-golfs">
                        <div class="scard-golf-chip"><span class="gchip-icon">⛳</span><div><span class="gchip-name">Ballybunion Old</span><br><span class="gchip-holes">18 trous · Confirmé</span></div></div>
                        <div class="scard-golf-chip"><span class="gchip-icon">⛳</span><div><span class="gchip-name">Waterville Links</span><br><span class="gchip-holes">18 trous · Confirmé</span></div></div>
                    </div>
                    <div class="scard-divider"></div>
                    <div class="scard-footer">
                        <div class="scard-price"><span class="price-label">Dès</span><span class="price-amount">1 190€</span><span class="price-per">/personne · tout compris</span></div>
                        <a href="<?php echo esc_url(home_url('/golf')); ?>" class="scard-btn">Voir →</a>
                    </div>
                </div>
            </div>
            */ ?>
        </div>
    </div>
</section>

<div class="sejours-departs-bridge">
    <div class="sejours-departs-bridge-inner">
        <div class="sejours-departs-bridge-icon">⛳</div>
        <p>Entre nos <em>coups de cœur</em> et les prochains départs, trouvez le séjour qui vous ressemble.</p>
    </div>
</div>

<section class="departs-section">
    <div class="container">
        <div class="section-header">
            <div><p class="section-label">🔥 Prochains départs</p><h2 class="section-title">Partez <em>bientôt</em></h2></div>
            <a href="<?php echo esc_url(home_url('/golf')); ?>" class="section-link">Voir tous les départs →</a>
        </div>
        <div class="departs-grid">
            <?php echo class_exists('VS08V_Homepage_Editor') ? VS08V_Homepage_Editor::render_departs_cards() : ''; ?>
        </div>
    </div>
</section>

<div class="wave-divider"><svg viewBox="0 0 1440 60" preserveAspectRatio="none"><path d="M0,60 C360,0 720,40 1080,10 C1260,0 1380,20 1440,15 L1440,60 Z" fill="#0f2424"/></svg></div>
<section class="why-section" id="why-section">
    <div class="why-glow-wrap"><div class="why-glow" id="why-glow"></div></div>
    <div class="container">
        <p class="section-label label-light">✦ Notre différence</p>
        <h2 class="section-title title-white">Pourquoi nous <em>faire confiance ?</em></h2>
        <div class="why-grid">
            <div class="why-item anim"><div class="why-icon">⛳</div><h3>Experts Golf</h3><p>Passionnés de golf depuis 20 ans, nous jouons sur les parcours que nous vous proposons.</p></div>
            <div class="why-item anim"><div class="why-icon">🏷️</div><h3>Prix transparents</h3><p>Tout est inclus : vols, hôtel, green fees, transferts. Le prix affiché est le prix payé.</p></div>
            <div class="why-item anim"><div class="why-icon">📞</div><h3>Conseiller unique</h3><p>Un conseiller unique dédié avant, pendant et après votre voyage. Pas de chatbot.</p></div>
            <div class="why-item anim"><div class="why-icon">🔒</div><h3>Paiement sécurisé</h3><p>3D Secure, acompte ou règlement total (voir conditions d'annulation sur chaque produit).</p></div>
        </div>
    </div>
</section>
<div class="wave-divider"><svg viewBox="0 0 1440 60" preserveAspectRatio="none"><path d="M0,0 C360,50 720,10 1080,40 C1260,50 1380,20 1440,30 L1440,0 Z" fill="#0f2424"/></svg></div>

<section class="dest-section">
    <div class="container">
        <div class="section-header">
            <div><p class="section-label">🌍 Nos destinations</p><h2 class="section-title">Partir jouer <em>partout dans le monde</em></h2></div>
            <a href="<?php echo esc_url(home_url('/resultats-recherche')); ?>" class="section-link">Toutes les destinations →</a>
        </div>
        <div class="dest-grid">
            <?php
            $vs08_dest_default_imgs = [
                'Portugal' => 'https://images.unsplash.com/photo-1555881400-74d7acaacd8b?w=400&q=80',
                'Espagne'  => 'https://images.unsplash.com/photo-1539020140153-e479b8c22e70?w=400&q=80',
                'Maroc'    => 'https://images.unsplash.com/photo-1553603227-2358aabe821e?w=400&q=80',
                'Thaïlande'=> 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=400&q=80',
                'Irlande'  => 'https://images.unsplash.com/photo-1590523741831-ab7e8b8f9c7f?w=400&q=80',
                'France'   => 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=400&q=80',
                'Italie'   => 'https://images.unsplash.com/photo-1523906834658-6e24ef2386f9?w=400&q=80',
                'Grèce'    => 'https://images.unsplash.com/photo-1533105077500-4b4c2d0c1e7e?w=400&q=80',
            ];
            $vs08_dests = class_exists('VS08V_Search') ? VS08V_Search::get_aggregated_options() : ['destinations' => []];
            foreach ($vs08_dests['destinations'] as $d):
                $dest_value = $d['value'] ?? '';
                $dest_label = $d['label'] ?? $dest_value;
                $dest_pays = $d['pays'] ?? '';
                $dest_img = !empty($d['image']) ? $d['image'] : ($vs08_dest_default_imgs[$dest_pays] ?? 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=400&q=80');
                $dest_count = isset($d['count']) ? (int) $d['count'] : 0;
                $dest_url = home_url('/resultats-recherche?dest=' . rawurlencode($dest_value));
                $dest_name_display = trim(($d['flag'] ?? '') . ' ' . $dest_label);
            ?>
            <a href="<?php echo esc_url($dest_url); ?>" class="dest-card anim">
                <img src="<?php echo esc_url($dest_img); ?>" alt="<?php echo esc_attr($dest_label); ?>">
                <div class="dest-overlay"></div>
                <div class="dest-info">
                    <p class="dest-country"><?php echo esc_html($dest_pays ?: 'Destination'); ?></p>
                    <p class="dest-name"><?php echo esc_html($dest_name_display); ?></p>
                    <p class="dest-count"><?php echo $dest_count; ?> séjour<?php echo $dest_count > 1 ? 's' : ''; ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="trust-section">
    <div class="container">
        <div class="trust-row">
            <div class="trust-item">
                <div class="trust-logo trust-logo-apst" aria-hidden="true"><svg viewBox="0 0 120 44" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="44" rx="8" fill="#1a5276"/><text x="60" y="28" text-anchor="middle" fill="#fff" font-family="Outfit,Arial,sans-serif" font-weight="800" font-size="16">APST</text><text x="60" y="38" text-anchor="middle" fill="rgba(255,255,255,.85)" font-family="Outfit,Arial,sans-serif" font-weight="600" font-size="8">Garantie voyageurs</text></svg></div>
                <div class="trust-text"><strong>Garantie APST</strong><span>Protection financière voyageurs</span></div>
            </div>
            <div class="trust-sep"></div>
            <div class="trust-item">
                <div class="trust-logo trust-logo-atout" aria-hidden="true"><svg viewBox="0 0 100 44" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="100" height="44" rx="8" fill="#002654"/><text x="50" y="26" text-anchor="middle" fill="#fff" font-family="Outfit,Arial,sans-serif" font-weight="800" font-size="11">Atout France</text><text x="50" y="38" text-anchor="middle" fill="rgba(255,255,255,.9)" font-family="Outfit,Arial,sans-serif" font-weight="600" font-size="8">IM051100014</text></svg></div>
                <div class="trust-text"><strong>Atout France</strong><span>Immatriculation tourisme</span></div>
            </div>
            <div class="trust-sep"></div>
            <div class="trust-item">
                <div class="trust-logo trust-logo-3d" aria-hidden="true"><svg viewBox="0 0 100 44" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="100" height="44" rx="8" fill="#1a1a2e"/><text x="50" y="28" text-anchor="middle" fill="#00d4aa" font-family="Outfit,Arial,sans-serif" font-weight="800" font-size="14">3D Secure</text><text x="50" y="40" text-anchor="middle" fill="rgba(0,212,170,.8)" font-family="Outfit,Arial,sans-serif" font-size="7">Paiement sécurisé</text></svg></div>
                <div class="trust-text"><strong>Paiement 3D Secure</strong><span>Transactions sécurisées SSL</span></div>
            </div>
            <div class="trust-sep"></div>
            <div class="trust-item">
                <div class="trust-logo trust-logo-hiscox" aria-hidden="true"><svg viewBox="0 0 100 44" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="100" height="44" rx="8" fill="#c41230"/><text x="50" y="28" text-anchor="middle" fill="#fff" font-family="Outfit,Arial,sans-serif" font-weight="800" font-size="14">Hiscox</text><text x="50" y="40" text-anchor="middle" fill="rgba(255,255,255,.9)" font-family="Outfit,Arial,sans-serif" font-size="7">Assurance RC Pro</text></svg></div>
                <div class="trust-text"><strong>Assurance Hiscox</strong><span>Responsabilité civile professionnelle</span></div>
            </div>
        </div>
    </div>
</section>

<section class="testi-section">
    <div class="container">
        <p class="section-label">⭐ Avis clients</p>
        <h2 class="section-title">Ils sont partis, <em>ils en parlent</em></h2>
        <p class="section-desc" style="text-align:center;color:var(--gray);font-size:14px;margin-bottom:24px">Avis Google 5 étoiles avec texte</p>
        <div class="testi-grid" id="testi-grid"></div>
        <div class="testi-dots" id="testi-dots"></div>
    </div>
</section>
<?php
            $vs08_google_reviews = get_option('vs08v_google_reviews', []);
            if (!is_array($vs08_google_reviews) || empty($vs08_google_reviews)) {
                $vs08_google_reviews = [
                    ['initials' => 'MR', 'name' => 'Michel R.', 'trip' => 'Portugal Algarve — Oct. 2024', 'text' => 'Séjour parfait au Portugal. Parcours magnifiques, hôtel de rêve. L\'équipe a tout pensé, on n\'avait qu\'à jouer. On repart l\'an prochain !'],
                    ['initials' => 'SL', 'name' => 'Sophie L.', 'trip' => 'Maroc Agadir — Fév. 2025', 'text' => 'Premier voyage golf en agence et je ne m\'en passerai plus. Prix vraiment transparent. Le conseiller était disponible même depuis le Maroc.'],
                    ['initials' => 'JP', 'name' => 'Jean-Pierre V.', 'trip' => 'Espagne Marbella — Avr. 2025', 'text' => 'On était 4 amis golfeurs, tout était parfaitement coordonné. Tee-times, transferts, dîner de groupe... Un vrai service premium à prix honnête.'],
                    ['initials' => 'CG', 'name' => 'Catherine G.', 'trip' => 'Thaïlande Hua Hin — Jan. 2026', 'text' => 'Nous sommes partis en couple pour la première fois avec une agence spécialiste golf. Organisation au top, parcours sublimes. On recommande à 200 %.'],
                    ['initials' => 'AD', 'name' => 'Alain D.', 'trip' => 'Irlande Kerry — Mai 2025', 'text' => 'Groupe de 8 golfeurs : tout a été coordonné à la perfection. Tee-times, dîners, navettes. Un service haut de gamme et des souvenirs incroyables.'],
                    ['initials' => 'PB', 'name' => 'Philippe B.', 'trip' => 'Turquie Belek — Nov. 2025', 'text' => '3ème séjour avec Sortir Monde : Algarve, Maroc et maintenant la Turquie. On ne change pas une équipe qui gagne, toujours au-delà de nos attentes.'],
                ];
            }
            ?>
<script>
window.VS08_REVIEWS = <?php echo wp_json_encode(array_values($vs08_google_reviews)); ?>;
</script>

<section class="newsletter-cta-section">
    <div class="newsletter-cta-band">
        <div class="newsletter-side">
            <p class="newsletter-badge">📧 Newsletter exclusive</p>
            <h2>Offres privées & <em>bons plans golf</em></h2>
            <p>Ventes flash, nouveaux parcours, conseils d'expert : recevez le meilleur du golf voyage directement dans votre boîte mail.</p>
            <form class="newsletter-form vs08-newsletter-form" id="vs08-newsletter-form">
                <input type="email" name="email" placeholder="Votre adresse email..." required>
                <button type="submit">S'inscrire →</button>
            </form>
            <p class="newsletter-msg" id="vs08-newsletter-msg" style="display:none;margin-top:10px;font-size:14px;"></p>
            <div class="newsletter-perks">
                <div class="newsletter-perk"><span>✓</span> 1 email / semaine max</div>
                <div class="newsletter-perk"><span>✓</span> Offres avant tout le monde</div>
                <div class="newsletter-perk"><span>✓</span> Désinscription en 1 clic</div>
            </div>
            <p class="newsletter-legal">En vous inscrivant, vous acceptez notre politique de confidentialité.</p>
        </div>
        <div class="newsletter-cta-sep" aria-hidden="true"></div>
        <div class="cta-side">
            <div class="cta-devis-wrap">
                <div class="cta-devis-box">
                    <p class="cta-devis-eyebrow">Devis gratuit</p>
                    <h2>Votre séjour golf sur mesure</h2>
                    <p class="cta-devis-desc">Dites-nous destination, budget et niveau. Un conseiller vous envoie une proposition sous 24h, sans engagement.</p>
                    <div class="cta-devis-trust">
                        <span>Réponse sous 24h</span><span>Sans engagement</span><span>Devis personnalisé</span>
                    </div>
                    <a href="<?php echo esc_url(home_url('/devis-golf')); ?>" class="btn-devis">Demander mon devis <span class="btn-arrow">→</span></a>
                    <div class="cta-phone-wrap">
                        <span>Ou par téléphone</span>
                        <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', vs08_opt('vs08_tel', '0326652863'))); ?>"><span class="phone-icon">📞</span> <?php echo esc_html(vs08_opt('vs08_tel', '03 26 65 28 63')); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="whatsapp-float">
    <span style="line-height:1">💬</span>
    <div class="whatsapp-tooltip">Besoin d'aide ? Écrivez-nous !</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var obs = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) { if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); } });
    }, { threshold: 0.15 });
    document.querySelectorAll('.anim').forEach(function(el) { obs.observe(el); });

    var header = document.getElementById('header');
    var heroBg = document.getElementById('hero-bg');

    window.addEventListener('scroll', function() {
        var scrollY = window.scrollY;
        if (header) header.classList.toggle('scrolled', scrollY > 80);
        if (heroBg && scrollY < window.innerHeight * 1.5) {
            heroBg.style.transform = 'translateY(' + (scrollY * 0.35) + 'px) scale(1.05)';
        }
    }, { passive: true });

    var whySec = document.getElementById('why-section');
    var whyGlow = document.getElementById('why-glow');
    if (whySec && whyGlow) {
        whySec.addEventListener('mousemove', function(e) {
            var rect = whySec.getBoundingClientRect();
            whyGlow.style.left = (e.clientX - rect.left) + 'px';
            whyGlow.style.top = (e.clientY - rect.top) + 'px';
            whyGlow.style.opacity = '1';
        });
        whySec.addEventListener('mouseleave', function() { whyGlow.style.opacity = '0'; });
    }

    var testiGrid = document.getElementById('testi-grid');
    var testiDotsWrap = document.getElementById('testi-dots');
    var allTestis = window.VS08_REVIEWS || [];
    if (testiGrid && testiDotsWrap && allTestis.length > 0) {
        var testiPage = 0;
        var testiPages = Math.ceil(allTestis.length / 3);
        var testiAutoTimer;
        for (var ti = 0; ti < testiPages; ti++) {
            var tdot = document.createElement('button');
            tdot.className = 'testi-dot' + (ti === 0 ? ' active' : '');
            tdot.setAttribute('data-idx', ti);
            testiDotsWrap.appendChild(tdot);
        }
        function buildCard(t) {
            return '<div class="testi-card anim visible"><div class="stars">★★★★★</div><div class="quote">"</div><p>' + t.text + '</p><div class="testi-author"><div class="avatar">' + t.initials + '</div><div><p class="author-name">' + t.name + '</p><p class="author-trip">' + t.trip + '</p></div></div></div>';
        }
        function showTestiPage(idx) {
            testiPage = idx;
            var cards = testiGrid.querySelectorAll('.testi-card');
            cards.forEach(function(c) { c.classList.add('fade-out'); });
            setTimeout(function() {
                var start = testiPage * 3;
                var slice = allTestis.slice(start, start + 3);
                testiGrid.innerHTML = slice.map(buildCard).join('');
                setTimeout(function() {
                    testiGrid.querySelectorAll('.testi-card').forEach(function(c) { c.classList.add('fade-in'); });
                }, 50);
            }, 400);
            testiDotsWrap.querySelectorAll('.testi-dot').forEach(function(d, i) { d.classList.toggle('active', i === idx); });
        }
        testiDotsWrap.addEventListener('click', function(e) {
            if (e.target.classList.contains('testi-dot')) {
                showTestiPage(parseInt(e.target.getAttribute('data-idx')));
                clearInterval(testiAutoTimer); testiAutoRun();
            }
        });
        function testiAutoRun() {
            testiAutoTimer = setInterval(function() { showTestiPage((testiPage + 1) % testiPages); }, 6000);
        }
        testiAutoRun();
    }

    var newsletterForm = document.getElementById('vs08-newsletter-form');
    var newsletterMsg = document.getElementById('vs08-newsletter-msg');
    if (newsletterForm && newsletterMsg) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var email = newsletterForm.querySelector('input[name="email"]');
            if (!email || !email.value) return;
            var btn = newsletterForm.querySelector('button[type="submit"]');
            if (btn) btn.disabled = true;
            var fd = new FormData();
            fd.append('action', 'vs08v_newsletter_subscribe');
            fd.append('email', email.value);
            fetch('<?php echo esc_url(admin_url("admin-ajax.php")); ?>', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    newsletterMsg.style.display = 'block';
                    newsletterMsg.textContent = (res && res.data && res.data.message) ? res.data.message : (res && res.success ? 'Merci pour votre inscription.' : 'Une erreur est survenue.');
                    newsletterMsg.style.color = (res && res.success) ? '#0f766e' : '#b91c1c';
                    if (res && res.success) email.value = '';
                })
                .finally(function() { if (btn) btn.disabled = false; });
        });
    }
});
</script>

<?php get_footer(); ?>
