<?php
/**
 * Template VS08 — Paiement commande / solde (surcharge WooCommerce).
 * Charte graphique Voyages Sortir 08 : teal, Playfair Display, Outfit, coral.
 *
 * @see woocommerce/templates/checkout/form-pay.php
 */
defined('ABSPATH') || exit;

$totals = $order->get_order_item_totals();

$is_solde = (bool) $order->get_meta('_vs08v_order_solde_parent');
$parent_id = $is_solde ? (int) $order->get_meta('_vs08v_order_solde_parent') : 0;
$parent = $parent_id ? wc_get_order($parent_id) : null;
$data = null;
if ($parent) {
    $data = $parent->get_meta('_vs08v_booking_data');
    if (empty($data)) {
        foreach ($parent->get_items() as $item) {
            $d = $item->get_meta('_vs08v_booking_data');
            if (!empty($d) && is_array($d)) { $data = $d; break; }
        }
    }
}

$titre = !empty($data['voyage_titre']) ? $data['voyage_titre'] : '';
if (!$titre) {
    $items = $order->get_items();
    $titre = $items ? current($items)->get_name() : __('Votre voyage', 'woocommerce');
}
$amount = $order->get_total();
$fact = $data['facturation'] ?? [];
$prenom = !empty($fact['prenom']) ? $fact['prenom'] : $order->get_billing_first_name();
$nom = !empty($fact['nom']) ? $fact['nom'] : $order->get_billing_last_name();
$client_label = trim($prenom . ' ' . $nom);
$heading = __('Paiement de votre séjour', 'woocommerce');
if ($is_solde) {
    // Détecter paiement partiel vs solde complet
    $is_partial = false;
    foreach ($order->get_items() as $_item) {
        if (strpos($_item->get_name(), 'Acompte solde') !== false) {
            $is_partial = true;
            break;
        }
    }
    $heading = $is_partial ? __('Paiement partiel', 'woocommerce') : __('Paiement du solde', 'woocommerce');
}
?>
<div class="vs08-pay-page">
    <div class="vs08-solde-page-hero">
        <h1><?php echo esc_html($heading); ?></h1>
        <p class="vs08-solde-page-subtitle"><?php echo esc_html($titre); ?></p>
        <?php if ($prenom) : ?>
        <p class="vs08-solde-page-hello"><?php echo esc_html(sprintf(__('Bonjour %s, finalisez votre paiement ci-dessous.', 'woocommerce'), $prenom)); ?></p>
        <?php endif; ?>
    </div>

    <div class="vs08-solde-page-amount-card">
        <div class="vs08-solde-page-amount-label"><?php echo $is_solde && $is_partial ? esc_html__('Montant partiel à régler', 'woocommerce') : esc_html__('Montant à régler', 'woocommerce'); ?></div>
        <div class="vs08-solde-page-amount-value"><?php echo number_format((float) $amount, 2, ',', ' '); ?>&nbsp;€</div>
        <?php if ($parent_id || $client_label) : ?>
        <div class="vs08-solde-page-ref">
            <?php
            if ($parent_id) {
                echo esc_html(sprintf(__('Dossier VS08-%d', 'woocommerce'), $parent_id));
                if ($client_label) echo ' — ';
            }
            if ($client_label) echo esc_html($client_label);
            ?>
        </div>
        <?php endif; ?>
    </div>

    <form id="order_review" method="post">
        <table class="shop_table">
            <thead>
                <tr>
                    <th class="product-name"><?php esc_html_e('Produit', 'woocommerce'); ?></th>
                    <th class="product-quantity"><?php esc_html_e('Qté', 'woocommerce'); ?></th>
                    <th class="product-total"><?php esc_html_e('Totaux', 'woocommerce'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($order->get_items()) > 0) : ?>
                    <?php foreach ($order->get_items() as $item_id => $item) : ?>
                        <?php
                        if (!apply_filters('woocommerce_order_item_visible', true, $item)) {
                            continue;
                        }
                        ?>
                        <tr class="<?php echo esc_attr(apply_filters('woocommerce_order_item_class', 'order_item', $item, $order)); ?>">
                            <td class="product-name">
                                <?php
                                echo wp_kses_post(apply_filters('woocommerce_order_item_name', $item->get_name(), $item, false));
                                do_action('woocommerce_order_item_meta_start', $item_id, $item, $order, false);
                                wc_display_item_meta($item);
                                do_action('woocommerce_order_item_meta_end', $item_id, $item, $order, false);
                                ?>
                            </td>
                            <td class="product-quantity"><?php echo apply_filters('woocommerce_order_item_quantity_html', ' <strong class="product-quantity">' . sprintf('&times;&nbsp;%s', esc_html($item->get_quantity())) . '</strong>', $item); ?></td>
                            <td class="product-subtotal"><?php echo $order->get_formatted_line_subtotal($item); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <?php if ($totals) : ?>
                    <?php foreach ($totals as $total) : ?>
                        <tr>
                            <th scope="row" colspan="2"><?php echo $total['label']; ?></th>
                            <td class="product-total"><?php echo $total['value']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tfoot>
        </table>

        <?php do_action('woocommerce_pay_order_before_payment'); ?>

        <div id="payment">
            <?php if ($order->needs_payment()) : ?>
                <ul class="wc_payment_methods payment_methods methods">
                    <?php
                    if (!empty($available_gateways)) {
                        foreach ($available_gateways as $gateway) {
                            wc_get_template('checkout/payment-method.php', array('gateway' => $gateway));
                        }
                    } else {
                        echo '<li>';
                        wc_print_notice(apply_filters('woocommerce_no_available_payment_methods_message', esc_html__('Aucun moyen de paiement disponible pour votre région.', 'woocommerce')), 'notice');
                        echo '</li>';
                    }
                    ?>
                </ul>
            <?php endif; ?>
            <div class="form-row">
                <input type="hidden" name="woocommerce_pay" value="1" />
                <?php wc_get_template('checkout/terms.php'); ?>
                <?php do_action('woocommerce_pay_order_before_submit'); ?>
                <?php echo apply_filters('woocommerce_pay_order_button_html', '<button type="submit" class="button alt vs08-pay-btn" id="place_order" value="' . esc_attr($order_button_text) . '" data-value="' . esc_attr($order_button_text) . '">' . esc_html($order_button_text) . '</button>'); ?>
                <?php do_action('woocommerce_pay_order_after_submit'); ?>
                <?php wp_nonce_field('woocommerce-pay', 'woocommerce-pay-nonce'); ?>
            </div>
        </div>
    </form>
</div>
