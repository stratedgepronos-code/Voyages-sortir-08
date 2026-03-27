<?php
class VS08V_PostType {
    public static function register() {
        register_post_type('vs08_voyage', [
            'labels' => [
                'name'               => '✈️ Voyages Golf',
                'singular_name'      => 'Voyage Golf',
                'add_new'            => 'Ajouter un voyage',
                'add_new_item'       => 'Nouveau voyage golf',
                'edit_item'          => 'Modifier le voyage',
                'menu_name'          => '✈️ Voyages Golf',
            ],
            'public'        => true,
            'show_ui'       => true,
            'show_in_menu'  => true,
            'menu_icon'     => 'dashicons-palmtree',
            'menu_position' => 5,
            'supports'      => ['title','editor','thumbnail','excerpt','comments'],
            'has_archive'   => false,
            'rewrite'       => ['slug' => 'sejour'],
            'show_in_rest'  => false,
        ]);
    }
}
