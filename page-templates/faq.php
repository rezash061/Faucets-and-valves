<?php
/**
 * Template Name: پرسش‌های متداول
 *
 * Write each question as a «جزئیات» (Details) block in the editor: the summary line is the
 * question, the content is the answer. Use H2 headings to group them. Questions are searchable
 * on the page and sent to search engines as FAQ data.
 *
 * @package FaucetsValves
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$items = fv_faq_items( get_post() );
	list( $html, $toc ) = fv_content_toc( apply_filters( 'the_content', get_the_content() ) );
	/* translators: %s: number of questions */
	fv_page_head( array( array( __( 'پرسش', 'faucets-valves' ), sprintf( __( '%s پاسخ', 'faucets-valves' ), number_format_i18n( count( $items ) ) ) ), array( __( 'پاسخ نگرفتید؟', 'faucets-valves' ), __( 'با ما تماس بگیرید', 'faucets-valves' ) ) ) );
	?>
	<div class="container info-layout<?php echo $toc ? '' : ' info-layout--plain'; ?>">
		<?php if ( $toc ) : ?>
			<nav class="info-toc" aria-label="<?php esc_attr_e( 'موضوع‌ها', 'faucets-valves' ); ?>">
				<p class="info-toc__title"><?php esc_html_e( 'موضوع‌ها', 'faucets-valves' ); ?></p>
				<ol>
					<?php foreach ( $toc as $item ) : ?>
						<li><a href="#<?php echo esc_attr( $item['id'] ); ?>"><?php echo esc_html( $item['text'] ); ?></a></li>
					<?php endforeach; ?>
				</ol>
			</nav>
		<?php endif; ?>
		<div class="faq">
			<div class="faq__search" hidden>
				<label class="sr-only" for="faq-q"><?php esc_html_e( 'جستجو در پرسش‌ها', 'faucets-valves' ); ?></label>
				<?php echo fv_icon( 'search' ); // phpcs:ignore ?>
				<input id="faq-q" type="search" placeholder="<?php esc_attr_e( 'جستجو در پرسش‌ها، مثلاً «ارسال»', 'faucets-valves' ); ?>" autocomplete="off">
			</div>
			<p class="faq__none" hidden role="status"><?php esc_html_e( 'پرسشی با این عبارت پیدا نشد. از برگهٔ تماس بپرسید.', 'faucets-valves' ); ?></p>
			<article class="info-sheet entry-content faq__body">
				<?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- the_content output. ?>
			</article>
		</div>
	</div>
	<?php
endwhile;

get_footer();
