<?php
/**
 * Template Name: Contact
 * Slug attendu : contact
 */
get_header();
$tel = vs08_opt('vs08_tel', '03 26 65 28 63');
$tel_raw = preg_replace('/\s+/', '', $tel);
$email = 'contact@sortirmonde.fr';
?>
<style>
.contact-wrap{background:#f9f6f0;padding:120px 0 80px}.contact-inner{max-width:800px;margin:0 auto;padding:0 30px}
.contact-inner h1{font-family:'Playfair Display',serif;font-size:clamp(28px,4vw,42px);color:#0f2424;margin-bottom:12px}
.contact-grid{display:grid;grid-template-columns:1fr 1fr;gap:32px;margin-top:32px}
.contact-card{background:#fff;border-radius:18px;padding:32px;box-shadow:0 4px 24px rgba(0,0,0,.06)}
.contact-card h3{font-size:16px;color:#59b7b7;margin-bottom:16px;text-transform:uppercase;letter-spacing:.08em}
.contact-card p,.contact-card a{font-size:15px;color:#4a5568;line-height:1.7}
.contact-card a{color:#0f2424;font-weight:600;text-decoration:none}
.contact-card a:hover{color:#59b7b7}
.contact-cta{margin-top:40px;text-align:center}
.contact-cta a{display:inline-block;background:#0f2424;color:#fff;padding:16px 32px;border-radius:100px;font-weight:700;text-decoration:none}
.contact-cta a:hover{background:#59b7b7;color:#fff}
@media(max-width:640px){.contact-grid{grid-template-columns:1fr}}
</style>
<div class="contact-wrap"><div class="contact-inner">
<h1>Nous contacter</h1>
<p style="font-size:15px;color:#6b7280">Une question, un devis sur mesure ? Nous sommes à votre écoute.</p>
<div class="contact-grid">
    <div class="contact-card">
        <h3>📞 Téléphone</h3>
        <p><a href="tel:<?php echo esc_attr($tel_raw); ?>"><?php echo esc_html($tel); ?></a><br>Lun — Ven · 9h — 18h</p>
    </div>
    <div class="contact-card">
        <h3>✉️ Email</h3>
        <p><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a><br>Réservations : resa@voyagessortir08.com</p>
    </div>
    <div class="contact-card">
        <h3>📍 Adresse</h3>
        <p>Voyages Sortir 08<br>24 rue Léon Bourgeois<br>51000 Châlons-en-Champagne</p>
    </div>
    <div class="contact-card">
        <h3>⛳ Devis golf</h3>
        <p><a href="<?php echo esc_url(home_url('/devis-golf/')); ?>">Demander un devis séjour golf</a> personnalisé (formulaire dédié).</p>
    </div>
</div>
<div class="contact-cta">
    <a href="tel:<?php echo esc_attr($tel_raw); ?>">Appeler maintenant</a>
    <a href="<?php echo esc_url(home_url('/devis-golf/')); ?>" style="margin-left:12px;background:#59b7b7">Devis golf</a>
</div>
</div>
</div>
<?php get_footer(); ?>
