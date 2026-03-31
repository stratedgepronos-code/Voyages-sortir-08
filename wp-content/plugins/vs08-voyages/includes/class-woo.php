<?php
class VS08V_Woo {

    /**
     * Crée un produit WooCommerce temporaire pour ce devis
     */
    public function create_booking_product($data) {
        if (!class_exists('WC_Product')) {
            return new WP_Error('woo_missing', 'WooCommerce requis.');
        }

        $voyage_id    = $data['voyage_id'];
        $m            = VS08V_MetaBoxes::get($voyage_id);
        $payer_tout   = $data['payer_tout'];
        $acompte_pct  = isset($data['acompte_pct_applied'])
            ? floatval($data['acompte_pct_applied'])
            : floatval($m['acompte_pct'] ?? 30);
        $total        = floatval($data['total']);
        $acompte      = floatval($data['acompte']);
        $prix_final   = $payer_tout ? $total : $acompte;

        // Nom du produit
        $label_type = $payer_tout
            ? 'Paiement intégral'
            : 'Acompte ' . $acompte_pct . '%';

        $product_name = sprintf(
            'Réservation — %s — %s (%s — %d pers.)',
            $data['voyage_titre'] ?? 'Séjour',
            date('d/m/Y', strtotime($data['params']['date_depart'] ?? 'now')),
            $label_type,
            (int) ($data['devis']['nb_total'] ?? 1)
        );

        // Chercher un produit existant avec le même hash
        $hash = md5(serialize($data));
        $existing = get_posts([
            'post_type'  => 'product',
            'meta_key'   => '_vs08v_booking_hash',
            'meta_value' => $hash,
            'posts_per_page' => 1,
        ]);
        if ($existing) return $existing[0]->ID;

        // Créer le produit simple WooCommerce
        $product = new WC_Product_Simple();
        $product->set_name($product_name);
        $product->set_price($prix_final);
        $product->set_regular_price($prix_final);
        $product->set_status('publish');
        $product->set_virtual(true);
        $product->set_sold_individually(true);
        $product->set_catalog_visibility('hidden');

        // Description du récap complet
        $desc = self::build_product_description($data, $payer_tout, $acompte_pct);
        $product->set_description($desc);
        $product->set_short_description($product_name);

        $product_id = $product->save();

        // Stocker toutes les données de réservation
        update_post_meta($product_id, '_vs08v_booking_data', $data);
        update_post_meta($product_id, '_vs08v_booking_hash', $hash);
        update_post_meta($product_id, '_vs08v_voyage_id', $voyage_id);
        update_post_meta($product_id, '_vs08v_total_voyage', $total);
        update_post_meta($product_id, '_vs08v_acompte', $acompte);
        update_post_meta($product_id, '_vs08v_payer_tout', $payer_tout);

        // Ajouter thumbnail depuis la galerie du voyage
        $galerie = $m['galerie'] ?? [];
        if (!empty($galerie[0])) {
            $attachment_id = attachment_url_to_postid($galerie[0]);
            if ($attachment_id) set_post_thumbnail($product_id, $attachment_id);
        }

        return $product_id;
    }

