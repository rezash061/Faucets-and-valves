<?php
/**
 * Top-level product categories as clay "room blocks": each sits on its room's floor tint from the
 * 3D house and takes the full tint on hover. Uses each category's thumbnail (Products › Categories).
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
			<a class="link-more" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'همهٔ محصولات', 'faucets-valves' ); ?></a>
		</div>

		<ul class="drawers" data-motion="stagger">
			<?php foreach ( $cats as $cat ) : ?>
				<?php
				$thumb = (int) get_term_meta( $cat->term_id, 'thumbnail_id', true );
				$note  = (string) get_term_meta( $cat->term_id, 'fv_branch_note', true );
				?>
				<li>
					<a class="drawer <?php echo esc_attr( fv_room_class( fv_term_room( $cat ) ) ); ?>" href="<?php echo esc_url( get_term_link( $cat ) ); ?>">
						<span class="drawer__media" aria-hidden="true">
							<?php if ( $thumb ) : ?>
								<?php echo wp_get_attachment_image( $thumb, 'woocommerce_thumbnail', false, array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
							<?php endif; ?>
						</span>
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
