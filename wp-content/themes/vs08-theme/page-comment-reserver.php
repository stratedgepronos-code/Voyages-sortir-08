<?php
/**
 * Template Name: Comment réserver
 * Slug attendu : comment-reserver
 */
get_header();
?>
<style>
.cr-hero{background:#0f2424;padding:140px 0 60px;text-align:center}
.cr-hero h1{font-family:'Playfair Display',serif;font-size:clamp(30px,4vw,44px);color:#fff;margin:0 0 12px}
.cr-hero h1 em{color:#7ecece;font-style:italic}
.cr-hero p{color:rgba(255,255,255,.6);font-family:'Outfit',sans-serif;font-size:15px}
.cr-wrap{background:#f9f6f0;padding:60px 0 80px}
.cr-inner{max-width:900px;margin:0 auto;padding:0 30px}
.cr-steps{display:flex;flex-direction:column;gap:0;margin-bottom:48px}
.cr-step{display:flex;gap:24px;padding:32px 0;border-bottom:1px solid rgba(89,183,183,.1)}
.cr-step:last-child{border-bottom:none}
.cr-step-num{width:56px;height:56px;border-radius:50%;background:#59b7b7;color:#fff;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;font-family:'Playfair Display',serif;flex-shrink:0}
.cr-step-body h3{font-size:18px;color:#0f2424;margin:0 0 8px;font-family:'Outfit',sans-serif;font-weight:700}
.cr-step-body p{font-size:14px;color:#4a5568;line-height:1.75;margin:0;font-family:'Outfit',sans-serif}
.cr-methods{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:48px}
.cr-method{background:#fff;border-radius:18px;padding:28px;box-shadow:0 4px 24px rgba(0,0,0,.05);text-align:center;transition:transform .3s}
.cr-method:hover{transform:translateY(-4px)}
.cr-method-ic{font-size:36px;margin-bottom:12px}
.cr-method h3{font-size:16px;color:#0f2424;margin:0 0 8px;font-family:'Outfit',sans-serif;font-weight:700}
.cr-method p{font-size:13px;color:#6b7280;line-height:1.6;margin:0;font-family:'Outfit',sans-serif}
.cr-info{background:#fff;border-radius:18px;padding:28px;box-shadow:0 4px 24px rgba(0,0,0,.05);margin-bottom:32px}
.cr-info h3{font-size:14px;color:#59b7b7;text-transform:uppercase;letter-spacing:1px;margin:0 0 16px;font-family:'Outfit',sans-serif;font-weight:700}
.cr-info p{font-size:14px;color:#4a5568;line-height:1.75;margin:0 0 10px;font-family:'Outfit',sans-serif}
.cr-cta{text-align:center;margin-top:32px}
.cr-cta a{display:inline-flex;align-items:center;gap:8px;padding:16px 32px;background:#59b7b7;color:#fff;border-radius:100px;font-weight:700;text-decoration:none;font-family:'Outfit',sans-serif;font-size:15px;transition:all .25s}
.cr-cta a:hover{background:#3d9a9a;transform:translateY(-2px)}
@media(max-width:768px){.cr-step{flex-direction:column;gap:16px}.cr-methods{grid-template-columns:1fr}}
</style>

<section class="cr-hero">
    <h1>Comment <em>r&eacute;server</em> ?</h1>
    <p>3 &eacute;tapes simples pour partir en voyage avec Voyages Sortir 08.</p>
</section>

<div class="cr-wrap"><div class="cr-inner">

<div class="cr-steps">
    <div class="cr-step">
        <div class="cr-step-num">1</div>
        <div class="cr-step-body">
            <h3>Choisissez votre voyage</h3>
            <p>Parcourez nos s&eacute;jours golf, circuits d&eacute;couverte ou s&eacute;jours all inclusive directement sur le site. Vous pouvez filtrer par destination, a&eacute;roport de d&eacute;part, dur&eacute;e et dates. Vous pouvez aussi nous <a href="<?php echo esc_url(home_url('/devis-gratuit/')); ?>" style="color:#59b7b7;font-weight:600">demander un devis sur mesure</a> gratuitement.</p>
        </div>
    </div>
    <div class="cr-step">
        <div class="cr-step-num">2</div>
        <div class="cr-step-body">
            <h3>Personnalisez et r&eacute;servez</h3>
            <p>Sur la fiche s&eacute;jour, choisissez vos dates, le nombre de voyageurs, l'a&eacute;roport de d&eacute;part et les options souhait&eacute;es. Validez en payant l'acompte de 30 % en ligne (paiement 3D Secure). Un email de confirmation vous est envoy&eacute; imm&eacute;diatement.</p>
        </div>
    </div>
    <div class="cr-step">
        <div class="cr-step-num">3</div>
        <div class="cr-step-body">
            <h3>On s'occupe de tout</h3>
            <p>Votre conseiller prend le relais : r&eacute;servation des vols, h&ocirc;tels, activit&eacute;s et transferts. Vous recevez votre carnet de voyage complet (vouchers, billets, contacts) par email avant le d&eacute;part. Le solde est d&ucirc; 30 jours avant le d&eacute;part.</p>
        </div>
    </div>
</div>

<h2 style="font-family:'Playfair Display',serif;font-size:24px;color:#0f2424;margin:0 0 24px;text-align:center">Nos canaux de r&eacute;servation</h2>
<div class="cr-methods">
    <div class="cr-method">
        <div class="cr-method-ic">&#x1f4bb;</div>
        <h3>En ligne</h3>
        <p>Directement sur notre site, 24h/24. Paiement s&eacute;curis&eacute; par carte bancaire.</p>
    </div>
    <div class="cr-method">
        <div class="cr-method-ic">&#x1f4de;</div>
        <h3>Par t&eacute;l&eacute;phone</h3>
        <p><a href="tel:0326652863" style="color:#59b7b7;font-weight:600">03 26 65 28 63</a><br>Lun&ndash;Ven 9h&ndash;18h30</p>
    </div>
    <div class="cr-method">
        <div class="cr-method-ic">&#x1f3e2;</div>
        <h3>En agence</h3>
        <p>24 rue L&eacute;on Bourgeois<br>Ch&acirc;lons-en-Champagne</p>
    </div>
</div>

<div class="cr-info">
    <h3>Paiement &amp; acompte</h3>
    <p><strong>Acompte :</strong> 30 % du prix total &agrave; la r&eacute;servation (minimum = co&ucirc;t des billets d'avion).</p>
    <p><strong>Solde :</strong> D&ucirc; 30 jours avant le d&eacute;part.</p>
    <p><strong>Moyens de paiement :</strong> Carte bancaire (Visa, Mastercard) via 3D Secure Paybox, ch&egrave;que, virement, ou en agence.</p>
    <p><strong>Assurance :</strong> Nous recommandons de souscrire une <a href="<?php echo esc_url(home_url('/assurances/')); ?>" style="color:#59b7b7;font-weight:600">assurance annulation</a> pour voyager l'esprit tranquille.</p>
</div>

<div class="cr-cta">
    <a href="<?php echo esc_url(home_url('/devis-gratuit/')); ?>">Demander un devis gratuit &rarr;</a>
</div>

</div></div>
<?php get_footer(); ?>
