<?php
/**
 * Template Name: Devis gratuit — hub
 * Slug : devis-gratuit
 */
get_header();

$golf_url = home_url('/devis-golf/');
$sejour_url = home_url('/devis-sejour-vacances/');
$city_url = home_url('/devis-city-trip/');
$road_url = home_url('/devis-road-trip/');
$circuit_url = home_url('/devis-circuit/');
$hero_bg = 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=1920&q=80';
?>
<style>
.vs08-dv-hero{position:relative;min-height:48vh;display:flex;align-items:center;justify-content:center;text-align:center;padding:120px 24px 64px;overflow:hidden}
.vs08-dv-hero-bg{position:absolute;inset:0;background-size:cover;background-position:center}
.vs08-dv-hero-bg::after{content:'';position:absolute;inset:0;background:linear-gradient(160deg,rgba(15,36,36,.94) 0%,rgba(26,58,58,.88) 40%,rgba(232,114,74,.2) 100%)}
.vs08-dv-hero-z{position:relative;z-index:2;max-width:720px}
.vs08-dv-hero-z .tag{font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#7ecece;font-family:'Outfit',sans-serif;margin-bottom:14px;display:block}
.vs08-dv-hero-z h1{font-family:'Playfair Display',serif;font-size:clamp(32px,5vw,48px);color:#fff;margin:0 0 16px;line-height:1.12}
.vs08-dv-hero-z h1 em{color:#59b7b7;font-style:italic}
.vs08-dv-hero-z p{color:rgba(255,255,255,.75);font-size:17px;line-height:1.65;font-family:'Outfit',sans-serif;font-weight:300;margin:0}
.vs08-dv-wrap{background:linear-gradient(180deg,#f9f6f0 0%,#f3efe8 100%);padding:56px 24px 96px;margin-top:-2px}
.vs08-dv-inner{max-width:1100px;margin:0 auto}
.vs08-dv-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px}
.vs08-dv-card{position:relative;display:flex;flex-direction:column;background:#fff;border-radius:24px;padding:32px 28px 28px;text-decoration:none;color:inherit;border:1px solid rgba(89,183,183,.15);box-shadow:0 12px 40px rgba(15,36,36,.07);transition:transform .35s,box-shadow .35s,border-color .35s;overflow:hidden}
.vs08-dv-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#59b7b7,#e8724a);opacity:.85}
.vs08-dv-card:hover{transform:translateY(-8px);box-shadow:0 28px 60px rgba(15,36,36,.14);border-color:rgba(89,183,183,.35)}
.vs08-dv-card-ic{font-size:40px;line-height:1;margin-bottom:16px}
.vs08-dv-card h2{font-family:'Playfair Display',serif;font-size:22px;color:#0f2424;margin:0 0 10px}
.vs08-dv-card p{font-size:14px;color:#6b7280;line-height:1.6;font-family:'Outfit',sans-serif;margin:0 0 20px;flex:1}
.vs08-dv-card span.cta{display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:#fff;background:#0f2424;padding:12px 22px;border-radius:100px;font-family:'Outfit',sans-serif;align-self:flex-start;transition:background .25s}
.vs08-dv-card:hover span.cta{background:#e8724a}
.vs08-dv-foot{text-align:center;margin-top:48px;padding:28px;font-size:14px;color:#6b7280;font-family:'Outfit',sans-serif}
.vs08-dv-foot a{color:#59b7b7;font-weight:600}
</style>

<section class="vs08-dv-hero">
    <div class="vs08-dv-hero-bg" style="background-image:url('<?php echo esc_url($hero_bg); ?>')"></div>
    <div class="vs08-dv-hero-z">
        <span class="tag">Sans engagement</span>
        <h1>Votre devis <em>sur mesure</em></h1>
        <p>Choisissez le type de voyage qui vous ressemble. Un conseiller dédié étudie votre demande et vous répond sous 24 à 48h.</p>
    </div>
</section>

<div class="vs08-dv-wrap">
    <div class="vs08-dv-inner">
        <div class="vs08-dv-grid">
            <a class="vs08-dv-card" href="<?php echo esc_url($golf_url); ?>">
                <div class="vs08-dv-card-ic" aria-hidden="true">⛳</div>
                <h2>Séjour golf</h2>
                <p>Parcours, green-fees, hôtel, vols, groupe ou couple : le formulaire pensé pour les golfeurs.</p>
                <span class="cta">Accéder au formulaire →</span>
            </a>
            <a class="vs08-dv-card" href="<?php echo esc_url($sejour_url); ?>">
                <div class="vs08-dv-card-ic" aria-hidden="true">🌴</div>
                <h2>Séjour vacances</h2>
                <p>Plage, soleil, famille, tout compris ou à la carte : décrivez l’esprit de vos prochaines vacances.</p>
                <span class="cta">Demander un devis →</span>
            </a>
            <a class="vs08-dv-card" href="<?php echo esc_url($city_url); ?>">
                <div class="vs08-dv-card-ic" aria-hidden="true">🏙️</div>
                <h2>City trip</h2>
                <p>Week-end ou escapade urbaine : culture, shopping, gastronomie et rythme sur mesure.</p>
                <span class="cta">Demander un devis →</span>
            </a>
            <a class="vs08-dv-card" href="<?php echo esc_url($road_url); ?>">
                <div class="vs08-dv-card-ic" aria-hidden="true">🚗</div>
                <h2>Road trip</h2>
                <p>Itinéraire libre, étapes, location de véhicule : nous montons le parcours avec vous.</p>
                <span class="cta">Demander un devis →</span>
            </a>
            <a class="vs08-dv-card" href="<?php echo esc_url($circuit_url); ?>">
                <div class="vs08-dv-card-ic" aria-hidden="true">🗺️</div>
                <h2>Circuit</h2>
                <p>Découverte guidée ou autonome, plusieurs destinations : votre circuit clé en main.</p>
                <span class="cta">Demander un devis →</span>
            </a>
        </div>
        <p class="vs08-dv-foot">Préférez-vous échanger de vive voix ? <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', vs08_opt('vs08_tel', '0326652863'))); ?>"><?php echo esc_html(vs08_opt('vs08_tel', '03 26 65 28 63')); ?></a> · <a href="<?php echo esc_url(home_url('/contact/')); ?>">Page contact</a></p>
    </div>
</div>

<?php get_footer(); ?>
