<?php
/**
 * Latest three guides/articles.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

$journal = new WP_Query( array(
	'post_type'           => 'post',
	'posts_per_page'      => 3,
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
) );
if ( ! $journal->have_posts() ) {
	return;
}
$blog = get_option( 'page_for_posts' ) ? get_permalink( get_option( 'page_for_posts' ) ) : '';
?>
<section class="section section--catalog journal" aria-labelledby="journal-title">
	<div class="container">
		<div class="section-head">
			<div>
				<h2 id="journal-title"><?php esc_html_e( 'راهنماها و مقالات', 'faucets-valves' ); ?></h2>
				<p><?php esc_html_e( 'انتخاب، نصب و نگهداری شیرآلات.', 'faucets-valves' ); ?></p>
			</div>
			<?php if ( $blog ) : ?>
				<a class="link-arrow" href="<?php echo esc_url( $blog ); ?>"><?php esc_html_e( 'همهٔ مقالات', 'faucets-valves' ); ?> <?php echo fv_icon( 'arrow' ); // phpcs:ignore ?></a>
			<?php endif; ?>
		</div>
		<ul class="post-grid" data-motion="stagger">
			<?php
			while ( $journal->have_posts() ) :
				$journal->the_post();
				get_template_part( 'template-parts/post-card' );
			endwhile;
			wp_reset_postdata();
			?>
		</ul>
	</div>
</section>
