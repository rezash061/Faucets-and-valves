<?php
/**
 * Small template helpers: icons, word split for headlines, logo.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inline SVG icon (Lucide-style, 1.5px stroke). Decorative by default.
 *
 * @param string $name  Icon name.
 * @param string $class Extra class.
 */
function fv_icon( $name, $class = '' ) {
	$paths = array(
		'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/>',
		'bag'      => '<path d="M5 7h14l-1.2 12.2a1 1 0 0 1-1 .8H7.2a1 1 0 0 1-1-.8z"/><path d="M9 7a3 3 0 0 1 6 0"/>',
		'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'close'    => '<path d="M6 6l12 12M18 6 6 18"/>',
		'plus'     => '<path d="M12 5v14M5 12h14"/>',
		'home'     => '<path d="M4 11 12 4l8 7v9h-5v-6H9v6H4z"/>',
		'grid'     => '<rect x="4" y="4" width="7" height="7"/><rect x="13" y="4" width="7" height="7"/><rect x="4" y="13" width="7" height="7"/><rect x="13" y="13" width="7" height="7"/>',
		'arrow'    => '<path d="M19 12H5m6-6-6 6 6 6"/>', // Points to the inline end in RTL (left).
		'phone'    => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a1 1 0 0 1-1 1A16 16 0 0 1 4 5a1 1 0 0 1 1-1z"/>',
		'pin'      => '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
		'clock'    => '<circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/>',
		'truck'    => '<path d="M3 6h11v10H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="17.5" r="1.5"/><circle cx="17" cy="17.5" r="1.5"/>',
		'shield'   => '<path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6z"/><path d="m9 12 2 2 4-4"/>',
		'card'     => '<rect x="3" y="6" width="18" height="12" rx="1"/><path d="M3 10h18"/>',
		'document' => '<path d="M7 3h7l4 4v14H7z"/><path d="M14 3v4h4M10 12h5M10 16h5"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return sprintf(
		'<svg class="icon icon-%1$s %2$s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
		esc_attr( $name ),
		esc_attr( $class ),
		$paths[ $name ] // Static markup above.
	);
}

/**
 * Wrap each word of a headline for the Cinematic Reveal.
 *
 * Splits by whitespace only, never by letter, so Persian letters keep joining.
 * Allows <strong> and <em> for emphasis; other tags are stripped.
 *
 * @param string $text Headline text.
 * @return string Safe HTML.
 */
function fv_split_words( $text ) {
	$text  = wp_kses( (string) $text, array( 'strong' => array(), 'em' => array() ) );
	$parts = preg_split( '/(<\/?(?:strong|em)>)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
	$out   = '';
	foreach ( $parts as $part ) {
		if ( preg_match( '/^<\/?(?:strong|em)>$/u', $part ) ) {
			$out .= $part;
			continue;
		}
		$tokens = preg_split( '/(\s+)/u', $part, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
		foreach ( $tokens as $token ) {
			$out .= preg_match( '/^\s+$/u', $token )
				? ' '
				: '<span class="w"><span>' . esc_html( html_entity_decode( $token, ENT_QUOTES, 'UTF-8' ) ) . '</span></span>';
		}
	}
	return $out;
}

/**
 * Site logo: the custom logo if set, otherwise the site name as a wordmark.
 */
function fv_logo() {
	if ( has_custom_logo() ) {
		the_custom_logo();
		return;
	}
	printf(
		'<a class="logo" href="%1$s" rel="home"><b>%2$s</b><small>%3$s</small></a>',
		esc_url( home_url( '/' ) ),
		esc_html( get_bloginfo( 'name' ) ),
		esc_html( fv_mod( 'fv_logo_tagline' ) )
	);
}

/**
 * Cart link with live count (refreshed by WooCommerce cart fragments).
 */
function fv_cart_link( $class = 'icon-btn header-cart', $with_label = false ) {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return '';
	}
	$count = WC()->cart->get_cart_contents_count();
	/* translators: %s: number of items */
	$label = sprintf( __( 'سبد خرید، %s قلم', 'faucets-valves' ), number_format_i18n( $count ) );

	// Header: the cart is a small water tank that fills as items are added.
	if ( false !== strpos( $class, 'header-cart' ) ) {
		$fill = $count ? min( 100, 18 + $count * 8 ) : 8;
		return sprintf(
			'<a class="%1$s tank" href="%2$s" aria-label="%3$s"><span class="tank__body" aria-hidden="true"><span class="tank__fill" style="height:%4$d%%"></span></span><span class="tank__label" aria-hidden="true">%5$s<b>%6$s</b></span><span class="cart-count" hidden>%7$s</span></a>',
			esc_attr( str_replace( 'icon-btn', '', $class ) ),
			esc_url( wc_get_cart_url() ),
			esc_attr( $label ),
			$fill,
			esc_html__( 'مخزن سفارش', 'faucets-valves' ),
			/* translators: %s: number of items */
			esc_html( sprintf( __( '%s قلم', 'faucets-valves' ), number_format_i18n( $count ) ) ),
			esc_html( number_format_i18n( $count ) )
		);
	}

	return sprintf(
		'<a class="%1$s" href="%2$s" aria-label="%3$s">%4$s<span class="cart-count" %5$s>%6$s</span>%7$s</a>',
		esc_attr( $class ),
		esc_url( wc_get_cart_url() ),
		esc_attr( $label ),
		fv_icon( 'bag' ),
		$count ? '' : 'hidden',
		esc_html( number_format_i18n( $count ) ),
		$with_label ? '<span aria-hidden="true">' . esc_html__( 'سبد', 'faucets-valves' ) . '</span>' : ''
	);
}

/**
 * URL of the first published page that uses a given page template.
 *
 * @param string $template Template path, e.g. page-templates/brands.php.
 */
function fv_page_url_by_template( $template ) {
	$pages = get_pages( array(
		'meta_key'   => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value' => $template, // phpcs:ignore WordPress.DB.SlowDBQuery
		'number'     => 1,
	) );
	return $pages ? get_permalink( $pages[0] ) : '';
}

/**
 * WooCommerce brands (product_brand) that have products, most products first.
 *
 * @param int $number Max brands; 0 = all.
 * @return WP_Term[]
 */
function fv_brand_terms( $number = 0 ) {
	if ( ! taxonomy_exists( 'product_brand' ) ) {
		return array();
	}
	$terms = get_terms( array(
		'taxonomy'   => 'product_brand',
		'hide_empty' => true,
		'number'     => $number,
		'orderby'    => 'count',
		'order'      => 'DESC',
	) );
	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * Reading time in minutes for a post (Persian text ≈ 200 words per minute).
 */
function fv_reading_time( $post = null ) {
	$text  = wp_strip_all_tags( get_post_field( 'post_content', $post ) );
	$words = count( preg_split( '/\s+/u', trim( $text ), -1, PREG_SPLIT_NO_EMPTY ) );
	return max( 1, (int) ceil( $words / 200 ) );
}
