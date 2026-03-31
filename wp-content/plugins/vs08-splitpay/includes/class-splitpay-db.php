<?php
/**
 * VS08 SplitPay — Gestion BDD
 *
 * 2 tables custom :
 *   wp_vs08sp_groups  → un groupe = un voyage partagé (capitaine + montant total + statut)
 *   wp_vs08sp_shares  → une part = un participant (email + montant + token unique + statut paiement)
 */
if (!defined('ABSPATH')) exit;

class VS08SP_DB {

    /* ══════════════════════════════════════════
     *  CRÉATION DES TABLES (à l'activation)
     * ══════════════════════════════════════════ */
    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        // Table des groupes
        $table_groups = $wpdb->prefix . 'vs08sp_groups';
        $sql_groups = "CREATE TABLE $table_groups (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            voyage_id       BIGINT UNSIGNED NOT NULL DEFAULT 0,
            voyage_titre    VARCHAR(255) NOT NULL DEFAULT '',
            booking_data    LONGTEXT,
            captain_email   VARCHAR(200) NOT NULL DEFAULT '',
            captain_name    VARCHAR(200) NOT NULL DEFAULT '',
            total_amount    DECIMAL(12,2) NOT NULL DEFAULT 0,
            min_share       DECIMAL(12,2) NOT NULL DEFAULT 0,
            nb_participants INT UNSIGNED NOT NULL DEFAULT 2,
            status          VARCHAR(20) NOT NULL DEFAULT 'pending',
            expires_at      DATETIME NOT NULL,
            created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_captain (captain_email),
            KEY idx_expires (expires_at)
        ) $charset;";

        // Table des parts individuelles
        $table_shares = $wpdb->prefix . 'vs08sp_shares';
        $sql_shares = "CREATE TABLE $table_shares (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id    BIGINT UNSIGNED NOT NULL,
            token       VARCHAR(64) NOT NULL,
            email       VARCHAR(200) NOT NULL DEFAULT '',
            name        VARCHAR(200) NOT NULL DEFAULT '',
            amount      DECIMAL(12,2) NOT NULL DEFAULT 0,
            is_captain  TINYINT(1) NOT NULL DEFAULT 0,
            order_id    BIGINT UNSIGNED NOT NULL DEFAULT 0,
            status      VARCHAR(20) NOT NULL DEFAULT 'pending',
            paid_at     DATETIME DEFAULT NULL,
            reminder_sent TINYINT(1) NOT NULL DEFAULT 0,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_token (token),
            KEY idx_group (group_id),
            KEY idx_email (email),
            KEY idx_status (status)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_groups);
        dbDelta($sql_shares);

        update_option('vs08sp_db_version', VS08SP_DB_VERSION);
    }

    /* ══════════════════════════════════════════
     *  OPÉRATIONS SUR LES GROUPES
     * ══════════════════════════════════════════ */

    /**
     * Crée un nouveau groupe de paiement.
     * @return int|false  L'ID du groupe créé, ou false en cas d'erreur.
     */
    public static function create_group(array $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'vs08sp_groups';

        $result = $wpdb->insert($table, [
            'voyage_id'       => intval($data['voyage_id'] ?? 0),
            'voyage_titre'    => sanitize_text_field($data['voyage_titre'] ?? ''),
            'booking_data'    => wp_json_encode($data['booking_data'] ?? [], JSON_UNESCAPED_UNICODE),
            'captain_email'   => sanitize_email($data['captain_email'] ?? ''),
            'captain_name'    => sanitize_text_field($data['captain_name'] ?? ''),
            'total_amount'    => floatval($data['total_amount'] ?? 0),
            'min_share'       => floatval($data['min_share'] ?? 0),
            'nb_participants' => intval($data['nb_participants'] ?? 2),
            'status'          => 'pending',
            'expires_at'      => date('Y-m-d H:i:s', time() + (VS08SP_EXPIRY_HOURS * 3600)),
        ], [
            '%d', '%s', '%s', '%s', '%s', '%f', '%f', '%d', '%s', '%s',
        ]);

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Récupère un groupe par son ID.
     */
    public static function get_group(int $group_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vs08sp_groups';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $group_id), ARRAY_A);
        if ($row && !empty($row['booking_data'])) {
            $row['booking_data'] = json_decode($row['booking_data'], true);
        }
        return $row;
    }

    /**
     * Met à jour le statut d'un groupe.
     */
    public static function update_group_status(int $group_id, string $status) {
        global $wpdb;
        $table = $wpdb->prefix . 'vs08sp_groups';
        return $wpdb->update($table, ['status' => $status], ['id' => $group_id], ['%s'], ['%d']);
    }

    /**
     * Liste tous les groupes (pour l'admin), avec pagination.
     */
    public static function list_groups(string $status = '', int $limit = 20, int $offset = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'vs08sp_groups';
        $where = '';
        if ($status) {
            $where = $wpdb->prepare("WHERE status = %s", $status);
        }
        return $wpdb->get_results("SELECT * FROM $table $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset", ARRAY_A);
    }

    /**
     * Compte les groupes par statut.
     */
    public static function count_groups(string $status = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'vs08sp_groups';
        if ($status) {
            return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE status = %s", $status));
        }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
    }

    /**
     * Récupère les groupes expirés encore en status "pending".
     */
    public static function get_expired_groups() {
        global $wpdb;
        $table = $wpdb->prefix . 'vs08sp_groups';
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table WHERE status = 'pending' AND expires_at < %s", current_time('mysql')),
            ARRAY_A
        );
    }

    /**
     * Récupère les groupes d'un capitaine par email.
     */
    public static function get_groups_by_captain(string $email) {
        global $wpdb;
        $table = $wpdb->prefix . 'vs08sp_groups';
        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table WHERE captain_email = %s ORDER BY created_at DESC", $email),
            ARRAY_A
        );
        foreach ($rows as &$row) {
            if (!empty($row['booking_data'])) {
                $row['booking_data'] = json_decode($row['booking_data'], true);
            }
        }
        return $rows;
    }

    /* ══════════════════════════════════════════
     *  OPÉRATIONS SUR LES PARTS
     * ══════════════════════════════════════════ */

    /**
     * Crée une part (un participant) dans un groupe.
     * Génère automatiquement un token unique.
     * @return int|false  L'ID de la part, ou false.
     */
    public static function create_share(array $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'vs08sp_shares';

        // Générer un token unique de 48 caractères
        $token = self::generate_unique_token();

        $result = $wpdb->insert($table, [
            'group_id'   => intval($data['group_id']),
            'token'      => $token,
            'email'      => sanitize_email($data['email'] ?? ''),
            'name'       => sanitize_text_field($data['name'] ?? ''),
            'amount'     => floatval($data['amount'] ?? 0),
            'is_captain' => intval($data['is_captain'] ?? 0),
            'status'     => 'pending',
        ], [
            '%d', '%s', '%s', '%s', '%f', '%d', '%s',
        ]);

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Récupère une part par son token (pour la page de paiement publique).
     */
    public static function get_share_by_token(string $token) {
        global $wpdb;
        $table = $wpdb->prefix . 'vs08sp_shares';
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE token = %s", $token),
            ARRAY_A
        );
    }

    /**
     * Récupère toutes les parts d'un groupe.
     */
    public static function get_shares(int $group_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vs08sp_shares';
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table WHERE group_id = %d ORDER BY is_captain DESC, id ASC", $group_id),
            ARRAY_A
        );
    }

    /**
     * Marque une part comme payée.
     */
    public static function mark_share_paid(int $share_id, int $order_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vs08sp_shares';
        return $wpdb->update($table, [
            'status'   => 'paid',
            'order_id' => $order_id,
            'paid_at'  => current_time('mysql'),
        ], ['id' => $share_id], ['%s', '%d', '%s'], ['%d']);
    }

    /**
     * Vérifie si toutes les parts d'un groupe sont payées.
     * @return bool
     */
    public static function is_group_fully_paid(int $group_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vs08sp_shares';
        $pending = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE group_id = %d AND status != 'paid'", $group_id)
        );
        return $pending === 0;
    }

    /**
     * Compte combien de parts sont payées dans un groupe.
     * @return array ['paid' => int, 'total' => int, 'amount_paid' => float, 'amount_total' => float]
     */
    public static function get_payment_progress(int $group_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vs08sp_shares';
        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT status, amount FROM $table WHERE group_id = %d", $group_id),
            ARRAY_A
        );
        $paid = 0;
        $total = count($rows);
        $amount_paid = 0;
        $amount_total = 0;
        foreach ($rows as $row) {
            $amount_total += floatval($row['amount']);
            if ($row['status'] === 'paid') {
                $paid++;
                $amount_paid += floatval($row['amount']);
            }
        }
        return compact('paid', 'total', 'amount_paid', 'amount_total');
    }

    /**
     * Marque le rappel comme envoyé pour une part.
     */
    public static function mark_reminder_sent(int $share_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vs08sp_shares';
        return $wpdb->update($table, ['reminder_sent' => 1], ['id' => $share_id], ['%d'], ['%d']);
    }

    /* ══════════════════════════════════════════
     *  UTILITAIRES
     * ══════════════════════════════════════════ */

    /**
     * Génère un token unique de 48 caractères (alphanumérique, pas de caractères ambigus).
     */
    private static function generate_unique_token(): string {
        global $wpdb;
        $table = $wpdb->prefix . 'vs08sp_shares';
        $max_attempts = 10;
        for ($i = 0; $i < $max_attempts; $i++) {
            $token = bin2hex(random_bytes(24)); // 48 chars hex
            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE token = %s", $token));
            if (!$exists) return $token;
        }
        // Fallback : ajouter un timestamp pour garantir l'unicité
        return bin2hex(random_bytes(16)) . dechex(time());
    }

    /**
     * Retourne l'URL publique de paiement pour un token donné.
     */
    public static function get_payment_url(string $token): string {
        return home_url('/paiement-groupe/' . $token);
    }
}
