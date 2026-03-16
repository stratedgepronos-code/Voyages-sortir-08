<?php
if (!defined('ABSPATH')) exit;

class VS08C_Emails {

    const ADMIN_RECIPIENTS = [
        'sortir08.ag@wanadoo.fr',
        'sortir08@wanadoo.fr',
    ];

    /**
     * Dispatch emails for a circuit booking order.
     */
    public static function dispatch($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        if ($order->get_meta('_vs08c_emails_sent')) return;

        // Chercher booking_data circuit
        $data = $order->get_meta('_vs08c_booking_data');
        if (empty($data)) {
            foreach ($order->get_items() as $item) {
                $data = $item->get_meta('_vs08c_booking_data');
                if (!empty($data)) break;
            }
        }
        if (empty($data) || ($data['type'] ?? '') !== 'circuit') return;

        $order->update_meta_data('_vs08c_booking_data', $data);
        $order->update_meta_data('_vs08c_emails_sent', current_time('mysql'));
        $order->save();

        self::send_admin($order_id, $order, $data);
        self::send_client($order_id, $order, $data);
    }

    /**
     * Email admin avec résumé complet.
     */
    private static function send_admin($order_id, $order, $data) {
        $fact   = $data['facturation'] ?? [];
        $params = $data['params'] ?? [];
        $devis  = $data['devis'] ?? [];
        $titre  = $data['circuit_titre'] ?? 'Circuit';
        $client = trim(($fact['prenom'] ?? '') . ' ' . strtoupper($fact['nom'] ?? ''));
        $total  = number_format(floatval($data['total'] ?? 0), 2, ',', ' ');

        $subject = sprintf('🗺️ Nouvelle résa Circuit VS08-%d — %s — %s', $order_id, $titre, $client);

        $rows = '';
        $rows .= self::tr('N° contrat', 'VS08-' . $order_id);
        $rows .= self::tr('Type', '🗺️ Circuit');
        $rows .= self::tr('Client', esc_html($client));
        $rows .= self::tr('Email', esc_html($fact['email'] ?? ''));
        $rows .= self::tr('Tél.', esc_html($fact['tel'] ?? ''));
        $rows .= self::tr('Circuit', esc_html($titre));
        $rows .= self::tr('Destination', esc_html(($data['params']['aeroport'] ?? '') ? 'Départ ' . strtoupper($data['params']['aeroport']) : ''));
        $rows .= self::tr('Date départ', esc_html($params['date_depart'] ?? ''));
        $rows .= self::tr('Voyageurs', ($devis['nb_total'] ?? 0) . ' pers. (' . intval($params['nb_adultes'] ?? 0) . ' adulte(s), ' . intval($params['nb_enfants'] ?? 0) . ' enfant(s))');
        $rows .= self::tr('Total', '<strong>' . $total . ' €</strong>');

        // Détail prix
        $detail = '';
        foreach ($devis['lines'] ?? [] as $line) {
            $detail .= '<tr><td style="padding:4px 8px;border-bottom:1px solid #eee;font-size:13px">' . esc_html($line['label']) . '<br><small style="color:#888">' . esc_html($line['detail'] ?? '') . '</small></td><td style="padding:4px 8px;border-bottom:1px solid #eee;text-align:right;font-size:13px">' . number_format($line['montant'], 2, ',', ' ') . ' €</td></tr>';
        }
        foreach ($data['options'] ?? [] as $opt) {
            $detail .= '<tr><td style="padding:4px 8px;border-bottom:1px solid #eee;font-size:13px">Option: ' . esc_html($opt['label']) . '</td><td style="padding:4px 8px;border-bottom:1px solid #eee;text-align:right;font-size:13px">' . number_format($opt['prix'], 2, ',', ' ') . ' €</td></tr>';
        }

        // Voyageurs
        $voy_rows = '';
        foreach ($data['voyageurs'] ?? [] as $i => $v) {
            $voy_rows .= '<tr><td style="padding:4px 8px;border-bottom:1px solid #eee;font-size:13px">' . ($i + 1) . '. ' . esc_html($v['prenom'] . ' ' . strtoupper($v['nom'])) . '</td><td style="padding:4px 8px;font-size:13px">' . esc_html($v['type'] ?? 'adulte') . '</td><td style="padding:4px 8px;font-size:13px">Ch.' . intval($v['chambre'] ?? 1) . '</td></tr>';
        }

        $body = self::wrap($subject,
            '<div style="padding:24px 32px">'
            . '<h2 style="margin:0 0 16px;color:#1a3a3a;font-family:Georgia,serif">🗺️ Nouvelle réservation Circuit</h2>'
            . '<table cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%">' . $rows . '</table>'
            . '<h3 style="margin:24px 0 8px;font-size:15px;color:#1a3a3a">Détail du prix</h3>'
            . '<table cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%">' . $detail . '</table>'
            . ($voy_rows ? '<h3 style="margin:24px 0 8px;font-size:15px;color:#1a3a3a">Voyageurs</h3><table cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%">' . $voy_rows . '</table>' : '')
            . '<p style="margin-top:20px"><a href="' . esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')) . '" style="display:inline-block;padding:10px 24px;background:#2a7f7f;color:#fff;text-decoration:none;border-radius:6px;font-weight:bold">Voir la commande</a></p>'
            . '</div>'
        );

        self::send(self::ADMIN_RECIPIENTS, $subject, $body);
    }

