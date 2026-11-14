<?php
/**
 * Fallback template: blog, archives, search, single posts.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="container page-wrap">
	<?php if ( is_singular() ) : ?>
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class( 'entry' ); ?>>
				<header class="entry-header">
					<h1 class="entry-title"><?php the_title(); ?></h1>
					<?php if ( 'post' === get_post_type() ) : ?>
						<p class="entry-meta"><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></p>
					<?php endif; ?>
				</header>
				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="entry-thumb material" data-motion="material"><?php the_post_thumbnail( 'large' ); ?></figure>
				<?php endif; ?>
				<div class="entry-content"><?php the_content(); ?></div>
				<?php wp_link_pages(); ?>
			</article>
			<?php
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
		endwhile;
		?>
	<?php else : ?>
		<header class="page-header">
			<h1 class="entry-title">
				<?php
				if ( is_search() ) {
					/* translators: %s: search query */
					printf( esc_html__( 'نتایج جستجو برای «%s»', 'faucets-valves' ), esc_html( get_search_query() ) );
				} elseif ( is_archive() ) {
					echo wp_kses_post( get_the_archive_title() );
				} else {
					esc_html_e( 'مقالات و راهنماها', 'faucets-valves' );
				}
				?>
			</h1>
		</header>

		<?php if ( have_posts() ) : ?>
			<ul class="post-grid" data-motion="stagger">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/post-card' );
				endwhile;
				?>
			</ul>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'چیزی پیدا نشد.', 'faucets-valves' ); ?></p>
		<?php endif; ?>
	<?php endif; ?>
</div>
<?php
get_footer();
