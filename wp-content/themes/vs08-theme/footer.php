<?php
/**
 * Footer — Voyages Sortir 08 (2026 Premium Editorial)
 */
if (!defined('ABSPATH')) exit;
$theme_uri = get_template_directory_uri();
$ft_slide_dir = get_template_directory() . '/assets/img/footer-slideshow/';
$ft_bg_slides = [];
$ft_slide_exts = ['jpg', 'jpeg', 'webp', 'png'];
for ($n = 1; $n <= 12; $n++) {
    foreach ($ft_slide_exts as $ext) {
        $fn = sprintf('%02d.%s', $n, $ext);
        if (is_readable($ft_slide_dir . $fn)) {
            $ft_bg_slides[] = $theme_uri . '/assets/img/footer-slideshow/' . $fn;
            break;
        }
    }
}
/** URLs séparées par | pour remplacer le diaporama (option) */
if (function_exists('vs08_opt')) {
    $custom = trim((string) vs08_opt('vs08_footer_bg_slides', ''));
    if ($custom !== '') {
        $ft_bg_slides = array_values(array_filter(array_map('trim', explode('|', $custom))));
    } elseif ($ft_bg_slides === []) {
        $one = trim((string) vs08_opt('vs08_footer_bg_img', ''));
        if ($one !== '') {
            $ft_bg_slides = [$one];
        }
    }
}
$ft_slide_count = count($ft_bg_slides);
?>
<style>
/* ═══════════════════════════════════════════════════════════════
   FOOTER 2026 — PLEINE LARGEUR + ANIMATIONS LÉGÈRES
   ═══════════════════════════════════════════════════════════════ */
:root{
    --ft-bg:#080c14;
    --ft-bg2:#0b1018;
    --ft-teal:#59b7b7;
    --ft-teal-l:#7dd3d3;
    --ft-teal-ul:rgba(89,183,183,.06);
    --ft-gold:#c8a45e;
    --ft-txt:#6b7a90;
    --ft-txt-l:#9aabbf;
    --ft-heading:#e8ecf2;
    --ft-border:rgba(89,183,183,.08);
    --ft-radius:16px;
    --ft-pad:clamp(24px,5vw,80px);
}

/* Bloc footer : fond sur toute la hauteur + pleine largeur écran */
.ft-wrap{
    position:relative;left:50%;transform:translateX(-50%);
    width:100vw;max-width:100vw;overflow:hidden;
    display:flex;flex-direction:column;
    margin-top:-1px;
}
.ft-bg-scene{
    position:absolute;left:0;top:0;right:0;bottom:0;width:100%;height:100%;
    z-index:0;pointer-events:none;overflow:hidden;
}
.ft-bg-slides{position:absolute;inset:0;width:100%;height:100%}
/* Couvre tout le bloc (pas de bande) ; léger débordement anti-tranche */
.ft-bg-slide{
    position:absolute;left:-2%;top:-2%;width:104%;height:104%;
    min-width:100%;min-height:100%;
    background-size:cover;background-position:center top;background-repeat:no-repeat;
    opacity:0;transition:opacity 1.6s ease-in-out;z-index:0;will-change:opacity;
}
.ft-bg-slide.is-active{opacity:1;z-index:1}
/* Voile uniforme (#080c14) — pas de radial ni paliers : évite la « vague » au milieu */
.ft-bg-veil{
    position:absolute;inset:0;z-index:2;
    background:rgba(8,12,20,.93);
}

/* ── Footer root (fond transparent : voile + photo dessous) ── */
.ft-root{
    background:transparent;
    font-family:'Outfit',sans-serif;
    color:var(--ft-txt);
    position:relative;z-index:3;
    overflow:hidden;
}
.ft-root *{margin:0;padding:0;box-sizing:border-box}

/* Ambient glows + pulse */
.ft-glow{position:absolute;border-radius:50%;pointer-events:none;filter:blur(120px);will-change:transform,opacity;z-index:4}
.ft-glow--1{width:min(70vw,700px);height:min(70vw,700px);top:-18%;left:-8%;background:radial-gradient(circle,rgba(89,183,183,.07),transparent 65%);animation:ft-glow-pulse 14s ease-in-out infinite}
.ft-glow--2{width:min(55vw,520px);height:min(55vw,520px);bottom:-12%;right:-5%;background:radial-gradient(circle,rgba(200,164,94,.05),transparent 60%);animation:ft-glow-pulse 18s ease-in-out infinite 3s}
@keyframes ft-glow-pulse{
    0%,100%{opacity:.55;transform:scale(1) translate(0,0)}
    50%{opacity:.95;transform:scale(1.08) translate(2%,-1%)}
}

