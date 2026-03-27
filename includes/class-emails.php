<?php
if (!defined('ABSPATH')) exit;

class VS08V_Emails {

    const ADMIN_RECIPIENTS = [
        'sortir08.ag@wanadoo.fr',
        'sortir08@wanadoo.fr',
    ];

    /**
     * Point d'entrée : envoie les emails admin + client pour une commande.
     * Ne s'exécute qu'une fois par commande (flag _vs08v_emails_sent).
     */
    public static function dispatch($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;

        if ($order->get_meta('_vs08v_emails_sent')) return;

        $data = VS08V_Contract::get_booking_data($order_id);
        if (!$data) {
            error_log('[VS08 Emails] dispatch(' . $order_id . ') — pas de booking_data');
            return;
        }

        // Copier les booking_data sur la commande pour accès futur
        $order->update_meta_data('_vs08v_booking_data', $data);
        $order->save();

        $contract_html = VS08V_Contract::generate($order_id);
        if (empty($contract_html)) {
            error_log('[VS08 Emails] dispatch(' . $order_id . ') — contrat vide');
        }

        self::send_admin_notification($order_id, $order, $data, $contract_html ?: '');
        self::send_client_confirmation($order_id, $order, $data, $contract_html ?: '');

        // Flag posé APRÈS l'envoi
        $order->update_meta_data('_vs08v_emails_sent', current_time('mysql'));
        $order->save();
        error_log('[VS08 Emails] dispatch(' . $order_id . ') — emails envoyés OK');
    }

    /**
     * Email aux 2 administrateurs avec résumé + contrat complet.
     */
    private static function send_admin_notification($order_id, $order, $data, $contract_html) {
        $fact   = $data['facturation'] ?? [];
        $params = $data['params'] ?? [];
        $devis  = $data['devis'] ?? [];
        $titre  = $data['voyage_titre'] ?? 'Séjour golf';
        $client = trim(($fact['prenom'] ?? '') . ' ' . strtoupper($fact['nom'] ?? ''));
        $nb     = intval($devis['nb_total'] ?? 1);
        $total  = number_format(floatval($data['total'] ?? 0), 2, ',', ' ');

        $subject = sprintf(
            'Nouvelle réservation VS08-%d — %s — %s',
            $order_id,
            $titre,
            $client
        );

        $body = self::email_wrapper(
            $subject,
            '<div style="padding:24px 32px;">'
            . '<h2 style="margin:0 0 16px;color:#1a3a3a;font-family:Georgia,serif;">Nouvelle réservation</h2>'
            . '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-size:14px;width:100%;">'
            . '<tr><td style="padding:6px 10px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;width:35%;">N° contrat</td><td style="padding:6px 10px;border:1px solid #e0e0e0;">VS08-' . $order_id . '</td></tr>'
            . '<tr><td style="padding:6px 10px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">Client</td><td style="padding:6px 10px;border:1px solid #e0e0e0;">' . esc_html($client) . '</td></tr>'
            . '<tr><td style="padding:6px 10px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">Email</td><td style="padding:6px 10px;border:1px solid #e0e0e0;">' . esc_html($fact['email'] ?? '') . '</td></tr>'
            . '<tr><td style="padding:6px 10px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">Tél.</td><td style="padding:6px 10px;border:1px solid #e0e0e0;">' . esc_html($fact['tel'] ?? '') . '</td></tr>'
            . '<tr><td style="padding:6px 10px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">Voyage</td><td style="padding:6px 10px;border:1px solid #e0e0e0;">' . esc_html($titre) . '</td></tr>'
            . '<tr><td style="padding:6px 10px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">Date de départ</td><td style="padding:6px 10px;border:1px solid #e0e0e0;">' . esc_html($params['date_depart'] ?? '') . '</td></tr>'
            . '<tr><td style="padding:6px 10px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">Voyageurs</td><td style="padding:6px 10px;border:1px solid #e0e0e0;">' . $nb . ' personne(s)</td></tr>'
            . '<tr style="font-weight:bold;"><td style="padding:6px 10px;border:1px solid #e0e0e0;background:#edf8f8;">Total</td><td style="padding:6px 10px;border:1px solid #e0e0e0;background:#edf8f8;font-size:16px;">' . $total . ' &euro;</td></tr>'
            . '</table>'
            . self::voyageurs_table($data['voyageurs'] ?? [])
            . '<p style="margin-top:16px;"><a href="' . esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')) . '" style="display:inline-block;padding:10px 24px;background:#2a7f7f;color:#fff;text-decoration:none;border-radius:6px;font-weight:bold;">Voir la commande dans WordPress</a></p>'
            . '</div>'
            . '<div style="border-top:3px solid #2a7f7f;margin-top:8px;"></div>'
            . $contract_html
        );

        self::send(self::ADMIN_RECIPIENTS, $subject, $body);
    }

