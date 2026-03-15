<?php
if (!defined('ABSPATH')) exit;
$destination = isset($_GET['destination']) ? sanitize_text_field($_GET['destination']) : '';
$theme = isset($_GET['theme']) ? sanitize_text_field($_GET['theme']) : '';
$duree = isset($_GET['duree']) ? sanitize_text_field($_GET['duree']) : '';
$prix_max = isset($_GET['prix_max']) ? floatval($_GET['prix_max']) : 0;
$ordre = isset($_GET['ordre']) ? sanitize_text_field($_GET['ordre']) : 'date';
$paged = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;

$q = VS08C_Search::query([
    'destination' => $destination,
    'theme' => $theme,
    'duree' => $duree,
    'prix_max' => $prix_max,
    'ordre' => $ordre,
    'paged' => $paged,
    'per_page' => 12,
]);

$destinations = get_terms(['taxonomy' => 'circuit_destination', 'hide_empty' => true]);
$themes = get_terms(['taxonomy' => 'circuit_theme', 'hide_empty' => true]);
$durees = get_terms(['taxonomy' => 'circuit_duree', 'hide_empty' => true]);
?>
<div class="vs08c-wrap">
    <div class="vs08c-page">
        <header class="vs08c-hero">
            <h1>Nos circuits</h1>
            <p>Voyages organisés par nos soins : itinéraires pensés, hébergements et activités sélectionnés. Trouvez le circuit qui vous ressemble.</p>
        </header>

        <form method="get" class="vs08c-filters" id="vs08c-filters">
            <?php if (get_option('permalink_structure')): ?>
                <input type="hidden" name="paged" value="1">
            <?php endif; ?>
            <div>
                <label>Destination</label>
                <select name="destination">
                    <option value="">Toutes</option>
                    <?php foreach ($destinations as $t): ?>
                        <option value="<?php echo esc_attr($t->slug); ?>" <?php selected($destination, $t->slug); ?>><?php echo esc_html($t->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Thème</label>
                <select name="theme">
                    <option value="">Tous</option>
                    <?php foreach ($themes as $t): ?>
                        <option value="<?php echo esc_attr($t->slug); ?>" <?php selected($theme, $t->slug); ?>><?php echo esc_html($t->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Durée</label>
                <select name="duree">
                    <option value="">Toutes</option>
                    <?php foreach ($durees as $t): ?>
                        <option value="<?php echo esc_attr($t->slug); ?>" <?php selected($duree, $t->slug); ?>><?php echo esc_html($t->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Budget max (€/pers)</label>
                <input type="number" name="prix_max" value="<?php echo $prix_max > 0 ? (int) $prix_max : ''; ?>" placeholder="Illimité" min="0" step="100" style="width:120px;padding:10px 14px;border:1.5px solid var(--circuit-border);border-radius:10px;">
            </div>
            <div>
                <label>Trier</label>
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
        <div class="vs08c-grid" id="vs08c-grid">
            <?php while ($q->have_posts()): $q->the_post();
                $meta = VS08C_MetaBoxes::get(get_the_ID());
                $prix_min = VS08C_Search::get_prix_min_for_circuit($meta);
                $duree_j = (int) ($meta['duree_jours'] ?? 8);
                $dest = get_the_terms(get_the_ID(), 'circuit_destination');
                $dest_name = $dest && !is_wp_error($dest) ? $dest[0]->name : '';
            ?>
            <article class="vs08c-card">
                <a href="<?php the_permalink(); ?>" class="vs08c-card-link">
                    <div class="vs08c-card-img">
                        <?php if (has_post_thumbnail()): the_post_thumbnail('medium_large'); else: ?>
                            <div class="vs08c-card-img-placeholder"></div>
                        <?php endif; ?>
                    </div>
                    <div class="vs08c-card-body">
                        <?php if ($dest_name): ?><div class="vs08c-card-meta"><?php echo esc_html($dest_name); ?></div><?php endif; ?>
                        <h2 class="vs08c-card-title"><?php the_title(); ?></h2>
                        <div class="vs08c-card-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 18); ?></div>
                        <div class="vs08c-card-footer">
                            <span class="vs08c-card-duree"><?php echo $duree_j; ?> jours</span>
                            <?php if ($prix_min > 0): ?>
                                <span class="vs08c-card-prix">À partir de <?php echo number_format($prix_min, 0, ',', ' '); ?> € <span>/ pers.</span></span>
                            <?php else: ?>
                                <span class="vs08c-card-prix">Sur devis</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </article>
            <?php endwhile; ?>
        </div>
        <?php
        $total = $q->max_num_pages;
        if ($total > 1):
            $base = add_query_arg('paged', '%#%');
            if ($destination) $base = add_query_arg('destination', $destination, $base);
            if ($theme) $base = add_query_arg('theme', $theme, $base);
            if ($duree) $base = add_query_arg('duree', $duree, $base);
            if ($prix_max > 0) $base = add_query_arg('prix_max', $prix_max, $base);
            if ($ordre && $ordre !== 'date') $base = add_query_arg('ordre', $ordre, $base);
        ?>
        <nav class="vs08c-pagination">
            <?php echo paginate_links([
                'base' => str_replace('%#%', $paged, $base),
                'format' => '?paged=%#%',
                'current' => $paged,
                'total' => $total,
                'prev_text' => '←',
                'next_text' => '→',
            ]); ?>
        </nav>
        <?php endif; wp_reset_postdata(); ?>

        <?php else: ?>
        <p class="vs08c-empty">Aucun circuit ne correspond à vos critères. Modifiez les filtres ou consultez toutes nos destinations.</p>
        <?php endif; ?>
    </div>
</div>
