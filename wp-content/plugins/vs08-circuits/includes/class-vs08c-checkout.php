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

        $params  = $data['params'] ?? [];
        $devis   = $data['devis'] ?? [];
        $m       = VS08C_Meta::get($data['circuit_id'] ?? 0);
        $flag    = VS08C_Meta::resolve_flag($m);
        $duree_n = intval($m['duree'] ?? 7);
        $duree_j = intval($m['duree_jours'] ?? ($duree_n + 1));
        $total   = floatval($data['total'] ?? 0);
        $acompte = floatval($data['acompte'] ?? 0);
        $payer_tout  = $data['payer_tout'] ?? false;
        $acompte_pct = floatval($data['acompte_pct'] ?? 30);

        $pension_labels   = ['bb'=>'Petit-déjeuner','dp'=>'Demi-pension','pc'=>'Pension complète','ai'=>'Tout inclus','mixed'=>'Selon programme'];
        $transport_labels = ['bus'=>'Bus climatisé','4x4'=>'4x4','voiture'=>'Voiture de location','train'=>'Train','mixed'=>'Transport mixte'];
        $etoiles_map      = ['2'=>'★★','3'=>'★★★','4'=>'★★★★','5'=>'★★★★★','riad'=>'Riad','camp'=>'Bivouac'];
        ?>
        <div class="vs08v-checkout-recap-wrapper">
            <div class="vs08v-checkout-recap-card">
                <div class="vs08c-woo-recap">
                    <h3>📋 Récapitulatif de votre réservation</h3>
                    <table>
                        <tr><td><strong>🗺️ Circuit</strong></td><td><?php echo esc_html($flag . ' ' . ($data['circuit_titre'] ?? '')); ?></td></tr>
                        <tr><td><strong>📍 Destination</strong></td><td><?php echo esc_html($m['destination'] ?? ''); ?></td></tr>
                        <tr><td><strong>📅 Départ</strong></td><td><?php echo esc_html(!empty($params['date_depart']) ? date('d/m/Y', strtotime($params['date_depart'])) : '—'); ?></td></tr>
                        <tr><td><strong>✈️ Aéroport</strong></td><td><?php echo esc_html(strtoupper($params['aeroport'] ?? '')); ?></td></tr>
                        <?php if (!empty($params['vol_aller_num'])): ?>
                        <tr><td><strong>✈️ Vol aller</strong></td><td><?php echo esc_html($params['vol_aller_num']); ?><?php if (!empty($params['vol_aller_cie'])): ?> (<?php echo esc_html($params['vol_aller_cie']); ?>)<?php endif; ?> — <?php echo esc_html($params['vol_aller_depart'] ?? ''); ?> → <?php echo esc_html($params['vol_aller_arrivee'] ?? ''); ?></td></tr>
                        <?php endif; ?>
                        <?php if (!empty($params['vol_retour_num'])): ?>
                        <tr><td><strong>✈️ Vol retour</strong></td><td><?php echo esc_html($params['vol_retour_num']); ?> — <?php echo esc_html($params['vol_retour_depart'] ?? ''); ?> → <?php echo esc_html($params['vol_retour_arrivee'] ?? ''); ?></td></tr>
                        <?php endif; ?>
                        <tr><td><strong>📅 Durée</strong></td><td><?php echo $duree_j; ?> jours / <?php echo $duree_n; ?> nuits</td></tr>
                        <?php $pension_lbl = $pension_labels[$m['pension'] ?? ''] ?? ''; if ($pension_lbl): ?>
                        <tr><td><strong>🍽️ Formule</strong></td><td><?php echo esc_html($pension_lbl); ?></td></tr>
                        <?php endif; ?>
                        <?php $transport_lbl = $transport_labels[$m['transport'] ?? ''] ?? ''; if ($transport_lbl): ?>
                        <tr><td><strong>🚌 Transport</strong></td><td><?php echo esc_html($transport_lbl); ?></td></tr>
                        <?php endif; ?>
                        <tr><td><strong>👥 Voyageurs</strong></td><td><?php echo intval($devis['nb_total'] ?? 0); ?> personne(s)</td></tr>
                        <tr><td><strong>🛏️ Chambres</strong></td><td><?php echo intval($params['nb_chambres'] ?? 1); ?> chambre(s)</td></tr>
                    </table>

                    <?php $hotels = $m['hotels'] ?? []; if (!empty($hotels)): ?>
                    <h4>🏨 Hébergements</h4>
                    <table>
                        <?php foreach ($hotels as $hotel):
                            if (empty($hotel['nom'])) continue;
                            $stars = $etoiles_map[$hotel['etoiles'] ?? '4'] ?? '';
                        ?>
                        <tr><td><strong><?php echo esc_html($hotel['nom']); ?></strong> <?php echo $stars; ?></td><td><?php echo esc_html($hotel['ville'] ?? ''); ?> — <?php echo intval($hotel['nuits'] ?? 1); ?> nuit(s)</td></tr>
                        <?php endforeach; ?>
                    </table>
                    <?php endif; ?>

                    <?php if (!empty($data['voyageurs'])): ?>
                    <h4>👥 Voyageurs</h4>
                    <table>
                        <tr style="font-weight:bold;background:#f8f8f8"><td>#</td><td>Nom</td><td>DDN</td><td>Ch.</td></tr>
                        <?php foreach ($data['voyageurs'] as $vi => $v): ?>
                        <tr>
                            <td><?php echo $vi + 1; ?></td>
                            <td><?php echo esc_html(($v['prenom'] ?? '') . ' ' . strtoupper($v['nom'] ?? '')); ?></td>
                            <td><?php echo esc_html(!empty($v['ddn']) ? date('d/m/Y', strtotime($v['ddn'])) : '—'); ?></td>
                            <td>Ch.<?php echo intval($v['chambre'] ?? 1); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <?php endif; ?>

                    <h4>💰 Détail du prix</h4>
                    <table>
                        <?php if (!empty($devis['lines'])): foreach ($devis['lines'] as $line): ?>
                        <tr><td><?php echo esc_html($line['label']); ?></td><td style="text-align:right;white-space:nowrap"><?php echo number_format($line['montant'], 2, ',', ' '); ?> €</td></tr>
                        <?php endforeach; endif; ?>
                        <?php if (!empty($data['options'])): foreach ($data['options'] as $opt): ?>
                        <tr><td>Option : <?php echo esc_html($opt['label']); ?> (x<?php echo $opt['qty']; ?>)</td><td style="text-align:right;white-space:nowrap"><?php echo number_format($opt['prix'], 2, ',', ' '); ?> €</td></tr>
                        <?php endforeach; endif; ?>
                        <?php if (($data['assurance'] ?? 0) > 0): ?>
                        <tr><td>🛡️ Assurance annulation</td><td style="text-align:right;white-space:nowrap"><?php echo number_format($data['assurance'], 2, ',', ' '); ?> €</td></tr>
                        <?php endif; ?>
                        <tr style="font-weight:bold;border-top:2px solid #333"><td>TOTAL CIRCUIT</td><td style="text-align:right;white-space:nowrap;font-size:16px"><?php echo number_format($total, 2, ',', ' '); ?> €</td></tr>
                        <tr><td colspan="2" style="text-align:right;font-size:12px;color:#888">soit <?php echo number_format($total / max(1, intval($devis['nb_total'] ?? 1)), 2, ',', ' '); ?> €/pers.</td></tr>
                        <?php if (!$payer_tout): ?>
                        <tr style="color:#e8724a"><td>Acompte à régler (<?php echo $acompte_pct; ?>%)</td><td style="text-align:right;white-space:nowrap;font-weight:bold"><?php echo number_format($acompte, 2, ',', ' '); ?> €</td></tr>
                        <tr><td>Solde à régler ultérieurement</td><td style="text-align:right;white-space:nowrap"><?php echo number_format($total - $acompte, 2, ',', ' '); ?> €</td></tr>
                        <?php endif; ?>
                    </table>

                    <?php if (!empty($data['facturation'])): $f = $data['facturation']; ?>
                    <h4>🧾 Facturation</h4>
                    <table>
                        <tr><td><strong>Client</strong></td><td><?php echo esc_html(($f['prenom'] ?? '') . ' ' . strtoupper($f['nom'] ?? '')); ?></td></tr>
                        <tr><td><strong>Email</strong></td><td><?php echo esc_html($f['email'] ?? ''); ?></td></tr>
                        <tr><td><strong>Tél.</strong></td><td><?php echo esc_html($f['tel'] ?? ''); ?></td></tr>
                        <?php if (!empty($f['adresse'])): ?>
                        <tr><td><strong>Adresse</strong></td><td><?php echo esc_html($f['adresse'] . ', ' . ($f['cp'] ?? '') . ' ' . ($f['ville'] ?? '')); ?></td></tr>
                        <?php endif; ?>
                    </table>
                    <?php endif; ?>
                </div>
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
