<?php
/**
 * Template Name: نمایشگاه (بخش‌های تمام‌عرض)
 *
 * Showroom page for brand and about pages: the plaster page header with the title card beside the
 * featured image (rounded photo), then wide content built from the «شیرآلات — نمایشگاه» block
 * patterns (intro, numbers, values, call to action). Same light clay surfaces as the rest of the site.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	fv_page_head( array(), array( 'image' => (int) get_post_thumbnail_id() ) );
	?>
	<div class="container page-wrap page-wrap--showroom">
		<div class="entry-content entry-content--showroom"><?php the_content(); ?></div>
	</div>
	<?php
endwhile;

get_footer();
