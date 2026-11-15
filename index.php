<?php
/**
 * Fallback template: blog, archives, search, single posts.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( ! is_singular() ) {
	if ( is_search() ) {
		/* translators: %s: search query */
		$fv_title = sprintf( __( 'نتایج جستجو برای «%s»', 'faucets-valves' ), get_search_query() );
		$fv_lead  = '';
	} elseif ( is_archive() ) {
		$fv_title = wp_strip_all_tags( get_the_archive_title() );
		$fv_lead  = wp_strip_all_tags( get_the_archive_description() );
	} else {
		$fv_title = __( 'مقالات و راهنماها', 'faucets-valves' );
		$fv_lead  = __( 'راهنمای انتخاب، نصب و نگهداری شیرآلات برای خریداران و پیمانکاران.', 'faucets-valves' );
	}
	fv_page_head( array(), array( 'title' => $fv_title, 'lead' => $fv_lead ) );
}
?>
<div class="container page-wrap<?php echo is_singular() ? '' : ' page-wrap--page'; ?>">
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
			<div class="empty-state">
				<p class="empty-state__title"><?php esc_html_e( 'چیزی پیدا نشد', 'faucets-valves' ); ?></p>
				<p><?php esc_html_e( 'واژهٔ دیگری را امتحان کنید، یا نام و کد کالا را در محصولات جستجو کنید.', 'faucets-valves' ); ?></p>
				<?php get_search_form(); ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
<?php
get_footer();
