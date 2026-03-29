<?php
/**
 * Passerelle WooCommerce « Paiement en agence » — pré-réservation sans encaissement CB en ligne.
 * Les libellés sont éditables dans WooCommerce → Réglages → Paiements (champs du gateway).
 */
if (!defined('ABSPATH')) exit;

class VS08V_Gateway_Agence extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'vs08_agence';
        $this->has_fields         = false;
        $this->method_title       = __('Paiement en agence (VS08)', 'vs08-voyages');
        $this->method_description = __('Proposé uniquement quand le client choisit « Régler en agence » dans le tunnel VS08. Commande en attente (pré-réservation).', 'vs08-voyages');
        $this->supports           = ['products'];

        $this->init_form_fields();
        $this->init_settings();

        $this->title              = $this->get_option('title');
        $this->description        = $this->get_option('description');
        $this->enabled            = $this->get_option('enabled');
        $this->order_button_text  = $this->get_option('order_button_text');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'   => __('Activer / Désactiver', 'vs08-voyages'),
                'type'    => 'checkbox',
                'label'   => __('Activer « Paiement en agence VS08 »', 'vs08-voyages'),
                'default' => 'yes',
            ],
            'title' => [
                'title'       => __('Titre affiché au client', 'vs08-voyages'),
                'type'        => 'text',
                'description' => __('Ex. : Régler en agence (pré-réservation)', 'vs08-voyages'),
                'default'     => __('Régler en agence (pré-réservation)', 'vs08-voyages'),
                'desc_tip'    => true,
            ],
            'description' => [
                'title'       => __('Description sous le titre', 'vs08-voyages'),
                'type'        => 'textarea',
                'description' => __('Expliquez que le prix n’est pas définitivement bloqué tant que le règlement n’est pas effectué.', 'vs08-voyages'),
                'default'     => __('Vous finaliserez le règlement en agence. Ce document ne constitue pas un contrat de vente définitif : le tarif peut encore évoluer tant que le paiement n’est pas encaissé.', 'vs08-voyages'),
                'css'         => 'width: 100%; min-height: 100px;',
            ],
            'order_button_text' => [
                'title'       => __('Texte du bouton de commande', 'vs08-voyages'),
                'type'        => 'text',
                'default'     => __('Valider ma pré-réservation', 'vs08-voyages'),
            ],
        ];
    }

    public function is_available() {
        if ('yes' !== $this->enabled) {
            return false;
        }
        return parent::is_available();
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return ['result' => 'failure'];
        }

        if (function_exists('wc_reduce_stock_levels')) {
            wc_reduce_stock_levels($order_id);
        }

        if (WC()->cart) {
            WC()->cart->empty_cart();
        }

        $order->update_meta_data('_vs08v_reglement_agence_confirmed', '1');
        $order->update_status(
            'on-hold',
            __('Pré-réservation — règlement prévu en agence (pas d’encaissement CB en ligne à ce stade).', 'vs08-voyages')
        );
        // PAS de $order->save() — update_status fait déjà un save interne

        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url($order),
        ];
    }
}
