<?php
if (!defined('ABSPATH')) exit;
// Réutilise la même logique que la page shortcode avec les query vars de l’archive
$destination = get_query_var('circuit_destination') ?: (isset($_GET['destination']) ? sanitize_text_field($_GET['destination']) : '');
$theme = get_query_var('circuit_theme') ?: (isset($_GET['theme']) ? sanitize_text_field($_GET['theme']) : '');
$duree = get_query_var('circuit_duree') ?: (isset($_GET['duree']) ? sanitize_text_field($_GET['duree']) : '');
$paged = get_query_var('paged') ?: 1;
$q = VS08C_Search::query([
    'destination' => $destination,
    'theme' => $theme,
    'duree' => $duree,
    'paged' => $paged,
    'per_page' => 12,
]);
// On affiche la même vue que page-circuits en injectant $q et les termes pour les filtres
$destinations = get_terms(['taxonomy' => 'circuit_destination', 'hide_empty' => true]);
$themes = get_terms(['taxonomy' => 'circuit_theme', 'hide_empty' => true]);
$durees = get_terms(['taxonomy' => 'circuit_duree', 'hide_empty' => true]);
$prix_max = isset($_GET['prix_max']) ? floatval($_GET['prix_max']) : 0;
$ordre = isset($_GET['ordre']) ? sanitize_text_field($_GET['ordre']) : 'date';
get_header();
?>
<div class="vs08c-wrap">
    <div class="vs08c-page">
        <header class="vs08c-hero">
            <h1>Nos circuits</h1>
            <p>Voyages organisés par nos soins : itinéraires pensés, hébergements et activités sélectionnés.</p>
        </header>
        <form method="get" class="vs08c-filters">
            <div><label>Destination</label>
                <select name="destination">
                    <option value="">Toutes</option>
                    <?php foreach ($destinations as $t): ?><option value="<?php echo esc_attr($t->slug); ?>" <?php selected($destination, $t->slug); ?>><?php echo esc_html($t->name); ?></option><?php endforeach; ?>
                </select>
            </div>
            <div><label>Thème</label>
                <select name="theme">
                    <option value="">Tous</option>
                    <?php foreach ($themes as $t): ?><option value="<?php echo esc_attr($t->slug); ?>" <?php selected($theme, $t->slug); ?>><?php echo esc_html($t->name); ?></option><?php endforeach; ?>
                </select>
            </div>
            <div><label>Durée</label>
                <select name="duree">
                    <option value="">Toutes</option>
                    <?php foreach ($durees as $t): ?><option value="<?php echo esc_attr($t->slug); ?>" <?php selected($duree, $t->slug); ?>><?php echo esc_html($t->name); ?></option><?php endforeach; ?>
                </select>
            </div>
            <div><label>Trier</label>
                <select name="ordre">
                    <option value="date" <?php selected($ordre, 'date'); ?>>Plus récents</option>
                    <option value="prix_asc" <?php selected($ordre, 'prix_asc'); ?>>Prix croissant</option>
                    <option value="prix_desc" <?php selected($ordre, 'prix_desc'); ?>>Prix décroissant</option>
                    <option value="titre" <?php selected($ordre, 'titre'); ?>>A–Z</option>
                </select>
            </div>
            <button type="submit" class="vs08c-btn">Rechercher</button>
        </form>
        <?php if ($q->have_posts()): ?>
        <div class="vs08c-grid">
            <?php while ($q->have_posts()): $q->the_post();
                $meta = VS08C_MetaBoxes::get(get_the_ID());
                $prix_min = VS08C_Search::get_prix_min_for_circuit($meta);
                $duree_j = (int) ($meta['duree_jours'] ?? 8);
                $dest = get_the_terms(get_the_ID(), 'circuit_destination');
                $dest_name = $dest && !is_wp_error($dest) ? $dest[0]->name : '';
            ?>
            <article class="vs08c-card">
                <a href="<?php the_permalink(); ?>" class="vs08c-card-link">
                    <div class="vs08c-card-img"><?php if (has_post_thumbnail()): the_post_thumbnail('medium_large'); endif; ?></div>
                    <div class="vs08c-card-body">
                        <?php if ($dest_name): ?><div class="vs08c-card-meta"><?php echo esc_html($dest_name); ?></div><?php endif; ?>
                        <h2 class="vs08c-card-title"><?php the_title(); ?></h2>
                        <div class="vs08c-card-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 18); ?></div>
                        <div class="vs08c-card-footer">
                            <span class="vs08c-card-duree"><?php echo $duree_j; ?> jours</span>
                            <?php if ($prix_min > 0): ?><span class="vs08c-card-prix">À partir de <?php echo number_format($prix_min, 0, ',', ' '); ?> € <span>/ pers.</span></span><?php else: ?><span class="vs08c-card-prix">Sur devis</span><?php endif; ?>
                        </div>
                    </div>
                </a>
            </article>
            <?php endwhile; ?>
        </div>
        <?php echo paginate_links(['total' => $q->max_num_pages, 'current' => $paged, 'prev_text' => '←', 'next_text' => '→']); ?>
        <?php else: ?>
        <p class="vs08c-empty">Aucun circuit trouvé.</p>
        <?php endif; wp_reset_postdata(); ?>
    </div>
</div>
<?php get_footer(); ?>
