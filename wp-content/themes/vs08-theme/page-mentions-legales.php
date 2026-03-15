<?php
/**
 * Template Name: Mentions légales
 * Slug attendu : mentions-legales
 */
get_header();
$legal = [
    'editeur' => 'SARL Sortir 08',
    'marque'  => 'Sortir Monde',
    'siege'   => '24 rue Léon Bourgeois, 51000 Châlons-en-Champagne',
    'siret'   => '439 131 640 00021',
    'rcs'     => 'RCS Châlons-en-Champagne',
    'im'      => 'IM051100014',
    'tel'     => '03 26 65 28 63',
    'email'   => 'contact@sortirmonde.fr',
];
?>
<style>
.cgv-wrap{background:#f9f6f0;padding:120px 0 80px}.cgv-inner{max-width:860px;margin:0 auto;padding:0 30px}
.cgv-inner h1{font-family:'Playfair Display',serif;font-size:clamp(28px,4vw,42px);color:#0f2424;margin-bottom:8px}
.cgv-inner .cgv-sub{font-size:14px;color:#6b7280;margin-bottom:36px}
.cgv-inner h2{font-family:'Playfair Display',serif;font-size:20px;color:#0f2424;margin:36px 0 10px;padding-top:20px;border-top:1px solid #e2ddd3}
.cgv-inner p,.cgv-inner li{font-size:14px;color:#4a5568;line-height:1.8}
.cgv-inner ul{padding-left:20px;margin:8px 0}.cgv-inner a{color:#3d9a9a;text-decoration:underline}
.cgv-card{background:#fff;border-radius:18px;padding:36px 40px;box-shadow:0 4px 24px rgba(0,0,0,.06)}
</style>
<div class="cgv-wrap"><div class="cgv-inner">
<h1>Mentions légales</h1>
<p class="cgv-sub">Site sortirmonde.fr — Dernière mise à jour : <?php echo date('F Y'); ?></p>
<div class="cgv-card">
<h2>Éditeur du site</h2>
<p><strong><?php echo esc_html($legal['editeur']); ?></strong>, exerçant sous la marque commerciale <strong><?php echo esc_html($legal['marque']); ?></strong><br>
Siège social : <?php echo esc_html($legal['siege']); ?><br>
SIRET : <?php echo esc_html($legal['siret']); ?> — <?php echo esc_html($legal['rcs']); ?><br>
Immatriculation tourisme : <?php echo esc_html($legal['im']); ?><br>
Téléphone : <a href="tel:0326652863"><?php echo esc_html($legal['tel']); ?></a> — Email : <a href="mailto:<?php echo esc_attr($legal['email']); ?>"><?php echo esc_html($legal['email']); ?></a></p>

<h2>Directeur de la publication</h2>
<p>Le directeur de la publication du site est le représentant légal de la SARL Sortir 08.</p>

<h2>Hébergement</h2>
<p>Le site sortirmonde.fr est hébergé par un prestataire d'hébergement web. Pour toute question relative à l'hébergement, contacter l'éditeur.</p>

<h2>Propriété intellectuelle</h2>
<p>L'ensemble du contenu de ce site (textes, images, logos, structure) est protégé par le droit d'auteur et le droit des marques. Toute reproduction ou utilisation non autorisée est interdite.</p>

<h2>Liens hypertextes</h2>
<p>Les liens vers des sites externes ne engagent pas la responsabilité de Sortir 08. La création de liens vers ce site est autorisée sous réserve de ne pas porter atteinte à l'image de l'éditeur.</p>

<h2>Données personnelles</h2>
<p>Pour l'utilisation de vos données personnelles et vos droits (RGPD), consultez notre <a href="<?php echo esc_url(home_url('/rgpd/')); ?>">politique de confidentialité</a>.</p>
</div>
</div>
</div>
<?php get_footer(); ?>
