<?php
/**
 * Plugin Name: VS08 Circuits — Tour Opérateur
 * Description: Circuits et voyages organisés. Back-office, catégories, moteur de recherche, fiches circuits. Intégration recherche vols.
 * Version: 1.0.0
 * Author: Voyages Sortir 08
 * Text Domain: vs08-circuits
 */

if (!defined('ABSPATH')) exit;

define('VS08C_PATH', plugin_dir_path(__FILE__));
define('VS08C_URL',  plugin_dir_url(__FILE__));
define('VS08C_VER',  '1.0.0');

// CPT Circuit
require_once VS08C_PATH . 'includes/class-cpt-circuit.php';
// Taxonomies (destinations, thèmes, durées)
require_once VS08C_PATH . 'includes/class-taxonomies-circuit.php';
// Meta boxes back-office
require_once VS08C_PATH . 'includes/class-meta-boxes-circuit.php';
// Recherche / filtres
require_once VS08C_PATH . 'includes/class-search-circuits.php';

add_action('init', ['VS08C_CPT', 'register']);
add_action('init', ['VS08C_Taxonomies', 'register']);
add_action('add_meta_boxes', ['VS08C_MetaBoxes', 'register']);
add_action('save_post_vs08_circuit', ['VS08C_MetaBoxes', 'save'], 10, 2);
add_action('wp_enqueue_scripts', ['VS08C_Plugin', 'enqueue_front'], 15);
add_action('admin_enqueue_scripts', ['VS08C_Plugin', 'enqueue_admin'], 10);

// Template single + archive
add_filter('template_include', ['VS08C_Plugin', 'template_include'], 99);

// Shortcode page liste circuits avec filtres
add_shortcode('vs08_circuits', ['VS08C_Plugin', 'shortcode_circuits']);

class VS08C_Plugin {
    public static function enqueue_front() {
        if (!is_singular('vs08_circuit') && !is_post_type_archive('vs08_circuit')) {
            global $post;
            if ($post && has_shortcode($post->post_content ?? '', 'vs08_circuits')) {
                self::enqueue_circuits_assets();
            }
            return;
        }
        self::enqueue_circuits_assets();
    }

    private static function enqueue_circuits_assets() {
        wp_enqueue_style(
            'vs08-circuits',
            VS08C_URL . 'assets/css/circuits.css',
            [],
            VS08C_VER
        );
        wp_enqueue_script(
            'vs08-circuits',
            VS08C_URL . 'assets/js/circuits.js',
            ['jquery'],
            VS08C_VER,
            true
        );
    }

    public static function enqueue_admin($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        global $post;
        if (!$post || $post->post_type !== 'vs08_circuit') return;
        wp_enqueue_style('vs08-circuits-admin', VS08C_URL . 'assets/css/admin.css', [], VS08C_VER);
    }

    public static function template_include($template) {
        if (is_singular('vs08_circuit')) {
            $file = VS08C_PATH . 'templates/single-circuit.php';
            if (file_exists($file)) return $file;
        }
        if (is_post_type_archive('vs08_circuit')) {
            $file = VS08C_PATH . 'templates/archive-circuit.php';
            if (file_exists($file)) return $file;
        }
        return $template;
    }

    public static function shortcode_circuits($atts) {
        ob_start();
        include VS08C_PATH . 'templates/page-circuits.php';
        return ob_get_clean();
    }
}