    /**
     * Email au client avec message de bienvenue + contrat complet.
     */
    private static function send_client_confirmation($order_id, $order, $data, $contract_html) {
        $fact  = $data['facturation'] ?? [];
        $email = $fact['email'] ?? '';
        if (empty($email) || !is_email($email)) return;

        $titre  = $data['voyage_titre'] ?? 'Séjour golf';
        $prenom = $fact['prenom'] ?? 'Cher voyageur';
        $params = $data['params'] ?? [];
        $total  = number_format(floatval($data['total'] ?? 0), 2, ',', ' ');
        $c      = VS08V_Contract::COMPANY;

        $subject = sprintf('Votre réservation — %s — Voyages Sortir 08', $titre);

        $account_url = class_exists('VS08V_Traveler_Space') ? VS08V_Traveler_Space::voyage_url($order_id) : home_url('/espace-voyageur/');

        $body = self::email_wrapper(
            $subject,
            '<div style="padding:32px;">'
            . '<h1 style="margin:0 0 8px;color:#1a3a3a;font-family:Georgia,serif;font-size:24px;">Merci ' . esc_html($prenom) . ' !</h1>'
            . '<p style="font-size:16px;color:#555;margin:0 0 24px;">Votre réservation a bien été enregistrée. Vous trouverez ci-dessous votre contrat de vente.</p>'
            . '<table cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%;background:#edf8f8;border-radius:8px;">'
            . '<tr><td style="padding:12px 16px;font-weight:bold;color:#1a3a3a;">Voyage</td><td style="padding:12px 16px;">' . esc_html($titre) . '</td></tr>'
            . '<tr><td style="padding:12px 16px;font-weight:bold;color:#1a3a3a;">Date de départ</td><td style="padding:12px 16px;">' . esc_html($params['date_depart'] ? date('d/m/Y', strtotime($params['date_depart'])) : '') . '</td></tr>'
            . '<tr><td style="padding:12px 16px;font-weight:bold;color:#1a3a3a;">N° contrat</td><td style="padding:12px 16px;">VS08-' . $order_id . '</td></tr>'
            . '<tr style="font-weight:bold;font-size:16px;"><td style="padding:12px 16px;color:#1a3a3a;">Total</td><td style="padding:12px 16px;color:#e8724a;">' . $total . ' &euro;</td></tr>'
            . '</table>'
            . '<div style="text-align:center;margin:24px 0;">'
            . '<a href="' . esc_url($account_url) . '" style="display:inline-block;padding:14px 32px;background:#2a7f7f;color:#fff;text-decoration:none;border-radius:8px;font-weight:bold;font-size:15px;">Voir mon voyage dans l\'espace voyageur</a>'
            . '</div>'
            . '<p style="font-size:13px;color:#888;">Pour toute question, contactez-nous au <strong>' . $c['tel'] . '</strong> ou par email à <a href="mailto:' . $c['email'] . '" style="color:#2a7f7f;">' . $c['email'] . '</a>.</p>'
            . '</div>'
            . '<div style="border-top:3px solid #2a7f7f;margin-top:8px;"></div>'
            . '<div style="padding:16px 32px;text-align:center;font-size:12px;color:#888;">Votre contrat de vente ci-dessous :</div>'
            . $contract_html
        );

        self::send([$email], $subject, $body);
    }