/* Particules / étoiles discrètes */
.ft-stars{position:absolute;inset:0;pointer-events:none;overflow:hidden;z-index:4}
.ft-star{
    position:absolute;width:3px;height:3px;border-radius:50%;
    background:rgba(89,183,183,.35);
    box-shadow:0 0 6px rgba(89,183,183,.25);
    animation:ft-twinkle 4.5s ease-in-out infinite;
}
.ft-star:nth-child(1){top:12%;left:8%;animation-delay:0s}
.ft-star:nth-child(2){top:22%;left:28%;animation-delay:.7s;width:2px;height:2px;opacity:.6}
.ft-star:nth-child(3){top:18%;right:18%;animation-delay:1.4s}
.ft-star:nth-child(4){top:45%;left:5%;animation-delay:2.1s;width:2px;height:2px}
.ft-star:nth-child(5){top:38%;right:12%;animation-delay:2.8s}
.ft-star:nth-child(6){top:62%;left:42%;animation-delay:1.2s;opacity:.5}
.ft-star:nth-child(7){top:55%;right:28%;animation-delay:3.5s;width:2px;height:2px}
.ft-star:nth-child(8){bottom:28%;left:15%;animation-delay:.4s}
@keyframes ft-twinkle{
    0%,100%{opacity:.15;transform:scale(.8)}
    50%{opacity:.9;transform:scale(1.15)}
}

/* Ligne lumineuse animée (au-dessus de la barre confiance) */
.ft-beam-wrap{
    position:relative;z-index:4;height:0;margin:0;padding:0 var(--ft-pad);
    overflow:visible;
}
.ft-beam-wrap--before-trust{margin:8px 0 0;padding:0}
.ft-beam-wrap--before-trust .ft-beam{padding:0 var(--ft-pad)}
.ft-beam{
    position:relative;height:2px;
    background:transparent;
    overflow:hidden;
}
.ft-beam::after{
    content:'';position:absolute;top:0;left:-30%;width:30%;height:100%;
    background:linear-gradient(90deg,transparent,rgba(125,211,211,.55),rgba(200,164,94,.35),transparent);
    animation:ft-beam-run 9s ease-in-out infinite;
    filter:blur(.5px);
}
@keyframes ft-beam-run{
    0%{left:-30%;opacity:0}
    10%{opacity:1}
    90%{opacity:1}
    100%{left:130%;opacity:0}
}

/* ── Separator pleine largeur ── */
.ft-sep{
    width:100%;height:1px;margin:0;
    background:linear-gradient(90deg,transparent,var(--ft-border) 15%,rgba(89,183,183,.12) 50%,var(--ft-border) 85%,transparent);
}

/* ── Main columns — pleine largeur ── */
.ft-cols{
    width:100%;
    padding:64px var(--ft-pad) 48px;
    display:grid;
    grid-template-columns:1.5fr 1fr 1fr 1.3fr;
    gap:clamp(32px,4vw,64px);
    position:relative;z-index:5;
}

/* Brand col */
.ft-brand-logo{height:40px;opacity:.8;margin-bottom:20px}
.ft-brand-desc{font-size:15.5px;line-height:1.75;margin-bottom:16px;max-width:none}
.ft-brand-tag{
    font-family:'Playfair Display',serif;font-style:italic;
    font-size:15px;color:var(--ft-teal-l);
    padding:10px 0 10px 16px;
    border-left:2px solid var(--ft-teal);
    margin-bottom:24px;
    display:block;
}
.ft-socials{display:flex;gap:8px}
.ft-soc{
    width:38px;height:38px;
    border-radius:12px;
    border:1px solid var(--ft-border);
    display:flex;align-items:center;justify-content:center;
    color:var(--ft-txt);
    text-decoration:none;
    transition:all .35s;
}
.ft-soc:hover{
    border-color:var(--ft-teal);
    color:var(--ft-teal);
    background:var(--ft-teal-ul);
    transform:translateY(-3px);
    box-shadow:0 6px 20px rgba(89,183,183,.12);
}
.ft-soc svg{width:15px;height:15px}

