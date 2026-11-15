<?php
/**
 * Empty cart: an empty water tank, a short line on what to do next, and the categories as
 * room-tinted chips (the same rooms as the front page house).
 *
 * Overrides woocommerce/cart/cart-empty.php
 *
 * @package FaucetsValves
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

// Notices (e.g. "item removed") still print; WooCommerce's own "cart is empty" notice is replaced below.
do_action( 'woocommerce_cart_is_empty' );

$fv_cats = get_terms( array(
	'taxonomy'   => 'product_cat',
	'parent'     => 0,
	'hide_empty' => true,
	'number'     => 8,
	'orderby'    => 'menu_order',
	'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
) );
$fv_qo   = function_exists( 'fv_page_url_by_template' ) ? fv_page_url_by_template( 'page-templates/quick-order.php' ) : '';
?>
<section class="cart-empty-state" aria-labelledby="cart-empty-title">
	<span class="cart-tank cart-tank--empty" aria-hidden="true"><span class="cart-tank__fill" style="height:0"></span></span>
	<h1 id="cart-empty-title"><?php esc_html_e( 'مخزن سفارش خالی است', 'faucets-valves' ); ?></h1>
	<p><?php esc_html_e( 'از خانهٔ صفحهٔ نخست یک اتاق را باز کنید، در دسته‌ها بگردید یا کد کالاها را در سفارش سریع وارد کنید.', 'faucets-valves' ); ?></p>
	<div class="cart-empty-state__actions">
		<a class="pill" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>"><?php esc_html_e( 'دیدن محصولات', 'faucets-valves' ); ?></a>
		<?php if ( $fv_qo ) : ?>
			<a class="pill pill--ghost" href="<?php echo esc_url( $fv_qo ); ?>"><?php esc_html_e( 'سفارش سریع با کد کالا', 'faucets-valves' ); ?></a>
		<?php endif; ?>
	</div>
	<?php if ( $fv_cats && ! is_wp_error( $fv_cats ) ) : ?>
		<ul class="room-chips" aria-label="<?php esc_attr_e( 'دسته‌ها', 'faucets-valves' ); ?>">
			<?php foreach ( $fv_cats as $fv_cat ) : ?>
				<li><a class="room-chip <?php echo esc_attr( fv_room_class( fv_term_room( $fv_cat ) ) ); ?>" href="<?php echo esc_url( get_term_link( $fv_cat ) ); ?>"><i aria-hidden="true"></i><?php echo esc_html( $fv_cat->name ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
