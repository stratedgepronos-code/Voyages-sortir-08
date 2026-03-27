<?php
if (!defined('ABSPATH')) exit;

class VS08C_Emails {

    const ADMIN_RECIPIENTS = [
        'sortir08.ag@wanadoo.fr',
        'sortir08@wanadoo.fr',
    ];

    public static function dispatch($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        if ($order->get_meta('_vs08c_emails_sent')) return;

        $data = VS08C_Contract::get_booking_data($order_id);
        if (!$data || ($data['type'] ?? '') !== 'circuit') {
            error_log('[VS08C Emails] dispatch(' . $order_id . ') — pas de booking_data circuit');
            return;
        }

        $order->update_meta_data('_vs08c_booking_data', $data);
        $order->save();

        $contract_html = VS08C_Contract::generate($order_id);

        self::send_admin($order_id, $order, $data, $contract_html ?: '');
        self::send_client($order_id, $order, $data, $contract_html ?: '');

        // Flag posé APRÈS l'envoi
        $order->update_meta_data('_vs08c_emails_sent', current_time('mysql'));
        $order->save();
        error_log('[VS08C Emails] dispatch(' . $order_id . ') — emails envoyés OK');
    }

    private static function send_admin($order_id, $order, $data, $contract_html) {
        $fact   = $data['facturation'] ?? [];
        $params = $data['params'] ?? [];
        $devis  = $data['devis'] ?? [];
        $titre  = $data['circuit_titre'] ?? 'Circuit';
        $client = trim(($fact['prenom'] ?? '') . ' ' . strtoupper($fact['nom'] ?? ''));
        $total  = number_format(floatval($data['total'] ?? 0), 2, ',', ' ');
        $nb     = intval($devis['nb_total'] ?? 1);

        $subject = sprintf('🗺️ Nouvelle résa Circuit VS08-%d — %s — %s', $order_id, $titre, $client);

        $body = self::email_wrapper($subject,
            '<div style="padding:24px 32px;">'
            . '<h2 style="margin:0 0 16px;color:#1a3a3a;font-family:Georgia,serif;">🗺️ Nouvelle réservation Circuit</h2>'
            . '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-size:14px;width:100%;">'
            . self::tr('N° contrat', 'VS08-' . $order_id)
            . self::tr('Client', esc_html($client))
            . self::tr('Email', esc_html($fact['email'] ?? ''))
            . self::tr('Tél.', esc_html($fact['tel'] ?? ''))
            . self::tr('Circuit', esc_html($titre))
            . self::tr('Destination', esc_html($params['aeroport'] ?? ''))
            . self::tr('Date départ', esc_html(!empty($params['date_depart']) ? date('d/m/Y', strtotime($params['date_depart'])) : ''))
            . self::tr('Voyageurs', $nb . ' personne(s)')
            . self::tr('Total', '<strong>' . $total . ' €</strong>')
            . '</table>'
            . self::voyageurs_table($data['voyageurs'] ?? [])
            . self::prix_detail_table($devis, $data)
            . '<p style="margin-top:16px;"><a href="' . esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')) . '" style="display:inline-block;padding:10px 24px;background:#2a7f7f;color:#fff;text-decoration:none;border-radius:6px;font-weight:bold;">Voir la commande dans WordPress</a></p>'
            . '</div>'
            . (empty($contract_html) ? '' : '<div style="border-top:3px solid #2a7f7f;margin-top:8px;"></div>' . $contract_html)
        );

        self::send(self::ADMIN_RECIPIENTS, $subject, $body);
    }

