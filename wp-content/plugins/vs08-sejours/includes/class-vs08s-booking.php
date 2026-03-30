<?php
if (!defined('ABSPATH')) exit;

class VS08S_Booking {

    public static function register() {}

    /**
     * Crée un produit WooCommerce ULTRA-LÉGER + cart token.
     * ZÉRO booking_data sur le produit → HPOS ne sync rien de lourd.
     * Toutes les données sont dans un transient récupéré au thankyou.
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
        $payment_mode = ($params['vs08_payment_mode'] ?? 'card') === 'agency' ? 'agency' : 'card';

        $acompte_pct = floatval($m['acompte_pct'] ?? 30);
        $product_name = 'Réservation — ' . $titre . ' — ' . date('d/m/Y', strtotime($params['date_depart'] ?? 'now'));

        // ── Données complètes → transient (PAS sur le produit) ──
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

        $booking_token = wp_generate_password(24, false);
        set_transient('vs08s_booking_full_' . $booking_token, $booking_data, 2 * DAY_IN_SECONDS);

        error_log('[VS08S Booking] Token=' . $booking_token . ' total=' . $total . ' acompte=' . $acompte);

        // ── Produit WooCommerce MINIMAL ──
        $product = new \WC_Product_Simple();
        $product->set_name($product_name);
        $product->set_price($acompte);
        $product->set_regular_price($acompte);
        $product->set_status('publish');
        $product->set_virtual(true);
        $product->set_sold_individually(true);
        $product->set_catalog_visibility('hidden');
        $product_id = $product->save();

        // Seules meta sur le produit : token + type + sejour_id (ultra-léger)
        update_post_meta($product_id, '_vs08s_booking_token', $booking_token);
        update_post_meta($product_id, '_vs08s_sejour_id', $sejour_id);
        // Marqueur minimal pour que les hooks golf sachent que c'est un séjour
        // + params suffisants pour le recap checkout
        update_post_meta($product_id, '_vs08v_booking_data', [
            'type' => 'sejour', 'sejour_id' => $sejour_id, 'voyage_id' => $sejour_id,
            'voyage_titre' => $titre, 'total' => $total, 'acompte' => $acompte,
            'payer_tout' => $devis['payer_tout'] ?? false,
            'params' => [
                'date_depart' => $params['date_depart'] ?? '',
                'aeroport' => strtoupper($params['aeroport'] ?? ''),
                'nb_adultes' => intval($params['nb_adultes'] ?? 2),
                'nb_chambres' => intval($params['nb_chambres'] ?? 1),
                'vol_aller_num' => $params['vol_aller_num'] ?? '',
                'vol_aller_cie' => $params['vol_aller_cie'] ?? '',
                'vol_aller_depart' => $params['vol_aller_depart'] ?? '',
                'vol_aller_arrivee' => $params['vol_aller_arrivee'] ?? '',
                'vol_retour_num' => $params['vol_retour_num'] ?? '',
                'vol_retour_depart' => $params['vol_retour_depart'] ?? '',
                'vol_retour_arrivee' => $params['vol_retour_arrivee'] ?? '',
            ],
            'devis' => ['nb_total' => intval($params['nb_adultes'] ?? 2)],
        ]);

        // ── Cart token ──
        $cart_token = wp_generate_password(32, false);
        set_transient('vs08_cart_' . $cart_token, [
            'product_id'   => $product_id,
            'payment_mode' => $payment_mode,
        ], 900);

        // Ajouter au panier
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
            error_log('[VS08S Booking] Cart: ' . $e->getMessage());
        }

        $checkout_url = wc_get_checkout_url();
        $sep = (strpos($checkout_url, '?') !== false) ? '&' : '?';
        $checkout_url .= $sep . 'vs08_cart=' . $cart_token;

        error_log('[VS08S Booking] Produit #' . $product_id . ' → ' . $checkout_url);

        return [
            'order_id'     => 0,
            'checkout_url' => $checkout_url,
            'total'        => $total,
            'acompte'      => $acompte,
        ];
    }
}
