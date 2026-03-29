<?php
if (!defined('ABSPATH')) exit;

class VS08S_Booking {

    public static function register() {}

    /**
     * Crée un produit WooCommerce temporaire + cart token (même flow que golf/circuits).
     * PAS de $order->save() → évite le crash Yoast SEO 2 Go.
     */
    public static function create_order($sejour_id, $params) {
        if (!class_exists('WC_Product_Simple')) {
            return new \WP_Error('no_wc', 'WooCommerce non disponible.', ['status' => 500]);
        }

        $m     = VS08S_Meta::get($sejour_id);
        $titre = get_the_title($sejour_id);
        $devis = VS08S_Calculator::compute($sejour_id, $params);

        $total   = floatval($devis['total']);
        $acompte = $devis['payer_tout'] ? $total : floatval($devis['acompte']);

        $fact      = is_array($params['facturation'] ?? null) ? $params['facturation'] : [];
        $voyageurs = is_array($params['voyageurs'] ?? null)   ? $params['voyageurs']   : [];

        $acompte_pct = floatval($m['acompte_pct'] ?? 30);
        $label_type  = $devis['payer_tout'] ? 'Paiement intégral' : 'Acompte ' . $acompte_pct . '%';

        $product_name = sprintf(
            'Réservation — %s — %s (%s — %d pers.)',
            $titre,
            date('d/m/Y', strtotime($params['date_depart'] ?? 'now')),
            $label_type,
            intval($params['nb_adultes'] ?? 2)
        );

        error_log('[VS08S Booking] Début: ' . $product_name . ' total=' . $total . ' acompte=' . $acompte);

        // Booking data (sauvé sur le produit, copié sur la commande au checkout)
        $booking_data = [
            'type'           => 'sejour',
            'sejour_id'      => $sejour_id,
            'sejour_titre'   => $titre,
            'voyage_titre'   => $titre,
            'voyage_id'      => $sejour_id,
            'params'         => $params,
            'devis'          => $devis,
            'facturation'    => $fact,
            'voyageurs'      => $voyageurs,
            'total'          => $total,
            'acompte'        => $acompte,
            'payer_tout'     => $devis['payer_tout'],
            'assurance'      => $devis['assurance'] ?? 0,
            'options'        => [],
        ];

        // Éviter les doublons
        $hash = md5(serialize($booking_data));
        $existing = get_posts(['post_type' => 'product', 'meta_key' => '_vs08v_booking_hash', 'meta_value' => $hash, 'posts_per_page' => 1]);
        if ($existing) {
            $product_id = $existing[0]->ID;
        } else {
            // Créer le produit WooCommerce temporaire
            $product = new \WC_Product_Simple();
            $product->set_name($product_name);
            $product->set_price($acompte);
            $product->set_regular_price($acompte);
            $product->set_status('private');
            $product->set_virtual(true);
            $product->set_sold_individually(true);
            $product->set_catalog_visibility('hidden');
            $product->set_short_description($product_name);
            $product_id = $product->save();
            update_post_meta($product_id, '_vs08v_booking_hash', $hash);
        }

        // Legacy _vs08v_: garder un payload minimal (évite l’explosion mémoire au checkout).
        $booking_data_legacy = [
            'type'         => 'sejour',
            'sejour_id'    => $sejour_id,
            'voyage_id'    => $sejour_id,
            'sejour_titre' => $titre,
            'voyage_titre' => $titre,
            'total'        => $total,
            'acompte'      => $acompte,
            'payer_tout'   => !empty($devis['payer_tout']),
            'params'       => [
                'date_depart' => (string) ($params['date_depart'] ?? ''),
                'aeroport'    => strtoupper((string) ($params['aeroport'] ?? '')),
                'nb_adultes'  => intval($params['nb_adultes'] ?? 0),
                'nb_chambres' => intval($params['nb_chambres'] ?? 0),
            ],
        ];

        // TOUJOURS mettre à jour les données + description (neuf OU existant)
        update_post_meta($product_id, '_vs08v_booking_data', $booking_data_legacy);
        update_post_meta($product_id, '_vs08s_booking_data', $booking_data);
        update_post_meta($product_id, '_vs08v_voyage_id', $sejour_id);
        update_post_meta($product_id, '_vs08v_total_voyage', $total);
        update_post_meta($product_id, '_vs08v_acompte', $acompte);
        update_post_meta($product_id, '_vs08v_payer_tout', $devis['payer_tout']);

        $desc = self::build_description($sejour_id, $params, $devis, $m, $titre, $total, $acompte, $acompte_pct);
        wp_update_post(['ID' => $product_id, 'post_content' => $desc]);
        // Vider le cache WC produit
        clean_post_cache($product_id);
        wc_delete_product_transients($product_id);

        error_log('[VS08S Booking] Produit #' . $product_id . ' — type=sejour — booking_data mis à jour');

        // Payment mode (card ou agency)
        $payment_mode = ($params['vs08_payment_mode'] ?? 'card') === 'agency' ? 'agency' : 'card';
        $reglement_agence = ($payment_mode === 'agency');

        // Stocker le mode de paiement sur le produit
        update_post_meta($product_id, '_vs08v_payment_mode', $payment_mode);
        update_post_meta($product_id, '_vs08v_reglement_agence', $reglement_agence ? 1 : 0);

        // Cart token (même mécanisme que golf)
        $cart_token = wp_generate_password(32, false);
        set_transient('vs08_cart_' . $cart_token, [
            'product_id'   => $product_id,
            'payment_mode' => $payment_mode,
        ], 900);

        // Tenter l'ajout au panier WooCommerce
        try {
            if (function_exists('WC') && WC()) {
                if (is_null(WC()->session) && method_exists(WC(), 'initialize_session')) WC()->initialize_session();
                if (is_null(WC()->cart)) {
                    if (function_exists('wc_load_cart')) wc_load_cart();
                    elseif (method_exists(WC(), 'initialize_cart')) WC()->initialize_cart();
                }
                if (WC()->cart) {
                    WC()->cart->empty_cart();
                    WC()->cart->add_to_cart($product_id, 1);
                    if (WC()->session) {
                        if (!WC()->session->has_session()) WC()->session->set_customer_session_cookie(true);
                        WC()->cart->calculate_totals();
                        WC()->cart->set_session();
                        WC()->cart->maybe_set_cart_cookies();
                        if (method_exists(WC()->session, 'save_data')) WC()->session->save_data();
                        do_action('woocommerce_set_cart_cookies', true);
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('[VS08S Booking] Cart add fallback: ' . $e->getMessage());
        }

        $checkout_url = wc_get_checkout_url();
        if (strpos($checkout_url, '?') !== false) $checkout_url .= '&vs08_cart=' . $cart_token;
        else $checkout_url .= '?vs08_cart=' . $cart_token;

        error_log('[VS08S Booking] OK → redirect ' . $checkout_url);

        return [
            'order_id'     => 0,
            'checkout_url' => $checkout_url,
            'total'        => $total,
            'acompte'      => $acompte,
        ];
    }

    private static function build_description($sejour_id, $params, $devis, $m, $titre, $total, $acompte, $acompte_pct) {
        $pension_map = ['ai'=>'All Inclusive','pc'=>'Pension complète','dp'=>'Demi-pension','bb'=>'Petit-déjeuner','lo'=>'Logement seul'];
        $pension = $pension_map[$m['pension'] ?? 'ai'] ?? 'All Inclusive';
        $hotel_nom = $m['hotel_nom'] ?? '';
        $hotel_etoiles = intval($m['hotel_etoiles'] ?? 5);
        $duree = intval($m['duree'] ?? 7);
        $duree_j = intval($m['duree_jours'] ?? ($duree + 1));
        $transfert_map = ['groupes'=>'Transferts groupés','prives'=>'Transferts privés','inclus'=>'Inclus dans l\'hôtel','aucun'=>'Non inclus'];
        $transfert_type = $m['transfert_type'] ?? '';
        if (empty($transfert_type)) $transfert_type = 'groupes';
        $transfert = $transfert_map[$transfert_type] ?? 'Transferts groupés';
        error_log('[VS08S Desc] transfert_type=' . var_export($m['transfert_type'] ?? 'NULL', true) . ' → ' . $transfert);
        $iata_dest = strtoupper($m['iata_dest'] ?? '');
        $aeroport = strtoupper($params['aeroport'] ?? '');
        $date_depart = $params['date_depart'] ?? '';
        $date_retour = $date_depart ? date('d/m/Y', strtotime($date_depart . ' +' . $duree . ' days')) : '';
        $date_fmt = $date_depart ? date('d/m/Y', strtotime($date_depart)) : '';
        $payer_tout = $devis['payer_tout'] ?? false;

        ob_start(); ?>
        <div class="vs08v-woo-recap">
            <h3>📋 Récapitulatif de votre réservation</h3>
            <table>
                <tr><td><strong>Séjour</strong></td><td><?php echo esc_html($titre); ?></td></tr>
                <tr><td><strong>🗓️ Dates</strong></td><td><?php echo esc_html($date_fmt . ' → ' . $date_retour); ?></td></tr>
                <tr><td><strong>🌙 Durée</strong></td><td><?php echo $duree_j; ?> jours / <?php echo $duree; ?> nuits</td></tr>
                <tr><td><strong>✈️ Vols</strong></td><td><?php echo esc_html($aeroport); ?> → <?php echo esc_html($iata_dest); ?></td></tr>
                <?php if (!empty($params['vol_aller_num'])): ?>
                <tr><td><strong>🛫 Aller</strong></td><td><?php echo esc_html($params['vol_aller_num']); ?> (<?php echo esc_html($params['vol_aller_cie'] ?? ''); ?>) · <?php echo esc_html($params['vol_aller_depart'] ?? ''); ?> → <?php echo esc_html($params['vol_aller_arrivee'] ?? ''); ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($params['vol_retour_num'])): ?>
                <tr><td><strong>🛬 Retour</strong></td><td><?php echo esc_html($params['vol_retour_num']); ?> · <?php echo esc_html($params['vol_retour_depart'] ?? ''); ?> → <?php echo esc_html($params['vol_retour_arrivee'] ?? ''); ?></td></tr>
                <?php endif; ?>
                <?php if ($hotel_nom): ?>
                <tr><td><strong>🏨 Hôtel</strong></td><td><?php echo esc_html($hotel_nom); ?><?php if ($hotel_etoiles): ?> <?php echo str_repeat('★', $hotel_etoiles); ?><?php endif; ?></td></tr>
                <?php endif; ?>
                <tr><td><strong>🍽️ Formule</strong></td><td><?php echo esc_html($pension); ?></td></tr>
                <tr><td><strong>🚐 Transferts</strong></td><td><?php echo esc_html($transfert); ?></td></tr>
                <tr><td><strong>👥 Voyageurs</strong></td><td><?php echo intval($params['nb_adultes'] ?? 2); ?> adulte(s)</td></tr>
            </table>
            <h4>💰 Détail du prix</h4>
            <table>
                <tr style="font-weight:bold;border-top:2px solid #333"><td>TOTAL VOYAGE</td><td><?php echo number_format($total, 2, ',', ' '); ?> €</td></tr>
            <?php if (!$payer_tout): ?>
                <tr style="color:#e8724a"><td>Acompte à régler (<?php echo $acompte_pct; ?>%)</td><td><?php echo number_format($acompte, 2, ',', ' '); ?> €</td></tr>
                <tr><td>Solde à régler <?php echo intval($m['delai_solde'] ?? 30); ?> jours avant le départ</td><td><?php echo number_format($total - $acompte, 2, ',', ' '); ?> €</td></tr>
            <?php endif; ?>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }
}
