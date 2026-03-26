<?php
/**
 * Template Name: Devis gratuit — hub
 * Slug : devis-gratuit
 */
get_header();

$golf_url    = home_url('/devis-golf/');
$sejour_url  = home_url('/devis-sejour-vacances/');
$city_url    = home_url('/devis-city-trip/');
$road_url    = home_url('/devis-road-trip/');
$circuit_url = home_url('/devis-circuit/');
$tel         = vs08_opt('vs08_tel', '03 26 65 28 63');
$tel_raw     = preg_replace('/\s+/', '', $tel);
$hero_bg     = 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=1920&q=80';
?>
<style>
/* ── HERO ── */
.dv-hero{position:relative;min-height:52vh;display:flex;align-items:center;justify-content:center;text-align:center;padding:140px 24px 72px;overflow:hidden}
.dv-hero-bg{position:absolute;inset:0;background-size:cover;background-position:center}
.dv-hero-bg::after{content:'';position:absolute;inset:0;background:linear-gradient(160deg,rgba(15,36,36,.95) 0%,rgba(26,58,58,.88) 45%,rgba(232,114,74,.15) 100%)}
.dv-hero-z{position:relative;z-index:2;max-width:760px}
.dv-hero-z .dv-tag{font-size:11px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:#c9a84c;font-family:'Outfit',sans-serif;margin-bottom:14px;display:block}
.dv-hero-z h1{font-family:'Playfair Display',serif;font-size:clamp(34px,5vw,52px);color:#fff;margin:0 0 18px;line-height:1.12}
.dv-hero-z h1 em{color:#59b7b7;font-style:italic}
.dv-hero-z p{color:rgba(255,255,255,.72);font-size:17px;line-height:1.65;font-family:'Outfit',sans-serif;font-weight:300;margin:0 auto}

/* ── Trust bar ── */
.dv-trust{display:flex;justify-content:center;gap:48px;margin-top:36px;flex-wrap:wrap}
.dv-trust-item{text-align:center}
.dv-trust-item span{display:block;font-family:'Playfair Display',serif;font-size:28px;font-weight:700;color:#7ecece;line-height:1}
.dv-trust-item small{font-size:10px;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:1px;margin-top:4px;display:block;font-family:'Outfit',sans-serif}

/* ── Body ── */
.dv-wrap{background:linear-gradient(180deg,#f9f6f0 0%,#f3efe8 100%);padding:64px 24px 96px;margin-top:-2px}
.dv-inner{max-width:1200px;margin:0 auto}
.dv-section-title{text-align:center;margin-bottom:48px}
.dv-section-title h2{font-family:'Playfair Display',serif;font-size:clamp(24px,3.5vw,36px);color:#0f2424;margin:0 0 10px}
.dv-section-title p{font-family:'Outfit',sans-serif;font-size:15px;color:#6b7280;margin:0}

/* ── Cards grid ── */
.dv-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:24px}
.dv-card{position:relative;display:flex;flex-direction:column;background:#fff;border-radius:24px;text-decoration:none;color:inherit;border:1px solid rgba(89,183,183,.1);box-shadow:0 12px 40px rgba(15,36,36,.07);transition:transform .4s,box-shadow .4s;overflow:hidden}
.dv-card:hover{transform:translateY(-8px);box-shadow:0 28px 70px rgba(15,36,36,.14)}
.dv-card-img{position:relative;height:200px;overflow:hidden}
.dv-card-img img{width:100%;height:100%;object-fit:cover;transition:transform .6s}
.dv-card:hover .dv-card-img img{transform:scale(1.08)}
.dv-card-img::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,transparent 50%,rgba(15,36,36,.6) 100%)}
.dv-card-icon{position:absolute;top:16px;left:16px;width:48px;height:48px;background:rgba(255,255,255,.95);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;z-index:2;box-shadow:0 4px 16px rgba(0,0,0,.1)}
.dv-card-body{padding:24px 24px 28px;display:flex;flex-direction:column;flex:1}
.dv-card-body h2{font-family:'Playfair Display',serif;font-size:22px;color:#0f2424;margin:0 0 8px}
.dv-card-body p{font-size:14px;color:#6b7280;line-height:1.6;font-family:'Outfit',sans-serif;margin:0 0 20px;flex:1}
.dv-card-cta{display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:#fff;background:linear-gradient(135deg,#0f2424,#1a3a3a);padding:12px 24px;border-radius:100px;font-family:'Outfit',sans-serif;align-self:flex-start;transition:all .3s}
.dv-card:hover .dv-card-cta{background:linear-gradient(135deg,#e8724a,#d4603c)}

/* ── Bottom section ── */
.dv-bottom{margin-top:56px;display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:center}
.dv-bottom-text{padding:40px}
.dv-bottom-text h3{font-family:'Playfair Display',serif;font-size:24px;color:#0f2424;margin:0 0 14px}
.dv-bottom-text p{font-family:'Outfit',sans-serif;font-size:15px;color:#6b7280;line-height:1.7;margin:0 0 24px}
.dv-bottom-text .dv-steps{list-style:none;padding:0;margin:0 0 28px;display:flex;flex-direction:column;gap:14px}
.dv-bottom-text .dv-steps li{display:flex;align-items:flex-start;gap:12px;font-family:'Outfit',sans-serif;font-size:14px;color:#374151;line-height:1.5}
.dv-bottom-text .dv-step-num{width:28px;height:28px;border-radius:50%;background:#59b7b7;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0}
.dv-bottom-cta{display:flex;gap:12px;flex-wrap:wrap}
.dv-bottom-cta a{display:inline-flex;align-items:center;gap:8px;padding:14px 28px;border-radius:100px;font-weight:700;text-decoration:none;font-family:'Outfit',sans-serif;font-size:14px;transition:all .25s}
.dv-btn-dark{background:#0f2424;color:#fff}
.dv-btn-dark:hover{background:#1a3a3a;transform:translateY(-2px)}
.dv-btn-outline{background:transparent;color:#0f2424;border:2px solid #0f2424}
.dv-btn-outline:hover{background:#0f2424;color:#fff;transform:translateY(-2px)}
.dv-bottom-visual{position:relative;border-radius:24px;overflow:hidden;min-height:380px}
.dv-bottom-visual img{width:100%;height:100%;object-fit:cover;display:block}
.dv-bottom-visual::after{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(89,183,183,.15),rgba(232,114,74,.1));pointer-events:none}

@media(max-width:900px){
    .dv-hero{padding:120px 24px 48px;min-height:44vh}
    .dv-trust{gap:28px}
    .dv-grid{grid-template-columns:1fr}
    .dv-bottom{grid-template-columns:1fr}
    .dv-bottom-text{padding:24px 0}
    .dv-bottom-visual{min-height:260px}
}
</style>

<!-- HERO -->
<section class="dv-hero">
    <div class="dv-hero-bg" style="background-image:url('<?php echo esc_url($hero_bg); ?>')"></div>
    <div class="dv-hero-z">
        <span class="dv-tag">Gratuit &amp; sans engagement</span>
        <h1>Votre devis <em>sur mesure</em></h1>
        <p>Choisissez le type de voyage qui vous ressemble. Un conseiller d&eacute;di&eacute; &eacute;tudie votre demande et vous r&eacute;pond sous 24 &agrave; 48h.</p>
        <div class="dv-trust">
            <div class="dv-trust-item"><span>24-48h</span><small>D&eacute;lai de r&eacute;ponse</small></div>
            <div class="dv-trust-item"><span>100%</span><small>Sur mesure</small></div>
            <div class="dv-trust-item"><span>0&euro;</span><small>Frais de devis</small></div>
        </div>
    </div>
</section>

<!-- CARDS -->
<div class="dv-wrap">
    <div class="dv-inner">
        <div class="dv-section-title">
            <h2>Quel voyage vous fait r&ecirc;ver ?</h2>
            <p>S&eacute;lectionnez la formule qui correspond &agrave; votre projet.</p>
        </div>

        <div class="dv-grid">
            <a class="dv-card" href="<?php echo esc_url($golf_url); ?>">
                <div class="dv-card-img">
                    <div class="dv-card-icon">&#x26f3;</div>
                    <img src="https://images.unsplash.com/photo-1587174486073-ae5e5cff23aa?w=640&q=80" alt="S&eacute;jour golf" loading="lazy">
                </div>
                <div class="dv-card-body">
                    <h2>S&eacute;jour golf</h2>
                    <p>Parcours, green-fees, h&ocirc;tel, vols, groupe ou couple : le formulaire pens&eacute; pour les golfeurs exigeants.</p>
                    <span class="dv-card-cta">Demander mon devis &rarr;</span>
                </div>
            </a>

            <a class="dv-card" href="<?php echo esc_url($sejour_url); ?>">
                <div class="dv-card-img">
                    <div class="dv-card-icon">&#x1f334;</div>
                    <img src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=640&q=80" alt="S&eacute;jour vacances" loading="lazy">
                </div>
                <div class="dv-card-body">
                    <h2>S&eacute;jour vacances</h2>
                    <p>Plage, soleil, famille, tout compris ou &agrave; la carte : d&eacute;crivez l'esprit de vos prochaines vacances.</p>
                    <span class="dv-card-cta">Demander mon devis &rarr;</span>
                </div>
            </a>

            <a class="dv-card" href="<?php echo esc_url($circuit_url); ?>">
                <div class="dv-card-img">
                    <div class="dv-card-icon">&#x1f5fa;&#xfe0f;</div>
                    <img src="https://images.unsplash.com/photo-1523906834658-6e24ef2386f9?w=640&q=80" alt="Circuit" loading="lazy">
                </div>
                <div class="dv-card-body">
                    <h2>Circuit</h2>
                    <p>D&eacute;couverte guid&eacute;e ou autonome, plusieurs destinations : votre circuit cl&eacute; en main.</p>
                    <span class="dv-card-cta">Demander mon devis &rarr;</span>
                </div>
            </a>

            <a class="dv-card" href="<?php echo esc_url($road_url); ?>">
                <div class="dv-card-img">
                    <div class="dv-card-icon">&#x1f697;</div>
                    <img src="https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=640&q=80" alt="Road trip" loading="lazy">
                </div>
                <div class="dv-card-body">
                    <h2>Road trip</h2>
                    <p>Itin&eacute;raire libre, &eacute;tapes, location de v&eacute;hicule : nous montons le parcours avec vous.</p>
                    <span class="dv-card-cta">Demander mon devis &rarr;</span>
                </div>
            </a>

            <a class="dv-card" href="<?php echo esc_url($city_url); ?>">
                <div class="dv-card-img">
                    <div class="dv-card-icon">&#x1f3d9;&#xfe0f;</div>
                    <img src="https://images.unsplash.com/photo-1499856871958-5b9627545d1a?w=640&q=80" alt="City trip" loading="lazy">
                </div>
                <div class="dv-card-body">
                    <h2>City trip</h2>
                    <p>Week-end ou escapade urbaine : culture, shopping, gastronomie et rythme sur mesure.</p>
                    <span class="dv-card-cta">Demander mon devis &rarr;</span>
                </div>
            </a>
        </div>

        <!-- Bottom: How it works + visual -->
        <div class="dv-bottom">
            <div class="dv-bottom-text">
                <h3>Comment &ccedil;a marche ?</h3>
                <p>Un devis chez Voyages Sortir 08, c'est simple, rapide et gratuit.</p>
                <ul class="dv-steps">
                    <li><span class="dv-step-num">1</span><div>Remplissez le formulaire correspondant &agrave; votre type de voyage (2 minutes).</div></li>
                    <li><span class="dv-step-num">2</span><div>Un conseiller d&eacute;di&eacute; &eacute;tudie votre demande et vous recontacte sous 24-48h.</div></li>
                    <li><span class="dv-step-num">3</span><div>Recevez votre devis d&eacute;taill&eacute; par email, pr&ecirc;t &agrave; r&eacute;server.</div></li>
                </ul>
                <div class="dv-bottom-cta">
                    <a href="tel:<?php echo esc_attr($tel_raw); ?>" class="dv-btn-dark">&#x1f4de; <?php echo esc_html($tel); ?></a>
                    <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="dv-btn-outline">Nous contacter</a>
                </div>
            </div>
            <div class="dv-bottom-visual">
                <img src="https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=800&q=80" alt="Voyager avec Sortir 08" loading="lazy">
            </div>
        </div>

    </div>
</div>

<?php get_footer(); ?>
