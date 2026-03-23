<?php
/**
 * Footer — Voyages Sortir 08 (boarding style)
 */
if (!defined('ABSPATH')) exit;
$theme_uri = get_template_directory_uri();
?>
<style>
.ft{
    --bg:#0a0e16;--bg2:#0a0e16;--bg3:#0a0e16;
    --tq:#59b7b7;--tq-light:#7dd3d3;--tq-glow:rgba(89,183,183,.15);--tq-dark:#3a8a8a;
    --txt:#8898b0;--txt-light:#b8c8d8;--heading:#eef0f6;
    --border:rgba(89,183,183,.08);
    font-family:'Outfit',sans-serif;color:var(--txt);background:var(--bg);position:relative;overflow:hidden
}
.ft-path{height:80px;position:relative;overflow:hidden}
.ft-path svg{width:100%;height:100%}
.flight-line{stroke-dasharray:8 12;animation:dash 20s linear infinite}
@keyframes dash{to{stroke-dashoffset:-600}}
.plane-icon{animation:flyPath 12s ease-in-out infinite}
@keyframes flyPath{0%{opacity:0;transform:translate(0,0)}5%{opacity:1}50%{transform:translate(600px,-15px)}95%{opacity:1}100%{opacity:0;transform:translate(1200px,0)}}
.ft-main{max-width:1200px;margin:0 auto;padding:56px 24px 44px;display:grid;grid-template-columns:1.6fr 1fr 1fr 1.4fr;gap:48px}
.ft-brand img{height:44px;opacity:.85;margin-bottom:18px}
.ft-brand p{font-size:13.5px;line-height:1.8;margin-bottom:16px}
.ft-brand .tag{font-family:'Playfair Display',serif;font-style:italic;font-size:13px;color:var(--tq-light);padding:8px 0 8px 14px;border-left:2px solid var(--tq);display:block;margin-bottom:22px}
.ft-social{display:flex;gap:6px}
.ft-social a{width:36px;height:36px;border-radius:10px;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--txt);text-decoration:none;transition:all .35s}
.ft-social a:hover{border-color:var(--tq);color:var(--tq);background:var(--tq-glow);transform:translateY(-2px)}
.ft-col h5{font-size:13px;letter-spacing:.12em;text-transform:uppercase;color:var(--tq);font-weight:600;margin-bottom:18px}
.ft-col ul{list-style:none}.ft-col li{margin-bottom:8px}
.ft-col a{color:var(--txt);text-decoration:none;font-size:13.5px;transition:all .3s;position:relative}
.ft-col a:hover{color:var(--tq-light);padding-left:8px}
.ft-col a::before{content:'→';position:absolute;left:-14px;opacity:0;color:var(--tq);font-size:11px;transition:all .3s;transform:translateX(-4px)}.ft-col a:hover::before{opacity:.6;transform:translateX(0)}
.ft-contact li{display:flex;gap:12px;align-items:flex-start;margin-bottom:14px;font-size:13.5px;line-height:1.5}
.ft-contact .ic{width:32px;height:32px;flex-shrink:0;border-radius:10px;background:var(--tq-glow);border:1px solid rgba(89,183,183,.12);display:flex;align-items:center;justify-content:center;font-size:13px}
.ft-contact a{color:var(--txt-light);text-decoration:none;transition:color .3s}.ft-contact a:hover{color:var(--tq-light)}
.ft-contact .sub{font-size:10px;color:var(--txt);opacity:.45;margin-top:1px}
.ft-stamps{padding:28px 24px;border-top:1px solid var(--border)}
.ft-stamps-in{max-width:1200px;margin:0 auto;display:flex;justify-content:center;gap:36px;flex-wrap:wrap}
.ft-stmp{width:80px;height:80px;border-radius:50%;border:1.5px solid rgba(89,183,183,.2);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;opacity:.45;transition:all .4s;position:relative}
.ft-stmp:hover{opacity:.85;border-color:var(--tq);transform:rotate(-3deg) scale(1.05)}
.ft-stmp::after{content:'';position:absolute;inset:3px;border-radius:50%;border:1px dashed rgba(89,183,183,.15)}
.ft-stmp .ico{font-size:18px}.ft-stmp .lbl{font-size:7px;letter-spacing:.15em;text-transform:uppercase;color:var(--tq);font-weight:700;text-align:center;line-height:1.2}
.ft-bot{padding:16px 24px;border-top:1px solid var(--border)}
.ft-bot-in{max-width:1200px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
.ft-cp{font-size:11px;opacity:.3}.ft-cp a{color:var(--txt);text-decoration:none}
.ft-lg{display:flex;gap:20px}
.ft-lg a{font-size:11px;color:var(--txt);opacity:.3;text-decoration:none;transition:all .3s}.ft-lg a:hover{opacity:.7;color:var(--tq)}
@media(max-width:960px){.ft-main{grid-template-columns:1fr 1fr;gap:32px}.ft-stmp{width:68px;height:68px}}
@media(max-width:600px){.ft-main{grid-template-columns:1fr;gap:28px}.ft-bot-in{flex-direction:column;text-align:center}.ft-lg{justify-content:center}.ft-stamps-in{gap:16px}.ft-stmp{width:60px;height:60px}.ft-stmp .ico{font-size:15px}.ft-stmp .lbl{font-size:6px}}
</style>

<footer class="ft">
<div class="ft-path">
<svg viewBox="0 0 1400 80" preserveAspectRatio="xMidYMid slice">
    <rect width="1400" height="80" fill="#0a0e16"/>
    <path d="M-20 55 Q200 30 400 42 Q600 20 800 38 Q1000 15 1200 35 Q1350 28 1420 40" fill="none" stroke="rgba(89,183,183,.12)" stroke-width="1.5" class="flight-line"/>
    <circle cx="120" cy="47" fill="#59b7b7" opacity=".6"><animate attributeName="r" values="2;4;2" dur="3s" repeatCount="indefinite"/></circle>
    <circle cx="400" cy="42" fill="#59b7b7" opacity=".6"><animate attributeName="r" values="2;4;2" dur="3s" begin=".5s" repeatCount="indefinite"/></circle>
    <circle cx="680" cy="28" fill="#59b7b7" opacity=".6"><animate attributeName="r" values="2;4;2" dur="3s" begin="1s" repeatCount="indefinite"/></circle>
    <circle cx="950" cy="25" fill="#59b7b7" opacity=".6"><animate attributeName="r" values="2;4;2" dur="3s" begin="1.5s" repeatCount="indefinite"/></circle>
    <circle cx="1250" cy="33" fill="#59b7b7" opacity=".6"><animate attributeName="r" values="2;4;2" dur="3s" begin="2s" repeatCount="indefinite"/></circle>
    <text x="120" y="64" fill="rgba(89,183,183,.35)" font-family="Outfit" font-size="8" text-anchor="middle">CDG</text>
    <text x="400" y="57" fill="rgba(89,183,183,.35)" font-family="Outfit" font-size="8" text-anchor="middle">RAK</text>
    <text x="680" y="43" fill="rgba(89,183,183,.35)" font-family="Outfit" font-size="8" text-anchor="middle">FAO</text>
    <text x="950" y="40" fill="rgba(89,183,183,.35)" font-family="Outfit" font-size="8" text-anchor="middle">AGP</text>
    <text x="1250" y="48" fill="rgba(89,183,183,.35)" font-family="Outfit" font-size="8" text-anchor="middle">HKT</text>
    <g class="plane-icon" style="transform-box:fill-box;transform-origin:center">
        <path d="M82 40 L74 40 L70 34 L68 34 L70 40 L56 40 L54 37 L52 37 L53 40 L50 40 L50 42 L53 42 L52 45 L54 45 L56 42 L70 42 L68 48 L70 48 L74 42 L82 42 Z" fill="rgba(89,183,183,.65)"/>
        <path d="M70 34 L68 34 L70 40 L56 40 L56 42 L70 42 L68 48 L70 48 L74 42 L74 40 Z" fill="rgba(89,183,183,.4)"/>
    </g>
</svg>
</div>

<div class="ft-main">
    <div class="ft-brand">
        <img src="<?php echo esc_url($theme_uri . '/assets/img/logo.png'); ?>" alt="Voyages Sortir 08" onerror="this.style.display='none'">
        <p>Spécialiste des séjours golf tout compris depuis plus de 20 ans. Parcours d'exception, hôtels de charme, vols inclus — vous n'avez qu'à jouer.</p>
        <span class="tag">« Libre à vous de payer plus cher ! »</span>
        <div class="ft-social">
            <a href="#" aria-label="Facebook"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
            <a href="#" aria-label="Instagram"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg></a>
            <a href="#" aria-label="LinkedIn"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>
        </div>
    </div>
    <div class="ft-col">
        <h5>Séjours Golf</h5>
        <ul>
            <li><a href="<?php echo esc_url(home_url('/resultats-recherche?dest=algarve')); ?>">Portugal — Algarve</a></li>
            <li><a href="<?php echo esc_url(home_url('/resultats-recherche?dest=marbella')); ?>">Espagne — Marbella</a></li>
            <li><a href="<?php echo esc_url(home_url('/resultats-recherche?dest=marrakech')); ?>">Maroc — Marrakech</a></li>
            <li><a href="<?php echo esc_url(home_url('/resultats-recherche?dest=belek')); ?>">Turquie — Belek</a></li>
            <li><a href="<?php echo esc_url(home_url('/resultats-recherche?dest=kerry')); ?>">Irlande — Kerry</a></li>
            <li><a href="<?php echo esc_url(home_url('/resultats-recherche?dest=phuket')); ?>">Thaïlande — Phuket</a></li>
        </ul>
    </div>
    <div class="ft-col">
        <h5>L'agence</h5>
        <ul>
            <li><a href="<?php echo esc_url(home_url('/qui-sommes-nous/')); ?>">Qui sommes-nous</a></li>
            <li><a href="<?php echo esc_url(home_url('/avis-clients/')); ?>">Avis clients</a></li>
            <li><a href="<?php echo esc_url(home_url('/comment-reserver/')); ?>">Comment réserver</a></li>
            <li><a href="<?php echo esc_url(home_url('/blog/')); ?>">Blog voyage & golf</a></li>
            <li><a href="<?php echo esc_url(home_url('/assurances/')); ?>">Assurances voyage</a></li>
            <li><a href="<?php echo esc_url(home_url('/faq/')); ?>">FAQ</a></li>
        </ul>
    </div>
    <div class="ft-col">
        <h5>Nous contacter</h5>
        <ul class="ft-contact">
            <li><span class="ic">📞</span><div><a href="tel:0326652863">03 26 65 28 63</a><div class="sub">Lun — Ven · 9h — 18h</div></div></li>
            <li><span class="ic">✉️</span><div><a href="mailto:resa@voyagessortir08.com">resa@voyagessortir08.com</a></div></li>
            <li><span class="ic">📍</span><div>Voyages Sortir 08<br>Châlons-en-Champagne (51)</div></li>
            <li><span class="ic">🏢</span><div><a href="<?php echo esc_url(home_url('/contact/')); ?>">Venir en agence →</a></div></li>
        </ul>
    </div>
</div>

<div class="ft-stamps"><div class="ft-stamps-in">
    <div class="ft-stmp"><span class="ico">🛡️</span><span class="lbl">APST<br>Garantie</span></div>
    <div class="ft-stmp"><span class="ico">✈️</span><span class="lbl">Atout<br>France</span></div>
    <div class="ft-stmp"><span class="ico">🔒</span><span class="lbl">3D<br>Secure</span></div>
    <div class="ft-stmp"><span class="ico">📋</span><span class="lbl">Hiscox<br>RC Pro</span></div>
</div></div>

<div class="ft-bot"><div class="ft-bot-in">
    <p class="ft-cp">© <?php echo esc_html(date('Y')); ?> <a href="<?php echo esc_url(home_url('/')); ?>">Voyages Sortir 08</a> — Tous droits réservés</p>
    <nav class="ft-lg">
        <a href="<?php echo esc_url(home_url('/conditions/')); ?>">CGV</a>
        <a href="<?php echo esc_url(home_url('/mentions-legales/')); ?>">Mentions légales</a>
        <a href="<?php echo esc_url(home_url('/rgpd/')); ?>">Confidentialité</a>
    </nav>
</div></div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
