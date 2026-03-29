<?php
/**
 * VS08 Voyages - Checkout: preremplissage facturation, recap visible, page solde design.
 */
if (!defined('ABSPATH')) exit;

class VS08V_Checkout {

    private static $booking_data = null;

    public static function init() {
        add_filter('woocommerce_checkout_get_value', [__CLASS__, 'prefill_billing'], 10, 2);
        add_filter('body_class', [__CLASS__, 'body_class'], 20);
        add_action('woocommerce_checkout_before_customer_details', [__CLASS__, 'maybe_hide_billing_css'], 5);
        add_action('woocommerce_checkout_after_customer_details', [__CLASS__, 'output_billing_summary'], 95);
        add_action('woocommerce_review_order_before_order_table', [__CLASS__, 'output_vs08_recap_card'], 5);
        add_filter('woocommerce_checkout_fields', [__CLASS__, 'maybe_remove_billing_fields_required'], 20);
        // maybe_order_pay_solde_notice retiré — méthode absente, provoquait un fatal error
        add_filter('woocommerce_locate_template', [__CLASS__, 'locate_form_pay_template'], 20, 3);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_order_pay_styles'], 15);
    }

    /** Surcharge du template WooCommerce form-pay : design VS08. */
    public static function locate_form_pay_template($template, $template_name, $template_path, $default_path = '') {
        if ($template_name !== 'checkout/form-pay.php') {
            return $template;
        }
        $plugin_template = VS08V_PATH . 'templates/woocommerce/checkout/form-pay.php';
        return file_exists($plugin_template) ? $plugin_template : $template;
    }

    /** Charge la feuille de style thème sur la page « Payer la commande ». */
    public static function enqueue_order_pay_styles() {
        if (!is_checkout() || !is_wc_endpoint_url('order-pay')) {
            return;
        }
        $css_file = VS08V_PATH . 'assets/css/order-pay.css';
        $ver = file_exists($css_file) ? (string) filemtime($css_file) : (defined('VS08V_VER') ? VS08V_VER : '2.0.0');
        wp_enqueue_style(
            'vs08-order-pay',
            VS08V_URL . 'assets/css/order-pay.css',
            array(),
            $ver
        );
    }

    public static function body_class($classes) {
        if (is_checkout() && !is_wc_endpoint_url('order-received') && self::cart_is_vs08_booking()) {
            $classes[] = 'vs08v-checkout-hide-billing';
        }
        return $classes;
    }

    private static function get_booking_data() {
        if (self::$booking_data !== null) {
            return self::$booking_data;
        }
        if (!WC()->cart) return null;
        foreach (WC()->cart->get_cart() as $item) {
            $id = $item['product_id'] ?? 0;
            if (!$id) continue;
            $data = get_post_meta($id, '_vs08v_booking_data', true);
            if (!empty($data) && !empty($data['facturation'])) {
                self::$booking_data = $data;
                return self::$booking_data;
            }
        }
        self::$booking_data = false;
        return null;
    }

    private static function cart_is_vs08_booking() {
        $data = self::get_booking_data();
        return is_array($data);
    }

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

    public static function maybe_remove_billing_fields_required($fields) {
        if (!self::cart_is_vs08_booking()) return $fields;
        if (isset($fields['billing'])) {
            foreach ($fields['billing'] as $key => $field) {
                $fields['billing'][$key]['required'] = false;
            }
        }
        return $fields;
    }

    public static function maybe_hide_billing_css() {
        if (!self::cart_is_vs08_booking()) return;
        echo '<style>.vs08v-checkout-hide-billing #customer_details{display:none!important}</style>';
    }

    public static function output_billing_summary() {
        $data = self::get_booking_data();
        if (!is_array($data) || empty($data['facturation'])) return;
        $f = $data['facturation'];
        $hidden_fields = [
            'billing_first_name' => $f['prenom'] ?? '',
            'billing_last_name'  => $f['nom'] ?? '',
            'billing_email'      => $f['email'] ?? '',
            'billing_phone'      => $f['tel'] ?? '',
            'billing_address_1'  => $f['adresse'] ?? '',
            'billing_postcode'   => $f['cp'] ?? '',
            'billing_city'       => $f['ville'] ?? '',
            'billing_country'    => 'FR',
        ];
        echo '<div class="vs08v-billing-hidden" aria-hidden="true">';
        foreach ($hidden_fields as $n => $v) {
            echo '<input type="hidden" name="' . esc_attr($n) . '" value="' . esc_attr($v) . '">';
        }
        echo '</div>';
    }

