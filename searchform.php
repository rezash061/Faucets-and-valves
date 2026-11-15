<?php
/**
 * Search form used by get_search_form() (404 page, widgets): searches products by name or
 * item code, like the header search.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

$fv_sid = wp_unique_id( 'fv-search-' );
?>
<form class="fv-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="sr-only" for="<?php echo esc_attr( $fv_sid ); ?>"><?php esc_html_e( 'جستجوی محصول', 'faucets-valves' ); ?></label>
	<span class="fv-search__field">
		<?php echo fv_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
		<input id="<?php echo esc_attr( $fv_sid ); ?>" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'نام محصول یا کد کالا…', 'faucets-valves' ); ?>">
	</span>
	<?php if ( class_exists( 'WooCommerce' ) ) : ?>
		<input type="hidden" name="post_type" value="product">
	<?php endif; ?>
	<button class="pill" type="submit"><?php esc_html_e( 'جستجو', 'faucets-valves' ); ?></button>
</form>
