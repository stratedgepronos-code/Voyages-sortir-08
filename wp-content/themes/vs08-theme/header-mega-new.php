<?php
// Nouveau header avec mega menu — à insérer dans header.php
$vs08_res = function_exists('vs08_mega_resultats_url') ? vs08_mega_resultats_url() : home_url('/resultats-recherche');
$vs08_golf_pays = function_exists('vs08_mega_golf_countries') ? vs08_mega_golf_countries(5) : [];
$vs08_airports = function_exists('vs08_mega_departure_airports') ? vs08_mega_departure_airports() : [];
$vs08_circuit_items = function_exists('vs08_mega_circuit_destinations') ? vs08_mega_circuit_destinations(8) : [];
$vs08_devis_hub = home_url('/devis-gratuit/');
$vs08_circuits_url = add_query_arg(['type' => 'circuit'], $vs08_res);
?>
<!-- SITE HEADER — MEGA MENU -->
<header class="header" id="header">
<?php if (vs08_opt('vs08_show_annonce', '1') === '1') : ?>
<div class="announce">
    <?php
    $annonce = vs08_opt('vs08_annonce_text', 'Offre Flash : -15% sur l\'Algarve jusqu\'au 15 mars.');
    $link = vs08_opt('vs08_annonce_link');
    $link_text = vs08_opt('vs08_annonce_link_text', 'En profiter →');
    echo wp_kses($annonce, ['strong' => []]);
    if ($link) echo ' <a href="' . esc_url($link) . '">' . esc_html($link_text) . '</a>';
    ?>