/* Link columns */
.ft-lcol h5{
    font-size:13px;letter-spacing:.15em;text-transform:uppercase;
    color:var(--ft-teal);font-weight:700;
    margin-bottom:20px;
    position:relative;padding-bottom:12px;
}
.ft-lcol h5::after{
    content:'';position:absolute;bottom:0;left:0;
    width:24px;height:2px;border-radius:2px;
    background:linear-gradient(90deg,var(--ft-teal),transparent);
}
.ft-lcol ul{list-style:none}
.ft-lcol li{margin-bottom:10px}
.ft-lcol a{
    color:var(--ft-txt);text-decoration:none;
    font-size:15.5px;line-height:1.45;
    transition:all .3s ease;
    display:inline-block;
    position:relative;
}
.ft-lcol a::after{
    content:'';position:absolute;bottom:-1px;left:0;
    width:0;height:1px;
    background:var(--ft-teal);
    transition:width .3s ease;
}
.ft-lcol a:hover{color:var(--ft-teal-l);transform:translateX(4px)}
.ft-lcol a:hover::after{width:100%}

/* Contact col */
.ft-contact-item{
    display:flex;gap:14px;align-items:flex-start;
    margin-bottom:18px;
}
.ft-contact-ic{
    width:36px;height:36px;flex-shrink:0;
    border-radius:12px;
    background:var(--ft-teal-ul);
    border:1px solid rgba(89,183,183,.1);
    display:flex;align-items:center;justify-content:center;
    font-size:14px;
    transition:all .3s;
}
.ft-contact-item:hover .ft-contact-ic{
    background:rgba(89,183,183,.12);
    border-color:rgba(89,183,183,.2);
    transform:scale(1.05);
}
.ft-contact-txt{font-size:15.5px;line-height:1.5}
.ft-contact-txt a{color:var(--ft-txt-l);text-decoration:none;transition:color .3s}
.ft-contact-txt a:hover{color:var(--ft-teal-l)}
.ft-contact-txt .ft-sub{font-size:12px;color:var(--ft-txt);opacity:.45;margin-top:2px}

/* ── Trust bar pleine largeur ── */
.ft-trust{
    position:relative;z-index:5;
    border-top:1px solid var(--ft-border);
    border-bottom:1px solid var(--ft-border);
}
.ft-trust-in{
    width:100%;
    padding:24px var(--ft-pad);
    display:flex;align-items:center;justify-content:center;
    gap:12px;
    flex-wrap:wrap;
}
.ft-trust-item{
    display:flex;align-items:center;gap:8px;
    padding:8px 18px;
    border-radius:10px;
    border:1px solid var(--ft-border);
    background:rgba(255,255,255,.015);
    transition:all .35s;
}
.ft-trust-item:hover{
    border-color:rgba(89,183,183,.2);
    background:var(--ft-teal-ul);
    transform:translateY(-2px);
}
.ft-trust-ico{font-size:16px;flex-shrink:0}
.ft-trust-lbl{font-size:13px;font-weight:600;color:var(--ft-txt-l);letter-spacing:.3px}
.ft-trust-lbl small{display:block;font-size:11px;font-weight:400;color:var(--ft-txt);opacity:.5;margin-top:1px}
.ft-trust-dot{
    width:3px;height:3px;border-radius:50%;
    background:rgba(89,183,183,.25);
    flex-shrink:0;
    animation:ft-dot-pulse 3s ease-in-out infinite;
}
.ft-trust-dot:nth-child(4){animation-delay:.5s}
.ft-trust-dot:nth-child(6){animation-delay:1s}
.ft-trust-dot:nth-child(8){animation-delay:1.5s}
@keyframes ft-dot-pulse{
    0%,100%{opacity:.35;transform:scale(1)}
    50%{opacity:.85;transform:scale(1.4)}
}

/* ── Bottom bar pleine largeur ── */
.ft-bottom{
    width:100%;
    padding:20px var(--ft-pad);
    display:flex;justify-content:space-between;align-items:center;
    flex-wrap:wrap;gap:12px;
    position:relative;z-index:5;
}
.ft-copy{font-size:13px;color:var(--ft-txt);opacity:.35}
.ft-copy a{color:inherit;text-decoration:none}
.ft-legal{display:flex;gap:20px}
.ft-legal a{
    font-size:13px;color:var(--ft-txt);opacity:.35;
    text-decoration:none;transition:all .3s;
}
.ft-legal a:hover{opacity:.7;color:var(--ft-teal)}
.ft-made{
    font-size:12px;color:var(--ft-txt);opacity:.2;
    display:flex;align-items:center;gap:4px;
}

