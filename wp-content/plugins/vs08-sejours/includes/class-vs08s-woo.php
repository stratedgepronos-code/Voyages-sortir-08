<?php
if (!defined('ABSPATH')) exit;

class VS08S_Woo {

    public static function register() {
        // Rediriger le thankyou vers l'espace voyageur
        add_action('woocommerce_thankyou', [__CLASS__, 'redirect_thankyou'], 5);
        // Afficher le récap dans la commande admin
        add_action('woocommerce_admin_order_data_after_billing_address', [__CLASS__, 'admin_order_details']);
    }

    public static function redirect_thankyou($order_id) {
        if (!$order_id) return;
        $order = wc_get_order($order_id);
        if (!$order) return;
        $data = $order->get_meta('_vs08s_booking_data');
        if (empty($data) || !is_array($data)) return;
        $url = home_url('/espace-voyageur/');
        echo '<script>window.location.replace("' . esc_js(esc_url($url)) . '");</script>';
    }

    public static function admin_order_details($order) {
        $data = $order->get_meta('_vs08s_booking_data');
        if (empty($data) || !is_array($data)) return;
        $params = $data['params'] ?? [];
        $devis = $data['devis'] ?? [];
        echo '<div style="background:#edf8f8;padding:12px;border-radius:8px;margin-top:12px">';
        echo '<h3 style="margin:0 0 8px">🏖️ Séjour All Inclusive</h3>';
        echo '<p><strong>' . esc_html($data['sejour_titre'] ?? '') . '</strong></p>';
        echo '<p>Départ : ' . esc_html($params['date_depart'] ?? '') . ' — ' . esc_html($params['aeroport'] ?? '') . '</p>';
        echo '<p>Total : <strong>' . number_format(floatval($data['total'] ?? 0), 2, ',', ' ') . ' €</strong></p>';
        echo '</div>';
    }
}
