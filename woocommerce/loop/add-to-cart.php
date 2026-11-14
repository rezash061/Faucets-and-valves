<?php
/**
 * Loop add-to-cart: a square icon button. The visible label is kept for screen readers,
 * and WooCommerce's AJAX classes and data attributes are untouched.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package FaucetsValves
 * @version 9.2.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

$aria_describedby = isset( $args['aria-describedby_text'] ) ? sprintf( 'aria-describedby="woocommerce_loop_add_to_cart_link_describedby_%s"', esc_attr( $product->get_id() ) ) : '';
$is_simple_buy    = $product->is_purchasable() && $product->is_in_stock() && $product->supports( 'ajax_add_to_cart' );

echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'woocommerce_loop_add_to_cart_link',
	sprintf(
		'<a href="%1$s" %2$s data-quantity="%3$s" class="%4$s card__add" %5$s>%6$s<span class="screen-reader-text">%7$s</span></a>',
		esc_url( $product->add_to_cart_url() ),
		$aria_describedby,
		esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
		esc_attr( isset( $args['class'] ) ? $args['class'] : 'button' ),
		isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
		fv_icon( $is_simple_buy ? 'plus' : 'arrow' ),
		esc_html( $product->add_to_cart_text() )
	),
	$product,
	$args
);
?>
<?php if ( isset( $args['aria-describedby_text'] ) ) : ?>
	<span id="woocommerce_loop_add_to_cart_link_describedby_<?php echo esc_attr( $product->get_id() ); ?>" class="screen-reader-text">
		<?php echo esc_html( $args['aria-describedby_text'] ); ?>
	</span>
<?php endif; ?>
