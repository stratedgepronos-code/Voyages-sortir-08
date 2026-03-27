<?php
if (!defined('ABSPATH')) exit;

class VS08S_Emails {

    const ADMIN_RECIPIENTS = ['sortir08.ag@wanadoo.fr', 'sortir08@wanadoo.fr'];

    public static function dispatch($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        if ($order->get_meta('_vs08s_emails_sent')) return;

        $data = $order->get_meta('_vs08s_booking_data');
        if (empty($data) || !is_array($data)) return;

        $contract_html = '';
        try {
            $contract_html = VS08S_Contract::generate($order_id);
        } catch (\Throwable $e) {
            error_log('[VS08S Emails] Contract crash: ' . $e->getMessage());
        }

        self::send_admin($order_id, $data, $contract_html);
        self::send_client($order_id, $data, $contract_html);

        $order->update_meta_data('_vs08s_emails_sent', current_time('mysql'));
        $order->save();
        error_log('[VS08S Emails] dispatch(' . $order_id . ') OK');
    }

    private static function send_admin($order_id, $data, $contract_html) {
        $fact = $data['facturation'] ?? [];
        $params = $data['params'] ?? [];
        $devis = $data['devis'] ?? [];
        $titre = $data['sejour_titre'] ?? 'Séjour';
        $client = trim(($fact['prenom'] ?? '') . ' ' . strtoupper($fact['nom'] ?? ''));
        $total = floatval($data['total'] ?? 0);
        $acompte = floatval($data['acompte'] ?? 0);

        $subject = sprintf('🆕 Séjour VS08-%d — %s — %s — %s €', $order_id, $titre, $client, number_format($total, 0, ',', ' '));

        $tr = function($l, $v) {
            return '<tr><td style="padding:8px 12px;border:1px solid #e5e7eb;background:#f9f6f0;font-weight:600;width:200px;font-size:13px">' . $l . '</td><td style="padding:8px 12px;border:1px solid #e5e7eb;font-size:14px">' . $v . '</td></tr>';
        };

        $html = '<div style="padding:28px 32px;font-family:Arial,sans-serif">';
        $html .= '<h2 style="margin:0 0 6px;color:#0f2424;font-size:22px">🏖️ Nouveau séjour all inclusive</h2>';
        $html .= '<p style="margin:0 0 20px;color:#6b7280;font-size:13px">Dossier VS08-' . $order_id . ' · ' . date('d/m/Y H:i') . '</p>';

        $html .= '<table cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%;margin-bottom:20px">';
        $html .= $tr('Voyage', esc_html($titre));
        $html .= $tr('Départ', !empty($params['date_depart']) ? date('d/m/Y', strtotime($params['date_depart'])) : '—');
        $html .= $tr('Aéroport', esc_html(strtoupper($params['aeroport'] ?? '')));
        $html .= $tr('Voyageurs', intval($devis['nb_total'] ?? 2) . ' pers.');
        $html .= $tr('Client', esc_html($client));
        $html .= $tr('Email', '<a href="mailto:' . esc_attr($fact['email'] ?? '') . '">' . esc_html($fact['email'] ?? '') . '</a>');
        $html .= $tr('Tél.', esc_html($fact['tel'] ?? ''));
        $html .= '</table>';

        // Détail prix
        $html .= '<h3 style="color:#59b7b7;font-size:11px;text-transform:uppercase;letter-spacing:2px;margin:0 0 8px">Détail du prix</h3>';
        $html .= '<table cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%;margin-bottom:16px">';
        foreach ($devis['lines'] ?? [] as $line) {
            $html .= '<tr><td style="padding:6px 12px;border-bottom:1px solid #f0ece4;font-size:13px">' . esc_html($line['label']) . '</td><td style="padding:6px 12px;border-bottom:1px solid #f0ece4;font-weight:bold;text-align:right">' . number_format($line['montant'], 0, ',', ' ') . ' €</td></tr>';
        }
        $html .= '<tr style="background:#edf8f8;font-weight:bold"><td style="padding:10px 12px;font-size:15px">Total</td><td style="padding:10px 12px;text-align:right;font-size:18px">' . number_format($total, 2, ',', ' ') . ' €</td></tr>';
        $html .= '<tr><td style="padding:8px 12px;color:#59b7b7;font-weight:600">Acompte payé</td><td style="padding:8px 12px;text-align:right;color:#59b7b7;font-weight:bold">' . number_format($acompte, 2, ',', ' ') . ' €</td></tr>';
        if (!$data['payer_tout']) {
            $html .= '<tr><td style="padding:8px 12px;color:#dc2626;font-weight:600">Solde restant</td><td style="padding:8px 12px;text-align:right;color:#dc2626;font-weight:bold">' . number_format($total - $acompte, 2, ',', ' ') . ' €</td></tr>';
        }
        $html .= '</table>';

        $html .= '<div style="text-align:center;margin-top:20px">';
        $html .= '<a href="' . esc_url(home_url('/espace-admin/dossier/' . $order_id . '/')) . '" style="display:inline-block;padding:12px 28px;background:#59b7b7;color:#fff;text-decoration:none;border-radius:100px;font-weight:bold">📁 Voir le dossier</a>';
        $html .= '</div></div>';

        $body = self::wrapper($subject, $html);
        $headers = ['Content-Type: text/html; charset=UTF-8', 'From: Voyages Sortir 08 <noreply@sortirmonde.fr>'];

        // Contrat en PJ
        $attachments = [];
        if (!empty($contract_html)) {
            $tmp = wp_tempnam('contrat-VS08-' . $order_id);
            if ($tmp) {
                $f = $tmp . '.html';
                file_put_contents($f, '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Contrat VS08-' . $order_id . '</title></head><body>' . $contract_html . '</body></html>');
                $attachments[] = $f;
            }
        }

        foreach (self::ADMIN_RECIPIENTS as $to) {
            $ok = wp_mail($to, $subject, $body, $headers, $attachments);
            error_log('[VS08S Emails] Admin to ' . $to . ' => ' . ($ok ? 'OK' : 'FAIL'));
        }

        foreach ($attachments as $f) { if (file_exists($f)) @unlink($f); }
    }