</div>
<?php endif; ?>
<nav class="nav-bar">
    <div class="logo-wrap">
        <?php if (has_custom_logo()) :
            the_custom_logo();
        else : ?>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="header-logo-link" aria-label="<?php bloginfo('name'); ?>">
                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/logo.png'); ?>" alt="<?php bloginfo('name'); ?>" class="header-logo-img">
            </a>
        <?php endif; ?>
    </div>
    <ul class="mega-nav">
        <li><a href="<?php echo esc_url(home_url('/')); ?>">Accueil</a></li>
        <li>
            <a href="<?php echo esc_url(add_query_arg(['type' => 'sejour_golf'], $vs08_res)); ?>">Séjours Golf <span class="arrow">▾</span></a>
            <div class="mega-drop">
                <div class="mega-drop-inner">
                    <div class="mega-cols">
                        <div class="mega-col-links">
                            <h4>Par destination</h4>
                            <ul>
                                <?php foreach ($vs08_golf_pays as $gp) : ?>
                                <li><a href="<?php echo esc_url($gp['url']); ?>"><span class="ml-icon"><?php echo esc_html($gp['flag'] ?: '⛳'); ?></span><div><?php echo esc_html($gp['label']); ?><span class="ml-desc"><?php echo esc_html(!empty($gp['desc']) ? $gp['desc'] : 'Séjours golf tout compris'); ?></span></div></a></li>
                                <?php endforeach; ?>
                                <li class="mega-voir-plus"><a href="<?php echo esc_url(add_query_arg(['type' => 'sejour_golf'], $vs08_res)); ?>"><span class="ml-icon">➕</span><div>Voir plus…<span class="ml-desc">Tous les séjours golf</span></div></a></li>
                            </ul>
                        </div>
                        <div class="mega-col-links" style="border-left:1px solid var(--gray-light)">
                            <h4>Par aéroport de départ</h4>
                            <ul>
                                <?php foreach ($vs08_airports as $ap) : ?>
                                <li><a href="<?php echo esc_url($ap['url']); ?>"><span class="ml-icon">✈️</span><div><?php echo esc_html($ap['label']); ?><span class="ml-desc"><?php echo !empty($ap['code']) ? 'Départs ' . esc_html($ap['code']) : 'Tous aéroports'; ?></span></div></a></li>
                                <?php endforeach; ?>
                                <li class="mega-voir-plus"><a href="<?php echo esc_url(add_query_arg(['type' => 'sejour_golf'], $vs08_res)); ?>"><span class="ml-icon">➕</span><div>Voir plus…<span class="ml-desc">Tous les aéroports</span></div></a></li>
                            </ul>
                        </div>
                        <div class="mega-col-visual">
                            <img src="https://images.unsplash.com/photo-1587174486073-ae5e5cff23aa?w=600&q=80" alt="Golf">
                            <div class="mega-col-visual-content">
                                <p>Séjours golf</p>
                                <h3>Catalogue en ligne</h3>
                                <span>Filtrez par pays ou aéroport</span>
                                <a href="<?php echo esc_url(add_query_arg(['type' => 'sejour_golf'], $vs08_res)); ?>">Tous les séjours golf</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </li>
        <li>
            <a href="<?php echo esc_url(add_query_arg(['airport' => 'XCR'], $vs08_res)); ?>">Départ Vatry</a>
        </li>
        <li>
            <a href="<?php echo esc_url($vs08_circuits_url); ?>">Circuits <span class="arrow">▾</span></a>
            <div class="mega-drop">
                <div class="mega-drop-inner">
                    <div class="mega-cols">
                        <div class="mega-col-links">
                            <h4>Circuits par destination</h4>
                            <ul>
                                <?php if (empty($vs08_circuit_items)) : ?>
                                <li><a href="<?php echo esc_url($vs08_circuits_url); ?>"><span class="ml-icon">🗺️</span><div>Tous les circuits<span class="ml-desc">Voir le catalogue complet</span></div></a></li>
                                <?php else : ?>
                                    <?php foreach ($vs08_circuit_items as $ci) : ?>
                                <li><a href="<?php echo esc_url($ci['url']); ?>"><span class="ml-icon"><?php echo esc_html($ci['flag'] ?: '🗺️'); ?></span><div><?php echo esc_html($ci['label']); ?><span class="ml-desc">Circuits tout compris</span></div></a></li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <li class="mega-voir-plus"><a href="<?php echo esc_url($vs08_circuits_url); ?>"><span class="ml-icon">➕</span><div>Voir plus…<span class="ml-desc">Tous les circuits</span></div></a></li>
                            </ul>
                        </div>
                        <div class="mega-col-visual">
                            <img src="https://images.unsplash.com/photo-1523906834658-6e24ef2386f9?w=600&q=80" alt="Circuits">
                            <div class="mega-col-visual-content">
                                <p>Circuits guidés</p>
                                <h3>Explorez le monde</h3>
                                <span>Italie, Grèce, Asie, Amérique…</span>
                                <a href="<?php echo esc_url($vs08_circuits_url); ?>">Voir tous les circuits</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </li>
        <li><a href="<?php echo esc_url(home_url('/resultats-recherche?type=sejour')); ?>">All Inclusive</a></li>
        <li><a href="<?php echo esc_url(home_url('/contact')); ?>">Contact</a></li>
        <li>
            <?php if (is_user_logged_in()) : ?>
                <a href="<?php echo esc_url(home_url('/espace-voyageur/')); ?>" class="nav-account" aria-label="Mon espace voyageur"><span class="nav-account-icon" aria-hidden="true"></span>Mon espace</a>
                <?php if (current_user_can('manage_options')) : ?>
                <a href="<?php echo esc_url(home_url('/espace-admin/')); ?>" class="nav-account" aria-label="Administration" style="margin-left:6px;background:#0f2424;color:#59b7b7;padding:6px 14px;border-radius:100px;font-size:12px;font-weight:700">⚙ Admin</a>
                <?php endif; ?>
            <?php else : ?>
                <a href="<?php echo esc_url(home_url('/connexion/')); ?>" class="nav-account" aria-label="Se connecter ou s'inscrire"><span class="nav-account-icon" aria-hidden="true"></span>Se connecter / s'inscrire</a>
            <?php endif; ?>
        </li>
        <li><a href="<?php echo esc_url($vs08_devis_hub); ?>" class="cta-link">Devis gratuit</a></li>
    </ul>
    <button class="nav-toggle" id="nav-toggle" aria-label="Menu" aria-expanded="false"><span></span><span></span><span></span></button>
</nav>
</header>

