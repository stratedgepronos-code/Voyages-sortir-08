<?php
if (!defined('ABSPATH')) exit;

/**
 * Module Newsletter : collecte emails, stockage persistant (option), export CSV compatible Brevo.
 * Les données sont en base (options) donc non perdues en mise à jour.
 */
class VS08V_Newsletter {

    const OPTION_EMAILS = 'vs08v_newsletter_emails_v1';

    public static function register() {
        add_action('wp_ajax_vs08v_newsletter_subscribe', [__CLASS__, 'ajax_subscribe']);
        add_action('wp_ajax_nopriv_vs08v_newsletter_subscribe', [__CLASS__, 'ajax_subscribe']);
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
    }

    public static function ajax_subscribe() {
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        if (!is_email($email)) {
            wp_send_json_error(['message' => 'Adresse email invalide.']);
        }
        $emails = self::get_emails();
        $key = strtolower($email);
        if (isset($emails[$key])) {
            wp_send_json_success(['message' => 'Vous êtes déjà inscrit.']);
        }
        $emails[$key] = [
            'email' => $email,
            'date'  => current_time('mysql'),
        ];
        update_option(self::OPTION_EMAILS, $emails, false);
        wp_send_json_success(['message' => 'Merci ! Vous recevrez nos offres.']);
    }

    /** Retourne la liste des emails (tableau associatif email_lower => [email, date]) */
    public static function get_emails() {
        $data = get_option(self::OPTION_EMAILS, []);
        return is_array($data) ? $data : [];
    }

    public static function admin_menu() {
        add_submenu_page(
            'edit.php?post_type=vs08_voyage',
            'Newsletter — Export Brevo',
            'Newsletter',
            'manage_options',
            'vs08v-newsletter',
            [__CLASS__, 'admin_page']
        );
    }

    public static function admin_page() {
        $emails = self::get_emails();
        $count = count($emails);

        if (isset($_GET['action']) && $_GET['action'] === 'export' && current_user_can('manage_options')) {
            check_admin_referer('vs08v_newsletter_export');
            self::send_csv_export();
            exit;
        }

        ?>
        <div class="wrap">
            <h1>Newsletter — Export pour Brevo</h1>
            <p>Les adresses sont enregistrées en base et ne sont pas perdues lors des mises à jour.</p>
            <p><strong><?php echo (int) $count; ?></strong> adresse(s) enregistrée(s).</p>
            <?php if ($count > 0) : ?>
                <p>
                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('edit.php?post_type=vs08_voyage&page=vs08v-newsletter&action=export'), 'vs08v_newsletter_export')); ?>" class="button button-primary">
                        Télécharger le fichier CSV (Brevo)
                    </a>
                </p>
                <p class="description">Fichier compatible Brevo : colonnes <code>EMAIL</code>, <code>DATE_INSCRIPTION</code>.</p>
            <?php else : ?>
                <p>Aucune inscription pour le moment.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function send_csv_export() {
        $emails = self::get_emails();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=newsletter-brevo-' . date('Y-m-d') . '.csv');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
        fputcsv($out, ['EMAIL', 'DATE_INSCRIPTION'], ';');
        foreach ($emails as $row) {
            fputcsv($out, [$row['email'], $row['date'] ?? ''], ';');
        }
        fclose($out);
    }
}
