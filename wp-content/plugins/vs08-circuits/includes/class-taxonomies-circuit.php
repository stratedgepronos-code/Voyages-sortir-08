<?php
if (!defined('ABSPATH')) exit;

class VS08C_Taxonomies {
    public static function register() {
        // Destinations (pays / régions) — pour le moteur de recherche
        register_taxonomy('circuit_destination', 'vs08_circuit', [
            'labels' => [
                'name'          => 'Destinations',
                'singular_name' => 'Destination',
                'search_items'  => 'Rechercher destinations',
                'all_items'     => 'Toutes les destinations',
                'edit_item'     => 'Modifier la destination',
                'add_new_item'  => 'Ajouter une destination',
            ],
            'hierarchical'      => true,
            'show_ui'          => true,
            'show_admin_column'=> true,
            'query_var'        => true,
            'rewrite'          => ['slug' => 'destination'],
        ]);

        // Thèmes (culturel, aventure, nature, luxe, gastronomique…)
        register_taxonomy('circuit_theme', 'vs08_circuit', [
            'labels' => [
                'name'          => 'Thèmes',
                'singular_name' => 'Thème',
                'search_items'  => 'Rechercher thèmes',
                'all_items'     => 'Tous les thèmes',
                'edit_item'     => 'Modifier le thème',
                'add_new_item'  => 'Ajouter un thème',
            ],
            'hierarchical'      => true,
            'show_ui'          => true,
            'show_admin_column'=> true,
            'query_var'        => true,
            'rewrite'          => ['slug' => 'theme'],
        ]);

        // Durée (7 jours, 10 jours, 15 jours…) — pour filtres
        register_taxonomy('circuit_duree', 'vs08_circuit', [
            'labels' => [
                'name'          => 'Durées',
                'singular_name' => 'Durée',
                'search_items'  => 'Rechercher durées',
                'all_items'     => 'Toutes les durées',
                'edit_item'     => 'Modifier la durée',
                'add_new_item'  => 'Ajouter une durée',
            ],
            'hierarchical'      => true,
            'show_ui'          => true,
            'show_admin_column'=> true,
            'query_var'        => true,
            'rewrite'          => ['slug' => 'duree'],
        ]);
    }
}
