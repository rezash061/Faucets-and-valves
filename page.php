<?php
/**
 * Standard page (catalog surface). Cart, checkout and account pages use this too.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

get_header();

$is_wc_page = function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() );
?>
<div class="container page-wrap<?php echo $is_wc_page ? ' page-wrap--wc' : ''; ?>">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class( 'entry' ); ?>>
			<header class="entry-header">
				<h1 class="entry-title"><?php the_title(); ?></h1>
			</header>
			<div class="entry-content"><?php the_content(); ?></div>
		</article>
	<?php endwhile; ?>
</div>
<?php
get_footer();