    /**
     * Email client confirmation.
     */
    private static function send_client($order_id, $order, $data) {
        $fact  = $data['facturation'] ?? [];
        $email = $fact['email'] ?? '';
        if (!$email || !is_email($email)) return;

        $titre = $data['circuit_titre'] ?? 'Circuit';
        $prenom = $fact['prenom'] ?? '';
        $params = $data['params'] ?? [];
        $devis  = $data['devis'] ?? [];
        $m      = VS08C_Meta::get($data['circuit_id'] ?? 0);

        $subject = sprintf('Confirmation réservation — %s — VS08-%d', $titre, $order_id);

        // Détail prix
        $detail = '';
        foreach ($devis['lines'] ?? [] as $line) {
            $detail .= '<tr><td style="padding:6px 10px;border-bottom:1px solid #eee;font-size:13px">' . esc_html($line['label']) . '</td><td style="padding:6px 10px;border-bottom:1px solid #eee;text-align:right;font-size:13px">' . number_format($line['montant'], 2, ',', ' ') . ' €</td></tr>';
        }
        foreach ($data['options'] ?? [] as $opt) {
            $detail .= '<tr><td style="padding:6px 10px;border-bottom:1px solid #eee;font-size:13px">' . esc_html($opt['label']) . '</td><td style="padding:6px 10px;border-bottom:1px solid #eee;text-align:right;font-size:13px">' . number_format($opt['prix'], 2, ',', ' ') . ' €</td></tr>';
        }

        $total = number_format(floatval($data['total'] ?? 0), 2, ',', ' ');
        $duree_txt = ($m['duree_jours'] ?? 8) . ' jours / ' . ($m['duree'] ?? 7) . ' nuits';

        $body = self::wrap($subject,
            '<div style="padding:32px">'
            . '<h2 style="margin:0 0 8px;color:#1a3a3a;font-family:Georgia,serif">Merci ' . esc_html($prenom) . ' !</h2>'
            . '<p style="color:#4a5568;font-size:15px;line-height:1.6">Votre réservation pour le circuit <strong>' . esc_html($titre) . '</strong> a bien été enregistrée.</p>'
            . '<div style="background:#edf8f8;border-radius:12px;padding:20px;margin:20px 0">'
            . '<table cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%">'
            . '<tr><td style="padding:6px 0;font-size:14px;color:#1a3a3a"><strong>N° dossier</strong></td><td style="padding:6px 0;font-size:14px;text-align:right">VS08-' . $order_id . '</td></tr>'
            . '<tr><td style="padding:6px 0;font-size:14px"><strong>Circuit</strong></td><td style="padding:6px 0;font-size:14px;text-align:right">' . esc_html($titre) . '</td></tr>'
            . '<tr><td style="padding:6px 0;font-size:14px"><strong>Date de départ</strong></td><td style="padding:6px 0;font-size:14px;text-align:right">' . esc_html(date('d/m/Y', strtotime($params['date_depart'] ?? 'now'))) . '</td></tr>'
            . '<tr><td style="padding:6px 0;font-size:14px"><strong>Durée</strong></td><td style="padding:6px 0;font-size:14px;text-align:right">' . esc_html($duree_txt) . '</td></tr>'
            . '<tr><td style="padding:6px 0;font-size:14px"><strong>Voyageurs</strong></td><td style="padding:6px 0;font-size:14px;text-align:right">' . intval($devis['nb_total'] ?? 0) . ' pers.</td></tr>'
            . '<tr style="border-top:2px solid #59b7b7"><td style="padding:10px 0;font-size:16px;font-weight:bold;color:#1a3a3a">Total</td><td style="padding:10px 0;font-size:16px;font-weight:bold;text-align:right;color:#3d9a9a">' . $total . ' €</td></tr>'
            . '</table></div>'
            . '<h3 style="font-size:15px;color:#1a3a3a;margin:24px 0 8px">Détail du prix</h3>'
            . '<table cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%">' . $detail . '</table>'
            . '<p style="margin-top:24px;color:#6b7280;font-size:13px;line-height:1.6">Un conseiller prendra contact avec vous sous 24h pour finaliser votre dossier.<br>Pour toute question : <a href="tel:0326652863" style="color:#3d9a9a">03 26 65 28 63</a> ou <a href="mailto:resa@voyagessortir08.com" style="color:#3d9a9a">resa@voyagessortir08.com</a></p>'
            . '</div>'
        );

        self::send([$email], $subject, $body);
    }

