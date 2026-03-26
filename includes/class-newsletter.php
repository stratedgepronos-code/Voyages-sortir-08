<?php
if (!defined('ABSPATH')) exit;

/**
 * VS08 Newsletter — Plugin maison léger
 * 
 * - Table custom wp_vs08_newsletter (pas wp_options)
 * - Sources : homepage, inscription, commande WooCommerce, admin
 * - Export CSV compatible Brevo (EMAIL;PRENOM;NOM;SOURCE;DATE)
 * - Page admin avec liste, recherche, suppression, stats
 * - Désinscription par token unique
 */
class VS08V_Newsletter {

    const TABLE_SUFFIX = 'vs08_newsletter';
    const DB_VERSION   = '2.0';

    /* ═══════════════════════════════════════════
       INIT
    ═══════════════════════════════════════════ */
    public static function register() {
        // Créer/mettre à jour la table
        add_action('init', [__CLASS__, 'maybe_create_table'], 5);

        // AJAX : formulaire homepage
        add_action('wp_ajax_vs08v_newsletter_subscribe',        [__CLASS__, 'ajax_subscribe']);
        add_action('wp_ajax_nopriv_vs08v_newsletter_subscribe', [__CLASS__, 'ajax_subscribe']);

        // Hook : inscription utilisateur WordPress
        add_action('user_register', [__CLASS__, 'on_user_register']);

        // Hook : commande WooCommerce (payée ou complétée)
        add_action('woocommerce_order_status_completed',  [__CLASS__, 'on_woo_order']);
        add_action('woocommerce_order_status_processing', [__CLASS__, 'on_woo_order']);
        add_action('woocommerce_thankyou',                [__CLASS__, 'on_woo_thankyou']);

        // Admin
        add_action('admin_menu', [__CLASS__, 'admin_menu']);

        // Désinscription front
        add_action('template_redirect', [__CLASS__, 'handle_unsubscribe']);
    }

