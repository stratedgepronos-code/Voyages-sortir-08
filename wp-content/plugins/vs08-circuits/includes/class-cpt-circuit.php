<?php
if (!defined('ABSPATH')) exit;

class VS08C_CPT {
    public static function register() {
        register_post_type('vs08_circuit', [
            'labels' => [
                'name'               => 'Circuits',
                'singular_name'      => 'Circuit',
                'add_new'            => 'Ajouter un circuit',
                'add_new_item'       => 'Nouveau circuit',
                'edit_item'          => 'Modifier le circuit',
                'new_item'           => 'Nouveau circuit',
                'view_item'          => 'Voir le circuit',
                'search_items'       => 'Rechercher des circuits',
                'not_found'          => 'Aucun circuit trouvé',
                'not_found_in_trash' => 'Aucun circuit dans la corbeille',
                'menu_name'          => 'Circuits',
            ],
            'public'        => true,
            'show_ui'       => true,
            'show_in_menu'  => true,
            'menu_icon'     => 'dashicons-location-alt',
            'menu_position' => 6,
            'supports'      => ['title', 'editor', 'thumbnail', 'excerpt'],
            'has_archive'   => true,
            'rewrite'       => ['slug' => 'circuits'],
            'show_in_rest'  => true,
        ]);
    }
}
