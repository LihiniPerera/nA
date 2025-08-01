<?php
/**
 * Cart totals
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-totals.php.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 2.3.6
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="cart_totals <?php echo ( WC()->customer->has_calculated_shipping() ) ? 'calculated_shipping' : ''; ?>">

	<?php do_action( 'woocommerce_before_cart_totals' ); ?>

	<div class="cart-summary-card">
		
		<!-- Order Summary Header -->
		<div class="cart-summary-header">
			<h2 class="cart-summary-title"><?php esc_html_e( 'Order Summary', 'woocommerce' ); ?></h2>
		</div>
		
		<!-- Cart Totals Table -->
		<div class="cart-summary-content">
			<table cellspacing="0" class="shop_table shop_table_responsive cart-totals-table">

				<!-- Subtotal -->
				<tr class="cart-subtotal">
					<th class="cart-total-label"><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
					<td class="cart-total-value" data-title="<?php esc_attr_e( 'Subtotal', 'woocommerce' ); ?>">
						<?php wc_cart_totals_subtotal_html(); ?>
					</td>
				</tr>

				<!-- Coupons -->
				<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
					<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
						<th class="cart-total-label cart-coupon-label">
							<span class="cart-coupon-code"><?php wc_cart_totals_coupon_label( $coupon ); ?></span>
						</th>
						<td class="cart-total-value cart-coupon-value" data-title="<?php echo esc_attr( wc_cart_totals_coupon_label( $coupon, false ) ); ?>">
							<?php wc_cart_totals_coupon_html( $coupon ); ?>
						</td>
					</tr>
				<?php endforeach; ?>

				<!-- Shipping -->
				<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>

					<?php do_action( 'woocommerce_cart_totals_before_shipping' ); ?>

					<?php wc_cart_totals_shipping_html(); ?>

					<?php do_action( 'woocommerce_cart_totals_after_shipping' ); ?>

				<?php elseif ( WC()->cart->needs_shipping() && 'yes' === get_option( 'woocommerce_enable_shipping_calc' ) ) : ?>

					<tr class="shipping">
						<th class="cart-total-label"><?php esc_html_e( 'Shipping', 'woocommerce' ); ?></th>
						<td class="cart-total-value" data-title="<?php esc_attr_e( 'Shipping', 'woocommerce' ); ?>">
							<?php woocommerce_shipping_calculator(); ?>
						</td>
					</tr>

				<?php endif; ?>

				<!-- Fees -->
				<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
					<tr class="fee">
						<th class="cart-total-label"><?php echo esc_html( $fee->name ); ?></th>
						<td class="cart-total-value" data-title="<?php echo esc_attr( $fee->name ); ?>">
							<?php wc_cart_totals_fee_html( $fee ); ?>
						</td>
					</tr>
				<?php endforeach; ?>

				<!-- Taxes -->
				<?php
				if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) {
					$taxable_address = WC()->customer->get_taxable_address();
					$estimated_text  = '';

					if ( WC()->customer->is_customer_outside_base() && ! WC()->customer->has_calculated_shipping() ) {
						/* translators: %s location. */
						$estimated_text = sprintf( ' <small>' . esc_html__( '(estimated for %s)', 'woocommerce' ) . '</small>', WC()->countries->estimated_for_prefix( $taxable_address[0] ) . WC()->countries->countries[ $taxable_address[0] ] );
					}

					if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) {
						foreach ( WC()->cart->get_tax_totals() as $code => $tax ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							?>
							<tr class="tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
								<th class="cart-total-label"><?php echo esc_html( $tax->label ) . $estimated_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></th>
								<td class="cart-total-value" data-title="<?php echo esc_attr( $tax->label ); ?>"><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
							</tr>
							<?php
						}
					} else {
						?>
						<tr class="tax-total">
							<th class="cart-total-label"><?php echo esc_html( WC()->countries->tax_or_vat() ) . $estimated_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></th>
							<td class="cart-total-value" data-title="<?php echo esc_attr( WC()->countries->tax_or_vat() ); ?>"><?php wc_cart_totals_taxes_total_html(); ?></td>
						</tr>
						<?php
					}
				}
				?>

				<?php do_action( 'woocommerce_cart_totals_before_order_total' ); ?>

				<!-- Order Total -->
				<tr class="order-total">
					<th class="cart-total-label cart-order-total-label"><?php esc_html_e( 'Total', 'woocommerce' ); ?></th>
					<td class="cart-total-value cart-order-total-value" data-title="<?php esc_attr_e( 'Total', 'woocommerce' ); ?>">
						<?php wc_cart_totals_order_total_html(); ?>
					</td>
				</tr>

				<?php do_action( 'woocommerce_cart_totals_after_order_total' ); ?>

			</table>
		</div>
		
		<!-- Checkout Section -->
		<div class="wc-proceed-to-checkout cart-checkout-section">
			<?php do_action( 'woocommerce_proceed_to_checkout' ); ?>
		</div>
		
		<!-- Trust Badges -->
		<div class="cart-summary-trust">
			<div class="cart-summary-badges">
				<div class="cart-summary-badge">
					<span class="cart-badge-icon">🔒</span>
					<span class="cart-badge-text">Secure Checkout</span>
				</div>
				<div class="cart-summary-badge">
					<span class="cart-badge-icon">🚚</span>
					<span class="cart-badge-text">Free Shipping</span>
				</div>
				<div class="cart-summary-badge">
					<span class="cart-badge-icon">↩️</span>
					<span class="cart-badge-text">30-Day Returns</span>
				</div>
			</div>
		</div>
		
		<!-- Payment Methods Preview -->
		<div class="cart-payment-methods">
			<p class="cart-payment-label">We Accept:</p>
			<div class="cart-payment-icons">
				<span class="cart-payment-icon" title="Visa">💳</span>
				<span class="cart-payment-icon" title="Mastercard">💳</span>
				<span class="cart-payment-icon" title="PayPal">🅿️</span>
				<span class="cart-payment-icon" title="Apple Pay">📱</span>
			</div>
		</div>

	</div>

	<?php do_action( 'woocommerce_after_cart_totals' ); ?>

</div>