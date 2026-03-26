<?php
/**
 * Template Name: Devis City Trip
 * Slug : devis-city-trip
 */
$vs08_devis_cfg = [
    'nonce_name'     => 'vs08_devis_nonce_city',
    'nonce_action'   => 'vs08_devis_city_trip',
    'subject_prefix' => '[Devis City Trip]',
    'type_label'     => 'City Trip',
    'hero_emoji'     => '🏙️',
    'hero_title'     => 'city trip',
    'hero_desc'      => 'Week-end culture, shopping ou gastronomie : nous optimisons votre temps sur place (hôtel central, transferts, idées d’expériences).',
    'hero_bg'        => 'https://images.unsplash.com/photo-1477959858617-67f85cf4f290?w=1600&q=80',
    'extra_field'    => 'city',
];
$devis_sent = false;
$devis_error = '';
require get_template_directory() . '/template-parts/devis-agence-handler.php';
get_header();
require get_template_directory() . '/template-parts/devis-agence-markup.php';
get_footer();
