<?php
if (!defined('ABSPATH')) exit;

class VS08V_Emails {

    const ADMIN_RECIPIENTS = [
        'sortir08.ag@wanadoo.fr',
        'sortir08@wanadoo.fr',
    ];

    /** Titre / chaîne pour email (évite TypeError sprintf si meta corrompue). */
    private static function email_scalar_title($v, $fallback) {
        if (is_scalar($v) && (string) $v !== '') {
            return (string) $v;
        }
        return $fallback;
    }

    /**
     * Point d'entrée : envoie les emails admin + client pour une commande.
     * Ne s'exécute qu'une fois par commande (flag _vs08v_emails_sent).
     */
    public static function dispatch($order_id) {
        // Verrou anti-réentrance : empêche la boucle infinie
        // ($order->save() dans dispatch peut déclencher des hooks WC qui rappellent dispatch)
        static $dispatching = [];
        if (isset($dispatching[$order_id])) return;
        $dispatching[$order_id] = true;

        $order = wc_get_order($order_id);
        if (!$order) { unset($dispatching[$order_id]); return; }

        if ($order->get_meta('_vs08v_emails_sent')) { unset($dispatching[$order_id]); return; }

        $data = null;
        $contract_html = '';

        try {
            $data = VS08V_Contract::get_booking_data($order_id);
        } catch (\Throwable $e) {
            error_log('[VS08 Emails] get_booking_data CRASH: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        }

        if (!$data) {
            // Fallback: lire depuis la meta de la commande
            $data = $order->get_meta('_vs08v_booking_data');
            if (empty($data) || !is_array($data)) {
                error_log('[VS08 Emails] dispatch(' . $order_id . ') — pas de booking_data');
                unset($dispatching[$order_id]);
                return;
            }
        }

        // NE PAS faire $order->save() ici — ça déclenchait une boucle infinie
        // (les hooks WooCommerce rappelaient dispatch() avant que le flag soit posé)

        try {
            $contract_html = VS08V_Contract::generate($order_id);
        } catch (\Throwable $e) {
            error_log('[VS08 Emails] Contract generate CRASH: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $contract_html = '';
        }

        try {
            self::send_admin_notification($order_id, $order, $data, $contract_html ?: '');
        } catch (\Throwable $e) {
            error_log('[VS08 Emails] send_admin_notification CRASH: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        }
        try {
            self::send_client_confirmation($order_id, $order, $data, $contract_html ?: '');
        } catch (\Throwable $e) {
            error_log('[VS08 Emails] send_client_confirmation CRASH: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        }

        // Flag + booking_data posés APRÈS l'envoi (un seul save)
        $order->update_meta_data('_vs08v_booking_data', $data);
        $order->update_meta_data('_vs08v_emails_sent', current_time('mysql'));
        $order->save();
        unset($dispatching[$order_id]);
        error_log('[VS08 Emails] dispatch(' . $order_id . ') — emails envoyés OK');
    }

    /**
     * Email aux 2 administrateurs avec TOUTES les infos + contrat en PJ.
     */
    private static function send_admin_notification($order_id, $order, $data, $contract_html) {
        $fact       = $data['facturation'] ?? [];
        $params     = $data['params'] ?? [];
        $devis      = $data['devis'] ?? [];
        $voyageurs  = $data['voyageurs'] ?? [];
        $options    = $data['options'] ?? [];
        $titre      = self::email_scalar_title($data['voyage_titre'] ?? null, 'Séjour golf');
        $client     = trim(($fact['prenom'] ?? '') . ' ' . strtoupper($fact['nom'] ?? ''));
        $nb         = intval($devis['nb_total'] ?? 1);
        $total      = floatval($data['total'] ?? 0);
        $acompte    = floatval($data['acompte'] ?? 0);
        $payer_tout = !empty($data['payer_tout']);
        $assurance  = floatval($data['assurance'] ?? 0);

        $voyage_id  = intval($data['voyage_id'] ?? 0);
        $m          = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($voyage_id) : [];
        $duree      = intval($m['duree'] ?? 7);
        $duree_j    = intval($m['duree_jours'] ?? ($duree + 1));
        $destination = $m['destination'] ?? '';
        $hotel_nom  = $m['hotel_nom'] ?? ($m['hotel']['nom'] ?? '');
        $hotel_et   = intval($m['hotel_etoiles'] ?? ($m['hotel']['etoiles'] ?? 0));
        $pension_labels = ['bb'=>'Petit-déjeuner','dp'=>'Demi-pension','pc'=>'Pension complète','ai'=>'Tout inclus','lo'=>'Logement seul'];
        $pension    = $pension_labels[$m['pension'] ?? ''] ?? '';
        $date_depart = $params['date_depart'] ?? '';
        $date_retour = $date_depart && $duree > 0 ? date('d/m/Y', strtotime($date_depart . ' +' . $duree . ' days')) : '';
        $aeroport   = strtoupper($params['aeroport'] ?? '');
        $delai_solde = intval($m['delai_solde'] ?? 30);

        $subject = sprintf('🆕 Réservation VS08-%d — %s — %s — %s €', $order_id, $titre, $client, number_format($total, 0, ',', ' '));

        // Helper pour une ligne de tableau
        $tr = function($label, $value, $highlight = false) {
            $bg = $highlight ? '#edf8f8' : '#fff';
            $fw = $highlight ? 'font-weight:bold;font-size:16px;' : '';
            return '<tr><td style="padding:10px 14px;border:1px solid #e5e7eb;background:#f9f6f0;font-weight:600;width:200px;color:#374151;font-size:13px">' . $label . '</td>'
                 . '<td style="padding:10px 14px;border:1px solid #e5e7eb;background:' . $bg . ';' . $fw . 'color:#0f2424;font-size:14px">' . $value . '</td></tr>';
        };

        $html = '<div style="padding:28px 32px">';

        // ── TITRE ──
        $html .= '<h2 style="margin:0 0 6px;color:#0f2424;font-family:Georgia,serif;font-size:24px">🆕 Nouvelle réservation</h2>';
        $html .= '<p style="margin:0 0 24px;color:#6b7280;font-size:14px">Dossier VS08-' . $order_id . ' · ' . date('d/m/Y H:i') . '</p>';

        // ── RÉCAPITULATIF DU VOYAGE ──
        $html .= '<h3 style="color:#59b7b7;font-size:12px;text-transform:uppercase;letter-spacing:2px;margin:0 0 10px;font-family:Arial,sans-serif">📋 Récapitulatif du voyage</h3>';
        $html .= '<table cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%;margin-bottom:24px">';
        $html .= $tr('Voyage', esc_html($titre));
        $html .= $tr('Destination', esc_html($destination));
        $html .= $tr('Date de départ', $date_depart ? date('d/m/Y', strtotime($date_depart)) : '—');
        if ($date_retour) $html .= $tr('Date de retour', $date_retour);
        $html .= $tr('Durée', $duree_j . ' jours / ' . $duree . ' nuits');
        if ($hotel_nom) $html .= $tr('Hébergement', esc_html($hotel_nom) . ($hotel_et ? ' ' . str_repeat('★', $hotel_et) : ''));
        if ($pension) $html .= $tr('Pension', esc_html($pension));
        $html .= $tr('Voyageurs', $nb . ' personne(s)');
        if ($aeroport) $html .= $tr('Aéroport de départ', $aeroport);
        if (!empty($params['vol_aller_num'])) $html .= $tr('Vol aller', '✈️ ' . esc_html($params['vol_aller_num']) . (!empty($params['vol_aller_cie']) ? ' (' . esc_html($params['vol_aller_cie']) . ')' : '') . ' — ' . esc_html($params['vol_aller_depart'] ?? '') . ' → ' . esc_html($params['vol_aller_arrivee'] ?? ''));
        if (!empty($params['vol_retour_num'])) $html .= $tr('Vol retour', '✈️ ' . esc_html($params['vol_retour_num']) . (!empty($params['vol_aller_cie']) ? ' (' . esc_html($params['vol_aller_cie']) . ')' : '') . ' — ' . esc_html($params['vol_retour_depart'] ?? '') . ' → ' . esc_html($params['vol_retour_arrivee'] ?? ''));
        // Transferts
        $transfert_labels = ['groupes'=>'🚌 Transferts groupés','prives'=>'🚐 Transferts privés','voiture'=>'🚗 Location de voiture'];
        $transfert_type = $m['transfert_type'] ?? $m['transport_type'] ?? '';
        if (!empty($transfert_type) && isset($transfert_labels[$transfert_type])) {
            $html .= $tr('Transferts', $transfert_labels[$transfert_type]);
        }
        $html .= '</table>';

        // ── CLIENT & FACTURATION ──
        $html .= '<h3 style="color:#59b7b7;font-size:12px;text-transform:uppercase;letter-spacing:2px;margin:0 0 10px;font-family:Arial,sans-serif">💰 Client & Facturation</h3>';
        $html .= '<table cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%;margin-bottom:24px">';
        $html .= $tr('Client', esc_html($client));
        $html .= $tr('Email', '<a href="mailto:' . esc_attr($fact['email'] ?? '') . '" style="color:#59b7b7">' . esc_html($fact['email'] ?? '') . '</a>');
        if (!empty($fact['tel'])) $html .= $tr('Téléphone', '<a href="tel:' . esc_attr($fact['tel']) . '" style="color:#59b7b7">' . esc_html($fact['tel']) . '</a>');
        if (!empty($fact['adresse'])) $html .= $tr('Adresse', esc_html($fact['adresse'] . ', ' . ($fact['cp'] ?? '') . ' ' . ($fact['ville'] ?? '')));
        $html .= '</table>';

        // ── VOYAGEURS ──
        if (!empty($voyageurs)) {
            $html .= '<h3 style="color:#59b7b7;font-size:12px;text-transform:uppercase;letter-spacing:2px;margin:0 0 10px;font-family:Arial,sans-serif">👥 Voyageurs (' . count($voyageurs) . ')</h3>';
            $html .= '<table cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%;margin-bottom:24px">';
            $html .= '<tr style="background:#0f2424;color:#fff"><th style="padding:8px 10px;text-align:left;font-size:11px">N°</th><th style="padding:8px 10px;text-align:left;font-size:11px">Nom</th><th style="padding:8px 10px;text-align:left;font-size:11px">Prénom</th><th style="padding:8px 10px;text-align:left;font-size:11px">DDN</th><th style="padding:8px 10px;text-align:left;font-size:11px">Type</th><th style="padding:8px 10px;text-align:left;font-size:11px">Passeport</th></tr>';
            foreach ($voyageurs as $i => $v) {
                $bg = ($i % 2 === 0) ? '#fff' : '#f9f6f0';
                $ddn = $v['ddn'] ?? $v['date_naissance'] ?? '';
                if ($ddn && preg_match('/^\d{4}-\d{2}-\d{2}$/', $ddn)) $ddn = date('d/m/Y', strtotime($ddn));
                $type = (isset($v['type']) && $v['type'] === 'golfeur') ? '⛳ Golfeur' : '👤 Accompagnant';
                $html .= '<tr style="background:' . $bg . '">'
                    . '<td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;font-size:13px">' . ($i + 1) . '</td>'
                    . '<td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;font-size:13px;font-weight:bold">' . esc_html(strtoupper($v['nom'] ?? '')) . '</td>'
                    . '<td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;font-size:13px">' . esc_html($v['prenom'] ?? '') . '</td>'
                    . '<td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;font-size:13px">' . esc_html($ddn ?: '—') . '</td>'
                    . '<td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;font-size:13px">' . $type . '</td>'
                    . '<td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;font-size:13px">' . esc_html($v['passeport'] ?? '—') . '</td>'
                    . '</tr>';
            }
            $html .= '</table>';
        }

        // ── DÉTAIL DU PRIX ──
        $html .= '<h3 style="color:#59b7b7;font-size:12px;text-transform:uppercase;letter-spacing:2px;margin:0 0 10px;font-family:Arial,sans-serif">📊 Détail du prix</h3>';
        $html .= '<table cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%;margin-bottom:8px">';
        foreach ($devis['lines'] ?? [] as $line) {
            $html .= '<tr><td style="padding:8px 14px;border-bottom:1px solid #f0ece4;font-size:13px;color:#374151">' . esc_html($line['label']) . '</td>'
                   . '<td style="padding:8px 14px;border-bottom:1px solid #f0ece4;font-size:13px;font-weight:bold;text-align:right;color:#0f2424">' . number_format($line['montant'], 0, ',', ' ') . ' €</td></tr>';
        }
        if ($assurance > 0) {
            $html .= '<tr><td style="padding:8px 14px;border-bottom:1px solid #f0ece4;font-size:13px;color:#374151">🛡️ Assurance Multirisques</td>'
                   . '<td style="padding:8px 14px;border-bottom:1px solid #f0ece4;font-size:13px;font-weight:bold;text-align:right;color:#0f2424">' . number_format($assurance, 0, ',', ' ') . ' €</td></tr>';
        }
        $html .= '</table>';

        // ── PAIEMENT ──
        $html .= '<table cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%;margin-bottom:24px;border:2px solid #59b7b7;border-radius:8px">';
        $html .= '<tr style="background:#0f2424"><td style="padding:12px 14px;font-size:11px;text-transform:uppercase;letter-spacing:2px;color:#7ecece;font-weight:bold" colspan="2">💳 Paiement</td></tr>';
        $html .= '<tr><td style="padding:10px 14px;font-size:14px;font-weight:bold;color:#0f2424">Total voyage</td><td style="padding:10px 14px;font-size:22px;font-weight:bold;text-align:right;color:#0f2424">' . number_format($total, 2, ',', ' ') . ' €</td></tr>';
        if ($payer_tout) {
            $html .= '<tr><td style="padding:10px 14px;font-size:13px;color:#059669" colspan="2">✅ Paiement intégral requis (départ dans moins de ' . $delai_solde . ' jours)</td></tr>';
            $html .= '<tr style="background:#ecfdf5"><td style="padding:10px 14px;font-size:14px;font-weight:bold;color:#059669">Payé à la réservation</td><td style="padding:10px 14px;font-size:18px;font-weight:bold;text-align:right;color:#059669">' . number_format($total, 2, ',', ' ') . ' €</td></tr>';
        } else {
            $html .= '<tr style="background:#edf8f8"><td style="padding:10px 14px;font-size:14px;font-weight:bold;color:#59b7b7">Acompte payé</td><td style="padding:10px 14px;font-size:18px;font-weight:bold;text-align:right;color:#59b7b7">' . number_format($acompte, 2, ',', ' ') . ' €</td></tr>';
            $html .= '<tr><td style="padding:10px 14px;font-size:14px;color:#dc2626;font-weight:600">Solde restant</td><td style="padding:10px 14px;font-size:16px;font-weight:bold;text-align:right;color:#dc2626">' . number_format($total - $acompte, 2, ',', ' ') . ' €</td></tr>';
            $solde_date = $date_depart ? date('d/m/Y', strtotime($date_depart) - ($delai_solde * 86400)) : '';
            if ($solde_date) $html .= '<tr><td style="padding:10px 14px;font-size:12px;color:#e8724a" colspan="2">📅 Échéance solde : <strong>' . $solde_date . '</strong> (' . $delai_solde . ' jours avant le départ)</td></tr>';
        }
        $html .= '</table>';

        // ── LIENS ──
        $html .= '<div style="margin-top:20px;text-align:center">';
        $html .= '<a href="' . esc_url(home_url('/espace-admin/dossier/' . $order_id . '/')) . '" style="display:inline-block;padding:14px 28px;background:#59b7b7;color:#fff;text-decoration:none;border-radius:100px;font-weight:bold;font-size:15px;margin-right:12px">📁 Voir dans l\'espace admin</a>';
        $html .= '<a href="' . esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')) . '" style="display:inline-block;padding:14px 28px;background:#0f2424;color:#fff;text-decoration:none;border-radius:100px;font-weight:bold;font-size:15px">⚙️ WordPress</a>';
        $html .= '</div>';

        $html .= '</div>';

        // ── Générer le PDF du contrat en pièce jointe ──
        $attachments = [];
        if (!empty($contract_html)) {
            $tmp_file = wp_tempnam('contrat-VS08-' . $order_id . '.html');
            if ($tmp_file) {
                $full_html = '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Contrat VS08-' . $order_id . '</title></head><body>' . $contract_html . '</body></html>';
                file_put_contents($tmp_file, $full_html);
                // Renommer en .html pour que le client puisse l'ouvrir
                $html_file = str_replace('.tmp', '.html', $tmp_file);
                if ($html_file === $tmp_file) $html_file .= '.html';
                rename($tmp_file, $html_file);
                $attachments[] = $html_file;
            }
        }

        $body = self::email_wrapper($subject, $html);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: Voyages Sortir 08 <noreply@sortirmonde.fr>',
        ];

        // Envoyer séparément à chaque admin
        foreach (self::ADMIN_RECIPIENTS as $admin_email) {
            try {
                $result = wp_mail($admin_email, $subject, $body, $headers, $attachments);
            } catch (\Throwable $e) {
                error_log('[VS08 Emails] wp_mail admin exception: ' . $e->getMessage());
                $result = false;
            }
            error_log('[VS08 Emails] Admin notification to ' . $admin_email . ' => ' . ($result ? 'OK' : 'FAIL'));
        }

        // Nettoyer le fichier temporaire
        foreach ($attachments as $f) {
            if (file_exists($f)) @unlink($f);
        }
    }

    /**
     * Email au client avec message de bienvenue + contrat complet.
     */
    private static function send_client_confirmation($order_id, $order, $data, $contract_html) {
        $fact  = $data['facturation'] ?? [];
        $email = $fact['email'] ?? '';
        if (empty($email) || !is_email($email)) return;

        $titre  = self::email_scalar_title($data['voyage_titre'] ?? null, 'Séjour golf');
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
            . '<tr><td style="padding:12px 16px;font-weight:bold;color:#1a3a3a;">Date de départ</td><td style="padding:12px 16px;">' . esc_html(!empty($params['date_depart']) ? date('d/m/Y', strtotime((string) $params['date_depart'])) : '') . '</td></tr>'
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
        try {
            $result = wp_mail($recipients, $subject, $html_body, $headers);
        } catch (\Throwable $e) {
            error_log('[VS08 Emails] wp_mail exception: ' . $e->getMessage());
            $result = false;
        }
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
