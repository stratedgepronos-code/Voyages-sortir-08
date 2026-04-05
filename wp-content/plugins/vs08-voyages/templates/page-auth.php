<?php
/**
 * Page Connexion / Inscription — Design premium Voyages Sortir 08.
 * Standalone (pas de header/footer WooCommerce).
 */
defined('ABSPATH') || exit;

$redirect_to = !empty($_GET['redirect_to']) ? esc_url($_GET['redirect_to']) : home_url('/espace-voyageur/');
$logo_url = '';
$site_name = get_bloginfo('name');
if (has_custom_logo()) {
    $logo_id = get_theme_mod('custom_logo');
    $logo_url = wp_get_attachment_image_url($logo_id, 'medium_large');
}
if (!$logo_url) {
    $logo_url = get_template_directory_uri() . '/assets/img/logo-voyages-sortir-08.png';
}
$logo_url = $logo_url ?: '';
$lp_url = home_url('/mot-de-passe-perdu/');
if (function_exists('wc_lostpassword_url')) {
    $lp_url = wc_lostpassword_url();
} elseif (function_exists('wp_lostpassword_url')) {
    $lp_url = wp_lostpassword_url();
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion — <?php bloginfo('name'); ?></title>
<?php wp_head(); ?>
</head>
<body class="vs08-auth-body">

<div class="auth-page">

    <!-- ============ PANNEAU GAUCHE : Visuel immersif ============ -->
    <div class="auth-visual">
        <div class="auth-visual-slides">
            <div class="auth-visual-slide auth-slide-active" style="background-image:url('https://images.unsplash.com/photo-1535131749006-b7f58c99034b?w=1400&q=80')"></div>
            <div class="auth-visual-slide" style="background-image:url('https://images.unsplash.com/photo-1514282401047-d79a71a590e8?w=1400&q=80')"></div>
            <div class="auth-visual-slide" style="background-image:url('https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=1400&q=80')"></div>
            <div class="auth-visual-slide" style="background-image:url('https://images.unsplash.com/photo-1480796927426-f609979314bd?w=1400&q=80')"></div>
            <div class="auth-visual-slide" style="background-image:url('https://images.unsplash.com/photo-1540541338287-41700207dee6?w=1400&q=80')"></div>
        </div>
        <div class="auth-visual-overlay"></div>
        <div class="auth-visual-content">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="auth-logo-link">
                <?php if ($logo_url) : ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>" class="auth-logo" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-block';">
                    <span class="auth-logo-text" style="display:none;"><?php echo esc_html($site_name); ?></span>
                <?php else : ?>
                    <span class="auth-logo-text"><?php echo esc_html($site_name); ?></span>
                <?php endif; ?>
            </a>
            <div class="auth-slogans">
                <p class="auth-slogan auth-slogan-active">🏌️ Séjours golf tout compris — Algarve, Marrakech, Turquie…</p>
                <p class="auth-slogan">🏖️ All inclusive au soleil — Farniente, plage & détente</p>
                <p class="auth-slogan">🗺️ Circuits sur mesure — Croatie, Malaisie, Scandinavie…</p>
                <p class="auth-slogan">🏙️ City breaks & escapades — Lisbonne, Prague, Dubaï…</p>
                <p class="auth-slogan">🎢 Parcs d'attractions — Disneyland, Parc Astérix, Europa-Park…</p>
            </div>
            <div class="auth-visual-pills">
                <span class="auth-pill">⛳ Golf</span>
                <span class="auth-pill">🌴 Séjours</span>
                <span class="auth-pill">🗺️ Circuits</span>
                <span class="auth-pill">🏙️ City Break</span>
                <span class="auth-pill">🎢 Parcs</span>
            </div>
            <div class="auth-social-proof">
                <span class="auth-sp-dot"></span>
                Départ de votre région · <strong>Tout inclus</strong> · Prix vols en temps réel
            </div>
        </div>
    </div>

    <!-- ============ PANNEAU DROIT : Formulaire ============ -->
    <div class="auth-form-panel">
        <div class="auth-form-inner">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="auth-back-home">&larr; Retour au site</a>

            <!-- Onglets -->
            <div class="auth-tabs">
                <button type="button" class="auth-tab auth-tab-active" data-tab="login">Se connecter</button>
                <button type="button" class="auth-tab" data-tab="register">Créer un compte</button>
                <div class="auth-tab-slider"></div>
            </div>

            <!-- Messages -->
            <div class="auth-msg" id="auth-msg" role="alert"></div>

            <!-- FORMULAIRE CONNEXION -->
            <form class="auth-form" id="auth-form-login" novalidate>
                <input type="hidden" name="action" value="vs08v_auth_login">
                <?php wp_nonce_field('vs08v_auth', 'nonce'); ?>
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">

                <div class="auth-field">
                    <input type="text" id="login-email" name="login" autocomplete="username" required>
                    <label for="login-email">E-mail ou identifiant</label>
                    <span class="auth-field-line"></span>
                </div>

                <div class="auth-field auth-field-password">
                    <input type="password" id="login-password" name="password" autocomplete="current-password" required>
                    <label for="login-password">Mot de passe</label>
                    <span class="auth-field-line"></span>
                    <button type="button" class="auth-toggle-pw" aria-label="Afficher le mot de passe">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>

                <div class="auth-row">
                    <label class="auth-remember">
                        <input type="checkbox" name="remember" value="1"> Se souvenir de moi
                    </label>
                    <a href="<?php echo esc_url($lp_url); ?>" class="auth-forgot">Mot de passe oublié ?</a>
                </div>

                <button type="submit" class="auth-submit" id="auth-submit-login">
                    <span class="auth-submit-text">Se connecter</span>
                    <span class="auth-submit-loader"></span>
                </button>
            </form>

            <!-- FORMULAIRE INSCRIPTION -->
            <form class="auth-form auth-form-hidden" id="auth-form-register" novalidate>
                <input type="hidden" name="action" value="vs08v_auth_register">
                <?php wp_nonce_field('vs08v_auth', 'nonce'); ?>
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">

                <div class="auth-field-row">
                    <div class="auth-field">
                        <input type="text" id="reg-prenom" name="prenom" autocomplete="given-name" required>
                        <label for="reg-prenom">Prénom</label>
                        <span class="auth-field-line"></span>
                    </div>
                    <div class="auth-field">
                        <input type="text" id="reg-nom" name="nom" autocomplete="family-name" required>
                        <label for="reg-nom">Nom</label>
                        <span class="auth-field-line"></span>
                    </div>
                </div>

                <div class="auth-field">
                    <input type="email" id="reg-email" name="email" autocomplete="email" required>
                    <label for="reg-email">Adresse e-mail</label>
                    <span class="auth-field-line"></span>
                </div>

                <div class="auth-field auth-field-password">
                    <input type="password" id="reg-password" name="password" autocomplete="new-password" minlength="8" required>
                    <label for="reg-password">Mot de passe</label>
                    <span class="auth-field-line"></span>
                    <button type="button" class="auth-toggle-pw" aria-label="Afficher le mot de passe">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>

                <div class="auth-pw-strength" id="auth-pw-strength">
                    <div class="auth-pw-bar"><div class="auth-pw-bar-fill"></div></div>
                    <span class="auth-pw-label"></span>
                </div>

                <button type="submit" class="auth-submit" id="auth-submit-register">
                    <span class="auth-submit-text">Créer mon compte</span>
                    <span class="auth-submit-loader"></span>
                </button>

                <p class="auth-legal">En créant un compte, vous acceptez nos <a href="<?php echo esc_url(home_url('/conditions-generales-de-vente/')); ?>">conditions générales de vente</a>.</p>
            </form>

            <div class="auth-separator"><span>ou</span></div>

            <a href="<?php echo esc_url(home_url('/')); ?>" class="auth-guest-link">Continuer sans compte</a>
        </div>
    </div>
</div>

<script>
(function(){
    var ajaxUrl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';

    /* ── Onglets ── */
    var tabs = document.querySelectorAll('.auth-tab');
    var formLogin = document.getElementById('auth-form-login');
    var formRegister = document.getElementById('auth-form-register');
    var slider = document.querySelector('.auth-tab-slider');
    var msg = document.getElementById('auth-msg');

    function switchTab(tab) {
        tabs.forEach(function(t){ t.classList.remove('auth-tab-active'); });
        tab.classList.add('auth-tab-active');
        var isLogin = tab.dataset.tab === 'login';
        formLogin.classList.toggle('auth-form-hidden', !isLogin);
        formRegister.classList.toggle('auth-form-hidden', isLogin);
        slider.style.transform = isLogin ? 'translateX(0)' : 'translateX(100%)';
        msg.className = 'auth-msg';
        msg.textContent = '';
    }
    tabs.forEach(function(t){
        t.addEventListener('click', function(){ switchTab(this); });
    });

    /* ── Toggle mot de passe ── */
    document.querySelectorAll('.auth-toggle-pw').forEach(function(btn){
        btn.addEventListener('click', function(){
            var inp = this.parentElement.querySelector('input');
            inp.type = inp.type === 'password' ? 'text' : 'password';
            this.classList.toggle('auth-pw-visible');
        });
    });

    /* ── Force du mot de passe (inscription) ── */
    var regPw = document.getElementById('reg-password');
    var pwStrength = document.getElementById('auth-pw-strength');
    if (regPw && pwStrength) {
        regPw.addEventListener('input', function(){
            var v = this.value;
            var score = 0;
            if (v.length >= 8) score++;
            if (v.length >= 12) score++;
            if (/[A-Z]/.test(v)) score++;
            if (/[0-9]/.test(v)) score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;
            var fill = pwStrength.querySelector('.auth-pw-bar-fill');
            var label = pwStrength.querySelector('.auth-pw-label');
            var pct = Math.min(score * 20, 100);
            fill.style.width = pct + '%';
            var colors = ['#ef4444','#f59e0b','#eab308','#22c55e','#16a34a'];
            var labels = ['Très faible','Faible','Moyen','Fort','Très fort'];
            fill.style.background = colors[Math.min(score,4)];
            label.textContent = v.length ? labels[Math.min(score,4)] : '';
            pwStrength.style.opacity = v.length ? '1' : '0';
        });
    }

    /* ── Soumission AJAX ── */
    function submitForm(form, btn) {
        var fd = new FormData(form);
        btn.classList.add('auth-loading');
        msg.className = 'auth-msg';
        msg.textContent = '';

        fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function(r){ return r.json(); })
        .then(function(res){
            btn.classList.remove('auth-loading');
            if (res.success && res.data && res.data.redirect) {
                msg.className = 'auth-msg auth-msg-success';
                msg.textContent = 'Connexion réussie ! Redirection…';
                setTimeout(function(){ window.location.href = res.data.redirect; }, 600);
            } else {
                msg.className = 'auth-msg auth-msg-error';
                msg.textContent = (res.data && res.data.message) ? res.data.message : 'Une erreur est survenue.';
            }
        })
        .catch(function(){
            btn.classList.remove('auth-loading');
            msg.className = 'auth-msg auth-msg-error';
            msg.textContent = 'Erreur de connexion au serveur.';
        });
    }

    formLogin.addEventListener('submit', function(e){
        e.preventDefault();
        submitForm(this, document.getElementById('auth-submit-login'));
    });
    formRegister.addEventListener('submit', function(e){
        e.preventDefault();
        var pw = document.getElementById('reg-password');
        if (pw.value.length < 8) {
            msg.className = 'auth-msg auth-msg-error';
            msg.textContent = 'Le mot de passe doit contenir au moins 8 caractères.';
            return;
        }
        submitForm(this, document.getElementById('auth-submit-register'));
    });

    /* ── Slides images (ken burns) ── */
    var slides = document.querySelectorAll('.auth-visual-slide');
    var slogans = document.querySelectorAll('.auth-slogan');
    var idx = 0;
    if (slides.length > 1) {
        setInterval(function(){
            slides[idx].classList.remove('auth-slide-active');
            if (slogans[idx]) slogans[idx].classList.remove('auth-slogan-active');
            idx = (idx + 1) % slides.length;
            slides[idx].classList.add('auth-slide-active');
            if (slogans[idx]) slogans[idx].classList.add('auth-slogan-active');
        }, 5000);
    }

    /* ── Floating labels ── */
    document.querySelectorAll('.auth-field input').forEach(function(inp){
        function check(){ inp.parentElement.classList.toggle('auth-field-filled', !!inp.value); }
        inp.addEventListener('input', check);
        inp.addEventListener('blur', check);
        check();
    });

    /* ── Ouverture automatique de l'onglet inscription si ?tab=register ── */
    (function() {
        var urlP = new URLSearchParams(window.location.search);
        if (urlP.get('tab') === 'register') {
            var tabRegister = document.querySelector('[data-tab="register"]');
            if (tabRegister) switchTab(tabRegister);
        }
    })();
})();
</script>

<?php wp_footer(); ?>
</body>
</html>