    /**
     * Tableau des voyageurs (HTML).
     */
    private static function voyageurs_table($voyageurs) {
        if (empty($voyageurs)) return '';
        $html = '<h3 style="margin:20px 0 8px;color:#1a3a3a;font-size:14px;">Voyageurs</h3>';
        $html .= '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-size:13px;width:100%;">';
        $html .= '<tr style="background:#1a3a3a;color:#fff;font-size:11px;"><th style="padding:6px 8px;text-align:left;">Nom</th><th style="padding:6px 8px;text-align:left;">Prénom</th><th style="padding:6px 8px;text-align:left;">DDN</th><th style="padding:6px 8px;text-align:left;">Type</th></tr>';
        foreach ($voyageurs as $i => $v) {
            $bg = ($i % 2 === 0) ? '#f8f8f8' : '#fff';
            $html .= '<tr style="background:' . $bg . ';">';
            $html .= '<td style="padding:6px 8px;border:1px solid #e0e0e0;font-weight:bold;">' . esc_html(strtoupper($v['nom'] ?? '')) . '</td>';
            $html .= '<td style="padding:6px 8px;border:1px solid #e0e0e0;">' . esc_html($v['prenom'] ?? '') . '</td>';
            $html .= '<td style="padding:6px 8px;border:1px solid #e0e0e0;">' . esc_html($v['ddn'] ?? '') . '</td>';
            $html .= '<td style="padding:6px 8px;border:1px solid #e0e0e0;">' . (($v['type'] ?? '') === 'golfeur' ? 'Golfeur' : 'Accompagnant') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }

    /**
     * Enveloppe HTML pour tous les emails (responsive, inline CSS).
     */
    private static function email_wrapper($title, $inner_html) {
        $c = VS08V_Contract::COMPANY;
        return '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . esc_html($title) . '</title></head>'
            . '<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#222;">'
            . '<div style="max-width:800px;margin:20px auto;background:#fff;border:1px solid #d0d0d0;border-radius:8px;overflow:hidden;">'
            . '<div style="background:#1a3a3a;color:#fff;padding:20px 32px;text-align:center;">'
            . '<div style="font-size:22px;font-weight:bold;font-family:Georgia,serif;letter-spacing:1px;">' . $c['name'] . '</div>'
            . '<div style="font-size:12px;margin-top:4px;color:#b0cece;">' . $c['address'] . ' — ' . $c['city'] . ' — ' . $c['tel'] . '</div>'
            . '</div>'
            . $inner_html
            . '<div style="background:#1a3a3a;color:#b0cece;padding:14px 32px;font-size:10px;text-align:center;line-height:1.5;">'
            . $c['legal'] . ' — Capital ' . $c['capital'] . ' &euro; — RCS ' . $c['rcs'] . ' — Immat. ' . $c['immat'] . '<br>'
            . 'Garantie : ' . $c['garantie'] . ' — ' . $c['email']
            . '</div>'
            . '</div>'
            . '</body></html>';
    }

    /**
     * Envoi effectif via wp_mail (HTML).
     */
    private static function send($recipients, $subject, $html_body) {
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: Voyages Sortir 08 <noreply@sortirmonde.fr>',
        ];
        $result = wp_mail($recipients, $subject, $html_body, $headers);
        $to = is_array($recipients) ? implode(', ', $recipients) : $recipients;
        error_log('[VS08 Emails] wp_mail to ' . $to . ' => ' . ($result ? 'OK' : 'FAIL') . ' — ' . $subject);
    }