    private static function send_client($order_id, $order, $data, $contract_html) {
        $fact  = $data['facturation'] ?? [];
        $email = $fact['email'] ?? '';
        if (!$email || !is_email($email)) return;

        $titre   = $data['circuit_titre'] ?? 'Circuit';
        $prenom  = $fact['prenom'] ?? 'Cher voyageur';
        $params  = $data['params'] ?? [];
        $devis   = $data['devis'] ?? [];
        $total   = number_format(floatval($data['total'] ?? 0), 2, ',', ' ');
        $m       = VS08C_Meta::get($data['circuit_id'] ?? 0);
        $duree_j = intval($m['duree_jours'] ?? (intval($m['duree'] ?? 7) + 1));
        $duree_n = intval($m['duree'] ?? 7);

        $subject = sprintf('Votre réservation — %s — Voyages Sortir 08', $titre);

        $body = self::email_wrapper($subject,
            '<div style="padding:32px;">'
            . '<h1 style="margin:0 0 8px;color:#1a3a3a;font-family:Georgia,serif;font-size:24px;">Merci ' . esc_html($prenom) . ' !</h1>'
            . '<p style="font-size:16px;color:#555;margin:0 0 24px;">Votre réservation pour le circuit <strong>' . esc_html($titre) . '</strong> a bien été enregistrée. Vous trouverez ci-dessous votre contrat de vente.</p>'
            . '<table cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%;background:#edf8f8;border-radius:8px;">'
            . '<tr><td style="padding:12px 16px;font-weight:bold;color:#1a3a3a;">Circuit</td><td style="padding:12px 16px;">' . esc_html($titre) . '</td></tr>'
            . '<tr><td style="padding:12px 16px;font-weight:bold;color:#1a3a3a;">Date de départ</td><td style="padding:12px 16px;">' . esc_html(!empty($params['date_depart']) ? date('d/m/Y', strtotime($params['date_depart'])) : '') . '</td></tr>'
            . '<tr><td style="padding:12px 16px;font-weight:bold;color:#1a3a3a;">Durée</td><td style="padding:12px 16px;">' . $duree_j . ' jours / ' . $duree_n . ' nuits</td></tr>'
            . '<tr><td style="padding:12px 16px;font-weight:bold;color:#1a3a3a;">N° contrat</td><td style="padding:12px 16px;">VS08-' . $order_id . '</td></tr>'
            . '<tr style="font-weight:bold;font-size:16px;"><td style="padding:12px 16px;color:#1a3a3a;">Total</td><td style="padding:12px 16px;color:#e8724a;">' . $total . ' €</td></tr>'
            . '</table>'
            . self::prix_detail_table($devis, $data)
            . '<p style="margin-top:24px;color:#6b7280;font-size:13px;line-height:1.6;">Un conseiller prendra contact avec vous sous 24h pour finaliser votre dossier.<br>Pour toute question : <a href="tel:0326652863" style="color:#3d9a9a">03 26 65 28 63</a> ou <a href="mailto:sortir08.ag@wanadoo.fr" style="color:#3d9a9a">sortir08.ag@wanadoo.fr</a></p>'
            . '</div>'
            . (empty($contract_html) ? '' : '<div style="border-top:3px solid #2a7f7f;margin-top:8px;"></div><div style="padding:16px 32px;text-align:center;font-size:12px;color:#888;">Votre contrat de vente ci-dessous :</div>' . $contract_html)
        );

        self::send([$email], $subject, $body);
    }

