<?php
/**
 * Catalog download band (dark, split: text + image with Material Reveal).
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

$url = fv_mod( 'fv_catalog_url' ) ? fv_mod( 'fv_catalog_url' ) : fv_catalogs_page_url();
if ( ! $url ) {
	return;
}
$image = (int) fv_mod( 'fv_catalog_image' );
?>
<section class="section section--showroom catalog-band" aria-labelledby="catalog-band-title">
	<div class="container catalog-band__inner">
		<div class="catalog-band__text">
			<p class="hero__eyebrow"><?php esc_html_e( 'PDF · مشخصات فنی', 'faucets-valves' ); ?></p>
			<h2 id="catalog-band-title"><?php echo esc_html( fv_mod( 'fv_catalog_title' ) ); ?></h2>
			<p><?php echo esc_html( fv_mod( 'fv_catalog_text' ) ); ?></p>
			<a class="btn btn--brass" href="<?php echo esc_url( $url ); ?>">
				<?php echo fv_icon( 'document' ); // phpcs:ignore ?>
				<?php echo esc_html( fv_mod( 'fv_catalog_button' ) ); ?>
			</a>
		</div>
		<div class="catalog-band__media material" data-motion="material">
			<?php
			if ( $image ) {
				echo wp_get_attachment_image( $image, 'large', false, array( 'alt' => '', 'loading' => 'lazy' ) );
			} else {
				echo '<span class="catalog-band__placeholder surface--brass" aria-hidden="true"></span>';
			}
			?>
		</div>
	</div>
</section>
