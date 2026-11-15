<?php
/**
 * Faucets & Valves theme bootstrap.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

define( 'FV_VERSION', '0.9.5' );
define( 'FV_DIR', get_template_directory() );
define( 'FV_URI', get_template_directory_uri() );

require FV_DIR . '/inc/rtl.php';
require FV_DIR . '/inc/setup.php';
require FV_DIR . '/inc/template-tags.php';
require FV_DIR . '/inc/enqueue.php';
require FV_DIR . '/inc/customizer.php';
require FV_DIR . '/inc/customizer-home.php';
require FV_DIR . '/inc/catalogs.php';
require FV_DIR . '/inc/blog.php';
require FV_DIR . '/inc/patterns.php';
require FV_DIR . '/inc/info-pages.php';

if ( class_exists( 'WooCommerce' ) ) {
	require FV_DIR . '/inc/strings.php';
	require FV_DIR . '/inc/woocommerce.php';
	require FV_DIR . '/inc/woocommerce-catalog.php';
	require FV_DIR . '/inc/woocommerce-single.php';
	require FV_DIR . '/inc/checkout.php';
	require FV_DIR . '/inc/invoice.php';
	require FV_DIR . '/inc/card-to-card.php';
	require FV_DIR . '/inc/emails.php';
	require FV_DIR . '/inc/account.php';
	require FV_DIR . '/inc/quick-order.php';
	require FV_DIR . '/inc/mini-cart.php';
	require FV_DIR . '/inc/scene.php';
	require FV_DIR . '/inc/product-3d.php';
}