    /* ═══════════════════════════════════════════
       TABLE BDD
    ═══════════════════════════════════════════ */
    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_SUFFIX;
    }

    public static function maybe_create_table() {
        if (get_option('vs08_nl_db_version') === self::DB_VERSION) return;
        global $wpdb;
        $table   = self::table_name();
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            email VARCHAR(180) NOT NULL,
            prenom VARCHAR(100) DEFAULT '',
            nom VARCHAR(100) DEFAULT '',
            source VARCHAR(30) DEFAULT 'homepage',
            token VARCHAR(64) NOT NULL,
            active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY active (active),
            KEY source (source)
        ) $charset;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Migrer les anciennes données depuis wp_options (v1)
        $old = get_option('vs08v_newsletter_emails_v1', []);
        if (is_array($old) && !empty($old)) {
            foreach ($old as $row) {
                self::add_subscriber(
                    $row['email'] ?? '',
                    '', '',
                    'homepage_v1',
                    $row['date'] ?? current_time('mysql')
                );
            }
            delete_option('vs08v_newsletter_emails_v1');
        }

        update_option('vs08_nl_db_version', self::DB_VERSION);
    }

    /* ═══════════════════════════════════════════
       AJOUTER UN ABONNÉ
    ═══════════════════════════════════════════ */
    public static function add_subscriber($email, $prenom = '', $nom = '', $source = 'homepage', $date = null) {
        global $wpdb;
        $email = strtolower(sanitize_email($email));
        if (!is_email($email)) return false;

        $table = self::table_name();
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE email = %s", $email));
        if ($exists) {
            // Mettre à jour le nom/prénom si vide + réactiver si désabonné
            $wpdb->query($wpdb->prepare(
                "UPDATE $table SET active = 1,
                 prenom = IF(prenom = '' AND %s != '', %s, prenom),
                 nom = IF(nom = '' AND %s != '', %s, nom)
                 WHERE email = %s",
                $prenom, $prenom, $nom, $nom, $email
            ));
            return 'exists';
        }

        $token = bin2hex(random_bytes(32));
        $wpdb->insert($table, [
            'email'      => $email,
            'prenom'     => sanitize_text_field($prenom),
            'nom'        => sanitize_text_field($nom),
            'source'     => sanitize_key($source),
            'token'       => $token,
            'active'     => 1,
            'created_at' => $date ?: current_time('mysql'),
        ], ['%s','%s','%s','%s','%s','%d','%s']);

        return $wpdb->insert_id ? 'added' : false;
    }

    /* ═══════════════════════════════════════════
       AJAX — FORMULAIRE HOMEPAGE
    ═══════════════════════════════════════════ */
    public static function ajax_subscribe() {
        $email  = isset($_POST['email'])  ? sanitize_email(wp_unslash($_POST['email']))  : '';
        $prenom = isset($_POST['prenom']) ? sanitize_text_field(wp_unslash($_POST['prenom'])) : '';

        if (!is_email($email)) {
            wp_send_json_error(['message' => 'Adresse email invalide.']);
        }

        $result = self::add_subscriber($email, $prenom, '', 'homepage');

        if ($result === 'exists') {
            wp_send_json_success(['message' => 'Vous êtes déjà inscrit à notre newsletter !']);
        } elseif ($result === 'added') {
            wp_send_json_success(['message' => 'Merci ! Vous recevrez nos meilleures offres.']);
        } else {
            wp_send_json_error(['message' => 'Une erreur est survenue, réessayez.']);
        }
    }

    /* ═══════════════════════════════════════════
       HOOK — INSCRIPTION UTILISATEUR
    ═══════════════════════════════════════════ */
    public static function on_user_register($user_id) {
        $user = get_userdata($user_id);
        if (!$user || !$user->user_email) return;
        self::add_subscriber(
            $user->user_email,
            $user->first_name ?? '',
            $user->last_name ?? '',
            'inscription'
        );
    }

    /* ═══════════════════════════════════════════
       HOOK — COMMANDE WOOCOMMERCE
    ═══════════════════════════════════════════ */
    public static function on_woo_order($order_id) {
        if (!function_exists('wc_get_order')) return;
        $order = wc_get_order($order_id);
        if (!$order) return;
        self::add_subscriber(
            $order->get_billing_email(),
            $order->get_billing_first_name(),
            $order->get_billing_last_name(),
            'commande'
        );
    }

    public static function on_woo_thankyou($order_id) {
        // Backup : si les hooks processing/completed ne se déclenchent pas
        self::on_woo_order($order_id);
    }

    /* ═══════════════════════════════════════════
       DÉSINSCRIPTION
    ═══════════════════════════════════════════ */
    public static function handle_unsubscribe() {
        if (!isset($_GET['vs08_unsub']) || !isset($_GET['token'])) return;
        global $wpdb;
        $token = sanitize_text_field($_GET['token']);
        $table = self::table_name();
        $updated = $wpdb->update($table, ['active' => 0], ['token' => $token], ['%d'], ['%s']);
        // Afficher une page simple
        wp_die(
            '<div style="max-width:500px;margin:80px auto;text-align:center;font-family:Outfit,sans-serif">'
            . '<h1 style="font-size:28px;color:#0f2424">Désinscription confirmée</h1>'
            . '<p style="color:#6b7280;margin-top:16px">Vous ne recevrez plus nos emails. Vous pouvez vous réinscrire à tout moment depuis notre site.</p>'
            . '<a href="' . esc_url(home_url('/')) . '" style="display:inline-block;margin-top:24px;padding:12px 28px;background:#59b7b7;color:#fff;border-radius:100px;text-decoration:none;font-weight:700">Retour au site</a>'
            . '</div>',
            'Désinscription — Voyages Sortir 08'
        );
    }

    /**
     * Génère le lien de désinscription pour un email donné.
     * Utilisable dans les emails marketing.
     */
    public static function get_unsubscribe_url($email) {
        global $wpdb;
        $table = self::table_name();
        $token = $wpdb->get_var($wpdb->prepare("SELECT token FROM $table WHERE email = %s", strtolower($email)));
        if (!$token) return '';
        return add_query_arg(['vs08_unsub' => '1', 'token' => $token], home_url('/'));
    }

    /* ═══════════════════════════════════════════
       ADMIN — MENU
    ═══════════════════════════════════════════ */
    public static function admin_menu() {
        add_submenu_page(
            'edit.php?post_type=vs08_voyage',
            'Newsletter',
            '📧 Newsletter',
            'manage_options',
            'vs08v-newsletter',
            [__CLASS__, 'admin_page']
        );
    }

    /* ═══════════════════════════════════════════
       ADMIN — PAGE
    ═══════════════════════════════════════════ */
    public static function admin_page() {
        global $wpdb;
        $table = self::table_name();

        // Actions
        if (isset($_GET['action']) && current_user_can('manage_options')) {
            // Export CSV
            if ($_GET['action'] === 'export') {
                check_admin_referer('vs08_nl_export');
                self::send_csv_export(isset($_GET['active_only']));
                exit;
            }
            // Supprimer
            if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
                check_admin_referer('vs08_nl_delete_' . intval($_GET['id']));
                $wpdb->delete($table, ['id' => intval($_GET['id'])], ['%d']);
                echo '<div class="notice notice-success"><p>Contact supprimé.</p></div>';
            }
        }

        // Stats
        $total    = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $active   = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE active = 1");
        $inactive = $total - $active;
        $sources  = $wpdb->get_results("SELECT source, COUNT(*) as cnt FROM $table WHERE active = 1 GROUP BY source ORDER BY cnt DESC");

        // Recherche
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $where = '';
        if ($search) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where = $wpdb->prepare(" WHERE email LIKE %s OR prenom LIKE %s OR nom LIKE %s", $like, $like, $like);
        }
        $subscribers = $wpdb->get_results("SELECT * FROM $table $where ORDER BY created_at DESC LIMIT 200");

        $base_url = admin_url('edit.php?post_type=vs08_voyage&page=vs08v-newsletter');
        ?>
        <div class="wrap">
            <h1>📧 Newsletter — Abonnés</h1>

            <!-- Stats -->
            <div style="display:flex;gap:16px;margin:20px 0">
                <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px 28px;flex:1">
                    <div style="font-size:32px;font-weight:700;color:#0f2424"><?php echo $active; ?></div>
                    <div style="color:#6b7280;font-size:13px;margin-top:4px">Abonnés actifs</div>
                </div>
                <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px 28px;flex:1">
                    <div style="font-size:32px;font-weight:700;color:#b91c1c"><?php echo $inactive; ?></div>
                    <div style="color:#6b7280;font-size:13px;margin-top:4px">Désabonnés</div>
                </div>
                <?php foreach ($sources as $src) : ?>
                <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px 28px;flex:1">
                    <div style="font-size:32px;font-weight:700;color:#59b7b7"><?php echo $src->cnt; ?></div>
                    <div style="color:#6b7280;font-size:13px;margin-top:4px"><?php echo esc_html(ucfirst($src->source)); ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Actions -->
            <div style="display:flex;gap:12px;align-items:center;margin-bottom:20px">
                <a href="<?php echo esc_url(wp_nonce_url($base_url . '&action=export&active_only=1', 'vs08_nl_export')); ?>" class="button button-primary">
                    📥 Export CSV actifs (Brevo)
                </a>
                <a href="<?php echo esc_url(wp_nonce_url($base_url . '&action=export', 'vs08_nl_export')); ?>" class="button">
                    📥 Export CSV tous
                </a>
                <form method="get" style="margin-left:auto;display:flex;gap:8px">
                    <input type="hidden" name="post_type" value="vs08_voyage">
                    <input type="hidden" name="page" value="vs08v-newsletter">
                    <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Rechercher..." style="min-width:240px">
                    <button type="submit" class="button">🔍 Chercher</button>
                </form>
            </div>

            <!-- Tableau -->
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Prénom</th>
                        <th>Nom</th>
                        <th>Source</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subscribers)) : ?>
                    <tr><td colspan="7" style="text-align:center;padding:24px;color:#9ca3af">Aucun abonné pour le moment.</td></tr>
                    <?php else : foreach ($subscribers as $sub) : ?>
                    <tr>
                        <td><strong><?php echo esc_html($sub->email); ?></strong></td>
                        <td><?php echo esc_html($sub->prenom); ?></td>
                        <td><?php echo esc_html($sub->nom); ?></td>
                        <td><span style="background:<?php echo $sub->source === 'homepage' ? '#dbeafe' : ($sub->source === 'commande' ? '#fef3c7' : '#e0e7ff'); ?>;padding:3px 10px;border-radius:100px;font-size:11px;font-weight:600"><?php echo esc_html(ucfirst($sub->source)); ?></span></td>
                        <td><?php echo $sub->active ? '<span style="color:#059669">✓ Actif</span>' : '<span style="color:#b91c1c">✗ Désabonné</span>'; ?></td>
                        <td><?php echo esc_html(date_i18n('j M Y H:i', strtotime($sub->created_at))); ?></td>
                        <td>
                            <a href="<?php echo esc_url(wp_nonce_url($base_url . '&action=delete&id=' . $sub->id, 'vs08_nl_delete_' . $sub->id)); ?>" onclick="return confirm('Supprimer cet abonné ?')" style="color:#b91c1c;font-size:12px">Supprimer</a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
            <p class="description" style="margin-top:16px">
                Le fichier CSV est compatible <strong>Brevo</strong> (ex-Sendinblue) : colonnes <code>EMAIL;PRENOM;NOM;SOURCE;DATE_INSCRIPTION;STATUT</code>, séparateur point-virgule, encodage UTF-8 avec BOM.
            </p>
        </div>
        <?php
    }

    /* ═══════════════════════════════════════════
       EXPORT CSV BREVO
    ═══════════════════════════════════════════ */
    private static function send_csv_export($active_only = false) {
        global $wpdb;
        $table = self::table_name();
        $where = $active_only ? "WHERE active = 1" : "";
        $rows = $wpdb->get_results("SELECT * FROM $table $where ORDER BY created_at DESC");

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=vs08-newsletter-brevo-' . date('Y-m-d') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
        fputcsv($out, ['EMAIL', 'PRENOM', 'NOM', 'SOURCE', 'DATE_INSCRIPTION', 'STATUT'], ';');
        foreach ($rows as $r) {
            fputcsv($out, [
                $r->email,
                $r->prenom,
                $r->nom,
                $r->source,
                $r->created_at,
                $r->active ? 'actif' : 'desabonne',
            ], ';');
        }
        fclose($out);
    }
}
