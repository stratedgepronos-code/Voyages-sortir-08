<?php
if (!defined('ABSPATH')) exit;

class VS08C_CPT {
    public static function register() {
        register_post_type('vs08_circuit', [
            'labels' => [
                'name'               => '🗺️ Circuits',
                'singular_name'      => 'Circuit',
                'add_new'            => 'Ajouter un circuit',
                'add_new_item'       => 'Nouveau circuit',
                'edit_item'          => 'Modifier le circuit',
                'menu_name'          => '🗺️ Circuits',
                'all_items'          => 'Tous les circuits',
                'search_items'       => 'Rechercher un circuit',
                'not_found'          => 'Aucun circuit trouvé',
            ],
            'public'        => true,
            'show_ui'       => true,
            'show_in_menu'  => true,
            'menu_icon'     => 'dashicons-location-alt',
            'menu_position' => 6,
            'supports'      => ['title', 'thumbnail', 'excerpt'],
            'has_archive'   => true,
            'rewrite'       => ['slug' => 'circuit', 'with_front' => false],
            'show_in_rest'  => true,
        ]);
    }
}
