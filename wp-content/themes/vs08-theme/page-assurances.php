<?php
/**
 * Template Name: Assurances voyage
 * Slug attendu : assurances
 */
get_header();
?>
<style>
.as-hero{background:#0f2424;padding:140px 0 60px;text-align:center}
.as-hero h1{font-family:'Playfair Display',serif;font-size:clamp(30px,4vw,44px);color:#fff;margin:0 0 12px}
.as-hero h1 em{color:#7ecece;font-style:italic}
.as-hero p{color:rgba(255,255,255,.6);font-family:'Outfit',sans-serif;font-size:15px;max-width:600px;margin:0 auto}
.as-wrap{background:#f9f6f0;padding:60px 0 80px}
.as-inner{max-width:1000px;margin:0 auto;padding:0 30px}
.as-intro{font-family:'Outfit',sans-serif;font-size:15px;color:#4a5568;line-height:1.8;margin-bottom:40px;max-width:700px}
.as-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:24px;margin-bottom:48px}
.as-card{background:#fff;border-radius:20px;padding:32px 28px;box-shadow:0 4px 24px rgba(0,0,0,.05);border:1px solid rgba(89,183,183,.08);transition:transform .3s}
.as-card:hover{transform:translateY(-4px)}
.as-card-head{display:flex;align-items:center;gap:14px;margin-bottom:16px}
.as-card-ic{font-size:32px}
.as-card h2{font-family:'Outfit',sans-serif;font-size:18px;color:#0f2424;margin:0;font-weight:700}
.as-card p{font-size:14px;color:#4a5568;line-height:1.75;margin:0 0 12px;font-family:'Outfit',sans-serif}
.as-card ul{margin:0;padding:0;list-style:none}
.as-card ul li{font-size:13px;color:#4a5568;line-height:1.7;padding:4px 0;font-family:'Outfit',sans-serif}
.as-card ul li::before{content:'✓ ';color:#59b7b7;font-weight:700}
.as-card .as-price{display:inline-block;margin-top:14px;background:rgba(89,183,183,.1);color:#3d9a9a;padding:6px 16px;border-radius:100px;font-size:13px;font-weight:700;font-family:'Outfit',sans-serif}
.as-note{background:#fff;border-radius:18px;padding:28px;box-shadow:0 4px 24px rgba(0,0,0,.05);margin-bottom:32px}
.as-note h3{font-size:14px;color:#59b7b7;text-transform:uppercase;letter-spacing:1px;margin:0 0 14px;font-family:'Outfit',sans-serif;font-weight:700}
.as-note p{font-size:14px;color:#4a5568;line-height:1.75;margin:0 0 10px;font-family:'Outfit',sans-serif}
.as-note a{color:#59b7b7;font-weight:600}
.as-cta{text-align:center;margin-top:32px}
.as-cta a{display:inline-flex;align-items:center;gap:8px;padding:14px 28px;border-radius:100px;font-weight:700;text-decoration:none;font-family:'Outfit',sans-serif;font-size:14px;transition:all .25s}
.as-cta .as-btn{background:#59b7b7;color:#fff}
.as-cta .as-btn:hover{background:#3d9a9a;transform:translateY(-2px)}
.as-cta .as-btn-o{background:transparent;color:#0f2424;border:2px solid #0f2424;margin-left:12px}
.as-cta .as-btn-o:hover{background:#0f2424;color:#fff;transform:translateY(-2px)}
@media(max-width:768px){.as-grid{grid-template-columns:1fr}}
</style>

<section class="as-hero">
    <h1>Assurances <em>voyage</em></h1>
    <p>Partez l'esprit tranquille avec nos assurances annulation et assistance, propos&eacute;es lors de votre r&eacute;servation.</p>
</section>

<div class="as-wrap"><div class="as-inner">

<p class="as-intro">Chez Voyages Sortir 08, nous vous recommandons fortement de souscrire une assurance voyage. Celle-ci vous prot&egrave;ge en cas d'annulation, de retard, de perte de bagages ou de probl&egrave;me m&eacute;dical pendant votre s&eacute;jour. L'assurance est propos&eacute;e en option lors de votre r&eacute;servation.</p>

<div class="as-grid">
    <div class="as-card">
        <div class="as-card-head"><div class="as-card-ic">&#x1f6e1;&#xfe0f;</div><h2>Assurance Annulation</h2></div>
        <p>Prot&eacute;gez votre investissement en cas d'emp&ecirc;chement avant le d&eacute;part.</p>
        <ul>
            <li>Maladie, accident, hospitalisation</li>
            <li>D&eacute;c&egrave;s d'un proche</li>
            <li>Licenciement &eacute;conomique</li>
            <li>Convocation juridique impr&eacute;vue</li>
            <li>Refus de visa</li>
            <li>Remboursement jusqu'&agrave; 100 % du voyage</li>
        </ul>
        <span class="as-price">&Agrave; partir de 3,5 % du prix du voyage</span>
    </div>
    <div class="as-card">
        <div class="as-card-head"><div class="as-card-ic">&#x1f6a8;</div><h2>Assurance Multirisques</h2></div>
        <p>Couverture compl&egrave;te : annulation + assistance + bagages.</p>
        <ul>
            <li>Tout ce qui est inclus dans l'annulation</li>
            <li>Frais m&eacute;dicaux &agrave; l'&eacute;tranger</li>
            <li>Rapatriement sanitaire</li>
            <li>Retard ou annulation de vol</li>
            <li>Perte ou vol de bagages</li>
            <li>Responsabilit&eacute; civile &agrave; l'&eacute;tranger</li>
        </ul>
        <span class="as-price">&Agrave; partir de 5 % du prix du voyage</span>
    </div>
</div>

<div class="as-note">
    <h3>&#x1f4cb; Conditions g&eacute;n&eacute;rales des assurances</h3>
    <p>Les conditions compl&egrave;tes (garanties, exclusions, plafonds, d&eacute;lais de carence) sont remises avec votre contrat d'assurance lors de la r&eacute;servation.</p>
    <p>L'assurance doit &ecirc;tre souscrite <strong>au moment de la r&eacute;servation</strong> (en m&ecirc;me temps que l'acompte). Elle ne peut pas &ecirc;tre souscrite apr&egrave;s coup.</p>
    <p>Pour toute question sur les assurances, contactez-nous au <a href="tel:0326652863">03 26 65 28 63</a> ou par email &agrave; <a href="mailto:resa@voyagessortir08.com">resa@voyagessortir08.com</a>.</p>
</div>

<div class="as-note">
    <h3>&#x1f512; Vos garanties l&eacute;gales</h3>
    <p>En plus de l'assurance voyage, Voyages Sortir 08 vous offre des garanties l&eacute;gales solides :</p>
    <p><strong>Garantie APST</strong> &mdash; En cas de d&eacute;faillance de l'agence, l'APST assure le remboursement de vos fonds ou le maintien de votre voyage.</p>
    <p><strong>Immatriculation Atout France</strong> &mdash; N&deg; IM051100014. L'exercice de l'activit&eacute; d'agent de voyages est r&eacute;glement&eacute; par le Code du Tourisme.</p>
    <p><strong>RC Professionnelle Hiscox</strong> &mdash; Responsabilit&eacute; civile professionnelle en cas de litige.</p>
</div>

<div class="as-cta">
    <a href="<?php echo esc_url(home_url('/devis-gratuit/')); ?>" class="as-btn">Demander un devis &rarr;</a>
    <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="as-btn-o">Nous contacter</a>
</div>

</div></div>
<?php get_footer(); ?>
