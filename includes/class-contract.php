<?php
if (!defined('ABSPATH')) exit;

class VS08V_Contract {

    const COMPANY = [
        'name'       => 'VOYAGES SORTIR 08',
        'legal'      => 'SARL Sortir 08',
        'address'    => '24 Rue Léon Bourgeois',
        'city'       => '51000 CHÂLONS-EN-CHAMPAGNE',
        'tel'        => '03.26.65.28.63',
        'tel2'       => '03.26.65.13.97',
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

    /**
     * Récupère les booking_data depuis une commande WooCommerce.
     */
    public static function get_booking_data($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return null;

        // D'abord essayer la meta sur la commande
        $data = $order->get_meta('_vs08v_booking_data');
        if (!empty($data) && is_array($data)) return $data;

        // Sinon la récupérer depuis le produit dans les items
        foreach ($order->get_items() as $item) {
            $pid = $item->get_product_id();
            if (!$pid) continue;
            $d = get_post_meta($pid, '_vs08v_booking_data', true);
            if (!empty($d) && is_array($d)) return $d;
            $d = $item->get_meta('_vs08v_booking_data');
            if (!empty($d) && is_array($d)) return $d;
        }
        return null;
    }

    /**
     * Génère le contrat de vente HTML complet (format IGA T9).
     */
    public static function generate($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return '';

        $data = self::get_booking_data($order_id);
        if (!$data) return '';

        $c        = self::COMPANY;
        $params   = $data['params'] ?? [];
        $devis    = $data['devis'] ?? [];
        $fact     = $data['facturation'] ?? [];
        $voyageurs= $data['voyageurs'] ?? [];
        $options  = $data['options'] ?? [];
        $total    = floatval($data['total'] ?? 0);
        $acompte  = floatval($data['acompte'] ?? 0);
        $payer_tout = !empty($data['payer_tout']);
        $assurance  = floatval($data['assurance'] ?? 0);

        $voyage_id = $data['voyage_id'] ?? 0;
        $m = class_exists('VS08V_MetaBoxes') ? VS08V_MetaBoxes::get($voyage_id) : [];
        $duree = intval($m['duree'] ?? 7);
        $pension_labels = ['bb'=>'Petit-déjeuner','dp'=>'Demi-pension','pc'=>'Pension complète','ai'=>'Tout inclus','lo'=>'Logement seul'];
        $hotel_nom     = $m['hotel_nom'] ?? ($m['hotel']['nom'] ?? '');
        $hotel_etoiles = $m['hotel_etoiles'] ?? ($m['hotel']['etoiles'] ?? '');
        $pension_code  = $m['pension'] ?? ($m['hotel']['pension'] ?? '');
        $pension_label = $pension_labels[$pension_code] ?? $pension_code;
        $destination   = $m['destination'] ?? '';
        $delai_solde   = intval($m['delai_solde'] ?? 30);
        $acompte_pct   = floatval($m['acompte_pct'] ?? 30);

        $date_depart  = $params['date_depart'] ?? '';
        $date_retour  = '';
        if ($date_depart && $duree > 0) {
            $date_retour = date('Y-m-d', strtotime($date_depart . ' +' . $duree . ' days'));
        }
        $nb_total    = intval($devis['nb_total'] ?? (($params['nb_golfeurs'] ?? 1) + ($params['nb_nongolfeurs'] ?? 0)));
        $nb_golfeurs = intval($params['nb_golfeurs'] ?? 1);

        $opt_bagage_qty  = 0;
        $opt_sacgolf_qty = 0;
        foreach ($options as $opt) {
            if (!is_array($opt)) continue;
            $oid = $opt['id'] ?? '';
            if (strpos($oid, 'bagage') !== false || strpos($oid, 'soute') !== false) {
                $opt_bagage_qty += intval($opt['qty'] ?? 0);
            } elseif ($oid === 'sac_golf') {
                $opt_sacgolf_qty = intval($opt['qty'] ?? 0);
            }
        }
        // Fallback : si inclus dans le prix de base (devis ou meta voyage)
        $devis_lines = $devis['lines'] ?? [];
        if ($opt_bagage_qty <= 0) {
            foreach ($devis_lines as $line) {
                $lbl = $line['label'] ?? '';
                if (stripos($lbl, 'bagage') !== false || stripos($lbl, 'soute') !== false) {
                    $opt_bagage_qty = $nb_total;
                    break;
                }
            }
            if ($opt_bagage_qty <= 0 && floatval($m['prix_bagage_soute'] ?? 0) > 0) {
                $opt_bagage_qty = $nb_total;
            }
        }
        if ($opt_sacgolf_qty <= 0 && $nb_golfeurs > 0) {
            foreach ($devis_lines as $line) {
                $lbl = $line['label'] ?? '';
                if (stripos($lbl, 'sac') !== false && stripos($lbl, 'golf') !== false) {
                    $opt_sacgolf_qty = $nb_golfeurs;
                    break;
                }
            }
        }

        $contrat_num = 'VS08-' . $order_id;
        $date_contrat = $order->get_date_created() ? $order->get_date_created()->format('d/m/Y') : date('d/m/Y');
        $client_nom   = strtoupper(esc_html($fact['nom'] ?? ''));
        $client_prenom= esc_html($fact['prenom'] ?? '');

        $solde = $payer_tout ? 0 : ($total - $acompte);
        $solde_date = '';
        if (!$payer_tout && $date_depart) {
            $solde_ts = strtotime($date_depart) - ($delai_solde * 86400);
            if ($solde_ts > time()) {
                $solde_date = date('d/m/Y', $solde_ts);
            }
        }

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#222;">
<div style="max-width:800px;margin:20px auto;background:#fff;border:1px solid #d0d0d0;">

<!-- ═══════════════ EN-TÊTE ═══════════════ -->
<div style="background:#1a3a3a;color:#fff;padding:24px 32px;">
    <table width="100%" cellpadding="0" cellspacing="0"><tr>
        <td style="vertical-align:top">
            <div style="font-size:22px;font-weight:bold;font-family:Georgia,serif;letter-spacing:1px;"><?php echo $c['name']; ?></div>
            <div style="font-size:11px;margin-top:4px;color:#b0cece;">(Détaillant)</div>
        </td>
        <td style="text-align:right;vertical-align:top;font-size:12px;color:#b0cece;line-height:1.6;">
            <?php echo $c['address']; ?><br>
            <?php echo $c['city']; ?><br>
            Tél. : <?php echo $c['tel']; ?><br>
            Email : <?php echo $c['email']; ?>
        </td>
    </tr></table>
</div>

<!-- ═══════════════ TITRE CONTRAT ═══════════════ -->
<div style="background:#2a7f7f;color:#fff;padding:12px 32px;font-size:16px;font-weight:bold;text-align:center;">
    Contrat de vente N° <?php echo $contrat_num; ?> du <?php echo $date_contrat; ?>
</div>

<!-- ═══════════════ CLIENT ═══════════════ -->
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
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;width:38%;font-weight:bold;">📋 Voyage</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo esc_html($data['voyage_titre'] ?? ''); ?></td></tr>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">📅 Date de départ</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo $date_depart ? date('d/m/Y', strtotime($date_depart)) : '—'; ?></td></tr>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">📅 Date de retour</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo $date_retour ? date('d/m/Y', strtotime($date_retour)) : '—'; ?></td></tr>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">✈️ Aéroport de départ</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo esc_html(strtoupper((string)($params['aeroport'] ?? '—'))); ?></td></tr>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">🛬 Aéroport de destination</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo esc_html(strtoupper((string)($m['iata_dest'] ?? $destination ?? '—'))); ?></td></tr>
        <?php if (!empty($params['vol_aller_num'])): ?>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">✈️ Vol aller</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo esc_html($params['vol_aller_num']); ?><?php if (!empty($params['vol_aller_cie'])): ?> (<?php echo esc_html($params['vol_aller_cie']); ?>)<?php endif; ?> — <?php echo esc_html($params['vol_aller_depart'] ?? ''); ?> → <?php echo esc_html($params['vol_aller_arrivee'] ?? ''); ?></td></tr>
        <?php endif; ?>
        <?php if (!empty($params['vol_retour_num'])): ?>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">✈️ Vol retour</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo esc_html($params['vol_retour_num']); ?> — <?php echo esc_html($params['vol_retour_depart'] ?? ''); ?> → <?php echo esc_html($params['vol_retour_arrivee'] ?? ''); ?></td></tr>
        <?php endif; ?>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">🌙 Durée</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo $duree; ?> nuits</td></tr>
        <?php if ($hotel_nom): ?>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">🏨 Hôtel</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo esc_html($hotel_nom); ?><?php if ($hotel_etoiles): ?> <?php echo str_repeat('★', intval($hotel_etoiles)); ?><?php endif; ?></td></tr>
        <?php endif; ?>
        <?php if ($pension_label): ?>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">🍽️ Formule</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo esc_html($pension_label); ?></td></tr>
        <?php endif; ?>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">👥 Voyageurs</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo $nb_total; ?> personne(s) (<?php echo $nb_golfeurs; ?> golfeur(s) + <?php echo max(0, $nb_total - $nb_golfeurs); ?> accompagnant(s))</td></tr>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">🛏️ Type chambre</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo esc_html(ucfirst($params['type_chambre'] ?? 'double')); ?><?php if (!empty($params['nb_chambres'])): ?> × <?php echo intval($params['nb_chambres']); ?><?php endif; ?></td></tr>
        <?php if ($opt_bagage_qty > 0): ?>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">🧳 Bagage soute</td><td style="padding:6px 12px;border:1px solid #e0e0e0;">Inclus × <?php echo $opt_bagage_qty; ?></td></tr>
        <?php endif; ?>
        <?php if ($opt_sacgolf_qty > 0): ?>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">🏌️ Sac de golf</td><td style="padding:6px 12px;border:1px solid #e0e0e0;">Inclus × <?php echo $opt_sacgolf_qty; ?></td></tr>
        <?php endif; ?>
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
            <th style="padding:8px 12px;text-align:left;border:1px solid #1a3a3a;">Type</th>
        </tr>
        <?php foreach ($voyageurs as $i => $v):
            $bg = ($i % 2 === 0) ? '#f8f8f8' : '#fff';
        ?>
        <tr style="background:<?php echo $bg; ?>;">
            <td style="padding:8px 12px;border:1px solid #e0e0e0;font-weight:bold;"><?php echo esc_html(strtoupper($v['nom'] ?? '')); ?></td>
            <td style="padding:8px 12px;border:1px solid #e0e0e0;"><?php echo esc_html($v['prenom'] ?? ''); ?></td>
            <td style="padding:8px 12px;border:1px solid #e0e0e0;"><?php
                $ddn_raw = $v['ddn'] ?? $v['date_naissance'] ?? '';
                if ($ddn_raw && (preg_match('/^\d{4}-\d{2}-\d{2}$/', $ddn_raw) || preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $ddn_raw))) {
                    $d = DateTime::createFromFormat('Y-m-d', $ddn_raw) ?: DateTime::createFromFormat('d/m/Y', $ddn_raw);
                    echo $d ? esc_html($d->format('d/m/Y')) : esc_html($ddn_raw);
                } else {
                    echo $ddn_raw ? esc_html($ddn_raw) : '—';
                }
            ?></td>
            <td style="padding:8px 12px;border:1px solid #e0e0e0;"><?php echo esc_html($v['passeport'] ?? '—'); ?></td>
            <td style="padding:8px 12px;border:1px solid #e0e0e0;"><?php echo ($v['type'] ?? 'golfeur') === 'golfeur' ? 'Golfeur' : 'Accompagnant'; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php echo self::section_title('Informations voyage'); ?>
<div style="padding:0 32px 16px;">
    <table width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse;">
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;width:40%;font-weight:bold;">Organisateur / Détaillant</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo $c['name']; ?>, <?php echo $c['address']; ?> <?php echo $c['city']; ?></td></tr>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">Destination</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo esc_html(strtoupper($destination)); ?></td></tr>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">Intitulé du voyage</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo esc_html($data['voyage_titre'] ?? ''); ?></td></tr>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">Type de contrat</td><td style="padding:6px 12px;border:1px solid #e0e0e0;">FORFAIT / SÉJOUR GOLF</td></tr>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">Nb nuitées</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo $duree; ?></td></tr>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">Nb voyageur(s)</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo $nb_total; ?></td></tr>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">Date de départ</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo $date_depart ? date('d/m/Y', strtotime($date_depart)) : ''; ?></td></tr>
        <tr><td style="padding:6px 12px;border:1px solid #e0e0e0;background:#f8f8f8;font-weight:bold;">Date de retour</td><td style="padding:6px 12px;border:1px solid #e0e0e0;"><?php echo $date_retour ? date('d/m/Y', strtotime($date_retour)) : ''; ?></td></tr>
    </table>
</div>

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
    </table>
    <div style="margin-top:8px;font-size:11px;color:#888;line-height:1.5;">
        * Vols spéciaux : Les prix sont calculés de façon forfaitaire. Si, en raison des horaires imposés par les compagnies aériennes, la première et la dernière journée se trouvaient écourtées, aucun remboursement ne pourrait avoir lieu.<br>
        * Vols réguliers : Sous réserve de modification des horaires par la compagnie.<br>
        Conformément à l'article L. 211-10 du Code du Tourisme, vous recevrez en temps utile avant votre départ les documents nécessaires et les informations sur les horaires.
    </div>
</div>

<?php echo self::section_title('Hébergement'); ?>
<div style="padding:0 32px 16px;">
    <table width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-size:13px;">
        <tr style="background:#1a3a3a;color:#fff;font-size:11px;text-transform:uppercase;">
            <th style="padding:8px;border:1px solid #1a3a3a;text-align:left;">Hôtel</th>
            <th style="padding:8px;border:1px solid #1a3a3a;text-align:left;">Catégorie</th>
            <th style="padding:8px;border:1px solid #1a3a3a;text-align:left;">Arrivée</th>
            <th style="padding:8px;border:1px solid #1a3a3a;text-align:left;">Départ</th>
            <th style="padding:8px;border:1px solid #1a3a3a;text-align:left;">Nuits</th>
            <th style="padding:8px;border:1px solid #1a3a3a;text-align:left;">Chambre</th>
            <th style="padding:8px;border:1px solid #1a3a3a;text-align:left;">Pension</th>
        </tr>
        <tr style="background:#f8f8f8;">
            <td style="padding:6px 8px;border:1px solid #e0e0e0;font-weight:bold;"><?php echo esc_html($hotel_nom ?: 'Hôtel du séjour'); ?></td>
            <td style="padding:6px 8px;border:1px solid #e0e0e0;"><?php echo $hotel_etoiles ? str_repeat('★', intval($hotel_etoiles)) : ''; ?></td>
            <td style="padding:6px 8px;border:1px solid #e0e0e0;"><?php echo $date_depart ? date('d/m/Y', strtotime($date_depart)) : ''; ?></td>
            <td style="padding:6px 8px;border:1px solid #e0e0e0;"><?php echo $date_retour ? date('d/m/Y', strtotime($date_retour)) : ''; ?></td>
            <td style="padding:6px 8px;border:1px solid #e0e0e0;"><?php echo $duree; ?></td>
            <td style="padding:6px 8px;border:1px solid #e0e0e0;"><?php echo esc_html(ucfirst($params['type_chambre'] ?? 'double')); ?> x<?php echo intval($params['nb_chambres'] ?? 1); ?></td>
            <td style="padding:6px 8px;border:1px solid #e0e0e0;"><?php echo esc_html($pension_label); ?></td>
        </tr>
    </table>
</div>

<?php echo self::section_title('Assurances'); ?>
<div style="padding:0 32px 16px;font-size:13px;line-height:1.6;">
    <?php if ($assurance > 0): ?>
    <p>- Assurance(s) proposée(s) par le vendeur et acceptée(s) par le client : <strong>Assurance annulation (<?php echo number_format($assurance, 2, ',', ' '); ?> &euro;)</strong></p>
    <?php else: ?>
    <p>- Assurance(s) proposée(s) refusée(s) par le client : Multirisques / Annulation.</p>
    <?php endif; ?>
    <p style="font-size:11px;color:#888;">Le contrat d'assurance est joint au présent bulletin d'inscription.</p>
</div>

<?php echo self::section_title('Décompte'); ?>
<div style="padding:0 32px 16px;">
    <!-- Détail des prestations : une ligne forfait + assurance si souscrite, sans quantités -->
    <table width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-size:13px;margin-bottom:12px;">
        <tr style="background:#1a3a3a;color:#fff;font-size:11px;text-transform:uppercase;">
            <th style="padding:8px 12px;text-align:left;border:1px solid #1a3a3a;">Détail des prestations</th>
        </tr>
        <tr style="background:#f8f8f8;">
            <td style="padding:6px 12px;border:1px solid #e0e0e0;">Forfait séjour golfique à <?php echo $hotel_nom ? esc_html($hotel_nom) : 'l\'hôtel réservé'; ?></td>
        </tr>
        <?php if ($assurance > 0): ?>
        <tr>
            <td style="padding:6px 12px;border:1px solid #e0e0e0;">Assurance Multirisques</td>
        </tr>
        <?php endif; ?>
    </table>
    <!-- Montants uniquement en bas -->
    <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;font-size:14px;">
        <tr style="background:#edf8f8;font-weight:bold;">
            <td style="padding:10px 12px;border:2px solid #2a7f7f;">Montant total</td>
            <td style="padding:10px 12px;border:2px solid #2a7f7f;text-align:right;font-size:16px;"><?php echo number_format($total, 2, ',', ' '); ?> &euro;</td>
        </tr>
        <?php if ($payer_tout): ?>
        <tr style="background:#fff3e6;font-weight:bold;">
            <td style="padding:10px 12px;border:1px solid #e8724a;color:#e8724a;">Paiement intégral</td>
            <td style="padding:10px 12px;border:1px solid #e8724a;text-align:right;color:#e8724a;"><?php echo number_format($total, 2, ',', ' '); ?> &euro;</td>
        </tr>
        <?php else: ?>
        <tr style="background:#fff3e6;font-weight:bold;">
            <td style="padding:10px 12px;border:1px solid #e8724a;color:#e8724a;">Acompte (<?php echo $acompte_pct; ?>%)</td>
            <td style="padding:10px 12px;border:1px solid #e8724a;text-align:right;color:#e8724a;"><?php echo number_format($acompte, 2, ',', ' '); ?> &euro;</td>
        </tr>
        <tr>
            <td style="padding:8px 12px;border:1px solid #e0e0e0;">Solde à verser au plus tard le <?php echo $solde_date ?: '—'; ?> *</td>
            <td style="padding:8px 12px;border:1px solid #e0e0e0;text-align:right;"><?php echo number_format($solde, 2, ',', ' '); ?> &euro;</td>
        </tr>
        <tr><td colspan="2" style="padding:4px 12px;font-size:11px;color:#888;">* Sans rappel de notre part et sous peine de non réalisation du voyage et moyennant les frais d'annulation prévus.</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php echo self::section_title('Formalités'); ?>
<div style="padding:0 32px 16px;font-size:12px;line-height:1.6;color:#555;">
    <p><strong>Police :</strong> Passeport en cours de validité.</p>
    <p><strong>Santé :</strong> Aucune formalité obligatoire (à vérifier selon la destination).</p>
    <p>Formalités données à titre d'information, dont l'accomplissement incombe au client. Les informations ci-dessus sont communiquées selon les données disponibles à la date d'établissement du contrat et sont susceptibles de modification.</p>
    <p>Vous devez vous tenir informés de leur évolution en consultant notamment les sites :<br>
    <a href="https://www.pasteur.fr/fr" style="color:#2a7f7f;">https://www.pasteur.fr/fr</a><br>
    <a href="https://www.diplomatie.gouv.fr/fr/conseils-aux-voyageurs/" style="color:#2a7f7f;">https://www.diplomatie.gouv.fr/fr/conseils-aux-voyageurs/</a></p>
</div>

<?php echo self::section_title('Révision du prix'); ?>
<div style="padding:0 32px 16px;font-size:12px;line-height:1.6;color:#555;">
    <p>Conformément aux articles L.211-12, R. 211-8 et R. 211-9 du Code du tourisme, les prix prévus au contrat sont révisables à la hausse comme à la baisse pour tenir compte des variations du coût des transports (carburant/énergie), des redevances et taxes et des taux de change.</p>
    <p>Vous serez informé de toute hausse du prix total du forfait, au plus tard 20 jours avant le départ. Pour toute hausse supérieure à 8%, vous recevrez le détail de la variation du prix et le choix d'accepter ou de refuser.</p>
</div>

<?php
        $annulation_paliers = $m['annulation'] ?? [];
        if (!is_array($annulation_paliers)) $annulation_paliers = [];
        $annulation_texte   = $m['annulation_texte'] ?? '';
?>
<?php echo self::section_title('Conditions de modification et d\'annulation'); ?>
<div style="padding:0 32px 16px;font-size:12px;line-height:1.6;color:#555;">
    <p><strong>Absence de droit de rétractation</strong> — Conformément aux articles L. 221-2 et L. 221-28 du Code de la consommation, le présent contrat n'est pas soumis au droit de rétractation.</p>
    <p><strong>Frais d'annulation</strong> (conditions définies au bon de commande) — Le voyageur a la possibilité d'annuler/résoudre le présent contrat moyennant le paiement des frais suivants (sur le prix total du voyage) :</p>
    <?php if (!empty($annulation_paliers)): ?>
    <ul style="margin:4px 0 4px 20px;">
        <?php foreach ($annulation_paliers as $p):
            if (!is_array($p)) continue;
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
        <li>De 60 à 30 jours avant le départ : 50% du prix total</li>
        <li>De 30 à 15 jours avant le départ : 75% du prix total</li>
        <li>Moins de 15 jours avant le départ : 100% du prix total</li>
    </ul>
    <?php endif; ?>
    <?php if ($annulation_texte !== ''): ?>
    <p style="margin-top:10px;"><?php echo nl2br(esc_html($annulation_texte)); ?></p>
    <?php endif; ?>
    <p><strong>Cession du contrat</strong> — Conformément à l'article L. 211-11 du Code du Tourisme, vous avez la possibilité de céder le présent contrat jusqu'à 7 jours du départ, à une personne remplissant les mêmes conditions que vous. Le cédant et le cessionnaire demeurent solidairement tenus du paiement du solde et des frais de cession.</p>
</div>

<?php echo self::section_title('Responsabilité et réclamations'); ?>
<div style="padding:0 32px 16px;font-size:12px;line-height:1.6;color:#555;">
    <p>Le détaillant et l'organisateur sont responsables de la bonne exécution des services prévus au présent contrat et sont tenus d'apporter de l'aide au voyageur en difficulté.</p>
    <p><strong>Réclamations :</strong> Le voyageur peut saisir le service client à l'adresse suivante : <?php echo $c['name']; ?> <?php echo $c['address']; ?> <?php echo $c['city']; ?> par lettre RAR ou par mail à <?php echo $c['email']; ?>.</p>
    <p>A défaut de réponse satisfaisante dans un délai de 60 jours, le client peut saisir le Médiateur du Tourisme et du Voyage : <a href="https://www.mtv.travel" style="color:#2a7f7f;">www.mtv.travel</a> — MTV Médiation Tourisme Voyage — BP 80 303 — 75 823 Paris Cedex 17.</p>
</div>

<?php echo self::section_title('Protection des données personnelles'); ?>
<div style="padding:0 32px 16px;font-size:12px;line-height:1.6;color:#555;">
    <p>La personne concluant le présent contrat accepte de transmettre ses données à l'agence dans le but de son exécution et garantit qu'il a recueilli le consentement des autres voyageurs aux mêmes fins.</p>
    <p>Conformément à la législation en vigueur, vous disposez d'un droit d'accès, de rectification, de suppression et de portabilité des données personnelles vous concernant. Pour exercer ces droits : <?php echo $c['email']; ?>.</p>
</div>

<?php echo self::section_title('Annexe — Formulaire d\'information standard (Directive UE 2015/2302)'); ?>
<div style="padding:0 32px 16px;font-size:11px;line-height:1.6;color:#666;">
    <p>La combinaison de services de voyage qui vous est proposée est un forfait au sens de la directive (UE) 2015/2302 et de l'article L.211-2 II du code du tourisme. Vous bénéficierez de tous les droits octroyés par l'Union européenne applicables aux forfaits. <?php echo $c['name']; ?> sera entièrement responsable de la bonne exécution du forfait dans son ensemble.</p>
    <p>En outre, <?php echo $c['name']; ?> dispose d'une protection afin de rembourser vos paiements et, si le transport est compris dans le forfait, d'assurer votre rapatriement au cas où elle deviendrait insolvable.</p>
    <p><strong>Droits essentiels :</strong></p>
    <ul style="margin:4px 0 4px 16px;">
        <li>Les voyageurs recevront toutes les informations essentielles sur le forfait avant de conclure le contrat.</li>
        <li>L'organisateur et le détaillant sont responsables de la bonne exécution de tous les services de voyage compris dans le contrat.</li>
        <li>Les voyageurs reçoivent un numéro de téléphone d'urgence : <?php echo $c['tel']; ?>.</li>
        <li>Les voyageurs peuvent céder leur forfait à une autre personne, moyennant un préavis raisonnable.</li>
        <li>Le prix du forfait ne peut être augmenté que si des coûts spécifiques augmentent et si cette possibilité est explicitement prévue dans le contrat.</li>
        <li>Si la majoration dépasse 8%, le voyageur peut résoudre le contrat.</li>
        <li>Les voyageurs peuvent résoudre le contrat sans frais en cas de circonstances exceptionnelles affectant la sécurité.</li>
        <li>Si l'organisateur devient insolvable, les montants versés seront remboursés. Protection : <?php echo $c['garantie']; ?>.</li>
    </ul>
    <p>Site de la directive : <a href="https://www.legifrance.gouv.fr" style="color:#2a7f7f;">legifrance.gouv.fr</a></p>
</div>

<!-- ═══════════════ FOOTER ═══════════════ -->
<div style="background:#1a3a3a;color:#b0cece;padding:16px 32px;font-size:10px;line-height:1.6;text-align:center;">
    Siège social : <?php echo $c['legal']; ?> <?php echo $c['address']; ?> <?php echo $c['city']; ?> — Capital de <?php echo $c['capital']; ?> &euro; — RCS <?php echo $c['rcs']; ?><br>
    APE <?php echo $c['ape']; ?> — TVA intra. <?php echo $c['tva_intra']; ?> — RCP : <?php echo $c['rcp']; ?><br>
    Garantie Financière : <?php echo $c['garantie']; ?> — Immat. <?php echo $c['immat']; ?> — Siret <?php echo $c['siret']; ?>
</div>

</div><!-- /main wrapper -->
</body>
</html>
        <?php
        return ob_get_clean();
    }

    private static function section_title($title) {
        return '<div style="background:#2a7f7f;color:#fff;padding:8px 32px;font-size:13px;font-weight:bold;text-transform:uppercase;letter-spacing:1px;margin-top:4px;">' . esc_html($title) . '</div>';
    }
}
