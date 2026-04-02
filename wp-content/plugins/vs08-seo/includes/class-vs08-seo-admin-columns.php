<?php
if (!defined('ABSPATH')) exit;

/**
 * Colonne « SEO » sur les listes produits (vs08_voyage, vs08_circuit).
 */
class VS08_SEO_Admin_Columns {

    const COLUMN = 'vs08_seo';

    public static function register() {
        if (!is_admin()) return;
        foreach (VS08_SEO_POST_TYPES as $pt) {
            add_filter("manage_edit-{$pt}_posts_columns", [__CLASS__, 'add_column']);
            add_action("manage_{$pt}_posts_custom_column", [__CLASS__, 'render_column'], 10, 2);
        }
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_list_styles']);
    }

    public static function enqueue_list_styles(string $hook): void {
        if ($hook !== 'edit.php') return;
        $pt = isset($_GET['post_type']) ? sanitize_key((string) $_GET['post_type']) : '';
        if (!in_array($pt, VS08_SEO_POST_TYPES, true)) return;
        wp_enqueue_style(
            'vs08-seo-admin-list',
            VS08_SEO_URL . 'assets/admin-list.css',
            [],
            VS08_SEO_VER
        );
    }

    public static function add_column(array $columns): array {
        $new = [];
        foreach ($columns as $key => $label) {
            $new[$key] = $label;
            if ($key === 'title') {
                $new[self::COLUMN] = 'SEO';
            }
        }
        if (!isset($new[self::COLUMN])) {
            $new[self::COLUMN] = 'SEO';
        }
        return $new;
    }

    public static function render_column(string $column, int $post_id): void {
        if ($column !== self::COLUMN) return;
        $seo = get_post_meta($post_id, '_vs08_seo_data', true);
        $ok  = self::is_seo_complete($seo);
        if ($ok) {
            echo '<span class="vs08-seo-pill vs08-seo-pill--ok" title="Titre + meta description présents">OK</span>';
        } else {
            $edit = get_edit_post_link($post_id, 'raw');
            $tip  = 'SEO non généré ou incomplet — ouvrez la fiche et utilisez « Générer via IA ».';
            echo '<span class="vs08-seo-pill vs08-seo-pill--no" title="' . esc_attr($tip) . '">Non</span>';
            if ($edit) {
                echo ' <a class="vs08-seo-pill-link" href="' . esc_url($edit) . '#vs08_seo_box">→</a>';
            }
        }
    }

    /**
     * @param mixed $seo meta _vs08_seo_data
     */
    public static function is_seo_complete($seo): bool {
        if (!is_array($seo)) return false;
        $t = isset($seo['seo_title']) ? trim((string) $seo['seo_title']) : '';
        $d = isset($seo['seo_desc']) ? trim((string) $seo['seo_desc']) : '';
        return $t !== '' && $d !== '';
    }

}
