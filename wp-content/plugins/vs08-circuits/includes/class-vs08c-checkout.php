<?php
/**
 * VS08 Circuits — Checkout: masquer facturation, injecter récap, pré-remplir.
 * Pattern identique à VS08V_Checkout.
 */
if (!defined('ABSPATH')) exit;

class VS08C_Checkout {

    private static $booking_data = null;

    public static function init() {
        add_filter('woocommerce_checkout_get_value', [__CLASS__, 'prefill_billing'], 10, 2);
        add_filter('body_class', [__CLASS__, 'body_class'], 20);
        add_action('woocommerce_checkout_before_customer_details', [__CLASS__, 'maybe_hide_billing_css'], 5);
        add_action('woocommerce_checkout_after_customer_details', [__CLASS__, 'output_billing_hidden'], 95);
        add_action('woocommerce_review_order_before_order_table', [__CLASS__, 'output_recap_card'], 5);
        add_filter('woocommerce_checkout_fields', [__CLASS__, 'maybe_unrequire_billing'], 20);
        // Changer le badge "Séjour Golf" → "Circuit" dans le header checkout
        add_action('woocommerce_checkout_before_order_review', [__CLASS__, 'inject_circuit_badge_override'], 1);
    }

    /* ─── Detect circuit booking in cart ─── */
    private static function get_booking_data() {
        if (self::$booking_data !== null) return self::$booking_data ?: null;
        if (!function_exists('WC') || !WC()->cart) { self::$booking_data = false; return null; }

        foreach (WC()->cart->get_cart() as $item) {
            $id = $item['product_id'] ?? 0;
            if (!$id) continue;
            $data = get_post_meta($id, '_vs08c_booking_data', true);
            if (!empty($data) && is_array($data) && ($data['type'] ?? '') === 'circuit') {
                self::$booking_data = $data;
                return $data;
            }
        }
        self::$booking_data = false;
        return null;
    }

    private static function is_circuit_checkout() {
        return is_array(self::get_booking_data());
    }

    /* ─── Body class ─── */
    public static function body_class($classes) {
        if (is_checkout() && !is_wc_endpoint_url('order-received') && self::is_circuit_checkout()) {
            $classes[] = 'vs08v-checkout-hide-billing'; // Réutilise la même classe CSS que le golf
            $classes[] = 'vs08c-checkout';
        }
        return $classes;
    }

    /* ─── Masquer #customer_details ─── */
    public static function maybe_hide_billing_css() {
        if (!self::is_circuit_checkout()) return;
        echo '<style>.vs08c-checkout #customer_details{display:none!important}</style>';
    }

    /* ─── Champs billing masqués (pour WooCommerce) ─── */
    public static function output_billing_hidden() {
        $data = self::get_booking_data();
        if (!is_array($data) || empty($data['facturation'])) return;
        $f = $data['facturation'];
        $fields = [
            'billing_first_name' => $f['prenom'] ?? '',
            'billing_last_name'  => $f['nom'] ?? '',
            'billing_email'      => $f['email'] ?? '',
            'billing_phone'      => $f['tel'] ?? '',
            'billing_address_1'  => $f['adresse'] ?? '',
            'billing_postcode'   => $f['cp'] ?? '',
            'billing_city'       => $f['ville'] ?? '',
            'billing_country'    => 'FR',
        ];
        echo '<div class="vs08c-billing-hidden" aria-hidden="true">';
        foreach ($fields as $n => $v) {
            echo '<input type="hidden" name="' . esc_attr($n) . '" value="' . esc_attr($v) . '">';
        }
        echo '</div>';
    }

    /* ─── Pré-remplir les champs billing ─── */
    public static function prefill_billing($value, $input) {
        $data = self::get_booking_data();
        if (!is_array($data) || empty($data['facturation'])) return $value;
        $f = $data['facturation'];
        $map = [
            'billing_first_name' => $f['prenom'] ?? '',
            'billing_last_name'  => $f['nom'] ?? '',
            'billing_email'      => $f['email'] ?? '',
            'billing_phone'      => $f['tel'] ?? '',
            'billing_address_1'  => $f['adresse'] ?? '',
            'billing_postcode'   => $f['cp'] ?? '',
            'billing_city'       => $f['ville'] ?? '',
            'billing_country'    => 'FR',
        ];
        return isset($map[$input]) ? $map[$input] : $value;
    }

    /* ─── Enlever le required sur les champs billing ─── */
    public static function maybe_unrequire_billing($fields) {
        if (!self::is_circuit_checkout()) return $fields;
        if (isset($fields['billing'])) {
            foreach ($fields['billing'] as $key => $field) {
                $fields['billing'][$key]['required'] = false;
            }
        }
        return $fields;
    }

    /* ─── Injecter le récap du circuit ─── */
    public static function output_recap_card() {
        $data = self::get_booking_data();
        if (!is_array($data)) return;

        // Chercher le produit WooCommerce pour récupérer la description
        $product_id = null;
        foreach (WC()->cart->get_cart() as $item) {
            $id = $item['product_id'] ?? 0;
            if (!$id) continue;
            if (get_post_meta($id, '_vs08c_booking_data', true)) {
                $product_id = $id;
                break;
            }
        }
        if (!$product_id) return;
        $product = wc_get_product($product_id);
        if (!$product) return;
        $desc = $product->get_description();
        if (strpos($desc, 'vs08c-woo-recap') === false) return;
        ?>
        <div class="vs08v-checkout-recap-wrapper">
            <div class="vs08v-checkout-recap-card">
                <?php echo wp_kses_post($desc); ?>
            </div>
        </div>
        <?php
    }

    /* ─── Changer le badge du header checkout pour les circuits ─── */
    public static function inject_circuit_badge_override() {
        if (!self::is_circuit_checkout()) return;
        echo '<script>document.addEventListener("DOMContentLoaded",function(){var b=document.querySelector(".vs08-order-card-badge");if(b){b.textContent="🗺️ Circuit";b.style.background="rgba(89,183,183,.15)";b.style.color="#3d9a9a";}});</script>';
    }
}

add_action('woocommerce_init', ['VS08C_Checkout', 'init']);
