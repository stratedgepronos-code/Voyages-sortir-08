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
        // PRÉ-RÉSA EMAILS : envoyés en arrière-plan via Action Scheduler
        // (programmé sur woocommerce_thankyou dans vs08-voyages.php)
        // Plus de hook ici — tout passe par vs08v_async_send_emails.
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
     * Wrapper pour woocommerce_thankyou (reçoit seulement $order_id).
     */
    public static function maybe_send_pre_reservation_emails_on_thankyou($order_id) {
        if (!$order_id) return;
        $order = wc_get_order($order_id);
        if (!$order) return;
        self::maybe_send_pre_reservation_emails($order_id, null, $order);
    }

    /**
     * Après commande « en agence » : emails pré-réservation (pas le contrat de vente définitif).
     * Note : les booking_data ne sont PAS sur la commande pendant le checkout
     * (copie reportée sur thankyou/payment_complete). On lit depuis le produit.
     */
    public static function maybe_send_pre_reservation_emails($order_id, $posted_data, $order) {
        if (!$order || !is_a($order, 'WC_Order')) {
            $order = wc_get_order($order_id);
        }
        if (!$order || $order->get_payment_method() !== 'vs08_agence') {
            return;
        }

        // Récupérer les booking_data : d'abord la commande, sinon le produit
        $data = null;
        $data_source = 'order';

        // 1. Essayer depuis la commande (cas normal post-checkout)
        $data = $order->get_meta('_vs08v_booking_data');
        if (empty($data) || !is_array($data)) {
            $data = $order->get_meta('_vs08c_booking_data');
        }

        // 2. Fallback : lire depuis le produit dans les line items
        if (empty($data) || !is_array($data)) {
            $data_source = 'product';
            foreach ($order->get_items() as $item) {
                $pid = $item->get_product_id();
                if (!$pid) continue;
                // Golf
                $d = get_post_meta($pid, '_vs08v_booking_data', true);
                if (!empty($d) && is_array($d)) { $data = $d; break; }
                // Circuit
                $d = get_post_meta($pid, '_vs08c_booking_data', true);
                if (!empty($d) && is_array($d)) { $data = $d; break; }
                // Séjour
                $d = get_post_meta($pid, '_vs08s_booking_data', true);
                if (!empty($d) && is_array($d)) { $data = $d; break; }
            }
        }

        if (empty($data) || !is_array($data)) {
            error_log('[VS08] pre_res_emails order #' . $order_id . ' — pas de booking_data (ni commande ni produit)');
            return;
        }

        $type = $data['type'] ?? '';
        error_log('[VS08] pre_res_emails order #' . $order_id . ' — type=' . $type . ', source=' . $data_source);

        // Circuit
        if ($type === 'circuit' && class_exists('VS08C_Emails')) {
            VS08C_Emails::dispatch_pre_reservation($order->get_id());
            return;
        }

        // Séjour → géré par son propre plugin (sur thankyou)
        if ($type === 'sejour') {
            return;
        }

        // Golf (ou type vide = golf par défaut)
        if (class_exists('VS08V_Emails')) {
            VS08V_Emails::dispatch_pre_reservation($order->get_id());
        }
    }
}
