<?php
/**
 * Template Name: نمایشگاه (زمینهٔ تیره)
 *
 * Dark showroom surface for brand and about pages. The featured image becomes a slim
 * Cinematic Reveal hero.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<section class="hero hero--slim" data-motion="cinematic" aria-labelledby="page-hero-title">
		<div class="hero__mask">
			<?php
			if ( has_post_thumbnail() ) {
				the_post_thumbnail( 'fv-hero', array(
					'class'         => 'hero__img',
					'loading'       => 'eager',
					'fetchpriority' => 'high',
					'sizes'         => '100vw',
				) );
			} else {
				echo '<div class="hero__img hero__img--placeholder" aria-hidden="true"></div>';
			}
			?>
		</div>
		<div class="hero__shade" aria-hidden="true"></div>
		<div class="container hero__content">
			<h1 class="hero__title" id="page-hero-title"><?php echo fv_split_words( get_the_title() ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p class="hero__sub"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<div class="container page-wrap">
		<div class="entry-content entry-content--showroom"><?php the_content(); ?></div>
	</div>
	<?php
endwhile;

get_footer();
