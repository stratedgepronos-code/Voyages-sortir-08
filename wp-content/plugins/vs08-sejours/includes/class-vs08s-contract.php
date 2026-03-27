<?php
if (!defined('ABSPATH')) exit;

class VS08S_Contract {

    const COMPANY = [
        'name'     => 'Voyages Sortir 08',
        'form'     => 'SARL',
        'capital'  => '7 500',
        'rcs'      => 'Châlons-en-Champagne 439 131 640',
        'address'  => '24 rue Léon Bourgeois',
        'city'     => '51000 Châlons-en-Champagne',
        'tel'      => '03 26 65 28 63',
        'email'    => 'resa@voyagessortir08.com',
        'immat'    => 'IM051100014',
        'garantie' => 'APST',
        'legal'    => 'SARL Voyages Sortir 08',
    ];

    public static function generate($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return '';

        $data = $order->get_meta('_vs08s_booking_data');
        if (empty($data) || !is_array($data)) return '';

        $c = self::COMPANY;
        $fact = $data['facturation'] ?? [];
        $params = $data['params'] ?? [];
        $devis = $data['devis'] ?? [];
        $voyageurs = $data['voyageurs'] ?? [];
        $total = floatval($data['total'] ?? 0);
        $acompte = floatval($data['acompte'] ?? 0);
        $payer_tout = !empty($data['payer_tout']);
        $assurance = floatval($data['assurance'] ?? 0);
        $titre = $data['sejour_titre'] ?? 'Séjour';

        $sejour_id = intval($data['sejour_id'] ?? 0);
        $m = VS08S_Meta::get($sejour_id);
        $duree = intval($m['duree'] ?? 7);
        $destination = $m['destination'] ?? '';
        $hotel_nom = $m['hotel_nom'] ?? '';
        $hotel_etoiles = intval($m['hotel_etoiles'] ?? 5);
        $pension_labels = ['ai'=>'All Inclusive','pc'=>'Pension complète','dp'=>'Demi-pension','bb'=>'Petit-déjeuner','lo'=>'Logement seul'];
        $pension = $pension_labels[$m['pension'] ?? 'ai'] ?? 'All Inclusive';
        $delai_solde = intval($m['delai_solde'] ?? 30);
        $date_depart = $params['date_depart'] ?? '';
        $date_retour = $date_depart ? date('d/m/Y', strtotime($date_depart . ' +' . $duree . ' days')) : '';
        $nb = intval($devis['nb_total'] ?? 2);

        $is_prereservation = !empty($data['reglement_agence']) && $order && !$order->is_paid();

        ob_start();
        ?>
        <div style="font-family:Arial,sans-serif;max-width:800px;margin:0 auto;padding:20px;font-size:13px;color:#222">
            <div style="text-align:center;border-bottom:3px solid #59b7b7;padding-bottom:16px;margin-bottom:20px">
                <div style="font-size:22px;font-weight:bold;color:#0f2424"><?php echo $c['name']; ?></div>
                <div style="font-size:11px;color:#888;margin-top:4px"><?php echo $c['form']; ?> au capital de <?php echo $c['capital']; ?> € — RCS <?php echo $c['rcs']; ?></div>
                <div style="font-size:11px;color:#888"><?php echo $c['address']; ?> — <?php echo $c['city']; ?> — <?php echo $c['tel']; ?></div>
                <div style="font-size:11px;color:#888">Immatriculation : <?php echo $c['immat']; ?> — Garantie : <?php echo $c['garantie']; ?></div>
            </div>

            <?php if (!empty($is_prereservation)) : ?>
            <div style="background:#fef3c7;color:#78350f;padding:14px 20px;font-size:12px;line-height:1.5;margin:0 0 16px;border-radius:8px;border:1px solid #fbbf24">
                <strong>Pré-réservation</strong> — Document indicatif, pas un contrat de vente définitif tant qu’aucun paiement n’a été encaissé. Le prix peut évoluer.
            </div>
            <?php endif; ?>

            <h2 style="color:#0f2424;font-size:18px;margin:0 0 16px"><?php echo !empty($is_prereservation) ? 'FICHE DE PRÉ-RÉSERVATION' : 'CONTRAT DE VENTE'; ?> — VS08-<?php echo $order_id; ?></h2>
            <p style="font-size:12px;color:#888;margin:0 0 20px">Date : <?php echo date('d/m/Y H:i'); ?></p>

            <h3 style="color:#59b7b7;font-size:13px;text-transform:uppercase;letter-spacing:1px;margin:20px 0 10px">Descriptif du voyage</h3>
            <table style="width:100%;border-collapse:collapse;margin-bottom:20px" cellpadding="0" cellspacing="0">
                <tr><td style="padding:8px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;width:200px">Voyage</td><td style="padding:8px 12px;border:1px solid #e0e0e0"><?php echo esc_html($titre); ?></td></tr>
                <tr><td style="padding:8px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold">Destination</td><td style="padding:8px 12px;border:1px solid #e0e0e0"><?php echo esc_html($destination); ?></td></tr>
                <tr><td style="padding:8px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold">Hébergement</td><td style="padding:8px 12px;border:1px solid #e0e0e0"><?php echo esc_html($hotel_nom); ?> <?php echo str_repeat('★', $hotel_etoiles); ?></td></tr>
                <tr><td style="padding:8px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold">Pension</td><td style="padding:8px 12px;border:1px solid #e0e0e0"><?php echo esc_html($pension); ?></td></tr>
                <tr><td style="padding:8px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold">Dates</td><td style="padding:8px 12px;border:1px solid #e0e0e0"><?php echo $date_depart ? date('d/m/Y', strtotime($date_depart)) : '—'; ?> → <?php echo $date_retour ?: '—'; ?></td></tr>
                <tr><td style="padding:8px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold">Durée</td><td style="padding:8px 12px;border:1px solid #e0e0e0"><?php echo intval($m['duree_jours'] ?? $duree + 1); ?> jours / <?php echo $duree; ?> nuits</td></tr>
                <tr><td style="padding:8px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold">Voyageurs</td><td style="padding:8px 12px;border:1px solid #e0e0e0"><?php echo $nb; ?> personne(s)</td></tr>
            </table>

            <h3 style="color:#59b7b7;font-size:13px;text-transform:uppercase;letter-spacing:1px;margin:20px 0 10px">Client</h3>
            <table style="width:100%;border-collapse:collapse;margin-bottom:20px" cellpadding="0" cellspacing="0">
                <tr><td style="padding:8px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;width:200px">Nom</td><td style="padding:8px 12px;border:1px solid #e0e0e0"><?php echo esc_html(trim(($fact['prenom']??'').' '.strtoupper($fact['nom']??''))); ?></td></tr>
                <tr><td style="padding:8px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold">Email</td><td style="padding:8px 12px;border:1px solid #e0e0e0"><?php echo esc_html($fact['email']??''); ?></td></tr>
                <tr><td style="padding:8px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold">Téléphone</td><td style="padding:8px 12px;border:1px solid #e0e0e0"><?php echo esc_html($fact['tel']??''); ?></td></tr>
                <tr><td style="padding:8px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold">Adresse</td><td style="padding:8px 12px;border:1px solid #e0e0e0"><?php echo esc_html(($fact['adresse']??'').', '.($fact['cp']??'').' '.($fact['ville']??'')); ?></td></tr>
            </table>

            <?php if (!empty($voyageurs)): ?>
            <h3 style="color:#59b7b7;font-size:13px;text-transform:uppercase;letter-spacing:1px;margin:20px 0 10px">Voyageurs</h3>
            <table style="width:100%;border-collapse:collapse;margin-bottom:20px" cellpadding="0" cellspacing="0">
                <tr style="background:#0f2424;color:#fff"><th style="padding:6px 8px;text-align:left">N°</th><th style="padding:6px 8px;text-align:left">Nom</th><th style="padding:6px 8px;text-align:left">Prénom</th><th style="padding:6px 8px;text-align:left">DDN</th><th style="padding:6px 8px;text-align:left">Passeport</th></tr>
                <?php foreach ($voyageurs as $i => $v): $ddn=$v['ddn']??$v['date_naissance']??''; if($ddn && preg_match('/^\d{4}/',$ddn)) $ddn=date('d/m/Y',strtotime($ddn)); ?>
                <tr style="background:<?php echo $i%2===0?'#fff':'#f9f6f0'; ?>">
                    <td style="padding:6px 8px;border-bottom:1px solid #e5e7eb"><?php echo $i+1; ?></td>
                    <td style="padding:6px 8px;border-bottom:1px solid #e5e7eb;font-weight:bold"><?php echo esc_html(strtoupper($v['nom']??'')); ?></td>
                    <td style="padding:6px 8px;border-bottom:1px solid #e5e7eb"><?php echo esc_html($v['prenom']??''); ?></td>
                    <td style="padding:6px 8px;border-bottom:1px solid #e5e7eb"><?php echo esc_html($ddn?:'—'); ?></td>
                    <td style="padding:6px 8px;border-bottom:1px solid #e5e7eb"><?php echo esc_html($v['passeport']??'—'); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>

            <h3 style="color:#59b7b7;font-size:13px;text-transform:uppercase;letter-spacing:1px;margin:20px 0 10px">Détail du prix</h3>
            <table style="width:100%;border-collapse:collapse;margin-bottom:16px" cellpadding="0" cellspacing="0">
                <?php foreach ($devis['lines'] ?? [] as $line): ?>
                <tr><td style="padding:6px 12px;border-bottom:1px solid #f0ece4"><?php echo esc_html($line['label']); ?></td><td style="padding:6px 12px;border-bottom:1px solid #f0ece4;text-align:right;font-weight:bold"><?php echo number_format($line['montant'], 0, ',', ' '); ?> €</td></tr>
                <?php endforeach; ?>
                <tr style="background:#edf8f8;font-weight:bold;font-size:16px"><td style="padding:10px 12px">TOTAL</td><td style="padding:10px 12px;text-align:right"><?php echo number_format($total, 2, ',', ' '); ?> €</td></tr>
            </table>

            <h3 style="color:#59b7b7;font-size:13px;text-transform:uppercase;letter-spacing:1px;margin:20px 0 10px">Modalités de paiement</h3>
            <?php if ($payer_tout): ?>
            <p>Paiement intégral à la réservation : <strong><?php echo number_format($total, 2, ',', ' '); ?> €</strong></p>
            <?php else: ?>
            <p>Acompte à la réservation (<?php echo intval($devis['acompte_pct'] ?? 30); ?>%) : <strong><?php echo number_format($acompte, 2, ',', ' '); ?> €</strong></p>
            <p>Solde restant : <strong><?php echo number_format($total - $acompte, 2, ',', ' '); ?> €</strong> — à régler au plus tard <?php echo $delai_solde; ?> jours avant le départ.</p>
            <?php endif; ?>

            <div style="margin-top:30px;border-top:2px solid #e5e7eb;padding-top:16px;font-size:10px;color:#888;text-align:center">
                <?php echo $c['legal']; ?> — Capital <?php echo $c['capital']; ?> € — RCS <?php echo $c['rcs']; ?> — Immat. <?php echo $c['immat']; ?><br>
                Garantie : <?php echo $c['garantie']; ?> — <?php echo $c['email']; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