    /**
     * Rappel solde : envoie un email au client J-14 et J-3 avant l'échéance.
     * Appelé par le cron quotidien vs08v_solde_reminder.
     */
    public static function run_solde_reminders() {
        $orders = wc_get_orders([
            'limit'    => -1,
            'status'   => array_keys(wc_get_order_statuses()),
            'return'   => 'ids',
        ]);
        $today = date('Y-m-d');
        foreach ($orders as $order_id) {
            $order = wc_get_order($order_id);
            if (!$order) {
                continue;
            }
            $data = $order->get_meta('_vs08v_booking_data');
            if (empty($data) || !is_array($data)) {
                continue;
            }
            $solde_info = VS08V_Traveler_Space::get_solde_info($order_id);
            if (!$solde_info || !$solde_info['solde_due'] || $solde_info['solde'] <= 0) {
                continue;
            }
            $params     = $data['params'] ?? [];
            $date_depart = $params['date_depart'] ?? '';
            if (!$date_depart) {
                continue;
            }
            $voyage_id  = (int) ($data['voyage_id'] ?? 0);
            $m          = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($voyage_id) : [];
            $delai_solde = (int) ($m['delai_solde'] ?? 30);
            $deadline_ts = strtotime($date_depart) - ($delai_solde * 86400);
            $deadline_ymd = date('Y-m-d', $deadline_ts);
            $days_left = (strtotime($deadline_ymd) - strtotime($today)) / 86400;
            if ($days_left < 0) {
                continue;
            }
            $customer_id = $order->get_customer_id();
            if (!$customer_id) {
                continue;
            }
            $user = get_userdata($customer_id);
            $email = $user ? $user->user_email : $order->get_billing_email();
            if (!$email) {
                continue;
            }
            $titre = $data['voyage_titre'] ?? 'Séjour golf';
            $solde_fmt = number_format($solde_info['solde'], 2, ',', ' ');
            $solde_date_fmt = $solde_info['solde_date'];
            $espace_url = VS08V_Traveler_Space::base_url();
            $body_tpl = self::email_wrapper(
                'Rappel : solde à régler — ' . $titre,
                '<div style="padding:24px 32px;">'
                . '<h2 style="margin:0 0 16px;color:#1a3a3a;font-family:Georgia,serif;">Rappel : solde à régler</h2>'
                . '<p style="font-size:15px;line-height:1.6;color:#333;">Bonjour,</p>'
                . '<p style="font-size:15px;line-height:1.6;color:#333;">Pour votre séjour <strong>' . esc_html($titre) . '</strong>, il reste un solde de <strong>' . $solde_fmt . ' €</strong> à régler.'
                . ($solde_date_fmt ? ' La date limite de paiement est le <strong>' . esc_html($solde_date_fmt) . '</strong>.' : '') . '</p>'
                . '<p style="font-size:15px;line-height:1.6;color:#333;">Vous pouvez régler ce solde directement depuis votre espace voyageur (paiement par carte sécurisé ou en agence).</p>'
                . '<p style="margin-top:24px;"><a href="' . esc_url($espace_url) . '" style="display:inline-block;padding:12px 28px;background:#2a7f7f;color:#fff;text-decoration:none;border-radius:8px;font-weight:bold;">Accéder à mon espace voyageur</a></p>'
                . '<p style="margin-top:20px;font-size:13px;color:#6b7280;">Dossier VS08-' . $order_id . '</p>'
                . '</div>'
            );
            if ($days_left <= 14 && $days_left > 3 && !$order->get_meta('_vs08v_solde_reminder_14')) {
                self::send([$email], 'Rappel : solde à régler avant le ' . $solde_date_fmt . ' — ' . $titre, $body_tpl);
                $order->update_meta_data('_vs08v_solde_reminder_14', current_time('mysql'));
                $order->save();
            } elseif ($days_left <= 3 && $days_left >= 0 && !$order->get_meta('_vs08v_solde_reminder_3')) {
                self::send([$email], 'Dernier rappel : solde à régler avant le ' . $solde_date_fmt . ' — ' . $titre, $body_tpl);
                $order->update_meta_data('_vs08v_solde_reminder_3', current_time('mysql'));
                $order->save();
            }
        }
    }
}
