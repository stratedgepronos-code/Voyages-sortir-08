<?php
/**
 * Template Name: FAQ
 * Slug attendu : faq
 */
get_header();
?>
<style>
.faq-hero{background:#0f2424;padding:140px 0 60px;text-align:center}
.faq-hero h1{font-family:'Playfair Display',serif;font-size:clamp(30px,4vw,44px);color:#fff;margin:0 0 12px}
.faq-hero h1 em{color:#7ecece;font-style:italic}
.faq-hero p{color:rgba(255,255,255,.6);font-family:'Outfit',sans-serif;font-size:15px}
.faq-wrap{background:#f9f6f0;padding:60px 0 80px}
.faq-inner{max-width:820px;margin:0 auto;padding:0 30px}
.faq-cat{margin-bottom:40px}
.faq-cat h2{font-family:'Outfit',sans-serif;font-size:13px;font-weight:700;color:#59b7b7;text-transform:uppercase;letter-spacing:2px;margin:0 0 20px;padding-bottom:12px;border-bottom:2px solid rgba(89,183,183,.15)}
.faq-q{background:#fff;border-radius:16px;padding:22px 24px;margin-bottom:10px;box-shadow:0 2px 16px rgba(0,0,0,.04);cursor:pointer;transition:all .2s}
.faq-q:hover{box-shadow:0 8px 30px rgba(0,0,0,.08);transform:translateY(-2px)}
.faq-q h3{font-size:15px;color:#0f2424;margin:0;display:flex;justify-content:space-between;align-items:center;font-family:'Outfit',sans-serif;font-weight:600}
.faq-q h3::after{content:'+';font-size:20px;color:#59b7b7;font-weight:300;transition:transform .3s;flex-shrink:0;margin-left:12px}
.faq-q.open h3::after{transform:rotate(45deg)}
.faq-q p{font-size:14px;color:#4a5568;line-height:1.75;margin:12px 0 0;display:none;font-family:'Outfit',sans-serif}
.faq-q.open p{display:block}
.faq-inner a{color:#3d9a9a;font-weight:600}
.faq-cta{text-align:center;margin-top:40px;padding:32px;background:#fff;border-radius:20px;box-shadow:0 4px 24px rgba(0,0,0,.06)}
.faq-cta p{font-family:'Outfit',sans-serif;font-size:15px;color:#6b7280;margin:0 0 16px}
.faq-cta a{display:inline-flex;align-items:center;gap:8px;padding:14px 28px;background:#59b7b7;color:#fff;border-radius:100px;font-weight:700;text-decoration:none;font-family:'Outfit',sans-serif;font-size:14px;transition:all .25s}
.faq-cta a:hover{background:#3d9a9a;transform:translateY(-2px)}
@media(max-width:640px){
    .faq-hero{padding:110px 20px 40px}
    .faq-inner{padding:0 16px}
    .faq-q{padding:16px 18px;border-radius:12px}
    .faq-q h3{font-size:14px}
    .faq-cta{padding:20px;margin-top:24px}
}
</style>

<section class="faq-hero">
    <h1>Questions <em>fr&eacute;quentes</em></h1>
    <p>Tout ce que vous devez savoir avant de r&eacute;server votre voyage.</p>
</section>

<div class="faq-wrap"><div class="faq-inner">

<div class="faq-cat">
    <h2>R&eacute;servation &amp; paiement</h2>
    <div class="faq-q"><h3>Comment r&eacute;server un voyage ?</h3><p>Choisissez votre s&eacute;jour (golf, circuit, all inclusive) sur le site, personnalisez les dates et le nombre de voyageurs, puis validez en payant l'acompte en ligne. Vous pouvez aussi nous contacter par t&eacute;l&eacute;phone ou <a href="<?php echo esc_url(home_url('/devis-gratuit/')); ?>">demander un devis gratuit</a>.</p></div>
    <div class="faq-q"><h3>Quel est le montant de l'acompte ?</h3><p>L'acompte est de 30 % minimum du prix total (il ne peut pas &ecirc;tre inf&eacute;rieur au co&ucirc;t des billets d'avion). Le solde est d&ucirc; 30 jours avant le d&eacute;part.</p></div>
    <div class="faq-q"><h3>Quels moyens de paiement acceptez-vous ?</h3><p>Carte bancaire (Visa, Mastercard) via notre syst&egrave;me 3D Secure Paybox, ch&egrave;que, ou virement. Vous pouvez aussi payer en agence &agrave; Ch&acirc;lons-en-Champagne.</p></div>
    <div class="faq-q"><h3>Le paiement en ligne est-il s&eacute;curis&eacute; ?</h3><p>Oui, nous utilisons le syst&egrave;me de paiement Paybox (V&eacute;rifone) avec authentification 3D Secure et chiffrement SSL. Vos donn&eacute;es bancaires ne transitent jamais par notre serveur.</p></div>
</div>

<div class="faq-cat">
    <h2>Annulation &amp; modification</h2>
    <div class="faq-q"><h3>Puis-je annuler ma r&eacute;servation ?</h3><p>Les conditions d'annulation sont d&eacute;taill&eacute;es dans nos <a href="<?php echo esc_url(home_url('/conditions-generales/')); ?>">Conditions G&eacute;n&eacute;rales de Vente</a>. Des frais peuvent s'appliquer selon le d&eacute;lai. Nous recommandons de souscrire une <a href="<?php echo esc_url(home_url('/assurances/')); ?>">assurance annulation</a>.</p></div>
    <div class="faq-q"><h3>Puis-je modifier les dates de mon voyage ?</h3><p>Sous r&eacute;serve de disponibilit&eacute;, les modifications de dates sont possibles. Contactez votre conseiller le plus t&ocirc;t possible. Des frais de modification peuvent s'appliquer selon les prestataires (compagnie a&eacute;rienne, h&ocirc;tel).</p></div>
</div>

<div class="faq-cat">
    <h2>Les voyages</h2>
    <div class="faq-q"><h3>Les vols sont-ils inclus ?</h3><p>La plupart de nos s&eacute;jours incluent les vols. Chaque fiche produit pr&eacute;cise clairement si le vol est inclus ou en option. Le prix affich&eacute; correspond au prix par personne avec ou sans vol, c'est indiqu&eacute; sur chaque fiche.</p></div>
    <div class="faq-q"><h3>Quels types de voyages proposez-vous ?</h3><p>S&eacute;jours golf (notre sp&eacute;cialit&eacute;), circuits d&eacute;couverte (Italie, Gr&egrave;ce, Portugal, Tha&iuml;lande...), s&eacute;jours all inclusive, road trips et billets parcs d'attractions &agrave; prix r&eacute;duit.</p></div>
    <div class="faq-q"><h3>Puis-je partir d'un autre a&eacute;roport que Paris ?</h3><p>Oui ! Nous proposons des d&eacute;parts depuis de nombreux a&eacute;roports fran&ccedil;ais : CDG, Orly, Lyon, Marseille, Nantes, Bordeaux, Toulouse, Nice, Lille, Vatry, et m&ecirc;me Bruxelles et Luxembourg.</p></div>
    <div class="faq-q"><h3>Proposez-vous des voyages de groupe ?</h3><p>Oui, nous organisons r&eacute;guli&egrave;rement des voyages pour des groupes de golfeurs, associations ou entreprises. <a href="<?php echo esc_url(home_url('/devis-gratuit/')); ?>">Demandez un devis groupe</a>.</p></div>
</div>

<div class="faq-cat">
    <h2>L'agence</h2>
    <div class="faq-q"><h3>O&ugrave; se trouve votre agence ?</h3><p>24 rue L&eacute;on Bourgeois, 51000 Ch&acirc;lons-en-Champagne. Ouverte du lundi au vendredi (9h&ndash;18h30) et le samedi matin (9h&ndash;12h). Parking gratuit.</p></div>
    <div class="faq-q"><h3>&Ecirc;tes-vous une agence agr&eacute;&eacute;e ?</h3><p>Oui, nous sommes immatricul&eacute;s aupr&egrave;s d'Atout France (IM051100014), garantis par l'APST et assur&eacute;s en responsabilit&eacute; civile par Hiscox.</p></div>
    <div class="faq-q"><h3>Comment vous contacter ?</h3><p>T&eacute;l&eacute;phone : <a href="tel:0326652863">03 26 65 28 63</a> &middot; Email : <a href="mailto:resa@voyagessortir08.com">resa@voyagessortir08.com</a> &middot; <a href="<?php echo esc_url(home_url('/contact/')); ?>">Page contact compl&egrave;te</a>.</p></div>
</div>

<div class="faq-cta">
    <p>Vous n'avez pas trouv&eacute; la r&eacute;ponse &agrave; votre question ?</p>
    <a href="<?php echo esc_url(home_url('/contact/')); ?>">Contactez-nous &rarr;</a>
</div>

</div></div>
<script>document.querySelectorAll('.faq-q').forEach(function(q){q.addEventListener('click',function(){q.classList.toggle('open');});});</script>
<?php get_footer(); ?>
