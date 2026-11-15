<?php
/**
 * WooCommerce emails in the house's look: clay ground, a soft paper panel, ink headings and
 * buttons, water-blue links, a Persian-friendly font, right-aligned header.
 *
 * Colours are only replaced while they are still WooCommerce's defaults, so anything the store
 * owner sets in WooCommerce › Settings › Emails wins. The rest is extra CSS that WooCommerce
 * inlines into every email.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

/** The house palette for emails (tokens.css). */
function fv_email_palette() {
	return array(
		'base'        => '#1D2326', // ink: buttons, logo text, old-style header band
		'bg'          => '#ECEAE5', // clay in shade: around the panel
		'body_bg'     => '#FBFAF7', // lit clay: the panel
		'text'        => '#1D2326',
		'footer_text' => '#4C565F',
		'link'        => '#1F5F96', // water
		'line'        => '#D8D5CE', // plaster hairline
	);
}

/*
 * Option => [palette key, WooCommerce defaults it replaces]. Both the classic and the
 * "email improvements" defaults are listed.
 */
add_action( 'init', function () {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	$map = array(
		'woocommerce_email_base_color'            => array( 'base', array( '#720eec', '#8526ff', '#7f54b3', '#96588a' ) ),
		'woocommerce_email_background_color'      => array( 'bg', array( '#f7f7f7', '#ffffff', '#f5f5f5' ) ),
		'woocommerce_email_body_background_color' => array( 'body_bg', array( '#ffffff', '#fdfdfd' ) ),
		'woocommerce_email_text_color'            => array( 'text', array( '#3c3c3c', '#1e1e1e', '#515151' ) ),
		'woocommerce_email_footer_text_color'     => array( 'footer_text', array( '#3c3c3c', '#787c82', '#8a8a8a' ) ),
	);
	foreach ( $map as $option => $spec ) {
		add_filter( 'option_' . $option, function ( $value ) use ( $spec ) {
			if ( is_admin() && isset( $_GET['tab'] ) && 'email' === $_GET['tab'] && ! isset( $_GET['preview_woocommerce_mail'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				return $value; // the settings screen shows what is really saved
			}
			$p = fv_email_palette();
			return ( ! $value || in_array( strtolower( (string) $value ), $spec[1], true ) ) ? $p[ $spec[0] ] : $value;
		} );
	}

	// "Left" is WooCommerce's default; in a right-to-left email the logo belongs on the right.
	add_filter( 'option_woocommerce_email_header_alignment', function ( $value ) {
		return ( is_rtl() && ( ! $value || 'left' === $value ) ) ? 'right' : $value;
	} );
} );

/** Extra CSS, inlined by WooCommerce into every email. */
add_filter( 'woocommerce_email_styles', function ( $css ) {
	$p    = fv_email_palette();
	$font = "'Estedad', Tahoma, 'Segoe UI', Arial, sans-serif";
	$side = is_rtl() ? 'right' : 'left';
	ob_start();
	?>
#wrapper { padding: 32px 12px; }
#inner_wrapper, #template_container { border-radius: 14px !important; }
#inner_wrapper { box-shadow: 0 1px 2px rgba(29, 35, 38, .06), 0 8px 24px rgba(29, 35, 38, .06); }
#template_header, #template_header h1, #body_content_inner, #body_content table td, #template_footer #credit,
.email-logo-text, #body_content table td td.email-additional-content, h1, h2, h3, p, td, th { font-family: <?php echo $font; // phpcs:ignore ?>; }
#template_header_image p, #header_wrapper, #header_wrapper h1 { text-align: <?php echo esc_attr( $side ); ?>; }
.email-logo-text { color: <?php echo esc_attr( $p['text'] ); ?>; font-size: 17px; font-weight: bold; letter-spacing: 0; }
#template_header { font-weight: normal; }
#header_wrapper h1, #template_header h1 { font-size: 28px; font-weight: normal; line-height: 140%; color: <?php echo esc_attr( $p['text'] ); ?>; }
h2 { font-size: 18px; font-weight: bold; color: <?php echo esc_attr( $p['text'] ); ?>; }
h3 { font-size: 15px; font-weight: bold; color: <?php echo esc_attr( $p['text'] ); ?>; }
#body_content_inner { font-size: 15px; line-height: 190%; color: <?php echo esc_attr( $p['text'] ); ?>; }
a, .link, #body_content_inner a { color: <?php echo esc_attr( $p['link'] ); ?>; }
.hr { border-bottom: 1px solid <?php echo esc_attr( $p['line'] ); ?>; opacity: 1; }
#body_content .email-order-details td, #body_content .email-order-details th,
.td, #body_content table.td td, #body_content table.td th { border-color: <?php echo esc_attr( $p['line'] ); ?>; }
#body_content .email-order-details .order-totals-total td, #body_content .email-order-details .order-totals-total th { font-size: 18px; color: <?php echo esc_attr( $p['text'] ); ?>; }
.email-order-item-meta, .wc-item-meta { color: <?php echo esc_attr( $p['footer_text'] ); ?>; }
address { border: 0; padding: 0; color: <?php echo esc_attr( $p['text'] ); ?>; font-style: normal; line-height: 180%; }
#template_footer #credit { border-top: 1px solid <?php echo esc_attr( $p['line'] ); ?>; line-height: 190%; }
#template_footer #credit a { color: <?php echo esc_attr( $p['footer_text'] ); ?>; }
/* Buttons (pay for order, reset password) as the site's ink pills */
.email-button, a.email-button {
	display: inline-block; background: <?php echo esc_attr( $p['base'] ); ?>; color: #FBFAF7 !important; text-decoration: none;
	border-radius: 999px; padding: 12px 26px; font-weight: bold;
}
	<?php
	return $css . ob_get_clean();
} );

/* Footer: add the shop's phone from the theme settings, when there is one. */
add_filter( 'woocommerce_email_footer_text', function ( $text ) {
	$phone = function_exists( 'fv_mod' ) ? trim( (string) fv_mod( 'fv_phone' ) ) : '';
	if ( $phone && false === strpos( (string) $text, $phone ) ) {
		/* translators: %s: phone number */
		$text .= '<br />' . sprintf( __( 'تلفن: %s', 'faucets-valves' ), '<span dir="ltr">' . esc_html( $phone ) . '</span>' );
	}
	return $text;
} );
