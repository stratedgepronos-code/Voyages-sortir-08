<?php
if (!defined('ABSPATH')) exit;

class VS08C_Contract {

    const COMPANY = [
        'name'       => 'VOYAGES SORTIR 08',
        'legal'      => 'SARL Sortir 08',
        'address'    => '24 Rue Léon Bourgeois',
        'city'       => '51000 CHÂLONS-EN-CHAMPAGNE',
        'tel'        => '03.26.65.28.63',
        'email'      => 'sortir08.ag@wanadoo.fr',
        'capital'    => '7 622',
        'rcs'        => 'B 439 131 640',
        'ape'        => '7911Z',
        'tva_intra'  => 'FR05439131640',
        'siret'      => '43913164000021',
        'immat'      => 'IM051100018',
        'garantie'   => 'APST 15 Avenue Carnot 75017 PARIS',
        'rcp'        => 'GENERALI 56 539 592',
    ];

    public static function get_booking_data($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return null;
        $data = $order->get_meta('_vs08c_booking_data');
        if (!empty($data) && is_array($data)) return $data;
        foreach ($order->get_items() as $item) {
            $pid = $item->get_product_id();
            if ($pid) {
                $d = get_post_meta($pid, '_vs08c_booking_data', true);
                if (!empty($d) && is_array($d)) return $d;
            }
            $d = $item->get_meta('_vs08c_booking_data');
            if (!empty($d) && is_array($d)) return $d;
        }
        return null;
    }

