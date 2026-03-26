<?php
/**
 * Template Name: Devis séjour vacances
 * Slug : devis-sejour-vacances
 */
$vs08_devis_cfg = [
    'nonce_name'     => 'vs08_devis_nonce_sejour',
    'nonce_action'   => 'vs08_devis_sejour_vac',
    'subject_prefix' => '[Devis Séjour vacances]',
    'type_label'     => 'Séjour vacances',
    'hero_emoji'     => '🌴',
    'hero_title'     => 'séjour vacances',
    'hero_desc'      => 'Mer, montagne, famille ou couple : indiquez vos envies et votre budget. Nous vous proposons un séjour clé en main ou à composer.',
    'hero_bg'        => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1600&q=80',
    'extra_field'    => '',
];
$devis_sent = false;
$devis_error = '';
require get_template_directory() . '/template-parts/devis-agence-handler.php';
get_header();
require get_template_directory() . '/template-parts/devis-agence-markup.php';
get_footer();
