<?php
/**
 * Template Name: Qui sommes-nous
 * Slug attendu : qui-sommes-nous
 */
get_header();
?>
<style>
.qsn-hero{position:relative;min-height:50vh;display:flex;align-items:flex-end;padding:140px 80px 64px;overflow:hidden}
.qsn-hero-bg{position:absolute;inset:0;background:url('https://images.unsplash.com/photo-1530521954074-e64f6810b32d?w=1920&q=80') center/cover}
.qsn-hero-bg::after{content:'';position:absolute;inset:0;background:linear-gradient(160deg,rgba(15,36,36,.94) 0%,rgba(26,58,58,.78) 50%,rgba(89,183,183,.2) 100%)}
.qsn-hero-z{position:relative;z-index:2;max-width:800px}
.qsn-hero-z h1{font-family:'Playfair Display',serif;font-size:clamp(34px,5vw,52px);color:#fff;margin:0 0 14px;line-height:1.1}
.qsn-hero-z h1 em{color:#7ecece;font-style:italic}
.qsn-hero-z p{font-size:17px;color:rgba(255,255,255,.75);line-height:1.65;font-family:'Outfit',sans-serif;font-weight:300;max-width:560px;margin:0}
.qsn-badge{display:inline-block;font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#c9a84c;font-family:'Outfit',sans-serif;margin-bottom:16px}
.qsn-wrap{background:#f9f6f0;padding:64px 0 80px}
.qsn-inner{max-width:900px;margin:0 auto;padding:0 30px}
.qsn-section{margin-bottom:48px}
.qsn-section h2{font-family:'Playfair Display',serif;font-size:26px;color:#0f2424;margin:0 0 16px}
.qsn-section p{font-family:'Outfit',sans-serif;font-size:15px;color:#4a5568;line-height:1.8;margin:0 0 14px}
.qsn-values{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-top:32px}
.qsn-val{background:#fff;border-radius:18px;padding:28px 24px;box-shadow:0 4px 24px rgba(0,0,0,.05);text-align:center;transition:transform .3s}
.qsn-val:hover{transform:translateY(-4px)}
.qsn-val-ic{font-size:32px;margin-bottom:12px}
.qsn-val h3{font-size:16px;color:#0f2424;margin:0 0 8px;font-family:'Outfit',sans-serif;font-weight:700}
.qsn-val p{font-size:13px;color:#6b7280;line-height:1.6;margin:0;font-family:'Outfit',sans-serif}
.qsn-stats{display:flex;gap:24px;margin:32px 0;flex-wrap:wrap}
.qsn-stat{background:#0f2424;border-radius:16px;padding:24px 32px;flex:1;min-width:140px;text-align:center}
.qsn-stat b{display:block;font-size:32px;color:#7ecece;font-family:'Playfair Display',serif}
.qsn-stat span{display:block;font-size:11px;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:1px;margin-top:6px;font-family:'Outfit',sans-serif}
.qsn-legal{background:#fff;border-radius:18px;padding:28px;box-shadow:0 4px 24px rgba(0,0,0,.05)}
.qsn-legal h3{font-size:14px;color:#59b7b7;text-transform:uppercase;letter-spacing:1px;margin:0 0 14px;font-family:'Outfit',sans-serif;font-weight:700}
.qsn-legal p{font-size:13px;color:#6b7280;line-height:1.7;margin:0 0 6px;font-family:'Outfit',sans-serif}
@media(max-width:768px){.qsn-hero{padding:120px 24px 48px}.qsn-values{grid-template-columns:1fr}.qsn-stats{flex-direction:column}}
</style>

<section class="qsn-hero">
    <div class="qsn-hero-bg"></div>
    <div class="qsn-hero-z">
        <span class="qsn-badge">Depuis 2016 &agrave; Ch&acirc;lons-en-Champagne</span>
        <h1>Voyages <em>Sortir 08</em></h1>
        <p>Une agence de voyages ind&eacute;pendante, passionn&eacute;e et engag&eacute;e. On n&eacute;gocie les meilleurs tarifs pour que vous partiez plus souvent, plus loin, et mieux.</p>
    </div>
</section>

<div class="qsn-wrap"><div class="qsn-inner">

<div class="qsn-section">
    <h2>Notre histoire</h2>
    <p>Fond&eacute;e en 2016, Voyages Sortir 08 est une agence de voyages implant&eacute;e au c&oelig;ur de Ch&acirc;lons-en-Champagne. D&egrave;s le d&eacute;part, notre ambition &eacute;tait simple : proposer des voyages de qualit&eacute; &agrave; des prix n&eacute;goci&eacute;s, avec un service humain et personnalis&eacute;.</p>
    <p>Sp&eacute;cialistes des s&eacute;jours golf, nous avons &eacute;largi notre offre aux circuits d&eacute;couverte, aux s&eacute;jours all inclusive et aux billets de parcs d'attractions. Aujourd'hui, nous accompagnons des centaines de voyageurs chaque ann&eacute;e vers plus de 18 destinations dans le monde.</p>
    <p>Notre slogan <strong>&laquo; Libre &agrave; vous de payer plus cher ! &raquo;</strong> r&eacute;sume notre philosophie : nous n&eacute;gocions au plus juste pour vous offrir le meilleur rapport qualit&eacute;-prix du march&eacute;.</p>
</div>

<div class="qsn-stats">
    <div class="qsn-stat"><b>2016</b><span>Cr&eacute;ation</span></div>
    <div class="qsn-stat"><b>18+</b><span>Pays</span></div>
    <div class="qsn-stat"><b>500+</b><span>Voyageurs/an</span></div>
    <div class="qsn-stat"><b>4.8★</b><span>Google</span></div>
</div>

<div class="qsn-section">
    <h2>Nos valeurs</h2>
    <div class="qsn-values">
        <div class="qsn-val"><div class="qsn-val-ic">🤝</div><h3>Proximit&eacute;</h3><p>Un interlocuteur unique qui vous conna&icirc;t, vous conseille et vous suit du devis au retour.</p></div>
        <div class="qsn-val"><div class="qsn-val-ic">💰</div><h3>Meilleur prix</h3><p>On n&eacute;gocie directement avec les h&ocirc;tels, compagnies et r&eacute;ceptifs pour casser les prix.</p></div>
        <div class="qsn-val"><div class="qsn-val-ic">🔒</div><h3>S&eacute;curit&eacute;</h3><p>Garantie APST, immatriculation Atout France, assurance Hiscox. Vous partez l'esprit tranquille.</p></div>
    </div>
</div>

<div class="qsn-section">
    <h2>Ce que nous proposons</h2>
    <p>&#x26f3; <strong>S&eacute;jours Golf</strong> &mdash; Notre sp&eacute;cialit&eacute;. Portugal, Espagne, Maroc, Turquie, Tha&iuml;lande... Vols, h&ocirc;tel, green fees et transferts inclus.</p>
    <p>&#x1f5fa;&#xfe0f; <strong>Circuits d&eacute;couverte</strong> &mdash; Italie, Gr&egrave;ce, Portugal, Tha&iuml;lande, Costa Rica, Mexique, Sri Lanka, Oman... Guid&eacute;s ou en libert&eacute;.</p>
    <p>&#x2600;&#xfe0f; <strong>S&eacute;jours All Inclusive</strong> &mdash; Soleil, plage et farniente dans les meilleurs h&ocirc;tels-clubs (bient&ocirc;t en ligne).</p>
    <p>&#x1f3a2; <strong>Billets Parcs</strong> &mdash; Disneyland Paris, Parc Ast&eacute;rix, Europa-Park... &agrave; prix r&eacute;duit (bient&ocirc;t en ligne).</p>
</div>

<div class="qsn-legal">
    <h3>Informations l&eacute;gales</h3>
    <p><strong>Raison sociale :</strong> SORTIR 08 &mdash; SIREN 439 131 640</p>
    <p><strong>Adresse :</strong> 24 rue L&eacute;on Bourgeois, 51000 Ch&acirc;lons-en-Champagne</p>
    <p><strong>T&eacute;l&eacute;phone :</strong> <a href="tel:0326652863" style="color:#59b7b7">03 26 65 28 63</a></p>
    <p><strong>Immatriculation :</strong> Atout France IM051100014</p>
    <p><strong>Garantie financi&egrave;re :</strong> APST &mdash; Association Professionnelle de Solidarit&eacute; du Tourisme</p>
    <p><strong>Assurance RC Pro :</strong> Hiscox</p>
</div>

</div></div>
<?php get_footer(); ?>
