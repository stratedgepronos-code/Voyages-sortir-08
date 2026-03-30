<?php
if (!defined('ABSPATH')) exit;

class VS08C_Woo {

    /**
     * Crée un produit WooCommerce temporaire pour ce devis circuit.
     */
    public static function create_booking_product($data) {
        if (!class_exists('WC_Product')) {
            return new WP_Error('woo_missing', 'WooCommerce requis.');
        }

        $circuit_id  = $data['circuit_id'];
        $payer_tout  = $data['payer_tout'];
        $acompte_pct = floatval($data['acompte_pct'] ?? 30);
        $total       = floatval($data['total']);
        $acompte     = floatval($data['acompte']);
        $prix_final  = $payer_tout ? $total : $acompte;

        $label_type = $payer_tout ? 'Paiement intégral' : 'Acompte ' . $acompte_pct . '%';

        $product_name = sprintf(
            'Réservation Circuit — %s — %s (%s — %d pers.)',
            $data['circuit_titre'] ?? 'Circuit',
            date('d/m/Y', strtotime($data['params']['date_depart'] ?? 'now')),
            $label_type,
            (int) ($data['devis']['nb_total'] ?? 1)
        );

        // Vérifier doublon
        $hash = md5(serialize($data));
        $existing = get_posts([
            'post_type'      => 'product',
            'meta_key'       => '_vs08c_booking_hash',
            'meta_value'     => $hash,
            'posts_per_page' => 1,
        ]);
        if ($existing) return $existing[0]->ID;

        $product = new WC_Product_Simple();
        $product->set_name($product_name);
        $product->set_price($prix_final);
        $product->set_regular_price($prix_final);
        $product->set_status('private');
        $product->set_virtual(true);
        $product->set_sold_individually(true);
        $product->set_catalog_visibility('hidden');

        $desc = self::build_description($data, $payer_tout, $acompte_pct);
        $product->set_description($desc);
        $product->set_short_description($product_name);

        $product_id = $product->save();

        update_post_meta($product_id, '_vs08c_booking_data', $data);
        update_post_meta($product_id, '_vs08c_booking_hash', $hash);
        update_post_meta($product_id, '_vs08c_circuit_id', $circuit_id);
        update_post_meta($product_id, '_vs08c_total', $total);
        update_post_meta($product_id, '_vs08c_acompte', $acompte);
        update_post_meta($product_id, '_vs08c_payer_tout', $payer_tout);

        // Thumbnail
        $m = VS08C_Meta::get($circuit_id);
        $galerie = $m['galerie'] ?? [];
        if (!empty($galerie[0])) {
            $att_id = attachment_url_to_postid($galerie[0]);
            if ($att_id) set_post_thumbnail($product_id, $att_id);
        }

        return $product_id;
    }