    private static function build_product_description($data, $payer_tout, $acompte_pct) {
        $total = $data['total'] ?? 0;
        $acompte = $data['acompte'] ?? 0;
        $params = $data['params'] ?? [];
        $devis = $data['devis'] ?? [];
        $voyage_id = (int) ($data['voyage_id'] ?? 0);
        if (!$voyage_id) return '';
        $m = VS08V_MetaBoxes::get($voyage_id);

        $pension_labels = ['bb'=>'Petit-déjeuner','dp'=>'Demi-pension','pc'=>'Pension complète','ai'=>'Tout inclus'];
        $hotel_nom    = $m['hotel_nom'] ?? ($m['hotel']['nom'] ?? '');
        $hotel_etoiles= $m['hotel_etoiles'] ?? ($m['hotel']['etoiles'] ?? '');
        $pension_code = $m['pension'] ?? ($m['hotel']['pension'] ?? '');
        $pension_label= $pension_labels[$pension_code] ?? '';

        ob_start(); ?>
        <div class="vs08v-woo-recap">
            <h3>📋 Récapitulatif de votre réservation</h3>
            <table>
                <tr><td><strong>Voyage</strong></td><td><?php echo esc_html($data['voyage_titre']); ?></td></tr>
                <tr><td><strong>Date de départ</strong></td><td><?php echo esc_html(date('d/m/Y', strtotime($params['date_depart']??''))); ?></td></tr>
                <tr><td><strong>Aéroport</strong></td><td><?php echo esc_html(strtoupper((string)($params['aeroport']??''))); ?></td></tr>
                <?php if (!empty($params['vol_aller_num'])): ?>
                <tr><td><strong>✈️ Vol aller</strong></td><td><?php echo esc_html($params['vol_aller_num']); ?><?php if (!empty($params['vol_aller_cie'])): ?> (<?php echo esc_html($params['vol_aller_cie']); ?>)<?php endif; ?> — <?php echo esc_html($params['vol_aller_depart']); ?> → <?php echo esc_html($params['vol_aller_arrivee']); ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($params['vol_retour_num'])): ?>
                <tr><td><strong>✈️ Vol retour</strong></td><td><?php echo esc_html($params['vol_retour_num']); ?> — <?php echo esc_html($params['vol_retour_depart']); ?> → <?php echo esc_html($params['vol_retour_arrivee']); ?></td></tr>
                <?php endif; ?>
                <tr><td><strong>Durée</strong></td><td><?php echo esc_html($m['duree']??''); ?> nuits</td></tr>
                <?php if (!empty($hotel_nom)): ?>
                <tr><td><strong>🏨 Hôtel</strong></td><td><?php echo esc_html($hotel_nom); ?><?php if ($hotel_etoiles): ?> <?php echo str_repeat('★', intval($hotel_etoiles)); ?><?php endif; ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($pension_label)): ?>
                <tr><td><strong>Formule</strong></td><td><?php echo esc_html($pension_label); ?></td></tr>
                <?php endif; ?>
                <tr><td><strong>Voyageurs</strong></td><td><?php echo esc_html($devis['nb_total']); ?> personne(s) (<?php echo esc_html($params['nb_golfeurs']??0); ?> golfeur(s) + <?php echo esc_html($params['nb_nongolfeurs']??0); ?> non-golfeur(s))</td></tr>
                <tr><td><strong>Type chambre</strong></td><td><?php echo esc_html(ucfirst($params['type_chambre']??'')); ?></td></tr>
            </table>
            <h4>💰 Détail du prix</h4>
            <table>
            <?php foreach (($devis['lines'] ?? []) as $line): ?>
                <tr><td><?php echo esc_html($line['label'] ?? ''); ?><br><small><?php echo esc_html($line['detail']??''); ?></small></td><td><?php echo number_format((float)($line['montant']??0),2,',',' '); ?> €</td></tr>
            <?php endforeach; ?>
            <?php foreach (($data['options'] ?? []) as $opt): ?>
                <tr><td>Option : <?php echo esc_html($opt['label']); ?> (x<?php echo $opt['qty'];?>)</td><td><?php echo number_format($opt['prix'],2,',',' '); ?> €</td></tr>
            <?php endforeach; ?>
            <?php if (($data['assurance'] ?? 0) > 0): ?>
                <tr><td>Assurance annulation</td><td><?php echo number_format($data['assurance'],2,',',' '); ?> €</td></tr>
            <?php endif; ?>
                <tr style="font-weight:bold;border-top:2px solid #333"><td>TOTAL VOYAGE</td><td><?php echo number_format($total,2,',',' '); ?> €</td></tr>
            <?php if (!$payer_tout): ?>
                <tr style="color:#e8724a"><td>Acompte à régler (<?php echo $acompte_pct; ?>%)</td><td><?php echo number_format($acompte,2,',',' '); ?> €</td></tr>
                <tr><td>Solde à régler <?php echo intval($m['delai_solde']??30); ?> jours avant le départ</td><td><?php echo number_format($total - $acompte,2,',',' '); ?> €</td></tr>
            <?php endif; ?>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }
}

// ════════════════════════════════════════════════════════════════════
// HOOKS CHECKOUT — approche "zéro écriture lourde pendant le checkout"
// Les booking_data vivent sur le produit temporaire. Pendant le checkout,
// on stocke SEULEMENT le voyage_id sur le line item (4 octets).
// La copie complète se fait APRÈS le checkout (thankyou ou payment_complete),
// dans une requête séparée où la mémoire est libre.
// C'est la même approche que vs08-sejours — prouvée, fiable.
// ════════════════════════════════════════════════════════════════════

/**
 * Copie les booking_data depuis le produit vers la commande.
 * Idempotent : si déjà copié, ne fait rien.
 * Utilisé par thankyou + payment_complete comme double filet.
 */
function vs08v_copy_booking_data_to_order($order_id) {
    if (!$order_id) return;

    // Déjà copié ? (idempotent)
    $existing = get_post_meta($order_id, '_vs08v_booking_data', true);
    if (!empty($existing) && is_array($existing)) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    foreach ($order->get_items() as $item) {
        $pid = $item->get_product_id();
        if (!$pid) continue;

        $data = get_post_meta($pid, '_vs08v_booking_data', true);
        if (empty($data) || !is_array($data)) continue;

        // Type séjour ou circuit → géré par leur propre plugin
        $type = $data['type'] ?? '';
        if ($type === 'sejour' || $type === 'circuit') continue;

        // Copier en base directement (pas $order->save())
        update_post_meta($order_id, '_vs08v_booking_data', $data);
        error_log('[VS08] booking_data copié sur order #' . $order_id . ' depuis product #' . $pid);
        return;
    }
}

