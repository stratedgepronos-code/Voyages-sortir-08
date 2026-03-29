<?php
/**
 * Checkout Form — VS08 Premium Golf & Travel — 2026
 * Design : header dark, trust badges, barre réassurance
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package VS08
 */
if ( ! defined( 'ABSPATH' ) ) exit;

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}
?>

<div class="vs08-checkout-page">

	<!-- ═══════════════════════════════════
	     STEPPER 3 étapes
	═══════════════════════════════════ -->
	<div class="vs08-checkout-header">
		<div class="vs08-checkout-steps">
			<div class="vs08-step completed">
				<span class="vs08-step-num">✓</span>
				<span class="vs08-step-label">Séjour</span>
			</div>
			<div class="vs08-step-line completed"></div>
			<div class="vs08-step completed">
				<span class="vs08-step-num">✓</span>
				<span class="vs08-step-label">Coordonnées</span>
			</div>
			<div class="vs08-step-line active"></div>
			<div class="vs08-step active">
				<span class="vs08-step-num">3</span>
				<span class="vs08-step-label">Paiement</span>
			</div>
		</div>
	</div>

	<!-- ═══════════════════════════════════
	     FORMULAIRE CHECKOUT
	═══════════════════════════════════ -->
	<form name="checkout" method="post"
	      class="checkout woocommerce-checkout vs08-checkout-form"
	      action="<?php echo esc_url( wc_get_checkout_url() ); ?>"
	      enctype="multipart/form-data"
	      aria-label="<?php echo esc_attr__( 'Paiement sécurisé', 'woocommerce' ); ?>">

		<?php if ( $checkout->get_checkout_fields() ) : ?>
			<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
			<div class="col2-set" id="customer_details">
				<div class="col-1"><?php do_action( 'woocommerce_checkout_billing' ); ?></div>
				<div class="col-2"><?php do_action( 'woocommerce_checkout_shipping' ); ?></div>
			</div>
			<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
		<?php endif; ?>

		<!-- ═══════════════════════════════════
		     CARTE PRINCIPALE
		═══════════════════════════════════ -->
		<div class="vs08-checkout-main">
			<div class="vs08-checkout-order-card">

				<!-- ── Header dark premium ── -->
				<div class="vs08-order-card-header">
					<h2 class="vs08-order-card-title">Votre <em>réservation</em></h2>
					<?php
					$vs08_badge = '⛳ Séjour Golf';
					if (function_exists('WC') && WC()->cart) {
						foreach (WC()->cart->get_cart() as $_item) {
							$_pid = $_item['product_id'] ?? 0;
							if (!$_pid) continue;
							$_sd = get_post_meta($_pid, '_vs08s_booking_data', true);
							if (is_array($_sd) && (($_sd['type'] ?? '') === 'sejour')) {
								$vs08_badge = '🏖️ Séjour';
								break;
							}
							$_cd = get_post_meta($_pid, '_vs08c_booking_data', true);
							if (is_array($_cd) && (($_cd['type'] ?? '') === 'circuit')) {
								$vs08_badge = '🗺️ Circuit';
								break;
							}
							$_bd = get_post_meta($_pid, '_vs08v_booking_data', true);
							if (is_array($_bd)) {
								$_type = $_bd['type'] ?? 'golf';
								if ($_type === 'sejour') $vs08_badge = '🏖️ Séjour';
								elseif ($_type === 'circuit') $vs08_badge = '🗺️ Circuit';
								break;
							}
						}
					}
					?>
					<span class="vs08-order-card-badge"><?php echo esc_html($vs08_badge); ?></span>
				</div>

				<!-- ── Hooks WooCommerce (recap séjour injecté ici par le plugin) ── -->
				<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
				<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
				<?php do_action( 'vs08_checkout_recap' ); ?>

				<!-- ── Récap commande + Paiement (WooCommerce) ── -->
				<div id="order_review" class="woocommerce-checkout-review-order vs08-order-review">
					<?php do_action( 'woocommerce_checkout_order_review' ); ?>
				</div>

				<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

				<!-- ── Trust badges DANS la carte ── -->
				<div class="vs08-trust-badges">
					<div class="vs08-trust-item">
						<span class="vs08-trust-icon">🔒</span>
						<span>Paiement<br>sécurisé</span>
					</div>
					<div class="vs08-trust-item">
						<span class="vs08-trust-icon">✈️</span>
						<span>Vols &amp;<br>transferts inclus</span>
					</div>
					<div class="vs08-trust-item">
						<span class="vs08-trust-icon">🛡️</span>
						<span>Assurance<br>annulation</span>
					</div>
				</div>

				<!-- ── Barre réassurance (fond dark, bas de carte) ── -->
				<div class="vs08-reassurance-bar">
					<span>⭐ 4.9/5 avis clients</span>
					<span class="vs08-dot"></span>
					<span>📞 03 26 65 28 63</span>
					<span class="vs08-dot"></span>
					<span>🏢 Agence immatriculée</span>
				</div>

			</div><!-- .vs08-checkout-order-card -->
		</div><!-- .vs08-checkout-main -->

	</form>

</div><!-- .vs08-checkout-page -->

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
