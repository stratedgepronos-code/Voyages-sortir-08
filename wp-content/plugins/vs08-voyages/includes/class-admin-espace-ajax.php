<?php
/**
 * AJAX espace admin front (/espace-admin/) — handlers avec shutdown handler pour capturer les fatals.
 */
if (!defined('ABSPATH')) exit;

class VS08V_Admin_Espace_Ajax {

    private static $ajax_action = '';

    public static function register() {
        add_action('wp_ajax_vs08_admin_save_notes', [__CLASS__, 'ajax_save_notes']);
        add_action('wp_ajax_vs08_admin_mark_paid', [__CLASS__, 'ajax_mark_paid']);
        add_action('wp_ajax_vs08_admin_send_reminder', [__CLASS__, 'ajax_send_reminder']);
        add_action('wp_ajax_vs08_admin_export_csv', [__CLASS__, 'ajax_export_csv']);
        add_action('wp_ajax_vs08_admin_resend_emails', [__CLASS__, 'ajax_resend_emails']);
    }

    private static function install_fatal_catcher($action) {
        self::$ajax_action = $action;
        register_shutdown_function([__CLASS__, 'shutdown_handler']);
    }

    public static function shutdown_handler() {
        $err = error_get_last();
        if (!$err || !in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            return;
        }
        $msg = sprintf('%s in %s:%d', $err['message'], basename($err['file']), $err['line']);
        error_log('[VS08 FATAL — ' . self::$ajax_action . '] ' . $err['message'] . ' @ ' . $err['file'] . ':' . $err['line']);
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8', true, 200);
        }
        echo json_encode(['success' => false, 'data' => 'Erreur PHP : ' . $msg]);
    }

    private static function safe_from_header() {
        $host = function_exists('home_url') ? wp_parse_url(home_url(), PHP_URL_HOST) : 'localhost';
        $host = is_string($host) ? preg_replace('/^www\./', '', $host) : 'localhost';
        return 'From: Voyages Sortir 08 <noreply@' . $host . '>';
    }

    public static function ajax_save_notes() {
        self::install_fatal_catcher('save_notes');
        if (!check_ajax_referer('vs08_admin_actions', 'nonce', false)) {
            wp_send_json_error('Nonce expiré, rechargez la page.');
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Non autorisé.');
        }
        $order_id = (int) ($_POST['order_id'] ?? 0);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        if (!$order_id) {
            wp_send_json_error('ID manquant.');
        }
        $order = function_exists('wc_get_order') ? wc_get_order($order_id) : null;
        if (!$order) {
            wp_send_json_error('Commande introuvable.');
        }
        $order->update_meta_data('_vs08_admin_notes', $notes);
        $order->save();
        wp_send_json_success('Notes sauvegardées.');
    }

    public static function ajax_mark_paid() {
        self::install_fatal_catcher('mark_paid');
        if (!check_ajax_referer('vs08_admin_actions', 'nonce', false)) {
            wp_send_json_error('Nonce expiré.');
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Non autorisé.');
        }
        $order_id = (int) ($_POST['order_id'] ?? 0);
        if (!$order_id) {
            wp_send_json_error('ID manquant.');
        }
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error('Commande introuvable.');
        }
        $order->update_meta_data('_vs08v_solde_paye', current_time('mysql'));
        $order->update_meta_data('_vs08c_solde_paye', current_time('mysql'));
        $order->set_status('completed');
        $order->save();
        wp_send_json_success('Dossier marqué comme soldé.');
    }

    public static function ajax_send_reminder() {
        self::install_fatal_catcher('send_reminder');
        try {
            self::do_send_reminder();
        } catch (\Throwable $e) {
            error_log('[VS08 Reminder CRASH] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            wp_send_json_error('Erreur interne : ' . $e->getMessage());
        }
    }

    private static function do_send_reminder() {
        if (!check_ajax_referer('vs08_admin_actions', 'nonce', false)) {
            wp_send_json_error('Nonce invalide.');
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Non autorisé.');
        }
        $order_id = (int) ($_POST['order_id'] ?? 0);
        if (!$order_id) {
            wp_send_json_error('ID manquant.');
        }
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error('Commande introuvable.');
        }

        $data = $order->get_meta('_vs08v_booking_data');
        if (empty($data) || !is_array($data)) {
            $data = $order->get_meta('_vs08c_booking_data');
        }
        if (empty($data) || !is_array($data)) {
            wp_send_json_error('Pas de données de réservation.');
        }

        $fact = $data['facturation'] ?? [];
        $email = !empty($fact['email']) ? $fact['email'] : $order->get_billing_email();
        if (!$email) {
            wp_send_json_error('Pas d\'email client.');
        }

        $is_circuit = isset($data['type']) && $data['type'] === 'circuit';
        $titre_raw = $is_circuit ? ($data['circuit_titre'] ?? 'Circuit') : ($data['voyage_titre'] ?? 'Séjour golf');
        $titre = (is_scalar($titre_raw) && $titre_raw !== '') ? (string) $titre_raw : ($is_circuit ? 'Circuit' : 'Séjour golf');

        $body = '<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:20px">'
            . '<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,.08)">'
            . '<div style="background:#1a3a3a;padding:24px;text-align:center;color:#fff;font-family:Georgia,serif;font-size:20px">Voyages Sortir 08</div>'
            . '<div style="padding:28px 32px">'
            . '<h2 style="color:#1a3a3a;margin:0 0 16px">Rappel : solde &agrave; r&eacute;gler</h2>'
            . '<p style="font-size:15px;color:#333;line-height:1.6">Bonjour,</p>'
            . '<p style="font-size:15px;color:#333;line-height:1.6">Pour votre voyage <strong>' . esc_html($titre) . '</strong> (dossier VS08-' . $order_id . '), merci de r&eacute;gler le solde restant.</p>'
            . '<p style="margin-top:20px"><a href="' . esc_url(home_url('/espace-voyageur/')) . '" style="display:inline-block;padding:12px 28px;background:#2a7f7f;color:#fff;text-decoration:none;border-radius:8px;font-weight:bold">Acc&eacute;der &agrave; mon espace voyageur</a></p>'
            . '</div></div></body></html>';

        $headers = ['Content-Type: text/html; charset=UTF-8', self::safe_from_header()];

        error_log('[VS08 Reminder] Tentative d\'envoi à ' . $email . ' pour VS08-' . $order_id);
        $sent = wp_mail($email, 'Rappel : solde à régler — ' . $titre, $body, $headers);
        error_log('[VS08 Reminder] to ' . $email . ' => ' . ($sent ? 'OK' : 'FAIL'));

        if ($sent) {
            wp_send_json_success('Rappel envoyé à ' . $email);
        }
        wp_send_json_error('Erreur d\'envoi email. Vérifiez la configuration SMTP du serveur.');
    }

    public static function ajax_export_csv() {
        if (!current_user_can('manage_options')) {
            wp_die('Non autorisé.');
        }
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'vs08_export_csv')) {
            wp_die('Nonce expiré.');
        }
        if (!function_exists('wc_get_orders')) {
            wp_die('WooCommerce indisponible.');
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="vs08-dossiers-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, ['N° Dossier', 'Type', 'Client', 'Email', 'Téléphone', 'Voyage', 'Destination', 'Date départ', 'Durée', 'Voyageurs', 'Total', 'Payé', 'Solde', 'Statut'], ';');
        $orders = wc_get_orders(['limit' => -1, 'status' => array_keys(wc_get_order_statuses()), 'orderby' => 'date', 'order' => 'DESC']);
        $today = date('Y-m-d');
        foreach ($orders as $order) {
            $data = VS08V_Traveler_Space::get_booking_data_from_order($order, true);
            if (!$data) {
                continue;
            }
            $is_circuit = isset($data['type']) && $data['type'] === 'circuit';
            $fact = $data['facturation'] ?? [];
            $params = $data['params'] ?? [];
            $devis = $data['devis'] ?? [];
            $total = (float) ($data['total'] ?? 0);
            $si = VS08V_Traveler_Space::get_solde_info($order->get_id());
            $paye = $si ? ($total - $si['solde']) : $total;
            $solde = $si ? $si['solde'] : 0;
            $dest = '';
            if ($is_circuit) {
                $pid = (int) ($data['circuit_id'] ?? 0);
                if ($pid && class_exists('VS08C_Meta')) {
                    $cm = VS08C_Meta::get($pid);
                    $dest = $cm['destination'] ?? '';
                }
            } else {
                $pid = (int) ($data['voyage_id'] ?? 0);
                if ($pid && class_exists('VS08V_MetaBoxes')) {
                    $vm = VS08V_MetaBoxes::get($pid);
                    $dest = $vm['destination'] ?? '';
                }
            }
            $m2 = $is_circuit
                ? (class_exists('VS08C_Meta') ? VS08C_Meta::get((int) ($data['circuit_id'] ?? 0)) : [])
                : (class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get((int) ($data['voyage_id'] ?? 0)) : []);
            $duree = (int) ($m2['duree'] ?? 7);
            $dep = $params['date_depart'] ?? '';
            fputcsv($out, [
                'VS08-' . $order->get_id(),
                $is_circuit ? 'Circuit' : 'Golf',
                trim(($fact['prenom'] ?? '') . ' ' . ($fact['nom'] ?? '')),
                $fact['email'] ?? '',
                $fact['tel'] ?? '',
                $is_circuit ? ($data['circuit_titre'] ?? '') : ($data['voyage_titre'] ?? ''),
                $dest,
                $dep ? date('d/m/Y', strtotime($dep)) : '',
                $duree . ' nuits',
                (int) ($devis['nb_total'] ?? 0),
                number_format($total, 2, ',', ' ') . ' €',
                number_format($paye, 2, ',', ' ') . ' €',
                number_format($solde, 2, ',', ' ') . ' €',
                ($dep && $dep >= $today) ? 'À venir' : 'Passé',
            ], ';');
        }
        fclose($out);
        exit;
    }

    public static function ajax_resend_emails() {
        self::install_fatal_catcher('resend_emails');
        try {
            self::do_resend_emails();
        } catch (\Throwable $e) {
            error_log('[VS08 Resend CRASH] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            wp_send_json_error('Erreur interne : ' . $e->getMessage());
        }
    }

    private static function do_resend_emails() {
        if (!check_ajax_referer('vs08_admin_actions', 'nonce', false)) {
            wp_send_json_error('Nonce invalide.');
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Non autorisé.');
        }

        $order_id = (int) ($_POST['order_id'] ?? 0);
        if (!$order_id) {
            wp_send_json_error('ID manquant.');
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error('Commande introuvable.');
        }

        error_log('[VS08 Resend] Début pour VS08-' . $order_id);

        $order->delete_meta_data('_vs08v_emails_sent');
        $order->delete_meta_data('_vs08c_emails_sent');
        $order->save();

        $sent = false;
        $errors = [];

        if (class_exists('VS08V_Emails')) {
            error_log('[VS08 Resend] Tentative VS08V_Emails::dispatch(' . $order_id . ')');
            try {
                VS08V_Emails::dispatch($order_id);
                $order_check = wc_get_order($order_id);
                if ($order_check && $order_check->get_meta('_vs08v_emails_sent')) {
                    $sent = true;
                    error_log('[VS08 Resend] Golf OK');
                } else {
                    $errors[] = 'Golf: dispatch terminé sans flag (booking_data absente ou type circuit ?)';
                    error_log('[VS08 Resend] Golf: dispatch terminé sans flag');
                }
            } catch (\Throwable $e) {
                $errors[] = 'Golf: ' . $e->getMessage();
                error_log('[VS08 Resend] Golf: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            }
        } else {
            error_log('[VS08 Resend] VS08V_Emails n\'existe pas');
        }

        if (class_exists('VS08C_Emails')) {
            error_log('[VS08 Resend] Tentative VS08C_Emails::dispatch(' . $order_id . ')');
            try {
                VS08C_Emails::dispatch($order_id);
                $order_check = wc_get_order($order_id);
                if ($order_check && $order_check->get_meta('_vs08c_emails_sent')) {
                    $sent = true;
                    error_log('[VS08 Resend] Circuit OK');
                } else {
                    error_log('[VS08 Resend] Circuit: dispatch terminé sans flag');
                }
            } catch (\Throwable $e) {
                $errors[] = 'Circuit: ' . $e->getMessage();
                error_log('[VS08 Resend] Circuit: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            }
        }

        if ($sent) {
            wp_send_json_success('Emails complets envoyés (avec contrat en PJ) !');
        }
        $msg = 'Échec de l\'envoi.';
        if (!empty($errors)) {
            $msg .= ' ' . implode(' / ', $errors);
        }
        wp_send_json_error($msg);
    }
}
