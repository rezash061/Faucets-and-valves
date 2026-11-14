<?php
/**
 * Top-level product categories as tall tiles (Staggered Entrance + Material Reveal).
 * Uses each category's thumbnail, set in Products → Categories.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

if ( ! taxonomy_exists( 'product_cat' ) ) {
	return;
}

$cats = get_terms( array(
	'taxonomy'   => 'product_cat',
	'parent'     => 0,
	'hide_empty' => true,
	'number'     => 8,
	'orderby'    => 'menu_order',
	'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
) );

if ( is_wp_error( $cats ) || empty( $cats ) ) {
	return;
}
?>
<section class="section section--catalog drawers-section" aria-labelledby="cats-title">
	<div class="container">
		<div class="section-head">
			<div>
				<h2 id="cats-title"><?php esc_html_e( 'کشوهای انبار', 'faucets-valves' ); ?></h2>
				<p><?php esc_html_e( 'همهٔ دسته‌ها با تعداد کد کالا.', 'faucets-valves' ); ?></p>
			</div>
			<a class="link-arrow" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'همهٔ محصولات', 'faucets-valves' ); ?> <?php echo fv_icon( 'arrow' ); // phpcs:ignore ?></a>
		</div>

		<ul class="drawers" data-motion="stagger">
			<?php foreach ( $cats as $cat ) : ?>
				<?php
				$thumb = (int) get_term_meta( $cat->term_id, 'thumbnail_id', true );
				$note  = (string) get_term_meta( $cat->term_id, 'fv_branch_note', true );
				?>
				<li>
					<a class="drawer" href="<?php echo esc_url( get_term_link( $cat ) ); ?>">
						<span class="drawer__handle" aria-hidden="true"></span>
						<?php if ( $thumb ) : ?>
							<span class="drawer__media"><?php echo wp_get_attachment_image( $thumb, 'woocommerce_thumbnail', false, array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ); ?></span>
						<?php endif; ?>
						<b><?php echo esc_html( $cat->name ); ?></b>
						<span>
							<?php
							/* translators: %s: product count */
							$line = sprintf( __( '%s کد', 'faucets-valves' ), number_format_i18n( $cat->count ) );
							echo esc_html( $note ? $line . '، ' . $note : $line );
							?>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