    private static function send_client($order_id, $data, $contract_html) {
        $fact = $data['facturation'] ?? [];
        $email = $fact['email'] ?? '';
        if (empty($email) || !is_email($email)) return;

        $titre = $data['sejour_titre'] ?? 'Séjour';
        $prenom = $fact['prenom'] ?? 'Cher voyageur';
        $total = number_format(floatval($data['total'] ?? 0), 2, ',', ' ');
        $params = $data['params'] ?? [];

        $subject = 'Votre réservation — ' . $titre . ' — Voyages Sortir 08';

        $html = '<div style="padding:32px;font-family:Arial,sans-serif">'
            . '<h1 style="margin:0 0 8px;color:#0f2424;font-size:22px">Merci ' . esc_html($prenom) . ' ! 🎉</h1>'
            . '<p style="font-size:15px;color:#555;margin:0 0 24px">Votre séjour a bien été réservé.</p>'
            . '<table cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%;background:#edf8f8;border-radius:8px">'
            . '<tr><td style="padding:12px;font-weight:bold;color:#0f2424">Voyage</td><td style="padding:12px">' . esc_html($titre) . '</td></tr>'
            . '<tr><td style="padding:12px;font-weight:bold;color:#0f2424">Départ</td><td style="padding:12px">' . (!empty($params['date_depart']) ? date('d/m/Y', strtotime($params['date_depart'])) : '') . '</td></tr>'
            . '<tr><td style="padding:12px;font-weight:bold;color:#0f2424">N° dossier</td><td style="padding:12px">VS08-' . $order_id . '</td></tr>'
            . '<tr style="font-weight:bold;font-size:16px"><td style="padding:12px;color:#0f2424">Total</td><td style="padding:12px;color:#e8724a">' . $total . ' €</td></tr>'
            . '</table>'
            . '<div style="text-align:center;margin:24px 0">'
            . '<a href="' . esc_url(home_url('/espace-voyageur/')) . '" style="display:inline-block;padding:14px 32px;background:#59b7b7;color:#fff;text-decoration:none;border-radius:100px;font-weight:bold;font-size:15px">Voir mon voyage</a>'
            . '</div>'
            . '<p style="font-size:13px;color:#888">Pour toute question : <strong>03 26 65 28 63</strong> — <a href="mailto:resa@voyagessortir08.com" style="color:#59b7b7">resa@voyagessortir08.com</a></p>'
            . '</div>'
            . ($contract_html ? '<div style="border-top:3px solid #59b7b7;margin-top:8px"></div><div style="padding:16px 32px;text-align:center;font-size:12px;color:#888">Votre contrat de vente ci-dessous :</div>' . $contract_html : '');

        $body = self::wrapper($subject, $html);
        $headers = ['Content-Type: text/html; charset=UTF-8', 'From: Voyages Sortir 08 <noreply@sortirmonde.fr>'];
        $ok = wp_mail($email, $subject, $body, $headers);
        error_log('[VS08S Emails] Client to ' . $email . ' => ' . ($ok ? 'OK' : 'FAIL'));
    }

    private static function wrapper($title, $inner) {
        $c = VS08S_Contract::COMPANY;
        return '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>' . esc_html($title) . '</title></head>'
            . '<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;font-size:14px;color:#222">'
            . '<div style="max-width:800px;margin:20px auto;background:#fff;border:1px solid #d0d0d0;border-radius:8px;overflow:hidden">'
            . '<div style="background:#1a3a3a;color:#fff;padding:20px 32px;text-align:center">'
            . '<div style="font-size:22px;font-weight:bold;letter-spacing:1px">' . $c['name'] . '</div>'
            . '<div style="font-size:12px;margin-top:4px;color:#b0cece">' . $c['address'] . ' — ' . $c['city'] . ' — ' . $c['tel'] . '</div>'
            . '</div>'
            . $inner
            . '<div style="background:#1a3a3a;color:#b0cece;padding:14px 32px;font-size:10px;text-align:center;line-height:1.5">'
            . $c['legal'] . ' — Capital ' . $c['capital'] . ' € — RCS ' . $c['rcs'] . ' — Immat. ' . $c['immat'] . '<br>Garantie : ' . $c['garantie'] . ' — ' . $c['email']
            . '</div></div></body></html>';
    }
}
