<?php
/**
 * Front page: 3D pipe manifold (categories), exploded faucet, then the classic sections. Sections can be switched on/off in
 * Customizer › تنظیمات قالب شیرآلات › بخش‌های صفحهٔ نخست.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

get_header();

$has_wc = class_exists( 'WooCommerce' );

// Without WooCommerce there are no categories to draw, so fall back to the classic hero.
if ( ! $has_wc ) {
	get_template_part( 'template-parts/hero' );
}

foreach ( array_keys( fv_home_sections() ) as $key ) {
	if ( ! fv_home_on( $key ) ) {
		continue;
	}
	switch ( $key ) {
		case 'manifold':
		case 'anatomy':
			if ( $has_wc ) {
				get_template_part( "template-parts/scene/{$key}" );
			}
			break;
		case 'categories':
			if ( $has_wc ) {
				get_template_part( 'template-parts/category-tiles' );
			}
			break;
		case 'best':
		case 'new':
			if ( $has_wc ) {
				get_template_part( 'template-parts/home/products', null, array( 'type' => $key ) );
			}
			break;
		default:
			get_template_part( "template-parts/home/{$key}" );
	}
}

// Page content from the editor, if a static front page is set and has any.
while ( is_page() && have_posts() ) :
	the_post();
	if ( '' !== trim( get_the_content() ) ) :
		?>
		<section class="section section--catalog">
			<div class="container entry-content"><?php the_content(); ?></div>
		</section>
		<?php
	endif;
endwhile;

get_footer();
