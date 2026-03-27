<?php
if (!defined('ABSPATH')) exit;

class VS08S_CPT {
    public static function register() {
        register_post_type('vs08_sejour', [
            'labels' => [
                'name'           => '🏖️ Séjours',
                'singular_name'  => 'Séjour',
                'add_new'        => 'Ajouter un séjour',
                'add_new_item'   => 'Nouveau séjour',
                'edit_item'      => 'Modifier le séjour',
                'menu_name'      => '🏖️ Séjours',
            ],
            'public'        => true,
            'show_ui'       => true,
            'show_in_menu'  => true,
            'menu_icon'     => 'dashicons-palmtree',
            'menu_position' => 6,
            'supports'      => ['title', 'editor', 'thumbnail', 'excerpt'],
            'has_archive'   => false,
            'rewrite'       => ['slug' => 'sejour-all-inclusive'],
            'show_in_rest'  => false, // Éditeur classique (compatibilité metaboxes)
        ]);
    }
}
