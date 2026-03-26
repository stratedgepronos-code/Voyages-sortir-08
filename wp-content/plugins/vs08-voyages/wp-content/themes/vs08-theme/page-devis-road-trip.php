<?php
/**
 * Template Name: Devis Road Trip
 * Slug : devis-road-trip
 */
$vs08_devis_cfg = [
    'nonce_name'     => 'vs08_devis_nonce_road',
    'nonce_action'   => 'vs08_devis_road_trip',
    'subject_prefix' => '[Devis Road Trip]',
    'type_label'     => 'Road Trip',
    'hero_emoji'     => '🚗',
    'hero_title'     => 'road trip',
    'hero_desc'      => 'Itinéraire sur plusieurs étapes, type de véhicule, kilométrage : nous construisons votre route avec les bons temps de trajet et hébergements.',
    'hero_bg'        => 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=1600&q=80',
    'extra_field'    => 'road',
];
$devis_sent = false;
$devis_error = '';
require get_template_directory() . '/template-parts/devis-agence-handler.php';
get_header();
require get_template_directory() . '/template-parts/devis-agence-markup.php';
get_footer();
