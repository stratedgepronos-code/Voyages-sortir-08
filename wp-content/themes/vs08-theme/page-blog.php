<?php
/**
 * Template Name: Blog
 * Slug attendu : blog
 */
get_header();
?>
<style>
.cgv-wrap{background:#f9f6f0;padding:120px 0 80px}.cgv-inner{max-width:700px;margin:0 auto;padding:0 30px;text-align:center}
.cgv-inner h1{font-family:'Playfair Display',serif;font-size:clamp(28px,4vw,42px);color:#0f2424;margin-bottom:16px}
.cgv-inner p{font-size:16px;color:#4a5568;line-height:1.8}
.cgv-inner a{color:#59b7b7;font-weight:600}
</style>
<div class="cgv-wrap"><div class="cgv-inner">
<h1>Blog voyage & golf</h1>
<p>Nos actualités et conseils seront bientôt disponibles ici. En attendant, découvrez nos <a href="<?php echo esc_url(home_url('/golf')); ?>">séjours golf</a> et nos <a href="<?php echo esc_url(home_url('/destinations')); ?>">destinations</a>.</p>
<p><a href="<?php echo esc_url(home_url('/')); ?>">← Retour à l'accueil</a></p>
</div>
</div>
<?php get_footer(); ?>
