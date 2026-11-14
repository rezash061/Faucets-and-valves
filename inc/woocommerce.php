<?php
/**
 * WooCommerce integration: support flags, image sizes, wrappers, cart fragments.
 *
 * Wholesale runs as ordinary WooCommerce sales: one public price, visible to everyone.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
	add_theme_support( 'woocommerce', array(
		'thumbnail_image_width' => 600,
		'single_image_width'    => 1000,
		'product_grid'          => array(
			'default_rows'    => 4,
			'min_rows'        => 1,
			'default_columns' => 4,
			'min_columns'     => 2,
			'max_columns'     => 4,
		),
	) );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
} );

/**
 * Product card images are 4:5 portrait, cropped from the center.
 */
add_filter( 'woocommerce_get_image_size_thumbnail', function () {
	return array( 'width' => 600, 'height' => 750, 'crop' => 1 );
} );

/**
 * Replace WooCommerce's layout stylesheet (floats) with the theme grid. The general and
 * small-screen sheets stay until the cart and checkout are restyled in Phase 4.
 */
add_filter( 'woocommerce_enqueue_styles', function ( $styles ) {
	unset( $styles['woocommerce-layout'] );
	return $styles;
} );

/**
 * Theme content wrappers instead of WooCommerce's defaults.
 */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

add_action( 'woocommerce_before_main_content', function () {
	echo '<div class="container shop-wrap">';
}, 10 );
add_action( 'woocommerce_after_main_content', function () {
	echo '</div>';
}, 10 );

/**
 * Breadcrumb: thin separator, no "Home" icon.
 */
add_filter( 'woocommerce_breadcrumb_defaults', function ( $args ) {
	$args['delimiter']   = '<span class="crumb-sep" aria-hidden="true">/</span>';
	$args['wrap_before'] = '<nav class="breadcrumb" aria-label="' . esc_attr__( 'مسیر صفحه', 'faucets-valves' ) . '">';
	$args['wrap_after']  = '</nav>';
	$args['home']        = _x( 'خانه', 'breadcrumb', 'faucets-valves' );
	return $args;
} );

/**
 * Keep the header cart count in sync after AJAX add-to-cart.
 */
add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
	$fragments['a.header-cart']     = fv_cart_link( 'icon-btn header-cart' );
	$fragments['a.mobile-bar-cart'] = fv_cart_link( 'mobile-bar__item mobile-bar-cart', true );
	return $fragments;
} );

/**
 * Finish label for a product card: first term of the "finish" attribute (pa_finish), if used.
 * Create a global attribute named "finish" (e.g. کروم، برنج، مشکی مات، طلایی) to fill it.
 *
 * @param WC_Product $product Product.
 */
function fv_product_finish( $product ) {
	$finish = $product->get_attribute( 'pa_finish' );
	if ( '' === $finish ) {
		return '';
	}
	$first = trim( explode( ',', $finish )[0] );
	return $first;
}

/**
 * Up to 4 columns, set from WooCommerce's own Customizer option.
 */
add_filter( 'loop_shop_columns', function () {
	return (int) get_option( 'woocommerce_catalog_columns', 4 );
} );
