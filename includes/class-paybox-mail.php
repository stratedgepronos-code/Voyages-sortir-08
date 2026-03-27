<?php
/**
 * VS08 Voyages — Intégration Paybox Mail API
 * Permet de créer des liens de paiement via l'API Paybox Mail et de gérer les webhooks.
 */
if (!defined('ABSPATH')) exit;

class VS08V_Paybox_Mail {

    const API_BASE = 'https://www.payboxmail.com/api';

    public static function register() {
        add_action('rest_api_init', [__CLASS__, 'register_webhook_endpoint']);
    }

    /**
     * Vérifie si l'API Paybox Mail est configurée.
     */
    public static function is_configured() {
        return defined('VS08_PAYBOX_MAIL_APP_KEY') && VS08_PAYBOX_MAIL_APP_KEY
            && defined('VS08_PAYBOX_MAIL_SECRET_KEY') && VS08_PAYBOX_MAIL_SECRET_KEY;
    }

    /**
     * Génère la signature HMAC pour l'API Paybox Mail.
     */
    private static function sign($url, $method, $body = '') {
        $time = time();
        $to_sign = $url . '+' . $method . '+' . VS08_PAYBOX_MAIL_SECRET_KEY . '+' . $body . '+' . $time;
        return [
            'signature' => hash('sha256', $to_sign),
            'time'      => $time,
        ];
    }

    /**
     * Headers d'authentification pour l'API.
     */
    private static function headers($signature, $time) {
        return [
            'Content-Type'      => 'application/json',
            'X-Auth-Appkey'     => VS08_PAYBOX_MAIL_APP_KEY,
            'X-Auth-Signature'  => $signature,
            'X-Method-Signature' => 'sha256',
            'X-Auth-Time'       => (string) $time,
        ];
    }

