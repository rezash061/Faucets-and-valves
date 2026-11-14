<?php
/**
 * Home product rows: best sellers or new arrivals (catalog surface).
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

$type = $args['type'] ?? 'best';

if ( 'new' === $type ) {
	$title     = __( 'تازه‌های انبار', 'faucets-valves' );
	$sub       = __( 'جدیدترین کدهای اضافه‌شده به فروشگاه.', 'faucets-valves' );
	$shortcode = '[products limit="4" columns="4" orderby="date" order="DESC"]';
	$link      = add_query_arg( 'orderby', 'date', wc_get_page_permalink( 'shop' ) );
} else {
	$title     = __( 'پرفروش‌ها', 'faucets-valves' );
	$sub       = __( 'قیمت‌ها برای همه قابل مشاهده است؛ برای سفارش عمده تعداد را در سبد تغییر دهید.', 'faucets-valves' );
	$shortcode = '[products limit="8" columns="4" best_selling="true"]';
	$link      = add_query_arg( 'orderby', 'popularity', wc_get_page_permalink( 'shop' ) );
}

$html = do_shortcode( $shortcode );
if ( false === strpos( $html, 'product-card' ) ) {
	return; // Nothing to show yet.
}
?>
<section class="section section--catalog home-products home-products--<?php echo esc_attr( $type ); ?>" aria-labelledby="home-<?php echo esc_attr( $type ); ?>-title">
	<div class="container">
		<div class="section-head">
			<div>
				<h2 id="home-<?php echo esc_attr( $type ); ?>-title"><?php echo esc_html( $title ); ?></h2>
				<p><?php echo esc_html( $sub ); ?></p>
			</div>
			<a class="link-more" href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'مشاهدهٔ همه', 'faucets-valves' ); ?></a>
		</div>
		<?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- WooCommerce shortcode output. ?>
	</div>
</section>