@media (prefers-reduced-motion:reduce){
    .ft-glow--1,.ft-glow--2,.ft-star,.ft-beam::after,.ft-trust-dot{animation:none!important}
    .ft-beam::after{left:50%;transform:translateX(-50%);opacity:.4}
    .ft-bg-slide{transition:none!important}
    .ft-bg-slide:not(:first-child){opacity:0!important;visibility:hidden}
    .ft-bg-slide:first-child{opacity:1!important}
}

/* ── Responsive ── */
@media(max-width:960px){
    .ft-cols{grid-template-columns:1fr 1fr;gap:36px}
}
@media(max-width:600px){
    .ft-cols{grid-template-columns:1fr;gap:28px;padding-top:40px}
    .ft-bottom{flex-direction:column;text-align:center}
    .ft-legal{justify-content:center}
    .ft-trust-in{gap:8px}
    .ft-trust-item{padding:6px 12px}
}
</style>

<!-- ═══════════════════════════════════════════════════════════════
     FOOTER (fond commun sur toute la hauteur — sans séparateur vague)
     ═══════════════════════════════════════════════════════════════ -->
<div class="ft-wrap">
    <div class="ft-bg-scene" aria-hidden="true">
        <?php if (!empty($ft_bg_slides)) : ?>
        <div class="ft-bg-slides" id="ft-bg-slides">
            <?php foreach ($ft_bg_slides as $si => $url) : ?>
            <div class="ft-bg-slide<?php echo $si === 0 ? ' is-active' : ''; ?>" style="background-image:url('<?php echo esc_url($url); ?>')" data-ft-slide="<?php echo (int) $si; ?>"></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="ft-bg-veil"></div>
    </div>

