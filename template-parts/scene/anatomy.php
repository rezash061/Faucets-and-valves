<?php
/**
 * Front page scene 2: an exploded faucet driven by scroll.
 *
 * Three steps: the closed faucet, its nine labelled parts, then the reassembled faucet with
 * the chosen product's price and add-to-cart (Customizer › صحنهٔ صفحهٔ نخست).
 * Without 3D the same text shows next to the product photo, with the part list below it.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

$product = function_exists( 'fv_anatomy_product' ) ? fv_anatomy_product() : null;
$parts   = function_exists( 'fv_anatomy_parts' ) ? fv_anatomy_parts() : array();
?>
<section class="fv-anatomy" id="fv-anatomy" aria-labelledby="fv-anat-title">
	<canvas class="fv-anatomy__canvas" aria-hidden="true"></canvas>
	<svg class="fv-anatomy__lines" aria-hidden="true"></svg>
	<div class="fv-anatomy__labels" aria-hidden="true"></div>

	<?php if ( $product && $product->get_image_id() ) : ?>
		<div class="fv-anatomy__photo">
			<?php echo wp_get_attachment_image( $product->get_image_id(), 'woocommerce_single', false, array( 'alt' => $product->get_name(), 'loading' => 'lazy' ) ); ?>
		</div>
	<?php endif; ?>

	<div class="anat-copy">
		<div class="stage-text">
			<h2 id="fv-anat-title"><?php echo esc_html( fv_mod( 'fv_anatomy_t1' ) ); ?></h2>
			<p><?php echo esc_html( fv_mod( 'fv_anatomy_p1' ) ); ?></p>
		</div>
		<div class="stage-text">
			<h3><?php echo esc_html( fv_mod( 'fv_anatomy_t2' ) ); ?></h3>
			<p><?php echo esc_html( fv_mod( 'fv_anatomy_p2' ) ); ?></p>
			<?php if ( $parts ) : ?>
				<ol class="fv-anatomy__parts">
					<?php foreach ( $parts as $part ) : ?>
						<li><b><?php echo esc_html( $part[0] ); ?></b> <span><?php echo esc_html( $part[1] ); ?></span></li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
		</div>
		<?php if ( $product ) : ?>
			<div class="stage-text">
				<h3><a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3>
				<?php $short = wp_strip_all_tags( $product->get_short_description() ); ?>
				<?php if ( $short ) : ?>
					<p><?php echo esc_html( wp_trim_words( $short, 24 ) ); ?></p>
				<?php endif; ?>
				<div class="buy">
					<span class="price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
					<?php if ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) : ?>
						<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" data-quantity="1" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>" class="pill add_to_cart_button ajax_add_to_cart" rel="nofollow"><?php esc_html_e( 'افزودن به سبد', 'faucets-valves' ); ?></a>
					<?php else : ?>
						<a href="<?php echo esc_url( $product->get_permalink() ); ?>" class="pill"><?php esc_html_e( 'مشاهدهٔ محصول', 'faucets-valves' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
	<div class="fv-anatomy__progress" aria-hidden="true"><span><?php esc_html_e( 'جدا کردن قطعه‌ها', 'faucets-valves' ); ?></span><i><b></b></i></div>
</section>
