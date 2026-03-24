<?php
/**
 * Template Name: Destinations
 * Slug attendu : destinations
 * Affiche les destinations avec lien vers la recherche.
 */
get_header();
$opts = class_exists('VS08V_Search') ? VS08V_Search::get_aggregated_options() : ['destinations' => []];
$default_imgs = [
    'Portugal' => 'https://images.unsplash.com/photo-1555881400-74d7acaacd8b?w=400&q=80',
    'Espagne'  => 'https://images.unsplash.com/photo-1539020140153-e479b8c22e70?w=400&q=80',
    'Maroc'    => 'https://images.unsplash.com/photo-1553603227-2358aabe821e?w=400&q=80',
    'Thaïlande'=> 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=400&q=80',
    'Irlande'  => 'https://images.unsplash.com/photo-1590523741831-ab7e8b8f9c7f?w=400&q=80',
];
?>
<style>
.dest-wrap{background:#f9f6f0;padding:120px 0 80px}.dest-inner{max-width:1200px;margin:0 auto;padding:0 30px}
.dest-inner h1{font-family:'Playfair Display',serif;font-size:clamp(28px,4vw,42px);color:#0f2424;margin-bottom:12px}
.dest-inner > p{font-size:15px;color:#6b7280;margin-bottom:40px}
.dest-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:20px}
.dest-card{border-radius:18px;overflow:hidden;aspect-ratio:3/4;position:relative;display:block;transition:transform .35s}
.dest-card:hover{transform:translateY(-6px)}
.dest-card img{width:100%;height:100%;object-fit:cover}
.dest-card-overlay{position:absolute;inset:0;background:linear-gradient(180deg,transparent 40%,rgba(15,36,36,.88) 100%)}
.dest-card-info{position:absolute;bottom:0;left:0;right:0;padding:20px;color:#fff}
.dest-card-info strong{display:block;font-size:18px;margin-bottom:4px}
.dest-card-info span{font-size:12px;opacity:.9}
</style>
<div class="dest-wrap"><div class="dest-inner">
<h1>Nos destinations</h1>
<p>Cliquez sur une destination pour voir les séjours disponibles (tous aéroports).</p>
<div class="dest-grid">
<?php foreach ($opts['destinations'] as $d) :
    $dest_value = $d['value'] ?? '';
    $dest_label = $d['label'] ?? $dest_value;
    $dest_pays = $d['pays'] ?? '';
    $dest_img = !empty($d['image']) ? $d['image'] : ($default_imgs[$dest_pays] ?? 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=400&q=80');
    $dest_count = isset($d['count']) ? (int) $d['count'] : 0;
    $dest_url = home_url('/resultats-recherche?dest=' . rawurlencode($dest_value));
    $dest_name_display = trim(($d['flag'] ?? '') . ' ' . $dest_label);
?>
    <a href="<?php echo esc_url($dest_url); ?>" class="dest-card">
        <img src="<?php echo esc_url($dest_img); ?>" alt="<?php echo esc_attr($dest_label); ?>">
        <div class="dest-card-overlay"></div>
        <div class="dest-card-info">
            <strong><?php echo esc_html($dest_name_display); ?></strong>
            <span><?php echo $dest_count; ?> séjour<?php echo $dest_count > 1 ? 's' : ''; ?></span>
        </div>
    </a>
<?php endforeach; ?>
</div>
<p style="margin-top:32px"><a href="<?php echo esc_url(home_url('/resultats-recherche')); ?>" style="color:#59b7b7;font-weight:600">→ Voir tous les séjours</a></p>
</div>
</div>
<?php get_footer(); ?>
