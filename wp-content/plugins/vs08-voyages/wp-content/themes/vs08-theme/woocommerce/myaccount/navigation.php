<?php
/**
 * My Account navigation — override VS08 (charte voyageur)
 */
if (!defined('ABSPATH')) exit;

do_action('woocommerce_before_account_navigation');
?>

<nav class="woocommerce-MyAccount-navigation vs08-myaccount-nav" aria-label="<?php esc_attr_e('Pages du compte', 'woocommerce'); ?>">
	<ul>
		<?php foreach (wc_get_account_menu_items() as $endpoint => $label) : ?>
			<li class="<?php echo esc_attr(wc_get_account_menu_item_classes($endpoint)); ?>">
				<a href="<?php echo esc_url(wc_get_account_endpoint_url($endpoint)); ?>" <?php echo wc_is_current_account_menu_item($endpoint) ? ' aria-current="page"' : ''; ?>><?php echo esc_html($label); ?></a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>

<?php do_action('woocommerce_after_account_navigation');
