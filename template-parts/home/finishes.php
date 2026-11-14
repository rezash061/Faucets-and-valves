<?php
/**
 * "One design, several finishes": clay cards on the plaster ground, each holding one small round
 * metal sample (the only real-metal colour outside product photos), linking to the shop filtered by
 * that finish. Needs the global attribute "finish" (pa_finish).
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

if ( ! taxonomy_exists( 'pa_finish' ) || ! function_exists( 'wc_get_page_permalink' ) ) {
	return;
}
$terms = get_terms( array( 'taxonomy' => 'pa_finish', 'hide_empty' => true, 'number' => 5, 'orderby' => 'count', 'order' => 'DESC' ) );
if ( is_wp_error( $terms ) || count( $terms ) < 2 ) {
	return;
}
$shop = wc_get_page_permalink( 'shop' );
?>
<section class="section section--plaster finishes" aria-labelledby="finishes-title">
	<div class="container">
		<div class="section-head">
			<div>
				<h2 id="finishes-title"><?php echo esc_html( fv_mod( 'fv_finishes_title' ) ); ?></h2>
				<p><?php echo esc_html( fv_mod( 'fv_finishes_sub' ) ); ?></p>
			</div>
		</div>
		<ul class="finish-tiles" data-motion="stagger">
			<?php foreach ( $terms as $term ) : ?>
				<?php
				$swatch = fv_finish_swatch( $term->name );
				$url    = add_query_arg( array( 'filter_finish' => rawurldecode( $term->slug ), 'query_type_finish' => 'or' ), $shop );
				?>
				<li>
					<a class="finish-tile" href="<?php echo esc_url( $url ); ?>">
						<span class="finish-tile__sample surface--<?php echo esc_attr( $swatch ? $swatch : 'steel' ); ?>" aria-hidden="true"></span>
						<span class="finish-tile__label">
							<b><?php echo esc_html( $term->name ); ?></b>
							<span>
								<?php
								/* translators: %s: product count */
								echo esc_html( sprintf( __( '%s محصول', 'faucets-valves' ), number_format_i18n( $term->count ) ) );
								?>
							</span>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