<footer class="ft-root">
    <div class="ft-stars" aria-hidden="true">
        <span class="ft-star"></span><span class="ft-star"></span><span class="ft-star"></span><span class="ft-star"></span>
        <span class="ft-star"></span><span class="ft-star"></span><span class="ft-star"></span><span class="ft-star"></span>
    </div>
    <div class="ft-glow ft-glow--1"></div>
    <div class="ft-glow ft-glow--2"></div>

    <div class="ft-cols">

        <div>
            <img class="ft-brand-logo" src="<?php echo esc_url($theme_uri . '/assets/img/logo.png'); ?>" alt="Voyages Sortir 08" onerror="this.style.display='none'">
            <p class="ft-brand-desc">Spécialiste des séjours golf tout compris depuis plus de 20 ans. Parcours d'exception, hôtels de charme, vols inclus.</p>
            <span class="ft-brand-tag">« Libre à vous de payer plus cher ! »</span>
            <div class="ft-socials">
                <a href="#" class="ft-soc" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
                <a href="#" class="ft-soc" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg></a>
                <a href="#" class="ft-soc" aria-label="LinkedIn"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>
                <a href="#" class="ft-soc" aria-label="TikTok"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 0010.86 4.43v-7.15a8.16 8.16 0 005.58 2.2v-3.45a4.85 4.85 0 01-1-.19z"/></svg></a>
            </div>
        </div>

        <div class="ft-lcol">
            <h5>Nos destinations</h5>
            <ul>
                <li><a href="<?php echo esc_url(home_url('/resultats-recherche?type=sejour_golf&dest=Portugal')); ?>">Portugal — Algarve</a></li>
                <li><a href="<?php echo esc_url(home_url('/resultats-recherche?type=sejour_golf&dest=Espagne')); ?>">Espagne — Andalousie</a></li>
                <li><a href="<?php echo esc_url(home_url('/resultats-recherche?type=sejour_golf&dest=Maroc')); ?>">Maroc — Golf</a></li>
                <li><a href="<?php echo esc_url(home_url('/resultats-recherche?type=circuit&dest=Italie')); ?>">Italie — Circuits</a></li>
                <li><a href="<?php echo esc_url(home_url('/resultats-recherche?type=circuit&dest=Grèce')); ?>">Grèce — Circuits</a></li>
                <li><a href="<?php echo esc_url(home_url('/resultats-recherche')); ?>">Toutes nos destinations →</a></li>
            </ul>
        </div>

        <div class="ft-lcol">
            <h5>L'agence</h5>
            <ul>
                <li><a href="<?php echo esc_url(home_url('/qui-sommes-nous/')); ?>">Qui sommes-nous</a></li>
                <li><a href="<?php echo esc_url(home_url('/avis-clients/')); ?>">Avis clients</a></li>
                <li><a href="<?php echo esc_url(home_url('/comment-reserver/')); ?>">Comment réserver</a></li>
                <li><a href="<?php echo esc_url(home_url('/devis-gratuit/')); ?>">Devis gratuit</a></li>
                <li><a href="<?php echo esc_url(home_url('/assurances/')); ?>">Assurances voyage</a></li>
                <li><a href="<?php echo esc_url(home_url('/faq/')); ?>">FAQ</a></li>
            </ul>
        </div>

        <div class="ft-lcol">
            <h5>Nous contacter</h5>
            <div class="ft-contact-item">
                <span class="ft-contact-ic">📞</span>
                <div class="ft-contact-txt"><a href="tel:0326652863">03 26 65 28 63</a><div class="ft-sub">Lun — Ven · 9h — 18h</div></div>
            </div>
            <div class="ft-contact-item">
                <span class="ft-contact-ic">✉️</span>
                <div class="ft-contact-txt"><a href="mailto:resa@voyagessortir08.com">resa@voyagessortir08.com</a></div>
            </div>
            <div class="ft-contact-item">
                <span class="ft-contact-ic">📍</span>
                <div class="ft-contact-txt">Voyages Sortir 08<br>Châlons-en-Champagne (51)</div>
            </div>
            <div class="ft-contact-item">
                <span class="ft-contact-ic">🏢</span>
                <div class="ft-contact-txt"><a href="<?php echo esc_url(home_url('/contact/')); ?>">Venir en agence →</a></div>
            </div>
        </div>
    </div>

    <div class="ft-beam-wrap ft-beam-wrap--before-trust"><div class="ft-beam" aria-hidden="true"></div></div>

    <div class="ft-trust"><div class="ft-trust-in">
        <div class="ft-trust-item"><span class="ft-trust-ico">🛡️</span><span class="ft-trust-lbl">APST<small>Garantie financière</small></span></div>
        <span class="ft-trust-dot"></span>
        <div class="ft-trust-item"><span class="ft-trust-ico">✈️</span><span class="ft-trust-lbl">Atout France<small>Immatriculation IM051</small></span></div>
        <span class="ft-trust-dot"></span>
        <div class="ft-trust-item"><span class="ft-trust-ico">🔒</span><span class="ft-trust-lbl">3D Secure<small>Paiement sécurisé</small></span></div>
        <span class="ft-trust-dot"></span>
        <div class="ft-trust-item"><span class="ft-trust-ico">📋</span><span class="ft-trust-lbl">Hiscox<small>RC Professionnelle</small></span></div>
        <span class="ft-trust-dot"></span>
        <div class="ft-trust-item"><span class="ft-trust-ico">⭐</span><span class="ft-trust-lbl">4.8 / 5<small>Avis clients vérifiés</small></span></div>
    </div></div>

    <div class="ft-bottom">
        <p class="ft-copy">© <?php echo esc_html(date('Y')); ?> <a href="<?php echo esc_url(home_url('/')); ?>">Voyages Sortir 08</a> — Tous droits réservés</p>
        <nav class="ft-legal">
            <a href="<?php echo esc_url(home_url('/conditions/')); ?>">CGV</a>
            <a href="<?php echo esc_url(home_url('/mentions-legales/')); ?>">Mentions légales</a>
            <a href="<?php echo esc_url(home_url('/rgpd/')); ?>">Confidentialité</a>
        </nav>
        <span class="ft-made">Fait avec ♥ à Châlons-en-Champagne</span>
    </div>
</footer>
</div><!-- .ft-wrap -->
<?php if ($ft_slide_count > 1) : ?>
<script>
(function(){
  var slides = document.querySelectorAll('#ft-bg-slides .ft-bg-slide');
  if (!slides.length || slides.length < 2) return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  var i = 0, t = 7500;
  function go(){
    slides[i].classList.remove('is-active');
    i = (i + 1) % slides.length;
    slides[i].classList.add('is-active');
  }
  setInterval(go, t);
})();
</script>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