    /* ─── Helpers ─── */

    private static function tr($label, $value) {
        return '<tr><td style="padding:8px 10px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;width:35%;font-size:13px">' . $label . '</td><td style="padding:8px 10px;border:1px solid #e0e0e0;font-size:13px">' . $value . '</td></tr>';
    }

    private static function wrap($title, $content) {
        $logo = 'https://sortirmonde.fr/wp-content/themes/vs08-theme/assets/img/logo.png';
        return '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="margin:0;padding:0;background:#f4f1ea;font-family:Arial,sans-serif">'
            . '<div style="max-width:680px;margin:24px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)">'
            . '<div style="background:linear-gradient(135deg,#0f2424,#1a4a4a);padding:28px 32px;text-align:center">'
            . '<img src="' . $logo . '" alt="Voyages Sortir 08" style="height:50px;margin-bottom:8px"><br>'
            . '<span style="color:rgba(255,255,255,.5);font-size:12px;letter-spacing:1px">SPÉCIALISTE VOYAGES DEPUIS 20 ANS</span>'
            . '</div>'
            . $content
            . '<div style="background:#f9f6f0;padding:20px 32px;text-align:center;font-size:11px;color:#999">'
            . 'Voyages Sortir 08 — 24 rue Léon Bourgeois, 51000 Châlons-en-Champagne<br>'
            . '03 26 65 28 63 · resa@voyagessortir08.com · APST · Atout France IM051100014'
            . '</div></div></body></html>';
    }

    private static function send($to, $subject, $html) {
        $headers = ['Content-Type: text/html; charset=UTF-8', 'From: Voyages Sortir 08 <resa@voyagessortir08.com>'];
        wp_mail($to, $subject, $html, $headers);
    }
}
