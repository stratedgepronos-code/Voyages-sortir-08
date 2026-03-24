<?php get_header(); ?>
<main class="vs08-main vs08-container" style="padding-top:120px;min-height:60vh;">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <article>
            <?php if (!function_exists('is_checkout') || !is_checkout()) : ?><h1><?php the_title(); ?></h1><?php endif; ?>
            <div><?php the_content(); ?></div>
        </article>
    <?php endwhile; endif; ?>
</main>
<?php get_footer(); ?>