// ── 1. Line item : juste le voyage_id (pendant le checkout) ──
add_action('woocommerce_checkout_create_order_line_item', function($item, $cart_item_key, $values, $order) {
    try {
        $product_id = $item->get_product_id();
        if (!$product_id) return;
        $booking_data = get_post_meta($product_id, '_vs08v_booking_data', true);
        if (!empty($booking_data) && is_array($booking_data)) {
            $item->add_meta_data('_vs08v_voyage_id', $booking_data['voyage_id'] ?? 0, true);
        }
    } catch (\Throwable $e) {
        error_log('VS08 checkout_create_order_line_item: ' . $e->getMessage());
    }
}, 10, 4);

// ── 2. Page merci : copier les booking_data (requête GET, mémoire libre) ──
add_action('woocommerce_thankyou', 'vs08v_copy_booking_data_to_order', 0);

// ── 3. Backup : copier aussi sur payment_complete (callback Paybox côté serveur) ──
// Si le client ferme le navigateur après paiement, la page merci ne charge pas.
// Mais payment_complete est appelé par le callback IPN de Paybox → les données
// sont quand même copiées sur la commande.
add_action('woocommerce_payment_complete', 'vs08v_copy_booking_data_to_order', 5);

// Afficher les infos de réservation + option "Solde marqué réglé" dans la commande admin
add_action('woocommerce_admin_order_data_after_order_details', function($order) {
    $booking = class_exists('VS08V_Traveler_Space') ? VS08V_Traveler_Space::get_booking_data_from_order($order) : null;
    if (!$booking) {
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $booking = get_post_meta($product_id, '_vs08v_booking_data', true);
            if (!$booking) continue;
            echo '<div style="margin-top:16px;padding:14px;background:#edf8f8;border-radius:8px">';
            echo '<strong>📋 Détails du voyage réservé :</strong><br>';
            echo '<small>Voyage : '.esc_html($booking['voyage_titre']).' — '.esc_html(date('d/m/Y', strtotime($booking['params']['date_depart']??'now'))).'</small><br>';
            echo '<small>Voyageurs : '.esc_html($booking['devis']['nb_total']).' pers.</small><br>';
            echo '<a href="'.admin_url('post.php?post='.$product_id.'&action=edit').'" class="button" style="margin-top:8px">Voir le récap complet</a>';
            echo '</div>';
            return;
        }
        return;
    }
    echo '<div style="margin-top:16px;padding:14px;background:#edf8f8;border-radius:8px">';
    echo '<strong>📋 Dossier voyage VS08</strong><br>';
    echo '<small>'.esc_html($booking['voyage_titre'] ?? 'Séjour').' — '.esc_html(date('d/m/Y', strtotime($booking['params']['date_depart'] ?? 'now'))).' — '.esc_html($booking['devis']['nb_total'] ?? 0).' pers.</small><br>';
    $solde_marque = $order->get_meta('_vs08v_solde_marque_paye');
    echo '<p style="margin:12px 0 0"><label><input type="checkbox" name="vs08v_solde_marque_paye" value="1" '.($solde_marque ? ' checked' : '').'> Solde marqué comme réglé (virement, chèque, agence…)</label></p>';
    echo '</div>';
});

// Sauvegarder la case "Solde marqué réglé" à l'enregistrement de la commande
// UNIQUEMENT en admin (pas pendant le checkout !) et SANS $order->save()
// car save() re-déclenche woocommerce_update_order → boucle infinie.
add_action('woocommerce_process_shop_order_meta', function($order_id) {
    if (!is_admin() || wp_doing_ajax()) return;
    if (!class_exists('VS08V_Traveler_Space')) return;
    $order = wc_get_order($order_id);
    if (!$order || !VS08V_Traveler_Space::get_booking_data_from_order($order)) return;
    if (!empty($_POST['vs08v_solde_marque_paye'])) {
        update_post_meta($order_id, '_vs08v_solde_marque_paye', 1);
    } else {
        delete_post_meta($order_id, '_vs08v_solde_marque_paye');
    }
}, 20);

// Masquer les produits de réservation du catalogue
add_filter('woocommerce_product_query_meta_query', function($meta_query) {
    $meta_query[] = [
        'key'     => '_vs08v_voyage_id',
        'compare' => 'NOT EXISTS',
    ];
    return $meta_query;
});

// Filet de sécurité : les produits de réservation VS08 sont TOUJOURS achetables
// (même si leur statut est encore 'private' dans la base — anciens produits)
add_filter('woocommerce_is_purchasable', function($purchasable, $product) {
    if (!$purchasable && $product && $product->get_id()) {
        $is_vs08 = get_post_meta($product->get_id(), '_vs08v_booking_data', true)
                || get_post_meta($product->get_id(), '_vs08v_voyage_id', true)
                || get_post_meta($product->get_id(), '_vs08c_booking_data', true)
                || get_post_meta($product->get_id(), '_vs08s_booking_data', true);
        if ($is_vs08) return true;
    }
    return $purchasable;
}, 10, 2);
