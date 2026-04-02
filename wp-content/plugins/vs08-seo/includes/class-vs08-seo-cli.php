<?php
if (!defined('ABSPATH')) exit;

/**
 * WP-CLI : génération SEO en masse (serveur avec wp-cli + clé Claude dans config.cfg).
 *
 * Exemples :
 *   wp vs08-seo generate-all
 *   wp vs08-seo generate-all --force
 *   wp vs08-seo generate 123
 */
class VS08_SEO_CLI_Command {

    /**
     * Génère le SEO pour tous les séjours et circuits publiés ou brouillon.
     *
     * ## OPTIONS
     *
     * [--force]
     * : Régénère même si un SEO existe déjà.
     *
     * [--sleep=<secondes>]
     * : Pause entre chaque appel API (défaut : 1). Limite les rafales.
     *
     * ## EXAMPLES
     *
     *     wp vs08-seo generate-all
     *     wp vs08-seo generate-all --force
     */
    public function generate_all($args, $assoc_args) {
        $force  = isset($assoc_args['force']);
        $sleep  = isset($assoc_args['sleep']) ? max(0, (float) $assoc_args['sleep']) : 1.0;
        $qargs  = [
            'post_type'      => VS08_SEO_POST_TYPES,
            'post_status'    => ['publish', 'draft'],
            'posts_per_page' => -1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'fields'         => 'ids',
        ];
        if (!$force) {
            $qargs['meta_query'] = [['key' => '_vs08_seo_generated', 'compare' => 'NOT EXISTS']];
        }
        $ids = get_posts($qargs);
        if (empty($ids)) {
            WP_CLI::success('Aucun produit à traiter.');
            return;
        }
        $ok = 0;
        $err = 0;
        $bar = \WP_CLI\Utils\make_progress_bar('Génération SEO', count($ids));
        foreach ($ids as $pid) {
            $r = VS08_SEO_Generator::generate_and_save((int) $pid);
            if (is_wp_error($r)) {
                WP_CLI::warning(sprintf('#%d %s : %s', $pid, get_the_title($pid), $r->get_error_message()));
                $err++;
            } else {
                $ok++;
            }
            $bar->tick();
            if ($sleep > 0) {
                usleep((int) ($sleep * 1000000));
            }
        }
        $bar->finish();
        WP_CLI::success(sprintf('%d produit(s) OK, %d erreur(s).', $ok, $err));
    }

    /**
     * Génère le SEO pour un ID de publication.
     *
     * ## OPTIONS
     *
     * <id>
     * : ID du séjour golf ou circuit.
     */
    public function generate($args) {
        $pid = isset($args[0]) ? (int) $args[0] : 0;
        if ($pid <= 0) {
            WP_CLI::error('Usage : wp vs08-seo generate <id>');
        }
        $r = VS08_SEO_Generator::generate_and_save($pid);
        if (is_wp_error($r)) {
            WP_CLI::error($r->get_error_message());
        }
        WP_CLI::success(sprintf('SEO généré pour #%d — %s', $pid, get_the_title($pid)));
    }
}
