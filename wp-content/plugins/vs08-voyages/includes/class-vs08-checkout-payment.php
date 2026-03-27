<?php
/**
 * Mode de paiement tunnel VS08 : CB (Paybox, etc.) vs agence — filtrage des gateways au checkout.
 */
if (!defined('ABSPATH')) exit;

class VS08_Checkout_Payment {

    const SESSION_KEY = 'vs08_checkout_payment_mode';

    /**
     * @param mixed $raw Valeur du transient (legacy : ID produit seul ; nouveau : array).
     * @return array{0:int,1:string} product_id, mode card|agency
     */
    public static function parse_cart_transient_payload($raw) {
        $product_id = 0;
        $mode       = 'card';
        if (is_array($raw)) {
            $product_id = (int) ($raw['product_id'] ?? 0);
            $mode       = (($raw['payment_mode'] ?? '') === 'agency') ? 'agency' : 'card';
        } elseif ($raw) {
            $product_id = (int) $raw;
        }
        return [$product_id, $mode];
    }

    public static function register() {
        add_filter('woocommerce_payment_gateways', [__CLASS__, 'alter_payment_gateways']);
        add_filter('woocommerce_available_payment_gateways', [__CLASS__, 'filter_gateways'], 50);
        add_action('woocommerce_checkout_order_processed', [__CLASS__, 'maybe_send_pre_reservation_emails'], 30, 3);
        add_action('woocommerce_checkout_order_processed', [__CLASS__, 'clear_session_payment_mode'], 999, 1);
    }

    /** Évite de filtrer les moyens de paiement sur une prochaine commande non VS08. */
    public static function clear_session_payment_mode($order_id) {
        unset($order_id);
        if (WC()->session) {
            WC()->session->set(self::SESSION_KEY, null);
        }
    }

    /**
     * Retire virement (BACS) et chèque du cœur WooCommerce + enregistre la passerelle agence VS08.
     */
    public static function alter_payment_gateways($methods) {
        $methods = array_values(array_filter((array) $methods, function ($class) {
            return !in_array($class, ['WC_Gateway_BACS', 'WC_Gateway_Cheque'], true);
        }));
        $methods[] = 'VS08V_Gateway_Agence';
        return $methods;
    }

    /**
     * Panier VS08 (golf / circuit / séjour sur produit) : n’afficher que les moyens cohérents avec le choix tunnel.
     */
    public static function filter_gateways($gateways) {
        if (!is_checkout() || empty($gateways) || !function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
            return $gateways;
        }

        if (!WC()->session) {
            return $gateways;
        }

        $mode = WC()->session->get(self::SESSION_KEY);
        if ($mode !== 'agency' && $mode !== 'card') {
            return $gateways;
        }

        $has_vs08 = false;
        foreach (WC()->cart->get_cart() as $item) {
            $pid = isset($item['product_id']) ? (int) $item['product_id'] : 0;
            if (!$pid) {
                continue;
            }
            if (get_post_meta($pid, '_vs08v_booking_data', true) || get_post_meta($pid, '_vs08c_booking_data', true) || get_post_meta($pid, '_vs08s_booking_data', true)) {
                $has_vs08 = true;
                break;
            }
        }

        if (!$has_vs08) {
            return $gateways;
        }

        if ($mode === 'agency') {
            foreach (array_keys($gateways) as $id) {
                if ($id !== 'vs08_agence') {
                    unset($gateways[$id]);
                }
            }
            return $gateways;
        }

        // card : masquer agence + moyens hors ligne pour forcer Paybox CB
        unset($gateways['vs08_agence']);
        foreach (['bacs', 'cheque', 'cod'] as $off) {
            unset($gateways[$off]);
        }

        return $gateways;
    }

    /**
     * Après commande « en agence » : emails pré-réservation (pas le contrat de vente définitif).
     */
    public static function maybe_send_pre_reservation_emails($order_id, $posted_data, $order) {
        if (!$order || !is_a($order, 'WC_Order')) {
            $order = wc_get_order($order_id);
        }
        if (!$order || $order->get_payment_method() !== 'vs08_agence') {
            return;
        }

        if (class_exists('VS08C_Emails')) {
            $c = $order->get_meta('_vs08c_booking_data');
            if (!empty($c) && is_array($c) && (($c['type'] ?? '') === 'circuit')) {
                VS08C_Emails::dispatch_pre_reservation($order->get_id());
                return;
            }
        }

        if (class_exists('VS08V_Emails')) {
            $v = $order->get_meta('_vs08v_booking_data');
            if (empty($v) || !is_array($v)) {
                return;
            }
            $t = $v['type'] ?? '';
            if ($t === 'circuit' || $t === 'sejour') {
                return;
            }
            VS08V_Emails::dispatch_pre_reservation($order->get_id());
        }
    }
}