    /**
     * Crée une demande de paiement via l'API Paybox Mail.
     *
     * @param string $reference   Référence interne (ex: "VS08-123-SOLDE")
     * @param float  $amount      Montant en euros
     * @param string $email       Email du client
     * @param bool   $send_mail   Envoyer l'email automatiquement via Paybox
     * @return array ['success' => bool, 'data' => array|null, 'error' => string|null]
     */
    public static function create_payment_request($reference, $amount, $email, $send_mail = true) {
        if (!self::is_configured()) {
            return ['success' => false, 'data' => null, 'error' => 'Paybox Mail non configuré. Ajoutez PAYBOX_MAIL_APP_KEY et PAYBOX_MAIL_SECRET_KEY dans config.cfg.'];
        }

        $url = self::API_BASE . '/paymentrequests/single/';
        $body_data = [
            'reference'     => sanitize_text_field($reference),
            'price'         => round((float) $amount, 2),
            'email_address' => sanitize_email($email),
            'send_mail'     => (bool) $send_mail,
        ];
        $body_json = wp_json_encode($body_data);

        error_log('VS08 PBM: Creating payment request — ref=' . $reference . ' amount=' . $amount . ' email=' . $email);

        $sign = self::sign($url, 'POST', $body_json);

        $response = wp_remote_post($url, [
            'timeout' => 5,
            'headers' => self::headers($sign['signature'], $sign['time']),
            'body'    => $body_json,
        ]);

        if (is_wp_error($response)) {
            $err = 'Erreur réseau Paybox Mail : ' . $response->get_error_message();
            error_log('VS08 PBM ERROR: ' . $err);
            return ['success' => false, 'data' => null, 'error' => $err];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        error_log('VS08 PBM: Response HTTP ' . $code . ' — body=' . substr((string)$body, 0, 500));

        if ($code >= 200 && $code < 300 && !empty($data)) {
            return ['success' => true, 'data' => $data, 'error' => null];
        }

        $error_msg = 'Erreur Paybox Mail (HTTP ' . $code . ')';
        if (!empty($data['message'])) {
            $error_msg .= ' : ' . $data['message'];
        } elseif (!empty($data['error'])) {
            $error_msg .= ' : ' . $data['error'];
        } elseif ($body) {
            $error_msg .= ' : ' . substr((string)$body, 0, 200);
        }

        return ['success' => false, 'data' => $data, 'error' => $error_msg];
    }

    /**
     * Récupère le statut d'une demande de paiement.
     *
     * @param string $request_id  ID Paybox Mail (ex: "PHBA16184878736223")
     * @return array ['success' => bool, 'data' => array|null, 'error' => string|null]
     */
    public static function get_payment_status($request_id) {
        if (!self::is_configured()) {
            return ['success' => false, 'data' => null, 'error' => 'Paybox Mail non configuré.'];
        }

        $url = self::API_BASE . '/paymentrequests/single/' . sanitize_text_field($request_id);
        $sign = self::sign($url, 'GET', '');

        $response = wp_remote_get($url, [
            'timeout' => 30,
            'headers' => self::headers($sign['signature'], $sign['time']),
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'data' => null, 'error' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code >= 200 && $code < 300 && !empty($data)) {
            return ['success' => true, 'data' => $data, 'error' => null];
        }

        return ['success' => false, 'data' => $data, 'error' => 'Erreur HTTP ' . $code];
    }

    /**
     * Enregistre l'endpoint REST pour le webhook Paybox Mail.
     */
    public static function register_webhook_endpoint() {
        register_rest_route('vs08v/v1', '/paybox-mail-webhook', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'handle_webhook'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Traite le webhook Paybox Mail (appelé quand un paiement est confirmé).
     */
    public static function handle_webhook(\WP_REST_Request $request) {
        $body = $request->get_json_params();
        if (empty($body)) {
            $body = $request->get_body_params();
        }

        $request_id = $body['IDrequest'] ?? ($body['id'] ?? '');
        $reference  = $body['reference'] ?? '';
        $status     = $body['status'] ?? ($body['payment_status'] ?? '');

        if (empty($reference)) {
            return new \WP_REST_Response(['status' => 'error', 'message' => 'Référence manquante.'], 400);
        }

        // Extraire l'order_id depuis la référence (format: VS08-{order_id}-SOLDE ou VS08-{order_id}-SOLDE-PARTIEL)
        if (!preg_match('/VS08-(\d+)-SOLDE/', $reference, $matches)) {
            return new \WP_REST_Response(['status' => 'error', 'message' => 'Référence non reconnue.'], 400);
        }

        $parent_order_id = (int) $matches[1];
        $order = wc_get_order($parent_order_id);
        if (!$order) {
            return new \WP_REST_Response(['status' => 'error', 'message' => 'Commande introuvable.'], 404);
        }

        $is_paid = in_array(strtolower($status), ['paid', 'completed', 'captured', 'success', 'payment_success'], true);

        if ($is_paid) {
            self::process_solde_paid($parent_order_id, $reference, $request_id, $body);
        }

        // Log
        $order->add_order_note(sprintf(
            'Webhook Paybox Mail reçu — Réf: %s — Statut: %s — ID: %s',
            $reference,
            $status ?: 'inconnu',
            $request_id ?: '—'
        ));
        $order->save();

        return new \WP_REST_Response(['status' => 'ok'], 200);
    }

    /**
     * Marque le solde comme payé suite au webhook.
     */
    private static function process_solde_paid($parent_order_id, $reference, $request_id, $webhook_data) {
        $order = wc_get_order($parent_order_id);
        if (!$order) return;

        $amount = 0;
        if (!empty($webhook_data['price'])) {
            $amount = (float) $webhook_data['price'];
        } elseif (!empty($webhook_data['amount'])) {
            $amount = (float) $webhook_data['amount'];
        }

        $pbm_payments = $order->get_meta('_vs08v_paybox_mail_payments');
        if (!is_array($pbm_payments)) $pbm_payments = [];
        $pbm_payments[] = [
            'request_id' => $request_id,
            'reference'  => $reference,
            'amount'     => $amount,
            'date'       => current_time('Y-m-d H:i:s'),
            'raw'        => $webhook_data,
        ];
        $order->update_meta_data('_vs08v_paybox_mail_payments', $pbm_payments);

        $total_pbm_paid = 0;
        foreach ($pbm_payments as $p) {
            $total_pbm_paid += (float) ($p['amount'] ?? 0);
        }

        $solde_info = VS08V_Traveler_Space::get_solde_info($parent_order_id);
        if ($solde_info) {
            $solde_after = max(0, $solde_info['solde'] - $total_pbm_paid);
            if ($solde_after <= 0) {
                $order->update_meta_data('_vs08v_solde_marque_paye', 1);
            }
        }

        $order->add_order_note(sprintf(
            'Paiement Paybox Mail confirmé — %.2f € — Réf: %s',
            $amount,
            $reference
        ));
        $order->save();

        // Email de confirmation au client
        self::send_payment_confirmation_email($order, $amount, $reference);
    }

    /**
     * Envoie un email de confirmation au client après paiement du solde via Paybox Mail.
     */
    private static function send_payment_confirmation_email($order, $amount, $reference) {
        $data = VS08V_Traveler_Space::get_booking_data_from_order($order);
        if (!$data) return;

        $fact = $data['facturation'] ?? [];
        $email = $fact['email'] ?? $order->get_billing_email();
        if (!$email) return;

        $titre = $data['voyage_titre'] ?? 'Séjour golf';
        $solde_info = VS08V_Traveler_Space::get_solde_info($order->get_id());
        $solde_restant = $solde_info ? $solde_info['solde'] : 0;

        $subject = 'Confirmation de paiement — ' . $titre;
        $body = '<div style="font-family:\'Outfit\',Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;">';
        $body .= '<h2 style="color:#2a7f7f;">Paiement reçu</h2>';
        $body .= '<p>Bonjour ' . esc_html(($fact['prenom'] ?? '') . ' ' . ($fact['nom'] ?? '')) . ',</p>';
        $body .= '<p>Nous avons bien reçu votre paiement de <strong>' . number_format($amount, 2, ',', ' ') . ' €</strong> pour votre dossier <strong>' . esc_html($titre) . '</strong> (Réf. ' . esc_html($reference) . ').</p>';
        if ($solde_restant > 0) {
            $body .= '<p>Il reste un solde de <strong>' . number_format($solde_restant, 2, ',', ' ') . ' €</strong> à régler.</p>';
        } else {
            $body .= '<p style="color:#22c55e;font-weight:700;">Votre séjour est entièrement réglé. Merci !</p>';
        }
        $body .= '<p><a href="' . esc_url(VS08V_Traveler_Space::voyage_url($order->get_id())) . '" style="display:inline-block;padding:12px 24px;background:#2a7f7f;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;">Voir mon dossier voyage</a></p>';
        $body .= '<p style="color:#999;font-size:13px;">Voyages Sortir 08 — Châlons-en-Champagne</p>';
        $body .= '</div>';

        $headers = ['Content-Type: text/html; charset=UTF-8', 'From: Voyages Sortir 08 <noreply@sortirmonde.fr>'];
        wp_mail($email, $subject, $body, $headers);

        $admin_emails = ['sortir08.ag@wanadoo.fr', 'sortir08@wanadoo.fr'];
        $admin_subject = 'Paiement solde reçu — ' . $titre . ' — VS08-' . $order->get_id();
        wp_mail($admin_emails, $admin_subject, $body, $headers);
    }

    /**
     * Crée un paiement solde via Paybox Mail pour une commande voyage.
     * Remplace le flux WooCommerce order-pay.
     *
     * @param int        $parent_order_id
     * @param float|null $amount  null = solde entier
     * @return array ['success' => bool, 'message' => string, 'payment_url' => string|null, 'error' => string|null]
     */
    public static function create_solde_payment($parent_order_id, $amount = null) {
        if (!VS08V_Traveler_Space::current_user_can_view_order($parent_order_id)) {
            return ['success' => false, 'error' => 'Accès refusé.'];
        }

        $parent = wc_get_order($parent_order_id);
        $solde_info = VS08V_Traveler_Space::get_solde_info($parent_order_id);
        if (!$solde_info || !$solde_info['solde_due'] || $solde_info['solde'] <= 0) {
            return ['success' => false, 'error' => 'Aucun solde à régler.'];
        }

        $solde_remaining = (float) $solde_info['solde'];
        if ($amount !== null && $amount !== '') {
            $amount = (float) $amount;
            if ($amount <= 0 || $amount > $solde_remaining) {
                return ['success' => false, 'error' => 'Montant invalide (entre 0,01 € et ' . number_format($solde_remaining, 2, ',', ' ') . ' €).'];
            }
            $pay_amount = $amount;
        } else {
            $pay_amount = $solde_remaining;
        }

        $data = VS08V_Traveler_Space::get_booking_data_from_order($parent);
        $fact = $data['facturation'] ?? [];
        $email = $fact['email'] ?? $parent->get_billing_email();
        $titre = $data['voyage_titre'] ?? 'Séjour golf';

        if (!$email) {
            return ['success' => false, 'error' => 'Aucun email client trouvé pour envoyer le lien de paiement.'];
        }

        $is_partial = $pay_amount < $solde_remaining;
        $reference = 'VS08-' . $parent_order_id . '-SOLDE' . ($is_partial ? '-PARTIEL-' . time() : '-' . time());

        $result = self::create_payment_request($reference, $pay_amount, $email, true);

        if (!$result['success']) {
            return ['success' => false, 'error' => $result['error'] ?? 'Erreur lors de la création du lien Paybox Mail.'];
        }

        $pbm_data = $result['data'];
        $payment_url = $pbm_data['payment_url'] ?? ($pbm_data['url'] ?? '');

        // Stocker la demande en meta de la commande parente
        $pending_requests = $parent->get_meta('_vs08v_paybox_mail_pending');
        if (!is_array($pending_requests)) $pending_requests = [];
        $pending_requests[] = [
            'request_id' => $pbm_data['id'] ?? '',
            'reference'  => $reference,
            'amount'     => $pay_amount,
            'email'      => $email,
            'date'       => current_time('Y-m-d H:i:s'),
            'payment_url' => $payment_url,
        ];
        $parent->update_meta_data('_vs08v_paybox_mail_pending', $pending_requests);
        $parent->add_order_note(sprintf(
            'Lien de paiement Paybox Mail créé — %.2f € — Réf: %s — Envoyé à %s',
            $pay_amount,
            $reference,
            $email
        ));
        $parent->save();

        $message = 'Un lien de paiement de ' . number_format($pay_amount, 2, ',', ' ') . ' € a été envoyé à ' . $email . '. Vérifiez votre boîte de réception (et vos spams).';

        return [
            'success'     => true,
            'message'     => $message,
            'payment_url' => $payment_url,
            'amount'      => $pay_amount,
            'email'       => $email,
            'error'       => null,
        ];
    }
}