<!-- MENU MOBILE DRAWER -->
<div class="vs08-mobile-menu" id="vs08-mobile-menu" aria-hidden="true">
    <div class="vs08-mm-backdrop" id="vs08-mm-backdrop"></div>
    <div class="vs08-mm-panel">
        <div class="vs08-mm-header">
            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/logo.png'); ?>" alt="<?php bloginfo('name'); ?>" style="height:28px">
            <button class="vs08-mm-close" id="vs08-mm-close" aria-label="Fermer">&times;</button>
        </div>
        <nav class="vs08-mm-nav">
            <a href="<?php echo esc_url(home_url('/resultats-recherche?type=sejour_golf')); ?>">⛳ Séjours Golf</a>
            <a href="<?php echo esc_url(home_url('/resultats-recherche?type=circuit')); ?>">🗺️ Circuits</a>
            <a href="<?php echo esc_url(home_url('/qui-sommes-nous')); ?>">Qui sommes-nous</a>
            <a href="<?php echo esc_url(home_url('/avis-clients')); ?>">Avis clients</a>
            <a href="<?php echo esc_url(home_url('/contact')); ?>">Contact</a>
            <div class="vs08-mm-sep"></div>
            <?php if (is_user_logged_in()) : ?>
                <a href="<?php echo esc_url(home_url('/espace-voyageur/')); ?>" class="vs08-mm-accent">✈ Mon espace voyageur</a>
                <?php if (current_user_can('manage_options')) : ?>
                <a href="<?php echo esc_url(home_url('/espace-admin/')); ?>" class="vs08-mm-admin">⚙ Espace admin</a>
                <?php endif; ?>
                <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>" style="color:#9ca3af">Déconnexion</a>
            <?php else : ?>
                <a href="<?php echo esc_url(home_url('/connexion/')); ?>" class="vs08-mm-accent">Se connecter</a>
            <?php endif; ?>
            <a href="<?php echo esc_url($vs08_devis_hub); ?>" class="vs08-mm-cta">Devis gratuit →</a>
        </nav>
        <div class="vs08-mm-footer">
            <div>📞 <a href="tel:0326652863" style="color:#59b7b7">03 26 65 28 63</a></div>
            <div style="font-size:11px;color:#6b7280;margin-top:4px">Lun–Ven 9h–18h30 · Sam 9h–18h</div>
        </div>
    </div>
</div>