    private static function build_description($data, $payer_tout, $acompte_pct) {
        $circuit_id = (int) ($data['circuit_id'] ?? 0);
        if (!$circuit_id) return '';
        $total   = (float) ($data['total'] ?? 0);
        $acompte = (float) ($data['acompte'] ?? 0);
        $params  = $data['params'] ?? [];
        $devis   = $data['devis'] ?? [];
        $m       = VS08C_Meta::get($circuit_id);

        $pension_labels   = ['bb'=>'Petit-déjeuner','dp'=>'Demi-pension','pc'=>'Pension complète (hors boissons)','ai'=>'Tout inclus','mixed'=>'Selon programme'];
        $transport_labels = ['bus'=>'Bus climatisé','4x4'=>'4x4','voiture'=>'Voiture de location','train'=>'Train','mixed'=>'Transport mixte'];
        $etoiles_map      = ['2'=>'★★','3'=>'★★★','4'=>'★★★★','5'=>'★★★★★','riad'=>'Riad','camp'=>'Bivouac'];

        $flag        = VS08C_Meta::resolve_flag($m);
        $duree_j     = intval($m['duree_jours'] ?? (intval($m['duree'] ?? 7) + 1));
        $duree_n     = intval($m['duree'] ?? 7);
        $pension_lbl = $pension_labels[$m['pension'] ?? ''] ?? '';
        $transport_lbl = $transport_labels[$m['transport'] ?? ''] ?? '';
        $hotels      = $m['hotels'] ?? [];
        $nb_chambres = intval($params['nb_chambres'] ?? 1);

        ob_start(); ?>
        <div class="vs08c-woo-recap">
            <h3>📋 Récapitulatif de votre réservation</h3>

            <table>
                <tr><td><strong>🗺️ Circuit</strong></td><td><?php echo esc_html($flag . ' ' . ($data['circuit_titre'] ?? 'Circuit')); ?></td></tr>
                <tr><td><strong>📍 Destination</strong></td><td><?php echo esc_html($m['destination'] ?? ''); ?></td></tr>
                <tr><td><strong>📅 Date de départ</strong></td><td><?php echo esc_html(date('d/m/Y', strtotime($params['date_depart'] ?? 'now'))); ?></td></tr>
                <tr><td><strong>✈️ Aéroport</strong></td><td><?php echo esc_html(strtoupper($params['aeroport'] ?? '')); ?></td></tr>
                <?php if (!empty($params['vol_aller_num'])): ?>
                <tr><td><strong>✈️ Vol aller</strong></td><td><?php echo esc_html($params['vol_aller_num']); ?><?php if (!empty($params['vol_aller_cie'])): ?> (<?php echo esc_html($params['vol_aller_cie']); ?>)<?php endif; ?> — <?php echo esc_html($params['vol_aller_depart'] ?? ''); ?> → <?php echo esc_html($params['vol_aller_arrivee'] ?? ''); ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($params['vol_retour_num'])): ?>
                <tr><td><strong>✈️ Vol retour</strong></td><td><?php echo esc_html($params['vol_retour_num']); ?> — <?php echo esc_html($params['vol_retour_depart'] ?? ''); ?> → <?php echo esc_html($params['vol_retour_arrivee'] ?? ''); ?></td></tr>
                <?php endif; ?>
                <tr><td><strong>📅 Durée</strong></td><td><?php echo $duree_j; ?> jours / <?php echo $duree_n; ?> nuits</td></tr>
                <?php if ($pension_lbl): ?>
                <tr><td><strong>🍽️ Formule</strong></td><td><?php echo esc_html($pension_lbl); ?></td></tr>
                <?php endif; ?>
                <?php if ($transport_lbl): ?>
                <tr><td><strong>🚌 Transport</strong></td><td><?php echo esc_html($transport_lbl); ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($m['guide_lang'])): ?>
                <tr><td><strong>🗣️ Guide</strong></td><td><?php echo esc_html($m['guide_lang']); ?></td></tr>
                <?php endif; ?>
                <tr><td><strong>👥 Voyageurs</strong></td><td><?php echo (int)($devis['nb_total'] ?? 0); ?> personne(s)</td></tr>
                <tr><td><strong>🛏️ Chambres</strong></td><td><?php echo $nb_chambres; ?> chambre(s)</td></tr>
            </table>

            <?php if (!empty($hotels)): ?>
            <h4>🏨 Hébergements</h4>
            <table>
                <?php foreach ($hotels as $hotel):
                    if (empty($hotel['nom'])) continue;
                    $stars = $etoiles_map[$hotel['etoiles'] ?? '4'] ?? '';
                ?>
                <tr>
                    <td><strong><?php echo esc_html($hotel['nom']); ?></strong> <?php echo $stars; ?></td>
                    <td><?php echo esc_html($hotel['ville'] ?? ''); ?> — <?php echo intval($hotel['nuits'] ?? 1); ?> nuit(s)</td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>

            <?php if (!empty($data['voyageurs'])): ?>
            <h4>👥 Voyageurs</h4>
            <table>
                <tr style="font-weight:bold;background:#f8f8f8"><td>#</td><td>Nom</td><td>DDN</td><td>Ch.</td></tr>
                <?php foreach ($data['voyageurs'] as $vi => $v): ?>
                <tr>
                    <td><?php echo $vi + 1; ?></td>
                    <td><?php echo esc_html(($v['prenom'] ?? '') . ' ' . strtoupper($v['nom'] ?? '')); ?></td>
                    <td><?php echo esc_html($v['ddn'] ?? ''); ?></td>
                    <td>Ch.<?php echo intval($v['chambre'] ?? 1); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>

            <h4>💰 Détail du prix</h4>
            <table>
            <?php foreach ($devis['lines'] as $line): ?>
                <tr><td><?php echo esc_html($line['label']); ?><br><small><?php echo esc_html($line['detail'] ?? ''); ?></small></td><td style="text-align:right;white-space:nowrap"><?php echo number_format($line['montant'], 2, ',', ' '); ?> €</td></tr>
            <?php endforeach; ?>
            <?php foreach ($data['options'] as $opt): ?>
                <tr><td>Option : <?php echo esc_html($opt['label']); ?> (x<?php echo $opt['qty']; ?>)</td><td style="text-align:right;white-space:nowrap"><?php echo number_format($opt['prix'], 2, ',', ' '); ?> €</td></tr>
            <?php endforeach; ?>
            <?php if ($data['assurance'] > 0): ?>
                <tr><td>🛡️ Assurance annulation</td><td style="text-align:right;white-space:nowrap"><?php echo number_format($data['assurance'], 2, ',', ' '); ?> €</td></tr>
            <?php endif; ?>
                <tr style="font-weight:bold;border-top:2px solid #333"><td>TOTAL CIRCUIT</td><td style="text-align:right;white-space:nowrap;font-size:16px"><?php echo number_format($total, 2, ',', ' '); ?> €</td></tr>
                <tr><td colspan="2" style="text-align:right;font-size:12px;color:#888">soit <?php echo number_format($total / max(1, $devis['nb_total']), 2, ',', ' '); ?> €/pers.</td></tr>
            <?php if (!$payer_tout): ?>
                <tr style="color:#e8724a"><td>Acompte à régler (<?php echo $acompte_pct; ?>%)</td><td style="text-align:right;white-space:nowrap;font-weight:bold"><?php echo number_format($acompte, 2, ',', ' '); ?> €</td></tr>
                <tr><td>Solde à régler <?php echo intval($m['delai_solde'] ?? 30); ?> jours avant le départ</td><td style="text-align:right;white-space:nowrap"><?php echo number_format($total - $acompte, 2, ',', ' '); ?> €</td></tr>
            <?php endif; ?>
            </table>

            <?php if (!empty($data['facturation'])): $f = $data['facturation']; ?>
            <h4>🧾 Facturation</h4>
            <table>
                <tr><td><strong>Client</strong></td><td><?php echo esc_html(($f['prenom'] ?? '') . ' ' . strtoupper($f['nom'] ?? '')); ?></td></tr>
                <tr><td><strong>Email</strong></td><td><?php echo esc_html($f['email'] ?? ''); ?></td></tr>
                <tr><td><strong>Tél.</strong></td><td><?php echo esc_html($f['tel'] ?? ''); ?></td></tr>
                <?php if (!empty($f['adresse'])): ?>
                <tr><td><strong>Adresse</strong></td><td><?php echo esc_html($f['adresse'] . ', ' . ($f['cp'] ?? '') . ' ' . ($f['ville'] ?? '')); ?></td></tr>
                <?php endif; ?>
            </table>
            <?php endif; ?>
        </div>
        <?php return ob_get_clean();
    }
}