    public static function output_vs08_recap_card() {
        $data = self::get_booking_data();
        if (!is_array($data)) return;
        $product_id = null;
        foreach (WC()->cart->get_cart() as $item) {
            $id = $item['product_id'] ?? 0;
            if (!$id) continue;
            if (get_post_meta($id, '_vs08v_booking_data', true)) {
                $product_id = $id;
                break;
            }
        }
        if (!$product_id) return;

        // Pour les séjours : lire directement depuis booking_data (pas le cache WC)
        $booking = get_post_meta($product_id, '_vs08v_booking_data', true);
        $type = is_array($booking) ? ($booking['type'] ?? 'golf') : 'golf';

        if ($type === 'sejour' && is_array($booking)) {
            self::render_sejour_recap($booking);
            return;
        }

        // Golf/Circuit : utiliser la description produit
        $desc = get_post_field('post_content', $product_id);
        if (empty($desc) || strpos($desc, 'vs08v-woo-recap') === false) return;
        ?>
        <div class="vs08v-checkout-recap-wrapper">
            <div class="vs08v-checkout-recap-card">
                <?php echo wp_kses_post($desc); ?>
            </div>
        </div>
        <?php
    }

    private static function render_sejour_recap($booking) {
        $params = $booking['params'] ?? [];
        $devis  = $booking['devis'] ?? [];
        $sejour_id = $booking['sejour_id'] ?? 0;
        $m = class_exists('VS08S_Meta') ? VS08S_Meta::get($sejour_id) : [];

        $pension_map = ['ai'=>'All Inclusive','pc'=>'Pension complète','dp'=>'Demi-pension','bb'=>'Petit-déjeuner','lo'=>'Logement seul'];
        $pension = $pension_map[$m['pension'] ?? 'ai'] ?? 'All Inclusive';
        $hotel_nom = $m['hotel_nom'] ?? '';
        $hotel_etoiles = intval($m['hotel_etoiles'] ?? 0);
        $duree = intval($m['duree'] ?? 7);
        $duree_j = intval($m['duree_jours'] ?? ($duree + 1));
        $transfert_map = ['groupes'=>'Transferts groupés','prives'=>'Transferts privés','inclus'=>'Inclus dans l\'hôtel','aucun'=>'Non inclus'];
        $tt = $m['transfert_type'] ?? '';
        if (empty($tt)) $tt = 'groupes';
        $transfert = $transfert_map[$tt] ?? 'Transferts groupés';
        $iata_dest = strtoupper($m['iata_dest'] ?? '');
        $aeroport = strtoupper($params['aeroport'] ?? '');
        $date_depart = $params['date_depart'] ?? '';
        $date_retour = $date_depart ? date('d/m/Y', strtotime($date_depart . ' +' . $duree . ' days')) : '';
        $date_fmt = $date_depart ? date('d/m/Y', strtotime($date_depart)) : '';
        $total = floatval($booking['total'] ?? 0);
        $acompte = floatval($booking['acompte'] ?? 0);
        $acompte_pct = floatval($m['acompte_pct'] ?? 30);
        $payer_tout = $booking['payer_tout'] ?? false;
        ?>
        <div class="vs08v-checkout-recap-wrapper">
            <div class="vs08v-checkout-recap-card">
                <div class="vs08v-woo-recap">
                    <h3><?php echo esc_html($booking['sejour_titre'] ?? ''); ?></h3>
                    <table>
                        <tr><td><strong>🗓️ Dates</strong></td><td><?php echo esc_html($date_fmt . ' → ' . $date_retour); ?></td></tr>
                        <tr><td><strong>🌙 Durée</strong></td><td><?php echo $duree_j; ?> jours / <?php echo $duree; ?> nuits</td></tr>
                        <tr><td><strong>✈️ Vols</strong></td><td><?php echo esc_html($aeroport . ' → ' . $iata_dest); ?></td></tr>
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
                        <tr><td>Solde à régler <?php echo intval($m['delai_solde'] ?? 30); ?>j avant le départ</td><td><?php echo number_format($total - $acompte, 2, ',', ' '); ?> €</td></tr>
                    <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
}

add_action('woocommerce_init', ['VS08V_Checkout', 'init']);