    private static function voyageurs_table($voyageurs) {
        if (empty($voyageurs)) return '';
        $html = '<h3 style="margin:20px 0 8px;color:#1a3a3a;font-size:14px;">Voyageurs</h3>';
        $html .= '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-size:13px;width:100%;">';
        $html .= '<tr style="background:#1a3a3a;color:#fff;font-size:11px;"><th style="padding:6px 8px;text-align:left;">Nom</th><th style="padding:6px 8px;text-align:left;">Prénom</th><th style="padding:6px 8px;text-align:left;">DDN</th><th style="padding:6px 8px;text-align:left;">Chambre</th></tr>';
        foreach ($voyageurs as $i => $v) {
            $bg = ($i % 2 === 0) ? '#f8f8f8' : '#fff';
            $ddn = $v['ddn'] ?? '';
            if ($ddn && preg_match('/^\d{4}-\d{2}-\d{2}$/', $ddn)) {
                $d = DateTime::createFromFormat('Y-m-d', $ddn);
                if ($d) $ddn = $d->format('d/m/Y');
            }
            $html .= '<tr style="background:' . $bg . ';">';
            $html .= '<td style="padding:6px 8px;border:1px solid #e0e0e0;font-weight:bold;">' . esc_html(strtoupper($v['nom'] ?? '')) . '</td>';
            $html .= '<td style="padding:6px 8px;border:1px solid #e0e0e0;">' . esc_html($v['prenom'] ?? '') . '</td>';
            $html .= '<td style="padding:6px 8px;border:1px solid #e0e0e0;">' . esc_html($ddn ?: '—') . '</td>';
            $html .= '<td style="padding:6px 8px;border:1px solid #e0e0e0;">Ch.' . intval($v['chambre'] ?? 1) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }

    private static function prix_detail_table($devis, $data) {
        $html = '<h3 style="margin:20px 0 8px;color:#1a3a3a;font-size:14px;">Détail du prix</h3>';
        $html .= '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-size:13px;width:100%;">';
        foreach ($devis['lines'] ?? [] as $line) {
            $html .= '<tr><td style="padding:4px 8px;border-bottom:1px solid #eee;">' . esc_html($line['label']) . '</td><td style="padding:4px 8px;border-bottom:1px solid #eee;text-align:right">' . number_format($line['montant'], 2, ',', ' ') . ' €</td></tr>';
        }
        foreach ($data['options'] ?? [] as $opt) {
            if (!is_array($opt)) continue;
            $html .= '<tr><td style="padding:4px 8px;border-bottom:1px solid #eee;">Option : ' . esc_html($opt['label'] ?? '') . '</td><td style="padding:4px 8px;border-bottom:1px solid #eee;text-align:right">' . number_format(floatval($opt['prix'] ?? 0), 2, ',', ' ') . ' €</td></tr>';
        }
        if (($data['assurance'] ?? 0) > 0) {
            $html .= '<tr><td style="padding:4px 8px;border-bottom:1px solid #eee;">🛡️ Assurance Multirisques</td><td style="padding:4px 8px;border-bottom:1px solid #eee;text-align:right">' . number_format($data['assurance'], 2, ',', ' ') . ' €</td></tr>';
        }
        $html .= '</table>';
        return $html;
    }

    private static function tr($label, $value) {
        return '<tr><td style="padding:8px 10px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;width:35%">' . $label . '</td><td style="padding:8px 10px;border:1px solid #e0e0e0">' . $value . '</td></tr>';
    }

    private static function email_wrapper($title, $content) {
        $c = VS08C_Contract::COMPANY;
        $logo = 'https://sortirmonde.fr/wp-content/themes/vs08-theme/assets/img/logo.png';
        return '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . esc_html($title) . '</title></head>'
            . '<body style="margin:0;padding:0;background:#f4f1ea;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#222;">'
            . '<div style="max-width:800px;margin:20px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)">'
            . '<div style="background:linear-gradient(135deg,#0f2424,#1a4a4a);padding:28px 32px;text-align:center">'
            . '<img src="' . $logo . '" alt="Voyages Sortir 08" style="height:50px;margin-bottom:8px"><br>'
            . '<span style="color:rgba(255,255,255,.5);font-size:12px;letter-spacing:1px">SPÉCIALISTE VOYAGES DEPUIS 20 ANS</span>'
            . '</div>'
            . $content
            . '<div style="background:#f9f6f0;padding:20px 32px;text-align:center;font-size:11px;color:#999;line-height:1.5">'
            . $c['legal'] . ' — ' . $c['address'] . ' ' . $c['city'] . ' — Capital ' . $c['capital'] . ' € — RCS ' . $c['rcs'] . '<br>'
            . 'Immat. ' . $c['immat'] . ' — Garantie : ' . $c['garantie'] . ' — ' . $c['email']
            . '</div></div></body></html>';
    }

    private static function send($to, $subject, $html) {
        $headers = ['Content-Type: text/html; charset=UTF-8', 'From: Voyages Sortir 08 <noreply@sortirmonde.fr>'];
        $result = wp_mail($to, $subject, $html, $headers);
        $dest = is_array($to) ? implode(', ', $to) : $to;
        error_log('[VS08C Emails] wp_mail to ' . $dest . ' => ' . ($result ? 'OK' : 'FAIL') . ' — ' . $subject);
    }
}