    public static function generate($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return '';
        $data = self::get_booking_data($order_id);
        if (!$data) return '';

        $c         = self::COMPANY;
        $params    = $data['params'] ?? [];
        $devis     = $data['devis'] ?? [];
        $fact      = $data['facturation'] ?? [];
        $voyageurs = $data['voyageurs'] ?? [];
        $options   = $data['options'] ?? [];
        $total     = floatval($data['total'] ?? 0);
        $acompte   = floatval($data['acompte'] ?? 0);
        $payer_tout = !empty($data['payer_tout']);
        $assurance  = floatval($data['assurance'] ?? 0);

        $circuit_id = $data['circuit_id'] ?? 0;
        $m = VS08C_Meta::get($circuit_id);
        $duree_n     = intval($m['duree'] ?? 7);
        $duree_j     = intval($m['duree_jours'] ?? ($duree_n + 1));
        $destination = $m['destination'] ?? '';
        $delai_solde = intval($m['delai_solde'] ?? 30);
        $acompte_pct = floatval($data['acompte_pct'] ?? ($m['acompte_pct'] ?? 30));
        $hotels      = $m['hotels'] ?? [];
        $nb_total    = intval($devis['nb_total'] ?? ($params['nb_adultes'] ?? 2));
        $nb_chambres = intval($params['nb_chambres'] ?? 1);

        $pension_labels   = ['bb'=>'Petit-déjeuner','dp'=>'Demi-pension','pc'=>'Pension complète','ai'=>'Tout inclus','mixed'=>'Selon programme'];
        $transport_labels = ['bus'=>'Bus climatisé','4x4'=>'4x4','voiture'=>'Voiture de location','train'=>'Train','mixed'=>'Transport mixte'];

        $pension_lbl   = $pension_labels[$m['pension'] ?? ''] ?? '';
        $transport_lbl = $transport_labels[$m['transport'] ?? ''] ?? '';

        $date_depart = $params['date_depart'] ?? '';
        $date_retour = $date_depart ? date('Y-m-d', strtotime($date_depart . ' +' . $duree_n . ' days')) : '';

        $contrat_num  = 'VS08-' . $order_id;
        $date_contrat = $order->get_date_created() ? $order->get_date_created()->format('d/m/Y') : date('d/m/Y');
        $client_nom   = strtoupper(esc_html($fact['nom'] ?? ''));
        $client_prenom = esc_html($fact['prenom'] ?? '');

        $solde = $payer_tout ? 0 : ($total - $acompte);
        $solde_date = '';
        if (!$payer_tout && $date_depart) {
            $solde_ts = strtotime($date_depart) - ($delai_solde * 86400);
            if ($solde_ts > time()) $solde_date = date('d/m/Y', $solde_ts);
        }

        ob_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#222;">
<div style="max-width:800px;margin:20px auto;background:#fff;border:1px solid #d0d0d0;">

<div style="background:#1a3a3a;color:#fff;padding:24px 32px;">
    <table width="100%" cellpadding="0" cellspacing="0"><tr>
        <td style="vertical-align:top">
            <div style="font-size:22px;font-weight:bold;font-family:Georgia,serif;letter-spacing:1px;"><?php echo $c['name']; ?></div>
            <div style="font-size:11px;margin-top:4px;color:#b0cece;">(Détaillant)</div>
        </td>
        <td style="text-align:right;vertical-align:top;font-size:12px;color:#b0cece;line-height:1.6;">
            <?php echo $c['address']; ?><br><?php echo $c['city']; ?><br>
            Tél. : <?php echo $c['tel']; ?><br>Email : <?php echo $c['email']; ?>
        </td>
    </tr></table>
</div>

<div style="background:#2a7f7f;color:#fff;padding:12px 32px;font-size:16px;font-weight:bold;text-align:center;">
    Contrat de vente N° <?php echo $contrat_num; ?> du <?php echo $date_contrat; ?>
</div>

<div style="padding:24px 32px 16px;">
    <table width="100%" cellpadding="0" cellspacing="0"><tr>
        <td style="vertical-align:top;width:55%">
            <div style="font-size:11px;color:#888;text-transform:uppercase;margin-bottom:4px;">Client</div>
            <div style="font-size:16px;font-weight:bold;"><?php echo $client_prenom; ?> <?php echo $client_nom; ?></div>
            <?php if (!empty($fact['adresse'])): ?>
            <div style="margin-top:4px;"><?php echo esc_html($fact['adresse']); ?></div>
            <div><?php echo esc_html($fact['cp'] ?? ''); ?> <?php echo esc_html(strtoupper($fact['ville'] ?? '')); ?></div>
            <?php endif; ?>
        </td>
        <td style="vertical-align:top;text-align:right;font-size:13px;color:#555;line-height:1.7;">
            <?php if (!empty($fact['email'])): ?>Email : <?php echo esc_html($fact['email']); ?><br><?php endif; ?>
            <?php if (!empty($fact['tel'])): ?>Tél. : <?php echo esc_html($fact['tel']); ?><br><?php endif; ?>
            Réf. commande : <?php echo $contrat_num; ?>
        </td>
    </tr></table>
</div>

<?php echo self::section_title('Récapitulatif de la réservation'); ?>
<div style="padding:0 32px 16px;">
    <table width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-size:13px;">
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;width:38%;font-weight:bold;">🗺️ Circuit</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo esc_html($data['circuit_titre'] ?? ''); ?></td></tr>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">📍 Destination</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo esc_html($destination); ?></td></tr>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">📅 Date de départ</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo $date_depart ? date('d/m/Y', strtotime($date_depart)) : '—'; ?></td></tr>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">📅 Date de retour</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo $date_retour ? date('d/m/Y', strtotime($date_retour)) : '—'; ?></td></tr>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">✈️ Aéroport de départ</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo esc_html(strtoupper($params['aeroport'] ?? '—')); ?></td></tr>
        <?php if (!empty($params['vol_aller_num'])): ?>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">✈️ Vol aller</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo esc_html($params['vol_aller_num']); ?><?php if (!empty($params['vol_aller_cie'])): ?> (<?php echo esc_html($params['vol_aller_cie']); ?>)<?php endif; ?> — <?php echo esc_html($params['vol_aller_depart'] ?? ''); ?> → <?php echo esc_html($params['vol_aller_arrivee'] ?? ''); ?></td></tr>
        <?php endif; ?>
        <?php if (!empty($params['vol_retour_num'])): ?>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">✈️ Vol retour</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo esc_html($params['vol_retour_num']); ?> — <?php echo esc_html($params['vol_retour_depart'] ?? ''); ?> → <?php echo esc_html($params['vol_retour_arrivee'] ?? ''); ?></td></tr>
        <?php endif; ?>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">🌙 Durée</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo $duree_j; ?> jours / <?php echo $duree_n; ?> nuits</td></tr>
        <?php if ($pension_lbl): ?>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">🍽️ Formule</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo esc_html($pension_lbl); ?></td></tr>
        <?php endif; ?>
        <?php if ($transport_lbl): ?>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">🚌 Transport</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo esc_html($transport_lbl); ?></td></tr>
        <?php endif; ?>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">👥 Voyageurs</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo $nb_total; ?> personne(s)</td></tr>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">🛏️ Chambres</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo $nb_chambres; ?> chambre(s)</td></tr>
    </table>
</div>

<?php echo self::section_title('Voyageurs'); ?>
<div style="padding:0 32px 16px;">
    <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;">
        <tr style="background:#1a3a3a;color:#fff;font-size:12px;text-transform:uppercase;">
            <th style="padding:8px 12px;text-align:left;border:1px solid #1a3a3a;">Nom</th>
            <th style="padding:8px 12px;text-align:left;border:1px solid #1a3a3a;">Prénom</th>
            <th style="padding:8px 12px;text-align:left;border:1px solid #1a3a3a;">Date de naissance</th>
            <th style="padding:8px 12px;text-align:left;border:1px solid #1a3a3a;">N° passeport</th>
            <th style="padding:8px 12px;text-align:left;border:1px solid #1a3a3a;">Chambre</th>
        </tr>
        <?php foreach ($voyageurs as $i => $v):
            $bg = ($i % 2 === 0) ? '#f8f8f8' : '#fff';
            $ddn_raw = $v['ddn'] ?? '';
            $ddn_fmt = $ddn_raw;
            if ($ddn_raw && preg_match('/^\d{4}-\d{2}-\d{2}$/', $ddn_raw)) {
                $d = DateTime::createFromFormat('Y-m-d', $ddn_raw);
                if ($d) $ddn_fmt = $d->format('d/m/Y');
            }
        ?>
        <tr style="background:<?php echo $bg; ?>;">
            <td style="padding:8px 12px;border:1px solid #e0e0e0;font-weight:bold;"><?php echo esc_html(strtoupper($v['nom'] ?? '')); ?></td>
            <td style="padding:8px 12px;border:1px solid #e0e0e0;"><?php echo esc_html($v['prenom'] ?? ''); ?></td>
            <td style="padding:8px 12px;border:1px solid #e0e0e0;"><?php echo esc_html($ddn_fmt ?: '—'); ?></td>
            <td style="padding:8px 12px;border:1px solid #e0e0e0;"><?php echo esc_html($v['passeport'] ?? '—'); ?></td>
            <td style="padding:8px 12px;border:1px solid #e0e0e0;">Ch.<?php echo intval($v['chambre'] ?? 1); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php if (!empty($hotels)): ?>
<?php echo self::section_title('Hébergement'); ?>
<div style="padding:0 32px 16px;">
    <table width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-size:13px;">
        <tr style="background:#1a3a3a;color:#fff;font-size:11px;text-transform:uppercase;">
            <th style="padding:8px;border:1px solid #1a3a3a;text-align:left;">Hôtel</th>
            <th style="padding:8px;border:1px solid #1a3a3a;text-align:left;">Ville</th>
            <th style="padding:8px;border:1px solid #1a3a3a;text-align:left;">Catégorie</th>
            <th style="padding:8px;border:1px solid #1a3a3a;text-align:left;">Nuits</th>
        </tr>
        <?php foreach ($hotels as $hi => $hotel):
            if (empty($hotel['nom'])) continue;
            $etoiles_map = ['2'=>'★★','3'=>'★★★','4'=>'★★★★','5'=>'★★★★★','riad'=>'Riad','camp'=>'Bivouac'];
            $stars = $etoiles_map[$hotel['etoiles'] ?? '4'] ?? '';
        ?>
        <tr style="background:<?php echo ($hi % 2 === 0) ? '#f8f8f8' : '#fff'; ?>;">
            <td style="padding:6px 8px;border:1px solid #e0e0e0;font-weight:bold;"><?php echo esc_html($hotel['nom']); ?></td>
            <td style="padding:6px 8px;border:1px solid #e0e0e0;"><?php echo esc_html($hotel['ville'] ?? ''); ?></td>
            <td style="padding:6px 8px;border:1px solid #e0e0e0;"><?php echo $stars; ?></td>
            <td style="padding:6px 8px;border:1px solid #e0e0e0;"><?php echo intval($hotel['nuits'] ?? 1); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>

<?php echo self::section_title('Transport'); ?>
<div style="padding:0 32px 16px;">
    <table width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-size:13px;">
        <tr style="background:#1a3a3a;color:#fff;font-size:11px;text-transform:uppercase;">
            <th style="padding:8px;border:1px solid #1a3a3a;text-align:left;">Type</th>
            <th style="padding:8px;border:1px solid #1a3a3a;text-align:left;">Départ de</th>
            <th style="padding:8px;border:1px solid #1a3a3a;text-align:left;">Date</th>
            <th style="padding:8px;border:1px solid #1a3a3a;text-align:left;">Heure</th>
            <th style="padding:8px;border:1px solid #1a3a3a;text-align:left;">Arrivée à</th>
            <th style="padding:8px;border:1px solid #1a3a3a;text-align:left;">Compagnie</th>
        </tr>
        <?php if (!empty($params['vol_aller_num'])): ?>
        <tr style="background:#f8f8f8;">
            <td style="padding:6px 8px;border:1px solid #e0e0e0;">AVION</td>
            <td style="padding:6px 8px;border:1px solid #e0e0e0;"><?php echo esc_html(strtoupper($params['aeroport'] ?? '')); ?></td>
            <td style="padding:6px 8px;border:1px solid #e0e0e0;"><?php echo $date_depart ? date('d/m/Y', strtotime($date_depart)) : ''; ?></td>
            <td style="padding:6px 8px;border:1px solid #e0e0e0;"><?php echo esc_html($params['vol_aller_depart'] ?? ''); ?></td>
            <td style="padding:6px 8px;border:1px solid #e0e0e0;"><?php echo esc_html(strtoupper($destination)); ?></td>
            <td style="padding:6px 8px;border:1px solid #e0e0e0;"><?php echo esc_html($params['vol_aller_cie'] ?? ''); ?> / <?php echo esc_html($params['vol_aller_num'] ?? ''); ?></td>
        </tr>
        <?php endif; ?>
        <?php if (!empty($params['vol_retour_num'])): ?>
        <tr>
            <td style="padding:6px 8px;border:1px solid #e0e0e0;">AVION</td>
            <td style="padding:6px 8px;border:1px solid #e0e0e0;"><?php echo esc_html(strtoupper($destination)); ?></td>
            <td style="padding:6px 8px;border:1px solid #e0e0e0;"><?php echo $date_retour ? date('d/m/Y', strtotime($date_retour)) : ''; ?></td>
            <td style="padding:6px 8px;border:1px solid #e0e0e0;"><?php echo esc_html($params['vol_retour_depart'] ?? ''); ?></td>
            <td style="padding:6px 8px;border:1px solid #e0e0e0;"><?php echo esc_html(strtoupper($params['aeroport'] ?? '')); ?></td>
            <td style="padding:6px 8px;border:1px solid #e0e0e0;"><?php echo esc_html($params['vol_aller_cie'] ?? ''); ?> / <?php echo esc_html($params['vol_retour_num'] ?? ''); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($transport_lbl): ?>
        <tr><td colspan="6" style="padding:6px 8px;border:1px solid #e0e0e0;">Transport local : <?php echo esc_html($transport_lbl); ?></td></tr>
        <?php endif; ?>
    </table>
</div>

<?php echo self::section_title('Assurances'); ?>
<div style="padding:0 32px 16px;font-size:13px;line-height:1.6;">
    <?php if ($assurance > 0): ?>
    <p>- Assurance(s) proposée(s) par le vendeur et acceptée(s) par le client : <strong>Assurance Multirisques GALAXY (<?php echo number_format($assurance, 2, ',', ' '); ?> €)</strong></p>
    <?php else: ?>
    <p>- Assurance(s) proposée(s) refusée(s) par le client : Multirisques / Annulation.</p>
    <?php endif; ?>
    <p style="font-size:11px;color:#888;">Le contrat d'assurance est joint au présent bulletin d'inscription.</p>
</div>

<?php echo self::section_title('Décompte'); ?>
<div style="padding:0 32px 16px;">
    <table width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-size:13px;margin-bottom:12px;">
        <tr style="background:#1a3a3a;color:#fff;font-size:11px;text-transform:uppercase;">
            <th style="padding:8px 12px;text-align:left;border:1px solid #1a3a3a;" colspan="2">Détail des prestations</th>
        </tr>
        <?php foreach ($devis['lines'] ?? [] as $line): ?>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo esc_html($line['label']); ?></td><td style="padding:6px 12px;border:1px solid #e0e0e0;text-align:right;white-space:nowrap"><?php echo number_format($line['montant'], 2, ',', ' '); ?> €</td></tr>
        <?php endforeach; ?>
        <?php foreach ($options as $opt): if (!is_array($opt)) continue; ?>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;">Option : <?php echo esc_html($opt['label'] ?? ''); ?></td><td style="padding:6px 12px;border:1px solid #e0e0e0;text-align:right;white-space:nowrap"><?php echo number_format(floatval($opt['prix'] ?? 0), 2, ',', ' '); ?> €</td></tr>
        <?php endforeach; ?>
        <?php if ($assurance > 0): ?>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;">🛡️ Assurance Multirisques</td><td style="padding:6px 12px;border:1px solid #e0e0e0;text-align:right;white-space:nowrap"><?php echo number_format($assurance, 2, ',', ' '); ?> €</td></tr>
        <?php endif; ?>
    </table>
    <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;font-size:14px;">
        <tr style="background:#edf8f8;font-weight:bold;">
            <td style="padding:10px 12px;border:2px solid #2a7f7f;">Montant total</td>
            <td style="padding:10px 12px;border:2px solid #2a7f7f;text-align:right;font-size:16px;"><?php echo number_format($total, 2, ',', ' '); ?> €</td>
        </tr>
        <?php if ($payer_tout): ?>
        <tr style="background:#fff3e6;font-weight:bold;">
            <td style="padding:10px 12px;border:1px solid #e8724a;color:#e8724a;">Paiement intégral</td>
            <td style="padding:10px 12px;border:1px solid #e8724a;text-align:right;color:#e8724a;"><?php echo number_format($total, 2, ',', ' '); ?> €</td>
        </tr>
        <?php else: ?>
        <tr style="background:#fff3e6;font-weight:bold;">
            <td style="padding:10px 12px;border:1px solid #e8724a;color:#e8724a;">Acompte (<?php echo $acompte_pct; ?>%)</td>
            <td style="padding:10px 12px;border:1px solid #e8724a;text-align:right;color:#e8724a;"><?php echo number_format($acompte, 2, ',', ' '); ?> €</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border:1px solid #e0e0e0;">Solde à verser au plus tard le <?php echo $solde_date ?: '—'; ?> *</td>
            <td style="padding:8px 12px;border:1px solid #e0e0e0;text-align:right;"><?php echo number_format($solde, 2, ',', ' '); ?> €</td>
        </tr>
        <tr><td colspan="2" style="padding:4px 12px;font-size:11px;color:#888;">* Sans rappel de notre part et sous peine de non réalisation du voyage et moyennant les frais d'annulation prévus.</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php echo self::section_title('Conditions de modification et d\'annulation'); ?>
<div style="padding:0 32px 16px;font-size:12px;line-height:1.6;color:#555;">
    <p><strong>Absence de droit de rétractation</strong> — Conformément aux articles L. 221-2 et L. 221-28 du Code de la consommation, le présent contrat n'est pas soumis au droit de rétractation.</p>
    <p><strong>Frais d'annulation :</strong></p>
    <?php $annulation = $m['annulation'] ?? []; if (!is_array($annulation)) $annulation = []; if (!empty($annulation)): ?>
    <ul style="margin:4px 0 4px 20px;">
        <?php foreach ($annulation as $p):
            $label = $p['label'] ?? '';
            $jours = $p['jours_avant'] ?? '';
            $pct   = $p['retenue'] ?? '';
            if ($label === '' && $jours === '' && $pct === '') continue;
        ?>
        <li><?php echo esc_html($label ?: (($jours !== '') ? $jours . ' jours avant le départ' : '—')); ?><?php if ($pct !== ''): ?> : <?php echo esc_html($pct); ?>% du prix total<?php endif; ?></li>
        <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <ul style="margin:4px 0 4px 20px;">
        <li>Plus de 60 jours avant le départ : 30% du prix total</li>
        <li>De 60 à 30 jours : 50%</li>
        <li>De 30 à 15 jours : 75%</li>
        <li>Moins de 15 jours : 100%</li>
    </ul>
    <?php endif; ?>
    <p><strong>Cession du contrat</strong> — Conformément à l'article L. 211-11 du Code du Tourisme, vous avez la possibilité de céder le présent contrat jusqu'à 7 jours du départ.</p>
</div>

<?php echo self::section_title('Responsabilité et réclamations'); ?>
<div style="padding:0 32px 16px;font-size:12px;line-height:1.6;color:#555;">
    <p>Le détaillant et l'organisateur sont responsables de la bonne exécution des services prévus au présent contrat.</p>
    <p><strong>Réclamations :</strong> <?php echo $c['name']; ?> <?php echo $c['address']; ?> <?php echo $c['city']; ?> ou <?php echo $c['email']; ?>.</p>
    <p>A défaut de réponse dans 60 jours : Médiateur du Tourisme — <a href="https://www.mtv.travel" style="color:#2a7f7f;">www.mtv.travel</a></p>
</div>

<?php echo self::section_title('Annexe — Directive UE 2015/2302'); ?>
<div style="padding:0 32px 16px;font-size:11px;line-height:1.6;color:#666;">
    <p>La combinaison de services de voyage qui vous est proposée est un forfait au sens de la directive (UE) 2015/2302. Vous bénéficierez de tous les droits applicables aux forfaits. <?php echo $c['name']; ?> sera entièrement responsable de la bonne exécution du forfait dans son ensemble.</p>
    <p>En outre, <?php echo $c['name']; ?> dispose d'une protection afin de rembourser vos paiements et d'assurer votre rapatriement au cas où elle deviendrait insolvable.</p>
    <p><strong>Droits essentiels :</strong></p>
    <ul style="margin:4px 0 4px 16px;">
        <li>Les voyageurs recevront toutes les informations essentielles avant de conclure le contrat.</li>
        <li>L'organisateur et le détaillant sont responsables de la bonne exécution de tous les services.</li>
        <li>Numéro de téléphone d'urgence : <?php echo $c['tel']; ?>.</li>
        <li>Les voyageurs peuvent céder leur forfait à une autre personne.</li>
        <li>Si la majoration dépasse 8%, le voyageur peut résoudre le contrat.</li>
        <li>Si l'organisateur devient insolvable, les montants versés seront remboursés. Protection : <?php echo $c['garantie']; ?>.</li>
    </ul>
</div>

<div style="background:#1a3a3a;color:#b0cece;padding:16px 32px;font-size:10px;line-height:1.6;text-align:center;">
    Siège social : <?php echo $c['legal']; ?> <?php echo $c['address']; ?> <?php echo $c['city']; ?> — Capital de <?php echo $c['capital']; ?> € — RCS <?php echo $c['rcs']; ?><br>
    APE <?php echo $c['ape']; ?> — TVA intra. <?php echo $c['tva_intra']; ?> — RCP : <?php echo $c['rcp']; ?><br>
    Garantie Financière : <?php echo $c['garantie']; ?> — Immat. <?php echo $c['immat']; ?> — Siret <?php echo $c['siret']; ?>
</div>

</div>
</body>
</html>
<?php
        return ob_get_clean();
    }

    private static function section_title($title) {
        return '<div style="background:#2a7f7f;color:#fff;padding:8px 32px;font-size:13px;font-weight:bold;text-transform:uppercase;letter-spacing:1px;margin-top:4px;">' . esc_html($title) . '</div>';
    }
}
