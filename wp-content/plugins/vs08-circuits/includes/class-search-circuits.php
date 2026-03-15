<?php
if (!defined('ABSPATH')) exit;

/**
 * Moteur de recherche circuits : filtres par destination, thème, durée, période (date), budget.
 * Utilisé par la page liste (shortcode) et l’archive.
 */
class VS08C_Search {
    public static function query($args = []) {
        $paged = isset($args['paged']) ? (int) $args['paged'] : (get_query_var('paged') ?: 1);
        $per_page = isset($args['per_page']) ? (int) $args['per_page'] : 12;
        $destination = isset($args['destination']) ? sanitize_text_field($args['destination']) : '';
        $theme = isset($args['theme']) ? sanitize_text_field($args['theme']) : '';
        $duree = isset($args['duree']) ? sanitize_text_field($args['duree']) : '';
        $prix_max = isset($args['prix_max']) ? floatval($args['prix_max']) : 0;
        $ordre = isset($args['ordre']) ? $args['ordre'] : 'date'; // date, prix_asc, prix_desc, titre

        $query_args = [
            'post_type'      => 'vs08_circuit',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $paged,
        ];

        $tax_query = [];
        if ($destination) {
            $tax_query[] = ['taxonomy' => 'circuit_destination', 'field' => 'slug', 'terms' => $destination];
        }
        if ($theme) {
            $tax_query[] = ['taxonomy' => 'circuit_theme', 'field' => 'slug', 'terms' => $theme];
        }
        if ($duree) {
            $tax_query[] = ['taxonomy' => 'circuit_duree', 'field' => 'slug', 'terms' => $duree];
        }
        if (!empty($tax_query)) {
            $query_args['tax_query'] = $tax_query;
        }

        if ($ordre === 'titre') {
            $query_args['orderby'] = 'title';
            $query_args['order'] = 'ASC';
        } elseif ($ordre === 'prix_asc' || $ordre === 'prix_desc') {
            $query_args['orderby'] = 'meta_value_num';
            $query_args['meta_key'] = 'vs08c_prix_min';
            $query_args['order'] = $ordre === 'prix_asc' ? 'ASC' : 'DESC';
        } else {
            $query_args['orderby'] = 'date';
            $query_args['order'] = 'DESC';
        }

        $q = new WP_Query($query_args);

        // Filtrage par prix max : on récupère les circuits et on filtre en PHP (prix min par période)
        if ($prix_max > 0 && $q->posts) {
            $filtered = [];
            foreach ($q->posts as $post) {
                $meta = VS08C_MetaBoxes::get($post->ID);
                $prix_min = self::get_prix_min_for_circuit($meta);
                if ($prix_min > 0 && $prix_min <= $prix_max) {
                    $filtered[] = $post;
                } elseif ($prix_min == 0) {
                    $filtered[] = $post;
                }
            }
            $q->posts = $filtered;
            $q->post_count = count($filtered);
        }

        return $q;
    }

    /** Prix minimum parmi toutes les périodes du circuit */
    public static function get_prix_min_for_circuit($meta) {
        $periodes = $meta['periodes'] ?? [];
        $min = 0;
        foreach ($periodes as $p) {
            $prix = isset($p['prix']) ? floatval($p['prix']) : 0;
            if ($prix > 0 && ($min === 0 || $prix < $min)) {
                $min = $prix;
            }
        }
        return $min;
    }
}
