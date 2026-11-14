<?php
/**
 * Front page scene 1: a pipe manifold on a plaster wall, one branch per category.
 *
 * Without 3D this is a static diagram whose branch names link to the categories.
 * With 3D (assets/js/scene/home.js) the names float over the real pipes, and opening a branch
 * turns its valve and slides out a sheet with that category's products, filled from the
 * <template> elements below.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

$branches = function_exists( 'fv_scene_branches' ) ? fv_scene_branches() : array();
if ( ! $branches ) {
	return;
}
$shop_url = wc_get_page_permalink( 'shop' );
?>
<section class="fv-manifold surface-plaster" id="fv-manifold" aria-labelledby="fv-scene-title">
	<canvas class="fv-manifold__canvas" aria-hidden="true"></canvas>

	<ul class="fv-branches" aria-label="<?php esc_attr_e( 'دسته‌های محصولات', 'faucets-valves' ); ?>">
		<?php foreach ( $branches as $b ) : ?>
			<li class="fv-branch fv-branch--<?php echo esc_attr( $b['kind'] ); ?>">
				<a class="blabel" href="<?php echo esc_url( $b['url'] ); ?>" data-key="<?php echo esc_attr( $b['key'] ); ?>" aria-expanded="false" aria-controls="fv-sheet">
					<b><?php echo esc_html( $b['name'] ); ?></b>
					<span><?php echo esc_html( $b['meta'] ); ?></span>
					<em>
						<?php
						/* translators: %s: valve name, e.g. شیر توپی */
						echo esc_html( sprintf( __( '%s را باز کنید', 'faucets-valves' ), $b['valve'] ) );
						?>
					</em>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>

	<div class="titleblock">
		<div class="tb-main">
			<h1 id="fv-scene-title"><?php echo fv_split_words( fv_mod( 'fv_scene_title' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?></h1>
			<?php if ( fv_mod( 'fv_scene_lead' ) ) : ?>
				<p><?php echo esc_html( fv_mod( 'fv_scene_lead' ) ); ?></p>
			<?php endif; ?>
		</div>
		<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
			<?php if ( fv_mod( "fv_scene_cell_{$i}_v" ) ) : ?>
				<div class="tb-cell"><?php echo esc_html( fv_mod( "fv_scene_cell_{$i}_l" ) ); ?><b><?php echo esc_html( fv_mod( "fv_scene_cell_{$i}_v" ) ); ?></b></div>
			<?php endif; ?>
		<?php endfor; ?>
	</div>

	<?php if ( fv_home_on( 'anatomy' ) ) : ?>
		<a class="fv-hint" href="#fv-anatomy"><i aria-hidden="true"></i><?php esc_html_e( 'کالبدشکافی یک شیر', 'faucets-valves' ); ?></a>
	<?php endif; ?>

	<aside class="fv-sheet" id="fv-sheet" aria-labelledby="fv-sheet-title" hidden>
		<div class="fv-sheet__head">
			<div>
				<h2 class="fv-sheet__title" id="fv-sheet-title"></h2>
				<p class="fv-sheet__meta"></p>
			</div>
			<button class="fv-sheet__close" type="button" aria-label="<?php esc_attr_e( 'بستن شیر', 'faucets-valves' ); ?>"><?php echo fv_icon( 'close' ); // phpcs:ignore ?></button>
		</div>
		<div class="fv-sheet__body"></div>
		<a class="fv-sheet__all" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'همهٔ محصولات این دسته', 'faucets-valves' ); ?></a>
	</aside>

	<?php
	foreach ( $branches as $b ) :
		$products = wc_get_products( array(
			'category'   => array( $b['term']->slug ),
			'limit'      => 6,
			'status'     => 'publish',
			'visibility' => 'catalog',
			'orderby'    => 'menu_order',
			'order'      => 'ASC',
		) );
		?>
		<template id="fv-sheet-<?php echo esc_attr( $b['key'] ); ?>">
			<?php if ( ! $products ) : ?>
				<p class="fv-sheet__empty"><?php esc_html_e( 'محصولی در این دسته نیست. بقیهٔ دسته‌ها را باز کنید یا از جستجو کمک بگیرید.', 'faucets-valves' ); ?></p>
			<?php else : ?>
				<ul class="fv-sheet__list">
					<?php
					foreach ( $products as $product ) :
						$link   = $product->get_permalink();
						$finish = function_exists( 'fv_product_finish' ) ? fv_product_finish( $product ) : '';
						$sub    = array_filter( array( $finish, $product->get_sku() ) );
						?>
						<li>
							<a class="fv-sheet__media" href="<?php echo esc_url( $link ); ?>" tabindex="-1" aria-hidden="true">
								<?php
								if ( $product->get_image_id() ) {
									echo wp_get_attachment_image( $product->get_image_id(), 'woocommerce_thumbnail', false, array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) );
								} else {
									printf( '<img data-thumb="%s" alt="" width="180" height="225">', esc_attr( fv_scene_thumb_key( $product, $b['kind'] ) ) );
								}
								?>
							</a>
							<div class="fv-sheet__info">
								<a href="<?php echo esc_url( $link ); ?>"><b><?php echo esc_html( $product->get_name() ); ?></b></a>
								<?php if ( $sub ) : ?>
									<small><?php echo esc_html( implode( '، ', $sub ) ); ?></small>
								<?php endif; ?>
								<span class="price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
							</div>
							<?php if ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) : ?>
								<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" data-quantity="1" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>" data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>" class="fv-sheet__add add_to_cart_button ajax_add_to_cart" rel="nofollow" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( 'افزودن «%s» به سبد', 'faucets-valves' ), $product->get_name() ) ); ?>"><?php esc_html_e( 'افزودن', 'faucets-valves' ); ?></a>
							<?php else : ?>
								<a href="<?php echo esc_url( $link ); ?>" class="fv-sheet__add"><?php echo $product->is_in_stock() ? esc_html__( 'انتخاب', 'faucets-valves' ) : esc_html__( 'ناموجود', 'faucets-valves' ); ?></a>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</template>
	<?php endforeach; ?>
</section>
