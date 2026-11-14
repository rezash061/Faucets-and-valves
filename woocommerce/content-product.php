<?php
/**
 * Product card used in every product loop.
 *
 * A white clay block from the house: photo on a light ground, finish and code in small plain text,
 * name, price and a round add button. The card takes its room's pastel tint on hover/focus.
 * Motion: Staggered Entrance on the list (loop-start.php), Material Reveal on the image.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package FaucetsValves
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}

$link   = $product->get_permalink();
$name   = $product->get_name();
$finish = fv_product_finish( $product );
$sku    = $product->get_sku();
$room   = fv_room_class( fv_product_room( $product ) );
?>
<li <?php wc_product_class( trim( 'card product-card ' . $room ), $product ); ?>>
	<a class="card__media material" href="<?php echo esc_url( $link ); ?>" data-motion="material" tabindex="-1" aria-hidden="true">
		<?php echo $product->get_image( 'woocommerce_thumbnail', array( 'class' => 'card__img', 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php if ( $product->is_on_sale() || $product->is_featured() || ! $product->is_in_stock() ) : ?>
			<span class="card__badges">
				<?php if ( $product->is_on_sale() ) : ?>
					<span class="card__badge card__badge--sale"><?php esc_html_e( 'حراج', 'faucets-valves' ); ?></span>
				<?php elseif ( $product->is_featured() ) : ?>
					<span class="card__badge"><?php esc_html_e( 'ویژه', 'faucets-valves' ); ?></span>
				<?php endif; ?>
				<?php if ( ! $product->is_in_stock() ) : ?>
					<span class="card__badge card__badge--muted"><?php esc_html_e( 'ناموجود', 'faucets-valves' ); ?></span>
				<?php endif; ?>
			</span>
		<?php endif; ?>
	</a>

	<div class="card__body">
		<?php if ( $finish || $sku ) : ?>
			<p class="card__meta">
				<?php if ( $finish ) : ?>
					<?php $sw = fv_finish_swatch( $finish ); ?>
					<span class="card__finish"><?php if ( $sw ) : ?><span class="swatch-dot swatch--<?php echo esc_attr( $sw ); ?>" aria-hidden="true"></span><?php endif; ?><?php echo esc_html( $finish ); ?></span>
				<?php endif; ?>
				<?php if ( $sku ) : ?>
					<span class="card__sku"><span class="screen-reader-text"><?php esc_html_e( 'کد کالا:', 'faucets-valves' ); ?> </span><span dir="ltr"><?php echo esc_html( $sku ); ?></span></span>
				<?php endif; ?>
			</p>
		<?php endif; ?>
		<h3 class="card__name"><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $name ); ?></a></h3>

		<div class="card__foot">
			<span class="price"><?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<?php woocommerce_template_loop_add_to_cart(); ?>
		</div>
		<?php do_action( 'fv_product_card_after', $product ); ?>
	</div>
</li>
