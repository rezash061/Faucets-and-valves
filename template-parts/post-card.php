<?php
/**
 * Article card (inside a <ul data-motion="stagger">).
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

$cats = get_the_category();
?>
<li <?php post_class( 'post-card' ); ?>>
	<a href="<?php the_permalink(); ?>" class="post-card__link">
		<span class="post-card__media material" data-motion="material">
			<?php
			if ( has_post_thumbnail() ) {
				the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy', 'alt' => '' ) );
			} else {
				// No featured image: a single drop of water on the clay, so the card isn't an empty box.
				echo '<svg class="post-card__ph" viewBox="0 0 48 64" aria-hidden="true"><path d="M24 4C24 4 6 26 6 40a18 18 0 0 0 36 0C42 26 24 4 24 4Z"/><path d="M15 42a9 9 0 0 0 9 9"/></svg>';
			}
			?>
		</span>
		<span class="post-card__meta">
			<?php if ( $cats ) : ?>
				<span class="post-card__cat"><?php echo esc_html( $cats[0]->name ); ?></span>
			<?php endif; ?>
			<span>
				<?php
				/* translators: %s: minutes */
				echo esc_html( sprintf( __( '%s دقیقه مطالعه', 'faucets-valves' ), number_format_i18n( fv_reading_time() ) ) );
				?>
			</span>
		</span>
		<span class="post-card__title"><?php the_title(); ?></span>
		<span class="post-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?></span>
	</a>
</li>
