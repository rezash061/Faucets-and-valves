<?php
/**
 * Template Name: درباره ما
 *
 * The page text on paper, followed by the home page numbers and the brands strip
 * (both set in Customizer › تنظیمات قالب شیرآلات).
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	fv_page_head( array(
		array( __( 'فروش', 'faucets-valves' ), __( 'عمده و جزئی', 'faucets-valves' ) ),
		array( __( 'ارسال', 'faucets-valves' ), __( 'سراسر ایران', 'faucets-valves' ) ),
		array( __( 'ضمانت', 'faucets-valves' ), __( 'اصالت کالا', 'faucets-valves' ) ),
	) );
	?>
	<div class="container info-layout info-layout--plain">
		<article class="info-sheet info-sheet--wide entry-content">
			<?php the_content(); ?>
		</article>
	</div>
	<?php
	get_template_part( 'template-parts/home/stats' );
	if ( function_exists( 'fv_brand_terms' ) && fv_brand_terms() ) {
		get_template_part( 'template-parts/home/brands' );
	}
endwhile;

get_footer();
