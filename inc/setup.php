<?php
/**
 * Theme supports, menus, widget areas, image sizes.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'faucets-valves', FV_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'custom-logo', array(
		'height'      => 64,
		'width'       => 220,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_post_type_support( 'page', 'excerpt' ); // Used as the lead line in page headers.
	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'assets/css/tokens.css', 'assets/css/base.css' ) );

	register_nav_menus( array(
		'primary' => __( 'منوی اصلی', 'faucets-valves' ),
		'footer'  => __( 'منوی فوتر', 'faucets-valves' ),
	) );

	// Hero: 16:9, large enough for retina laptops.
	add_image_size( 'fv-hero', 2000, 1125, true );
	// Category tiles: 3:4.
	add_image_size( 'fv-tile', 720, 960, true );
} );

add_action( 'widgets_init', function () {
	// Shop filters first: WordPress moves leftover default widgets into the first area, which the
	// footer would otherwise show.
	register_sidebar( array(
		'name'          => __( 'فیلترهای فروشگاه', 'faucets-valves' ),
		'id'            => 'shop-filters',
		'description'   => __( 'ابزارک‌های فیلتر محصولات در صفحه فروشگاه.', 'faucets-valves' ),
		'before_widget' => '<section id="%1$s" class="filter-widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="filter-widget__title">',
		'after_title'   => '</h2>',
	) );
	register_sidebar( array(
		'name'          => __( 'نمادهای اعتماد فوتر (اینماد و ...)', 'faucets-valves' ),
		'id'            => 'footer-badges',
		'description'   => __( 'کد نماد اعتماد الکترونیکی یا ساماندهی را اینجا قرار دهید.', 'faucets-valves' ),
		'before_widget' => '<div id="%1$s" class="badge-widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<span class="sr-only">',
		'after_title'   => '</span>',
	) );
} );

/**
 * Body classes: mark the page surface so header and colors can adapt.
 */
add_filter( 'body_class', function ( $classes ) {
	$classes[] = fv_is_showroom() ? 'surface-showroom' : 'surface-catalog';
	if ( is_front_page() ) {
		$classes[] = 'has-overlay-header';
	}
	return $classes;
} );

/**
 * Showroom page template (wide content from the block patterns). Its body class, surface-showroom,
 * is kept as a styling hook; every surface, this one included, is the light paper/clay/plaster system.
 */
function fv_is_showroom() {
	$showroom = is_page_template( 'page-templates/showroom.php' );
	return (bool) apply_filters( 'fv_is_showroom', $showroom );
}
