<?php
/**
 * Dupliquer un voyage (vs08_voyage) : produit vide sans photos, ni prix.
 */
if (!defined('ABSPATH')) exit;

class VS08V_Duplicate_Voyage {

    const ACTION = 'vs08v_duplicate_voyage';

    public static function register() {
        add_filter('post_row_actions', [__CLASS__, 'row_actions'], 10, 2);
        add_action('admin_init', [__CLASS__, 'handle_duplicate']);
    }

    /**
     * Ajoute le lien "Dupliquer" sous chaque voyage dans la liste.
     */
    public static function row_actions($actions, $post) {
        if ($post->post_type !== 'vs08_voyage') return $actions;
        if (!current_user_can('edit_post', $post->ID)) return $actions;

        $url = wp_nonce_url(
            admin_url(sprintf('admin.php?action=%s&post=%d', self::ACTION, $post->ID)),
            self::ACTION . '_' . $post->ID
        );
        $actions['vs08v_duplicate'] = '<a href="' . esc_url($url) . '" aria-label="Dupliquer ce voyage (produit vide)">Dupliquer</a>';
        return $actions;
    }

    /**
     * Traite la duplication et redirige vers l'édition du nouveau voyage.
     */
    public static function handle_duplicate() {
        if (!isset($_GET['action']) || $_GET['action'] !== self::ACTION) return;
        $post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
        if (!$post_id) return;
        if (!current_user_can('edit_post', $post_id)) wp_die(__('Autorisation insuffisante.', 'vs08-voyages'));
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', self::ACTION . '_' . $post_id)) wp_die(__('Lien invalide ou expiré.', 'vs08-voyages'));

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'vs08_voyage') wp_die(__('Voyage introuvable.', 'vs08-voyages'));

        $new_id = self::duplicate_post($post_id);
        if (is_wp_error($new_id)) wp_die($new_id->get_error_message());

        wp_safe_redirect(admin_url('post.php?action=edit&post=' . $new_id));
        exit;
    }

    /**
     * Crée une copie du post sans photos, ni prix, ni données métier.
     *
     * @param int $post_id ID du voyage à dupliquer
     * @return int|WP_Error ID du nouveau post ou erreur
     */
    private static function duplicate_post($post_id) {
        $post = get_post($post_id);
        if (!$post) return new WP_Error('invalid_post', 'Post invalide.');

        $new_post = [
            'post_type'   => 'vs08_voyage',
            'post_title'  => 'Copie de ' . $post->post_title,
            'post_content'=> '',
            'post_excerpt'=> '',
            'post_status' => 'draft',
            'post_author' => (int) get_current_user_id(),
        ];

        $new_id = wp_insert_post($new_post, true);
        if (is_wp_error($new_id)) return $new_id;

        // Données vides : pas de prix, pas de galerie, pas d'hôtel, etc.
        update_post_meta($new_id, 'vs08v_data', []);

        // Pas d'image à la une
        delete_post_meta($new_id, '_thumbnail_id');

        return $new_id;
    }
}
