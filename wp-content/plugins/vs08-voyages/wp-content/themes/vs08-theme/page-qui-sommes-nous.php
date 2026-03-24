<?php
/**
 * Template Name: Qui sommes-nous
 * Slug attendu : qui-sommes-nous
 */
get_header();
?>
<style>
.cgv-wrap{background:#f9f6f0;padding:120px 0 80px}.cgv-inner{max-width:860px;margin:0 auto;padding:0 30px}
.cgv-inner h1{font-family:'Playfair Display',serif;font-size:clamp(28px,4vw,42px);color:#0f2424;margin-bottom:16px}
.cgv-inner p,.cgv-inner li{font-size:15px;color:#4a5568;line-height:1.85}
.cgv-inner h2{font-family:'Playfair Display',serif;font-size:22px;color:#0f2424;margin:32px 0 12px;padding-top:24px;border-top:1px solid #e2ddd3}
.cgv-card{background:#fff;border-radius:18px;padding:40px 44px;box-shadow:0 4px 24px rgba(0,0,0,.06)}
.cgv-inner a{color:#3d9a9a;font-weight:600}
</style>
<div class="cgv-wrap"><div class="cgv-inner">
<h1>Qui sommes-nous</h1>
<div class="cgv-card">
<p><strong>Voyages Sortir 08</strong> (marque commerciale <strong>Sortir Monde</strong>) est une agence de voyages implantée à Châlons-en-Champagne, spécialisée dans les <strong>séjours golf</strong> et les circuits découverte depuis plus de 20 ans.</p>

<h2>Notre métier</h2>
<p>Nous concevons et commercialisons des forfaits touristiques tout compris : vols, hébergement, green fees, transferts ou location de voiture. Notre équipe de passionnés sélectionne pour vous les plus beaux parcours et hôtels partenaires en Europe, au Maroc, en Thaïlande et au-delà.</p>

<h2>Nos engagements</h2>
<ul>
<li>Prix transparents : le prix affiché est le prix payé</li>
<li>Un conseiller dédié avant, pendant et après votre voyage</li>
<li>Garantie financière APST et assurance RC professionnelle Hiscox</li>
<li>Immatriculation Atout France (IM051100014)</li>
</ul>

<h2>Nous contacter</h2>
<p>Voyages Sortir 08 — 24 rue Léon Bourgeois, 51000 Châlons-en-Champagne<br>
Téléphone : <a href="tel:0326652863">03 26 65 28 63</a> — Email : <a href="mailto:contact@sortirmonde.fr">contact@sortirmonde.fr</a><br>
<a href="<?php echo esc_url(home_url('/contact/')); ?>">→ Page contact</a></p>
</div>
</div>
</div>
<?php get_footer(); ?>