<style>
.vs08-mobile-menu{position:fixed;inset:0;z-index:100000;pointer-events:none;visibility:hidden}
.vs08-mobile-menu.open{pointer-events:auto;visibility:visible}
.vs08-mm-backdrop{position:absolute;inset:0;background:rgba(0,0,0,0);transition:background .3s}
.vs08-mobile-menu.open .vs08-mm-backdrop{background:rgba(0,0,0,.5)}
.vs08-mm-panel{position:absolute;top:0;right:-320px;width:min(320px,85vw);height:100%;background:#fff;box-shadow:-8px 0 40px rgba(0,0,0,.15);display:flex;flex-direction:column;transition:right .3s cubic-bezier(.4,0,.2,1);overflow-y:auto}
.vs08-mobile-menu.open .vs08-mm-panel{right:0}
.vs08-mm-header{display:flex;justify-content:space-between;align-items:center;padding:18px 20px;border-bottom:1px solid #f0ece4}
.vs08-mm-close{border:none;background:none;font-size:28px;color:#6b7280;cursor:pointer;padding:0;line-height:1}
.vs08-mm-nav{flex:1;padding:12px 0}
.vs08-mm-nav a{display:block;padding:14px 24px;color:#0f2424;text-decoration:none;font-family:'Outfit',sans-serif;font-size:15px;font-weight:500;transition:background .15s;border-bottom:1px solid #f9f6f0}
.vs08-mm-nav a:hover,.vs08-mm-nav a:active{background:#f9f6f0}
.vs08-mm-sep{height:1px;background:#e5e7eb;margin:8px 24px}
.vs08-mm-accent{color:#59b7b7!important;font-weight:700!important}
.vs08-mm-admin{color:#0f2424!important;font-weight:700!important;background:#edf8f8!important}
.vs08-mm-cta{background:#59b7b7!important;color:#fff!important;margin:12px 20px!important;border-radius:100px!important;text-align:center!important;font-weight:700!important;padding:14px 24px!important}
.vs08-mm-footer{padding:20px 24px;border-top:1px solid #f0ece4;font-family:'Outfit',sans-serif;font-size:13px;color:#374151}
/* Hamburger animation */
.nav-toggle.open span:nth-child(1){transform:rotate(45deg) translate(5px,5px)}
.nav-toggle.open span:nth-child(2){opacity:0}
.nav-toggle.open span:nth-child(3){transform:rotate(-45deg) translate(5px,-5px)}
.nav-toggle span{transition:all .3s}
</style>

<script>
(function(){
    var toggle = document.getElementById('nav-toggle');
    var menu = document.getElementById('vs08-mobile-menu');
    var close = document.getElementById('vs08-mm-close');
    var backdrop = document.getElementById('vs08-mm-backdrop');
    if (!toggle || !menu) return;
    function open(){ menu.classList.add('open'); toggle.classList.add('open'); toggle.setAttribute('aria-expanded','true'); document.body.style.overflow='hidden'; }
    function shut(){ menu.classList.remove('open'); toggle.classList.remove('open'); toggle.setAttribute('aria-expanded','false'); document.body.style.overflow=''; }
    toggle.addEventListener('click', function(){ menu.classList.contains('open') ? shut() : open(); });
    if (close) close.addEventListener('click', shut);
    if (backdrop) backdrop.addEventListener('click', shut);
    // Fermer sur navigation
    menu.querySelectorAll('a').forEach(function(a){ a.addEventListener('click', function(){ setTimeout(shut, 150); }); });
})();
</script>

<!-- OVERLAY RECHERCHE GLOBALE -->
<div class="vs08-search-overlay" id="vs08-search-overlay" aria-hidden="true">
    <div class="vs08-search-backdrop" id="vs08-search-backdrop"></div>
    <div class="vs08-search-panel">
        <div class="vs08-search-header">
            <div class="vs08-search-input-wrap">
                <svg class="vs08-search-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" class="vs08-search-input" id="vs08-search-input" placeholder="Rechercher une destination, un s&eacute;jour, un h&ocirc;tel..." autocomplete="off" autofocus>
                <kbd class="vs08-search-kbd" id="vs08-search-kbd">ESC</kbd>
                <button class="vs08-search-close" id="vs08-search-close" aria-label="Fermer">&times;</button>
            </div>
        </div>
        <div class="vs08-search-body" id="vs08-search-body">
            <div class="vs08-search-status" id="vs08-search-status"></div>
            <div class="vs08-search-results" id="vs08-search-results"></div>
            <div class="vs08-search-empty" id="vs08-search-empty" style="display:none">
                <span>🔍</span>
                <h3>Aucun s&eacute;jour trouv&eacute;</h3>
                <p>Essayez un autre terme ou <a href="<?php echo esc_url($vs08_devis_hub); ?>">demandez un devis gratuit</a>.</p>
            </div>
            <div class="vs08-search-popular" id="vs08-search-popular">
                <p class="vs08-search-popular-title">Destinations populaires</p>
                <div class="vs08-search-results" id="vs08-search-popular-grid"></div>
            </div>
        </div>
    </div>
</div>

<script>
(function(){var h=document.getElementById('header');if(h){window.addEventListener('scroll',function(){h.classList.toggle('scrolled',window.scrollY>80);},{passive:true});}})();
</script>
<script>
(function(){
    var overlay   = document.getElementById('vs08-search-overlay'),
        toggle    = document.getElementById('vs08-search-toggle'),
        backdrop  = document.getElementById('vs08-search-backdrop'),
        closeBtn  = document.getElementById('vs08-search-close'),
        input     = document.getElementById('vs08-search-input'),
        results   = document.getElementById('vs08-search-results'),
        empty     = document.getElementById('vs08-search-empty'),
        popular   = document.getElementById('vs08-search-popular'),
        popGrid   = document.getElementById('vs08-search-popular-grid'),
        status    = document.getElementById('vs08-search-status'),
        ajaxUrl   = (typeof vs08v !== 'undefined' && vs08v.ajax_url) ? vs08v.ajax_url : '<?php echo esc_js(admin_url("admin-ajax.php")); ?>',
        timer     = null,
        ctrl      = null,
        popLoaded = false;

    if (!overlay || !toggle) return;

    function open(){
        overlay.classList.add('active');
        overlay.setAttribute('aria-hidden','false');
        document.body.style.overflow = 'hidden';
        setTimeout(function(){ input.focus(); }, 80);
        if (!popLoaded) loadPopular();
    }
    function close(){
        overlay.classList.remove('active');
        overlay.setAttribute('aria-hidden','true');
        document.body.style.overflow = '';
        input.value = '';
        results.innerHTML = '';
        empty.style.display = 'none';
        popular.style.display = '';
        status.textContent = '';
        if (ctrl) { ctrl.abort(); ctrl = null; }
    }

    toggle.addEventListener('click', open);
    closeBtn.addEventListener('click', close);
    backdrop.addEventListener('click', close);

    document.addEventListener('keydown', function(e){
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            overlay.classList.contains('active') ? close() : open();
        }
        if (e.key === 'Escape' && overlay.classList.contains('active')) {
            close();
        }
    });

    function debounce(fn, ms){
        return function(){
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function(){ fn.apply(null, args); }, ms);
        };
    }

    input.addEventListener('input', debounce(function(){
        var q = input.value.trim();
        if (q.length < 2) {
            results.innerHTML = '';
            empty.style.display = 'none';
            popular.style.display = '';
            status.textContent = '';
            return;
        }
        popular.style.display = 'none';
        doSearch(q);
    }, 300));

    function showSkeletons(){
        var html = '';
        for (var i = 0; i < 3; i++){
            html += '<div class="vs08-search-skeleton"><div class="vs08-search-skeleton-img"></div><div class="vs08-search-skeleton-body"><div class="vs08-search-skeleton-line"></div><div class="vs08-search-skeleton-line"></div><div class="vs08-search-skeleton-line"></div></div></div>';
        }
        results.innerHTML = html;
    }

    function doSearch(q){
        if (ctrl) ctrl.abort();
        ctrl = new AbortController();
        showSkeletons();
        empty.style.display = 'none';
        status.textContent = 'Recherche en cours\u2026';

        var fd = new FormData();
        fd.append('action', 'vs08v_search');
        fd.append('q', q);

        fetch(ajaxUrl, { method:'POST', body:fd, signal:ctrl.signal })
            .then(function(r){ return r.json(); })
            .then(function(data){
                status.textContent = '';
                if (!data.success || !data.data.results.length) {
                    results.innerHTML = '';
                    empty.style.display = '';
                    return;
                }
                empty.style.display = 'none';
                renderCards(results, data.data.results);
                status.textContent = data.data.total + ' s\u00e9jour' + (data.data.total > 1 ? 's' : '') + ' trouv\u00e9' + (data.data.total > 1 ? 's' : '');
            })
            .catch(function(e){
                if (e.name === 'AbortError') return;
                status.textContent = '';
                results.innerHTML = '';
            });
    }

    function loadPopular(){
        popLoaded = true;
        var fd = new FormData();
        fd.append('action', 'vs08v_search');
        fd.append('q', '');
        fetch(ajaxUrl, { method:'POST', body:fd })
            .then(function(r){ return r.json(); })
            .then(function(data){
                if (data.success && data.data.results.length) {
                    renderCards(popGrid, data.data.results);
                } else {
                    popular.style.display = 'none';
                }
            })
            .catch(function(){ popular.style.display = 'none'; });
    }

    var badgeLabels = { 'new':'Nouveau', 'promo':'Promo', 'best':'Best-seller', 'derniere':'Derni\u00e8re place' };
    var niveauLabels = { 'tous':'Tous niveaux', 'debutant':'D\u00e9butant', 'intermediaire':'Interm\u00e9diaire', 'confirme':'Confirm\u00e9' };

    function renderCards(container, items){
        var html = '';
        items.forEach(function(r){
            var badge = '';
            if (r.badge && badgeLabels[r.badge]) {
                badge = '<span class="vs08-search-card-badge vs08-search-card-badge-' + esc(r.badge) + '">' + esc(badgeLabels[r.badge]) + '</span>';
            }
            var tags = [];
            if (r.duree) tags.push('\ud83c\udf19 ' + esc(r.duree) + ' nuits');
            if (r.nb_parcours) tags.push('\u26f3 ' + esc(r.nb_parcours) + ' parcours');
            if (r.niveau && niveauLabels[r.niveau]) tags.push(esc(niveauLabels[r.niveau]));
            var tagsHtml = tags.map(function(t){ return '<span class="vs08-search-card-tag">' + t + '</span>'; }).join('');

            var img = r.thumbnail ? '<img src="' + esc(r.thumbnail) + '" alt="' + esc(r.title) + '" loading="lazy">' : '';
            var prix = r.prix ? parseFloat(r.prix) : 0;
            var prixStr = prix ? new Intl.NumberFormat('fr-FR',{style:'currency',currency:'EUR',minimumFractionDigits:0}).format(prix) : '';
            var prixSuffix = prix ? (r.has_vol ? '/pers. tout compris' : '/pers. hors vols') : 'Prix sur demande';

            html += '<a href="' + esc(r.url) + '" class="vs08-search-card">'
                + '<div class="vs08-search-card-img">' + img + badge + '</div>'
                + '<div class="vs08-search-card-body">'
                    + '<p class="vs08-search-card-dest">' + esc(r.flag) + ' ' + esc(r.destination || r.pays) + '</p>'
                    + '<h3 class="vs08-search-card-title">' + esc(r.title) + '</h3>'
                    + '<div class="vs08-search-card-meta">' + tagsHtml + '</div>'
                    + '<div class="vs08-search-card-footer">'
                        + (prixStr ? '<div><span class="vs08-search-card-price-label">D\u00e8s</span><span class="vs08-search-card-price">' + prixStr + '</span><span class="vs08-search-card-price-per">' + prixSuffix + '</span></div>' : '<div><span class="vs08-search-card-price-per">' + prixSuffix + '</span></div>')
                        + '<span class="vs08-search-card-cta">Voir \u2192</span>'
                    + '</div>'
                + '</div>'
            + '</a>';
        });
        container.innerHTML = html;
    }

    function esc(s){
        if (!s) return '';
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(String(s)));
        return d.innerHTML;
    }
})();
</script>
