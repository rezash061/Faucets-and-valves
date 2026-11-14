<?php
/**
 * My account dashboard: greeting, orders awaiting card-to-card receipt, summary cards,
 * last order, shortcuts.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package FaucetsValves
 * @version 4.4.0
 */

defined( 'ABSPATH' ) || exit;

$summary   = fv_account_summary();
$quick_url = fv_page_url_by_template( 'page-templates/quick-order.php' );
?>
<div class="account-dash">
	<h2 class="account-dash__hello">
		<?php
		/* translators: %s: customer name */
		echo esc_html( sprintf( __( 'سلام %s', 'faucets-valves' ), $current_user->first_name ? $current_user->first_name : $current_user->display_name ) );
		?>
	</h2>

	<?php foreach ( $summary['awaiting'] as $o ) : ?>
		<div class="account-alert">
			<?php echo fv_icon( 'card' ); // phpcs:ignore ?>
			<p>
				<?php
				/* translators: 1: order number 2: total */
				echo wp_kses_post( sprintf( __( 'سفارش %1$s به مبلغ %2$s منتظر واریز کارت به کارت و ثبت رسید است.', 'faucets-valves' ), '<b>' . esc_html( fv_fa_digits( $o->get_order_number() ) ) . '</b>', $o->get_formatted_order_total() ) );
				?>
			</p>
			<a class="btn btn--brass" href="<?php echo esc_url( $o->get_view_order_url() ); ?>"><?php esc_html_e( 'ثبت رسید', 'faucets-valves' ); ?></a>
		</div>
	<?php endforeach; ?>

	<ul class="account-cards">
		<li>
			<span class="account-cards__label"><?php esc_html_e( 'همهٔ سفارش‌ها', 'faucets-valves' ); ?></span>
			<span class="account-cards__value"><?php echo esc_html( number_format_i18n( $summary['count'] ) ); ?></span>
			<a href="<?php echo esc_url( wc_get_endpoint_url( 'orders' ) ); ?>"><?php esc_html_e( 'مشاهده', 'faucets-valves' ); ?></a>
		</li>
		<li>
			<span class="account-cards__label"><?php esc_html_e( 'جمع خرید', 'faucets-valves' ); ?></span>
			<span class="account-cards__value"><?php echo wp_kses_post( wc_price( $summary['spent'] ) ); ?></span>
		</li>
		<li>
			<span class="account-cards__label"><?php esc_html_e( 'نشانی‌ها', 'faucets-valves' ); ?></span>
			<span class="account-cards__value account-cards__value--sm"><?php esc_html_e( 'نشانی ارسال و صورتحساب', 'faucets-valves' ); ?></span>
			<a href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address' ) ); ?>"><?php esc_html_e( 'ویرایش', 'faucets-valves' ); ?></a>
		</li>
		<?php if ( $quick_url ) : ?>
			<li class="is-accent">
				<span class="account-cards__label"><?php esc_html_e( 'سفارش عمده', 'faucets-valves' ); ?></span>
				<span class="account-cards__value account-cards__value--sm"><?php esc_html_e( 'چند کالا را با کد و تعداد یک‌جا اضافه کنید', 'faucets-valves' ); ?></span>
				<a href="<?php echo esc_url( $quick_url ); ?>"><?php esc_html_e( 'سفارش سریع', 'faucets-valves' ); ?></a>
			</li>
		<?php endif; ?>
	</ul>

	<?php if ( $summary['last'] ) : ?>
		<?php $last = $summary['last']; ?>
		<section class="account-last">
			<h3><?php esc_html_e( 'آخرین سفارش', 'faucets-valves' ); ?></h3>
			<div class="account-last__row">
				<span><?php esc_html_e( 'شماره', 'faucets-valves' ); ?> <b><?php echo esc_html( fv_fa_digits( $last->get_order_number() ) ); ?></b></span>
				<span><?php echo esc_html( fv_jalali_date( $last->get_date_created() ? $last->get_date_created()->getTimestamp() : time() ) ); ?></span>
				<span class="order-status status-<?php echo esc_attr( $last->get_status() ); ?>"><?php echo esc_html( wc_get_order_status_name( $last->get_status() ) ); ?></span>
				<span><?php echo wp_kses_post( $last->get_formatted_order_total() ); ?></span>
				<a class="btn btn--ghost" href="<?php echo esc_url( $last->get_view_order_url() ); ?>"><?php esc_html_e( 'جزئیات', 'faucets-valves' ); ?></a>
			</div>
		</section>
	<?php endif; ?>

	<?php
	do_action( 'woocommerce_account_dashboard' );
	do_action( 'woocommerce_before_my_account' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks
	do_action( 'woocommerce_after_my_account' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks
	?>
</div>
