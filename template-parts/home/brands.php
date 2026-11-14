<?php
/**
 * Brand wall: WooCommerce Brands with their logos (Products › Brands › thumbnail).
 * Logos show in monochrome and take their own colors on hover.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

$brands = fv_brand_terms( 12 );
if ( ! $brands ) {
	return;
}
$brands_page = fv_page_url_by_template( 'page-templates/brands.php' );
?>
<section class="section section--catalog brands-band" aria-labelledby="brands-title">
	<div class="container">
		<div class="section-head">
			<div>
				<h2 id="brands-title"><?php esc_html_e( 'برندهایی که عرضه می‌کنیم', 'faucets-valves' ); ?></h2>
				<p><?php esc_html_e( 'نمایندگی و تأمین مستقیم از تولیدکنندگان معتبر.', 'faucets-valves' ); ?></p>
			</div>
			<?php if ( $brands_page ) : ?>
				<a class="link-arrow" href="<?php echo esc_url( $brands_page ); ?>"><?php esc_html_e( 'همهٔ برندها', 'faucets-valves' ); ?> <?php echo fv_icon( 'arrow' ); // phpcs:ignore ?></a>
			<?php endif; ?>
		</div>
		<?php get_template_part( 'template-parts/brand-grid', null, array( 'brands' => $brands ) ); ?>
	</div>
</section>
