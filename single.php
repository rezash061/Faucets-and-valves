<?php
/**
 * Single article: light header (breadcrumb, category chip, title, meta), the featured image in a
 * rounded frame (Material Reveal), reading column, tags, related guides.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$cats = get_the_category();
	?>
	<article <?php post_class( 'article' ); ?>>
		<header class="container article__head">
			<?php
			if ( function_exists( 'woocommerce_breadcrumb' ) ) {
				woocommerce_breadcrumb();
			}
			?>
			<p class="chip-label"><?php echo esc_html( $cats ? $cats[0]->name : __( 'مقاله', 'faucets-valves' ) ); ?></p>
			<h1 class="article__title"><?php the_title(); ?></h1>
			<p class="article__meta">
				<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
				<span aria-hidden="true">·</span>
				<span>
					<?php
					/* translators: %s: minutes */
					echo esc_html( sprintf( __( '%s دقیقه مطالعه', 'faucets-valves' ), number_format_i18n( fv_reading_time() ) ) );
					?>
				</span>
			</p>
		</header>
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="container">
				<figure class="article__figure material" data-motion="material">
					<?php the_post_thumbnail( 'fv-hero', array( 'alt' => '', 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 1440px) 100vw, 1440px' ) ); ?>
				</figure>
			</div>
		<?php endif; ?>

		<div class="container article__body">
			<?php if ( has_excerpt() ) : ?>
				<p class="article__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
			<div class="entry-content article__content"><?php the_content(); ?></div>
			<?php wp_link_pages(); ?>

			<?php $tags = get_the_tags(); ?>
			<?php if ( $tags ) : ?>
				<ul class="article__tags" aria-label="<?php esc_attr_e( 'برچسب‌ها', 'faucets-valves' ); ?>">
					<?php foreach ( $tags as $tag ) : ?>
						<li><a href="<?php echo esc_url( get_tag_link( $tag ) ); ?>">#<?php echo esc_html( $tag->name ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</article>

	<?php
	$related = fv_related_posts( get_the_ID(), 3 );
	if ( $related->have_posts() ) :
		?>
		<section class="section section--catalog related-posts" aria-labelledby="related-posts-title">
			<div class="container">
				<div class="section-head"><h2 id="related-posts-title"><?php esc_html_e( 'راهنماهای مرتبط', 'faucets-valves' ); ?></h2></div>
				<ul class="post-grid" data-motion="stagger">
					<?php
					while ( $related->have_posts() ) :
						$related->the_post();
						get_template_part( 'template-parts/post-card' );
					endwhile;
					wp_reset_postdata();
					?>
				</ul>
			</div>
		</section>
		<?php
	endif;

	if ( comments_open() || get_comments_number() ) :
		?>
		<div class="container article__comments"><?php comments_template(); ?></div>
		<?php
	endif;
endwhile;

get_footer();
