<?php
/**
 * Standard page. Cart, checkout and account pages use this too (each WooCommerce screen draws its own
 * heading); every other page gets the shared plaster page header with the title card.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

get_header();

$is_wc_page = function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() );

while ( have_posts() ) :
	the_post();
	if ( ! $is_wc_page ) {
		fv_page_head();
	}
	?>
	<div class="container page-wrap<?php echo $is_wc_page ? ' page-wrap--wc' : ' page-wrap--page'; ?>">
		<article <?php post_class( 'entry' ); ?>>
			<?php if ( $is_wc_page ) : ?>
				<header class="entry-header">
					<h1 class="entry-title"><?php the_title(); ?></h1>
				</header>
			<?php endif; ?>
			<div class="entry-content"><?php the_content(); ?></div>
			<?php wp_link_pages(); ?>
		</article>
	</div>
	<?php
endwhile;

get_footer();
