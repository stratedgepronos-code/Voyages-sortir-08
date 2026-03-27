<?php
if (!defined('ABSPATH')) exit;

class VS08S_Booking {

    public static function register() {
        // Le booking est géré via REST API (vs08s/v1/booking)
    }

    /**
     * Crée une commande WooCommerce pour un séjour.
     */
    public static function create_order($sejour_id, $params) {
        $m = VS08S_Meta::get($sejour_id);
        $titre = get_the_title($sejour_id);

        // Recalculer le devis côté serveur
        $devis = VS08S_Calculator::compute($sejour_id, $params);

        $total = $devis['total'];
        $acompte = $devis['payer_tout'] ? $total : $devis['acompte'];

        // Facturation
        $fact = $params['facturation'] ?? [];
        $voyageurs = $params['voyageurs'] ?? [];

        // Créer le produit WooCommerce temporaire
        $product = new \WC_Product_Simple();
        $product->set_name('Séjour ' . $titre);
        $product->set_regular_price($acompte);
        $product->set_catalog_visibility('hidden');
        $product->set_virtual(true);
        $product->save();

        // Créer la commande
        $order = wc_create_order();
        $order->add_product($product, 1);

        // Billing
        $order->set_billing_first_name(sanitize_text_field($fact['prenom'] ?? ''));
        $order->set_billing_last_name(sanitize_text_field($fact['nom'] ?? ''));
        $order->set_billing_email(sanitize_email($fact['email'] ?? ''));
        $order->set_billing_phone(sanitize_text_field($fact['tel'] ?? ''));
        $order->set_billing_address_1(sanitize_text_field($fact['adresse'] ?? ''));
        $order->set_billing_postcode(sanitize_text_field($fact['cp'] ?? ''));
        $order->set_billing_city(sanitize_text_field($fact['ville'] ?? ''));
        $order->set_billing_country('FR');

        if (is_user_logged_in()) {
            $order->set_customer_id(get_current_user_id());
        }

        $order->set_total($acompte);

        // Sauvegarder les données de réservation
        $booking_data = [
            'type'           => 'sejour',
            'sejour_id'      => $sejour_id,
            'sejour_titre'   => $titre,
            'voyage_titre'   => $titre, // compatibilité espace admin
            'params'         => $params,
            'devis'          => $devis,
            'facturation'    => $fact,
            'voyageurs'      => $voyageurs,
            'total'          => $total,
            'acompte'        => $acompte,
            'payer_tout'     => $devis['payer_tout'],
            'assurance'      => $devis['assurance'],
            'options'        => [],
        ];

        $order->update_meta_data('_vs08s_booking_data', $booking_data);
        $order->update_meta_data('_vs08v_booking_data', $booking_data); // compatibilité espace admin
        $order->set_status('pending');
        $order->save();

        // Nettoyer le produit temporaire
        $product->delete(true);

        return [
            'order_id'    => $order->get_id(),
            'checkout_url' => $order->get_checkout_payment_url(),
            'total'       => $total,
            'acompte'     => $acompte,
        ];
    }
}
