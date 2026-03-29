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
            $product_id = $product->save();

            // Stocker les données sur le produit (pas sur l'order — pas encore créée)
            update_post_meta($product_id, '_vs08v_booking_data', $booking_data);
            update_post_meta($product_id, '_vs08s_booking_data', $booking_data);
            update_post_meta($product_id, '_vs08v_booking_hash', $hash);
            update_post_meta($product_id, '_vs08v_voyage_id', $sejour_id);
            update_post_meta($product_id, '_vs08v_total_voyage', $total);
            update_post_meta($product_id, '_vs08v_acompte', $acompte);
            update_post_meta($product_id, '_vs08v_payer_tout', $devis['payer_tout']);
        }

        error_log('[VS08S Booking] Produit #' . $product_id . ' créé');

        // Cart token (même mécanisme que golf)
        $cart_token = wp_generate_password(32, false);
        set_transient('vs08_cart_' . $cart_token, [
            'product_id'   => $product_id,
            'payment_mode' => 'card',
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
}
