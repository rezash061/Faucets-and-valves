<?php
/**
 * Right-to-left everywhere on the front end, whatever language WordPress is set to.
 *
 * The store is Persian only. Setting the locale's text direction makes is_rtl() true, so
 * WordPress and WooCommerce also load their own RTL stylesheets. The admin keeps the
 * language and direction each user picked.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether this request renders the public site.
 */
function fv_is_front_request() {
	return ! is_admin() || wp_doing_ajax();
}

/**
 * Set the global locale to right-to-left.
 */
function fv_force_rtl() {
	global $wp_locale;
	if ( fv_is_front_request() && $wp_locale instanceof WP_Locale ) {
		$wp_locale->text_direction = 'rtl';
	}
}

fv_force_rtl(); // $wp_locale already exists when the theme loads.
add_action( 'after_setup_theme', 'fv_force_rtl', 1 );
add_action( 'init', 'fv_force_rtl', 1 );
add_action( 'change_locale', 'fv_force_rtl' ); // switch_to_locale() builds a new WP_Locale.

/**
 * <html dir="rtl" lang="fa-IR">
 */
add_filter( 'language_attributes', function ( $output, $doctype ) {
	if ( ! fv_is_front_request() || 'html' !== $doctype ) {
		return $output;
	}
	return 'dir="rtl" lang="fa-IR"';
}, 20, 2 );
