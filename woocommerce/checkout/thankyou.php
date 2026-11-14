<?php
/**
 * Order received.
 *
 * Steps bar, success header, order summary cards, invoice button, then the payment
 * method's own block (card-to-card instructions + receipt form) and the order details.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package FaucetsValves
 * @version 8.1.0
 *
 * @var WC_Order $order
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="woocommerce-order order-received">
	<?php fv_checkout_steps( 3 ); ?>

	<?php if ( $order ) : ?>
		<?php do_action( 'woocommerce_before_thankyou', $order->get_id() ); ?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>
			<div class="order-received__head is-failed">
				<span class="order-received__icon" aria-hidden="true">!</span>
				<div>
					<h1><?php esc_html_e( 'پرداخت انجام نشد', 'faucets-valves' ); ?></h1>
					<p><?php esc_html_e( 'بانک تراکنش را رد کرد. می‌توانید دوباره پرداخت کنید یا روش دیگری انتخاب کنید.', 'faucets-valves' ); ?></p>
				</div>
			</div>
			<p class="order-received__actions">
				<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="btn btn--brass"><?php esc_html_e( 'پرداخت دوباره', 'faucets-valves' ); ?></a>
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="btn btn--ghost"><?php esc_html_e( 'حساب من', 'faucets-valves' ); ?></a>
				<?php endif; ?>
			</p>
		<?php else : ?>
			<?php $awaiting = 'bacs' === $order->get_payment_method() && $order->has_status( array( 'on-hold', 'pending' ) ); ?>
			<div class="order-received__head">
				<span class="order-received__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="m5 12 5 5L20 7"/></svg></span>
				<div>
					<h1><?php echo $awaiting ? esc_html__( 'سفارش شما ثبت شد؛ منتظر واریز هستیم', 'faucets-valves' ) : esc_html__( 'سفارش شما ثبت شد', 'faucets-valves' ); ?></h1>
					<p><?php echo esc_html( apply_filters( 'woocommerce_thankyou_order_received_text', __( 'از خرید شما سپاسگزاریم. وضعیت سفارش را در حساب کاربری یا با پیامک دنبال کنید.', 'faucets-valves' ), $order ) ); ?></p>
				</div>
				<a class="btn btn--ghost order-received__invoice" href="<?php echo esc_url( fv_order_invoice_url( $order ) ); ?>" target="_blank" rel="noopener"><?php echo fv_icon( 'document' ); // phpcs:ignore ?><?php esc_html_e( 'فاکتور سفارش', 'faucets-valves' ); ?></a>
			</div>

			<ul class="order-overview woocommerce-order-overview woocommerce-thankyou-order-details order_details">
				<li class="woocommerce-order-overview__order order">
					<span><?php esc_html_e( 'شمارهٔ سفارش', 'faucets-valves' ); ?></span>
					<strong><?php echo esc_html( fv_fa_digits( $order->get_order_number() ) ); ?></strong>
				</li>
				<li class="woocommerce-order-overview__date date">
					<span><?php esc_html_e( 'تاریخ', 'faucets-valves' ); ?></span>
					<strong><?php echo esc_html( fv_jalali_date( $order->get_date_created() ? $order->get_date_created()->getTimestamp() : time() ) ); ?></strong>
				</li>
				<li class="woocommerce-order-overview__total total">
					<span><?php esc_html_e( 'مبلغ کل', 'faucets-valves' ); ?></span>
					<strong><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong>
				</li>
				<?php if ( $order->get_payment_method_title() ) : ?>
					<li class="woocommerce-order-overview__payment-method method">
						<span><?php esc_html_e( 'روش پرداخت', 'faucets-valves' ); ?></span>
						<strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
					</li>
				<?php endif; ?>
			</ul>
		<?php endif; ?>

		<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
		<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>

	<?php else : ?>
		<?php wc_get_template( 'checkout/order-received.php', array( 'order' => false ) ); ?>
	<?php endif; ?>
</div>
