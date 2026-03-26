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
?>
<style>
.vs08-contact-hero{position:relative;min-height:52vh;display:flex;align-items:flex-end;padding:140px 80px 72px;overflow:hidden}
.vs08-contact-hero-bg{position:absolute;inset:0;background-size:cover;background-position:center}
.vs08-contact-hero-bg::after{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(15,36,36,.92) 0%,rgba(26,58,58,.75) 45%,rgba(89,183,183,.35) 100%)}
.vs08-contact-hero-inner{position:relative;z-index:2;max-width:900px}
.vs08-contact-hero-inner h1{font-family:'Playfair Display',serif;font-size:clamp(34px,5vw,52px);color:#fff;margin:0 0 14px;line-height:1.1}
.vs08-contact-hero-inner h1 em{color:#7ecece;font-style:italic}
.vs08-contact-hero-inner p{font-size:17px;color:rgba(255,255,255,.78);line-height:1.65;font-family:'Outfit',sans-serif;font-weight:300;max-width:520px;margin:0}
.vs08-contact-hero-badge{display:inline-block;font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#7ecece;margin-bottom:16px;font-family:'Outfit',sans-serif}
.contact-wrap{background:#f9f6f0;padding:72px 0 96px;margin-top:-1px}
.contact-inner{max-width:920px;margin:0 auto;padding:0 30px}
.contact-intro{font-size:15px;color:#6b7280;margin-bottom:36px;font-family:'Outfit',sans-serif;line-height:1.65}
.contact-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:24px}
.contact-card{background:#fff;border-radius:22px;padding:32px 28px;box-shadow:0 8px 40px rgba(15,36,36,.08);border:1px solid rgba(89,183,183,.12);transition:transform .3s,box-shadow .3s}
.contact-card:hover{transform:translateY(-4px);box-shadow:0 20px 50px rgba(15,36,36,.12)}
.contact-card h3{font-size:12px;color:#59b7b7;margin:0 0 14px;text-transform:uppercase;letter-spacing:.12em;font-family:'Outfit',sans-serif;font-weight:700}
.contact-card p,.contact-card a{font-size:16px;color:#374151;line-height:1.75;font-family:'Outfit',sans-serif;margin:0}
.contact-card a{color:#0f2424;font-weight:600;text-decoration:none;word-break:break-word}
.contact-card a:hover{color:#59b7b7}
.contact-card .contact-mail-big{font-size:clamp(17px,2.5vw,20px);font-weight:700;color:#0f2424}
.contact-cta{margin-top:44px;text-align:center;display:flex;flex-wrap:wrap;gap:14px;justify-content:center}
.contact-cta a{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:#0f2424;color:#fff;padding:16px 32px;border-radius:100px;font-weight:700;text-decoration:none;font-family:'Outfit',sans-serif;font-size:15px;transition:background .25s,transform .2s}
.contact-cta a:hover{background:#59b7b7;color:#fff;transform:translateY(-2px)}
.contact-cta a.vs08-cta-teal{background:#59b7b7}
.contact-cta a.vs08-cta-teal:hover{background:#3d9a9a}
@media(max-width:768px){.vs08-contact-hero{padding:120px 24px 48px}.contact-grid{grid-template-columns:1fr}}
</style>

<section class="vs08-contact-hero" aria-label="En-tête contact">
    <div class="vs08-contact-hero-bg" style="background-image:url('<?php echo esc_url($hero_img); ?>')"></div>
    <div class="vs08-contact-hero-inner">
        <span class="vs08-contact-hero-badge">Voyages Sortir 08</span>
        <h1>Nous <em>contacter</em></h1>
        <p>Une question sur un séjour, une réservation ou un projet sur mesure : notre équipe vous répond avec le même soin que pour vos vacances.</p>
    </div>
</section>

<div class="contact-wrap">
    <div class="contact-inner">
        <p class="contact-intro">Choisissez le canal qui vous convient. Pour une demande de tarif détaillée, passez aussi par notre page <a href="<?php echo esc_url($devis_hub); ?>" style="color:#59b7b7;font-weight:600">Devis gratuit</a>.</p>
        <div class="contact-grid">
            <div class="contact-card">
                <h3>📞 Téléphone</h3>
                <p><a href="tel:<?php echo esc_attr($tel_raw); ?>"><?php echo esc_html($tel); ?></a><br><span style="color:#6b7280;font-size:14px">Lun — Ven · 9h — 18h</span></p>
            </div>
            <div class="contact-card">
                <h3>✉️ Email</h3>
                <p><a class="contact-mail-big" href="mailto:<?php echo esc_attr($email_resa); ?>"><?php echo esc_html($email_resa); ?></a></p>
            </div>
            <div class="contact-card">
                <h3>📍 Adresse</h3>
                <p>Voyages Sortir 08<br>24 rue Léon Bourgeois<br>51000 Châlons-en-Champagne</p>
            </div>
            <div class="contact-card">
                <h3>⛳ Devis golf</h3>
                <p><a href="<?php echo esc_url(home_url('/devis-golf/')); ?>">Formulaire dédié séjour golf</a> — réponse sous 24h.</p>
            </div>
        </div>
        <div class="contact-cta">
            <a href="tel:<?php echo esc_attr($tel_raw); ?>">📞 Appeler maintenant</a>
            <a href="<?php echo esc_url($devis_hub); ?>" class="vs08-cta-teal">📋 Devis gratuit</a>
        </div>
    </div>
</div>
<?php get_footer(); ?>