// Copier booking_data dans les items de commande ET sur la commande
add_action('woocommerce_checkout_create_order_line_item', function($item, $cart_item_key, $values, $order) {
    try {
        $pid = $item->get_product_id();
        if (!$pid) return;
        $bd = get_post_meta($pid, '_vs08c_booking_data', true);
        if (!empty($bd) && is_array($bd)) {
            $item->add_meta_data('_vs08c_booking_data', $bd, true);
            $item->add_meta_data('_vs08c_circuit_id', $bd['circuit_id'] ?? 0, true);
            if ($order && is_object($order)) {
                $order->update_meta_data('_vs08c_booking_data', $bd);
            }
        }
    } catch (Throwable $e) {
        error_log('VS08C checkout_create_order_line_item: ' . $e->getMessage());
    }
}, 10, 4);

// Copier sur la commande
add_action('woocommerce_checkout_update_order_meta', function($order_id) {
    try {
        $order = wc_get_order($order_id);
        if (!$order || $order->get_meta('_vs08c_booking_data')) return;
        foreach ($order->get_items() as $item) {
            $data = $item->get_meta('_vs08c_booking_data');
            if (!empty($data) && is_array($data)) {
                update_post_meta($order_id, '_vs08c_booking_data', $data);
                break;
            }
        }
    } catch (Throwable $e) {
        error_log('VS08C checkout_update_order_meta: ' . $e->getMessage());
    }
}, 10, 2);

// Masquer les produits circuit du catalogue
add_filter('woocommerce_product_query_meta_query', function($meta_query) {
    $meta_query[] = [
        'key'     => '_vs08c_circuit_id',
        'compare' => 'NOT EXISTS',
    ];
    return $meta_query;
});

// Afficher infos circuit dans commande admin
add_action('woocommerce_admin_order_data_after_order_details', function($order) {
    $bd = $order->get_meta('_vs08c_booking_data');
    if (empty($bd)) {
        foreach ($order->get_items() as $item) {
            $bd = $item->get_meta('_vs08c_booking_data');
            if (!empty($bd)) break;
        }
    }
    if (empty($bd) || ($bd['type'] ?? '') !== 'circuit') return;
    echo '<div style="margin-top:16px;padding:14px;background:#edf8f8;border-radius:8px">';
    echo '<strong>🗺️ Dossier Circuit VS08</strong><br>';
    echo '<small>' . esc_html($bd['circuit_titre'] ?? 'Circuit') . ' — ' . esc_html(date('d/m/Y', strtotime($bd['params']['date_depart'] ?? 'now'))) . ' — ' . esc_html($bd['devis']['nb_total'] ?? 0) . ' pers.</small><br>';
    echo '<a href="' . admin_url('post.php?post=' . ($bd['circuit_id'] ?? 0) . '&action=edit') . '" class="button" style="margin-top:8px">Voir le circuit</a>';
    echo '</div>';
});
