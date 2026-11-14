<?php
/**
 * Shop and category pages: filters, sorting, query tweaks, assets.
 *
 * Filters use WooCommerce's own URL parameters where they exist, so they keep working with
 * WooCommerce's caching and widgets:
 *   filter_{attribute}=slug,slug & query_type_{attribute}=or   (global attributes, e.g. finish)
 *   min_price / max_price                                       (price)
 * and two theme parameters:
 *   brand=slug,slug   (WooCommerce Brands taxonomy, product_brand)
 *   stock=1           (in-stock only)
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

/**
 * Persian and Arabic-Indic digits → Latin digits.
 */
function fv_latin_digits( $value ) {
	return strtr( (string) $value, array(
		'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
		'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
		'٬' => '', ',' => '', '،' => '',
	) );
}

/**
 * Normalise filter input before WooCommerce reads it:
 * - checkbox arrays (filter_x[]=a&filter_x[]=b, the no-JS form) → "a,b"
 * - Persian digits in prices → Latin, empty prices removed.
 */
add_action( 'init', function () {
	if ( is_admin() || empty( $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	foreach ( $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification
		if ( is_array( $value ) && ( 0 === strpos( $key, 'filter_' ) || 'brand' === $key ) ) {
			$_GET[ $key ] = implode( ',', array_map( 'sanitize_title', wp_unslash( $value ) ) );
		}
	}
	foreach ( array( 'min_price', 'max_price' ) as $key ) {
		if ( isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$num = preg_replace( '/[^0-9.]/', '', fv_latin_digits( wp_unslash( $_GET[ $key ] ) ) ); // phpcs:ignore
			if ( '' === $num ) {
				unset( $_GET[ $key ] );
			} else {
				$_GET[ $key ] = $num;
			}
		}
	}
}, 1 );

/**
 * Brand and stock filters.
 */
add_action( 'woocommerce_product_query', function ( $query ) {
	if ( ! $query instanceof WP_Query ) {
		return;
	}
	$brands = fv_get_list_param( 'brand' );
	if ( $brands && taxonomy_exists( 'product_brand' ) ) {
		$tax   = (array) $query->get( 'tax_query' );
		$tax[] = array(
			'taxonomy' => 'product_brand',
			'field'    => 'slug',
			'terms'    => $brands,
			'operator' => 'IN',
		);
		$query->set( 'tax_query', $tax );
	}
	if ( ! empty( $_GET['stock'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$meta   = (array) $query->get( 'meta_query' );
		$meta[] = array(
			'key'     => '_stock_status',
			'value'   => 'instock',
			'compare' => '=',
		);
		$query->set( 'meta_query', $meta );
	}
} );

/**
 * A comma-separated GET list, sanitised.
 */
function fv_get_list_param( $key ) {
	if ( empty( $_GET[ $key ] ) || ! is_string( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return array();
	}
	$raw = explode( ',', rawurldecode( wp_unslash( $_GET[ $key ] ) ) ); // phpcs:ignore
	return array_values( array_filter( array_map( 'sanitize_title', $raw ) ) );
}

/**
 * 16 products per page (4 rows of 4).
 */
add_filter( 'loop_shop_per_page', function () {
	return 16;
} );

/**
 * Sorting options, in Persian.
 */
function fv_orderby_options() {
	$options = array(
		'menu_order' => __( 'پیش‌فرض', 'faucets-valves' ),
		'popularity' => __( 'پرفروش‌ترین', 'faucets-valves' ),
		'date'       => __( 'جدیدترین', 'faucets-valves' ),
		'price'      => __( 'ارزان‌ترین', 'faucets-valves' ),
		'price-desc' => __( 'گران‌ترین', 'faucets-valves' ),
	);
	if ( wc_review_ratings_enabled() ) {
		$options['rating'] = __( 'بیشترین امتیاز', 'faucets-valves' );
	}
	return apply_filters( 'woocommerce_catalog_orderby', $options );
}

/**
 * The URL of the current listing without any filter parameters.
 */
function fv_shop_base_url() {
	if ( is_product_taxonomy() ) {
		$link = get_term_link( get_queried_object() );
		return is_wp_error( $link ) ? wc_get_page_permalink( 'shop' ) : $link;
	}
	return wc_get_page_permalink( 'shop' );
}

/**
 * Global attributes worth filtering by: those with at least one term in use.
 *
 * @return array[] Each: taxonomy, name (slug without pa_), label, terms (WP_Term[]), chosen (slugs).
 */
function fv_filter_attributes() {
	$out = array();
	foreach ( wc_get_attribute_taxonomies() as $attr ) {
		$taxonomy = wc_attribute_taxonomy_name( $attr->attribute_name );
		if ( ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}
		$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => true ) );
		if ( is_wp_error( $terms ) || ! $terms ) {
			continue;
		}
		$out[] = array(
			'taxonomy' => $taxonomy,
			'name'     => $attr->attribute_name,
			'label'    => $attr->attribute_label,
			'terms'    => $terms,
			'chosen'   => fv_get_list_param( 'filter_' . $attr->attribute_name ),
		);
	}
	return $out;
}

/**
 * Price bounds of all published products, for the price inputs' placeholders.
 */
function fv_price_bounds() {
	global $wpdb;
	$cache = wp_cache_get( 'fv_price_bounds', 'faucets-valves' );
	if ( false !== $cache ) {
		return $cache;
	}
	$row = $wpdb->get_row( "SELECT MIN(min_price) AS lo, MAX(max_price) AS hi FROM {$wpdb->wc_product_meta_lookup}" ); // phpcs:ignore WordPress.DB
	$out = array( 'min' => $row ? floor( (float) $row->lo ) : 0, 'max' => $row ? ceil( (float) $row->hi ) : 0 );
	wp_cache_set( 'fv_price_bounds', $out, 'faucets-valves', HOUR_IN_SECONDS );
	return $out;
}

/**
 * A colour swatch for well-known finishes, matched by name.
 */
function fv_finish_swatch( $name ) {
	$map = array(
		'مشکی'  => 'black',
		'طلا'   => 'gold',
		'رزگلد' => 'rose',
		'مس'    => 'copper',
		'برنج'  => 'brass',
		'کروم'  => 'chrome',
		'نیکل'  => 'steel',
		'استیل' => 'steel',
		'سفید'  => 'white',
		'chrome' => 'chrome',
		'brass'  => 'brass',
		'black'  => 'black',
		'gold'   => 'gold',
	);
	foreach ( $map as $needle => $swatch ) {
		if ( false !== mb_stripos( $name, $needle ) ) {
			return $swatch;
		}
	}
	return '';
}

/**
 * Active filters, each with a link that removes it.
 *
 * @return array[] Each: label, url.
 */
function fv_active_filters() {
	$active = array();
	$base   = remove_query_arg( 'paged', add_query_arg( null, null ) );
	$base   = preg_replace( '#/page/\d+/?#', '/', $base );

	foreach ( fv_filter_attributes() as $attr ) {
		foreach ( $attr['chosen'] as $slug ) {
			$term = get_term_by( 'slug', $slug, $attr['taxonomy'] );
			if ( ! $term ) {
				continue;
			}
			$rest     = array_diff( $attr['chosen'], array( $slug ) );
			$key      = 'filter_' . $attr['name'];
			$url      = $rest ? add_query_arg( $key, implode( ',', $rest ), $base ) : remove_query_arg( array( $key, 'query_type_' . $attr['name'] ), $base );
			$active[] = array( 'label' => $term->name, 'url' => $url );
		}
	}
	$brands = fv_get_list_param( 'brand' );
	foreach ( $brands as $slug ) {
		$term = taxonomy_exists( 'product_brand' ) ? get_term_by( 'slug', $slug, 'product_brand' ) : null;
		if ( ! $term ) {
			continue;
		}
		$rest     = array_diff( $brands, array( $slug ) );
		$url      = $rest ? add_query_arg( 'brand', implode( ',', $rest ), $base ) : remove_query_arg( 'brand', $base );
		$active[] = array( 'label' => $term->name, 'url' => $url );
	}
	if ( isset( $_GET['min_price'] ) || isset( $_GET['max_price'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$lo = isset( $_GET['min_price'] ) ? wc_price( (float) $_GET['min_price'] ) : ''; // phpcs:ignore
		$hi = isset( $_GET['max_price'] ) ? wc_price( (float) $_GET['max_price'] ) : ''; // phpcs:ignore
		if ( $lo && $hi ) {
			/* translators: 1: min price 2: max price */
			$label = sprintf( __( 'قیمت: %1$s تا %2$s', 'faucets-valves' ), $lo, $hi );
		} elseif ( $lo ) {
			/* translators: %s: min price */
			$label = sprintf( __( 'قیمت از %s', 'faucets-valves' ), $lo );
		} else {
			/* translators: %s: max price */
			$label = sprintf( __( 'قیمت تا %s', 'faucets-valves' ), $hi );
		}
		$active[] = array( 'label' => wp_strip_all_tags( $label ), 'url' => remove_query_arg( array( 'min_price', 'max_price' ), $base ) );
	}
	if ( ! empty( $_GET['stock'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$active[] = array( 'label' => __( 'فقط موجود', 'faucets-valves' ), 'url' => remove_query_arg( 'stock', $base ) );
	}
	return $active;
}

/**
 * Categories for the filter panel: children of the current category, or top-level ones.
 *
 * @return array Parent term (or null) and its child terms.
 */
function fv_filter_categories() {
	$current = is_product_category() ? get_queried_object() : null;
	$parent  = 0;
	if ( $current ) {
		$children = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $current->term_id, 'hide_empty' => true ) );
		$parent   = ( ! is_wp_error( $children ) && $children ) ? $current->term_id : $current->parent;
	}
	$terms = get_terms( array(
		'taxonomy'   => 'product_cat',
		'parent'     => $parent,
		'hide_empty' => true,
		'orderby'    => 'menu_order',
		'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
	) );
	return array(
		'parent'  => $parent ? get_term( $parent, 'product_cat' ) : null,
		'terms'   => is_wp_error( $terms ) ? array() : $terms,
		'current' => $current ? $current->term_id : 0,
	);
}

/**
 * Assets for listings.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! ( is_shop() || is_product_taxonomy() || ( is_search() && 'product' === get_query_var( 'post_type' ) ) ) ) {
		return;
	}
	$rel = 'assets/css/shop.css';
	wp_enqueue_style( 'fv-shop', FV_URI . "/{$rel}", array( 'fv-woocommerce' ), fv_asset_ver( $rel ) );
	$rel = 'assets/js/ui/shop-filters.js';
	wp_enqueue_script( 'fv-shop-filters', FV_URI . "/{$rel}", array(), fv_asset_ver( $rel ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
}, 20 );
