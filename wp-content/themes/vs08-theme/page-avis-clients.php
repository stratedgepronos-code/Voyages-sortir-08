<?php
/**
 * Template Name: Avis clients
 * Slug attendu : avis-clients
 */
get_header();
$reviews = get_option('vs08v_google_reviews', []);
if (!is_array($reviews) || empty($reviews)) {
    $reviews = [
        ['name' => 'Michel R.', 'trip' => 'Portugal Algarve — Oct. 2024', 'text' => 'Séjour parfait au Portugal. Parcours magnifiques, hôtel de rêve. L\'équipe a tout pensé, on n\'avait qu\'à jouer. On repart l\'an prochain !'],
        ['name' => 'Sophie L.', 'trip' => 'Maroc Agadir — Fév. 2025', 'text' => 'Premier voyage golf en agence et je ne m\'en passerai plus. Prix vraiment transparent. Le conseiller était disponible même depuis le Maroc.'],
        ['name' => 'Jean-Pierre V.', 'trip' => 'Espagne Marbella — Avr. 2025', 'text' => 'On était 4 amis golfeurs, tout était parfaitement coordonné. Tee-times, transferts, dîner de groupe... Un vrai service premium à prix honnête.'],
    ];
}
?>
<style>
.cgv-wrap{background:#f9f6f0;padding:120px 0 80px}.cgv-inner{max-width:860px;margin:0 auto;padding:0 30px}
.cgv-inner h1{font-family:'Playfair Display',serif;font-size:clamp(28px,4vw,42px);color:#0f2424;margin-bottom:12px}
.cgv-inner p.cgv-sub{font-size:14px;color:#6b7280;margin-bottom:32px}
.avis-card{background:#fff;border-radius:16px;padding:28px 32px;margin-bottom:20px;box-shadow:0 4px 20px rgba(0,0,0,.06);border-left:4px solid #59b7b7}
.avis-card .stars{color:#c9a84c;font-size:14px;margin-bottom:8px}
.avis-card .author{font-weight:700;color:#0f2424;margin-bottom:4px}
.avis-card .trip{font-size:12px;color:#6b7280;margin-bottom:12px}
.avis-card .text{font-size:14px;color:#4a5568;line-height:1.75}
</style>
<div class="cgv-wrap"><div class="cgv-inner">
<h1>Avis clients</h1>
<p class="cgv-sub">Ils sont partis avec nous et nous font confiance. Avis 5 étoiles (Google, Facebook).</p>
<?php foreach ($reviews as $r) : ?>
<div class="avis-card">
    <div class="stars">★★★★★</div>
    <div class="author"><?php echo esc_html($r['name'] ?? ''); ?></div>
    <div class="trip"><?php echo esc_html($r['trip'] ?? ''); ?></div>
    <p class="text"><?php echo esc_html($r['text'] ?? ''); ?></p>
</div>
<?php endforeach; ?>
<p style="margin-top:32px"><a href="<?php echo esc_url(home_url('/')); ?>#testi-grid" style="color:#3d9a9a;font-weight:600">← Retour à l'accueil (carousel avis)</a></p>
</div>
</div>
<?php get_footer(); ?>
