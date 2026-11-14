<?php
/**
 * Styles, scripts, font preload and the motion boot flag.
 *
 * Everything is self-hosted: no Google Fonts or CDNs, so the store stays fast inside Iran.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

/**
 * Version an asset by its modification time so browsers pick up edits.
 */
function fv_asset_ver( $rel ) {
	$path = FV_DIR . '/' . ltrim( $rel, '/' );
	return file_exists( $path ) ? (string) filemtime( $path ) : FV_VERSION;
}

add_action( 'wp_enqueue_scripts', function () {
	$css = array( 'tokens', 'base', 'layout', 'components', 'showroom', 'motion' );
	$prev = array();
	foreach ( $css as $name ) {
		$rel = "assets/css/{$name}.css";
		wp_enqueue_style( "fv-{$name}", FV_URI . "/{$rel}", $prev, fv_asset_ver( $rel ) );
		$prev = array( "fv-{$name}" );
	}
	if ( class_exists( 'WooCommerce' ) ) {
		$rel = 'assets/css/woocommerce.css';
		wp_enqueue_style( 'fv-woocommerce', FV_URI . "/{$rel}", array( 'fv-motion' ), fv_asset_ver( $rel ) );
	}

	// GSAP core + ScrollTrigger, local copies, deferred.
	wp_register_script( 'gsap', FV_URI . '/assets/vendor/gsap/gsap.min.js', array(), '3.15.1', array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_register_script( 'gsap-scrolltrigger', FV_URI . '/assets/vendor/gsap/ScrollTrigger.min.js', array( 'gsap' ), '3.15.1', array( 'in_footer' => true, 'strategy' => 'defer' ) );

	$rel = 'assets/js/motion/loader.js';
	wp_enqueue_script( 'fv-motion', FV_URI . "/{$rel}", array( 'gsap', 'gsap-scrolltrigger' ), fv_asset_ver( $rel ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_add_inline_script( 'fv-motion', 'window.FV_MOTION = ' . wp_json_encode( array(
		'base' => FV_URI . '/assets/js/motion/',
		'ver'  => FV_VERSION,
	) ) . ';', 'before' );

	$rel = 'assets/js/ui/header.js';
	wp_enqueue_script( 'fv-header', FV_URI . "/{$rel}", array(), fv_asset_ver( $rel ), array( 'in_footer' => true, 'strategy' => 'defer' ) );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
} );

/**
 * Preload the variable font, and add the motion flag before first paint.
 *
 * The flag (html.js-motion) turns on the hidden start states in motion.css. It is set only when
 * the visitor allows motion, and a failsafe removes it if the motion script never reports in,
 * so content can never stay invisible.
 */
add_action( 'wp_head', function () {
	printf(
		'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
		esc_url( FV_URI . '/assets/fonts/Estedad.woff2' )
	);
	?>
<script>(function(d){d.classList.add('js');if(!window.matchMedia||matchMedia('(prefers-reduced-motion: reduce)').matches)return;d.classList.add('js-motion');setTimeout(function(){if(!window.FV_MOTION_READY)d.classList.remove('js-motion');},3500);})(document.documentElement);</script>
	<?php
}, 1 );
