<?php
/**
 * Template Name: Devis circuit
 * Slug : devis-circuit
 */
$vs08_devis_cfg = [
    'nonce_name'     => 'vs08_devis_nonce_circuit',
    'nonce_action'   => 'vs08_devis_circuit',
    'subject_prefix' => '[Devis Circuit]',
    'type_label'     => 'Circuit',
    'hero_emoji'     => '🗺️',
    'hero_title'     => 'circuit',
    'hero_desc'      => 'Découverte multi-destinations, rythme souhaité, guide ou autonomie : nous adaptons le circuit à votre niveau et vos centres d’intérêt.',
    'hero_bg'        => 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=1600&q=80',
    'extra_field'    => 'circuit',
];
$devis_sent = false;
$devis_error = '';
require get_template_directory() . '/template-parts/devis-agence-handler.php';
get_header();
require get_template_directory() . '/template-parts/devis-agence-markup.php';
get_footer();
