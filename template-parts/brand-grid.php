<?php
/**
 * Grid of brand logos (or names when a brand has no logo), linking to each brand's products.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

$brands = $args['brands'] ?? array();
$large  = ! empty( $args['large'] );
if ( ! $brands ) {
	return;
}
?>
<ul class="brand-grid<?php echo $large ? ' brand-grid--large' : ''; ?>" data-motion="stagger">
	<?php foreach ( $brands as $brand ) : ?>
		<?php $logo = (int) get_term_meta( $brand->term_id, 'thumbnail_id', true ); ?>
		<li>
			<a class="brand-tile" href="<?php echo esc_url( get_term_link( $brand ) ); ?>">
				<span class="brand-tile__logo">
					<?php
					if ( $logo ) {
						echo wp_get_attachment_image( $logo, 'medium', false, array( 'alt' => $brand->name, 'loading' => 'lazy' ) );
					} else {
						echo '<span class="brand-tile__name">' . esc_html( $brand->name ) . '</span>';
					}
					?>
				</span>
				<?php if ( $large ) : ?>
					<span class="brand-tile__meta">
						<b><?php echo esc_html( $brand->name ); ?></b>
						<span>
							<?php
							/* translators: %s: product count */
							echo esc_html( sprintf( __( '%s محصول', 'faucets-valves' ), number_format_i18n( $brand->count ) ) );
							?>
						</span>
					</span>
					<?php if ( $brand->description ) : ?>
						<span class="brand-tile__desc"><?php echo esc_html( wp_trim_words( $brand->description, 18 ) ); ?></span>
					<?php endif; ?>
				<?php else : ?>
					<span class="sr-only"><?php echo esc_html( $brand->name ); ?></span>
				<?php endif; ?>
			</a>
		</li>
	<?php endforeach; ?>
</ul>
