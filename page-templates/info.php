<?php
/**
 * Template Name: صفحهٔ اطلاعات (ارسال، مرجوعی، شرایط)
 *
 * Long policy text set like a drawing sheet: a title block with the last revision date, a table
 * of contents built from the page's H2 headings, and the text on paper.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	list( $html, $toc ) = fv_content_toc( apply_filters( 'the_content', get_the_content() ) );
	$date = function_exists( 'fv_jalali_date' ) ? fv_jalali_date( (int) get_post_modified_time( 'U', true ) ) : get_the_modified_date();
	$cells = array( array( __( 'آخرین بازبینی', 'faucets-valves' ), $date ) );
	if ( $toc ) {
		/* translators: %s: number of sections */
		$cells[] = array( __( 'بخش‌ها', 'faucets-valves' ), sprintf( __( '%s بخش', 'faucets-valves' ), number_format_i18n( count( $toc ) ) ) );
	}
	fv_page_head( $cells );
	?>
	<div class="container info-layout<?php echo $toc ? '' : ' info-layout--plain'; ?>">
		<?php if ( $toc ) : ?>
			<nav class="info-toc" aria-label="<?php esc_attr_e( 'فهرست این صفحه', 'faucets-valves' ); ?>">
				<p class="info-toc__title"><?php esc_html_e( 'در این صفحه', 'faucets-valves' ); ?></p>
				<ol>
					<?php foreach ( $toc as $item ) : ?>
						<li><a href="#<?php echo esc_attr( $item['id'] ); ?>"><?php echo esc_html( $item['text'] ); ?></a></li>
					<?php endforeach; ?>
				</ol>
			</nav>
		<?php endif; ?>
		<article class="info-sheet entry-content">
			<?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- the_content output. ?>
		</article>
	</div>
	<?php
endwhile;

get_footer();
