<?php
/**
 * Template Name: Contact
 * Slug attendu : contact
 */
get_header();
$tel = vs08_opt('vs08_tel', '03 26 65 28 63');
$tel_raw = preg_replace('/\s+/', '', $tel);
$email_resa = 'resa@voyagessortir08.com';
$hero_img = vs08_opt('vs08_hero_img', 'https://images.unsplash.com/photo-1526772662000-3f88f10405ff?w=1920&q=80');
$devis_hub = home_url('/devis-gratuit/');
$wa_num = '33326652863';
?>
<style>
.ct-hero{position:relative;min-height:50vh;display:flex;align-items:flex-end;padding:140px 80px 64px;overflow:hidden}
.ct-hero-bg{position:absolute;inset:0;background-size:cover;background-position:center}
.ct-hero-bg::after{content:'';position:absolute;inset:0;background:linear-gradient(160deg,rgba(15,36,36,.94) 0%,rgba(26,58,58,.78) 50%,rgba(89,183,183,.25) 100%)}
.ct-hero-z{position:relative;z-index:2;max-width:900px}
.ct-hero-z h1{font-family:'Playfair Display',serif;font-size:clamp(34px,5vw,52px);color:#fff;margin:0 0 14px;line-height:1.1}
.ct-hero-z h1 em{color:#7ecece;font-style:italic}
.ct-hero-z p{font-size:17px;color:rgba(255,255,255,.78);line-height:1.65;font-family:'Outfit',sans-serif;font-weight:300;max-width:560px;margin:0}
.ct-hero-badge{display:inline-block;font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#c9a84c;font-family:'Outfit',sans-serif;margin-bottom:16px}
.ct-wrap{background:#f9f6f0;padding:64px 0 96px;margin-top:-1px}
.ct-inner{max-width:1100px;margin:0 auto;padding:0 30px}
.ct-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:40px}
.ct-card{background:#fff;border-radius:22px;padding:28px 24px;box-shadow:0 8px 36px rgba(15,36,36,.06);border:1px solid rgba(89,183,183,.1);transition:transform .3s,box-shadow .3s}
.ct-card:hover{transform:translateY(-4px);box-shadow:0 20px 50px rgba(15,36,36,.1)}
.ct-card-icon{font-size:32px;margin-bottom:12px}
.ct-card h3{font-size:11px;color:#59b7b7;margin:0 0 10px;text-transform:uppercase;letter-spacing:.12em;font-family:'Outfit',sans-serif;font-weight:700}
.ct-card p{font-size:15px;color:#374151;line-height:1.7;font-family:'Outfit',sans-serif;margin:0}
.ct-card a{color:#0f2424;font-weight:600;text-decoration:none;transition:color .2s}
.ct-card a:hover{color:#59b7b7}
.ct-card .ct-big{font-size:clamp(17px,2.5vw,20px);font-weight:700;color:#0f2424}
.ct-card .ct-small{color:#6b7280;font-size:13px;display:block;margin-top:4px}
.ct-duo{display:grid;grid-template-columns:1.4fr 1fr;gap:24px;margin-bottom:40px}
.ct-map{border-radius:22px;overflow:hidden;box-shadow:0 8px 36px rgba(15,36,36,.08);min-height:320px}
.ct-map iframe{width:100%;height:100%;border:0;display:block;min-height:320px}
.ct-hours{background:#0f2424;border-radius:22px;padding:36px 32px;color:#fff;display:flex;flex-direction:column;justify-content:center}
.ct-hours h3{font-size:11px;color:#7ecece;text-transform:uppercase;letter-spacing:2px;margin:0 0 24px;font-family:'Outfit',sans-serif;font-weight:700}
.ct-hours-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.08);font-family:'Outfit',sans-serif;font-size:14px}
.ct-hours-row:last-of-type{border-bottom:none}
.ct-hours-row span:first-child{color:rgba(255,255,255,.6)}
.ct-hours-row span:last-child{color:#fff;font-weight:600}
.ct-hours-row.ct-closed span:last-child{color:rgba(255,255,255,.3)}
.ct-hours-note{margin-top:20px;font-size:12px;color:rgba(255,255,255,.4);font-family:'Outfit',sans-serif;line-height:1.5}
.ct-cta{display:flex;flex-wrap:wrap;gap:14px;justify-content:center;margin-top:8px}
.ct-cta a{display:inline-flex;align-items:center;gap:8px;padding:16px 30px;border-radius:100px;font-weight:700;text-decoration:none;font-family:'Outfit',sans-serif;font-size:15px;transition:all .25s}
.ct-cta .ct-btn-dark{background:#0f2424;color:#fff}
.ct-cta .ct-btn-dark:hover{background:#1a3a3a;transform:translateY(-2px)}
.ct-cta .ct-btn-teal{background:#59b7b7;color:#fff}
.ct-cta .ct-btn-teal:hover{background:#3d9a9a;transform:translateY(-2px)}
.ct-cta .ct-btn-wa{background:#25D366;color:#fff}
.ct-cta .ct-btn-wa:hover{background:#1fba59;transform:translateY(-2px)}
@media(max-width:900px){.ct-hero{padding:120px 24px 48px}.ct-grid{grid-template-columns:1fr}.ct-duo{grid-template-columns:1fr}.ct-map iframe{min-height:260px}}
</style>

<section class="ct-hero">
    <div class="ct-hero-bg" style="background-image:url('<?php echo esc_url($hero_img); ?>')"></div>
    <div class="ct-hero-z">
        <span class="ct-hero-badge">Agence ouverte du lundi au samedi</span>
        <h1>Nous <em>contacter</em></h1>
        <p>Une question, un projet de voyage, une r&eacute;servation en cours : notre &eacute;quipe vous r&eacute;pond avec le m&ecirc;me soin que pour vos vacances.</p>
    </div>
</section>

<div class="ct-wrap">
    <div class="ct-inner">
        <div class="ct-grid">
            <div class="ct-card">
                <div class="ct-card-icon">&#x1f4de;</div>
                <h3>T&eacute;l&eacute;phone</h3>
                <p><a class="ct-big" href="tel:<?php echo esc_attr($tel_raw); ?>"><?php echo esc_html($tel); ?></a>
                <span class="ct-small">Lun &mdash; Ven &middot; 9h &mdash; 18h30 | Sam &middot; 9h &mdash; 12h</span></p>
            </div>
            <div class="ct-card">
                <div class="ct-card-icon">&#x2709;&#xfe0f;</div>
                <h3>Email</h3>
                <p><a class="ct-big" href="mailto:<?php echo esc_attr($email_resa); ?>"><?php echo esc_html($email_resa); ?></a>
                <span class="ct-small">R&eacute;ponse sous 24h ouvr&eacute;es</span></p>
            </div>
            <div class="ct-card">
                <div class="ct-card-icon">&#x1f4cd;</div>
                <h3>En agence</h3>
                <p><strong>Voyages Sortir 08</strong><br>24 rue L&eacute;on Bourgeois<br>51000 Ch&acirc;lons-en-Champagne
                <span class="ct-small">Acc&egrave;s libre &middot; Parking gratuit</span></p>
            </div>
        </div>

        <div class="ct-duo">
            <div class="ct-map">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2618.7!2d4.3618!3d48.9566!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47e97780f6fc0001%3A0x1!2s24+Rue+L%C3%A9on+Bourgeois%2C+51000+Ch%C3%A2lons-en-Champagne!5e0!3m2!1sfr!2sfr!4v1" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Voyages Sortir 08"></iframe>
            </div>
            <div class="ct-hours">
                <h3>Horaires d'ouverture</h3>
                <div class="ct-hours-row"><span>Lundi</span><span>9h00 — 18h30</span></div>
                <div class="ct-hours-row"><span>Mardi</span><span>9h00 — 18h30</span></div>
                <div class="ct-hours-row"><span>Mercredi</span><span>9h00 — 18h30</span></div>
                <div class="ct-hours-row"><span>Jeudi</span><span>9h00 — 18h30</span></div>
                <div class="ct-hours-row"><span>Vendredi</span><span>9h00 — 18h30</span></div>
                <div class="ct-hours-row"><span>Samedi</span><span>9h00 — 12h00</span></div>
                <div class="ct-hours-row ct-closed"><span>Dimanche</span><span>Fermé</span></div>
                <p class="ct-hours-note">Fermetures exceptionnelles et jours fériés communiqués sur nos réseaux sociaux.</p>
            </div>
        </div>

        <div class="ct-cta">
            <a href="tel:<?php echo esc_attr($tel_raw); ?>" class="ct-btn-dark">📞 Appeler maintenant</a>
            <a href="<?php echo esc_url($devis_hub); ?>" class="ct-btn-teal">📋 Devis gratuit</a>
            <a href="https://wa.me/<?php echo esc_attr($wa_num); ?>?text=<?php echo rawurlencode('Bonjour, je souhaite un renseignement pour un voyage.'); ?>" class="ct-btn-wa" target="_blank" rel="noopener">💬 WhatsApp</a>
        </div>
    </div>
</div>
<?php get_footer(); ?>
