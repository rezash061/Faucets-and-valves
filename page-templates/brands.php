<?php
/**
 * Template Name: برندها
 *
 * All WooCommerce brands as a large logo grid (logo, name, product count, short description).
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<div class="container page-wrap brands-page">
		<header class="page-header">
			<h1 class="entry-title"><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p class="page-lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
		</header>
		<?php if ( '' !== trim( get_the_content() ) ) : ?>
			<div class="entry-content"><?php the_content(); ?></div>
		<?php endif; ?>
		<?php
		$brands = fv_brand_terms();
		if ( $brands ) {
			get_template_part( 'template-parts/brand-grid', null, array( 'brands' => $brands, 'large' => true ) );
		} else {
			echo '<p class="shop-empty">' . esc_html__( 'برندی ثبت نشده است. از محصولات › برندها اضافه کنید.', 'faucets-valves' ) . '</p>';
		}
		?>
	</div>
	<?php
endwhile;

get_footer();
