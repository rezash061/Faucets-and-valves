<?php
/**
 * Mini-cart drawer: opens from the header cart icon and after AJAX add-to-cart.
 * Content is WooCommerce's own mini cart, kept fresh by its cart fragments.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_footer', function () {
	if ( is_cart() || is_checkout() ) {
		return;
	}
	?>
	<div class="cart-drawer" id="cart-drawer" hidden>
		<div class="cart-drawer__scrim" data-drawer-close></div>
		<aside class="cart-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="cart-drawer-title">
			<header class="cart-drawer__head">
				<h2 id="cart-drawer-title"><?php esc_html_e( 'سبد خرید', 'faucets-valves' ); ?></h2>
				<button type="button" class="icon-btn" data-drawer-close aria-label="<?php esc_attr_e( 'بستن', 'faucets-valves' ); ?>"><?php echo fv_icon( 'close' ); // phpcs:ignore ?></button>
			</header>
			<div class="widget_shopping_cart_content"><?php woocommerce_mini_cart(); ?></div>
		</aside>
	</div>
	<?php
}, 5 );

/**
 * Proforma link among the mini cart buttons.
 */
add_action( 'woocommerce_widget_shopping_cart_buttons', function () {
	if ( WC()->cart && ! WC()->cart->is_empty() ) {
		printf( '<a href="%1$s" class="button proforma" target="_blank" rel="noopener">%2$s</a>', esc_url( fv_proforma_url() ), esc_html__( 'پیش‌فاکتور', 'faucets-valves' ) );
	}
}, 30 );

/**
 * Persian labels for the mini cart buttons.
 */
remove_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_button_view_cart', 10 );
remove_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_proceed_to_checkout', 20 );
add_action( 'woocommerce_widget_shopping_cart_buttons', function () {
	printf( '<a href="%1$s" class="button wc-forward">%2$s</a>', esc_url( wc_get_cart_url() ), esc_html__( 'مشاهدهٔ سبد', 'faucets-valves' ) );
}, 10 );
add_action( 'woocommerce_widget_shopping_cart_buttons', function () {
	printf( '<a href="%1$s" class="button checkout wc-forward">%2$s</a>', esc_url( wc_get_checkout_url() ), esc_html__( 'تسویه حساب', 'faucets-valves' ) );
}, 20 );

add_action( 'wp_enqueue_scripts', function () {
	if ( is_cart() || is_checkout() ) {
		return;
	}
	$rel = 'assets/js/ui/mini-cart.js';
	wp_enqueue_script( 'fv-mini-cart', FV_URI . "/{$rel}", array(), fv_asset_ver( $rel ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_enqueue_script( 'wc-cart-fragments' );
}, 20 );
